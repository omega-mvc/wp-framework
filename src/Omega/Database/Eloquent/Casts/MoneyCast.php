<?php

namespace Omega\Database\Eloquent\Casts;

use Omega\Contracts\Database\Eloquent\CastsAttributes;

class MoneyCast implements CastsAttributes {

	public function get( $model, string $key, $value, array $attributes ) {
		return $value / 100;
	}

	public function set( $model, string $key, $value, array $attributes ) {
		return (int) round( $value * 100 );
	}
}