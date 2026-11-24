<?php declare (strict_types=1);

/**
 * Web entry point — folder configuration and gateway hand-off.
 * --
 * Defines the application roots (apps, src, modules), then delegates
 * bootstrapping to the `nanoradf` gateway. This file stays minimal:
 * no business logic, only paths.
 *
 * @since 0.0.1
 */

/**
 * Application MVC root folder name.
 * --
 * Point this at a different folder name or location to relocate
 * the application layer.
 */
$application_folder = 'apps';

/**
 * Framework core folder name.
 * --
 * Holds the system brain — treat every change here as fragile.
 */
$system_folder = 'src';

/**
 * Optional HMVC root folder name.
 * --
 * Used only when `app.modules` is true in the environment config.
 */
$modules_folder = 'modules';

/** 
 * End of configuration. 
 * --
 * Open `.env` file to see the others configuration settings
*/

require realpath ( dirname (__DIR__).DIRECTORY_SEPARATOR.'nanoradf');

// Says who only silence is golden?!
