<?php declare (strict_types=1);

/**
 * Theme override configuration.
 *
 * - `default`: theme names per surface.
 *   - `public`: public frontend theme (served from `public/themes/<name>`)
 *   - `private`: backend theme (served from `apps/Themes/<name>`)
 *
 * @since 0.0.1
 */

return [
    'default' => [
        'private'   => 'admin',
    ],
];