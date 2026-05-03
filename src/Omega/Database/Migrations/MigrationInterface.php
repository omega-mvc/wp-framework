<?php

declare(strict_types=1);

namespace Omega\Database\Migrations;

interface MigrationInterface
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void;

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void;
}
