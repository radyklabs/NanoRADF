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
$expect = [
    'true'  => ['nsappmod' => 'App\Modules', 'appmodf' => 'modules', 'modulesf' => 'SET', 'resolve' => 'YES', 'path_suffix' => 'modules/Welcome/Controllers/WelcomeController.php'],
    'false' => ['nsappmod' => 'App', 'appmodf' => 'apps', 'modulesf' => 'SET', 'resolve' => 'YES', 'path_suffix' => 'apps/Config/Routes.php'],
];

foreach ($expect as $value => $want)
{
    $got = $run ($value);
    echo "--- app.modules={$value} ---\n";
    foreach ($got as $k => $v) { echo "{$k}={$v}\n"; }

    foreach (['nsappmod', 'modulesf', 'resolve'] as $k)
    {
        $g = $got[$k] ?? 'MISSING';
        $w = $want[$k];
        if ($w === 'SET') { $ok = ($g !== 'UNDEF' && $g !== 'MISSING' && $g !== ''); }
        else { $ok = ($g === str_replace ('\\', '/', $w)); }
        if (!$ok) { $pass = false; echo "FAIL {$k}: got '{$g}', want '{$w}'\n"; }
    }

    $appmodf = $got['appmodf'] ?? 'MISSING';
    if (!str_ends_with ($appmodf, '/app/' . $want['appmodf']))
    {
        $pass = false; echo "FAIL appmodf: got '{$appmodf}', want suffix 'app/{$want['appmodf']}'\n";
    }

    $path = $got['path'] ?? 'MISSING';
    if (!str_ends_with ($path, $want['path_suffix']))
    {
        $pass = false; echo "FAIL path: got '{$path}', want suffix '{$want['path_suffix']}'\n";
    }
}

if ($backup !== false) { file_put_contents ($envFile, $backup); }

echo $pass ? "LAYOUT-CHECK-PASS\n" : "LAYOUT-CHECK-FAIL\n";
exit ($pass ? 0 : 1);
