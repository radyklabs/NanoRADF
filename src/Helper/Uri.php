<?php declare (strict_types=1);

/**
 * URL helper library
 *
 * Need refrase
 *
 * @since 0.0.1
 */

if (!defined ('basepath'))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p>");
}

function baseurl (string $path = '/'): string
{
    return urlify (interlace(genv('app.url'), $path));
}