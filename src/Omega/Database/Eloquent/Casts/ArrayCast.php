<?php

namespace Omega\Database\Eloquent\Casts;

use Omega\Database\Eloquent\CastsAttributesInterface;

class ArrayCast implements CastsAttributesInterface {

	public function get( $model, string $key, $value, array $attributes ) {
		return json_decode( $value, true );
	}

	public function set( $model, string $key, $value, array $attributes ) {
		return wp_json_encode( $value );
	}
}
