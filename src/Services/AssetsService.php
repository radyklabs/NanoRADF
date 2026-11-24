<?php declare (strict_types=1);

/**
 * AssetsService — enqueue, URL masking, and asset serving.
 *
 * Mask patterns live in `apps/Config/AssetsConfig.php` (`mask_urls`): a URL pattern
 * maps to a disk pattern. `{*}` matches any number of segments;
 * `{face_theme_active_name}` / `{bone_theme_active_name}` expand to the active
 * theme. Only paths matching a pattern are reachable — everything else is
 * unknown and served 404.
 *
 * @since 0.0.1
 */

namespace System\Services;

use function System\interlace;

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
        // $config = require interlace (configf, fl ('AssetsConfig'));
        // if (is_readable (interlace (configfapp, fl ('AssetsConfig'))))
        // {
            // $configapp = require interlace (configfapp, fl ('AssetsConfig'));
        // }
        $config = gconfig ('assets');

        return $config;
    }

    /**
     * Active theme name for a surface (config value, override in controllers).
     *
         * Reads from gconfig('theme')['default']['public'|'private'].
         * Accepts 'public', 'private', 'face', or 'bone' (backwards compat alias).
         *
     * @since  0.0.1
         * @param  string $surface 'public'/'face' (frontend) or 'private'/'bone' (backend).
     * @return string Theme name.
     */
        public static function theme (string $surface = 'public'): string
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
         * Uses segment prefix matching (O(n) string ops) instead of regex per request.
         *
     * @since  0.0.1
     * @param  string $url URL path (no leading slash required).
     * @return array{file:string, mime:string}|null Null when unknown, missing, or escaping the root.
     */
    public static function resolve (string $url): ?array
    {
        $url = $url === '' ? '/' : '/'.ltrim ($url, '/');

            $allPatterns = self::allPatterns ();

            foreach ($allPatterns as $urlPrefix => $cfg)
        {
                if (str_starts_with ($url, $urlPrefix))
                {
                    $value = substr ($url, strlen ($urlPrefix));
                    if (str_contains ($value, '..')) {return null;}

                    $diskPath = self::anchor ($cfg['disk_prefix'].$value);
                    $real = realpath ($diskPath);

                    if ($real !== false && is_file ($real))
                    {
                        return ['file' => $real, 'mime' => self::mime ($real)];
                    }
                }
        }

        return null;
    }

        /**
         * Get all mask patterns without surface filtering (for resolve).
         *
         * @since  0.0.1
         * @return array<string, array{url_prefix:string, disk_prefix:string}>
         */
        private static function allPatterns (): array
        {
            static $cache = null;

            if ($cache !== null) {return $cache;}

            $config = self::config ();
            $faceTheme = self::theme ('face');
            $boneTheme = self::theme ('bone');
            $map = [];

            foreach ($config['mask_urls'] ?? [] as $urlPattern => $diskPattern)
            {
                if (!is_string ($urlPattern) || !is_string ($diskPattern)) {continue;}

                $starPos = strpos ($urlPattern, '{*}');
                if ($starPos === false) {continue;}

                $urlPrefix = substr ($urlPattern, 0, $starPos);
                $diskPrefix = substr ($diskPattern, 0, strpos ($diskPattern, '{*}'));

                $diskPrefix = str_replace (
                    ['{face_theme_active_name}', '{bone_theme_active_name}'],
                    [$faceTheme, $boneTheme],
                    $diskPrefix
                );

                $map[$urlPrefix] = [
                    'url_prefix' => $urlPrefix,
                    'disk_prefix' => $diskPrefix,
                ];
            }

            $cache = $map;
            return $map;
        }

        /**
         * Anchor a config disk path (leading `/public` or `/src`) to its real root.
         *
         * Config disk patterns are rooted at the project base, e.g. `/public/themes/...`.
         *
         * @since  0.0.1
         * @param  string $path Disk path starting with `/`.
         * @return string Absolute path.
         */
        private static function anchor (string $path): string
        {
            if (str_starts_with ($path, '/public/'))
            {
                return interlace (basepath, 'public', substr ($path, strlen ('/public/')));
            }

            if (str_starts_with ($path, '/src/'))
            {
                return interlace (systemf, substr ($path, strlen ('/src/')));
            }

                    if (str_starts_with ($path, '/apps/'))
                    {
                        return interlace (appf, substr ($path, strlen ('/apps/')));
                    }

            return $path;
        }

    /**
     * URL for a queued asset name, masked through the configured patterns.
     *
     * Accepts either symbolic ids (looked up in `id_registry`) or raw paths.
         * Uses segment prefix matching (O(n) string ops) instead of regex per request.
     *
     * @since  0.0.1
     * @param  string $name    Asset id or path, e.g. `style`, `design-system`, `css/style.css`.
         * @param  string $surface 'face' (public frontend) or 'bone' (backend).
     * @return string URL with a cache buster.
     */
    public static function url (string $name, string $surface = 'face'): string
    {
        $config = self::config ();
        $registry = (array) ($config['id_registry'] ?? []);

        // Resolve symbolic id → full mask-relative path from registry.
        if (isset ($registry[$name]))
        {
            $name = (string) $registry[$name];
        }

        // Fast-path for legacy ds/ tokens prefix.
        if (str_starts_with ($name, 'ds/'))
        {
                $patternMap = self::patternMap ($surface);
                foreach ($patternMap as $prefix => $cfg)
            {
                    if (str_starts_with ($name, $prefix))
                {
                        $tail = substr ($name, strlen ($prefix));
                        $url = $cfg['url_prefix'].$tail;
                        return $url.'?v='.self::version ($name, $surface);
                }
            }
            return '/'.$name;
        }

            $patternMap = self::patternMap ($surface);
        $prefix = $surface === 'bone' ? 'assets/' : '';
            $tail = '/'.ltrim ($prefix.$name, '/');

            foreach ($patternMap as $urlPrefix => $cfg)
            {
                if (str_starts_with ($tail, $urlPrefix))
                {
                    $value = substr ($tail, strlen ($urlPrefix));
                    $url = $cfg['url_prefix'].$value;
                    return $url.'?v='.self::version ($name, $surface);
                }
            }

            return '/'.$name;
        }

        /**
         * Build pre-compiled pattern map for a surface.
         * Returns [url_prefix => [url_prefix, disk_prefix, theme_placeholder]].
         *
         * @since  0.0.1
         * @param  string $surface 'face' or 'bone'.
         * @return array<string, array{url_prefix:string, disk_prefix:string, theme:string}>
         */
        private static function patternMap (string $surface): array
        {
            static $cache = [];

            if (isset ($cache[$surface])) {return $cache[$surface];}

            $config = self::config ();
            $faceTheme = self::theme ('face');
            $boneTheme = self::theme ('bone');
            $map = [];

            foreach ($config['mask_urls'] ?? [] as $urlPattern => $diskPattern)
        {
                if (!is_string ($urlPattern) || !is_string ($diskPattern)) {continue;}

                // Only include patterns for this surface
                if (!self::servesSurface ($diskPattern, $surface)) {continue;}

                // Extract prefix before {*}
                $starPos = strpos ($urlPattern, '{*}');
                if ($starPos === false) {continue;}

                $urlPrefix = substr ($urlPattern, 0, $starPos);
                $diskPrefix = substr ($diskPattern, 0, strpos ($diskPattern, '{*}'));

                // Expand theme placeholders in disk prefix
                $diskPrefix = str_replace (
                    ['{face_theme_active_name}', '{bone_theme_active_name}'],
                    [$faceTheme, $boneTheme],
                    $diskPrefix
                );

                $map[$urlPrefix] = [
                    'url_prefix' => $urlPrefix,
                    'disk_prefix' => $diskPrefix,
                ];
        }

            $cache[$surface] = $map;
            return $map;
    }

    /**
     * Whether a resolved disk path belongs to a surface's theme or to Tokens.
     *
     * @since  0.0.1
     * @param  string $preview Resolved disk path.
         * @param  string $surface 'face' (public frontend) or 'bone' (backend).
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
        $html      = '';
        $registry  = (array) (self::config ()['id_registry'] ?? []);

        foreach ($names as $name)
        {
            $url = self::url ($name, $surface);

            // Look up original id from registry to use as tag id attribute.
            $tagId = '';
            foreach ($registry as $id => $stem)
            {
                $expected = str_contains ((string) $stem, '.') ? (string) $stem : ((string) $stem).($kind === 'style' ? '.css' : '.js');
                if ($expected === $name) {$tagId = $id; break;}
            }

            if ($kind === 'style')
            {
                $html .= '<link rel="stylesheet" href="'.self::escape ($url).'"';
                if ($tagId !== '') {$html .= ' id="'.self::escape ($tagId).'"';}
                $html .= '>';
            }

            else
            {
                $html .= '<script src="'.self::escape ($url).'" defer';
                if ($tagId !== '') {$html .= ' id="'.self::escape ($tagId).'"';}
                $html .= '></script>';
            }
        }

        return $html;
    }

    /**
     * Build the queued names for a surface (config first, then runtime),
     * appending the extension when missing.
     *
     * Config entries may be either symbolic ids (looked up in `id_registry`)
     * or raw paths. A raw path already containing a dot is kept as-is; an id
     * is expanded to `<registry_entry>.<ext>`.
     *
     * @since  0.0.1
     * @param  array            $config  Config list (mixed ids / paths).
     * @param  string           $surface 'face' or 'bone'.
     * @param  string           $kind    'style' or 'script'.
     * @param  array<string, mixed> $all     Full config (for id_registry lookup).
     * @return list<string> Names with extensions.
     */
    private static function names (array $config, string $surface, string $kind, array $all): array
    {
        $ext      = $kind === 'style' ? '.css' : '.js';
        $registry = (array) ($all['id_registry'] ?? []);
        $merged   = array_values (array_filter ([...$config, ...(self::$queue[$surface][$kind.'s'] ?? [])], 'is_string'));
        $out      = [];

        foreach ($merged as $item)
        {
            $item = trim ((string) $item);
            if ($item === '') {continue;}

            // Registry entries carry mask-relative stem without extension; append ext directly.
            if (isset ($registry[$item]))
            {
                $resolved = (string) $registry[$item] . $ext;
            }

            elseif (str_contains ($item, '.'))
            {
                $resolved = ltrim ($item, '/');
            }

            else
            {
                $resolved = ltrim ($item, '/').$ext;
            }

            if (!in_array ($resolved, $out, true)) {$out[] = $resolved;}
        }

        return $out;
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
