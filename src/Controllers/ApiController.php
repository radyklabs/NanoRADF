<?php declare (strict_types=1);

namespace System\Controllers;

use System\Kernel\Controller as BaseController;

/**
 * API surface — headless JSON endpoints. `respond()` always emits a JSON
 * envelope; `authorize()` stays open until Phase 5 wires token auth.
 *
 * @since 0.0.1
 */
class ApiController extends BaseController
{
    protected string $surface = 'api';

    /**
     * Convenience JSON response: `{ok, data}` or `{ok:false, error}`.
     *
     * @since  0.0.1
     * @param  mixed $data   Payload for `data`, or an error string when `$ok` is false.
     * @param  int   $status HTTP status.
     * @param  bool  $ok     false sends `{error}` instead of `{data}`.
     * @return array{status:int, body:string}
     */
        protected function json (mixed $data = null, int $status = 200, bool $ok = true): array
    {
        $envelope = $ok ? ['ok' => true, 'data' => $data] : ['ok' => false, 'error' => is_string ($data) ? $data : 'Request failed'];

        return $this->respond (null, $status, ['json' => $envelope]);
    }
}
