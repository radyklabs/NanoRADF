<?php declare (strict_types=1);

// use Nanoradf\Src\Kernel\Request;
// use Throwable;

// Modules folder name
// $application_folder = 'apps';

// System folder name, don't change except you know what're you doing
// $system_folder = 'src';

// Stop editing. Leave code below, it's fragile to the system.
// These messages are visible only if the framework cannot load at all.
$heading = 'Internal System Error';
$message = 'An unexpected error occurred. Please contact the Administrator.';

// Confirm that the three built-in functions we depend on are available.
// if (!is_callable ('call_user_func', false, $call)
//     || !is_callable ('define', false, $lock_as)
//     || !is_callable ('defined', false, $is_locked_as))
// {
//     die ("<h1>{$heading}</h1><p>{$message}</p>");
// }

// `basepath` stays /public (mirrors BoneRADF contract). .config resolves
// project-root paths from it via $directories map provided here.
define ('sep', DIRECTORY_SEPARATOR);
define ('publicpath', realpath (__DIR__).sep);
define ('basepath', dirname (__DIR__).sep);

try
{
    @require call_user_func ('implode', '.', [basepath, 'config']);

    if (isset ($boot) && class_exists ($boot))
    {
        $boot::initiate ();
    }
    else {throw new Exception ('Boot not starting up');}
}
catch (Throwable $th)
{
    if (in_array (__env ('app.env'), ['local', 'dev']) 
        && is (__env ('app.debug'), true))
    {
        error_reporting (E_ALL);
        @ini_set ('display_errors', 1);
    }

    else
    {
        error_reporting (0);
        @ini_set ('display_errors', 0);
    }

    // Debugging purpose
    $message = "{$th->getMessage ()} in {$th->getFile()}:{$th->getLine()}";

    error_log ($th->getMessage () . ' in ' . $th->getFile() . ':' . $th->getLine());
    http_response_code (500);
    header ('Content-Type: text/html; charset=utf-8');
    exit ("<h1>{$heading}</h1><p>{$message}</p>");
}
