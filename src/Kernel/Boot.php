<?php declare (strict_types=1);

namespace System\Kernel;

use Exception;

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p>");
}

/**
 * Kernel bootstrap — one-shot autoloader registration.
 *
 * @since 0.0.1
 */
final class Boot
{
    /**
     * Register the PSR-4 autoloader. Idempotent: safe to call repeatedly.
     *
     * @since  0.0.1
     * @return void
     */
    public static function initiate ()
    {
        static $registered = false;
        if ($registered) {return;}
        $registered = true;

        self::autoloadHelper ();

        spl_autoload_register ([self::class, 'autoloader']);
    }

    /**
     * PSR-4 autoloader registration — two roots, always active (D6/D9):
     *
     *   System\X\Y        → <root>/src/X/Y.php
     *   App\X\Y           → <root>/apps/X/Y.php
     *   Modules\Name\Y    → <root>/modules/Name/Y.php
     *
     * Prefixes come from the global namespace constants defined in `.config`:
     * `nssys` ("System"), `nsapp` ("App"), `nsmod` ("Modules"). `app.modules`
     * gates only the per-module folder/namespace templates in `.structures`.
     *
     * @since 0.0.1
     */
    public static function autoloader (string $class): void
    {
        $class = ltrim ($class, '\\');

        if (str_starts_with ($class, nssys))
        {
            $system_file = interlace (systemf, substr ($class, strlen (nssys)) . '.php');
            if (is_readable ($system_file))
            {
                require_once $system_file;
            }

            return;
        }

        if (str_starts_with ($class, nsapp))
        {
            $app_file = interlace (appf, substr ($class, strlen (nsapp)) . '.php');
            if (is_readable ($app_file))
            {
                require_once $app_file;
            }

            return;
        }

        if (str_starts_with ($class, nsmod))
        {
            $app_file = interlace (modulesf, substr ($class, strlen (nsmod)) . '.php');
            if (is_readable ($app_file))
            {
                require_once $app_file;
            }

            return;
        }
    }

    private static function autoloadHelper (): void
    {
        $stems       = [];

        $helper_config = interlace (configf, fl ('HelperConfig'));
        if (is_readable ($helper_config)) {$stems = (array) require $helper_config;}

        foreach ($stems as $stem)
        {
            $file = interlace (helperf, fl ("{$stem}Helper"));
            if (!is_readable ($file)) {throw new Exception ('helper', 500);}
            require_once $file;
            
            // If there will be helper in apps
            // $fileapp = interlace (helperfapp, fl ("{$stem}Helper"));
            // if (!is_readable ($fileapp)) {throw new Exception ('helperapp', 500);}
            // require_once $fileapp;
        }
    }
}