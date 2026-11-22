<?php declare (strict_types=1);

/**
 * Helper registry — file stems loaded by `.starter` before the kernel.
 *
 * Each stem maps to `src/Helper/<stem>Helper.php`, a file that declares
 * global functions only (no classes, interfaces, traits, or enums).
 *
 * @since 0.0.1
 */

return [
    'uri',
    'config',
    'utilities',
];
