<?php declare (strict_types=1);

/**
 * Views — resolves a dot-notation view name to an `.html` file and renders it.
 *
 * Name forms (never pass the extension):
 *   `file`              → apps/Views/file.html
 *   `folder.file`       → apps/Views/folder/file.html
 *   `Module.file`       → modules/Module/Views/file.html
 *   `Module.folder.file`→ modules/Module/Views/folder/file.html
 *
 * A leading capital letter marks a module; folders under a view root are
 * lowercase. Inside a module controller a plain `file` resolves to that
 * module's own Views folder (requires `app.modules=true`).
 *
 * @since 0.0.1
 */

namespace System\Services;

use function System\interlace;

final class ViewsService
{
    /**
     * Render a view, optionally wrapped by a layout file.
     *
     * @since  0.0.1
     * @param  string      $name    View name without extension.
     * @param  array       $data    Variables extracted into the template scope.
     * @param  string|null $layout  Layout name; overrides `@extends` when set.
     * @param  string|null $context Module name when rendering from a module.
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
     * Module name owning a controller class (`Modules\Shop\…` → `Shop`).
     *
     * @since  0.0.1
     * @param  string $class Fully-qualified controller class name.
     * @return string|null Module name, or null for the app layer.
     */
    public static function context (string $class): ?string
    {
        if (preg_match ('/^Modules\\\\([A-Za-z0-9_]+)\\\\/', $class, $match)) {return $match[1];}
        return null;
    }

    /**
     * Resolve a view name to an existing `.html` file.
     *
     * @since  0.0.1
     * @param  string      $name    View name without extension.
     * @param  string|null $context Module name when rendering from a module.
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
     * Build the not-found error, naming both likely causes.
     *
     * @since  0.0.1
     * @param  string $name View name as written by the caller.
     * @param  string $path Resolved path that does not exist.
     * @return \RuntimeException
     */
    private static function missing (string $name, string $path): \RuntimeException
    {
        return new \RuntimeException ("View \"{$name}\" not found ({$path}). Pastikan file ada dan pastikan menggunakan file html.");
    }
}
