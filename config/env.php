<?php
// config/env.php
// Load environment variables from .env file

function loadEnv($filePath = __DIR__ . '/../.env') {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set as environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load the .env file
loadEnv();

// Helper function to get env variables
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}
