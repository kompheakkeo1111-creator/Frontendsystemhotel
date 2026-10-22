# CONTROLLERS EXPLAINED
## What Each Controller Does

---

## 1. LoginController.php

**Purpose:** Handle staff login

**What It Does:**
- Shows login form (GET)
- Processes login (POST)
- Validates username/password
- Creates session
- Redirects to dashboard

**Code Flow:**
```
User enters: admin / admin123
                    ↓
System finds user in database
                    ↓
Verifies password hash
                    ↓
Creates session variables:
  - user_id
  - username
  - user_role
  - user_name
                    ↓
Redirects to dashboard
```

**Lines:** 50

---

## 2. LogoutController.php

**Purpose:** End user session

**What It Does:**
- Destroys session
- Redirects to login page

**Code:**
```php
session_destroy();
header('Location: index.php?r=login/index');
```

**Lines:** 20

---

## 3. DashboardController.php

**Purpose:** Main admin page with stats

**What It Shows:**
- Total rooms count
- Available rooms count
- Occupied rooms count
- Today's check-ins
- Today's revenue
- Revenue chart (last 7 days)
- Room type distribution chart
- Today's arrivals table
- Today's departures table
- Room status board

**Database Queries:**
```sql
SELECT COUNT(*) FROM rooms
SELECT COUNT(*) FROM rooms WHERE status='Available'
SELECT COUNT(*) FROM rooms WHERE status='Occupied'
SELECT COUNT(*) FROM check_ins WHERE DATE(check_in_time) = CURDATE()
SELECT SUM(amount) FROM payments WHERE DATE(payment_date) = CURDATE()
```

**Lines:** 150

---

## 4. RoomController.php

**Purpose:** Manage rooms (CRUD)

**Operations:**
- **List:** Show all rooms with filters
- **Add:** Create new room
- **Edit:** Update room details
- **Delete:** Remove room

**Form Fields:**
- Room Number
- Room Type (dropdown)
- Floor
- Capacity
- Price per Night
- Description
- Status

**Database Operations:**
```sql
SELECT r.*, rt.type_name FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id
INSERT INTO rooms (room_number, room_type_id, ...) VALUES (...)
UPDATE rooms SET ... WHERE id = ?
DELETE FROM rooms WHERE id = ?
```

**Lines:** 100

---

## 5. RoomTypeController.php

**Purpose:** Manage room types (CRUD)

**Operations:**
- **List:** Show all room types with room counts
- **Add:** Create new room type
- **Edit:** Update room type
- **Delete:** Remove room type

**Form Fields:**
- Type Name
- Description
- Price per Night
- Capacity

**Database Operations:**
```sql
SELECT rt.*, COUNT(r.id) as room_count FROM room_types rt LEFT JOIN rooms r ON rt.id=r.room_type_id
INSERT INTO room_types (type_name, ...) VALUES (...)
UPDATE room_types SET ... WHERE id = ?
DELETE FROM room_types WHERE id = ?
```

**Lines:** 80

---

## 6. GuestController.php

**Purpose:** Manage guest profiles

**Operations:**
- **List:** Show all guests
- **Add:** Create new guest
- **Edit:** Update guest details
- **Delete:** Remove guest
- **View:** Show guest details and stay history

**Form Fields:**
- Full Name
- Gender
- Date of Birth
- Nationality
- Phone
- Email
- Address
- ID Number

**Lines:** 100

---

## 7. ReservationController.php

**Purpose:** Manage bookings

**Operations:**
- **List:** Show all reservations with filters
- **Add:** Create new reservation
- **Edit:** Update reservation
- **Cancel:** Cancel reservation
- **Delete:** Remove reservation

**Form Fields:**
- Guest (dropdown)
- Room (dropdown)
- Check-in Date
- Check-out Date
- Number of Guests
- Special Requests
- Status

**Database Operations:**
```sql
-- Check room availability
SELECT * FROM reservations WHERE room_id = ? AND status != 'Cancelled'
AND check_in_date <= ? AND check_out_date >= ?

-- Create reservation
INSERT INTO reservations (reservation_number, guest_id, room_id, ...) VALUES (...)
```

**Lines:** 120

---

## 8. CheckInController.php

**Purpose:** Handle guest check-in

**What It Shows:**
- Reservations ready for check-in
- Currently checked-in guests
- Walk-in check-in modal

**Operations:**
- **Check-in:** Process reservation check-in
- **Walk-in:** Check-in without reservation

**Code Flow:**
```
Staff clicks "Check In"
                    ↓
System creates check_in record
                    ↓
Updates room status to "Occupied"
                    ↓
Shows success message
```

