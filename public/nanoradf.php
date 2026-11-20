<?php declare (strict_types=1);

if (!isset ($application_folder) 
    && !isset ($system_folder))
{
    http_response_code (404);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>404 Not Found</h1><p>The requested page (or file) could not be found.</p>");
}

// Stop editing. Leave code below, it's fragile to the system.
// These messages are visible only if the framework cannot load at all.
$heading = 'Internal System Error';
$message = 'An unexpected error occurred. Please contact the Administrator.';

define ('sep', DIRECTORY_SEPARATOR);
define ('publicpath', realpath (__DIR__).sep);
define ('basepath', dirname (__DIR__).sep);

try
{
    @require call_user_func ('implode', '.', [basepath, 'config']);

    if (in_array (__env ('app.env'), ['local', 'dev']) 
        && is (__env ('app.debug'), true))
    {
        define ('appdebug', true);
        error_reporting (E_ALL);
        @ini_set ('display_errors', 1);
    }

    else
    {
        define ('appdebug', false);
        error_reporting (0);
        @ini_set ('display_errors', 0);
    }

    if (isset ($boot) && class_exists ($boot))
    {
        $boot::initiate ();
    }

    else {throw new Exception ('Boot not starting up');}
}
catch (Throwable $th)
{
    if (defined ('appdebug') && appdebug === true)
    {
        $message = "{$th->getMessage ()} in {$th->getFile()}:{$th->getLine()}";
    }

    error_log ($th->getMessage () . ' in ' . $th->getFile() . ':' . $th->getLine());

    http_response_code (500);
    header ('Content-Type: text/html; charset=utf-8');

    exit ("<h1>{$heading}</h1><p>{$message}</p>");
}
