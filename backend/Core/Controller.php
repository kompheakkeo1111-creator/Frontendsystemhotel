<?php
namespace App\Core;

/**
 * No Code Duplication Pattern - Base Controller Class
 * All controllers inherit from this class to reuse common methods
 * Avoids repeating login checks, view rendering, redirects in every controller
 */
abstract class Controller
{
    /**
     * Whether this controller requires authentication.
     * Set to false in controllers like LoginController/LogoutController.
     * @var bool
     */
    public $authRequired = true;

    /**
     * MVC Pattern - Render a view file with data
     * Reusable: All controllers use this to render views
     *
     * @param string $view relative path under Views (e.g., 'users/index')
     * @param array  $data  variables passed to the view
     * @param string $active nav key used by the layout header
     * @param string $pageTitle
     */
    protected function view($view, array $data = [], $active = '', $pageTitle = '')
    {
        extract($data); // Extract array into variables: $users, $roles, etc.
        if (empty($active)) $active = $this->active ?? '';
        $GLOBALS['active'] = $active;
        $pageTitle = $pageTitle ?: ($this->pageTitle ?? '');
        require __DIR__ . '/../Views/layout/header.php'; // Reusable header
        require __DIR__ . '/../Views/' . $view . '.php'; // Render view
        require __DIR__ . '/../Views/layout/footer.php'; // Reusable footer
    }

    /**
     * Reusable: Redirect to URL
     * All controllers use this for redirects
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Security: Require login
     * Reusable: All controllers call this for authentication
     */
    protected function loginRequired()
    {
        requireLogin(); // Calls function from config.php
    }

    /**
     * Security: Require specific role(s)
     * Reusable: All controllers call this for authorization
     * Example: $this->roleRequired(['Administrator']);
     */
    protected function roleRequired($roles)
    {
        requireRole($roles); // Calls function from config.php
    }

    /**
     * Reusable: Send JSON response
     * Used by API controllers
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        echo json_encode($data);
        exit;
    }
}
