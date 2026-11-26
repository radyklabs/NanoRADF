<?php declare (strict_types=1);

/**
 * Drip — the template engine behind every rendered page.
 * --
 * Drip reads an `.html` file, translates it into plain PHP, and caches the
 * result so the next request skips the work. The cache is rebuilt whenever the
 * source file changes, so a template can be edited and immediately reloaded.
 *
 * What templates can say to it:
 *
 * - `{{$var}}` prints a value, escaped for HTML. Spaces are optional.
 * - `{{!$var!}}` prints raw, for HTML you already trust.
 * - `{{-- … --}}` is dropped entirely.
 * - `@extends`, `@section`, `@yield` build the page out of a layout.
 * - `@include` pulls in another view, sharing the variables in scope.
 * - `@if / @elseif / @else / @endif` and `@foreach / @endfor` for control flow.
 * - `@style … @endstyle` and `@script … @endscript` for inline assets.
 * - `@php … @endphp` for the odd case that needs real PHP.
 * - `@class` for conditional class lists, `@enqueueStyles`/`@enqueueScripts`
 *   for the asset tags.
 *
 * A directive or echo written inside an HTML comment is unwrapped and compiled,
 * so a template still previews as plain static HTML. Unknown `@word` sequences
 * pass through untouched — CSS `@media` and `user@host` stay literal.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Services;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class DripService
{
    /**
     * Render a template file, wrapped in a layout when one applies.
     *
     * @since  0.0.1
     * @param  string      $path       Absolute path to the `.html` template.
     * @param  array       $data       Variables made available inside the template.
     * @param  string|null $layoutPath Layout file; wins over `@extends`.
     * @param  string|null $context    Module the view belongs to, when rendering from one.
     * @return string Rendered HTML.
     */
    public static function renderFile (string $path, array $data = [], ?string $layoutPath = null, ?string $context = null): string
    {
        $sections = [];
        $body     = self::render ($path, $data, $sections, $context);

        $layout = $layoutPath;

        if ($layout === null)
        {
            $template = self::load ($path);
            $layout   = $template['extends'] === null ? null : ViewsService::path ($template['extends'], $context);
        }

        if ($layout === null)
        {
            return $body;
        }

        // An explicit `@section('content')` wins; otherwise the child body is
        // the layout's content.
        if (!array_key_exists ('content', $sections))
        {
            $sections['content'] = $body;
        }

        return self::render ($layout, $data, $sections, $context);
    }

    /**
     * Render another view from inside a template, sharing its scope and sections.
     *
     * @since  0.0.1
     * @param  string      $name    View name, without extension.
     * @param  array       $extra   Variables layered over the caller's.
     * @param  array       $scope   The caller's own variables.
     * @param  string|null $context Module the view belongs to, when rendering from one.
     * @param  array       $sections Section store, shared with the caller.
     * @return string Rendered HTML.
     */
    public static function renderInclude (string $name, array $extra, array $scope, ?string $context, array &$sections): string
    {
        foreach (['__data', '__sections', '__sections_stack', '__context', '__level'] as $key)
        {
            unset ($scope[$key]);
        }

        return self::render (ViewsService::path ($name, $context), array_merge ($scope, $extra), $sections, $context);
    }

    /**
     * Run one compiled template, letting it fill the section store.
     *
     * @since  0.0.1
     * @param  string      $path     Absolute template path.
     * @param  array       $data     Variables for the template scope.
     * @param  array       $sections Section store (written by `@section`).
     * @param  string|null $context  Module the view belongs to, for `@include`.
     * @return string Rendered HTML.
     */
    private static function render (string $path, array $data, array &$sections, ?string $context): string
    {
        $template = self::load ($path);

        return ($template['render']) ($data, $sections, $context);
    }

    /**
     * Load a compiled template, recompiling when the source has changed.
     *
     * @since  0.0.1
     * @param  string $path Absolute template path.
     * @return array{render:callable, extends:?string}
     */
    private static function load (string $path): array
    {
        $cache = self::cachePath ($path);

        if (!is_file ($cache) || filemtime ($cache) < (int) filemtime ($path))
        {
            $source = @file_get_contents ($path);

            if ($source === false)
            {
                            throw new \RuntimeException ('drip_read', 500);
            }

            self::write ($cache, self::compile ($source, $path));
        }

        /** @var array{render:callable, extends:?string} $template */
        $template = require $cache;

        return $template;
    }

    /**
     * Turn template source into a PHP file that can be required.
     *
     * @since  0.0.1
     * @param  string $source Raw template content.
     * @param  string $path   Source path, used in error messages.
     * @return string PHP file contents, returning `render` and `extends`.
     */
    private static function compile (string $source, string $path): string
    {
        $blocks = [];

        // 1. Drip comments are removed before anything else.
        $source = (string) preg_replace ('/\{\{--.*?--\}\}/s', '', $source);

        // 2. Unwrap directive/echo written inside an HTML comment.
        $source = (string) preg_replace (
            '/<!--\s*(@[a-zA-Z_]+\s*\((?:[^()]|\([^()]*\))*\)|\{\{.*?\}\})\s*-->/s',
            '$1',
            $source
        );

        // 3. Raw PHP blocks stay opaque to the directive scanner.
        $source = (string) preg_replace_callback (
            '/@php\b(.*?)@endphp/s',
            static function (array $m) use (&$blocks): string
            {
                $token = "\x1A".count ($blocks)."\x1A";
                $blocks[$token] = "<?php{$m[1]}?>";

                return $token;
            },
            $source
        );

        // 4. Echoes: raw first, then escaped.
        $source = (string) preg_replace_callback (
            '/\{\{!\s*(.+?)\s*!\}\}/s',
            static fn (array $m): string => '<?php echo '.$m[1].'; ?>',
            $source
        );

        $source = (string) preg_replace_callback (
            '/\{\{\s*(.+?)\s*\}\}/s',
            static function (array $m) use ($path): string
            {
                self::guard ($m[1], $path);

                return '<?php echo e ('.$m[1].'); ?>';
            },
            $source
        );

        // 5. Layout inheritance is metadata, not output.
        $extends = null;

        $source = (string) preg_replace_callback (
            '/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            static function (array $m) use (&$extends): string
            {
                $extends ??= $m[1];

                return '';
            },
            $source
        );

        // 6. Remaining directives.
        $source = self::directives ($source);

        // 7. Restore opaque blocks.        if ($blocks !== [])
        {
            $source = strtr ($source, $blocks);
        }

        $meta = var_export ($extends, true);
        $note = var_export ($path, true);

        return "<?php declare (strict_types=1);\n"
            ."// Compiled by Drip from {$note} — do not edit.\n"
            ."return [\n"
            ."    'extends' => {$meta},\n"
            ."    'render'  => static function (array \$__data, array &\$__sections, ?string \$__context): string\n"
            ."    {\n"
            ."        \$__level          = ob_get_level ();\n"
            ."        \$__sections_stack = [];\n"
            ."        extract (\$__data, EXTR_SKIP);\n"
            ."        ob_start ();\n"
            ."        try {\n"
            ."?>{$source}<?php\n"
            ."            return (string) ob_get_clean ();\n"
            ."        } finally {\n"
            ."            while (ob_get_level () > \$__level) { ob_end_clean (); }\n"
            ."        }\n"
            ."    },\n"
            ."];\n";
    }

    /**
     * Replace the directives a template can use with their PHP equivalents.
     *
     * @since  0.0.1
     * @param  string $source Template content, echoes already compiled.
     * @return string Mixed PHP and HTML.
     */
    private static function directives (string $source): string
    {
        $arg = '\(((?:[^()]|\([^()]*\))*)\)';

        // Callback directives first: their replacement needs the parsed name.
        $source = (string) preg_replace_callback (
            '/@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(\[.*?\]))?\s*\)/s',
            static fn (array $m): string => '<?php echo '.self::class.'::renderInclude ('
                .var_export ($m[1], true).', '.($m[2] ?? '[]').', get_defined_vars (), $__context, $__sections); ?>',
            $source
        );

        // Inline assets: content stays verbatim between the markers.
        $source = (string) preg_replace_callback (
            '/@style\b(.*?)@endstyle\b/s',
            static fn (array $m): string => '<style>'.$m[1].'</style>',
            $source
        );

        $source = (string) preg_replace_callback (
            '/@script\b(.*?)@endscript\b/s',
            static fn (array $m): string => '<script>'.$m[1].'</script>',
            $source
        );

        $source = (string) preg_replace_callback (
            '/@section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            static fn (array $m): string => '<?php $__sections_stack[] = '.var_export ($m[1], true).'; ob_start (); ?>',
            $source
        );

        $source = (string) preg_replace (
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<?php echo $__sections[\'$1\'] ?? \'\'; ?>',
            $source
        );

        $source = (string) preg_replace (
            '/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\s*\)/',
            '<?php echo $__sections[\'$1\'] ?? \'$2\'; ?>',
            $source
        );

        $map = [
            // Control flow (longest name first so `@elseif` beats `@else`).
            '/@elseif\s*'.$arg.'/'  => '<?php elseif ($1): ?>',
            '/@endif\b/'            => '<?php endif; ?>',
            '/@if\s*'.$arg.'/'      => '<?php if ($1): ?>',
            '/@else\b/'             => '<?php else: ?>',
            '/@endforeach\b/'       => '<?php endforeach; ?>',
            '/@foreach\s*'.$arg.'/' => '<?php foreach ($1): ?>',
            '/@endfor\b/'           => '<?php endfor; ?>',
            '/@for\s*'.$arg.'/'     => '<?php for ($1): ?>',

            // Section close pairs with the opener emitted above.
            '/@endsection\b/' => '<?php $__sections[array_pop ($__sections_stack)] = ob_get_clean (); ?>',

            // Helpers.
            '/@class\s*'.$arg.'/' => '<?php echo classlist ($1); ?>',

            // Assets. Same namespace, no import needed.
            '/@enqueueStyles\b/'  => '<?php echo \\System\\Services\\AssetsService::renderFaceStyles (); ?>',
            '/@enqueueScripts\b/' => '<?php echo \\System\\Services\\AssetsService::renderFaceScripts (); ?>',
        ];

        foreach ($map as $pattern => $replace)
        {
            $source = (string) preg_replace ($pattern, $replace, $source);
        }

        return $source;
    }

    /**
     * Reject manual escaping inside `{{ }}` — the engine already escapes.
     *
     * @since  0.0.1
     * @param  string $expression Expression text between the braces.
     * @param  string $path       Template path, used in the error message.
     * @return void
     */
    private static function guard (string $expression, string $path): void
    {
        if (preg_match ('/(?<![\w$>:\\\\])(htmlspecialchars|htmlentities|e)\s*\(/', $expression, $m))
        {
                    throw new \RuntimeException ('drip_escape', 500);
        }
    }

    /**
     * Cache file path for a template.
     *
     * @since  0.0.1
     * @param  string $path Absolute template path.
     * @return string Absolute cache path.
     */
    private static function cachePath (string $path): string
    {
        $dir = (defined ('basepath') ? basepath : '').'storage'.sep.'cache'.sep.'views'.sep;

        if (!is_dir ($dir) && !@mkdir ($dir, 0775, true) && !is_dir ($dir))
        {
            $dir = sys_get_temp_dir ().sep.'nanoradf-views'.sep;
            @mkdir ($dir, 0775, true);
        }

        return $dir.md5 ($path).'.php';
    }

    /**
     * Write a compiled template atomically.
     *
     * @since  0.0.1
     * @param  string $path    Cache path.
     * @param  string $content PHP file contents.
     * @return void
     */
    private static function write (string $path, string $content): void
    {
        $temp = $path.'.'.bin2hex (random_bytes (4)).'.tmp';

        if (@file_put_contents ($temp, $content) === false || !@rename ($temp, $path))
        {
            @unlink ($temp);
                    throw new \RuntimeException ('drip_write', 500);
        }
    }
}
