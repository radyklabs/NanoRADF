<?php declare (strict_types=1);

namespace System\Kernel;

use Exception;

final class Boot
{
    public static function initiate ()
    {
        static $registered = false;
        if ($registered) {return;}

        if (!defined ('sep'))
        {
            define ('basepath', dirname (__DIR__, 2));
            define ('publicpath', basepath."/public");
            define ('sep', DIRECTORY_SEPARATOR);
        }

        // if (self::$basepath === '') {self::$basepath = basepath;}
        // if (self::$publicpath === '') {self::$publicpath = publicpath;}

        $supports_file = interlace (basepath, '.supports');
        if (!is_readable ($supports_file))
        {
            throw new Exception ('No supports');
        }

        require_once $supports_file;

        $structures_file = interlace (basepath, '.structures');
        if (!is_readable ($structures_file))
        {
            throw new Exception ('No structures');
        }

        require_once $structures_file;

        // Directories are declared by the web entry point. For CLI
        // consumers (bin/face, tests) that never include public/index.php
        // the system directory is resolved from this file's own location.
        // $map = [];

        // $index_file = interlace (publicpath, 'index.php');
        // if (!is_readable ($index_file)) {die('No index');}

        // foreach (read_directories ($index_file) as $type => $dir)
        // {
        //     if (!defined ($type))
        //     {
        //         define ($type, interlace (basepath, $dir));
        //     }
        // }
        // $map += ['system' => 'system', 'modules' => 'modules', 'views' => 'views'];

        // foreach ($map as $type => $dir) {
        //     if (!defined($type)) {
        //         define($type, interlace(basepath, $dir));
        //     }
        // }


        $registered = true;

        spl_autoload_register ([self::class, 'autoloader']);
    }

    /**
     * PSR-4 autoloader registration.
     *
     *   System\X\Y            → <root>/src/X/Y.php
     *   App\Modules\Name\X    → <root>/apps/Modules/Name/X.php
     *
     * Prefixes come from the global namespace constants defined while
     * registering the structure map: `nssys` (e.g. "System") and
     * `nsappmod` (e.g. "App\Modules").
     */
    public static function autoloader (string $class): void
    {
        $class = ltrim ($class, '\\');
        // $nssys = defined ('nssys') ? (string) constant ('nssys') : '';
        // $nsmod = defined ('nsappmod') ? (string) constant ('nsappmod') : '';

        if (str_starts_with ($class, nssys))
        {
            $system_file = interlace (systemf, substr ($class, strlen (nssys)) . '.php');
            if (is_readable ($system_file))
            {
                require_once $system_file;
            }

            return;
        }

        if (str_starts_with ($class, nsappmod))
        {
            $module_file = interlace (modulesfapp, substr ($class, strlen (nsappmod)) . '.php');
            if (is_readable ($module_file))
            {
                require_once $module_file;
            }

            return;
        }
    }
}