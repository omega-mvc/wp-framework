<?php

declare(strict_types=1);

namespace Omega\Database\Schema;

class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $blueprint->setCreate();
        $callback($blueprint);
        $blueprint->run();
    }

    public static function drop(string $table): void
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $blueprint->run();
    }
}
