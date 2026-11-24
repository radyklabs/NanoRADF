<?php declare(strict_types=1);

/**
 * Phase 2 asset smoke check (decision D7: tests live in /tests).
 * Run from anywhere: php tests/assets_check.php
 *
 * Covers the mask-pattern engine, active themes, enqueue lists, render helpers,
 * serving, traversal rejection, and the Drip asset directives.
 * Pass = exit 0.
 */

chdir (dirname (__DIR__).'/public');
require dirname (__DIR__).'/public/index.php';

use System\Kernel\Router;
use System\Services\AssetsService;
use System\Services\DripService;
use function System\basepath;

// Global wrappers for System namespace functions (helpers are in global namespace after bootstrap)
if (!function_exists('urlify')) { function urlify(string $path): string { return \System\urlify($path); } }
if (!function_exists('e')) { function e(mixed $value): string { return \System\e($value); } }
if (!function_exists('interlace')) { function interlace(...$segments) { return \System\interlace(...$segments); } }
if (!function_exists('genv')) { function genv(string $key, mixed $default = false): mixed { return \System\genv($key, $default); } }

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

check ('config exposes every enqueue list', static fn (): bool => array_key_exists ('enqueue_styles', AssetsService::config ()) && array_key_exists ('bone_enqueue_scripts', AssetsService::config ()));
check ('face styles queue style.css and System.css', static fn (): bool => AssetsService::styles ('face') === ['css/style.css', 'system/css/System.css']);
check ('face scripts queue app.js', static fn (): bool => AssetsService::scripts ('face') === ['js/app.js']);
check ('bone styles queue style.css', static fn (): bool => AssetsService::styles ('bone') === ['css/style.css']);
check ('runtime enqueue is deduped', function (): bool
{
    AssetsService::enqueueStyle ('css/style');
    return AssetsService::styles ('face') === ['css/style.css', 'system/css/System.css'];
});

echo "\nActive themes\n";

check ('default face theme is "default"', static fn (): bool => AssetsService::theme ('face') === 'default');
check ('default bone theme is "admin"', static fn (): bool => AssetsService::theme ('bone') === 'admin');

echo "\nRendering\n";

check ('face styles link the masked css and tokens', static fn (): bool => substr_count (AssetsService::renderFaceStyles (), '<link') === 2
    && str_contains (AssetsService::renderFaceStyles (), '/css/style.css')
    && str_contains (AssetsService::renderFaceStyles (), '/system/css/System.css'));
check ('face scripts defer-load the masked js', static fn (): bool => str_contains (AssetsService::renderFaceScripts (), '<script src="/js/app.js?v=') && str_contains (AssetsService::renderFaceScripts (), 'id="app"'));
check ('bone styles link the bone css', static fn (): bool => str_contains (AssetsService::renderBoneStyles (), '/assets/css/style.css'));
check ('bone scripts link the bone js', static fn (): bool => str_contains (AssetsService::renderBoneScripts (), '/assets/js/app.js'));

echo "\nResolution\n";

check ('masked face css resolves', static fn (): bool => AssetsService::resolve ('/css/style.css')['mime'] === 'text/css; charset=utf-8');
check ('masked face js resolves', static fn (): bool => AssetsService::resolve ('/js/app.js')['mime'] === 'text/javascript; charset=utf-8');
check ('masked tokens resolve', static fn (): bool => AssetsService::resolve ('/system/css/System.css')['mime'] === 'text/css; charset=utf-8');
check ('masked bone css resolves', static fn (): bool => AssetsService::resolve ('/assets/css/style.css')['mime'] === 'text/css; charset=utf-8');
check ('unknown pattern returns null', static fn (): bool => AssetsService::resolve ('/nope/style.css') === null);
check ('traversal is rejected', static fn (): bool => AssetsService::resolve ('/css/../../../nanoradf') === null);
check ('missing file returns null', static fn (): bool => AssetsService::resolve ('/css/nope.css') === null);

echo "\nRoutes\n";

$targets = [];
$r       = new ReflectionClass (Router::class);
$table   = $r->getMethod ('table');
$table->setAccessible (true);
foreach ($table->invoke (null) as $verb => $routes)
{
    foreach ($routes as $route) {$targets[] = $route[1];}
}

check ('no masked-asset route is registered (dispatcher intercepts)', static fn (): bool => count (array_keys ($targets, 'App\Kernel\AssetsController::serve', true)) === 0);

echo "\nDrip directives\n";

$dir = basepath.'storage'.sep.'cache'.sep.'views';
@mkdir ($dir, 0775, true);
$probe = $dir.'assets.html';
file_put_contents ($probe, "@enqueueStyles\n@enqueueScripts\n@style\n.x{color:red}\n@endstyle\n@script\nconsole.log(1);\n@endscript");

$html = DripService::renderFile ($probe, []);
check ('@enqueue* directives emit asset tags', static fn (): bool => str_contains ($html, '<link') && str_contains ($html, '<script'));
check ('@style emits a style block', static fn (): bool => str_contains ($html, '<style>') && str_contains ($html, '.x{color:red}'));
check ('@script emits a script block', static fn (): bool => str_contains ($html, '<script>') && str_contains ($html, 'console.log(1);'));

@unlink ($probe);

echo "\nHelpers\n";

check ('baseurl() is registered', static fn (): bool => function_exists ('baseurl') && str_contains (baseurl ('/x'), '/x'));
check ('e() escapes', static fn (): bool => e ('<b>') === '<b>');

echo "\n".($fail === 0 ? "PASS" : "FAIL")." — {$pass} passed, {$fail} failed\n";
exit ($fail === 0 ? 0 : 1);