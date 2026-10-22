<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * SettingController - System settings and database management
 * 
 * Administrator-only controller for managing hotel system settings
 * and performing database backup/restore operations.
 * 
 * Handles:
 *   - System settings (hotel name, address, phone, email, currency, tax rate)
 *   - Database backup (generates SQL dump files)
 *   - Database restore (from uploaded SQL backup files)
 *   - Backup file management (list, delete)
 * 
 * Access Roles: Administrator only
 * 
 * Available Actions:
 *   - indexAction(): Display settings page and handle save/backup/restore/delete operations
 */
class SettingController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'settings';

    /** @var string Page title for the layout */
    protected $pageTitle = 'Settings';

    /** @var array Allowed system setting keys */
    private $settingFields = ['hotel_name', 'hotel_address', 'hotel_phone', 'hotel_email', 'currency', 'tax_rate'];

    /** @var string Path to the backup directory */
    private $backupDir;

    /**
     * Constructor - Enforce Administrator-only access and initialize backup directory.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator']); // Administrator-only access control
        $this->db = getDB(); // Initialize database connection
        // Set backup directory path (relative to project root)
        $this->backupDir = __DIR__ . '/../../backups/';
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * indexAction - Display settings page and handle all settings operations.
     * 
     * Handles four POST actions:
     *   - 'save'         : Save system settings to database
     *   - 'backup'       : Create a SQL backup of the entire database
     *   - 'restore'      : Restore database from an uploaded backup file
     *   - 'delete_backup': Delete a specific backup file
     * 
     * Data Flow:
     *   Input:   POST data with action and relevant fields/files
     *   Process: Validates input, performs database operations
     *   Output:  Renders settings/index view with settings, backups, and messages
     */
    public function indexAction()
    {
        $message = '';
        $error = '';

        // Handle POST form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'save') {
                // --- Save System Settings ---
                try {
                    // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior
                    $stmt = $this->db->prepare("INSERT INTO system_settings (setting_key, setting_value)
                                                VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    // Save each setting field from POST data
                    foreach ($this->settingFields as $key) {
                        $val = trim($_POST[$key] ?? '');
                        $stmt->execute([$key, $val]);
                    }
                    $message = 'Settings updated successfully.';
                } catch (\Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }

            } elseif ($action === 'backup') {
                // --- Create Database Backup ---
                try {
                    // Generate timestamped backup filename
                    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                    $filepath = $this->backupDir . $filename;

                    // Get all table names from the database
                    $tables = [];
                    $result = $this->db->query("SHOW TABLES");
                    while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                        $tables[] = $row[0];
                    }

                    // Build SQL dump with transaction wrapper
                    $sql = "-- Hotel Management System Backup\n";
                    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
                    $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
                    $sql .= "SET AUTOCOMMIT = 0;\n";
                    $sql .= "START TRANSACTION;\n\n";

                    // Export each table's structure and data
                    foreach ($tables as $table) {
                        $sql .= "-- Table: $table\n";
                        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

                        // Get CREATE TABLE statement
                        $create = $this->db->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_NUM);
                        $sql .= $create[1] . ";\n\n";

                        // Get all rows and build INSERT statements
                        $rows = $this->db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_NUM);
                        if ($rows) {
                            // Get column names for INSERT statement
                            $cols = $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_NUM);
                            $colNames = array_map(function($c) { return '`' . $c[0] . '`'; }, $cols);
                            $sql .= "INSERT INTO `$table` (" . implode(', ', $colNames) . ") VALUES\n";

                            // Build value rows with proper escaping
                            $valueRows = [];
                            foreach ($rows as $row) {
                                $vals = array_map(function($v) {
                                    return $v === null ? 'NULL' : "'" . addslashes($v) . "'";
                                }, $row);
                                $valueRows[] = '(' . implode(', ', $vals) . ')';
                            }
                            $sql .= implode(",\n", $valueRows) . ";\n\n";
                        }
                    }

                    $sql .= "COMMIT;\n";
                    // Write SQL dump to file
                    file_put_contents($filepath, $sql);
                    $message = "Backup created: $filename";
                } catch (\Exception $e) {
                    $error = 'Backup failed: ' . $e->getMessage();
                }

            } elseif ($action === 'restore') {
                // --- Restore Database from Backup ---
                try {
                    // Validate file upload
                    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new \Exception('Please select a backup file.');
                    }

                    // Read the uploaded SQL file
                    $content = file_get_contents($_FILES['backup_file']['tmp_name']);
                    if (empty($content)) {
                        throw new \Exception('Backup file is empty.');
                    }

                    // Disable foreign key checks during restore
                    $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");

                    // Split SQL file into individual statements and execute valid ones
                    $statements = array_filter(array_map('trim', explode(';', $content)));
                    foreach ($statements as $stmt) {
                        // Only execute recognized SQL statements (DROP, CREATE, INSERT, etc.)
                        if (!empty($stmt) && preg_match('/^(DROP|CREATE|INSERT|ALTER|SET|START|COMMIT)/i', $stmt)) {
                            $this->db->exec($stmt);
                        }
                    }

                    // Re-enable foreign key checks
                    $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

                    $message = 'Database restored successfully.';
                } catch (\Exception $e) {
                    $error = 'Restore failed: ' . $e->getMessage();
                }

            } elseif ($action === 'delete_backup') {
                // --- Delete a Backup File ---
                $file = $_POST['file'] ?? '';
                // Use basename() to prevent directory traversal attacks
                $filepath = $this->backupDir . basename($file);
                if (file_exists($filepath)) {
                    unlink($filepath);
                    $message = 'Backup deleted.';
                }
            }
        }

        // --- List Existing Backup Files ---
        $backups = [];
        if (is_dir($this->backupDir)) {
            foreach (glob($this->backupDir . '*.sql') as $f) {
                $backups[] = [
                    'name' => basename($f),
                    'size' => round(filesize($f) / 1024, 1), // Size in KB
                    'date' => date('Y-m-d H:i', filemtime($f)),
                ];
            }
        }
        // Sort backups by name (newest first)
        usort($backups, function($a, $b) { return strcmp($b['name'], $a['name']); });

        // Get current system settings for display
        $settings = getSystemSettings();

        // Render the settings view
        $this->view('settings/index', [
            'settings' => $settings,
            'backups'  => $backups,
            'message'  => $message,
            'error'    => $error,
        ], 'settings', 'Settings');
    }
}
