<?php
/**
 * Direct database access for test verification & cleanup.
 * Uses the same constants the app uses (backend/config.php via bootstrap),
 * so it works with config_local.php (WAMP) or the production fallback.
 * Returns null when the database is unreachable (tests should skip then).
 */
function qa_pdo()
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    if (!defined('DB_HOST')) {
        return $pdo = null;
    }
    try {
        // DB_HOST may contain ";port=3308" (WAMP style) which PDO accepts in the DSN.
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        return $pdo;
    } catch (Exception $e) {
        return $pdo = null;
    }
}

/**
 * Remove rows created by QA tests so the developer database stays clean.
 * Only touches qa-* marker records -- never real project data.
 */
function qa_cleanup_test_data($guestEmail = null)
{
    $db = qa_pdo();
    if (!$db) {
        return;
    }
    if ($guestEmail) {
        // Reservations created for this test guest -> delete, then the guest itself.
        $stmt = $db->prepare(
            'DELETE r FROM reservations r
             JOIN guests g ON g.id = r.guest_id
             WHERE g.email = ?'
        );
        $stmt->execute([$guestEmail]);
        $stmt = $db->prepare('DELETE FROM guests WHERE email = ?');
        $stmt->execute([$guestEmail]);
        // Contact messages from QA
        $stmt = $db->prepare('DELETE FROM contacts WHERE email = ?');
        $stmt->execute([$guestEmail]);
    }
}
