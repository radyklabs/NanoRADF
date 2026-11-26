<?php declare (strict_types=1);

/**
 * The gconfig() function
 */
use function System\interlace;
use function System\fl;
use function System\genv;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

function gconfig (string $config): array
{
    $resolve = ucfirst (strtolower ($config));
    $confpath = interlace (configf, fl ("{$resolve}Config"));
    $merged = is_readable ($confpath) ? require $confpath : [];
    $confapppath = interlace (configfapp, fl ("{$resolve}Config"));
    if (is_readable ($confapppath))
    {
        $merged = array_replace_recursive ($merged, require $confapppath);
    }
    if (genv ('app.modules') === true && defined ('modulesf') && is_dir (modulesf))
    {
        $moduleDirs = glob (interlace (modulesf, '*'), GLOB_ONLYDIR);
        foreach ($moduleDirs as $moduleDir)
        {
            $moduleConfig = interlace ($moduleDir, 'Config', fl ("{$resolve}Config"));
            if (is_readable ($moduleConfig))
            {
                $merged = array_replace_recursive ($merged, require $moduleConfig);
            }
        }
    }
    return $merged;
}