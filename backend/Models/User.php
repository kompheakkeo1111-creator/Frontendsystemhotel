<?php
/**
 * User Model
 * 
 * Represents the 'users' database table. Manages system users (staff) who can
 * access the hotel management system.
 * 
 * Database Table: users
 * Columns: id, username, password, full_name, email, phone, role, status, created_at
 * 
 * CRUD Operations:
 * - Read: all(), find(), findByUsername(), usernameExists(), emailExists()
 * - Create: create()
 * - Update: update()
 * - Delete: delete()
 * 
 * Relationships:
 * - One-to-Many: Reservation (via created_by in reservations table)
 * - One-to-Many: CheckIn (via created_by in check_ins table)
 * 
 * Security Notes:
 * - Passwords are hashed using password_hash() with PASSWORD_DEFAULT
 * - Username and email uniqueness is enforced via existence checks
 * - Role values: admin, staff
 * - Status values: Active, Inactive
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    /**
     * Get all users ordered by creation date.
     * 
     * SQL Structure:
     * - SELECT: all columns from users
     * - ORDER BY: created_at descending (newest first)
     * 
     * Security: No user input, static query
     */
    public function all()
    {
        return $this->db->query(
            "SELECT * FROM users ORDER BY created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user by ID.
     * 
     * SQL Structure:
     * - SELECT: all columns from users
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id User ID
     * @return array|false User record or false if not found
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find a user by username.
     * Used for authentication/login.
     * 
     * SQL Structure:
     * - SELECT: all columns from users
     * - WHERE: username = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param string $username The username to search for
     * @return array|false User record or false if not found
     */
    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a username already exists.
     * Optionally excludes a specific user ID (for updates).
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: users
     * - WHERE: username = ? (AND id != ? if excluding)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param string $username The username to check
     * @param int|null $excludeId Optional user ID to exclude
     * @return bool True if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        // Add exclusion condition if provided
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if an email already exists.
     * Optionally excludes a specific user ID (for updates).
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: users
     * - WHERE: email = ? (AND id != ? if excluding)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param string $email The email to check
     * @param int|null $excludeId Optional user ID to exclude
     * @return bool True if email exists
     */
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];
        // Add exclusion condition if provided
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Create a new user record.
     * Password is hashed before storage using password_hash().
     * 
     * SQL Structure:
     * - INSERT INTO users (username, password, full_name, email, phone, role, status)
     * - VALUES (?, ?, ?, ?, ?, ?, ?)
     * 
     * Security: 
     * - Prepared statement with parameterized query
     * - Password hashed with PASSWORD_DEFAULT (bcrypt)
     * 
     * @param array $data User data array with required keys
     * @return int The newly created user ID
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT), // Security: Hash password before storing
            $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['status']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing user record.
     * Password is only updated if provided (non-empty).
     * 
     * SQL Structure (without password):
     * - UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, status=?
     * - WHERE id = ?
     * 
     * SQL Structure (with password):
     * - UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, status=?, password=?
     * - WHERE id = ?
     * 
     * Security:
     * - Prepared statement with parameterized query
     * - Password hashed with PASSWORD_DEFAULT if provided
     * 
     * @param int $id User ID to update
     * @param array $data Updated user data
     */
    public function update($id, array $data)
    {
        // Base update without password
        $sql = "UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, status=? WHERE id=?";
        $params = [$data['username'], $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['status'], $id];
        
        // Include password in update if provided
        if (!empty($data['password'])) {
            $sql = "UPDATE users SET username=?, full_name=?, email=?, phone=?, role=?, status=?, password=? WHERE id=?";
            $params = [$data['username'], $data['full_name'], $data['email'], $data['phone'], $data['role'], $data['status'], password_hash($data['password'], PASSWORD_DEFAULT), $id]; // Security: Hash password before updating
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Delete a user record.
     * 
     * SQL Structure:
     * - DELETE FROM users WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id User ID to delete
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}
