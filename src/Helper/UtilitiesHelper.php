<?php declare (strict_types=1);

/**
 * Escaping helpers — global, loaded from the helper registry.
 *
 * Function-only by contract. Templates should rely on Drip's automatic
 * escaping instead of calling these; the compiler rejects escape calls inside
 * `{{ }}` to prevent double-escaping.
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
 * Escape a value for HTML text or attribute context.
 *
 * @since  0.0.1
 * @param  mixed $value Raw value from controller data.
 * @return string Escaped text, safe for HTML.
 */
function e (mixed $value): string
{
    if ($value === null || is_array ($value)) {return '';}
    if (is_object ($value) && !$value instanceof Stringable) {return '';}

    return htmlspecialchars ((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Join class names, dropping inactive entries.
 *
 * @since  0.0.1
 * @param  array<array-key, mixed> $classes Class map or list.
 * @return string Class attribute, or '' when empty.
 */
function classlist (array $classes): string
{
    $names = [];

    foreach ($classes as $class => $enabled)
    {
        if (is_int ($class)) {$class = (string) $enabled; $enabled = true;}
        if ($enabled) {$names[] = (string) $class;}
    }

    return $names === [] ? '' : 'class="'.e (implode (' ', $names)).'"';
}
