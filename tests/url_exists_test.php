<?php declare (strict_types=1);

require __DIR__ . '/../public/index.php';

use function System\url_exists;
use function System\urlprettify;
use function System\interlace;

echo "Test url_exists function:" . PHP_EOL;

// Test 1: Local server (if running)
$localRoot = url_exists('http://nanoradfw.local/', 3);
echo "  Local root exists: " . ($localRoot ? 'YES' : 'NO') . PHP_EOL;

// Test 2: Local 404 (errors.css - file sengaja disalahkan)
$local404 = url_exists('http://nanoradfw.local/errors.css', 3);
echo "  Local errors.css exists: " . ($local404 ? 'YES' : 'NO') . PHP_EOL;

// Test 3: Wrong domain (should timeout fast)
$wrong = url_exists('http://nonexistent.invalid.test/', 1);
echo "  Wrong domain exists: " . ($wrong ? 'YES' : 'NO') . PHP_EOL;

// Test 4: urlprettify + url_exists combo (local - may fail if server not running)
$url = urlprettify('errors.css');
echo "  urlprettify result: $url" . PHP_EOL;
$exists_combo = url_exists($url, 3);
echo "  Combo check exists: " . ($exists_combo ? 'YES' : 'NO') . PHP_EOL;

// Test 5: Local filesystem check (recommended for local assets)
$localFile = interlace(publicpath, 'errors.css');
$localExists = is_file($localFile) && is_readable($localFile);
echo "  Local errors.css (filesystem): " . ($localExists ? 'YES' : 'NO') . PHP_EOL;

$localFile2 = interlace(publicpath, 'themes', 'default', 'css', 'style.css');
$localExists2 = is_file($localFile2) && is_readable($localFile2);
echo "  Local style.css (filesystem): " . ($localExists2 ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL . "Test completed!" . PHP_EOL;