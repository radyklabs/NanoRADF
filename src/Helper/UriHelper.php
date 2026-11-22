<?php declare (strict_types=1);

/**
 * URL helpers — global, loaded from the helper registry.
 *
 * Function-only by contract (see apps/Config/Helper.php).
 *
 * @since 0.0.1
 */

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page could not be found.</p>");
}

/**
 * Absolute application URL for a path.
 *
 * @since  0.0.1
 * @param  string $path Path relative to the app root.
 * @return string Absolute URL.
 */
function baseurl (string $path = '/'): string
{
    return urlify (interlace ((string) genv ('app.url', 'http://localhost'), $path));
}

/**
 * URL for a public asset that the web server serves directly.
 *
 * @since  0.0.1
 * @param  string $path Path relative to `public/`.
 * @return string Public URL.
 */
function publicurl (string $path): string
{
    return urlify (interlace ((string) genv ('app.url', 'http://localhost'), 'public', $path));
}
