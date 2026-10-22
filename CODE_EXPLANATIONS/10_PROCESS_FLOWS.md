# PROCESS FLOWS EXPLAINED
## How Processes Work

---

## Flow 1: Guest Books Room (Frontend)

```
1. Guest opens rooms.html
        ↓
2. JavaScript calls apiGet('rooms')
        ↓
3. API returns room types
        ↓
4. Guest selects dates, clicks Search
        ↓
5. JavaScript calls apiGet('availableRooms&...')
        ↓
6. API checks availability
        ↓
7. Guest clicks "Book Now"
        ↓
8. Guest fills in details
        ↓
9. User submits form
        ↓
10. JavaScript calls apiPost('reservation', data)
        ↓
11. API creates reservation
        ↓
12. Guest sees confirmation
```

**Time:** 2-3 minutes

---

## Flow 2: Staff Check-In

```
1. Staff logs in
        ↓
2. Goes to Check-in page
        ↓
3. Sees ready reservations
        ↓
4. Clicks "Check In"
        ↓
5. Backend creates check_in record
        ↓
6. Room status changes to "Occupied"
        ↓
7. Shows success message
```

**Time:** 30 seconds

---

## Flow 3: Staff Check-Out

```
1. Staff goes to Check-out page
        ↓
2. Sees active stays with bills
        ↓
3. Clicks "Payment & Check-out"
        ↓
4. Adds extra charges (if any)
        ↓
5. Selects payment method
        ↓
6. Backend processes payment
        ↓
7. Room status changes to "Cleaning"
        ↓
8. Receipt is generated
```

**Time:** 1-2 minutes

---

## Flow 4: Database Installation

```
1. User visits backend
        ↓
2. System detects no tables
        ↓
3. Redirects to setup page
        ↓
4. User clicks "Install Database"
        ↓
5. System creates all tables
        ↓
6. Default admin account created
        ↓
7. User can now login
```

**Time:** 1 minute

---

## Flow 5: Contact Form Submission

```
1. Guest opens contact.html
        ↓
2. Fills in form (name, email, message)
        ↓
3. Clicks "Send Message"
        ↓
4. JavaScript calls apiPost('contact', data)
        ↓
5. Backend saves message in database
        ↓
6. Guest sees "Thank You" message
```

**Time:** 30 seconds

---

## Flow 6: Room Cleaning

```
1. Guest checks out
        ↓
2. Room status changes to "Cleaning"
        ↓
3. Housekeeping staff goes to Housekeeping page
        ↓
4. Sees rooms needing cleaning
        ↓
5. Cleans room
        ↓
6. Clicks "Mark as Available"
        ↓
7. Room status changes to "Available"
```

**Time:** 30 minutes (actual cleaning)

---

## Flow 7: Adding New Room Type (Admin)

```
1. Admin logs in
        ↓
2. Goes to Room Types page
        ↓
3. Clicks "Add Room Type"
        ↓
4. Fills in details (name, price, capacity)
        ↓
5. Clicks Save
        ↓
6. Room type is created
        ↓
7. Admin can now add rooms of this type
```

**Time:** 1 minute

---

## Flow 8: Creating Reservation (Admin)

```
1. Admin logs in
        ↓
2. Goes to Reservations page
        ↓
3. Clicks "Add Reservation"
        ↓
4. Selects guest
        ↓
5. Selects room
        ↓
6. Enters dates
        ↓
7. Clicks Save
        ↓
8. Reservation is created
        ↓
9. Room status changes to "Reserved"
```

**Time:** 2 minutes

---

## SUMMARY

| Flow | Time | Steps |
|------|------|-------|
| Guest Books Room | 2-3 min | 12 steps |
| Staff Check-In | 30 sec | 7 steps |
| Staff Check-Out | 1-2 min | 8 steps |
| Database Install | 1 min | 7 steps |
| Contact Form | 30 sec | 6 steps |
| Room Cleaning | 30 min | 7 steps |
| Add Room Type | 1 min | 7 steps |
| Create Reservation | 2 min | 9 steps |
