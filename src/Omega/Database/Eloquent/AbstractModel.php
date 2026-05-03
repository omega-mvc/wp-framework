<?php

namespace Omega\Database\Eloquent;

use ArrayAccess;
use Omega\Application\ApplicationInstance;
use Omega\Collection\Collection;
use Omega\Database\Database;
use Omega\Database\Eloquent\Casts\ArrayCast;
use Omega\Database\Eloquent\Casts\Attribute;
use Omega\Database\Eloquent\Casts\BooleanCast;
use Omega\Database\Eloquent\Casts\MoneyCast;
use Omega\Database\Eloquent\Relations\BelongsTo;
use Omega\Database\Eloquent\Relations\HasMany;
use Omega\Database\Eloquent\Relations\HasOne;
use Omega\Database\Utils\Reflection;
use Omega\Paginator\Paginator;
use ReflectionClass;
use ReflectionException;
use ReturnTypeWillChange;

use function array_key_exists;
use function array_merge;
use function call_user_func;
use function class_exists;
use function class_uses;
use function current_time;
use function get_called_class;
use function in_array;
use function is_string;
use function lcfirst;
use function method_exists;
use function preg_replace;
use function str_replace;
use function strtolower;
use function ucwords;

defined( 'ABSPATH' ) || exit;

abstract class AbstractModel implements ArrayAccess
{
    private static array $instances = [];

    protected string $primaryKey = 'id';

    protected array $fillable = [];

    protected string $prefix = '';

    protected string $table;

    protected string $foreignKey;

    protected array $data = [];

    private bool $wasRetrieved = false;

    public bool $timestamps = false;

    protected array $casts = [];

    private array $updateData = [];

    /** @var mixed Database instance. */
    protected mixed $db;

    /**
     * Database instance
     *
     * @return AbstractModel
     */
    public static function getInstance(): AbstractModel
    {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class([]);
        }

