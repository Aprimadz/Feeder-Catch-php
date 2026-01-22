<?php

require_once __DIR__ . '/DotEnv.php';

// Load environment variables
try {
    $dotenv = new DotEnv(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    die('Error loading .env file: ' . $e->getMessage() . "\n");
}

return [
    'url' => DotEnv::get('FEEDER_URL', 'http://10.150.1.213:3003/ws/live2.php'),
    'username' => DotEnv::get('FEEDER_USERNAME'),
    'password' => DotEnv::get('FEEDER_PASSWORD'),
    'log_enabled' => filter_var(DotEnv::get('LOG_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'log_file' => DotEnv::get('LOG_FILE', 'feeder.log')
];