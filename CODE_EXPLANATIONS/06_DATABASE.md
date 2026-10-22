# DATABASE EXPLAINED
## What Each Table Does

---

## 1. users - Staff Accounts

**Purpose:** Store staff login information

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| username | VARCHAR(50) | Login name |
| password | VARCHAR(255) | Encrypted password |
| full_name | VARCHAR(100) | Staff name |
| email | VARCHAR(100) | Email address |
| phone | VARCHAR(20) | Phone number |
| role | ENUM | Job title |
| status | ENUM | Active/Inactive |
| created_at | TIMESTAMP | When created |
| updated_at | TIMESTAMP | When changed |

**Roles:**
- Administrator
- Receptionist
- Manager
- Housekeeping Staff
- Accountant

**Example Data:**
```sql
INSERT INTO users (username, password, full_name, email, role, status)
VALUES ('admin', '$2y$10$...', 'System Administrator', 'admin@hotel.com', 'Administrator', 'Active');
```

**Lines:** 30

---

## 2. room_types - Room Categories

**Purpose:** Define room types

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| type_name | VARCHAR(50) | Category name |
| description | TEXT | What's included |
| price_per_night | DECIMAL | Cost per night |
| capacity | INT | Max guests |
| created_at | TIMESTAMP | When created |

**Example Data:**
```sql
INSERT INTO room_types (type_name, description, price_per_night, capacity)
VALUES ('VIP Room', 'Luxury VIP suite with premium services', 100.00, 5);
```

**Lines:** 43

---

## 3. rooms - Individual Rooms

**Purpose:** Store room information

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| room_number | VARCHAR(10) | Room number |
| room_type_id | INT | Links to room_types |
| floor | INT | Which floor |
| capacity | INT | Max guests |
| price_per_night | DECIMAL | Cost per night |
| description | TEXT | Room details |
| status | ENUM | Room status |
| image | VARCHAR(255) | Photo filename |
| created_at | TIMESTAMP | When created |

**Status Values:**
- Available
- Reserved
- Occupied
- Cleaning
- Maintenance

**Example Data:**
```sql
INSERT INTO rooms (room_number, room_type_id, floor, capacity, price_per_night, status)
VALUES ('101', 1, 1, 2, 50.00, 'Available');
```

**Lines:** 61

---

## 4. guests - Customer Profiles

**Purpose:** Store guest information

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| full_name | VARCHAR(100) | Guest name |
| gender | ENUM | Male/Female/Other |
| date_of_birth | DATE | Birthday |
| nationality | VARCHAR(50) | Country |
| phone | VARCHAR(20) | Phone number |
| email | VARCHAR(100) | Email address |
| address | TEXT | Home address |
| identification_number | VARCHAR(50) | Passport/ID |
| created_at | TIMESTAMP | When created |

**Example Data:**
```sql
INSERT INTO guests (full_name, gender, phone, email, identification_number)
VALUES ('John Smith', 'Male', '1234567890', 'john@email.com', 'US123456');
```

**Lines:** 78

---

## 5. reservations - Bookings

**Purpose:** Store reservation information

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| reservation_number | VARCHAR(20) | Booking code |
| guest_id | INT | Links to guests |
| room_id | INT | Links to rooms |
| check_in_date | DATE | Arrival date |
| check_out_date | DATE | Departure date |
| number_of_guests | INT | How many people |
| status | ENUM | Booking status |
| special_requests | TEXT | Special notes |
| total_amount | DECIMAL | Total cost |
| created_by | INT | Staff who created |
| created_at | TIMESTAMP | When created |

**Status Values:**
- Pending
- Confirmed
- Cancelled
- Completed

**Example Data:**
```sql
INSERT INTO reservations (reservation_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, status, total_amount)
VALUES ('RES-20260909-1234', 1, 1, '2026-09-15', '2026-09-18', 2, 'Pending', 150.00);
```

**Lines:** 98

---

## 6. check_ins - Physical Check-ins

**Purpose:** Track actual guest stays

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| reservation_id | INT | Links to reservations |
| guest_id | INT | Links to guests |
| room_id | INT | Links to rooms |
| check_in_time | DATETIME | Actual arrival |
| expected_check_out | DATE | When they should leave |
| actual_check_out | DATETIME | When they actually left |
| status | ENUM | Active/Checked Out |
| created_by | INT | Staff who checked in |
| created_at | TIMESTAMP | When processed |

**Status Values:**
- Active
- Checked Out

**Lines:** 115

---

## 7. payments - Transactions

**Purpose:** Store payment records

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| reservation_id | INT | Links to reservations |
| check_in_id | INT | Links to check_ins |
| amount | DECIMAL | Amount paid |
| payment_method | ENUM | How they paid |
| transaction_id | VARCHAR(100) | Bank reference |
| payment_date | DATETIME | When paid |
| invoice_number | VARCHAR(20) | Receipt number |

**Payment Methods:**
- Cash
- Credit/Debit Card
- QR Payment
- Bank Transfer

**Lines:** 130

---

## 8. extra_charges - Additional Fees

**Purpose:** Track extra charges

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| check_in_id | INT | Links to check_ins |
| charge_type | ENUM | Type of charge |
| description | TEXT | What was charged |
| amount | DECIMAL | How much |
| created_at | TIMESTAMP | When added |

**Charge Types:**
- Room Service
- Laundry
- Restaurant
- Mini Bar
- Damage Fee
- Other

**Lines:** 143

---

## 9. contacts - Contact Form

**Purpose:** Store contact form submissions

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| full_name | VARCHAR(100) | Sender name |
| email | VARCHAR(100) | Sender email |
| phone | VARCHAR(20) | Sender phone |
| subject | VARCHAR(200) | Message subject |
| message | TEXT | Message content |
| is_read | TINYINT | 0=unread, 1=read |
| created_at | TIMESTAMP | When sent |

**Lines:** 143

---

## 10. system_settings - Hotel Config

**Purpose:** Store hotel configuration

**Columns:**
| Column | Type | Purpose |
|--------|------|---------|
| id | INT | Unique number |
| setting_key | VARCHAR(50) | Setting name |
| setting_value | TEXT | Setting value |
| updated_at | TIMESTAMP | When changed |

**Settings:**
- hotel_name
- hotel_address
- hotel_phone
- hotel_email
- currency
- tax_rate

**Example Data:**
```sql
INSERT INTO system_settings (setting_key, setting_value)
VALUES ('hotel_name', 'Arizu Arimato');
```

**Lines:** 203

---

## SUMMARY

| Table | Purpose | Lines |
|-------|---------|-------|
| users | Staff accounts | 30 |
| room_types | Room categories | 43 |
| rooms | Individual rooms | 61 |
| guests | Guest profiles | 78 |
| reservations | Bookings | 98 |
| check_ins | Physical stays | 115 |
| payments | Transactions | 130 |
| extra_charges | Additional fees | 143 |
| contacts | Contact form | 143 |
| system_settings | Hotel config | 203 |
