<?php declare (strict_types=1);

namespace System\Services;

use function System\interlace;

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

if (!defined ('basepath'))
{
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

final class ViewsService
{
    /**
     * Render a view, wrapped in a layout when one is given.
     *
     * @since  0.0.1
     * @param  string      $name    View name, without extension.
     * @param  array       $data    Variables made available inside the template.
     * @param  string|null $layout  Layout name; wins over `@extends` in the view.
     * @param  string|null $context Module the view belongs to, when rendering from one.
     * @return string Rendered HTML.
     */
    public static function render (string $name, array $data = [], ?string $layout = null, ?string $context = null): string
    {
        // Asset tags are a system feature: every view gets them for free.
        // Explicit data wins, so a view can override any of these keys.
        $data += [
            'head'      => AssetsService::renderFaceStyles (),
            'foot'      => AssetsService::renderFaceScripts (),
            'bone_head' => AssetsService::renderBoneStyles (),
            'bone_foot' => AssetsService::renderBoneScripts (),
        ];

        return DripService::renderFile (
            self::path ($name, $context),
            $data,
            $layout === null ? null : self::path ($layout, $context),
            $context
        );
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
