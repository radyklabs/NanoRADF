<?php declare (strict_types=1);

namespace System\Controllers;

use System\Kernel\Controller as BaseController;

/**
 * Public surface — SSR pages reachable without authentication.
 *
 * @since 0.0.1
 */
class PublicController extends BaseController
{
    protected string $surface = 'public';

    protected string $theme = 'default';
}
