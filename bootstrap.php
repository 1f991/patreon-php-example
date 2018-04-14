<?php require_once __DIR__ . '/vendor/autoload.php';

// Define the path to our `database`
define('DATABASE', __DIR__ . '/database.json');

// Load the environment configuration
(new \Dotenv\Dotenv(__DIR__))->load();
