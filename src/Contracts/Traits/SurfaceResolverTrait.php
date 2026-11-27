<?php declare (strict_types=1);

/**
 * Trait that resolves a controller's surface from its class hierarchy.
 * --
 * Implements `SurfaceAwareInterface::getSurface()` so subclasses do not need to repeat the declaration. Resolution is driven by inheritance: if the class extends `PrivateController` the surface is `private`, etc. If nothing matches, the default is `public`.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.2
 */

namespace System\Contracts\Traits;

use Throwable;
use System\Controllers\ApiController;
use System\Controllers\PrivateController;
use System\Controllers\PublicController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

trait SurfaceResolverTrait
{
    /**
     * Cached surface value computed once per instance.
     *
     * @var string|null
     */
    private ?string $surface = null;

    /**
     * Determine the surface from the class hierarchy.
     *
     * @return string 'public' | 'private' | 'api'
     */
    public function getSurface (): string
    {
        if ($this->surface !== null) {return $this->surface;}

        $class = static::class;

        // Order matters: most-specific subclass check first.
        $this->surface = match (true) {
            is_subclass_of($class, ApiController::class)    => 'api',
            is_subclass_of($class, PrivateController::class) => 'private',
            is_subclass_of($class, PublicController::class)  => 'public',
            default                                           => 'public',
        };

        return $this->surface;
    }
}
