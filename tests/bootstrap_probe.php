<?php declare(strict_types=1);

/**
 * Bootstrap probe — prints the resolved structure constants and autoload
 * results for the current .env. Consumed by tests/layout_check.php.
 *
 * Two roots are always active (D6): App\… → apps/, Modules\… → modules/.
 * app.modules registers the per-module folder/namespace templates.
 */

chdir (dirname (__DIR__) . '/public');
require dirname (__DIR__) . '/public/index.php';

use function System\genv;

$const = fn (string $c): string => defined ($c) ? (string) constant ($c) : 'UNDEF';

printf ("mode=%s\n", var_export (genv ('app.modules'), true));
printf ("nsapp=%s\n", $const ('nsapp'));
printf ("nsmod=%s\n", $const ('nsmod'));
printf ("appf=%s\n", str_replace ('\\', '/', $const ('appf')));
printf ("modulesf=%s\n", str_replace ('\\', '/', $const ('modulesf')));
printf ("controllersfmod=%s\n", $const ('controllersfmod'));

// Both roots must resolve, regardless of app.modules (D6 two-root model).
printf ("app_resolve=%s\n", is_file (appf . '/Config/RoutesConfig.php') ? 'YES' : 'NO');
printf ("mod_resolve=%s\n", is_file (modulesf . '/Welcome/Controllers/WelcomeController.php') ? 'YES' : 'NO');
printf ("mod_templates=%s\n", defined ('controllersfmod') ? 'REGISTERED' : 'IGNORED');