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
    $errorcss = '/error.css';
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    if (realpath (getcwd ().$errorcss)) {echo "<link href=\"{$errorcss}\" rel=\"stylesheet\" />";}
    exit ("<notice><h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p></notice>");
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
