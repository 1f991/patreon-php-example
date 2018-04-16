<?php require_once __DIR__ . '/vendor/autoload.php';

// Define the path to our `database`
define('DATABASE', __DIR__ . '/database.json');

define('LOG', __DIR__ . '/patreon.log');

// Load the environment configuration
(new \Dotenv\Dotenv(__DIR__))->load();

if (getenv('DEBUG') === "true") {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
} else {
    set_exception_handler(function ($error) {
        echo 'An error occurred that prevented this request from completing. ';
        echo 'If you are the creator of this website: ';
        echo 'change DEBUG=false to DEBUG=true in .env to see more details. ';
        echo 'NOTE: DO NOT enable DEBUG when anyone else can access your website. ';
        echo 'Debug mode will leak your secrets.';
        die;
    });
}
