<?php declare (strict_types=1);

/**
 * Turns a short view name into an `.html` file and renders it.
 * --
 * Views are named with dots instead of slashes, and the extension is never
 * written — the service adds it. Which folder a name points at depends on how
 * many parts it has and whether the first one starts with a capital letter:
 *
 * - `file` → `apps/Views/file.html`
 * - `folder.file` → `apps/Views/folder/file.html`
 * - `Module.file` → `modules/Module/Views/file.html`
 * - `Module.folder.file` → `modules/Module/Views/folder/file.html`
 *
 * A leading capital always means a module, which is why folders under a view
 * root stay lowercase. Inside a module controller a bare `file` resolves to that
 * module's own Views folder, and requires `app.modules` to be on.
 *
 * Every rendered view also receives the asset tags for both surfaces, so a
 * template can print `{{!$head!}}` without wiring anything up.
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
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class ViewsService
{
    /**
     * Render a view, wrapped in a layout when one is given.
     *
     * Layout resolution order:
     *   1. Explicit `$layout` argument (wins over `@extends`).
     *   2. Layout from the active theme (`<theme>/index.html`).
     *   3. No layout — the view renders as-is.
     *
     * The active theme comes from `gconfig('theme')['default']['public'|'private']`.
     * Only the active theme's layout is used — no scanning across themes, no
     * fallback to `src/Views/Layouts/`. If the theme's layout file is missing,
     * an exception is thrown so the missing layout is visible instead of silent.
     *
     * @since  0.0.1
     * @param  string      $name    View name, without extension.
     * @param  array       $data    Variables made available inside the template.
     * @param  string|null $layout  Layout name; wins over `@extends` in the view.
     * @param  string|null $context Module the view belongs to, when rendering from one.
     * @param  string      $surface `public` or `private`; picks the active theme.
     * @return string Rendered HTML.
     */
    public static function render (string $name, array $data = [], ?string $layout = null, ?string $context = null, string $surface = 'public'): string
    {
        // Asset tags are a system feature: every view gets them for free.
        // Explicit data wins, so a view can override any of these keys.
        $data += self::assets ();

        // Resolve layout: explicit argument first, then active theme layout.
        // Explicit layout is resolved from the active theme folder — never from
        // apps/Views/layouts/ and never by scanning across themes.
        $layoutPath = $layout === null
            ? self::resolveThemeLayout ($surface)
            : self::resolveThemeLayoutByName ($layout, $surface);

        return DripService::renderFile (
            self::path ($name, $context),
            $data,
            $layoutPath,
            $context
        );
    }

    /**
     * Resolve a named layout from the active theme folder.
     *
     * @since  0.0.2
     * @param  string $layout Layout name (e.g. 'main' → <theme>/main.html).
     * @param  string $surface `public` or `private`.
     * @return string Absolute path to <theme>/<layout>.html.
     */
    private static function resolveThemeLayoutByName (string $layout, string $surface): string
    {
        $theme  = theme ($surface);
        $base   = $surface === 'private' ? appf : publicpath;
        $folder = $surface === 'private' ? 'Themes' : 'themes';
        $path   = interlace ($base, $folder, $theme, $layout.'.html');

        if (!is_file ($path))
        {
            throw new \RuntimeException ("Layout '{$layout}' tidak ditemukan di tema '{$theme}' ({$path}). Tema aktif harus menyediakan file layout ini.");
        }

        return $path;
    }

    /**
     * Render a view on its own, with no layout around it.
     * --
     * Use this for partials and fragments — anything that is meant to be
     * included inside another template, or returned as a bare HTML snippet.
     *
     * @since  0.0.2
     * @param  string      $name    View name, without extension.
     * @param  array       $data    Variables made available inside the template.
     * @param  string|null $context Module the view belongs to, when rendering from one.
     * @return string Rendered HTML.
     */
    public static function fragment (string $name, array $data = [], ?string $context = null): string
    {
        return DripService::renderFile (self::path ($name, $context), $data + self::assets (), null, $context);
    }

    /**
     * Asset tag groups injected into every rendered view.
     *
     * @since  0.0.2
     * @return array{head:string, foot:string, bone_head:string, bone_foot:string}
     */
    private static function assets (): array
    {
        return [
            'head'      => AssetsService::renderFaceStyles (),
            'foot'      => AssetsService::renderFaceScripts (),
            'bone_head' => AssetsService::renderBoneStyles (),
            'bone_foot' => AssetsService::renderBoneScripts (),
        ];
    }

    /**
     * Resolve the active theme's layout file path.
     *
     * @since  0.0.2
     * @param  string $surface `public` for the frontend, `private` for the backend.
     * @return string Absolute path to `<theme>/index.html`.
     */
    private static function resolveThemeLayout (string $surface): string
    {
        $surface = $surface === 'private' ? 'private' : 'public';
        $theme   = theme ($surface);
        $base    = $surface === 'private' ? appf : publicpath;
        $folder  = $surface === 'private' ? 'Themes' : 'themes';
        $layout  = interlace ($base, $folder, $theme, 'index.html');

        if (!is_file ($layout))
        {
                    throw new \RuntimeException ("Layout tema '{$theme}' untuk surface '{$surface}' tidak ditemukan ({$layout}). Tema aktif harus menyediakan file index.html.");
        }

        return $layout;
    }

    /**
     * Which module a controller class belongs to.
     *
     * @since  0.0.1
     * @param  string $class Fully-qualified controller class name.
     * @return string|null Module name, or null when the controller lives in the app layer.
     */
    public static function context (string $class): ?string
    {
        if (preg_match ('/^Modules\\\\([A-Za-z0-9_]+)\\\\/', $class, $match)) {return $match[1];}
        return null;
    }

    /**
     * Find the file a view name refers to.
     *
     * @since  0.0.1
     * @param  string      $name    View name, without extension.
     * @param  string|null $context Module the view belongs to, when rendering from one.
     * @return string Absolute path to the view file.
     */
    public static function path (string $name, ?string $context = null): string
    {
        $name = trim ($name);

        if ($name === '')
        {
            throw new \InvalidArgumentException ('View name must not be empty.');
        }

        if (preg_match ('/\.html?$/i', $name))
        {
            throw new \InvalidArgumentException ("View \"{$name}\": pass the name without extension (\"file\" or \"folder.file\"); .html is appended automatically.");
        }

        $segments = explode ('.', $name);

        foreach ($segments as $segment)
        {
            if (!preg_match ('/^[A-Za-z0-9_-]+$/', $segment))
            {
                throw new \InvalidArgumentException ("View \"{$name}\": invalid segment \"{$segment}\".");
            }
        }

        $module = null;
        $rest   = $segments;

        if (count ($segments) > 1 && preg_match ('/^[A-Z]/', $segments[0]))
        {
            $module = $segments[0];
            $rest   = array_slice ($segments, 1);
        }

        elseif ($context !== null)
        {
            $module = $context;
        }

        if ($module !== null)
        {
            if (genv ('app.modules') !== true)
            {
                throw self::missing ($name, interlace (modulesf, $module, 'Views', ...$rest).'.html');
            }

            $base = interlace (modulesf, $module, 'Views');
        }

        else
        {
            $base = defined ('viewsfapp') ? viewsfapp : interlace (appf, 'Views');
        }

        $path = interlace ($base, ...$rest).'.html';

        if (!is_file ($path))
        {
            throw self::missing ($name, $path);
        }

        return $path;
    }

    /**
     * Build the error shown when a view file is missing.
     *
     * @since  0.0.1
     * @param  string $name View name as the caller wrote it.
     * @param  string $path Path that was looked at and does not exist.
     * @return \RuntimeException
     */
    private static function missing (string $name, string $path): \RuntimeException
    {
        return new \RuntimeException ("View \"{$name}\" not found ({$path}). Pastikan file ada dan pastikan menggunakan file html.");
    }
}
