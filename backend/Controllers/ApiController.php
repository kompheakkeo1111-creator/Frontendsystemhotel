<?php
namespace App\Controllers;

use App\Core\Controller;
use PDO;

/**
 * ApiController - Public-facing REST API Controller
 * 
 * Handles all public-facing API endpoints for the hotel's frontend website.
 * This controller provides room listings, availability checks, reservations,
 * contact form submissions, and settings retrieval without requiring authentication.
 * 
 * Access: Public (no authentication required)
 * 
 * Available Actions:
 *   - roomsAction()         : GET  - List all room types with availability counts
 *   - viewAction()          : GET  - View a single room type with its rooms
 *   - availableRoomsAction(): GET  - Search rooms available for given dates
 *   - contactAction()       : POST - Submit a contact form message
 *   - reservationAction()   : POST - Create a new guest reservation
 *   - settingsAction()      : GET  - Retrieve public hotel settings
 *   - addRoomAction()       : POST - Create a new room type and optionally a room
 *   - editRoomAction()      : POST - Update an existing room type
 *   - deleteRoomAction()    : POST - Delete a room type (only if no rooms reference it)
 */
class ApiController extends Controller
{
    /** @var bool No authentication required for this controller */
    public $authRequired = false;

    /** @var PDO Database connection instance */
    private $db;

