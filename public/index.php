<?php declare (strict_types=1);

/**
 * Web entry point — folder configuration and gateway hand-off.
 *
 * Sets the three source-root names and the debug master switch,
 * then requires the `public/nanoradf` gateway. No other logic here.
 *
 * @since 1.0.0
 */

$application_folder = 'apps';   // main MVC root
$system_folder = 'src';         // framework core — do not change
$modules_folder = 'modules';    // optional HMVC root

// Debug master switch: file:line detail in bootstrap error output.
// True for local/dev, false for production.
$debug = true;

require realpath ('nanoradf');

// Says who only silence is golden
