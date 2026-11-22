<?php declare (strict_types=1);

namespace System\Helper;

/**
 * Output helpers shared by controllers and compiled templates.
 *
 * Everything here is context-bound output: escaping for HTML text and the
 * conditional class attribute used by `@class([...])` in Drip templates.
 *
 * @since 0.0.1
 */
final class Utilities
{
    /**
     * Escape a value for HTML output (text or attribute context).
     *
     * Arrays and objects without `__toString()` render as an empty string so a
     * template can never leak a structure dump into the page.
     *
     * @since  0.0.1
     * @param  mixed $value Raw value from the view data.
     * @return string Escaped text, safe for HTML.
     */
    public static function escape (mixed $value): string
    {
        if ($value === null) {return '';}
        if (is_array ($value)) {return '';}
        if (is_object ($value) && !$value instanceof \Stringable) {return '';}

        return htmlspecialchars ((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Render a conditional class attribute.
     *
     * Accepts both map form (`['btn' => true, 'btn--lg' => $large]`) and list
     * form (`['btn', 'btn--lg']`); inactive entries are dropped and the result
     * is an empty string when nothing remains.
     *
     * @since  0.0.1
     * @param  array<array-key, mixed> $classes Class map or list.
     * @return string `class="..."` attribute, or '' when no class is active.
     */
    public static function classAttr (array $classes): string
    {
        $names = [];

        foreach ($classes as $class => $enabled)
        {
            if (is_int ($class)) {$class = (string) $enabled; $enabled = true;}
            if ($enabled) {$names[] = (string) $class;}
        }

        return $names === [] ? '' : 'class="'.self::escape (implode (' ', $names)).'"';
    }
}
