<?php declare (strict_types=1);

/**
 * Base class for pages meant for signed-in users only.
 * --
 * Extend this for the staff-facing part of the application — dashboards, admin
 * screens, anything behind a login. The router asks `authorize()` before running
 * any action on this surface, which is the single place a future login check
 * belongs. For now the gate is open and nothing is enforced.
 *
 * Pages built on this class use the backend theme.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Controllers;

use System\Kernel\Controller as BaseController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

class PrivateController extends BaseController
{
}
