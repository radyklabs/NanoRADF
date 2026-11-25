<?php declare (strict_types=1);

/**
 * Landing detector — demonstrates a routed SSR page.
 *
 * @since 0.0.1
 */

namespace App\Controllers;

use System\Controllers\PublicController;
use function System\genv;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class WelcomeController extends PublicController
{
    /**
     * `/welcome` — renders the Drip demo view.
     *
     * @since  0.0.1
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function index (): array
    {
        return $this->view ('welcome.index', [
            'title'    => 'Welcome — Drip',
            'heading'  => 'Drip Template Engine',
            'mode'     => (string) genv ('app.mode', 'full'),
            'features' => ['dot-notation names', '@include partials', 'auto-escaping'],
            'loud'     => true,
            'unsafe'   => '<script>alert(\'parsing successful\')</script>',
        ]);
    }
}
