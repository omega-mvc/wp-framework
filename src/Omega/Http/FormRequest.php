<?php

declare(strict_types=1);

namespace Omega\Http;

use Omega\Validator\Validator;
use WP_REST_Request;

use function strtolower;

class FormRequest extends Validator
{
    /**
     * Validator constructor
     *
     * @param WP_REST_Request $request
     */
    public function __construct(WP_REST_Request $request)
    {
        $this->data = $request->get_params();
    }

    public function isMethod(string $method): bool
    {
        return strtolower($method) === strtolower($_SERVER['REQUEST_METHOD']);
    }
}
