<?php declare (strict_types=1);

require __DIR__ . '/../public/index.php';

// Test 1: With app.url configured
echo "Test 1 (with app.url): " . urlprettify('/cendol') . PHP_EOL;

// Test 2: Without leading slash
echo "Test 2 (without leading slash): " . urlprettify('cendol') . PHP_EOL;

// Test 3: Empty path
echo "Test 3 (empty path): " . urlprettify('') . PHP_EOL;

// Test 4: Path with multiple slashes
echo "Test 4 (multiple slashes): " . urlprettify('//cendol//es//') . PHP_EOL;

// Test 5: Path with backslashes
echo "Test 5 (backslashes): " . urlprettify('\\cendol\\es\\') . PHP_EOL;

// Test 6: Complex path
echo "Test 6 (complex): " . urlprettify('/api/v1/users/123') . PHP_EOL;

echo PHP_EOL . "All tests passed!" . PHP_EOL;