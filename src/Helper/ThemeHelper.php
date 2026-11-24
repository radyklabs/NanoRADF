<?php declare (strict_types=1);

/**
 * The `theme()` function — which theme a surface is wearing.
 * --
 * Answers the question templates and services keep asking: what is this side of
 * the application styled as? The answer comes from the theme config, with the
 * application's choice winning over the framework default.
 *
 * A resolver class can take over that decision — set `resolver` in the theme
 * config to a class implementing `ThemeResolverInterface`. That is the seam
 * where a database or user preference plugs in later; when no resolver is
 * registered, or it declines to answer, config decides.
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

    $config  = gconfig ('theme');
    $default = (array) ($config['default'] ?? []);

    // A resolver, when registered, decides first — that is where a database
    // or user preference plugs in. Returning null falls through to config.
    $resolver = $config['resolver'] ?? null;

    if (is_string ($resolver) && $resolver !== '' && class_exists ($resolver))
    {
        $instance = new $resolver ();

        if ($instance instanceof \System\Contracts\Interfaces\ThemeResolverInterface)
        {
            $resolved = $instance->resolve ($surface);

            if (is_string ($resolved) && $resolved !== '') {return $resolved;}
        }
    }

    return (string) ($default[$surface] ?? 'default');
}