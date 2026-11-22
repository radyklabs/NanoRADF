<?php declare (strict_types=1);

namespace System\Services;

/**
 * AssetsService — enqueue, URL masking, and asset serving.
 *
 * Mask patterns live in `apps/Config/Assets.php` (`mask_urls`): a URL pattern
 * maps to a disk pattern. `{*}` matches any number of segments;
 * `{face_theme_active_name}` / `{bone_theme_active_name}` expand to the active
 * theme. Only paths matching a pattern are reachable — everything else is
 * unknown and served 404.
 *
 * @since 0.0.1
 */
final class AssetsService
{
    /**
     * Runtime enqueues, layered over the config lists.
     *
     * @var array<string, array{styles:list<string>, scripts:list<string>}>
     */
    private static array $queue = [];

    /**
     * Read the asset configuration.
     *
     * @since  0.0.1
     * @return array<string, mixed>
     */
    public static function config (): array
    {
        /** @var array<string, mixed> $config */
        $config = require interlace (appf, 'Config', 'Assets.php');

        return $config;
    }

    /**
     * Active theme name for a surface (config value, override in controllers).
     *
     * @since  0.0.1
     * @param  string $surface 'face' or 'bone'.
     * @return string Theme name.
     */
    public static function theme (string $surface = 'face'): string
    {
        /** @var array{face?:string, bone?:string} $themes */
        $themes = (array) (self::config ()['theme'] ?? []);

        return (string) ($themes[$surface] ?? 'default');
    }

    /**
     * Register a style for a surface.
     *
     * @since  0.0.1
     * @param  string $path    Asset path relative to the mask base.
     * @param  string $surface 'face' or 'bone'.
     * @return void
     */
    public static function enqueueStyle (string $path, string $surface = 'face'): void
    {
        self::$queue[$surface]['styles'][] = $path;
    }

    /**
     * Register a script for a surface.
     *
     * @since  0.0.1
     * @param  string $path    Asset path relative to the mask base.
     * @param  string $surface 'face' or 'bone'.
     * @return void
     */
    public static function enqueueScript (string $path, string $surface = 'face'): void
    {
        self::$queue[$surface]['scripts'][] = $path;
    }

    /**
     * Queued styles for a surface.
     *
     * @since  0.0.1
     * @param  string $surface 'face' or 'bone'.
     * @return list<string>
     */
    public static function styles (string $surface = 'face'): array
    {
        $config = self::config ();
        $key    = $surface === 'bone' ? 'bone_enqueue_styles' : 'enqueue_styles';

        return self::names ((array) ($config[$key] ?? []), $surface, 'style', $config);
    }

    /**
     * Queued scripts for a surface.
     *
     * @since  0.0.1
     * @param  string $surface 'face' or 'bone'.
     * @return list<string>
     */
    public static function scripts (string $surface = 'face'): array
    {
        $config = self::config ();
        $key    = $surface === 'bone' ? 'bone_enqueue_scripts' : 'enqueue_scripts';

        return self::names ((array) ($config[$key] ?? []), $surface, 'script', $config);
    }

    /**
     * Render queued face styles as `<link>` tags.
     *
     * @since  0.0.1
     * @return string HTML.
     */
    public static function renderFaceStyles (): string
    {
        return self::render (self::styles ('face'), 'face', 'style');
    }

    /**
     * Render queued face scripts as `<script>` tags.
     *
     * @since  0.0.1
     * @return string HTML.
     */
    public static function renderFaceScripts (): string
    {
        return self::render (self::scripts ('face'), 'face', 'script');
    }

    /**
     * Render queued bone styles as `<link>` tags.
     *
     * @since  0.0.1
     * @return string HTML.
     */
    public static function renderBoneStyles (): string
    {
        return self::render (self::styles ('bone'), 'bone', 'style');
    }

