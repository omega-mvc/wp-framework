<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Relations;

use Omega\Database\Eloquent\AbstractModel;
use ReflectionException;

abstract class AbstractHasOneOrMany extends AbstractRelation
{
    /**
     * Attach a model instance to the parent model.
     *
     * @param AbstractModel $model
     * @return AbstractModel|false
     */
    public function save(AbstractModel $model): AbstractModel|false
    {
        $this->setForeignAttributesForCreate($model);

        return $model->save() ? $model : false;
    }

    /**
     * Create a record
     *
     * @param array $attributes
     * @return AbstractModel|false
     * @throws ReflectionException
     */
    public function create(array $attributes): AbstractModel|false
    {
        $attributes[$this->getForeignKey()] = $this->parent->{$this->getLocalKey()};

        return $this->relatedClass::create($attributes);
    }

    public function delete(): false|int
    {
        return $this->relatedClass::where($this->getForeignKey(), $this->parent->{$this->getLocalKey()})->delete();
    }

    /**
     * Set the foreign ID for creating a related model.
     *
     * @param AbstractModel $model
     * @return void
     */
    protected function setForeignAttributesForCreate(AbstractModel $model): void
    {
        $localKey = $this->getLocalKey();
        $model->setAttribute($this->getForeignKey(), $this->parent->$localKey);
    }
}
