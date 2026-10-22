<?php
/**
 * RoomType Model
 * 
 * Represents the 'room_types' database table. Stores room type definitions
 * including pricing and capacity information.
 * 
 * Database Table: room_types
 * Columns: id, type_name, description, price_per_night, capacity
 * 
 * CRUD Operations:
 * - Read: all(), find()
 * - Create: create()
 * - Update: update()
 * - Delete: delete()
 * 
 * Relationships:
 * - One-to-Many: Room (via room_type_id in rooms table)
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class RoomType extends Model
{
    /**
     * Get all room types with room count.
     * Includes a computed column showing how many rooms exist for each type.
     * 
     * SQL Structure:
     * - SELECT: all room_type columns, plus subquery for room count
     * - Subquery: COUNT of rooms with matching room_type_id
     * - ORDER BY: id
     * 
     * Security: No user input, static query
     */
    public function all()
    {
        return $this->db->query(
            "SELECT rt.*,
                    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id) AS room_count
             FROM room_types rt
             ORDER BY rt.id"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a room type by ID.
     * 
     * SQL Structure:
     * - SELECT: all columns from room_types
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room type ID
     * @return array|false Room type record or false if not found
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new room type record.
     * 
     * SQL Structure:
     * - INSERT INTO room_types (type_name, description, price_per_night, capacity)
     * - VALUES (?, ?, ?, ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param string $type_name Name of the room type
     * @param string $description Description of the room type
     * @param float $price Price per night
     * @param int $capacity Maximum guest capacity
     * @return int The newly created room type ID
     */
    public function create($type_name, $description, $price, $capacity)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO room_types (type_name, description, price_per_night, capacity)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$type_name, $description, $price, $capacity]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update an existing room type record.
     * 
     * SQL Structure:
     * - UPDATE room_types SET type_name=?, description=?,
     *                         price_per_night=?, capacity=?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room type ID to update
     * @param string $type_name Updated name
     * @param string $description Updated description
     * @param float $price Updated price
     * @param int $capacity Updated capacity
     */
    public function update($id, $type_name, $description, $price, $capacity)
    {
        $stmt = $this->db->prepare(
            "UPDATE room_types SET type_name = ?, description = ?, price_per_night = ?, capacity = ? WHERE id = ?"
        );
        $stmt->execute([$type_name, $description, $price, $capacity, $id]);
    }

    /**
     * Delete a room type record.
     * 
     * SQL Structure:
     * - DELETE FROM room_types WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Room type ID to delete
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM room_types WHERE id = ?");
        $stmt->execute([$id]);
    }
}
