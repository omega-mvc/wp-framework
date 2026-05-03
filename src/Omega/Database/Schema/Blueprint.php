<?php

declare(strict_types=1);

namespace Omega\Database\Schema;

use Omega\Collection\Collection;

use function array_merge;
use function compact;
use function count;
use function implode;
use function in_array;

class Blueprint
{
    /** @var ColumnDefinition[] The columns that should be added to the table. */
    protected array $columns = [];

    /** @var string The command that should be executed on the table. */
    protected string $command = 'alter';

    /** @var array The commands that should be executed on the table. */
    protected array $commands = [];

    public function __construct(protected string $table)
    {
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column)->primary();
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return void
     */
    public function setCreate(): void
    {
        $this->command = 'create';
    }

    /**
     * Generate single column sql.
     *
     * @param ColumnDefinition $column
     * @return string
     *
     */
    private function generateSingleColumnSql(ColumnDefinition $column): string
    {
        $type = $column->getType();
        $name = $column->getName();
        $sql = "`$name`";

        switch ($type) {
            case 'bigInteger':
                $sql .= ' bigint(20)';
                break;
            case 'integer':
                $sql .= ' int(11)';
                break;
            case 'boolean':
                $sql .= ' tinyint(1)';
                break;
            case 'string':
                $length = $column->length ?? 255;
                $sql .= " varchar($length)";
                break;
            case 'timestamp':
                $sql .= ' timestamp';
                // Fractional seconds support can be added here if needed
                break;
            case 'dateTime':
                $sql .= ' datetime';
                // Fractional seconds support can be added here if needed
                break;
            case 'text':
                $sql .= ' text';
                break;
            case 'longText':
                $sql .= ' longtext';
                break;
            case 'json':
                $sql .= ' json';
                break;
            default:
                $sql .= ' text';
        }

        if (in_array($type, ['integer', 'bigInteger'], true) && $column->isUnsigned()) {
            $sql .= ' unsigned';
        }

        $sql .= $column->isNullable() ? ' DEFAULT NULL' : " NOT NULL";

        if (!$column->isNullable() && $column->getDefault() !== null) {
            $sql .= " DEFAULT '" . esc_sql($column->getDefault()) . "'";
        }

        if (
            $column->isAutoIncrement()
            && in_array($type, ['bigInteger', 'unsignedBigInteger', 'bigIncrements'], true)
        ) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }

    private function prepareColumns(): array
    {
        $columnsSql = [];
        $primaryKey = [];
        $uniqueKeys = [];
        $indexKeys = [];
        foreach ($this->columns as $column) {
            $columnsSql[] = $this->generateSingleColumnSql($column);

            if ($column->isPrimary()) {
                $primaryKey[] = $column->getName();
            }

            if ($column->isUnique()) {
                $uniqueKeys[] = $column->getName();
            }

            if ($column->isIndex() && !$column->isUnique() && !$column->isPrimary()) {
                $indexKeys[] = $column->getName();
            }
        }

        if (!empty($uniqueKeys)) {
            foreach ($uniqueKeys as $uniqueKey) {
                $columnsSql[] = "UNIQUE KEY (`$uniqueKey`)";
            }
        }

        if (!empty($indexKeys)) {
            foreach ($indexKeys as $indexKey) {
                $columnsSql[] = "KEY (`$indexKey`)";
            }
        }

        if (!empty($primaryKey)) {
            $columnsSql[] = "PRIMARY KEY (" . implode(', ', $primaryKey) . ")";
        }

        if (!empty($this->commands)) {
            foreach ($this->commands as $command) {
                if ($command instanceof ForeignKeyDefinition) {
                    $columnsSql[] = $command->getForeignKeySql();
                }
            }
        }

        return $columnsSql;
    }

    public function tableExists(string $tableName): bool
    {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName));

        return $exists !== null;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE %s", $columnName));

        return $exists !== null;
    }

    public function run(): void
    {
        global $wpdb;

        if ($this->command === 'create') {
            $tableName = $wpdb->prefix . $this->table;

            if ($this->tableExists($tableName)) {
                return;
            }

            $columnsSql = $this->prepareColumns();

            $columnsDef = implode(",\n  ", $columnsSql);

            $sql = "CREATE TABLE `$tableName` (\n  $columnsDef\n) {$wpdb->get_charset_collate()};";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $wpdb->query($sql);
        } else {
            $tableName = $wpdb->prefix . $this->table;

            foreach ($this->columns as $column) {
                $columnName = $column->getName();

                if (!$this->columnExists($tableName, $columnName)) {
                    $columnSql = $this->generateSingleColumnSql($column);

                    $afterColumn = $column->getAfter();
                    $sql = "
                        ALTER TABLE `$tableName` ADD $columnSql" . ($afterColumn ? " AFTER `$afterColumn`" : "") . ";
                        ";
                    $wpdb->query($sql);
                }
            }
        }
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param int|null $precision
     * @return Collection<int, ColumnDefinition>
     */
    public function timestamps(?int $precision = null): Collection
    {
        return new Collection([
            $this->dateTime('created_at', $precision)->nullable(),
            $this->dateTime('updated_at', $precision)->nullable(),
        ]);
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param string $column
     * @param int|null $precision
     * @return ColumnDefinition
     */
    public function timestamp(string $column, ?int $precision = null): ColumnDefinition
    {
        $precision ??= $this->defaultTimePrecision();

        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param string $column
     * @param int|null $precision
     * @return ColumnDefinition
     */
    public function dateTime(string $column, ?int $precision = null): ColumnDefinition
    {
        $precision ??= $this->defaultTimePrecision();

        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn('longText', $column);
    }

    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn('json', $column);
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    public function uuid(string $column): ColumnDefinition
    {
        return $this->addColumn('string', $column, ['length' => 36]);
    }

    /**
     * Get the default time precision.
     */
    protected function defaultTimePrecision(): ?int
    {
        return 0;
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new string column on the table.
     *
     * @param string $column
     * @param int|null $length
     * @return ColumnDefinition
     */
    public function string(string $column, ?int $length = null): ColumnDefinition
    {
        $length = $length ?: 255;

        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return ForeignKeyDefinition
     */
    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $foreignInstance = $this->columns[count($this->columns) - 1];

        if ($foreignInstance instanceof ForeignIdColumnDefinition) {
            $command = new ForeignKeyDefinition($this, $foreignInstance->getAttributes());
            $this->commands[] = $command;
            return $command;
        }

        return new ForeignKeyDefinition($this, [
            'columns'   => $columns,
            'name'      => $name,
            'blueprint' => $this,
        ]);
    }

    protected function indexCommand($type, $columns, $index, $algorithm = null): static
    {
        // implementation of indexCommand
        return $this;
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ForeignIdColumnDefinition|ColumnDefinition
     */
    public function foreignId(string $column): ForeignIdColumnDefinition|ColumnDefinition
    {
        return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
            'type'          => 'bigInteger',
            'name'          => $column,
            'autoIncrement' => false,
            'unsigned'      => true,
        ]));
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnDefinition
     */
    public function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        return $this->addColumnDefinition(new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        ));
    }

    /**
     * Add a new column definition to the blueprint.
     *
     * @param ColumnDefinition $definition
     * @return ColumnDefinition
     */
    protected function addColumnDefinition(ColumnDefinition $definition): ColumnDefinition
    {
        $this->columns[] = $definition;

        return $definition;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function dropColumn(string $column): static
    {
        $this->commands[] = ['dropColumn', $column];

        return $this;
    }
}
