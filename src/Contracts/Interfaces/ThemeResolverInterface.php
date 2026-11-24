<?php declare (strict_types=1);

/**
 * Contract for deciding which theme a surface uses.
 * --
 * The default answer comes from `ThemeConfig`. A resolver exists for the case
 * where the choice must come from somewhere else — a database, a user
 * preference, a tenant record. Register the class name in `ThemeConfig['resolver']`
 * and its answer takes over from the config map.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.2
 */

namespace System\Contracts\Interfaces;

interface ThemeResolverInterface
{
    /**
     * Resolve the theme name for a surface.
     *
     * @param  string $surface `public` or `private`.
     * @return string|null Theme name, or null to fall back to the config map.
     */
    public function resolve (string $surface): ?string;
}
