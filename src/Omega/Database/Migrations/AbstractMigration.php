<?php

declare(strict_types=1);

namespace Omega\Database\Migrations;

abstract class AbstractMigration implements MigrationInterface
{
    protected string $oldVersion;

    /**
     * Run the migrations.
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    abstract public function down(): void;

    public function setOldVersion($version): void
    {
        $this->oldVersion = $version;
    }
}
