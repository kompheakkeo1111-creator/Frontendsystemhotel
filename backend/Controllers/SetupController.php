<?php
namespace App\Controllers;

/**
 * SetupController - Initial installation and database setup
 * 
 * Handles the initial installation of the hotel management system.
 * Executes the SQL schema file to create all database tables and
 * sets the default admin password.
 * 
 * WARNING: This controller should be disabled or protected after
 * initial installation to prevent unauthorized database resets.
 * 
 * Access: Public (no authentication required)
 * 
 * Available Actions:
 *   - indexAction(): Execute database setup and display results
 */
class SetupController extends \App\Core\Controller {
    /** @var bool No authentication required for initial setup */
    public $authRequired = false;

    /**
     * indexAction - Execute database installation from SQL file.
     * 
     * On POST:
     *   1. Connects to the database using configured credentials
     *   2. Reads and executes the hotel_clean.sql schema file
     *   3. Resets the admin user's password to 'admin123'
     *   4. Displays success or error message
     * 
     * Data Flow:
     *   Input:   POST request (no parameters needed)
     *   Process: Reads SQL file, splits into statements, executes each
     *   Output:  Renders setup/index.php with success/error message
     */
    public function indexAction() {
        $message = '';
        $success = false;

        // Process setup on POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Connect to database using constants from config
                $pdo = new \PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Read the SQL schema file
                $sqlFile = __DIR__ . '/../database/hotel_clean.sql';
                $sql = file_get_contents($sqlFile);

                // Split SQL into individual statements and execute each
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    // Only execute non-empty statements that start with a letter (SQL commands)
                    if (!empty($stmt) && preg_match('/^[A-Z]/i', $stmt)) {
                        $pdo->exec($stmt);
                    }
                }

                // Reset admin user password to default
                // Security: Uses password_hash for secure storage
                $hash = password_hash('admin123', PASSWORD_DEFAULT);
                $pdo->exec("UPDATE users SET password = '" . $hash . "' WHERE username = 'admin'");

                $success = true;
                $message = "Installation complete! Default login: admin / admin123";

            } catch (\PDOException $e) {
                // Display database connection/execution errors
                $message = "Error: " . $e->getMessage();
            }
        }

        // Render the setup page
        require __DIR__ . '/../Views/setup/index.php';
    }
}
