<?php declare(strict_types=1);

/**
 * Bootstrap probe — prints the resolved structure constants and autoload
 * results for the current .env. Consumed by tests/layout_check.php.
 */

chdir (dirname (__DIR__) . '/public');
require dirname (__DIR__) . '/public/index.php';

$const = fn (string $c): string => defined ($c) ? (string) constant ($c) : 'UNDEF';

printf ("mode=%s\n", var_export (__env ('app.modules'), true));
printf ("nsappmod=%s\n", str_replace ('\\', '/', $const ('nsappmod')));
printf ("appmodf=%s\n", str_replace ('\\', '/', $const ('appmodf')));
printf ("modulesfapp=%s\n", str_replace ('\\', '/', $const ('modulesfapp')));
printf ("controllersfapp=%s\n", str_replace ('\\', '/', $const ('controllersfapp')));

// Autoload probe: the application root must resolve this mode's class file.
// (Scaffold files are 0-byte; resolution is proven by the mapped path existing.)
$root_ns = (string) constant ('nsappmod');
$root_fp = str_replace ('\\', '/', (string) constant ('appmodf'));
$rel     = $root_ns === 'App\Modules' ? 'Welcome/Controllers/WelcomeController.php' : 'Config/Routes.php';
$expect  = rtrim ($root_fp, '/') . '/' . $rel;

printf ("path=%s\n", $expect);
printf ("resolve=%s\n", is_file ($expect) ? 'YES' : 'NO');
