<?php declare (strict_types=1);

/**
 * Theme helpers — global, loaded from the helper registry.
 *
 * Function-only by contract (see apps/Config/HelperConfig.php).
 *
 * @since 0.0.1
 */

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page could not be found.</p>");
}

/**
     * Get active theme name for a surface.
     *
     * Reads from gconfig('theme')['default']['public'|'private'].
     * Accepts 'public', 'private', 'face', or 'bone' (backwards compat alias).
     *
     * @since  0.0.1
     * @param  string $surface 'public'/'face' (frontend) or 'private'/'bone' (backend).
     * @return string Theme name.
     */
function theme (string $surface = 'public'): string
{
    $surface = match ($surface)
    {
        'face', 'public' => 'public',
        'bone', 'private' => 'private',
        default => 'public',
    };

    $config = gconfig ('theme');
    $default = (array) ($config['default'] ?? []);
    return (string) ($default[$surface] ?? 'default');
}