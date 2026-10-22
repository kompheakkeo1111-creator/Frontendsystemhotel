<?php
/**
 * MVC bootstrap loader.
 * Loads the legacy app config (DB connection, session, shared helpers)
 * and registers a PSR-0 style autoloader for the app/ namespace.
 */
require_once __DIR__ . '/config.php'; // Load configuration (DB, session, helpers)

// Autoloading: Auto-load classes without require/include
// When you use "use App\Controllers\LoginController", this auto-loads the file
spl_autoload_register(function ($class) {
    $prefix = 'App\\'; // App namespace prefix
    if (strpos($class, $prefix) !== 0) {
        return; // Not our namespace, skip
    }
    $relative = substr($class, strlen($prefix)); // Remove prefix: "Controllers/LoginController"
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php'; // Convert to file path
    if (is_file($file)) {
        require $file; // Auto-require the file
    }
});
