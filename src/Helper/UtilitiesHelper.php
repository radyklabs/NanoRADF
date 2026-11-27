<?php declare (strict_types=1);

/**
 * The escaping helpers — `e()` and `classlist()`.
 * --
 * `e()` makes a value safe to print in HTML, so user-supplied text can never
 * turn into live markup. Templates rarely need it: Drip escapes `{{ }}` on its
 * own, and calling `e()` there is rejected to prevent escaping twice. Reach for
 * it in plain PHP that builds HTML by hand.
 *
 * `classlist()` turns a list or map of class names into a ready `class="…"`
 * attribute, dropping the entries that are switched off.
 *
 * Helper files hold functions only — no classes, interfaces, traits, or enums.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

/**
 * Make a value safe to print in HTML text or an attribute.
 *
 * Null, arrays, and objects that cannot be turned into text become an empty
 * string rather than triggering a warning.
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
 * Build a `class` attribute from a list or a map.
 *
 * A plain list keeps every name; a map keeps only the names whose value is
 * truthy, which is how conditional classes are written.
 *
 * @since  0.0.1
 * @param  array<array-key, mixed> $classes Class list, or name => enabled map.
 * @return string Ready `class="…"` attribute, or '' when nothing is active.
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
