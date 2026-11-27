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

use function System\interlace;

echo "BOOT-LOADED\n";

$consts = ['controllersf', 'servicesf', 'viewsf', 'nsctrl', 'nsserv', 'components', 'layouts', 'nsapp', 'nsmod', 'appf', 'modulesf'];
foreach ($consts as $c)
{
    $v = defined ($c) ? (string) constant ($c) : 'UNDEF';
    printf ("%s=%s\n", $c, str_replace ("\n", ' ', substr ($v, 0, 100)));
}

// Autoload probe: both roots (D6) — App\… → apps/, Modules\… → modules/.
printf ("autoload sys=%s app=%s mod=%s\n",
    is_readable (interlace (basepath, 'src/Helper/UtilitiesHelper.php')) ? 'YES' : 'NO',
    is_readable (interlace (appf, 'Config/RoutesConfig.php')) ? 'YES' : 'NO',
    is_readable (interlace (modulesf, 'Welcome/Controllers/WelcomeController.php')) ? 'YES' : 'NO');
exit ((is_readable (interlace (basepath, 'src/Helper/UtilitiesHelper.php'))
    && is_readable (interlace (appf, 'Config/RoutesConfig.php'))
    && is_readable (interlace (modulesf, 'Welcome/Controllers/WelcomeController.php'))) ? 0 : 1);