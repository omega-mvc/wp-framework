<?php

declare(strict_types=1);

namespace Omega\Http;

use WP_Error;
use WP_REST_Response;

use function is_wp_error;
use function rest_ensure_response;

class Response
{
    public function json(
        array $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): WP_Error|WP_REST_Response {
        if ($status >= 400) {
            return new WP_Error(
                $status,
                $data['message'] ?? ($data['error'] ?? 'Error'),
                ['status' => $status]
            );
        }

        $response = rest_ensure_response($data);

        if (is_wp_error($response)) {
            return $response;
        }

        $response->set_status($status);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
