<?php declare (strict_types=1);

/**
 * Welcome data access — read-only against headless API (Phase 4).
 * v1: placeholder returning static data for SSR rendering.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace Modules\Welcome\Model;

use Throwable;

if (!defined ('basepath'))
{
    try {exit (@require realpath (__DIR__.'/../../').DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class WelcomeModel
{
    /**
     * Get static welcome data for v1 (no API yet).
     *
     * @return array{id:int, title:string, features:list<string>}
     */
    public function getWelcomeData (): array
    {
        return [
            'id'       => 1,
            'title'    => 'Welcome — Drip Template Engine',
            'features' => [
                'dot-notation view names',
                '@include partials',
                'auto-escaping {{ }}',
                'raw output {{! !}}',
            ],
        ];
    }
}