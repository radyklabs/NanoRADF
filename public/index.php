<?php declare (strict_types=1);

// Application folder name
$application_folder = 'apps';

// Modules folder name
$modules_folder = 'modules';

// System folder name, don't change except you know what're you doing
$system_folder = 'src';

// Debug disclosure: detail (file:line) in bootstrap error output when true.
// Master switch for local/dev. Set false for production. See .env app.env.
$debug = true;

// Calling nanoradf gateway
require realpath ('nanoradf');

// Says who only silence is golden
