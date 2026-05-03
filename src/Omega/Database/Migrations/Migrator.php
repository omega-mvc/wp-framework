<?php

declare(strict_types=1);

namespace Omega\Database\Migrations;

use Omega\Application\Application;
use Omega\Database\Database;
use Omega\Database\Schema\Blueprint;
use Omega\Database\Schema\Schema;

use function array_merge;
use function basename;
use function current_time;
use function file_exists;
use function get_option;
use function glob;
use function in_array;
use function method_exists;

class Migrator
{
    /**
     * The id of the migration.
     *
     * @var string|array
     */
    protected string|array $prefix;

    protected string $path;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;


    protected string $tableName;

    protected mixed $oldVersion;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->prefix = $app->getIdAsUnderscore();
        $this->path = $app->getBasePath();
        $this->tableName = "{$this->prefix}_migrations";
        $this->oldVersion = get_option("{$this->prefix}_version", $app->version());
    }

    public function maybeCreateMigrationsTable(): void
    {
        if (Database::tableExists($this->tableName)) {
            return;
        }

        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id('id');
            $table->string('name');
            $table->string('file');
            $table->timestamps();
        });
    }

    public function processMigrationFile(string $file): bool
    {
        /** @var AbstractMigration $migration */
        $migration = require $file;

        if (method_exists($migration, 'up')) {
            $migration->setOldVersion($this->oldVersion);
            $migration->up();
            return true;
        }

        return false;
    }

    public function run()
    {
        $files = glob("$this->path/database/migrations/*.php");

        if (!empty($this->app->getMigrationFolders()) && is_array($this->app->getMigrationFolders())) {
            foreach ($this->app->getMigrationFolders() as $folder) {
                $extraFiles = glob("$folder/*.php");
                if ($extraFiles) {
                    $files = array_merge($files, $extraFiles);
                }
            }
        }

        if (!$files) {
            return;
        }

        $this->maybeCreateMigrationsTable();

        $model = Database::table($this->tableName);

        $migrations = $model->select('name')->get()->pluck('name')->toArray();
        $applied = [];

        foreach ($files as $file) {
            $migrationId = basename($file, '.php');

            if (in_array($migrationId, $migrations, true)) {
                continue;
            }

            $migrated = $this->processMigrationFile($file);

            if ($migrated) {
                $migrations[] = $migrationId;
                $applied[]    = $migrationId;

                Database::insert(Database::getTableName($this->tableName), [
                    'name'       => $migrationId,
                    'file'       => $file,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ]);
            }
        }

        return $applied;
    }

    public function fresh(): ?array
    {
        $model = Database::table($this->tableName);

        $migrations = $model->get();

        if (!$migrations->isEmpty()) {
            foreach ($migrations as $mg) {
                if (file_exists($mg->file)) {
                    $migration = require_once $mg->file;

                    if (method_exists($migration, 'down')) {
                        $migration->down();
                        $success[] = $mg->name;
                        $model->where(['id' => $mg->id])->delete();
                    }
                }
            }
        }

        return $this->run();
    }
}
