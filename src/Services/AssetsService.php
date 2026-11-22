<?php declare (strict_types=1);

namespace System\Services;

/**
 * AssetsService — enqueue, URL generation, and masked-URL resolution.
 *
 * Theme files live in `public/themes/<scope>` (scope `default`) or
 * `src/Themes/admin` (scope `admin`). Public URLs are `/themes/<scope>/<path>`;
 * masks rewrite them to a virtual prefix (`/Files/<slug>/<path>`) whose disk
 * file is resolved by {@see resolve()} through the asset route.
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
     * Read the asset configuration file.
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
     * Register a style for a scope.
     *
     * @since  0.0.1
     * @param  string $path  Asset path relative to the scope root.
     * @param  string $scope 'default' or 'admin'.
     * @return void
     */
    public static function enqueueStyle (string $path, string $scope = 'default'): void
    {
        self::$queue[$scope]['styles'][] = $path;
    }

    /**
     * Register a script for a scope.
     *
     * @since  0.0.1
     * @param  string $path  Asset path relative to the scope root.
     * @param  string $scope 'default' or 'admin'.
     * @return void
     */
    public static function enqueueScript (string $path, string $scope = 'default'): void
    {
        self::$queue[$scope]['scripts'][] = $path;
    }

    /**
     * All queued styles for a scope (config first, then runtime).
     *
     * @since  0.0.1
     * @param  string $scope 'default' or 'admin'.
     * @return array<int, array{name:string, base:string}>
     */
    public static function styles (string $scope = 'default'): array
    {
        $config = self::config ();
        $names  = $scope === 'admin' ? 'admin_enqueue_styles' : 'enqueue_styles';
        $merged = [...(array) ($config[$names] ?? []), ...(self::$queue[$scope]['styles'] ?? [])];

        return self::normalize ($merged, 'style');
    }

    /**
     * All queued scripts for a scope (config first, then runtime).
     *
     * @since  0.0.1
     * @param  string $scope 'default' or 'admin'.
     * @return array<int, array{name:string, base:string}>
     */
    public static function scripts (string $scope = 'default'): array
    {
        $config = self::config ();
        $names  = $scope === 'admin' ? 'admin_enqueue_scripts' : 'enqueue_scripts';
        $merged = [...(array) ($config[$names] ?? []), ...(self::$queue[$scope]['scripts'] ?? [])];

        return self::normalize ($merged, 'script');
    }

    /**
     * Render queued styles as `<link>` tags.
     *
     * @since  0.0.1
     * @param  string $scope 'default' or 'admin'.
     * @return string HTML.
     */
    public static function renderStyles (string $scope = 'default'): string
    {
        $html = '';

        foreach (self::styles ($scope) as $asset)
        {
            $href = self::escape (self::mask ($scope, $asset));
            $html .= '<link rel="stylesheet" href="'.$href.'">';
        }

        return $html;
    }

    /**
     * Render queued scripts as `<script>` tags.
     *
     * @since  0.0.1
     * @param  string $scope 'default' or 'admin'.
     * @return string HTML.
     */
    public static function renderScripts (string $scope = 'default'): string
    {
        $html = '';

        foreach (self::scripts ($scope) as $asset)
        {
            $src  = self::escape (self::mask ($scope, $asset));
            $html .= '<script src="'.$src.'" defer></script>';
        }

        return $html;
    }

    /**
     * URL for one asset: masked when a mask rule matches its scope, otherwise
     * the public theme URL.
     *
     * @since  0.0.1
     * @param  string                          $scope 'default' or 'admin'.
     * @param  array{name:string, base:string} $asset Resolved asset entry.
     * @return string URL with a cache buster.
     */
    public static function mask (string $scope, array $asset): string
    {
        $path   = $asset['base'] === '' ? $asset['name'] : $asset['base'].'/'.$asset['name'];
        $buster = self::version ($scope, $asset);

        foreach ((array) (self::config ()['mask_urls'] ?? []) as $mask)
        {
            if (($mask['slug'] ?? '') === '' || ($mask['dir'] ?? '') === '') {continue;}

            if (realpath ((string) $mask['dir']) === realpath (interlace (self::root ($scope), $asset['base'])))
            {
                return '/'.trim ((string) $mask['slug'], '/').'/'.ltrim ($asset['name'], '/').'?v='.$buster;
            }
        }

        return '/themes/'.$scope.'/'.ltrim ($path, '/').'?v='.$buster;
    }

    /**
     * Theme root directory for a scope.
     *
     * @since  0.0.1
     * @param  string $scope 'default' or 'admin'.
     * @return string Absolute directory path.
     */
    private static function root (string $scope): string
    {
        return $scope === 'admin'
            ? interlace (systemf, 'Themes', 'admin')
            : interlace (publicpath, 'themes', 'default');
    }

    /**
     * Map a request path to a root directory: a mask prefix or a theme scope.
     *
     * @since  0.0.1
     * @param  string $url Path after the front controller.
     * @return array{root:string, relative:string}|null Null when nothing matches.
     */
    private static function rootAndRelative (string $url): ?array
    {
        foreach ((array) (self::config ()['mask_urls'] ?? []) as $mask)
        {
            $slug = trim ((string) ($mask['slug'] ?? ''), '/');

            if ($slug === '' || ($mask['dir'] ?? '') === '') {continue;}
            if (!str_starts_with ($url, $slug.'/')) {continue;}

            return ['root' => (string) $mask['dir'], 'relative' => substr ($url, strlen ($slug) + 1)];
        }

        if (preg_match ('#^themes/(default|admin)/(.+)$#', $url, $m))
        {
            return ['root' => self::root ($m[1]), 'relative' => $m[2]];
        }

        return null;
    }

    /**
     * Resolve a request path to a real, contained file and its MIME type.
     *
     * @since  0.0.1
     * @param  string $url Path after the front controller.
     * @return array{file:string, mime:string}|null Null when unknown, missing, or escaping the root.
     */
    public static function resolve (string $url): ?array
    {
        $match = self::rootAndRelative ($url);

        if ($match === null) {return null;}

        $file = self::contained ($match['root'], $match['relative']);

        if ($file === null) {return null;}

        return ['file' => $file, 'mime' => self::mime ($file)];
    }

    /**
     * Join a root and relative path, rejecting traversal outside the root.
     *
     * @since  0.0.1
     * @param  string $root     Absolute root directory.
     * @param  string $relative Relative path from the URL.
     * @return string|null Absolute file path, or null when absent or outside the root.
     */
    private static function contained (string $root, string $relative): ?string
    {
        $file = interlace ($root, $relative);

        if (!is_file ($file)) {return null;}

        $rootReal = realpath ($root);
        $fileReal = realpath ($file);

        if ($rootReal === false || $fileReal === false || !str_starts_with ($fileReal, $rootReal)) {return null;}

        return $file;
    }

    /**
     * MIME type by extension.
     *
     * @since  0.0.1
     * @param  string $file File path.
     * @return string MIME type, or `application/octet-stream` when unknown.
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
     * Absolute disk path for a scope asset.
     *
     * @since  0.0.1
     * @param  string                          $scope 'default' or 'admin'.
     * @param  array{name:string, base:string} $asset Resolved asset entry.
     * @return string Absolute file path.
     */
    public static function filePath (string $scope, array $asset): string
    {
        return interlace (self::root ($scope), $asset['base'], $asset['name']);
    }

    /**
     * Cache-buster for an asset: its file mtime.
     *
     * @since  0.0.1
     * @param  string                          $scope 'default' or 'admin'.
     * @param  array{name:string, base:string} $asset Resolved asset entry.
     * @return string Unix mtime, or '0' when the file is absent.
     */
    private static function version (string $scope, array $asset): string
    {
        $path = $asset['base'] === '' ? $asset['name'] : $asset['base'].'/'.$asset['name'];

        return (string) (int) @filemtime (interlace (self::root ($scope), $path));
    }

    /**
     * Drop duplicate entries, preserving order.
     *
     * @since  0.0.1
     * @param  list<string> $items Raw list.
     * @return list<string> Unique list.
     */
    private static function normalize (array $items, string $type): array
    {
        $ext = $type === 'script' ? '.js' : '.css';
        $out = [];

        foreach ($items as $item)
        {
            if (!is_string ($item) || trim ($item) === '') {continue;}

            $item  = trim ($item);
            $parts = explode ('/', $item);
            $last  = array_pop ($parts);
            $base  = implode ('/', $parts);

            if (!str_contains ($last, '.')) {$last .= $ext;}

            $key = $base === '' ? $last : $base.'/'.$last;

            if (isset ($out[$key])) {continue;}
            $out[$key] = ['name' => $last, 'base' => $base];
        }

        return array_values ($out);
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
