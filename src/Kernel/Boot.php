<?php declare (strict_types=1);

namespace System\Kernel;

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p>");
}

// use Exception;

final class Boot
{
    public static function initiate ()
    {
        static $registered = false;
        if ($registered) {return;}
        $registered = true;

        spl_autoload_register ([self::class, 'autoloader']);
    }

    /**
     * PSR-4 autoloader registration.
     *
     *   System\X\Y            → <root>/src/X/Y.php
     *   App\X\Y (flat)        → <root>/apps/X/Y.php
     *   App\Modules\Name\Y    → <root>/apps/Modules/Name/Y.php
     *
     * Prefixes come from the global namespace constants defined while
     * registering the structure map: `nssys` (e.g. "System") and
     * `nsappmod` (e.g. "App\Modules" modular, "App" flat — decision D6).
     * The path root is `appmodf` (<root>/apps/Modules or <root>/apps),
     * so both layouts resolve from the same branch.
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
            $app_file = interlace (appmodf, substr ($class, strlen (nsappmod)) . '.php');
            if (is_readable ($app_file))
            {
                require_once $app_file;
            }
        }
    }
}