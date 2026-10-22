<?php
/**
 * Room Model
 * 
 * Represents the 'rooms' database table. Manages hotel rooms including their
 * types, pricing, status, and availability.
 * 
 * Database Table: rooms
 * Columns: id, room_number, room_type_id, floor, capacity, price_per_night,
 *          description, status
 * 
 * CRUD Operations:
 * - Read: all(), find(), byStatuses(), hasReservations(), distinctFloors()
 * - Create: create()
 * - Update: update(), setStatus()
 * - Delete: delete()
 * 
 * Relationships:
 * - Many-to-One: RoomType (via room_type_id)
 * - One-to-Many: Reservation (via room_id in reservations table)
 * - One-to-Many: CheckIn (via room_id in check_ins table)
 * 
 * Status Values: Available, Reserved, Occupied, Maintenance
 */
namespace App\Models;

use App\Core\Model; // No Code Duplication: Import base Model
use PDO;

/**
 * MVC Pattern - Model
 * Handles database operations for rooms
 * No Code Duplication: Extends base Model for shared DB connection
 */
class Room extends Model // No Code Duplication: Extends base Model (inherits $db)
{
    /**
     * Get all rooms with optional filters for status, floor, and type.
     * Includes room type name and current occupant (if any).
     * 
     * SQL Structure:
     * - SELECT: all room columns, type_name, subquery for current occupant
     * - LEFT JOIN: room_types (for type name)
     * - Subquery: gets guest name from active check-in
     * - WHERE: dynamically built based on $with array
     * - ORDER BY: room_number
     * 
     * Security: Uses prepared statements for all parameterized values
     * 
     * @param array $with Optional filters (status, floor, type)
     * @return array Filtered room records
     */
    public function all(array $with = [])
    {
        $sql = "SELECT r.*, rt.type_name,
                       (SELECT g.full_name FROM check_ins c JOIN guests g ON g.id=c.guest_id
                         WHERE c.room_id=r.id AND c.status='Active' LIMIT 1) occupant
                FROM rooms r LEFT JOIN room_types rt ON r.room_type_id=rt.id";

        $where = [];
        $params = [];
        // Add status filter if provided
        if (isset($with['status']) && $with['status'] !== '') { $where[] = 'r.status=?'; $params[] = $with['status']; }
        // Add floor filter if provided
        if (isset($with['floor']) && $with['floor'] !== '') { $where[] = 'r.floor=?'; $params[] = (int)$with['floor']; }
        // Add type filter if provided
        if (isset($with['type']) && $with['type'] !== '') { $where[] = 'r.room_type_id=?'; $params[] = (int)$with['type']; }

        // Append WHERE clause if filters exist
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY r.room_number';

        $stmt = $this->db->prepare($sql); // Security: Prepared statement
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a room by ID.
     * 
     * SQL Structure:
     * - SELECT: all columns from rooms
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room ID
     * @return array|false Room record or false if not found
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ?"); // Security: Prepared statement
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new room record.
     * 
     * SQL Structure:
     * - INSERT INTO rooms (room_number, room_type_id, floor, capacity,
     *                       price_per_night, description, status)
     * - VALUES (?, ?, ?, ?, ?, ?, ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param array $data Room data array with required keys
     * @return int The newly created room ID
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare( // Security: Prepared statement
            "INSERT INTO rooms (room_number, room_type_id, floor, capacity, price_per_night, description, status)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['room_number'], $data['room_type_id'], $data['floor'], $data['capacity'],
            $data['price_per_night'], $data['description'], $data['status']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing room record.
     * 
     * SQL Structure:
     * - UPDATE rooms SET room_number=?, room_type_id=?, floor=?, capacity=?,
     *                     price_per_night=?, description=?, status=?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room ID to update
     * @param array $data Updated room data
     */
    public function update($id, array $data)
    {
        $stmt = $this->db->prepare( // Security: Prepared statement
            "UPDATE rooms SET room_number=?, room_type_id=?, floor=?, capacity=?, price_per_night=?, description=?, status=? WHERE id=?"
        );
        $stmt->execute([
            $data['room_number'], $data['room_type_id'], $data['floor'], $data['capacity'],
            $data['price_per_night'], $data['description'], $data['status'], $id
        ]);
    }

    /**
     * Update only the status of a room.
     * 
     * SQL Structure:
     * - UPDATE rooms SET status = ?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room ID
     * @param string $status New status value
     */
    public function setStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE rooms SET status=? WHERE id=?"); // Security: Prepared statement
        $stmt->execute([$status, $id]);
    }

    /**
     * Get rooms filtered by multiple status values.
     * Dynamically builds IN clause with placeholders.
     * 
     * SQL Structure:
     * - SELECT: room columns, type_name
     * - LEFT JOIN: room_types
     * - WHERE: status IN (?, ?, ...)
     * - ORDER BY: room_number
     * 
     * Security: Uses dynamically generated placeholders for IN clause
     * 
     * @param array $statuses Array of status values to filter by
     * @return array Matching room records
     */
    public function byStatuses(array $statuses)
    {
        // Generate placeholder string for IN clause (e.g., "?,?,?" for 3 statuses)
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = $this->db->prepare( // Security: Prepared statement
            "SELECT r.*, rt.type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id=rt.id
             WHERE r.status IN ($placeholders) ORDER BY r.room_number"
        );
        $stmt->execute($statuses);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a room has any non-cancelled reservations.
     * Used to prevent deletion of rooms with active/upcoming reservations.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: reservations
     * - WHERE: room_id = ?, status NOT IN ('Cancelled')
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room ID
     * @return bool True if room has reservations
     */
    public function hasReservations($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE room_id=? AND status NOT IN ('Cancelled')"); // Security: Prepared statement
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Delete a room record.
     * 
     * SQL Structure:
     * - DELETE FROM rooms WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room ID to delete
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?"); // Security: Prepared statement
        $stmt->execute([$id]);
    }

    /**
     * Get all distinct floor values from the rooms table.
     * Used for populating floor filter dropdowns.
     * 
     * SQL Structure:
     * - SELECT: DISTINCT floor
     * - FROM: rooms
     * - WHERE: floor IS NOT NULL
     * - ORDER BY: floor
     * 
     * Security: No user input, static query
     * 
     * @return array List of distinct floor numbers
     */
    public function distinctFloors()
    {
        return $this->db->query("SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL ORDER BY floor")
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
