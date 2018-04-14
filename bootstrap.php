<?php require_once __DIR__ . '/vendor/autoload.php';

// Define the path to our `database`
define('DATABASE', __DIR__ . '/database.json');

define('LOG', __DIR__ . '/patreon.log');

// Load the environment configuration
(new \Dotenv\Dotenv(__DIR__))->load();
