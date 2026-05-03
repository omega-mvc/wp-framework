<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Database\Eloquent\AbstractModel;

class DynamicModel extends AbstractModel
{
    protected string $primaryKey = 'id';

    public function __construct($data, $table)
    {
        parent::__construct($data, $table);
    }

    public function getTableName(): string
    {
        return $this->table;
    }
}