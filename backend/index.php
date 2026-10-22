<?php
/**
 * MVC Pattern - Front Controller (Entry Point)
 * All pages are served through this single entry point
 * URL format: index.php?r=controller/action
 * Example: index.php?r=rooms/index -> RoomController::indexAction()
 */

// Enable CORS for cross-origin requests
require __DIR__ . '/cors.php';

// Autoloading: Bootstrap loads all classes automatically
require __DIR__ . '/bootstrap.php';

// Autoloading: No require needed for Router
use App\Core\Router;

// MVC: Get route from URL parameter
$route = isset($_GET['r']) ? $_GET['r'] : '';

// Security: Check if system is installed
if (!isInstalled() && $route !== 'setup/index') {
    header('Location: index.php?r=setup/index');
    exit();
}

// MVC: Create router and dispatch request to Controller
$router = new Router(); // Autoloading: No require needed
$router->dispatch($route); // Routes to Controller -> Action -> View
