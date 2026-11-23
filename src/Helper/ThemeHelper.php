<?php declare (strict_types=1);

/**
 * Theme helpers — global, loaded from the helper registry.
 *
 * Function-only by contract (see apps/Config/HelperConfig.php).
 *
 * @since 0.0.1
 */

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page could not be found.</p>");
}