<?php declare (strict_types=1);

/**
 * Welcome service — business logic orchestrator for the Welcome module.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace Modules\Welcome\Services;

use Throwable;
use function System\genv;
use Modules\Welcome\Model\WelcomeModel;

if (!defined ('basepath'))
{
    try {exit (@require realpath (__DIR__.'/../../').DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class WelcomeService
{
    private WelcomeModel $model;

    public function __construct ()
    {
        $this->model = new WelcomeModel();
    }

    /**
     * Prepare data array for the welcome view.
     *
     * @return array<string, mixed>
     */
    public function prepareData (): array
    {
        $data = $this->model->getWelcomeData();
        $data['mode'] = genv('app.mode', 'full');
        $data['loud'] = true;
        $data['unsafe'] = '<script>alert(\'parsing successful\')</script>';
        return $data;
    }
}