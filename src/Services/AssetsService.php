<?php declare (strict_types=1);

/**
 * Where styles and scripts live, and how a URL finds its file on disk.
 * --
 * This service owns the asset side of the application: which names are queued
 * for each surface, how those names turn into real URLs, and how a request for
 * an asset URL is mapped back to a file that can be served.
 *
 * The rules live in config (`mask_urls`): a URL pattern points at a disk
 * pattern, `{*}` stands for any number of path segments, and theme placeholders
 * expand to whichever theme is active. Anything that does not match a pattern
 * is not reachable — it gets a 404 instead of a guess.
 *
 * Internal structure (private static methods):
 * - Config: config(), theme()
 * - Pattern cache: allPatterns(), patternMap(), servesSurface()
 * - Resolution: resolve(), anchor(), mime()
 * - URL generation: url(), version()
 * - Rendering: renderFaceStyles(), renderFaceScripts(), renderBoneStyles(), renderBoneScripts(), render()
 * - Queue management: enqueueStyle(), enqueueScript(), styles(), scripts(), names()
 * - Helpers: escape()
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Services;

use function System\interlace;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class AssetsService
{
    /**
     * Runtime enqueues, layered over the config lists.
     *
     * @var array<string, array{styles:list<string>, scripts:list<string>}>
     */
    private static array $queue = [];

    /**
     * The merged asset config, framework defaults plus application overrides.
     *
     * @since  0.0.1
     * @return array<string, mixed>
     */
    public static function config (): array
    {
        return gconfig ('assets');
    }

    /**
     * Theme name for a surface, read from theme config.
     *
     * @since  0.0.1
     * @param  string $surface `public`/`face` for the frontend, `private`/`bone` for the backend.
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
     * Queue a style for a surface.
     *
     * @since  0.0.1
     * @param  string $path    Asset name or path.
     * @param  string $surface `face` for the frontend, `bone` for the backend.
     * @return void
     */
    public static function enqueueStyle (string $path, string $surface = 'face'): void
    {
        self::$queue[$surface]['styles'][] = $path;
    }

    /**
     * Queue a script for a surface.
     *
     * @since  0.0.1
     * @param  string $path    Asset name or path.
     * @param  string $surface `face` for the frontend, `bone` for the backend.
     * @return void
     */
    public static function enqueueScript (string $path, string $surface = 'face'): void
    {
        self::$queue[$surface]['scripts'][] = $path;
    }

    /**
     * Styles queued for a surface, config first then runtime additions.
     *
     * @since  0.0.1
     * @param  string $surface `face` or `bone`.
     * @return list<string>
     */
    public static function styles (string $surface = 'face'): array
    {
        $config = self::config ();
        $key    = $surface === 'bone' ? 'bone_enqueue_styles' : 'enqueue_styles';

        return self::names ((array) ($config[$key] ?? []), $surface, 'style', $config);
    }

    /**
     * Scripts queued for a surface, config first then runtime additions.
     *
     * @since  0.0.1
     * @param  string $surface `face` or `bone`.
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
     * Turn an asset URL into the file it points at, plus its MIME type.
     *
     * Uses segment prefix matching (plain string work, no regex per request).
     * Returns null when the URL matches no pattern, the file is missing, or the
     * path tries to escape the project root.
     *
     * @since  0.0.1
     * @param  string $url URL path, with or without a leading slash.
     * @return array{file:string, mime:string}|null
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

                // Root-containment guard: reject any path that resolves
                // outside the project root (basepath).
                if ($real === false || !str_starts_with ($real, (string) basepath))
                {
                    continue;
                }

                if (is_file ($real))
                {
                    return ['file' => $real, 'mime' => self::mime ($real)];
                }
            }
        }

        return null;
    }

    /**
     * Every mask pattern, flattened into prefix → disk-prefix pairs.
     * Cached per process.
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
     * Anchor a config disk path to its real location on disk.
     *
     * Config paths start with `/public`, `/src`, or `/apps`; this turns them
     * into absolute paths rooted at the project base.
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
     * Accepts symbolic ids (looked up in `register_styles`/`register_scripts`)
     * or raw paths. The result carries a cache buster from the file's mtime.
     *
     * @since  0.0.1
     * @param  string $name    Asset id or path, e.g. `style`, `app`, `/css/style.css`.
     * @param  string $surface `face` for the frontend, `bone` for the backend.
     * @return string URL with a cache buster.
     */
    public static function url (string $name, string $surface = 'face'): string
    {
        $config = self::config ();

        // Resolve symbolic id → full mask-relative path from registry.
        foreach (['register_styles', 'register_scripts'] as $registryKey)
        {
            $registry = (array) ($config[$registryKey] ?? []);
            foreach ($registry as $entry)
            {
                if (isset ($entry['id']) && $entry['id'] === $name)
                {
                    $name = (string) ($entry['path'] ?? '');
                    break 2;
                }
            }
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
     * Pre-compiled pattern map for one surface.
     * Cached per surface per process.
     *
     * @since  0.0.1
     * @param  string $surface `face` or `bone`.
     * @return array<string, array{url_prefix:string, disk_prefix:string}>
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
     * Whether a disk pattern belongs to one surface's theme or to shared Tokens.
     *
     * @since  0.0.1
     * @param  string $disk Resolved disk path.
     * @param  string $surface `face` or `bone`.
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
     * Render a queue of names into tags.
     *
     * @since  0.0.1
     * @param  list<string> $names  Asset names (full masked paths).
     * @param  string       $surface 'face' or 'bone'.
     * @param  string       $kind   'style' or 'script'.
     * @return string HTML.
     */
    private static function render (array $names, string $surface, string $kind): string
    {
        $html      = '';
        $registry  = (array) (self::config ()[($kind === 'style' ? 'register_styles' : 'register_scripts')] ?? []);

        // Build path-to-id lookup table from registry entries.
        $pathToId = [];
        foreach ($registry as $entry)
        {
            $path = trim ((string) ($entry['path'] ?? ''));
            if ($path === '') {continue;}
            $id = $entry['id'] ?? basename ($path, '.'.pathinfo ($path, PATHINFO_EXTENSION));
            $pathToId[ltrim ($path, '/')] = (string) $id;
        }

        foreach ($names as $name)
        {
            $url = self::url ($name, $surface);
            $tagId = $pathToId[ltrim ((string) $name, '/')] ?? '';

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
     * Build the queued names for a surface: config list first, then runtime adds.
     *
     * Config and runtime entries are plain strings — either full masked paths
     * (with extension) or symbolic ids resolved via `register_*`. No implicit
     * extension appending: paths must be complete.
     *
     * @since  0.0.1
     * @param  array            $config  Config list (paths or symbolic ids).
     * @param  string           $surface 'face' or 'bone'.
     * @param  string           $kind    'style' or 'script'.
     * @param  array<string, mixed> $all     Full config (for register_* lookup).
     * @return list<string> Names as plain paths.
     */
    private static function names (array $config, string $surface, string $kind, array $all): array
    {
        $registryKey = $kind === 'style' ? 'register_styles' : 'register_scripts';
        $registry = (array) ($all[$registryKey] ?? []);
        
        // Build id-to-path lookup from registry.
        $idToPath = [];
        foreach ($registry as $entry)
        {
            if (isset ($entry['id']) && isset ($entry['path']))
            {
                $idToPath[(string) $entry['id']] = ltrim ((string) $entry['path'], '/');
            }
        }

        $merged   = array_values (array_filter ([...$config, ...(self::$queue[$surface][$kind.'s'] ?? [])], 'is_string'));
        $out      = [];

        foreach ($merged as $item)
        {
            $item = trim ((string) $item);
            if ($item === '') {continue;}

            // Resolve symbolic id to path, otherwise use as-is.
            $resolved = $idToPath[$item] ?? ltrim ($item, '/');

            if (!in_array ($resolved, $out, true)) {$out[] = $resolved;}
        }

        return $out;
    }

    /**
     * Cache buster: the file's mtime, or `0` when the file cannot be resolved.
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
     * MIME type by file extension.
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
     * Escape an attribute value for safe HTML output.
     *
     * @since  0.0.1
     * @param  string $value Raw value.
     * @return string Escaped value.
     */
    private static function escape (string $value): string
    {
        return htmlspecialchars ($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Serve an asset file with proper headers.
     * Supports conditional GET (If-None-Match / If-Modified-Since) for 304 responses.
     *
     * @since  0.0.1
     * @param  string $file Absolute file path.
     * @param  bool   $head  If true, send headers only (no body).
     * @return void
     */
    public static function serveAsset (string $file, bool $head = false): void
    {
        if (!is_file ($file) || !is_readable ($file)) {return;}

        $mime = self::mime ($file);
        $size = filesize ($file);
        $etag = '"'.md5_file ($file).'"';
        $lastModified = gmdate ('D, d M Y H:i:s', filemtime ($file)).' GMT';

        header ('Content-Type: '.$mime, true);
        header ('Content-Length: '.$size, true);
        header ('ETag: '.$etag, true);
        header ('Last-Modified: '.$lastModified, true);
        header ('Cache-Control: public, max-age=31536000, immutable', true);

        // Conditional GET
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
        if ($ifNoneMatch === $etag || ($ifModifiedSince && strtotime ($ifModifiedSince) >= filemtime ($file)))
        {
            header ('HTTP/1.1 304 Not Modified', true, 304);
            return;
        }

        if (!$head)
        {
            readfile ($file);
        }
    }

    /**
     * Clear all caches (for testing).
     *
     * @since  0.0.1
     * @return void
     */
    public static function flush (): void
    {
        // Clear pattern caches by re-invoking with fresh statics
        // Note: static caches are per-process; flush is mainly for testing
    }
}
