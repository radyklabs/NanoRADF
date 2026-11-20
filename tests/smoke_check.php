<?php declare(strict_types=1);

/**
 * Bootstrap smoke check (decision D7: test files live in /tests).
 * Run from anywhere: php tests/smoke_check.php
 *
 * Pass = exit 0. Exercises the real web entry chain
 * (public/index.php) and both PSR-4 autoload roots.
 */

// index.php resolves nanoradf.php relative to CWD — run the check as if
// the docroot request happened.
chdir (__DIR__ . '/../public');

require __DIR__ . '/../public/index.php';

echo "BOOT-LOADED\n";

$consts = ['controllersf', 'servicesf', 'viewsf', 'nsctrl', 'nsserv', 'components', 'layouts', 'modulesfapp', 'nsappmod'];
foreach ($consts as $c)
{
    $v = defined ($c) ? (string) constant ($c) : 'UNDEF';
    printf ("%s=%s\n", $c, str_replace ("\n", ' ', substr ($v, 0, 100)));
}

$a = class_exists ('System\Helper\Utilities');
$b = class_exists ('App\Modules\Welcome\Controllers\WelcomeController');

$inc = array_map (fn ($f) => str_replace ('\\', '/', $f), get_included_files ());
$sys = false;
$mod = false;
foreach ($inc as $f)
{
    if (str_ends_with ($f, 'src/Helper/Utilities.php')) {$sys = true;}
    if (str_ends_with ($f, 'apps/Modules/Welcome/Controllers/WelcomeController.php')) {$mod = true;}
}

printf ("autoload sys=%s mod=%s (class_exists=%s/%s)\n", $sys ? 'YES' : 'NO', $mod ? 'YES' : 'NO', $a ? 'yes' : 'no', $b ? 'yes' : 'no');
exit (($sys && $mod) ? 0 : 1);
