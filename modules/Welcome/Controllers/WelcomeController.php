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

use System\Controllers\PublicController;
use Modules\Welcome\Services\WelcomeService;

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
}