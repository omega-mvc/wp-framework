<?php

/** @noinspection PhpUnusedParameterInspection */

/** @noinspection PhpUnused */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database\Eloquent;

use Closure;
use Exception;
use Omega\Collection\Collection;
use Omega\Database\Database;
use Omega\Database\Eloquent\Relations\BelongsTo;
use Omega\Database\Eloquent\Relations\HasMany;
use Omega\Database\Eloquent\Relations\HasOne;
use Omega\Database\Eloquent\Relations\AbstractRelation;
use Omega\Paginator\Paginator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function array_fill;
use function array_filter;
use function array_merge;
use function call_user_func;
use function count;
use function current_time;
use function implode;
use function is_array;
use function is_callable;
use function is_string;
use function strtoupper;
use function wp_list_pluck;

/**
 * QueryBuilder class.
 *
 * This class is responsible for building database queries.
 */
class QueryBuilder
{
    /** @var string Table name. */
    protected string $tableName;

    /** @var Database Database instance. */
    protected Database $db;

    /**
     * Where array
     *
     * @var array{
     *      column: string,
     *      value: mixed,
     *      operator: string,
     *      method: string,
     *      table: string
     * }
     */
    protected array $whereArray = [];

    /**
     * Exists array
     *
     * @var array{
     *      sql: string,
     *      method: string
     * }
     */
    protected array $existsArray = [];

    /**
     * Where column array
     *
     * @var array{
     *      column_one: string,
     *      operator: string,
     *      column_two: string,
     *      method: string
     * }
     */
    protected array $whereColumnArray = [];

    /** @var array @var array Join array. */
    protected array $joinArray = [];

    /** @var array Group by array. */
    protected array $groupBy = [];

    /**
     * With array
     *
     * @var array{
     *      @type string $relation,
     *      @type string $table,
     *      @type string $foreign_key,
     *      @type string $local_key,
     *      @type AbstractModel $model ,
     *      @type AbstractRelation $relation_type
     * }
     */
    protected array $withArray = [];

    /** @var array Order by array. */
    protected array $orderBy = [];

    /** @var string  */
    private string $select = '*';

    /** @var int Limit. */
    private int $limit;

    /** @var int Offset. */
    private int $offset;

    public function __construct(protected AbstractModel $model)
    {
        $this->db        = $model->getDatabase();
        $this->tableName = $model->getTableName();

        if ($model->trashed()) {
            $this->whereArray[] = ['column' => 'deleted_at', 'value' => '!#####NULL#####!', 'operator' => 'IS'];
        }
    }

    /**
     * Select columns
     *
     * @param array|string $columns
     * @return QueryBuilder
     */
    public function select(array|string $columns): QueryBuilder
    {
        $this->select = is_array($columns)
            ? implode(', ', $columns)
            : $columns;

        return $this;
    }

    public function find($id): ?AbstractModel
    {
        return $this->where($this->model->primaryKey, $id)->first();
    }

    public function whereNull(string $column): static
    {
        $this->where($column, 'IS', '!#####NULL#####!');

        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->where($column, 'IS NOT', '!#####NULL#####!');

        return $this;
    }


