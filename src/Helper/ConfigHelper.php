<?php declare (strict_types=1);

/**
 * The `gconfig()` function — read a config file and merge its two layers.
 * --
 * Every config name has two homes: the framework's defaults in `src/Config/`
 * and the application's own version in `apps/Config/`. This function reads both
 * and merges them, application values winning key by key, so an app only has to
 * write down what it changes.
 *
 * The name is matched case-insensitively: `gconfig('assets')`,
 * `gconfig('Assets')`, and `gconfig('ASSETS')` all read `AssetsConfig.php`.
 *
 * Helper files hold functions only — no classes, interfaces, traits, or enums.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

use function System\interlace;
use function System\fl;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

/**
 * Read a config file, with the application's values laid over the defaults.
 *
 * @since  0.0.1
 * @param  string $config Config name, case-insensitive (`assets`, `theme`, …).
 * @return array<string, mixed> Merged config; empty array when neither file exists.
 */
function gconfig (string $config): array
{
    $resolve = ucfirst (strtolower ($config));

    $confpath = interlace (configf, fl ("{$resolve}Config"));
    if (is_readable ($confpath)) {$confexists = require $confpath;}

    $confapppath = interlace (configfapp, fl ("{$resolve}Config"));
    if (is_readable ($confapppath)) {$confappexists = require $confapppath;}

    if (isset ($confexists) && isset ($confappexists))
    {
        return array_replace_recursive ($confexists, $confappexists);
    }

    return $confexists ?? $confappexists ?? [];
}