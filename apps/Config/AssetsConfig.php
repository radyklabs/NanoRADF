<?php declare (strict_types=1);

/**
 * Which styles and scripts the application loads, and where they come from.
 * --
 * This is the application's own say over its assets. The framework ships a
 * base set in `src/Config/AssetsConfig.php`; whatever is written here is laid
 * on top of it, so an entry listed here replaces the base entry of the same
 * name instead of being appended to it. Leave a key out to keep the base
 * behaviour.
 *
 * - `enqueue_*` / `bone_enqueue_*`: the paths queued for the public frontend
 *   (face) and the backend (bone). Values are full masked paths with extension.
 * - `register_styles` / `register_scripts`: additional entries layered over the
 *   framework defaults, overriding entries with the same `id` when provided.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

return [
    // Face (public frontend) asset queues
    'enqueue_styles'  => ['style'],
    'enqueue_scripts' => [],

    // Bone (backend) asset queues
    'bone_enqueue_styles'  => [],
    'bone_enqueue_scripts' => [],

    // Assets mask URL patterns.
    'mask_urls' => [],
];
