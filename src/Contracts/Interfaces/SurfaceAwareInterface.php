<?php declare (strict_types=1);

/**
 * Interface for controllers that must declare which request surface they serve.
 * --
 * Guarantees every controller exposes its surface name (`public`, `private`, or `api`) through a single contract method. The framework reads this before dispatching; your code never touches the property directly.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.2
 */

namespace System\Contracts\Interfaces;

use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

interface SurfaceAwareInterface
{
    /**
     * Return the surface this controller belongs to.
     *
     * @return string One of 'public', 'private', or 'api'.
     */
    public function getSurface (): string;
}
