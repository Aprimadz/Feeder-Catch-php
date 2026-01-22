<?php

/**
 * Simple .env file loader
 * Membaca file .env dan memuat variabel ke $_ENV
 */
class DotEnv {
    private string $path;

    public function __construct(string $dir) {
        $this->path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.env';
    }

    public function load(): void {
        if (!file_exists($this->path)) {
            throw new Exception('.env file not found at: ' . $this->path);
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');

                // Set to environment
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    public static function get(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
