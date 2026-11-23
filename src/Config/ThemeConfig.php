<?php declare (strict_types=1);

/**
 * Theme default configuration.
 *
 * - `default`: theme names per surface.
 *   - `public`: public frontend theme (served from `public/themes/<name>`)
 *   - `private`: backend theme (served from `src/Themes/<name>`)
 *
 * @since 0.0.1
 */

return [
    'default' => [
        'public'    => 'default',
        'private'   => 'default',
    ],
];