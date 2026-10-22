<?php
/**
 * Guest Model
 * 
 * Represents the 'guests' database table. Stores information about hotel guests
 * including personal details and identification.
 * 
 * Database Table: guests
 * Columns: id, full_name, gender, date_of_birth, nationality, phone, email,
 *          address, identification_number, created_at
 * 
 * CRUD Operations:
 * - Read: all(), find()
 * - Create: create()
 * - Update: update()
 * - Delete: delete()
 * 
 * Relationships:
 * - One-to-Many: Reservation (guest can have multiple reservations)
 * - One-to-Many: CheckIn (guest can have multiple check-ins)
 * 
 * Helper Methods:
 * - hasReservations(): Check if guest has any reservations
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class Guest extends Model
{
    /**
     * Get all guests with additional computed columns.
     * Includes count of stays (non-cancelled reservations) and active stay status.
     * 
     * SQL Structure:
     * - SELECT: all guest columns, plus subqueries for stay count and active status
     * - Subquery 1: COUNT of non-cancelled reservations
     * - Subquery 2: COUNT of active check-ins (0 or 1)
     * - ORDER BY: created_at descending (newest first)
     * 
     * Security: No user input, static query
     */
    public function all()
    {
        return $this->db->query(
            "SELECT g.*,
                    (SELECT COUNT(*) FROM reservations r WHERE r.guest_id=g.id AND r.status != 'Cancelled') stays,
                    (SELECT COUNT(*) FROM check_ins c WHERE c.guest_id=g.id AND c.status='Active') active_stay
             FROM guests g ORDER BY g.created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a guest by ID.
     * 
     * SQL Structure:
     * - SELECT: all columns from guests
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id The guest ID
     * @return array|false Guest record or false if not found
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM guests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new guest record.
     * 
     * SQL Structure:
     * - INSERT INTO guests (full_name, gender, date_of_birth, nationality,
     *                       phone, email, address, identification_number)
     * - VALUES (?, ?, ?, ?, ?, ?, ?, ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param array $data Guest data array with required keys
     * @return int The newly created guest ID
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO guests (full_name, gender, date_of_birth, nationality, phone, email, address, identification_number)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['full_name'], $data['gender'], $data['date_of_birth'], $data['nationality'],
            $data['phone'], $data['email'], $data['address'], $data['identification_number']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing guest record.
     * 
     * SQL Structure:
     * - UPDATE guests SET full_name=?, gender=?, date_of_birth=?, nationality=?,
     *                     phone=?, email=?, address=?, identification_number=?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id The guest ID to update
     * @param array $data Updated guest data
     */
    public function update($id, array $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE guests SET full_name=?, gender=?, date_of_birth=?, nationality=?, phone=?, email=?, address=?, identification_number=? WHERE id=?"
        );
        $stmt->execute([
            $data['full_name'], $data['gender'], $data['date_of_birth'], $data['nationality'],
            $data['phone'], $data['email'], $data['address'], $data['identification_number'], $id
        ]);
    }

    /**
     * Check if a guest has any reservations.
     * Used to prevent deletion of guests with existing reservations.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: reservations
     * - WHERE: guest_id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id The guest ID
     * @return bool True if guest has reservations, false otherwise
     */
    public function hasReservations($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE guest_id=?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Delete a guest record.
     * 
     * SQL Structure:
     * - DELETE FROM guests WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id The guest ID to delete
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM guests WHERE id = ?");
        $stmt->execute([$id]);
    }
}
