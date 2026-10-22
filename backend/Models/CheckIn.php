<?php
/**
 * CheckIn Model
 * 
 * Represents the 'check_ins' database table. Tracks guest check-ins and check-outs
 * for hotel rooms. Each check-in record represents a guest's actual stay at the hotel,
 * linked to a reservation.
 * 
 * Database Table: check_ins
 * Columns: id, reservation_id, guest_id, room_id, check_in_time, expected_check_out,
 *          actual_check_out, status, created_by
 * 
 * CRUD Operations:
 * - Read: readyReservations(), activeStays(), activeStaysForCheckout(), findByReservation(),
 *         activeCountForReservation(), roomCurrentlyOccupied(), activeCountForRoomOverlap(),
 *         checkoutDetail(), extraChargesTotal()
 * - Create: insert(), addExtraCharge()
 * - Update: markCheckedOut()
 * 
 * Relationships:
 * - Many-to-One: Reservation (via reservation_id)
 * - Many-to-One: Guest (via guest_id)
 * - Many-to-One: Room (via room_id)
 * - One-to-Many: ExtraCharge (via check_in_id in extra_charges table)
 */
namespace App\Models;

use App\Core\Model;
use PDO;

class CheckIn extends Model
{
    /**
     * Get all reservations that are ready for check-in.
     * Returns reservations with status 'Pending' or 'Confirmed' that have an assigned room
     * but no active check-in record yet.
     * 
     * SQL Structure:
     * - SELECT: reservation ID, number, guest name, room number, dates, status
     * - JOIN: guests (for guest name), rooms (for room number)
     * - LEFT JOIN: rooms (optional, in case room not yet assigned)
     * - WHERE: status is Pending/Confirmed, room is assigned, no active check-in exists
     * - ORDER BY: check-in date ascending (earliest first)
     * 
     * Security: No user input, static query
     */
    public function readyReservations()
    {
        return $this->db->query(
            "SELECT r.id, r.reservation_number, g.full_name guest_name, rm.room_number, r.check_in_date, r.check_out_date, r.status
             FROM reservations r
             JOIN guests g ON r.guest_id=g.id
             LEFT JOIN rooms rm ON r.room_id=rm.id
             WHERE r.status IN ('Pending','Confirmed') AND r.room_id IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM check_ins c WHERE c.reservation_id=r.id AND c.status='Active')
             ORDER BY r.check_in_date ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all currently active stays (guests who have checked in but not yet checked out).
     * 
     * SQL Structure:
     * - SELECT: check-in ID, guest name, room number, times, reservation number
     * - JOIN: guests (for guest name), rooms (for room number)
     * - LEFT JOIN: reservations (for reservation number, optional)
     * - WHERE: status = 'Active'
     * - ORDER BY: check-in time descending (most recent first)
     * 
     * Security: No user input, static query
     */
    public function activeStays()
    {
        return $this->db->query(
            "SELECT c.id, g.full_name guest_name, rm.room_number, c.check_in_time, c.expected_check_out, r.reservation_number
             FROM check_ins c
             JOIN guests g ON c.guest_id=g.id
             JOIN rooms rm ON c.room_id=rm.id
             LEFT JOIN reservations r ON c.reservation_id=r.id
             WHERE c.status='Active' ORDER BY c.check_in_time DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get active stays with extra charges information for checkout processing.
     * Includes aggregated extra charges data for billing.
     * 
     * SQL Structure:
     * - SELECT: check-in details, guest info, room info, reservation info,
     *           plus subqueries for extra charges total and count
     * - JOIN: guests, rooms (required), reservations (LEFT)
     * - WHERE: status = 'Active'
     * - ORDER BY: check-in time ascending (oldest first)
     * 
     * Security: No user input, static query
     */
    public function activeStaysForCheckout()
    {
        return $this->db->query(
            "SELECT c.id, c.check_in_time, c.expected_check_out,
                    g.full_name guest_name, rm.room_number, rm.price_per_night,
                    r.reservation_number,
                    (SELECT COALESCE(SUM(amount),0) FROM extra_charges WHERE check_in_id=c.id) extra_total,
                    (SELECT COUNT(*) FROM extra_charges WHERE check_in_id=c.id) extra_count
             FROM check_ins c
             JOIN guests g ON c.guest_id=g.id
             JOIN rooms rm ON c.room_id=rm.id
             LEFT JOIN reservations r ON c.reservation_id=r.id
             WHERE c.status='Active'
             ORDER BY c.check_in_time ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find an active check-in record by reservation ID.
     * 
     * SQL Structure:
     * - SELECT: all columns from check_ins
     * - WHERE: reservation_id = ?, status = 'Active'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $reservationId The reservation ID to search for
     * @return array|false Check-in record or false if not found
     */
    public function findByReservation($reservationId)
    {
        $stmt = $this->db->prepare("SELECT * FROM check_ins WHERE reservation_id=? AND status='Active'");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Count active check-ins for a specific reservation.
     * Used to prevent duplicate check-ins for the same reservation.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - WHERE: reservation_id = ?, status = 'Active'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $reservationId The reservation ID to check
     * @return int Number of active check-ins
     */
    public function activeCountForReservation($reservationId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM check_ins WHERE reservation_id=? AND status='Active'");
        $stmt->execute([$reservationId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if a room is currently occupied by an active check-in.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - WHERE: room_id = ?, status = 'Active'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $roomId The room ID to check
     * @return bool True if room is occupied, false otherwise
     */
    public function roomCurrentlyOccupied($roomId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM check_ins WHERE room_id=? AND status='Active'");
        $stmt->execute([$roomId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Count active check-ins for a specific room (used for overlap detection).
     * Note: Currently does not use check-in/check-out date parameters for overlap detection.
     * 
     * SQL Structure:
     * - SELECT: COUNT(*)
     * - WHERE: room_id = ?, status = 'Active'
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $roomId The room ID to check
     * @param string $checkIn Check-in date (currently unused)
     * @param string $checkOut Check-out date (currently unused)
     * @return int Number of active check-ins for the room
     */
    public function activeCountForRoomOverlap($roomId, $checkIn, $checkOut)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM check_ins WHERE room_id=? AND status='Active'");
        $stmt->execute([$roomId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new check-in record.
     * Sets status to 'Active' and records current timestamp as check-in time.
     * 
     * SQL Structure:
     * - INSERT INTO check_ins (reservation_id, guest_id, room_id, check_in_time,
     *                          expected_check_out, status, created_by)
     * - VALUES (?, ?, ?, NOW(), ?, 'Active', ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $reservationId The associated reservation ID
     * @param int $guestId The guest ID
     * @param int $roomId The room ID
     * @param string $expectedOut Expected check-out date
     * @param int $userId ID of the user creating the check-in
     * @return int The newly created check-in ID
     */
    public function insert($reservationId, $guestId, $roomId, $expectedOut, $userId)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO check_ins (reservation_id, guest_id, room_id, check_in_time, expected_check_out, status, created_by)
             VALUES (?,?,?,NOW(),?,'Active',?)"
        );
        $stmt->execute([$reservationId, $guestId, $roomId, $expectedOut, $userId]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get detailed check-in information for checkout processing.
     * Includes guest, room, and reservation details.
     * 
     * SQL Structure:
     * - SELECT: all check-in columns, guest name/ID, room number/ID/price,
     *           reservation details
     * - JOIN: guests, rooms (required), reservations (LEFT)
     * - WHERE: check-in ID = ?, status = 'Active'
     * - LIMIT 1
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $checkInId The check-in ID to retrieve
     * @return array|false Detailed check-in record or false if not found
     */
    public function checkoutDetail($checkInId)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, g.full_name AS guest_name, g.id AS guest_id,
                    rm.room_number, rm.id AS room_id, rm.price_per_night,
                    r.reservation_number, r.check_in_date, r.check_out_date, r.id AS reservation_id
             FROM check_ins c
             JOIN guests g ON c.guest_id=g.id
             JOIN rooms rm ON c.room_id=rm.id
             LEFT JOIN reservations r ON c.reservation_id=r.id
             WHERE c.id=? AND c.status='Active' LIMIT 1"
        );
        $stmt->execute([$checkInId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mark a check-in as checked out and record the actual check-out time.
     * 
     * SQL Structure:
     * - UPDATE check_ins SET status='Checked Out', actual_check_out=NOW()
     * - WHERE id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $checkInId The check-in ID to update
     */
    public function markCheckedOut($checkInId)
    {
        $this->db->prepare("UPDATE check_ins SET status='Checked Out', actual_check_out=NOW() WHERE id=?")
            ->execute([$checkInId]);
    }

    /**
     * Calculate the total extra charges for a check-in.
     * 
     * SQL Structure:
     * - SELECT: COALESCE(SUM(amount), 0)
     * - FROM: extra_charges
     * - WHERE: check_in_id = ?
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $checkInId The check-in ID
     * @return float Total extra charges amount
     */
    public function extraChargesTotal($checkInId)
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM extra_charges WHERE check_in_id=?");
        $stmt->execute([$checkInId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Add an extra charge to a check-in record.
     * 
     * SQL Structure:
     * - INSERT INTO extra_charges (check_in_id, charge_type, description, amount)
     * - VALUES (?, ?, ?, ?)
     * 
     * Security: Prepared statement with parameterized query
     * 
     * @param int $checkInId The check-in ID
     * @param string $type Type of charge (e.g., 'minibar', 'room_service')
     * @param string $desc Description of the charge
     * @param float $amount Charge amount
     */
    public function addExtraCharge($checkInId, $type, $desc, $amount)
    {
        $stmt = $this->db->prepare("INSERT INTO extra_charges (check_in_id, charge_type, description, amount) VALUES (?,?,?,?)");
        $stmt->execute([$checkInId, $type, $desc, $amount]);
    }
}
