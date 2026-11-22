<?php declare (strict_types=1);

namespace System\Controllers;

use System\Kernel\Controller as BaseController;

/**
 * Private surface — authenticated HTML pages. `authorize()` stays open until
 * Phase 5 wires session/token auth; the router calls it before every action.
 *
 * @since 0.0.1
 */
class PrivateController extends BaseController
{
    protected string $surface = 'private';

    protected string $theme = 'default';
}
