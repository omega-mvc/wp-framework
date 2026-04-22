<?php

namespace Omega\Database\Eloquent\Casts;

use Omega\Database\Eloquent\CastsAttributesInterface;

class BooleanCast implements CastsAttributesInterface {

	public function get( $model, string $key, $value, array $attributes ) {
		return (bool) $value;
	}

	public function set( $model, string $key, $value, array $attributes ) {
		return (int) $value;
	}
}