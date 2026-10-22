<?php
namespace App\Core;

/**
 * MVC Pattern - Router (Controller dispatcher)
 * Routes URL requests to the appropriate Controller and Action
 * Format: ?r=controller/action (e.g., ?r=room_types/index)
 */
class Router
{
    /**
     * MVC Pattern - Dispatch request to Controller
     * @param string $route e.g. "room_types/index"
     */
    public function dispatch($route)
    {
        $route = trim($route, '/');
        if ($route === '') {
            $route = 'dashboard/index'; // Default route
        }

        // Parse route: "room_types/index" -> controller="room_types", action="index"
        $parts = explode('/', $route);
        $controllerRaw = preg_replace('/[^a-zA-Z0-9_]/', '', $parts[0]);
        $controller = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($controllerRaw))));
        // singularize a trailing "s" so "room_types" -> RoomTypeController
        if (substr($controller, -1) === 's' && substr($controller, -1) !== 'ss') {
            $controller = substr($controller, 0, -1);
        }
        // Fix common camelCase: checkin -> CheckIn, checkout -> CheckOut
        $controller = str_replace(['Checkin','Checkout'], ['CheckIn','CheckOut'], $controller);
        $action = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : 'index';

        // MVC: Build controller class name and method name
        $class = 'App\\Controllers\\' . $controller . 'Controller'; // e.g., App\Controllers\RoomTypeController
        $method = $action . 'Action'; // e.g., indexAction

        // MVC: Check if controller exists
        if (!class_exists($class)) {
            http_response_code(404);
            echo 'Controller not found: ' . htmlspecialchars($class);
            return;
        }

        // MVC: Create controller instance and call action method
        $instance = new $class();

        // MVC: Check authentication if required
        if ($instance->authRequired && !isLoggedIn()) {
            $instance->redirect('index.php?r=login/index');
        }

        // MVC: Check if action method exists
        if (!method_exists($instance, $method)) {
            http_response_code(404);
            echo 'Action not found: ' . htmlspecialchars($method);
            return;
        }

        // MVC: Execute the action method
        $instance->$method();
    }

    /**
     * Build a route URL.
     */
    public static function url($route)
    {
        return 'index.php?r=' . $route;
    }
}
