<?php

namespace Omega\Http\Facade;

use Omega\Facade\AbstractFacade;
use Omega\Http\Response as HttpResponse;

defined('ABSPATH') || exit;

/**
 * @method static \WP_Error|\WP_REST_Response json(array $data = [], int $status = 200, array $headers = [], int $options = 0)
 */

class Response extends AbstractFacade
{

    protected static function getFacadeAccessor()
    {
        return HttpResponse::class;
    }
}
