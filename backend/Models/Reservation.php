<?php
/**
 * Reservation Model
 * 
 * Represents the 'reservations' database table. Manages hotel room reservations,
 * including booking, status updates, and availability checks.
 * 
 * Database Table: reservations
 * Columns: id, reservation_number, guest_id, room_id, check_in_date, check_out_date,
 *          number_of_guests, status, special_requests, total_amount, created_by, created_at
 * 
 * CRUD Operations:
 * - Read: all(), roomIsAvailable(), roomNightlyRate(), roomIdFor(),
 *         hasActiveCheckIn(), hasOtherNonCancelledForRoom()
 * - Create: create()
 * - Update: updateDatesAndStatus(), setStatus(), setRoomStatus()
 * - Delete: delete()
 * 
 * Relationships:
 * - Many-to-One: Guest (via guest_id)
 * - Many-to-One: Room (via room_id)
 * - One-to-Many: CheckIn (via reservation_id in check_ins table)
 * 
 * Status Values: Pending, Confirmed, Cancelled, Checked In, Checked Out
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class Reservation extends Model
{
    /**
     * Get all reservations with optional filters for status and search query.
     * Dynamically builds WHERE clauses based on provided parameters.
     * 
     * SQL Structure:
     * - SELECT: reservation details, guest name, room number/price, type name,
     *           computed nights (DATEDIFF)
     * - JOIN: guests (required), rooms (LEFT), room_types (LEFT)
     * - WHERE: dynamically built based on $status and $q parameters
     * - ORDER BY: created_at descending (newest first)
     * 
     * Security: Uses prepared statements for all parameterized values.
     *           LIKE queries use % wildcards for partial matching.
     * 
     * @param string $status Optional status filter
     * @param string $q Optional search query (matches reservation_number or guest name)
     * @return array Filtered reservation records
     */
    public function all($status = '', $q = '')
    {
        $where = [];
        $params = [];
        // Add status filter if provided
        if ($status !== '') { $where[] = 'r.status=?'; $params[] = $status; }
        // Add search filter if provided (searches reservation number and guest name)
        if ($q !== '') { $where[] = '(r.reservation_number LIKE ? OR g.full_name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

        $sql = "SELECT r.*, g.full_name guest_name, rm.room_number, rm.price_per_night, rt.type_name,
                DATEDIFF(r.check_out_date, r.check_in_date) nights
                FROM reservations r
                JOIN guests g ON r.guest_id=g.id
                LEFT JOIN rooms rm ON r.room_id=rm.id
                LEFT JOIN room_types rt ON rm.room_type_id=rt.id";
        // Append WHERE clause if filters exist
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a room is available for a given date range.
     * Excludes cancelled reservations and optionally excludes a specific reservation.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: reservations
     * - WHERE: room_id = ?, status NOT IN ('Cancelled'), id <> ?,
     *           check_in_date < ? AND check_out_date > ?
     * 
     * Availability Logic: A room is available if no non-cancelled reservation
     * overlaps with the requested date range (standard interval overlap detection).
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $room_id The room ID to check
     * @param string $check_in Requested check-in date
     * @param string $check_out Requested check-out date
     * @param int $excludeResId Optional reservation ID to exclude (for updates)
     * @return bool True if room is available, false otherwise
     */
    public function roomIsAvailable($room_id, $check_in, $check_out, $excludeResId = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE room_id=? AND status NOT IN ('Cancelled')
             AND id <> ? AND check_in_date < ? AND check_out_date > ?"
        );
        $stmt->execute([$room_id, $excludeResId, $check_out, $check_in]);
        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * Get the nightly rate for a specific room.
     * 
     * SQL Structure:
     * - SELECT: price_per_night
     * - FROM: rooms
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $room_id The room ID
     * @return float The nightly rate
     */
    public function roomNightlyRate($room_id)
    {
        $stmt = $this->db->prepare("SELECT price_per_night FROM rooms WHERE id=?");
        $stmt->execute([$room_id]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Create a new reservation record.
     * 
     * SQL Structure:
     * - INSERT INTO reservations (reservation_number, guest_id, room_id, check_in_date,
     *                             check_out_date, number_of_guests, status, special_requests,
     *                             total_amount, created_by)
     * - VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param array $data Reservation data array with required keys
     * @return int The newly created reservation ID
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reservations (reservation_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, status, special_requests, total_amount, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['reservation_number'], $data['guest_id'], $data['room_id'],
            $data['check_in_date'], $data['check_out_date'], $data['number_of_guests'],
            $data['status'], $data['special_requests'], $data['total_amount'], $data['created_by']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update reservation dates, guest count, status, requests, and total.
     * 
     * SQL Structure:
     * - UPDATE reservations SET check_in_date=?, check_out_date=?,
     *                           number_of_guests=?, status=?, special_requests=?,
     *                           total_amount=?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Reservation ID
     * @param string $check_in New check-in date
     * @param string $check_out New check-out date
     * @param int $numGuests Number of guests
     * @param string $status New status
     * @param string $requests Special requests
     * @param float $totalAmount New total amount
     */
    public function updateDatesAndStatus($id, $check_in, $check_out, $numGuests, $status, $requests, $totalAmount)
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations SET check_in_date=?, check_out_date=?, number_of_guests=?, status=?, special_requests=?, total_amount=? WHERE id=?"
        );
        $stmt->execute([$check_in, $check_out, $numGuests, $status, $requests, $totalAmount, $id]);
    }

    /**
     * Update only the status of a reservation.
     * 
     * SQL Structure:
     * - UPDATE reservations SET status = ?
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Reservation ID
     * @param string $status New status value
     */
    public function setStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE reservations SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }

    /**
     * Get the room ID associated with a reservation.
     * 
     * SQL Structure:
     * - SELECT: room_id
     * - FROM: reservations
     * - WHERE: id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Reservation ID
     * @return int|null Room ID or null if not found
     */
    public function roomIdFor($id)
    {
        $stmt = $this->db->prepare("SELECT room_id FROM reservations WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    /**
     * Check if a reservation has an active check-in.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: check_ins
     * - WHERE: reservation_id = ?, status = 'Active'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Reservation ID
     * @return bool True if reservation has active check-in
     */
    public function hasActiveCheckIn($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM check_ins WHERE reservation_id=? AND status='Active'");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if a room has other non-cancelled reservations.
     * Used to determine if room status can be changed.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - FROM: reservations
     * - WHERE: room_id = ?, status NOT IN ('Cancelled')
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $room_id Room ID
     * @return bool True if room has other active reservations
     */
    public function hasOtherNonCancelledForRoom($room_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE room_id=? AND status NOT IN ('Cancelled')");
        $stmt->execute([$room_id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Delete a reservation record.
     * 
     * SQL Structure:
     * - DELETE FROM reservations WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $id Reservation ID to delete
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM reservations WHERE id=?");
        $stmt->execute([$id]);
    }

    /**
     * Update room status (only if currently 'Reserved').
     * Used when cancelling a reservation to free up the room.
     * 
     * SQL Structure:
     * - UPDATE rooms SET status = ?
     * - WHERE id = ? AND status = 'Reserved'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $room_id Room ID
     * @param string $status New room status
     */
    public function setRoomStatus($room_id, $status)
    {
        $stmt = $this->db->prepare("UPDATE rooms SET status=? WHERE id=? AND status='Reserved'");
        $stmt->execute([$status, $room_id]);
    }
}
