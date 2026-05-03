<?php

declare(strict_types=1);

namespace Omega\Http\Facade;

use Omega\Facade\AbstractFacade;
use Omega\Http\Response as HttpResponse;
use WP_Error;
use WP_REST_Response;

/**
 * @method static WP_Error|WP_REST_Response json(array $data = [], int $status = 200, array $headers = [], int $options = 0)
 */
class Response extends AbstractFacade
{
    public static function getFacadeAccessor(): string
    {
        return HttpResponse::class;
    }
}
