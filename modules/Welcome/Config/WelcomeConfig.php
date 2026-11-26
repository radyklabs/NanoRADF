<?php declare (strict_types=1);

/**
 * Welcome module configuration — mergeable via gconfig().
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require realpath (__DIR__.'/../../').DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

return [
    'name'             => 'Welcome',
    'title'            => 'Welcome Module',
    'description'      => 'Default SSR demo module.',
    'enabled'          => true,
    'view_namespace'   => 'modules/Welcome/Views',
];