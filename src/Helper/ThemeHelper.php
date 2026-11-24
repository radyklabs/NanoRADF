<?php declare (strict_types=1);

/**
 * The `theme()` function — which theme a surface is wearing.
 * --
 * Answers the question templates and services keep asking: what is this side of
 * the application styled as? The answer comes from the theme config, with the
 * application's choice winning over the framework default.
 *
 * Both naming styles are accepted, so older code keeps working: `public`/`face`
 * both mean the visitor-facing side, `private`/`bone` both mean the backend.
 *
 * Helper files hold functions only — no classes, interfaces, traits, or enums.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System;

if (!defined ('basepath'))
{
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

/**
 * Name of the theme currently used by a surface.
 *
 * @since  0.0.1
 * @param  string $surface `public`/`face` for the frontend, `private`/`bone` for the backend.
 * @return string Theme name, falling back to `default`.
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