<?php

namespace Omega\Database;

use Omega\Database\Eloquent\Model;

class DynamicModel extends Model {
	protected $primaryKey = 'id';

	public function __construct( $data, $table ) {
		parent::__construct( $data, $table );
	}

	public function getTableName() {
		return $this->table;
	}
}