        return self::$instances[$class];
    }

    /**
     * Constructor
     *
     * @param array       $data
     * @param string|null $table
     * @return void
     * @throws ReflectionException
     */
    public function __construct(array $data = [], ?string $table = null)
    {
        $this->db         = ApplicationInstance::app('database');
        $this->table      = $table ?? self::getFullTableName();
        $this->foreignKey = $this->modelToForeign(get_called_class());
        $this->data       = $data;

        foreach ($data as $key => $value) {
            $this->data[$key] = $this->getAttributeValue($key, $value);
        }
    }

    /**
     * @throws ReflectionException
     */
    public static function getFullTableName(): string
    {
        $class = get_called_class();
        $defaultTableName = Reflection::getDefaultValue($class, 'table');

        if (empty($defaultTableName)) {
            return Database::getTableName(self::modelToTable(get_called_class()), self::getPrefix());
        } else {
            return Database::getTableName($defaultTableName, self::getPrefix());
        }
    }

    public static function modelToTable($model): string
    {
        $reflect              = new ReflectionClass($model);
        $tableNameUnderscored = preg_replace('/(?<!^)([A-Z])/', '_$1', $reflect->getShortName());

        return strtolower($tableNameUnderscored) . 's';
    }

    private function modelToForeign($model): string
    {
        $reflect              = new ReflectionClass($model);
        $tableNameUnderscored = preg_replace('/(?<!^)([A-Z])/', '_$1', $reflect->getShortName());

        return strtolower($tableNameUnderscored);
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public static function getForeignKeyStatic(): string
    {
        $reflect              = new ReflectionClass(get_called_class());
        $tableNameUnderscored = preg_replace('/(?<!^)([A-Z])/', '_$1', $reflect->getShortName());

        return strtolower($tableNameUnderscored) . '_id';
    }

    /**
     * Query
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        $instance     = self::getInstance();

        return new QueryBuilder($instance);
    }


    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Add relations to a QueryBuilder
     *
     * @param array|string $relationName
     * @return QueryBuilder
     */
    public static function with(array|string $relationName): QueryBuilder
    {
        return self::query()->with($relationName);
    }


    /**
     * Find a record by id
     *
     * @param int $id
     * @return AbstractModel|null
     */
    public static function find(int $id): ?AbstractModel
    {
        return self::query()->find($id);
    }

    /**
     * Get all records from the database
     *
     * @return Collection
     */
    public static function all(): Collection
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        return $builder->get();
    }

    /**
     * Get count for all records from the table
     *
     * @return int Database query results.
     */
    public static function count(): int
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        return $builder->count();
    }

    /**
     * Where Null
     *
     * @param mixed $column Column name
     * @return QueryBuilder
     */
    public static function whereNull(mixed $column): QueryBuilder
    {
        return self::query()->whereNull($column);
    }

    /**
     * Where
     *
     * @param mixed $column Column name
     * @param mixed $operator Value or Operator
     * @param mixed $value Valor or null
     * @return QueryBuilder
     */
    public static function where(mixed $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        $builder->where($column, $operator, $value);

        return $builder;
    }

    /**
     * Where Has
     *
     * @param string   $relation
     * @param callable $callback
     * @return QueryBuilder
     */
    public static function whereHas(string $relation, callable $callback): QueryBuilder
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        $builder->whereHas($relation, $callback);

        return $builder;
    }

    /**
     * Select
     *
     * @param string|array $columns
     * @return QueryBuilder
     */
    public static function select(string|array $columns): QueryBuilder
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        $builder->select($columns);

        return $builder;
    }

    /**
     * Where In
     *
     * @param string $column Name of the column.
     * @param array  $values Array values of the column.
     * @return QueryBuilder
     */
    public static function whereIn(string $column, array $values = []): QueryBuilder
    {
        $instance = self::getInstance();
        $builder  = new QueryBuilder($instance);

        $builder->whereIn($column, $values);

        return $builder;
    }

    /**
     * Get Prefix
     *
     * Add Compatibility with PHP 7.4
     * PHP >= 8 use  getDefaultValue
     *
     * @return string
     * @throws ReflectionException
     */
    public static function getPrefix(): string
    {
        $class = get_called_class();

        return Reflection::getDefaultValue($class, 'prefix', '');
    }

    /**
     * @throws ReflectionException
     */
    public static function usesTimestamps()
    {
        $class = get_called_class();

        return Reflection::getDefaultValue($class, 'timestamps', false);
    }

    /**
     * Create a record
     *
     * @param array $columnsValues
     * @return false|AbstractModel
     * @throws ReflectionException
     */
    public static function create(array $columnsValues): false|AbstractModel
    {
        $instance = self::getInstance();

        $tableName = $instance->getTableName();

        foreach ($columnsValues as $key => $value) {
            $columnsValues[$key] = $instance->setAttributeValue($key, $value, $columnsValues);
        }

        if (self::usesTimestamps()) {
            $columnsValues['created_at'] = current_time('mysql');
            $columnsValues['updated_at'] = current_time('mysql');
        }

        $inserted = Database::insert($tableName, $columnsValues);
        if ($inserted) {
            $class    = get_called_class();
            $newClass = new $class(
                array_merge($columnsValues, ['id' => $inserted])
            );

            $newClass->setWasRetrieved(true);

            return $newClass;
        }
        return false;
    }


    /**
     * Update
     *
     * @param array $columnsValues
     * @param array $whereValues
     * @return bool|int
     */
    public static function update(array $columnsValues, array $whereValues): bool|int
    {
        $instance = self::getInstance();

        return $instance->db->update($instance->table, $columnsValues, $whereValues);
    }

    /**
     * @throws ReflectionException
     */
    public static function updateOrCreate(array $whereValues, array $columnsValues): bool|int|AbstractModel
    {
        $instance = self::getInstance();
        $existing = $instance->where($whereValues)->first();

        if ($existing) {
            return $existing->update($columnsValues, $whereValues);
        } else {
            $data = array_merge($whereValues, $columnsValues);
            return self::create($data);
        }
    }

    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable) || empty($this->fillable)) {
                $this->updateData[$key] = $value;
            }
        }
    }

    /**
     * Save the model to the database.
     *
     * @return false|int
     */
    public function save(): false|int
    {
        if ($this->wasRetrieved()) {
            $data         = $this->updateData;
            $id           = $this->data[$this->primaryKey];
            $queryBuilder = new QueryBuilder($this);
            $result       = $queryBuilder->where($this->primaryKey, $id)->update($data);
            if ($result) {
                $this->data = array_merge($this->data, $this->updateData);
            }
            return $result;
        } else {
            $result = $this->db->insert($this->table, $this->data);
            if ($result) {
                $this->data[$this->primaryKey] = $result;
            }
            return $result;
        }
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|int
     */
    public function delete(): bool|int
    {
        if ($this->wasRetrieved()) {
            $data         = $this->data;
            $id           = $data[$this->primaryKey];
            $queryBuilder = new QueryBuilder($this);

            return $queryBuilder->where($this->primaryKey, $id)->delete();
        }

        return false;
    }

    public function relationLoaded($relation): bool
    {
        return isset($this->data[$relation]);
    }

    public function setAttribute($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Create Many
     *
     * @param array $columnsValues
     * @return bool|int
     */
    public static function createMany(array $columnsValues): bool|int
    {
        $instance = self::getInstance();

        return $instance->db->insertMultiple($instance->table, $columnsValues);
    }

    /**
     * One-to-one relationship
     *
     * @param string $relatedClass
     * @return HasOne
     */
    public function hasOne(string $relatedClass): HasOne
    {
        return new HasOne($this, $relatedClass, "{$this->foreignKey}_id", "id");
    }

    /**
     * Belongs to relationship
     *
     * @param string $relatedClass
     * @return BelongsTo
     */
    public function belongsTo(string $relatedClass): BelongsTo
    {
        $foreignKey = $this->modelToForeign($relatedClass);

        return new BelongsTo($this, $relatedClass, "{$foreignKey}_id", "id");
    }

    /**
     * HasMany to relationship
     *
     * @param string $relatedClass
     * @return HasMany
     */
    public function hasMany(string $relatedClass): HasMany
    {
        //$foreignKey = $this->modelToForeign( $related_class );
        return new HasMany($this, $relatedClass, "{$this->foreignKey}_id", "id");
    }


    /**
     * Get table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Get primary key
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }


    /**
     * Get table name
     *
     * @return string
     */
    static public function getTable(): string
    {
        $instance = self::getInstance();

        return $instance->table;
    }

    /**
     * Get database
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }


    public function trashed(): bool
    {
        return in_array(SoftDeletesTrait::class, class_uses($this));
    }

    public static function isTrashed(): bool
    {
        return in_array(SoftDeletesTrait::class, class_uses(get_called_class()));
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            if ($value instanceof Collection) {
                $result[$key] = $value->map(function ($item) {
                    return $item instanceof AbstractModel ? $item->toArray() : $item;
                });
            } elseif ($value instanceof AbstractModel) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function keyExists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get the attribute method name for a given attribute.
     *
     * @param string $key
     * @return string|null
     */
    protected function getAttributeMethod(string $key): ?string
    {
        // Convert snake_case to camelCase for method name
        $method = lcfirst(str_replace('_', '', ucwords($key, '_')));

        return method_exists($this, $method) ? $method : null;
    }

    /**
     * Get attribute value using Attribute accessor if available
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    private function getAttributeValue(string $key, mixed $value = null): mixed
    {

        $attributeMethod = $this->getAttributeMethod($key);
        if ($attributeMethod && method_exists($this, $attributeMethod)) {
            $attribute = $this->$attributeMethod();
            if ($attribute instanceof Attribute && $attribute->get) {
                return call_user_func($attribute->get, $value !== null ? $value : ($this->data[$key] ?? null), $this->data);
            }
        }


        $casts = $this->casts();

        if (array_key_exists($key, $casts)) {
            $cast = $casts[$key];
            if (is_string($cast)) {
                switch (strtolower($cast)) {
                    case 'boolean':
                    case 'bool':
                        $cast = BooleanCast::class;
                        break;
                    case 'array':
                        $cast = ArrayCast::class;
                        break;
                    case 'money':
                        $cast = MoneyCast::class;
                        break;
                    case 'int':
                    case 'integer':
                        return (int)$value;
                    case 'real':
                    case 'float':
                    case 'double':
                        return (float)$value;
                    case 'string':
                        return $value === null ? null : (string)$value;
                }

                if (class_exists($cast)) {
                    $cast = new $cast;
                }
            }
            if ($cast instanceof CastsAttributesInterface) {
                return $cast->get($this, $key, $value, $this->data);
            }
        }

        return $value;
    }

    /**
     * Set attribute value using Attribute mutator if available
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $data
     * @return mixed
     */
    private function setAttributeValue(string $key, mixed $value, array $data = []): mixed
    {
        $attributeMethod = $this->getAttributeMethod($key);

        if ($attributeMethod && method_exists($this, $attributeMethod)) {
            $attribute = $this->$attributeMethod();
            if ($attribute instanceof Attribute && $attribute->set) {
                return call_user_func($attribute->set, $value, array_merge($data, $this->data));
            }
        }

        $casts = $this->casts();

        if (array_key_exists($key, $casts)) {
            $cast = $casts[$key];
            if (is_string($cast)) {
                switch (strtolower($cast)) {
                    case 'boolean':
                    case 'bool':
                        $cast = BooleanCast::class;
                        break;
                    case 'array':
                        $cast = ArrayCast::class;
                        break;
                    case 'money':
                        $cast = MoneyCast::class;
                        break;
                    case 'int':
                    case 'integer':
                        return (int)$value;
                    case 'real':
                    case 'float':
                    case 'double':
                        return (float)$value;
                    case 'string':
                        return $value === null ? null : (string)$value;
                }
                if (class_exists($cast)) {
                    $cast = new $cast;
                }
            }
            if ($cast instanceof CastsAttributesInterface) {
                return $cast->set($this, $key, $value, $this->data);
            }
        }

        return $value;
    }

    protected function casts(): array
    {
        return $this->casts ?? [];
    }

    public function __get($name)
    {
        if ($this->keyExists($name)) {
            return $this->data[$name];
        }

        $value = $this->getAttributeValue($name);

        if ($value !== null) {
            return $value;
        }

        return $this->$name;
    }

    /**
     * Dynamically set an attribute on the model.
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $value = $this->setAttributeValue($name, $value);

        if ($this->wasRetrieved()) {
            $this->updateData[$name] = $value;
        } else {
            $this->data[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->keyExists($offset)) {
            return $this->data[$offset];
        }

        $value = $this->getAttributeValue($offset);

        if ($value !== null) {
            return $value;
        }

        return null;
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset !== null) {
            $value = $this->setAttributeValue($offset, $value);
        }

        if ($this->wasRetrieved()) {
            $this->updateData[$offset] = $value;
        } else {
            if ($offset === null) {
                $this->data[] = $value;
            } else {
                $this->data[$offset] = $value;
            }
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    public function wasRetrieved(): bool
    {
        return $this->wasRetrieved;
    }

    public function setWasRetrieved($wasRetrieved): void
    {
        $this->wasRetrieved = $wasRetrieved;
    }

    /**
     * Execute a callback if the condition is true, otherwise return a default value.
     *
     * @param mixed    $condition
     * @param callable $callback
     * @return QueryBuilder
     */
    public static function when(mixed $condition, callable $callback): QueryBuilder
    {
        if (isset($condition) && !empty($condition) && $condition !== false) {
            return $callback(self::query(), $condition);
        }

        return self::query();
    }

    /**
     * Paginate the results.
     *
     * @param mixed $perPage
     * @return Paginator
     */
    public static function paginate(mixed $perPage): Paginator
    {
        $builder = self::query();

        return $builder->paginate($perPage);
    }
}
