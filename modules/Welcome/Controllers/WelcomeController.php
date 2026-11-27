<?php declare (strict_types=1);

/**
 * Welcome controller — handles /welcome route via the module.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace Modules\Welcome\Controllers;

use Throwable;
use System\Controllers\PublicController;
use Modules\Welcome\Services\WelcomeService;
use function gconfig;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename (__FILE__); exit("404 Forbidden {$file}");}
}

final class WelcomeController extends PublicController
{
    /**
     * `/welcome` — renders the Drip demo view.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function index (): array
    {
        $service = new WelcomeService();
        $data = $service->prepareData();

        return $this->view('welcome.index', $data, null, 'Welcome');
    }

    /**
     * `/welcome/about` — shows module information page.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function about (): array
    {
        $config = gconfig('welcome', []);

        return $this->view('welcome.about', [
            'title'       => 'About — Welcome Module',
            'module_name' => $config['name'] ?? 'Welcome',
            'description' => $config['description'] ?? '',
            'version'     => '0.0.1',
        ], null, 'Welcome');
    }

    /**
     * `/welcome/features` — lists available features from config.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function features (): array
    {
        $config = gconfig('welcome', []);
        $features = $config['features'] ?? [];

        return $this->view('welcome.features', [
            'title'    => 'Features — Welcome Module',
            'features' => $features,
        ], null, 'Welcome');
    }
}