    /**
     * @param string   $relation
     * @param callable $callback
     * @return static
     * @throws ReflectionException
     */
    public function whereDoesntHave(string $relation, callable $callback): static
    {
        $reflection    = new ReflectionClass($this->model);
        $method        = $reflection->getMethod($relation);
        /** @var AbstractRelation $relation */
        $relation      = $method->invoke($this->model);
        $relatedClass  = $relation->getRelatedClass();
        $query         = $relatedClass::query();

        if ($relation instanceof HasMany) {
            $query->whereColumn(
                $relation->getForeignKey(),
                $this->model->getTableName()
                . '.'
                . $relation->getLocalKey()
            );
        } elseif ($relation instanceof BelongsTo) {
            $query->whereColumn(
                $relation->getLocalKey(),
                $this->model->getTableName()
                . '.'
                . $relation->getForeignKey()
            );
        }

        if (is_callable($callback)) {
            call_user_func($callback, $query);
        }

        $sql = $query->generateQuery();

        $this->existsArray[] = [
            'sql'    => $sql,
            'method' => 'AND',
            'not'    => true,
        ];

        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->whereArray[] = [
            'type'     => 'Raw',
            'sql'      => $sql,
            'bindings' => $bindings,
            'method'   => $boolean
        ];

        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param mixed|null $column Column name
     * @param mixed|null $operator Value or Operator
     * @param mixed|null $value Valor or null
     * @param mixed|null $method
     * @param mixed|null $table
     * @return QueryBuilder
     */
    public function where(
        mixed $column,
        mixed $operator = null,
        mixed $value = null,
        mixed $method = null,
        mixed $table = null
    ): static {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->where($col, $val);
            }
            return $this;
        }

        if ($column instanceof Closure) {
            $this->whereArray[] = [
                'type'     => 'Nested',
                'callback' => $column,
                'method'   => $method ?? 'AND'
            ];
            return $this;
        }

        $where = [
            'column'   => $column,
            'value'    => $value ?? $operator,
            'operator' => isset($value) ? $operator : '='
        ];

        if ($method) {
            $where['method'] = $method;
        }

        if ($table) {
            $where['table'] = $table;
        }

