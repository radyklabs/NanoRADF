<?php declare (strict_types=1);

namespace App\Controllers;

use System\Controllers\PublicController;

/**
 * Landing detector — demonstrates a routed SSR page.
 *
 * @since 0.0.1
 */
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
            'unsafe'   => '<script>alert(1)</script>',
        ]);
    }
}
