<?php declare (strict_types=1);

/**
 * Kernel bootstrap — PSR-4 autoloader and helper initializer.
 * --
 * The framework's bootstrapping anchor. Registers the single shared autoloader.
 * Also loads application helpers from config before the autoloader
 * accepts any class. Safe to call repeatedly.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace System\Kernel;

use Exception;
use function System\interlace;
use function System\fl;

if (!defined ('basepath'))
{
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
}

final class Boot
{
    /**
     * Register the PSR-4 autoloader if not yet done.
     *
     * Calling multiple times is safe — the autoloader runs exactly once per process.
     * Helper files are loaded first so they are available to classes that follow.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    void
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
     * PSR-4 autoloader — resolves classes from their namespace to disk paths.
     *
     * Three autoload roots are always active:
     *   `System\…`  → src/
     *   `App\…`     → apps/
     *   `Modules\…` → modules/
     *
     * Only `app.modules=true` in the environment activates module sub-folders.
     * A missing file simply falls through — no error is thrown, allowing lazy
     * discovery of optional classes.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @param     string $class Fully qualified class name.
     * @return    void
     */
    public static function autoloader (string $class): void
    {
        $class = ltrim ($class, '\\');

        if (str_starts_with ($class, nssys))
        {
            $system_file = interlace (systemf, fl (substr ($class, strlen (nssys))));
            if (is_readable ($system_file)) {require_once $system_file;}
            return;
        }

        if (str_starts_with ($class, nsapp))
        {
            $app_file = interlace (appf, fl (substr ($class, strlen (nsapp))));
            if (is_readable ($app_file)) {require_once $app_file;}
            return;
        }

        if (str_starts_with ($class, nsmod))
        {
            $app_file = interlace (modulesf, fl (substr ($class, strlen (nsmod))));
            if (is_readable ($app_file)) {require_once $app_file;}
            return;
        }
    }

    /**
     * Load helper files declared by the application config.
     *
     * Reads helpers from both src/Config (system level) and apps/Config (app level),
     * requiring each file so utility functions are available before any class loads.
     * Missing helpers throw a sentinel error caught by the gateway's blackwall handler.
     *
     * @package   NanoRADF
     * @author    RADLabs Development Team
     * @copyright Radlabs Studio (c) 1986 - 2026
     * @since     0.0.1
     * @return    void
     */
    private static function autoloadHelper (): void
    {
        $helper_key = 'Helper';
        $stems       = [];

        $helper_config = interlace (configf, fl ("{$helper_key}Config"));
        if (is_readable ($helper_config)) {$stems = (array) require $helper_config;}

        foreach ($stems as $stem)
        {
            $file = interlace (helperf, fl ("{$stem}{$helper_key}"));
            if (!is_readable ($file)) {throw new Exception ('helper', 500);}
            require_once $file;
        }

        $stemsapp       = [];

        $helperapp_config = interlace (configfapp, fl ("{$helper_key}Config"));
        if (is_readable ($helperapp_config)) {$stems = (array) require $helperapp_config;}

        foreach ($stemsapp as $stemapp)
        {
            $fileapp = interlace (helperfapp, fl ("{$stemapp}{$helper_key}"));
            if (!is_readable ($fileapp)) {throw new Exception ('helperapp', 500);}
            require_once $fileapp;
        }
    }
}