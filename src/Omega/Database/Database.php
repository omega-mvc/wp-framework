<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Application\Application;
use Omega\Database\Eloquent\QueryBuilder;
use Omega\Database\Migrations\Migrator;
use ReflectionException;
use wpdb;

use function array_fill;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * Database class
 *
 * This class is responsible for handling all database operations
 */
class Database
{
    /** @var wpdb Wordpress database object. */
    protected wpdb $wpdb;

    /** @var Migrator The migrator instance. */
    protected Migrator $migrator;

    /**
     * Class constructor.
     *
     * @param Application $app
     * @return void
     * @throws ReflectionException
     */
    public function __construct(Application $app)
    {
        global $wpdb;

        $this->wpdb     = $wpdb;
        $this->migrator = $app->make('migrator');
    }

    public function migrator(): Migrator
    {
        return $this->migrator;
    }

    public static function getTableName(string $tableName, string $prefix = ''): string
    {
        global $wpdb;

        return sprintf('%s%s%s', $wpdb->prefix, $prefix, $tableName);
    }

    /**
     * Create or update a table in the database
     *
     * Useful for creating new tables and updating existing tables to a new structure.
     *
     * @param string $tableName
     * @param array  $columns
     * @return void
     */
    public static function createOrUpdateTable(string $tableName, array $columns): void
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $fullTableName  = self::getTableName($tableName);

        $generatedColumns = array_map(function ($columnName, $columnType) {
            return "$columnName $columnType";
        }, array_keys($columns), $columns);

        $sql = "CREATE TABLE $fullTableName (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			" . implode(",\n", $generatedColumns) . ",
			PRIMARY KEY  (id)
		) $charsetCollate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }


    public static function tableExists(string $tableName): ?bool
    {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName));

        return $exists !== null;
    }

    /**
     * Prepare a query
     *
     * @param string $query
     * @param mixed  $args
     * @return string
     */
    public function prepare(string $query, mixed ...$args): string
    {
        return $this->wpdb->prepare($query, $args);
    }


    public static function table(string $table): QueryBuilder
    {
        $model = new DynamicModel([], self::getTableName($table));

        return $model->getQueryBuilder();
    }

    /**
     * Run a query
     *
     * @param string $query
     * @return bool|int
     */
    public function query(string $query): bool|int
    {
        return $this->wpdb->query($query);
    }

    /**
     * Get rows from the database.
     *
     * @param string $query
     * @return object|null
     */
    public function getResults(string $query): ?object
    {
        return $this->wpdb->get_results($query);
    }

    /**
     * Get a single row from the database.
     *
     * @param string $query
     * @return string|null
     *
     */
    public function getVar(string $query): ?string
    {
        return $this->wpdb->get_var($query);
    }

    /**
     * Delete row(s) from the database.
     *
     * @param string     $table
     * @param array      $whereValues
     * @param array|null $whereFormat
     * @return bool|int
     */
    public function delete(string $table, array $whereValues, ?array $whereFormat = null): bool|int
    {
        return $this->wpdb->delete($table, $whereValues, $whereFormat);
    }

    /**
     * Insert data into a table.
     *
     * @param string $table
     * @param array  $data
     * @return bool|int
     */
    public static function insert(string $table, array $data): bool|int
    {
        global $wpdb;

        $inserted = $wpdb->insert($table, $data);
        if (!$inserted) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update data into a table.
     *
     * @param string $table
     * @param array  $data
     * @param array  $whereValues
     * @return bool|int
     */
    public function update(string $table, array $data, array $whereValues): bool|int
    {
        $updated = $this->wpdb->update($table, $data, $whereValues);
        if (!$updated) {
            return false;
        }

        return $updated;
    }

    /**
     * Insert multiple rows into a table.
     *
     * @param string $tableName
     * @param array  $data
     * @return bool|int Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
     *                  affected/selected for all other queries. Boolean false on error.
     */
    public function insertMultiple(string $tableName, array $data): bool|int
    {
        if (empty($data)) {
            return false;
        }

        $firstItem    = $data[0];
        $columns      = array_keys($firstItem);
        $columnsSql   = implode(', ', $columns);
        $values       = [];
        $placeholders = [];

        foreach ($data as $item) {
            foreach ($item as $value) {
                $values[] = $value;
            }

            $placeholders[] = '(' . implode(', ', array_fill(0, count($item), '%s')) . ')';
        }

        $values_sql = implode(', ', $placeholders);

        $sql = "INSERT INTO $tableName ($columnsSql) VALUES $values_sql";

        return $this->wpdb->query($this->wpdb->prepare($sql, $values));
    }
}
