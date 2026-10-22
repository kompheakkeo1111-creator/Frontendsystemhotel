# MODELS EXPLAINED
## What Each Model Does

---

## 1. Room.php

**Purpose:** Room database operations

**What It Does:**
```php
class Room extends Model {
    // Update room status
    public function setStatus($roomId, $status) {
        $this->db->prepare("UPDATE rooms SET status = ? WHERE id = ?")
            ->execute([$status, $roomId]);
    }
}
```

**Status Values:**
- Available
- Reserved
- Occupied
- Cleaning
- Maintenance

**Lines:** 30

---

## 2. RoomType.php

**Purpose:** Room type database operations

**What It Does:**
```php
class RoomType extends Model {
    // All functions inherited from Model
    // Uses $this->db for queries
}
```

**Lines:** 20

---

## 3. Guest.php

**Purpose:** Guest database operations

**What It Does:**
```php
class Guest extends Model {
    // Get all guests
    public function all() {
        return $this->db->query("SELECT * FROM guests")->fetchAll();
    }
    
    // Get single guest
    public function find($id) {
        return $this->db->prepare("SELECT * FROM guests WHERE id = ?")
            ->execute([$id])->fetch();
    }
}
```

**Lines:** 40

---

## 4. Reservation.php

**Purpose:** Reservation database operations

**What It Does:**
```php
class Reservation extends Model {
    // Check if room is available
    public function roomIsAvailable($roomId, $checkIn, $checkOut) {
        $sql = "SELECT COUNT(*) FROM reservations 
                WHERE room_id = ? 
                AND status != 'Cancelled'
                AND check_in_date <= ? 
                AND check_out_date >= ?";
        return $this->db->prepare($sql)->execute([$roomId, $checkOut, $checkIn])->fetchColumn() == 0;
    }
    
    // Get room price per night
    public function roomNightlyRate($roomId) {
        return $this->db->prepare("SELECT price_per_night FROM rooms WHERE id = ?")
            ->execute([$roomId])->fetchColumn();
    }
}
```

**Lines:** 60

---

## 5. CheckIn.php

**Purpose:** Check-in database operations

**What It Does:**
```php
class CheckIn extends Model {
    // Get reservations ready for check-in
    public function readyReservations() {
        $sql = "SELECT r.*, g.full_name, rm.room_number 
                FROM reservations r
                JOIN guests g ON r.guest_id = g.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.status IN ('Pending','Confirmed')
                AND r.room_id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM check_ins ci 
                    WHERE ci.reservation_id = r.id 
                    AND ci.status = 'Active'
                )";
        return $this->db->query($sql)->fetchAll();
    }
    
    // Get currently checked-in guests
    public function activeStays() {
        $sql = "SELECT ci.*, g.full_name, rm.room_number 
                FROM check_ins ci
                JOIN guests g ON ci.guest_id = g.id
                JOIN rooms rm ON ci.room_id = rm.id
                WHERE ci.status = 'Active'";
        return $this->db->query($sql)->fetchAll();
    }
    
    // Create check-in record
    public function insert($reservationId, $guestId, $roomId, $expectedOut, $userId) {
        $sql = "INSERT INTO check_ins (reservation_id, guest_id, room_id, check_in_time, expected_check_out, created_by)
                VALUES (?, ?, ?, NOW(), ?, ?)";
        $this->db->prepare($sql)->execute([$reservationId, $guestId, $roomId, $expectedOut, $userId]);
    }
    
    // Mark as checked out
    public function markCheckedOut($checkInId) {
        $this->db->prepare("UPDATE check_ins SET status = 'Checked Out', actual_check_out = NOW() WHERE id = ?")
            ->execute([$checkInId]);
    }
    
    // Get extra charges total
    public function extraChargesTotal($checkInId) {
        return $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM extra_charges WHERE check_in_id = ?")
            ->execute([$checkInId])->fetchColumn();
    }
    
    // Add extra charge
    public function addExtraCharge($checkInId, $type, $desc, $amount) {
        $this->db->prepare("INSERT INTO extra_charges (check_in_id, charge_type, description, amount) VALUES (?, ?, ?, ?)")
            ->execute([$checkInId, $type, $desc, $amount]);
    }
}
```

**Lines:** 100

---

## 6. User.php

**Purpose:** User database operations

**What It Does:**
```php
class User extends Model {
    // All functions inherited from Model
}
```

**Lines:** 20

---

## SUMMARY

| Model | Purpose | Lines |
|-------|---------|-------|
| Room | Room operations | 30 |
| RoomType | Room type operations | 20 |
| Guest | Guest operations | 40 |
| Reservation | Reservation operations | 60 |
| CheckIn | Check-in operations | 100 |
| User | User operations | 20 |