**Database Operations:**
```sql
-- Create check-in
INSERT INTO check_ins (reservation_id, guest_id, room_id, ...) VALUES (...)
-- Update room
UPDATE rooms SET status = 'Occupied' WHERE id = ?
```

**Lines:** 130

---

## 9. CheckOutController.php

**Purpose:** Handle guest check-out and payment

**What It Shows:**
- Currently checked-in guests
- Bill breakdown (room charge + extras + tax)
- Payment modal

**Operations:**
- **Add Charge:** Add extra charges (room service, laundry)
- **Checkout:** Process payment and check-out

**Bill Calculation:**
```
Room Charge = Nights × Price per Night
Extra Charges = Sum of all extra charges
Tax = (Room Charge + Extra Charges) × Tax Rate
Total = Room Charge + Extra Charges + Tax
```

**Code Flow:**
```
Staff clicks "Payment & Check-out"
                    ↓
System calculates total bill
                    ↓
Creates payment record
                    ↓
Updates check_in status to "Checked Out"
                    ↓
Updates reservation status to "Completed"
                    ↓
Updates room status to "Cleaning"
                    ↓
Shows receipt
```

**Lines:** 140

---

## 10. BillingController.php

**Purpose:** Payment history and receipts

**What It Shows:**
- List of all payments
- Search by guest name or invoice number
- Filter by payment method
- Date range filter
- Receipt modal
- Export to CSV

**Database Operations:**
```sql
SELECT p.*, g.full_name, r.room_number
FROM payments p
JOIN check_ins ci ON p.check_in_id = ci.id
JOIN guests g ON ci.guest_id = g.id
JOIN rooms ci ON ci.room_id = r.id
```

**Lines:** 100

---

## 11. ReportController.php

**Purpose:** Revenue and occupancy reports

**What It Shows:**
- Occupancy rate
- ADR (Average Daily Rate)
- RevPAR (Revenue Per Available Room)
- Period revenue
- Total revenue
- 6-month revenue chart
- Revenue by room type chart
- Top 5 guests table

**Lines:** 100

---

## 12. HousekeepingController.php

**Purpose:** Room cleaning management

**What It Shows:**
- Rooms with status "Cleaning"
- Button to mark room as "Available"

**Lines:** 50

---

## 13. MaintenanceController.php

**Purpose:** Room maintenance management

**What It Shows:**
- All rooms with maintenance toggle
- Button to set room as "Maintenance" or "Available"

**Lines:** 50

---

## 14. UserController.php

**Purpose:** Staff user management (admin only)

**Operations:**
- **List:** Show all staff users
- **Add:** Create new user
- **Edit:** Update user details
- **Delete:** Remove user

**Form Fields:**
- Username
- Password
- Full Name
- Email
- Phone
- Role (dropdown)
- Status

**Lines:** 100

---

## 15. SettingController.php

**Purpose:** Hotel settings and backup

**What It Shows:**
- Hotel name, address, phone, email
- Currency
- Tax rate
- Backup database
- Restore database

**Lines:** 100

---

## 16. NotificationController.php

**Purpose:** Activity alerts

**What It Shows:**
- Today's check-ins
- Today's check-outs
- Rooms needing cleaning
- Rooms under maintenance
- Pending reservations
- Today's revenue

**Lines:** 60

---

## 17. ApiController.php

**Purpose:** Public API for frontend website

**Endpoints:**
- GET /api/rooms - Get room types
- GET /api/availableRooms - Search available rooms
- POST /api/reservation - Create booking
- POST /api/contact - Submit contact form
- GET /api/settings - Get hotel settings

**Lines:** 200

---

## 18. SetupController.php

**Purpose:** First-time database installation

**What It Does:**
- Creates all database tables
- Inserts default admin user
- Inserts default settings
- Shows success message

**Lines:** 80

---

## SUMMARY

| Controller | Purpose | Lines |
|------------|---------|-------|
| LoginController | Staff login | 50 |
| LogoutController | End session | 20 |
| DashboardController | Main page | 150 |
| RoomController | Manage rooms | 100 |
| RoomTypeController | Manage room types | 80 |
| GuestController | Manage guests | 100 |
| ReservationController | Manage bookings | 120 |
| CheckInController | Check-in guests | 130 |
| CheckOutController | Check-out + payment | 140 |
| BillingController | Payment history | 100 |
| ReportController | Reports | 100 |
| HousekeepingController | Room cleaning | 50 |
| MaintenanceController | Room repairs | 50 |
| UserController | Staff accounts | 100 |
| SettingController | Hotel settings | 100 |
| NotificationController | Alerts | 60 |
| ApiController | Public API | 200 |
| SetupController | Database install | 80 |
