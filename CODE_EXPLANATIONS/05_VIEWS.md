# VIEWS EXPLAINED
## What Each View Does

---

## Layout Files

### layout/header.php
**Purpose:** Sidebar navigation and top bar

**What It Shows:**
- Hotel logo (HMS)
- User info (Welcome, Role)
- Navigation menu links
- Logout button

**Navigation Links:**
- Dashboard
- Users (Admin only)
- Room Types
- Rooms
- Guests
- Reservations
- Check-in
- Check-out
- Billing
- Notifications
- Housekeeping (Staff only)
- Maintenance (Staff only)
- Reports
- Settings (Admin only)
- Logout

**Lines:** 140

---

### layout/footer.php
**Purpose:** Close HTML and load JavaScript

**What It Does:**
- Closes main content div
- Loads Bootstrap JS
- Sidebar toggle function
- Auto-hide alerts
- Table row highlight
- Print receipt function
- Export to CSV function

**Lines:** 126

---

## Page Views

### login/index.php
**Purpose:** Login form

**What It Shows:**
- Username field
- Password field
- Login button
- Error message (if any)

**Lines:** 67

---

### setup/index.php
**Purpose:** Database installer

**What It Shows:**
- Install button
- Success/error message

**Lines:** 50

---

### dashboard/index.php
**Purpose:** Main admin page

**What It Shows:**
- 4 stat cards (Total Rooms, Occupancy, Check-ins, Revenue)
- Revenue chart (last 7 days)
- Room type distribution chart
- Today's arrivals table
- Today's departures table
- Room status board

**Lines:** 136

---

### rooms/index.php
**Purpose:** Room management

**What It Shows:**
- View toggle (List/Board)
- Add Room button
- Filter form (Status, Floor, Type)
- Room table (Room, Type, Floor, Capacity, Price, Status, Actions)
- Room board (colored tiles)
- Add/Edit modals

**Lines:** 169

---

### room_types/index.php
**Purpose:** Room type management

**What It Shows:**
- Add Room Type button
- Room type table (Name, Price, Capacity, Rooms, Actions)
- Add/Edit modals

**Lines:** 120

---

### guests/index.php
**Purpose:** Guest management

**What It Shows:**
- Add Guest button
- Guest table (Name, Gender, Phone, Email, ID, Actions)
- View modal (guest details + stay history)
- Add/Edit modals

**Lines:** 150

---

### reservations/index.php
**Purpose:** Reservation management

**What It Shows:**
- Add Reservation button
- Filter by status
- Reservation table (Number, Guest, Room, Dates, Status, Actions)
- Add/Edit modals

**Lines:** 160

---

### checkin/index.php
**Purpose:** Check-in page

**What It Shows:**
- Walk-in Check-in button
- Reservations ready for check-in table
- Currently checked-in guests table
- Walk-in modal

**Lines:** 82

---

### checkout/index.php
**Purpose:** Check-out page

**What It Shows:**
- Active stays with bill breakdown
- Extra charges section
- Payment modal
- Receipt modal

**Lines:** 150

---

### billing/index.php
**Purpose:** Payment history

**What It Shows:**
- Search and filter form
- Payment table (Invoice, Guest, Room, Amount, Method, Date, Actions)
- Receipt modal
- Export to CSV button

**Lines:** 130

---

### reports/index.php
**Purpose:** Revenue reports

**What It Shows:**
- Date range filter
- Metrics cards (Occupancy, ADR, RevPAR, Revenue)
- Revenue chart (6 months)
- Room type distribution chart
- Top 5 guests table

**Lines:** 120

---

### housekeeping/index.php
**Purpose:** Room cleaning

**What It Shows:**
- Rooms with status "Cleaning"
- Mark as Available button

**Lines:** 60

---

### maintenance/index.php
**Purpose:** Room maintenance

**What It Shows:**
- All rooms with maintenance toggle
- Set Maintenance/Available buttons

**Lines:** 60

---

### users/index.php
**Purpose:** Staff user management

**What It Shows:**
- Add User button
- User table (Name, Username, Role, Status, Actions)
- Add/Edit modals

**Lines:** 120

---

### settings/index.php
**Purpose:** Hotel settings

**What It Shows:**
- Hotel info form (Name, Address, Phone, Email)
- Currency and tax rate
- Backup database button
- Restore database button

**Lines:** 100

---

### notifications/index.php
**Purpose:** Activity alerts

**What It Shows:**
- Today's check-ins
- Today's check-outs
- Rooms needing cleaning
- Rooms under maintenance
- Pending reservations
- Today's revenue

**Lines:** 80

---

## SUMMARY

| View | Purpose | Lines |
|------|---------|-------|
| layout/header.php | Sidebar + nav | 140 |
| layout/footer.php | Footer + JS | 126 |
| login/index.php | Login form | 67 |
| setup/index.php | Database installer | 50 |
| dashboard/index.php | Main page | 136 |
| rooms/index.php | Room management | 169 |
| room_types/index.php | Room type management | 120 |
| guests/index.php | Guest management | 150 |
| reservations/index.php | Reservation management | 160 |
| checkin/index.php | Check-in page | 82 |
| checkout/index.php | Check-out page | 150 |
| billing/index.php | Payment history | 130 |
| reports/index.php | Revenue reports | 120 |
| housekeeping/index.php | Room cleaning | 60 |
| maintenance/index.php | Room maintenance | 60 |
| users/index.php | Staff management | 120 |
| settings/index.php | Hotel settings | 100 |
| notifications/index.php | Activity alerts | 80 |