    /**
     * Render queued bone scripts as `<script>` tags.
     *
     * @since  0.0.1
     * @return string HTML.
     */
    public static function renderBoneScripts (): string
    {
        return self::render (self::scripts ('bone'), 'bone', 'script');
    }

    /**
     * Resolve a URL to a file and MIME type.
     *
     * @since  0.0.1
     * @param  string $url URL path (no leading slash required).
     * @return array{file:string, mime:string}|null Null when unknown, missing, or escaping the root.
     */
    public static function resolve (string $url): ?array
    {
        $url = $url === '' ? '/' : '/'.ltrim ($url, '/');

        foreach (self::config ()['mask_urls'] ?? [] as $pattern => $disk)
        {
            if (!is_string ($pattern) || !is_string ($disk)) {continue;}

            $match = self::matchPattern ($pattern, $url);

            if ($match === null) {continue;}

            $file = self::expand ($disk, $match);

            if ($file === null) {continue;}

            $real = realpath ($file);

            if ($real === false || !is_file ($real)) {continue;}

            return ['file' => $real, 'mime' => self::mime ($real)];
        }

        return null;
    }

    /**
     * URL for a queued asset name, masked through the configured patterns.
     *
     * @since  0.0.1
     * @param  string $name    Asset name, e.g. `style.css` or `css/style.css`.
     * @param  string $surface 'face' or 'bone'.
     * @return string URL with a cache buster.
     */
    public static function url (string $name, string $surface = 'face'): string
    {
        // Fast paths for known surfaces before scanning the entire pattern list.
        if (str_starts_with ($name, 'ds/'))
        {
            foreach (self::config ()['mask_urls'] ?? [] as $pattern => $disk)
            {
                if (!is_string ($pattern) || !is_string ($disk)) {continue;}
                if (str_contains ($disk, 'Views/Tokens') && preg_match (self::regex ($pattern), '/'.$name, $m) === 1)
                {
                    $value = (string) ($m['s'] ?? '');
                    return str_replace ('{*}', $value, $pattern).'?v='.self::version ($name, $surface);
                }
            }

            return '/'.$name;
        }

        $seen   = [];
        $prefix = $surface === 'bone' ? 'assets/' : '';
        $tail   = ltrim ($prefix.$name, '/');

        foreach (self::config ()['mask_urls'] ?? [] as $pattern => $disk)
        {
            if (!is_string ($pattern) || !is_string ($disk)) {continue;}

            if (!self::servesSurface ((string) $disk, $surface)) {continue;}
            if (preg_match (self::regex ($pattern), '/'.$tail, $m) !== 1) {continue;}

            $value = (string) ($m['s'] ?? '');

            // Rewrite only the tokens placeholder to prevent leaking it into URLs.
            $rewritten = str_replace ($pattern, str_replace ('{*}', $value, $pattern), $pattern);
            $seen[] = $rewritten;
        }

        return match (true)
        {
            $seen === []        => '/'.$name,
            count ($seen) === 1 => $seen[0],
            default             => reset ($seen),
        };
    }

    /**
     * Whether a resolved disk path belongs to a surface's theme or to Tokens.
     *
     * @since  0.0.1
     * @param  string $preview Resolved disk path.
     * @param  string $surface 'face' or 'bone'.
     * @return bool
     */
    private static function servesSurface (string $disk, string $surface): bool
    {
        if (str_contains ($disk, 'Views/Tokens')) {return true;}
        if (str_contains ($disk, '{face_theme_active_name}')) {return $surface === 'face';}
        if (str_contains ($disk, '{bone_theme_active_name}')) {return $surface === 'bone';}
        return false;
    }

