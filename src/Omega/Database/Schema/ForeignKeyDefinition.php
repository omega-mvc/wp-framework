<?php

declare(strict_types=1);

namespace Omega\Database\Schema;

use function md5;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;

class ForeignKeyDefinition
{
    public function __construct(protected Blueprint $blueprint, protected array $attributes = [])
    {
    }

    public function references(string $column): static
    {
        $this->attributes['references'] = $column;

        return $this;
    }

    public function on(string $table): static
    {
        $this->attributes['on'] = $table;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Specify the action to take when the referenced row is deleted.
     *
     * @param string $action 'cascade', 'set null', 'restrict', etc.
     * @return $this
     */
    public function onDelete(string $action): static
    {
        $this->attributes['onDelete'] = $action;

        return $this;
    }

    public function getForeignKeySql(): string
    {
        global $wpdb;

        $column = $this->attributes['name'] ?? null;
        $references = $this->attributes['references'] ?? null;
        $table = $this->attributes['on'] ?? null;
        $onDelete = $this->attributes['onDelete'] ?? null;

        if (!$column || !$references || !$table) {
            return '';
        }

        $constraintName = sprintf(
            '%s_%s_foreign',
            $wpdb->prefix . $this->blueprint->getTable(),
            $column
        );

        if (strlen($constraintName) > 64) {
            $hash = substr(md5($constraintName), 0, 8);
            $base = substr($constraintName, 0, 55);
            $constraintName = $base . '_' . $hash;
        }

        $sql = sprintf(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)',
            $constraintName,
            $column,
            $wpdb->prefix . $table,
            $references
        );

        if ($onDelete) {
            $sql .= ' ON DELETE ' . strtoupper($onDelete);
        }

        return $sql;
    }
}
