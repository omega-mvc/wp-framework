<?php

declare(strict_types=1);

namespace Omega\Database\Schema;

use function explode;

class ForeignIdColumnDefinition extends ColumnDefinition
{
    /** @var Blueprint The schema builder blueprint instance. */
    protected Blueprint $blueprint;

    /**
     * Create a new foreign ID column definition.
     *
     * @param Blueprint $blueprint
     * @param array $attributes
     */
    public function __construct(Blueprint $blueprint, array $attributes = [])
    {
        parent::__construct($attributes);

        $this->blueprint = $blueprint;
    }

    /**
     * Create a foreign key constraint on this column referencing the "id" column of the conventionally related table.
     *
     * @param string|null $table
     * @param string|null $column
     * @param string|null $indexName
     * @return ForeignKeyDefinition
     */
    public function constrained(
        ?string $table = null,
        ?string $column = null,
        ?string $indexName = null
    ): ForeignKeyDefinition {
        $table ??= $this->getTableByColumn($this->name);
        $column ??= 'id';

        return $this->references($column, $indexName)->on($table);
    }

    public function getTableByColumn(string $column): string
    {
        $parts = explode('_', $column);

        return "{$parts[0]}s";
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @param string $column
     * @param string|null $indexName
     * @return ForeignKeyDefinition
     */
    public function references(string $column, ?string $indexName = null): ForeignKeyDefinition
    {
        return $this->blueprint->foreign($this->name, $indexName)->references($column);
    }
}