    /**
     * Compile a URL pattern into a regex, capturing `{*}` as one group.
     *
     * @since  0.0.1
     * @param  string $pattern Pattern such as `/css/{*}`.
     * @return string Regex with delimiters, or '' when the pattern is static.
     */
    private static function regex (string $pattern): string
    {
        $quoted = preg_quote ($pattern, '~');

        // `{*}` is the wildcard marker; rebuild it after quoting.
        $quoted = str_replace (['\{', '\}', '\*'], ['{', '}', '*'], $quoted);

        $out = '';
        $i   = 0;
        $len = strlen ($quoted);

        while ($i < $len)
        {
            if ($quoted[$i] === '{' && $quoted[$i + 1] === '*' && $quoted[$i + 2] === '}')
            {
                $out .= '(?P<s>.+)';
                $i   += 3;
                continue;
            }

            $out .= $quoted[$i];
            $i++;
        }

        return '~^'.$out.'$~';
    }

    /**
     * Match a URL against a pattern; capture the `{*}` tail.
     *
     * @since  0.0.1
     * @param  string $pattern URL pattern.
     * @param  string $url     URL to test.
     * @return array{s:string}|null Captured tail, or null when no match.
     */
    private static function matchPattern (string $pattern, string $url): ?array
    {
        $regex = self::regex ($pattern);

        if ($regex === '') {return null;}

        if (preg_match ($regex, $url, $m) === 1) {return ['s' => (string) ($m['s'] ?? '')];}

        return null;
    }

    /**
     * Expand a disk pattern with captured values and theme placeholders.
     *
     * @since  0.0.1
     * @param  string               $disk Disk pattern.
     * @param  array{s:string}|null $match Captured tail.
     * @return string|null Absolute-ish path, or null when expansion fails.
     */
    private static function expand (string $disk, ?array $match): ?string
    {
        $value = (string) ($match['s'] ?? '');

        if (str_contains ($value, '..')) {return null;}

        $disk = str_replace (['{*}', '{face_theme_active_name}', '{bone_theme_active_name}'], [$value, self::theme ('face'), self::theme ('bone')], $disk);

        if ($disk === '') {return null;}

        $segments = explode ('/', ltrim ($disk, '/'));
        $resolved = [];

        foreach ($segments as $segment)
        {
            if ($segment === '') {continue;}

            if ($segment === '..') {return null;}

            if ($segment === 'public' && count ($resolved) === 0) {array_push ($resolved, realpath (publicpath)); continue;}
            if ($segment === 'src' && count ($resolved) === 0)     {array_push ($resolved, realpath (systemf)); continue;}

            $resolved[] = $segment;
        }

        if ($resolved === []) {return null;}

        $path = implode (sep, $resolved);

        return realpath ($path) ?: $path;
    }

    /**
     * Rewrite a pattern: replace `{*}` with the value and theme placeholders
     * with the active theme name.
     *
     * @since  0.0.1
     * @param  string $pattern Pattern text.
     * @param  string $value   Captured tail.
     * @param  string $surface Surface for theme expansion.
     * @return string Rewritten text.
     */
    private static function rewrite (string $pattern, string $value, string $surface): string
    {
        $pattern = str_replace ('{*}', $value, $pattern);

        foreach (['face', 'bone'] as $s)
        {
            $pattern = str_replace ('{'.$s.'_theme_active_name}', self::theme ($s), $pattern);
        }

        return $pattern;
    }

    /**
     * Resolve a config disk path (starts with `/`) to a real path.
     *
     * @since  0.0.1
     * @param  string $disk Disk pattern starting with `/`.
     * @return string Real path without placeholders.
     */
    private static function diskPath (string $disk): string
    {
        $segments = explode ('/', ltrim ($disk, '/'));

        $resolved = [];
        foreach ($segments as $segment)
        {
            if ($segment === '') {continue;}

            if ($segment === 'public') {array_push ($resolved, 'public'); continue;}
            if ($segment === 'src')    {array_push ($resolved, 'src'); continue;}

            $resolved[] = $segment;
        }

        return realpath (implode (sep, $resolved)) ?: '';
    }

