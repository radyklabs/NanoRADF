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
use function System\require_file;
use function System\get_required_file;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
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
            require_file (interlace (systemf, fl (substr ($class, strlen (nssys)))));
            return;
        }

        if (str_starts_with ($class, nsapp))
        {
            require_file (interlace (appf, fl (substr ($class, strlen (nsapp)))));
            return;
        }

        if (str_starts_with ($class, nsmod))
        {
            require_file (interlace (modulesf, fl (substr ($class, strlen (nsmod)))));
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
        $stems       = [];
        $stems = (array) get_required_file (interlace (configf, fl ('HelperConfig')), 'helper', []);
        foreach ($stems as $stem)
        {
            require_file (interlace (helperf, fl ("{$stem}Helper")));
        }

        $stemsapp       = [];
        $stemsapp = (array) get_required_file (interlace (configfapp, fl ('HelperConfig')), 'helperapp', []);
        foreach ($stemsapp as $stemapp)
        {
            require_file (interlace (helperfapp, fl ("{$stemapp}Helper")));
        }
    }
}