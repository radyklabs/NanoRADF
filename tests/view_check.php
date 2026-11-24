<?php declare(strict_types=1);

/**
 * Phase 2 view smoke check (decision D7: tests live in /tests).
 * Run from anywhere: php tests/view_check.php
 *
 * Covers name resolution, compile output, escaping, the double-escape guard,
 * layout inheritance, includes, @class, and the not-found message. Pass = exit 0.
 */

chdir (dirname (__DIR__).'/public');
require dirname (__DIR__).'/public/index.php';

use System\Services\DripService;
use System\Services\ViewsService;
use function System\interlace;

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

function throws (callable $fn, string $needle): bool
{
    try {$fn ();}
    catch (\Throwable $e) {return str_contains ($e->getMessage (), $needle);}

    return false;
}

echo "View resolution\n";

check ('plain file resolves under apps/Views', static fn (): bool => ViewsService::path ('welcome.meta') === interlace (appf, 'Views', 'welcome', 'meta').'.html');
check ('extension is rejected', static fn (): bool => throws (static fn () => ViewsService::path ('welcome.index.html'), 'without extension'));
check ('missing view names both causes', static fn (): bool => throws (static fn () => ViewsService::path ('nope.nope'), 'Pastikan file ada'));

echo "\nRendering\n";

$html = ViewsService::render ('welcome.index', [
    'title'    => 'T',
    'heading'  => 'H',
    'mode'     => 'full',
    'features' => ['a'],
    'loud'     => true,
    'unsafe'   => '<b>x</b>',
]);

check ('layout wraps the view', static fn (): bool => str_contains ($html, '<!doctype html>'));
check ('escaped echo is escaped', static fn (): bool => str_contains ($html, '<b>x</b>'));
check ('raw echo stays raw', static fn (): bool => str_contains ($html, '<p>Raw works: <b>x</b></p>'));
check ('@include inherits caller data', static fn (): bool => str_contains ($html, 'Mode: full'));
check ('@foreach repeats', static fn (): bool => substr_count ($html, '<li>') === 1);
check ('@class builds the attribute', static fn (): bool => str_contains ($html, 'class="note note--loud"'));

echo "\nLayout argument\n";

$bare = ViewsService::render ('welcome.meta', ['label' => 'L', 'mode' => 'M']);
check ('no layout renders the fragment only', static fn (): bool => !str_contains ($bare, '<!doctype html>'));

$override = ViewsService::render ('welcome.index', ['title' => 'T', 'heading' => 'H', 'mode' => 'm', 'features' => [], 'loud' => false, 'unsafe' => 'x'], 'layouts.main');
check ('layout argument is accepted', static fn (): bool => str_contains ($override, '<!doctype html>'));

echo "\nCompile guards\n";

$guard = interlace (basepath, 'storage', 'cache', 'views', 'guard.html');
@mkdir (dirname ($guard), 0775, true);
file_put_contents ($guard, '{{ htmlspecialchars($x) }}');

check ('escape call inside {{ }} throws', static fn (): bool => throws (
    static fn () => DripService::renderFile ($guard),
    'already escapes'
));

@unlink ($guard);

check ('comment-wrapped directive compiles', static fn (): bool => str_contains (
    ViewsService::render ('welcome.index', ['title' => 'T', 'heading' => 'H', 'mode' => 'm', 'features' => [], 'loud' => false, 'unsafe' => 'x']),
    '<title>T</title>'
));

echo "\nCache\n";

$cacheDir = basepath.'storage'.sep.'cache'.sep.'views';
check ('compiled cache is written', static fn (): bool => is_dir ($cacheDir) && count (glob ($cacheDir.sep.'*.php')) > 0);

echo "\n".($fail === 0 ? "PASS" : "FAIL")." — {$pass} passed, {$fail} failed\n";
exit ($fail === 0 ? 0 : 1);