<?php declare (strict_types=1);

/**
 * Web entry point — folder configuration and gateway hand-off.
 *
 * Sets the three source-root names and the debug master switch,
 * then requires the `public/nanoradf` gateway. No other logic here.
 *
 * @since 0.0.1
 */

/**
 * Application MVC root folder name.
 *
 * Point this at a different folder name or location to relocate
 * the application layer.
 */
$application_folder = 'apps';

/**
 * Framework core folder name.
 *
 * Holds the system brain — treat every change here as fragile.
 */
$system_folder = 'src';

/**
 * Optional HMVC root folder name.
 *
 * Used only when `app.modules` is true in the environment config.
 */
$modules_folder = 'modules';

/**
 * Debug master switch.
 *
 * True shows file:line detail in bootstrap error output during
 * development; set false for production.
 *
 * @link https://nanoradf.local/support/article/debugging-in-actions/
 */
$debug = true;

/* End of configuration. */

require realpath ('nanoradf');

// Says who only silence is golden?!
