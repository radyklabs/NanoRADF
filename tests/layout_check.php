<?php declare(strict_types=1);

/**
 * Phase 0b layout smoke check (decision D7: test files live in /tests).
 * Run from anywhere: php tests/layout_check.php
 *
 * Backs up .env, toggles app.modules=true / false, boots the real web entry
 * chain (public/index.php) in a separate PHP subprocess per mode, and asserts
 * the structure constants + autoload roots for each layout. Pass = exit 0.
 */

$php     = PHP_BINARY;
$root    = dirname (__DIR__);
$envFile = $root . '/.env';
$probe   = $root . '/tests/bootstrap_probe.php';

$backup = is_file ($envFile) ? file_get_contents ($envFile) : false;

register_shutdown_function (function () use ($envFile, $backup)
{
    if ($backup !== false) { file_put_contents ($envFile, $backup); }
});

$run = function (string $value) use ($envFile, $probe, $php): array
{
    $raw = (string) file_get_contents ($envFile);
    $line = 'app.modules=' . $value;
    $raw = preg_match ('/^app\.modules=.*$/m', $raw)
        ? preg_replace ('/^app\.modules=.*$/m', $line, $raw)
        : rtrim ($raw) . "\n" . $line . "\n";
    file_put_contents ($envFile, $raw);

    $out = shell_exec (sprintf ('%s %s 2>&1', escapeshellarg ($php), escapeshellarg ($probe)));
    parse_str (str_replace ("\n", '&', trim ((string) $out)), $map);
    return array_map (fn ($v) => str_replace ('\\', '/', $v), $map);
};

$pass = true;

// Both roots always active; app.modules toggles ONLY the per-module templates.
foreach (['true', 'false'] as $value)
{
    $got = $run ($value);
    echo "--- app.modules={$value} ---\n";
    foreach ($got as $k => $v) { echo "{$k}={$v}\n"; }

    foreach (['nsapp' => 'App', 'nsmod' => 'Modules', 'app_resolve' => 'YES', 'mod_resolve' => 'YES'] as $k => $w)
    {
        $g = $got[$k] ?? 'MISSING';
        if ($g !== $w) { $pass = false; echo "FAIL {$k}: got '{$g}', want '{$w}'\n"; }
    }

    $want_tpl = $value === 'true' ? 'REGISTERED' : 'IGNORED';
    if (($got['mod_templates'] ?? '') !== $want_tpl)
    {
        $pass = false; echo "FAIL mod_templates: got '{$got['mod_templates']}', want '{$want_tpl}'\n";
    }
}

if ($backup !== false) { file_put_contents ($envFile, $backup); }

echo $pass ? "LAYOUT-CHECK-PASS\n" : "LAYOUT-CHECK-FAIL\n";
exit ($pass ? 0 : 1);