        $this->whereArray[] = $where;

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public function orWhere(mixed $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->orWhere($col, $val);
            }
            return $this;
        }

        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Where has relation
     *
     * @param string $relation
     * @param callable|null $callback
     * @return QueryBuilder
     * @throws ReflectionException
     */
    public function whereHas(string $relation, ?callable $callback = null): QueryBuilder
    {
        $reflection   = new ReflectionClass($this->model);
        $method       = $reflection->getMethod($relation);
        /** @var AbstractRelation $relation */
        $relation     = $method->invoke($this->model);
        $relatedClass = $relation->getRelatedClass();
        $query        = $relatedClass::query();

        if ($relation instanceof HasMany || $relation instanceof HasOne) {
            $query->whereColumn(
                $relation->getForeignKey(),
                $this->model->getTableName()
                . '.'
                . $relation->getLocalKey()
            );
        } elseif ($relation instanceof BelongsTo) {
            $query->whereColumn(
                $relation->getLocalKey(),
                $this->model->getTableName()
                . '.'
                . $relation->getForeignKey()
            );
        }

        if (is_callable($callback)) {
            call_user_func($callback, $query);
        }

        $sql = $query->generateQuery();

        $this->existsArray[] = [
            'sql'    => $sql,
            'method' => 'AND'
        ];

        return $this;
    }

    /**
     * Group by columns
     *
     * @param string ...$columns
     * @return QueryBuilder
     */
    public function groupBy(string ...$columns): QueryBuilder
    {
        foreach ($columns as $column) {
            $this->groupBy[] = $column;
        }
        return $this;
    }

    /**
     * Where In
     *
     * @param string $column Name of the column.
     * @param array  $values Array values of the column.
     * @return QueryBuilder
     */
    public function whereIn(string $column, array $values = []): QueryBuilder
    {
        $this->where($column, 'IN', $values);

        return $this;
    }

    /**
     * Add an "or where" with relation clause to the query.
     *
     * @param mixed $relation
     * @param mixed $column
     * @param mixed $valueOrOperator
     * @param mixed|null $fieldValue
     * @return static
     * @throws ReflectionException
     */
    public function orWhereRelation(
        mixed $relation,
        mixed $column,
        mixed $valueOrOperator,
        mixed $fieldValue = null
    ): static {
        return $this->whereRelation(
            $relation,
            $column,
            $valueOrOperator,
            $fieldValue,
            'OR'
        );
    }

    /**
     * Add a "where" with relation clause to the query.
     *
     * @param mixed      $relation
     * @param mixed      $column
     * @param mixed      $valueOrOperator
     * @param mixed|null $fieldValue
     * @param mixed      $queryMethod
     * @return static
     * @throws ReflectionException
     */
    public function whereRelation(
        mixed $relation,
        mixed $column,
        mixed $valueOrOperator,
        mixed $fieldValue = null,
        mixed $queryMethod = 'AND'
    ): static {
        //TODO: Verify and refactor this
        $reflection = new ReflectionClass($this->model);
        $method     = $reflection->getMethod($relation);
        $returnType = $method->getReturnType();

        if ($returnType && $relation === $method->getName()) {
            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === HasOne::class) {
                /**
                 * @var HasOne $hasOne
                 * TODO: Verify not implemented
                 */
                $hasOne       = $method->invoke($this->model);
                $relatedClass = $hasOne->getRelatedClass();
                $tableName    = $relatedClass::getFullTableName();

                $this->joinArray[] = [
                    'table'       => $tableName,
                    'foreign_key' => $this->model->getForeignKey() . '_id',
                    'local_key'   => 'id',
                ];

                //TODO: Replace by $this->where
                $this->whereArray[] = [
                    'method'   => $queryMethod,
                    'column'   => "{$column}",
                    'table'    => $tableName,
                    'value'    => $fieldValue ?? $valueOrOperator,
                    'operator' => isset($fieldValue) ? $valueOrOperator : '='
                ];

                if ($relatedClass::isTrashed()) {
                    $this->whereArray[] = [
                        'column'   => 'deleted_at',
                        'table'    => $tableName,
                        'value'    => '!#####NULL#####!',
                        'operator' => 'IS'
                    ];
                }
            }

            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === BelongsTo::class) {
                /**
                 * @var BelongsTo $belongsTo
                 */
                $belongsTo    = $method->invoke($this->model);
                $relatedClass = $belongsTo->getRelatedClass();
                $tableName    = $relatedClass::getFullTableName();


                $this->joinArray[] = [
                    'table'       => $tableName,
                    'foreign_key' => $belongsTo->getLocalKey(),
                    'local_key'   => $belongsTo->getForeignKey()
                ];

                //TODO: Replace by $this->where
                $this->whereArray[] = [
                    'method'   => $queryMethod,
                    'column'   => "{$column}",
                    'table'    => $tableName,
                    'value'    => $fieldValue ?? $valueOrOperator,
                    'operator' => isset($fieldValue) ? $valueOrOperator : '='
                ];

                if ($relatedClass::isTrashed()) {
                    $this->whereArray[] = [
                        'column'   => 'deleted_at',
                        'table'    => $tableName,
                        'value'    => '!#####NULL#####!',
                        'operator' => 'IS'
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * Add a "where" with columns compare clause to the query.
     *
     * @param string      $columnOne Column name
     * @param string|null $operator  Operator or Column name
     * @param string|null $columnTwo Column name
     * @param string      $method    Method to use
     * @return static
     */
    public function whereColumn(
        string $columnOne,
        ?string $operator = null,
        ?string $columnTwo = null,
        string $method = 'AND'
    ): static {
        $this->whereColumnArray[] = [
            'column_one' => $columnOne,
            'operator'   => $columnTwo ? $operator : '=',
            'column_two' => $columnTwo ?? $operator,
            'method'     => $method
        ];

        return $this;
    }

    /**
     * Add relations to be loaded with the query
     *
     * @param string|array $relations
     * @return QueryBuilder
     */
    public function with(array|string $relations): QueryBuilder
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        if (!is_array($relations)) {
            return $this;
        }

        foreach ($relations as $relation) {
            $this->addRelationToWith($relation);
        }

        return $this;
    }

    /**
     * Add a single relation to the with array
     *
     * @param string $relation
     * @return void
     */
    private function addRelationToWith(string $relation): void
    {
        if (empty($relation)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($this->model);

            if (!$reflection->hasMethod($relation)) {
                return;
            }

            $method = $reflection->getMethod($relation);

            if ($relation !== $method->getName()) {
                return;
            }

            $returnType = $method->getReturnType();
            if (!$returnType instanceof ReflectionNamedType) {
                return;
            }

            $relationInstance = $method->invoke($this->model);
            $relatedClass     = $relationInstance->getRelatedClass();
            $returnTypeName   = $returnType->getName();
            $relationConfig   = $this->buildRelationConfig($returnTypeName, $relatedClass, $relation);

            if ($relationConfig) {
                $this->withArray[] = $relationConfig;
            }
        } catch (Exception) {
            // Silently ignore invalid relations
            return;
        }
    }

    /**
     * Build relation configuration based on relation type
     *
     * @param string $relationTypeName
     * @param $relatedClass
     * @param string $relation
     * @return array|null
     */
    private function buildRelationConfig(string $relationTypeName, $relatedClass, string $relation): ?array
    {
        $baseConfig = [
            'model'    => $relatedClass,
            'relation' => $relation,
            'table'    => $relatedClass::getTable(),
        ];

        return match ($relationTypeName) {
            HasOne::class    => array_merge($baseConfig, [
                'foreign_key'   => $this->model->getForeignKey() . '_id',
                'local_key'     => 'id',
                'relation_type' => HasOne::class
            ]),
            BelongsTo::class  => array_merge($baseConfig, [
                'foreign_key'   => 'id',
                'local_key'     => $relatedClass::getForeignKeyStatic(),
                'relation_type' => BelongsTo::class
            ]),
            HasMany::class   => array_merge($baseConfig, [
                'foreign_key'   => $this->model->getForeignKey() . '_id',
                'local_key'     => 'id',
                'relation_type' => HasMany::class
            ]),
            default => null,
        };
    }

    /**
     * Count all records from the table
     *
     * @return int Database query results.
     */
    public function count(): int
    {
        return (int)$this->db->getVar($this->generateQuery(true));
    }

    /**
     * Check if a record exists in the table
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Delete a record from the table
     *
     * @return int|false The number of rows updated, or false on error.
     */
    public function delete(mixed $whereFormat = null): int|false
    {
        if ($this->model->trashed()) {
            return $this->update(['deleted_at' => current_time('mysql')]);
        } else {
            $where = [];
            foreach ($this->whereArray as $item) {
                $where[$item['column']] = $item['value'];
            }
            return $this->db->delete($this->tableName, $where, $whereFormat);
        }
    }

    public function update($columnsValues): bool|int
    {
        $setClauses = [];
        $values     = [];

        foreach ($columnsValues as $column => $value) {
            $setClauses[] = "{$column} = %s";
            $values[]     = $value;
        }

        $setClause      = implode(', ', $setClauses);
        $whereExistsSql = $this->resolveWhereExists();
        $whereColumnSql = $this->resolveWhereColumn();
        $where          = $this->resolveWhere();
        $placeholders   = $where['placeholders'];
        $whereSql       = implode(' ', $placeholders);
        $conditions     = array_filter([$whereExistsSql, $whereColumnSql, $whereSql]);

        $sql = "UPDATE {$this->tableName} SET {$setClause}";

        if (!empty($conditions)) {
            $sql   .= ' WHERE ' . implode(' AND ', $conditions);
            $values = array_merge($values, $where['values']);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';
            $orderBy = [];
            foreach ($this->orderBy as $order) {
                $orderBy[] = "{$order['column']} " . strtoupper($order['order']);
            }
            $sql .= implode(', ', $orderBy);
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        $preparedQuery = $this->db->prepare($sql, ...$values);

        return $this->db->query($preparedQuery);
    }

    /**
     * Generate the query
     *
     * @param bool $count
     * @return string
     */
    protected function generateQuery(bool $count = false): string
    {
        $sql = $count ? "SELECT \count(*) FROM {$this->tableName}" : "SELECT {$this->select} FROM {$this->tableName}";

        foreach ($this->joinArray as $join) {
            $sql .= " INNER JOIN {$join['table']} ON {$this->tableName}
            .{$join['local_key']} = {$join['table']}.{$join['foreign_key']}";
        }

        if (!empty($this->whereArray) || !empty($this->whereColumnArray)) {
            $sql .= ' WHERE ';
            $whereExistsSql = $this->resolveWhereExists();
            $whereColumnSql = $this->resolveWhereColumn();
            $where = $this->resolveWhere();
            $placeholders = $where['placeholders'];
            $values = $where['values'];
            $whereSql = implode(' ', $placeholders);
            $conditions = array_filter([$whereExistsSql, $whereColumnSql, $whereSql]);
            $sql .= implode(' AND ', $conditions);
            $sql = $this->db->prepare($sql, ...$values);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ';
            $sql .= implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';
            $orderBy = [];
            foreach ($this->orderBy as $order) {
                $orderBy[] = "{$order['column']} " . strtoupper($order['order']);
            }
            $sql .= implode(', ', $orderBy);
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function resolveWhere(): array
    {
        $placeholders = [];
        $values = [];

        foreach ($this->whereArray as $where) {
            if (isset($where['type']) && $where['type'] === 'Nested') {
                $nestedQuery = new self($this->model);
                $nestedQuery->whereArray = [];
                call_user_func($where['callback'], $nestedQuery);
                $nestedWhere = $nestedQuery->resolveWhere();

                if (!empty($nestedWhere['placeholders'])) {
                    $nestedSql = '(' . implode(' ', $nestedWhere['placeholders']) . ')';
                    $method = $where['method'] ?? 'AND';
                    $placeholders[] = empty($placeholders) ? $nestedSql : "{$method} {$nestedSql}";
                    $values = array_merge($values, $nestedWhere['values']);
                }
                continue;
            }

            if (isset($where['type']) && $where['type'] === 'Raw') {
                $sql = $where['sql'];
                $method = $where['method'] ?? 'AND';
                $placeholders[] = empty($placeholders) ? "({$sql})" : "{$method} ({$sql})";
                if (!empty($where['bindings'])) {
                    $values = array_merge($values, (array)$where['bindings']);
                }
                continue;
            }

            $operator = $where['operator'] ?? '=';
            $where['value'] = is_array($where['value']) && empty($where['value']) ? [null] : $where['value'];
            $value = $where['operator'] === 'IN'
                ? '(' . implode(', ', array_fill(0, count($where['value']), '%s')) . ')'
                : '%s';
            $table_name = $where['table'] ?? $this->tableName;
            $placeholder = "{$table_name}.{$where['column']} {$operator} {$value}";
            $method = $where['method'] ?? 'AND';
            $placeholders[] = empty($placeholders) ? $placeholder : "{$method} {$placeholder}";

            if (is_array($where['value'])) {
                $values = array_merge($values, $where['value']);
            } else {
                $values[] = $where['value'];
            }
        }

        return ['placeholders' => $placeholders, 'values' => $values];
    }

    public function resolveWhereColumn(): string
    {
        $placeholders = [];

        foreach ($this->whereColumnArray as $where) {
            $operator = $where['operator'] ?? '=';
            $column_two = $where['column_two'];
            $table_name = $this->tableName;
            $placeholder = "{$table_name}.{$where['column_one']} {$operator} {$column_two}";
            $method = $where['method'] ?? 'AND';
            $placeholders[] = empty($placeholders) ? $placeholder : "{$method} {$placeholder}";
        }

        return implode(' ', $placeholders);
    }

    public function resolveWhereExists(): string
    {
        $placeholders = [];
        foreach ($this->existsArray as $where) {
            $placeholder = $where['sql'];
            $method = $where['method'] ?? 'AND';
            $not = ! empty($where['not']);
            $exists = ( $not ? 'NOT ' : '' ) . "EXISTS ({$placeholder})";
            $placeholders[] = empty($placeholders) ? $exists : "{$method} {$exists}";
        }

        return implode(' ', $placeholders);
    }

    /**
     * Generate the query
     *
     * @param $results
     * @return array
     */
    public function getWithRelations($results): array
    {
        if (empty($this->withArray)) {
            return [];
        }

        $relations = [];

        $ids = wp_list_pluck($results, $this->model->getPrimaryKey());

        //TODO: optimize this
        foreach ($this->withArray as $with) {
            foreach ($ids as $id) {
                $initial_data = $with['relation_type'] !== BelongsTo::class && $with['relation_type'] !== HasOne::class
                    ? new Collection([])
                    : null;
                $relations[$id][$with['relation']] = $initial_data;
            }


            $foreignIds = wp_list_pluck($results, $with['local_key']);

            /** @var AbstractModel $foreignModel */
            $foreignModel = new $with['model']();
            $relationResult = $foreignModel::whereIn($with['foreign_key'], $foreignIds)->get();

            if ($with['relation_type'] === BelongsTo::class) {
                foreach ($results as $item) {
                    $data = $relationResult->firstWhere($with['foreign_key'], $item->{$with['local_key']});
                    $foreignKey = $with['foreign_key'];
                    $relations[$item->$foreignKey][$with['relation']] = $data;
                }
            } elseif ($with['relation_type'] === HasOne::class) {
                foreach ($relationResult as $item) {
                    $foreignKey = $with['foreign_key'];
                    $relations[$item->$foreignKey][$with['relation']] = $item;
                }
            } else {
                foreach ($relationResult as $item) {
                    $foreignKey = $with['foreign_key'];
                    if (isset($relations[$item->$foreignKey], $relations[$item->$foreignKey][$with['relation']])) {
                        $relations[$item->$foreignKey][$with['relation']]->push($item);
                    }
                }
            }
        }

        return $relations;
    }

    /**
     * Get all records from the table
     *
     * @return Collection Database query results.
     */
    public function get(): Collection
    {
        $results = $this->db->getResults($this->generateQuery());
        $relations = $this->getWithRelations($results);
        $items = [];
        foreach ($results as $result) {
            $primaryKey = $this->model->getPrimaryKey();
            if (isset($result->$primaryKey)) {
                $relation = $relations[$result->$primaryKey] ?? [];
                $result = array_merge((array)$result, $relation);
            }
            $itemModel = new $this->model((array)$result, $this->model->getTableName());
            $itemModel->setWasRetrieved(true);

            $items[] = $itemModel;
        }

        return new Collection($items);
    }

    /**
     * Get the first record from the table
     *
     * @return AbstractModel|null Database query result.
     */
    public function first(): ?AbstractModel
    {
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Get the first record from the table or throw an exception
     *
     * @return AbstractModel Database query result.
     * @throws Exception
     */
    public function firstOrFail(): AbstractModel
    {
        $result = $this->first();
        if (!$result) {
            throw new Exception('No record found');
        }

        return $result;
    }

    /**
     * Order by
     *
     * @param array  $column
     * @param string $order Values: asc, desc
     * @return QueryBuilder
     */
    public function orderBy(array $column, string $order = 'asc'): QueryBuilder
    {
        $this->orderBy[] = ['column' => $column, 'order' => $order];

        return $this;
    }

    /**
     * Limit
     *
     * @param int $limit
     * @return QueryBuilder
     */
    public function limit(int $limit): QueryBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Offset
     *
     * @param int $offset
     * @return QueryBuilder
     */
    public function offset(int $offset): QueryBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get the model instance
     *
     * @return AbstractModel
     */
    public function getModel(): AbstractModel
    {
        return $this->model;
    }

    /**
     * Paginate the results.
     *
     * @param mixed  $perPage
     * @param string $queryPageKey
     * @return Paginator
     */
    public function paginate(mixed $perPage, string $queryPageKey = 'page'): Paginator
    {
        $currentPage = (int)($_GET[$queryPageKey] ?? 1);
        $total       = $this->count();
        $items       = $this->offset(($currentPage - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return new Paginator($items->getAll(), $total, $perPage, $currentPage);
    }
}
