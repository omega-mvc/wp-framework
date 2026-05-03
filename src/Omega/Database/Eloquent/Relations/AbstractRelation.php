<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Relations;

use Omega\Database\Eloquent\AbstractModel;

abstract class AbstractRelation
{
    /**
     * Relation constructor.
     *
     * @param AbstractModel $parent
     * @param AbstractModel|string $relatedClass
     * @param string $foreignKey
     * @param string $localKey
     */
    public function __construct(
        protected AbstractModel $parent,
        protected AbstractModel|string $relatedClass,
        protected string $foreignKey,
        protected string $localKey)
    {
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    /**
     * Related Model Class
     *
     * @return AbstractModel|string
     */
    public function getRelatedClass(): AbstractModel|string
    {
        return $this->relatedClass;
    }
}
