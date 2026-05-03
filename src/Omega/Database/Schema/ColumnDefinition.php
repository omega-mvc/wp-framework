<?php

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Omega\Database\Schema;

use InvalidArgumentException;

use function is_bool;

class ColumnDefinition
{
    /**
     * Indicates if the column is nullable.
     *
     * @var bool
     */
    protected bool $nullable = false;

    /**
     * The name of the column.
     *
     * @var string
     */
    protected string $name;

    /**
     * The type of the column.
     *
     * @var string
     */
    protected string $type;

    /**
     * Indicates if the column is auto-incrementing.
     *
     * @var bool
     */
    protected bool $autoIncrement = false;

    /**
     * Indicates if the column is unsigned.
     *
     * @var bool
     */
    protected bool $unsigned = false;

    protected bool $primary = false;

    protected bool $unique = false;

    protected bool $index = false;

    /**
     * The default value of the column.
     *
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * The column that this column should be placed after.
     *
     * @var string|null
     */
    protected ?string $after = null;

    /**
     * Create a new column definition instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (empty($data['type'])) {
            throw new InvalidArgumentException('Column type is required');
        }

        if (empty($data['name'])) {
            throw new InvalidArgumentException('Column name is required');
        }

        $this->autoIncrement = $data['autoIncrement'] ?? false;
        $this->unsigned = $data['unsigned'] ?? false;
        $this->type = $data['type'];
        $this->name = $data['name'];
    }

    /**
     * Check if the column is nullable.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function nullable(): static
    {
        $this->nullable = true;

        return $this;
    }


    public function isUnsigned()
    {
        return $this->unsigned;
    }

    public function unsigned(): static
    {
        $this->unsigned = true;

        return $this;
    }

    public function isAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * Check if the column is a primary key.
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function primary(): static
    {
        $this->primary = true;

        return $this;
    }

    public function default($value): static
    {
        $this->default = $value;

        return $this;
    }

    public function unique(): static
    {
        $this->unique = true;

        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function index(): static
    {
        $this->index = true;

        return $this;
    }

    public function isIndex(): bool
    {
        return $this->index;
    }

    /**
     * Place the column after another column.
     *
     * @param string $column
     * @return $this
     */
    public function after(string $column): static
    {
        $this->after = $column;

        return $this;
    }

    /**
     * Get the column that this column should be placed after.
     *
     * @return string|null
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Get the type of the column.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the name of the column.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the default value of the column.
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        if (is_bool($this->default)) {
            return $this->default ? 1 : 0;
        }

        return $this->default;
    }

    public function getAttributes(): array
    {
        return [
            'type'          => $this->getType(),
            'name'          => $this->getName(),
            'nullable'      => $this->isNullable(),
            'autoIncrement' => $this->isAutoIncrement(),
            'unsigned'      => $this->isUnsigned(),
            'primary'       => $this->isPrimary(),
            'index'         => $this->isIndex(),
            'after'         => $this->getAfter(),
        ];
    }
}
