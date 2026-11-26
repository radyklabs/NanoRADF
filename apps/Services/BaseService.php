<?php declare (strict_types=1);

/**
 * Application-wide base service — common business logic patterns.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace App\Services;

use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 2).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

abstract class BaseService
{
    /**
     * Get a config value with dot-notation path (e.g., 'theme.default.public').
     *
     * @param string $path Dot-notation config path
     * @param mixed  $default Default value if not found
     * @return mixed
     */
    protected function config (string $path, mixed $default = null): mixed
    {
        $config = \System\gconfig('');
        $keys = explode('.', $path);

        foreach ($keys as $key)
        {
            if (!is_array($config) || !array_key_exists($key, $config))
            {
                return $default;
            }
            $config = $config[$key];
        }

        return $config;
    }
}