    /**
     * Constructor - Initialize database connection.
     * No authentication is needed as this is a public API.
     */
    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * GET ?r=api/rooms
     * 
     * Returns all room types with their available rooms count.
     * Used by the frontend to display room listings.
     * 
     * Data Flow:
     *   Input:   None (no parameters required)
     *   Process: Queries room_types joined with rooms, aggregates total/available counts
     *   Output:  JSON response with array of room type objects
     */
    public function roomsAction()
    {
        // Query all room types with aggregated room counts
        $stmt = $this->db->query("
            SELECT rt.id, rt.type_name, rt.description, rt.price_per_night, rt.capacity,
                   COUNT(r.id) as total_rooms,
                   SUM(CASE WHEN r.status = 'Available' THEN 1 ELSE 0 END) as available_rooms
            FROM room_types rt
            LEFT JOIN rooms r ON r.room_type_id = rt.id
            GROUP BY rt.id
            ORDER BY rt.price_per_night ASC
        ");
        $roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich each room type with image and description data
        foreach ($roomTypes as &$type) {
            // Cast numeric fields to proper types for JSON serialization
            $type['price_per_night'] = (float) $type['price_per_night'];
            $type['total_rooms'] = (int) $type['total_rooms'];
            $type['available_rooms'] = (int) $type['available_rooms'];
            // Map room type name to a frontend image path
            $type['image'] = $this->getRoomTypeImage($type['type_name']);
            // Provide a default description if none exists in the database
            if (empty($type['description'])) {
                $type['description'] = $this->getRoomDescription($type['type_name'], $type['capacity']);
            }
        }

        // Return JSON response with room type data
        $this->jsonResponse(['success' => true, 'data' => $roomTypes]);
    }

    /**
     * GET ?r=api/rooms/view&id=X
     * 
     * Returns a single room type with its associated rooms.
     * Used by the frontend to display room type details.
     * 
     * Data Flow:
     *   Input:   GET parameter 'id' (room type ID)
     *   Process: Validates ID, fetches room type, fetches associated rooms
     *   Output:  JSON response with room type object including rooms array
     */
    public function viewAction()
    {
        // Validate room type ID parameter
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid room type ID'], 400);
        }

        // Fetch room type by ID using prepared statement to prevent SQL injection
        $stmt = $this->db->prepare("SELECT * FROM room_types WHERE id = ?");
        $stmt->execute([$id]);
        $roomType = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return 404 if room type not found
        if (!$roomType) {
            $this->jsonResponse(['success' => false, 'message' => 'Room type not found'], 404);
        }

        // Cast price to float and attach image
        $roomType['price_per_night'] = (float) $roomType['price_per_night'];
        $roomType['image'] = $this->getRoomTypeImage($roomType['type_name']);

        // Fetch all rooms belonging to this room type
        $stmt = $this->db->prepare("SELECT id, room_number, floor, capacity, price_per_night, status FROM rooms WHERE room_type_id = ? ORDER BY room_number");
        $stmt->execute([$id]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cast room prices to float
        foreach ($rooms as &$room) {
            $room['price_per_night'] = (float) $room['price_per_night'];
        }

        // Attach rooms array to room type response
        $roomType['rooms'] = $rooms;
        $this->jsonResponse(['success' => true, 'data' => $roomType]);
    }

    /**
     * GET ?r=api/available-rooms&check_in=YYYY-MM-DD&check_out=YYYY-MM-DD&guests=N
     * 
     * Returns rooms available for the given date range and guest count.
     * Checks against existing reservations and active check-ins.
     * 
     * Data Flow:
     *   Input:   GET parameters: check_in, check_out (dates), guests (count)
     *   Process: Validates dates, queries rooms with subqueries for reservations/check-ins
     *   Output:  JSON response with array of available rooms
     */
    public function availableRoomsAction()
    {
        // Extract and sanitize input parameters
        $checkIn = $_GET['check_in'] ?? '';
        $checkOut = $_GET['check_out'] ?? '';
        $guests = (int) ($_GET['guests'] ?? 1);

        // Validate required date parameters
        if (empty($checkIn) || empty($checkOut)) {
            $this->jsonResponse(['success' => false, 'message' => 'check_in and check_out dates are required'], 400);
        }

        // Validate check-out is after check-in
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            $this->jsonResponse(['success' => false, 'message' => 'check_out must be after check_in'], 400);
        }

        // Query rooms with subqueries to check for overlapping reservations and active check-ins
        $stmt = $this->db->prepare("
            SELECT r.id, r.room_number, r.floor, r.capacity, r.price_per_night, r.description, r.image,
                   rt.id as room_type_id, rt.type_name,
                   (SELECT COUNT(*) FROM reservations res
                    WHERE res.room_id = r.id
                    AND res.status IN ('Pending','Confirmed')
                    AND res.check_in_date < ? AND res.check_out_date > ?
                   ) as reserved,
                   (SELECT COUNT(*) FROM check_ins ci
                    WHERE ci.room_id = r.id
                    AND ci.status = 'Active'
                    AND ci.expected_check_out > ?
                   ) as occupied
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.status NOT IN ('Maintenance')
            AND r.capacity >= ?
            ORDER BY r.price_per_night ASC
        ");
        // Bind parameters: checkOut for reservation overlap, checkIn for reservation overlap,
        // checkIn for active check-ins, guests for capacity filter
        $stmt->execute([$checkOut, $checkIn, $checkIn, $guests]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter to only rooms with no reservations and no active check-ins
        $available = [];
        foreach ($rooms as $room) {
            if ($room['reserved'] == 0 && $room['occupied'] == 0) {
                $room['price_per_night'] = (float) $room['price_per_night'];
                // Remove internal count fields from response
                unset($room['reserved'], $room['occupied']);
                $available[] = $room;
            }
        }

        // Return available rooms with search parameters echoed back
        $this->jsonResponse([
            'success' => true,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'data' => $available
        ]);
    }

    /**
     * POST ?r=api/contact
     * Body: { full_name, email, phone, subject, message }
     * 
     * Submits a contact form message and stores it in the database.
     * 
     * Data Flow:
     *   Input:   JSON or POST body with contact form fields
     *   Process: Validates required fields, validates email format, inserts into contacts table
     *   Output:  JSON success/failure response
     */
    public function contactAction()
    {
        // Enforce POST method only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        }

        // Parse input from JSON body or POST data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Extract and trim input fields
        $fullName = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');

        // Validate required fields
        if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
            $this->jsonResponse(['success' => false, 'message' => 'full_name, email, subject, and message are required'], 400);
        }

        // Validate email format using PHP's filter
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid email address'], 400);
        }

        // Insert contact message into database
        $stmt = $this->db->prepare("INSERT INTO contacts (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $phone, $subject, $message]);

        $this->jsonResponse(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
    }

    /**
     * POST ?r=api/reservation
     * Body: { room_type_id, check_in_date, check_out_date, full_name, email, phone, guests, special_requests }
     * 
     * Creates a new guest reservation, automatically finding an available room.
     * 
     * Data Flow:
     *   Input:   JSON or POST body with reservation details
     *   Process: Validates fields, finds available room, creates/reuses guest, creates reservation
     *   Output:  JSON response with reservation confirmation details
     */
    public function reservationAction()
    {
        // Enforce POST method only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        }

        // Parse input from JSON body or POST data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // Extract and sanitize reservation fields
        $roomTypeId = (int) ($input['room_type_id'] ?? 0);
        $checkIn = trim($input['check_in_date'] ?? '');
        $checkOut = trim($input['check_out_date'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $numGuests = (int) ($input['guests'] ?? 1);
        $specialRequests = trim($input['special_requests'] ?? '');

        // Validate all required fields are present
        if ($roomTypeId <= 0 || empty($checkIn) || empty($checkOut) || empty($fullName) || empty($email) || empty($phone)) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Validate check-out date is after check-in date
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            $this->jsonResponse(['success' => false, 'message' => 'check_out must be after check_in'], 400);
        }

        // Find an available room that matches the room type, capacity, and date requirements
        // Subqueries check for overlapping reservations and active check-ins
        $stmt = $this->db->prepare("
            SELECT r.id, r.room_number, r.price_per_night
            FROM rooms r
            WHERE r.room_type_id = ? AND r.status NOT IN ('Maintenance','Cleaning')
            AND r.capacity >= ?
            AND NOT EXISTS (
                SELECT 1 FROM reservations res
                WHERE res.room_id = r.id AND res.status IN ('Pending','Confirmed')
                AND res.check_in_date < ? AND res.check_out_date > ?
            )
            AND NOT EXISTS (
                SELECT 1 FROM check_ins ci
                WHERE ci.room_id = r.id AND ci.status = 'Active'
                AND ci.expected_check_out > ?
            )
            LIMIT 1
        ");
        $stmt->execute([$roomTypeId, $numGuests, $checkOut, $checkIn, $checkIn]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return error if no rooms are available
        if (!$room) {
            $this->jsonResponse(['success' => false, 'message' => 'No rooms available for the selected dates'], 400);
        }

        // Find existing guest by email or phone to avoid duplicates
        $stmt = $this->db->prepare("SELECT id, full_name FROM guests WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$email, $phone]);
        $guest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guest) {
            // Reuse existing guest record
            $guestId = $guest['id'];
            // Update guest name if it has changed
            if ($guest['full_name'] !== $fullName) {
                $stmt = $this->db->prepare("UPDATE guests SET full_name = ? WHERE id = ?");
                $stmt->execute([$fullName, $guestId]);
            }
        } else {
            // Create new guest record with a temporary identification number
            $stmt = $this->db->prepare("INSERT INTO guests (full_name, email, phone, identification_number) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $phone, 'PENDING-' . rand(1000, 9999)]);
            $guestId = $this->db->lastInsertId();
        }

        // Calculate total cost: nights * nightly rate
        $nights = max(1, (int) ((strtotime($checkOut) - strtotime($checkIn)) / 86400));
        $total = round($nights * (float) $room['price_per_night'], 2);

        // Generate unique reservation number with date prefix and random suffix
        $resNumber = 'RES-' . date('Ymd') . '-' . rand(1000, 9999);

        // Insert reservation record with 'Pending' status
        $stmt = $this->db->prepare("
            INSERT INTO reservations (reservation_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, status, special_requests, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
        ");
        $stmt->execute([$resNumber, $guestId, $room['id'], $checkIn, $checkOut, $numGuests, $specialRequests, $total]);

        // Return reservation confirmation details
        $this->jsonResponse([
            'success' => true,
            'message' => 'Reservation created successfully!',
            'data' => [
                'reservation_number' => $resNumber,
                'room_number' => $room['room_number'] ?? '',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'total' => $total
            ]
        ]);
    }

    /**
     * GET ?r=api/settings
     * 
     * Returns public hotel settings for the frontend.
     * 
     * Data Flow:
     *   Input:   None
     *   Process: Retrieves system settings from global helper function
     *   Output:  JSON response with public hotel configuration
     */
    public function settingsAction()
    {
        // Retrieve hotel settings from the system configuration
        $settings = getSystemSettings();
        $this->jsonResponse([
            'success' => true,
            'data' => [
                'hotel_name' => $settings['hotel_name'] ?? 'Arizu Arimato',
                'hotel_address' => $settings['hotel_address'] ?? 'Siem Reap, Cambodia',
                'hotel_phone' => $settings['hotel_phone'] ?? '+855 12 345 678',
                'hotel_email' => $settings['hotel_email'] ?? 'info@arizuhotel.com',
                'currency' => $settings['currency'] ?? 'USD',
            ]
        ]);
    }

    /**
     * POST ?r=api/addRoom
     * Body: { type_name, description, price_per_night, capacity, room_number, floor }
     * 
     * Creates a new room type and optionally an individual room.
     * If the room type already exists, only a new room is added.
     * 
     * Data Flow:
     *   Input:   JSON or POST body with room type/room details
     *   Process: Validates fields, checks for existing type, creates type/room if needed
     *   Output:  JSON success response with type ID and details
     */
    public function addRoomAction()
    {
        // Enforce POST method only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        }

        // Parse input from JSON body or POST data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        // Extract and sanitize room fields
        $typeName = trim($input['type_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price = (float) ($input['price_per_night'] ?? 0);
        $capacity = (int) ($input['capacity'] ?? 2);
        $roomNumber = trim($input['room_number'] ?? '');
        $floor = (int) ($input['floor'] ?? 1);

        // Validate required fields: type name and positive price
        if (empty($typeName) || $price <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Room type name and price are required'], 400);
        }

        // Check if room type already exists in the database
        $stmt = $this->db->prepare("SELECT id FROM room_types WHERE type_name = ?");
        $stmt->execute([$typeName]);
        $roomType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($roomType) {
            // Reuse existing room type
            $typeId = $roomType['id'];
        } else {
            // Create new room type with description (or default if empty)
            $stmt = $this->db->prepare("INSERT INTO room_types (type_name, description, price_per_night, capacity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$typeName, $description ?: $this->getRoomDescription($typeName, $capacity), $price, $capacity]);
            $typeId = $this->db->lastInsertId();
        }

        // Add a room instance if room_number is provided
        if (!empty($roomNumber)) {
            // Check for duplicate room number
            $stmt = $this->db->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $stmt->execute([$roomNumber]);
            if ($stmt->fetch()) {
                $this->jsonResponse(['success' => false, 'message' => "Room number $roomNumber already exists"], 400);
            }
            // Insert new room with default 'Available' status
            $stmt = $this->db->prepare("INSERT INTO rooms (room_number, room_type_id, floor, capacity, price_per_night, status) VALUES (?, ?, ?, ?, ?, 'Available')");
            $stmt->execute([$roomNumber, $typeId, $floor, $capacity, $price]);
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Room added successfully!',
            'data' => ['type_id' => $typeId, 'type_name' => $typeName, 'price' => $price]
        ]);
    }

    /**
     * POST ?r=api/editRoom
     * Body: { id, type_name, description, price_per_night, capacity }
     * 
     * Updates an existing room type and cascades changes to all rooms of that type.
     * 
     * Data Flow:
     *   Input:   JSON or POST body with room type ID and updated fields
     *   Process: Validates fields, updates room type, cascades price/capacity to rooms
     *   Output:  JSON success/failure response
     */
    public function editRoomAction()
    {
        // Enforce POST method only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        }

        // Parse input from JSON body or POST data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        // Extract and sanitize fields
        $id = (int) ($input['id'] ?? 0);
        $typeName = trim($input['type_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $price = (float) ($input['price_per_night'] ?? 0);
        $capacity = (int) ($input['capacity'] ?? 2);

        // Validate required fields
        if ($id <= 0 || empty($typeName) || $price <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        // Update the room type record
        $stmt = $this->db->prepare("UPDATE room_types SET type_name=?, description=?, price_per_night=?, capacity=? WHERE id=?");
        $stmt->execute([$typeName, $description, $price, $capacity, $id]);

        // Cascade price and capacity changes to all rooms of this type
        $stmt = $this->db->prepare("UPDATE rooms SET price_per_night=?, capacity=? WHERE room_type_id=?");
        $stmt->execute([$price, $capacity, $id]);

        $this->jsonResponse(['success' => true, 'message' => 'Room type updated!']);
    }

    /**
     * POST ?r=api/deleteRoom
     * Body: { id }
     * 
     * Deletes a room type, but only if no rooms are currently referencing it.
     * 
     * Data Flow:
     *   Input:   JSON body with room type ID
     *   Process: Validates ID, checks for dependent rooms, deletes if safe
     *   Output:  JSON success/failure response
     */
    public function deleteRoomAction()
    {
        // Enforce POST method only
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'POST required'], 405);
        }

        // Parse input from JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int) ($input['id'] ?? 0);

        // Validate room type ID
        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
        }

        // Check if any rooms reference this room type (foreign key integrity check)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();

        // Prevent deletion if rooms still reference this type
        if ($count > 0) {
            $this->jsonResponse(['success' => false, 'message' => "Cannot delete: $count room(s) still use this type. Delete rooms first."], 400);
        }

        // Delete the room type record
        $stmt = $this->db->prepare("DELETE FROM room_types WHERE id = ?");
        $stmt->execute([$id]);

        $this->jsonResponse(['success' => true, 'message' => 'Room type deleted!']);
    }

    /**
     * Maps room type names to frontend image paths.
     * Uses keyword matching on the type name to determine the appropriate image.
     * 
     * @param string $typeName The room type name
     * @return string Path to the room type image
     */
    private function getRoomTypeImage($typeName)
    {
        $lower = strtolower($typeName);
        if (strpos($lower, 'vip') !== false || strpos($lower, 'presidential') !== false) {
            return 'images/room-vip.jpg';
        }
        if (strpos($lower, 'suite') !== false || strpos($lower, 'family') !== false) {
            return 'images/room-suite.jpg';
        }
        if (strpos($lower, 'premier') !== false || strpos($lower, 'deluxe') !== false) {
            return 'images/room-deluxe.jpg';
        }
        return 'images/room-deluxe.jpg';
    }

    /**
     * Generates a default description for room types based on name keywords.
     * Used when no description is provided in the database.
     * 
     * @param string $typeName The room type name
     * @param int    $capacity The room capacity
     * @return string Default description text
     */
    private function getRoomDescription($typeName, $capacity)
    {
        $lower = strtolower($typeName);
        if (strpos($lower, 'vip') !== false) {
            return 'Full luxury apartment with kitchen, dining area, living room, and separate bedroom. Perfect for families.';
        }
        if (strpos($lower, 'family') !== false) {
            return 'Spacious family room with multiple beds, garden views, and en-suite bathroom. Ideal for families.';
        }
        if (strpos($lower, 'suite') !== false) {
            return 'Luxurious suite with handcrafted wooden furniture, en-suite bathroom with bathtub, and elegant decor.';
        }
        if (strpos($lower, 'premier') !== false) {
            return 'Premium deluxe room with king bed, private balcony, city views, and luxurious bathroom with rain shower.';
        }
        if (strpos($lower, 'deluxe') !== false) {
            return 'Elegant room featuring a comfortable bed, modern amenities, and colonial-style decor.';
        }
        if (strpos($lower, 'sweet') !== false) {
            return 'Cozy and romantic room perfect for couples, with queen bed and beautiful interior design.';
        }
        return 'Comfortable room with modern amenities and elegant colonial-style decor.';
    }
}
