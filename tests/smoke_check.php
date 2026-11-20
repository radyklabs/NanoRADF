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

$consts = ['controllersf', 'servicesf', 'viewsf', 'nsctrl', 'nsserv', 'components', 'layouts', 'nsappmod', 'appmodf', 'modulesf'];
foreach ($consts as $c)
{
    $v = defined ($c) ? (string) constant ($c) : 'UNDEF';
    printf ("%s=%s\n", $c, str_replace ("\n", ' ', substr ($v, 0, 100)));
}

// Autoload probe for the current application root (flat apps/ | modular modules/).
// Scaffold classes are 0-byte; resolvability is proven by the mapped file path.
$root_ns = (string) constant ('nsappmod');
$rel     = $root_ns === 'App\Modules' ? 'Welcome/Controllers/WelcomeController.php' : 'Config/Routes.php';
$appmodf = str_replace ('\\', '/', (string) constant ('appmodf'));

$system_file = 'src/Helper/Utilities.php';
$app_file    = $appmodf . '/' . $rel;

printf ("autoload sys=%s app=%s\n",
    is_readable (interlace (basepath, $system_file)) ? 'YES' : 'NO',
    is_readable ($app_file) ? 'YES' : 'NO');
printf ("app_root=%s\n", $appmodf . '/' . $rel);
exit ((is_readable (interlace (basepath, $system_file)) && is_readable ($app_file)) ? 0 : 1);
