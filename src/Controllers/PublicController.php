<?php declare (strict_types=1);

/**
 * Base class for pages anyone can open.
 * --
 * Extend this for the visitor-facing part of the application — landing pages,
 * articles, anything that needs no login. Pages are rendered on the server and
 * dressed in the public theme.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Controllers;

use Throwable;
use System\Kernel\Controller as BaseController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

class PublicController extends BaseController {}
