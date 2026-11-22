<?php declare(strict_types=1);

/**
 * Phase 2 asset smoke check (decision D7: tests live in /tests).
 * Run from anywhere: php tests/asset_check.php
 *
 * Covers enqueue lists, extension appending, masked URL generation, cache
 * buster, masked/public resolution, traversal rejection, and the Drip asset
 * directives. Pass = exit 0.
 */

chdir (dirname (__DIR__).'/public');
require dirname (__DIR__).'/public/index.php';

use System\Kernel\Router;
use System\Services\AssetsService;
use System\Services\Drip;

$pass = 0;
$fail = 0;

function check (string $label, callable $test): void
{
    global $pass, $fail;

    try
    {
        if ($test () === true) {$pass++; echo "  ok   {$label}\n";}
        else {$fail++; echo "  FAIL {$label}\n";}
    }

    catch (\Throwable $e)
    {
        $fail++;
        echo "  FAIL {$label} — ".$e->getMessage ()."\n";
    }
}

echo "Config & enqueue\n";

check ('config exposes default and admin lists', static fn (): bool => array_key_exists ('enqueue_styles', AssetsService::config ()) && array_key_exists ('admin_enqueue_scripts', AssetsService::config ()));
check ('styles resolve to name + base', static fn (): bool => AssetsService::styles ('default') === [['name' => 'style.css', 'base' => 'css']]);
check ('scripts resolve to js extension', static fn (): bool => AssetsService::scripts ('default') === [['name' => 'app.js', 'base' => 'js']]);
check ('runtime enqueue dedupes', function (): bool
{
    AssetsService::enqueueStyle ('css/style');
    $list = AssetsService::styles ('default');

    return count ($list) === 1;
});

echo "\nURL generation\n";

$url = AssetsService::mask ('default', ['name' => 'style.css', 'base' => 'css']);
check ('mask rewrites theme css to the slug', static fn (): bool => str_starts_with ($url, '/Files/c/style.css?') && str_contains ($url, 'v='));
$jsUrl = AssetsService::mask ('default', ['name' => 'app.js', 'base' => 'js']);
check ('mask rewrites theme js to the slug', static fn (): bool => str_starts_with ($jsUrl, '/Files/j/app.js?'));
check ('renderStyles emits one link', static fn (): bool => substr_count (AssetsService::renderStyles ('default'), '<link') === 1);
check ('renderScripts emits one deferred script', static fn (): bool => substr_count (AssetsService::renderScripts ('default'), '<script') === 1);

echo "\nResolution\n";

check ('masked css resolves to a file', static fn (): bool => AssetsService::resolve ('Files/c/style.css')['mime'] === 'text/css; charset=utf-8');
check ('public theme css resolves', static fn (): bool => AssetsService::resolve ('themes/default/css/error.css')['mime'] === 'text/css; charset=utf-8');
check ('masked js resolves', static fn (): bool => (int) AssetsService::resolve ('Files/j/app.js') > 0);
check ('unknown mask returns null', static fn (): bool => AssetsService::resolve ('Files/z/style.css') === null);
check ('traversal is rejected', static fn (): bool => AssetsService::resolve ('Files/c/../../../nanoradf') === null);
check ('missing file returns null', static fn (): bool => AssetsService::resolve ('Files/c/nope.css') === null);

echo "\nRoutes\n";

// `match()` would run the controller (it streams and exits), so assert the
// compiled table instead.
$targets = [];
$r       = new ReflectionClass (Router::class);
$table   = $r->getMethod ('table');
$table->setAccessible (true);
foreach ($table->invoke (null) as $verb => $routes)
{
    foreach ($routes as $route) {$targets[] = $route[1];}
}

check ('masked asset route is registered', static fn (): bool => in_array ('App\Controllers\AssetsController::serve', $targets, true));
check ('two asset routes are registered', static fn (): bool => count (array_keys ($targets, 'App\Controllers\AssetsController::serve', true)) === 2);
echo "\nDrip directives\n";

$dir = basepath.'storage'.sep.'cache'.sep.'views';
@mkdir ($dir, 0775, true);
$probe = $dir.'assets.html';
file_put_contents ($probe, "@enqueueStyles\n@enqueueScripts\n@style\n.x{color:red}\n@endstyle\n@script\nconsole.log(1);\n@endscript");

$html = Drip::renderFile ($probe, []);
check ('@enqueue* directives emit asset tags', static fn (): bool => str_contains ($html, '<link') && str_contains ($html, '<script'));
check ('@style emits a style block', static fn (): bool => str_contains ($html, '<style>') && str_contains ($html, '.x{color:red}'));
check ('@script emits a script block', static fn (): bool => str_contains ($html, '<script>') && str_contains ($html, 'console.log(1);'));

@unlink ($probe);
@unlink (Drip::class === null ? '' : '');

echo "\n".($fail === 0 ? "PASS" : "FAIL")." — {$pass} passed, {$fail} failed\n";
exit ($fail === 0 ? 0 : 1);