    /**
     * Render a queue of names into tags.
     *
     * @since  0.0.1
     * @param  list<string> $names  Asset names.
     * @param  string       $surface 'face' or 'bone'.
     * @param  string       $kind   'style' or 'script'.
     * @return string HTML.
     */
    private static function render (array $names, string $surface, string $kind): string
    {
        $html = '';

        foreach ($names as $name)
        {
            $url = self::url ($name, $surface);

            if ($kind === 'style')
            {
                $html .= '<link rel="stylesheet" href="'.self::escape ($url).'">';
            }

            else
            {
                $html .= '<script src="'.self::escape ($url).'" defer></script>';
            }
        }

        return $html;
    }

    /**
     * Build the queued names for a surface (config first, then runtime),
     * appending the extension when missing.
     *
     * @since  0.0.1
     * @param  array            $config  Config list.
     * @param  string           $surface 'face' or 'bone'.
     * @param  string           $kind    'style' or 'script'.
     * @param  array<string, mixed> $all     Full config.
     * @return list<string> Names with extensions.
     */
    private static function names (array $config, string $surface, string $kind, array $all): array
    {
        $ext    = $kind === 'style' ? '.css' : '.js';
        $merged = array_values (array_filter ([...$config, ...(self::$queue[$surface][$kind.'s'] ?? [])], 'is_string'));
        $out    = [];

        foreach ($merged as $item)
        {
            $item = trim ((string) $item);

            if ($item === '') {continue;}

            // `css/style.css` → basename keeps the extension; keep the kind
            // folder only when there's no extension (config-style names).
            if (str_contains ($item, '.') && str_starts_with ($item, $base.'/')) {$item = basename ($item);}
            elseif (!str_contains (basename ($item), '.')) {$item .= $ext;}

            if (!str_contains ($item, '/') && !str_contains ($item, '.')) {$item .= $ext;}

            if (!in_array ($item, $out, true)) {$out[] = $item;}
        }

        return $out;
    }

    /**
     * First readable queue file for a name (theme root, then Tokens).
     *
     * @since  0.0.1
     * @param  string $item Queue entry.
     * @param  string $ext  Extension.
     * @param  string|null $prefix Optional folder prefix.
     * @return string File name without directory, or '' when absent.
     */
    private static function candidate (string $item, string $ext, ?string $prefix = null): string
    {
        $name = str_contains ($item, '.') ? $item : $item.$ext;
        $name = ltrim ($name, '/');

        foreach ([self::theme ('face'), self::theme ('bone')] as $theme)
        {
            $root = interlace ($theme === self::theme ('bone') ? systemf : publicpath, 'Themes', $theme);
            $path = $prefix === null ? interlace ($root, $name) : interlace ($root, $prefix, $name);

            if (is_file ($path)) {return str_contains ($item, '.') ? $name : basename ($name);}
        }

        $tokens = interlace (systemf, 'Views', 'Tokens', $name);

        if (is_file ($tokens)) {return $name;}

        return '';
    }

    /**
     * Cache-buster: file mtime when the file exists, else '0'.
     *
     * @since  0.0.1
     * @param  string $name    Asset name.
     * @param  string $surface 'face' or 'bone'.
     * @return string
     */
    private static function version (string $name, string $surface): string
    {
        return (string) (int) @filemtime (self::resolve ($name)['file'] ?? '');
    }

    /**
     * MIME type by extension.
     *
     * @since  0.0.1
     * @param  string $file File path.
     * @return string MIME type, or `application/octet-stream`.
     */
    private static function mime (string $file): string
    {
        return match (strtolower (pathinfo ($file, PATHINFO_EXTENSION)))
        {
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'text/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'  => 'font/ttf',
            'ico'  => 'image/x-icon',
            'html' => 'text/html; charset=utf-8',
            default => 'application/octet-stream',
        };
    }

    /**
     * Escape an attribute value.
     *
     * @since  0.0.1
     * @param  string $value Raw value.
     * @return string Escaped value.
     */
    private static function escape (string $value): string
    {
        return htmlspecialchars ($value, ENT_QUOTES, 'UTF-8');
    }
}
