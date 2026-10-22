# 13. GRADING GUIDE - Web App Framework Project

## Overview

This guide explains all grading criteria for your hotel management system project. Follow each step to ensure maximum points.

---

## Step 1: System Design (10 Points)

### 1.1 ERD (Entity Relationship Diagram)

The ERD shows all database tables and their relationships.

#### Your Tables:

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    users     │     │  room_types │     │    rooms    │
├─────────────┤     ├─────────────┤     ├─────────────┤
│ id (PK)     │     │ id (PK)     │◄────│ room_type_id│
│ username    │     │ type_name   │     │ id (PK)     │
│ password    │     │ description │     │ room_number │
│ full_name   │     │ price/night │     │ floor       │
│ email       │     │ capacity    │     │ status      │
│ role        │     └─────────────┘     └─────────────┘
└─────────────┘                              │
       │                                     │
       │                                     ▼
       │                              ┌─────────────┐
       │                              │ reservations│
       │                              ├─────────────┤
       │                              │ id (PK)     │
       │                              │ guest_id    │
       │                              │ room_id ────│
       │                              │ check_in    │
       │                              │ check_out   │
       │                              │ total_amount│
       │                              │ status      │
       │                              └─────────────┘
       │                                     │
       │                                     ▼
       │                              ┌─────────────┐
       │                              │  check_ins  │
       │                              ├─────────────┤
       │                              │ id (PK)     │
       │                              │ reservation_id
       │                              │ guest_id    │
       │                              │ room_id ────│
       │                              │ check_in_time
       │                              │ status      │
       │                              └─────────────┘
       │                                     │
       │                                     ▼
       │                              ┌─────────────┐
       │                              │   payments  │
       │                              ├─────────────┤
       │                              │ id (PK)     │
       │                              │ reservation_id
       │                              │ check_in_id │
       │                              │ amount      │
       │                              │ method      │
       │                              └─────────────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   guests    │     │maintenance  │     │housekeeping │
├─────────────┤     ├─────────────┤     ├─────────────┤
│ id (PK)     │     │ id (PK)     │     │ id (PK)     │
│ full_name   │     │ room_id     │     │ room_id     │
│ phone       │     │ issue_type  │     │ task_type   │
│ email       │     │ priority    │     │ priority    │
│ id_number   │     │ assigned_to │     │ assigned_to │
└─────────────┘     │ status      │     │ status      │
                    └─────────────┘     └─────────────┘

┌─────────────┐     ┌─────────────┐
│notifications│     │   contacts  │
├─────────────┤     ├─────────────┤
│ id (PK)     │     │ id (PK)     │
│ user_id     │     │ full_name   │
│ title       │     │ email       │
│ message     │     │ phone       │
│ is_read     │     │ subject     │
└─────────────┘     │ message     │
                    └─────────────┘
```

#### Relationships:

| Relationship | Type | Description |
|--------------|------|-------------|
| room_types → rooms | One-to-Many | One type has many rooms |
| rooms → reservations | One-to-Many | One room has many reservations |
| guests → reservations | One-to-Many | One guest has many reservations |
| reservations → check_ins | One-to-Many | One reservation can have check-ins |
| check_ins → payments | One-to-Many | One check-in can have payments |
| check_ins → extra_charges | One-to-Many | One check-in can have extra charges |
| users → maintenance_requests | One-to-Many | One user reports/gets assigned tasks |
| users → housekeeping_tasks | One-to-Many | One user gets assigned cleaning tasks |
| users → notifications | One-to-Many | One user receives many notifications |

---

### 1.2 Database Design

Your database has 13 tables in MySQL/InnoDB format.

#### Key Design Features:

1. **Normalization**: Data is split into logical tables (rooms separate from room_types)
2. **Denormalization**: Room.price_per_night copied from room_types for flexibility
3. **No Foreign Keys**: Relationships enforced in application code (not SQL constraints)
4. **Timestamps**: All tables have created_at for audit trail
5. **Status Fields**: ENUM types for controlled values (room status, reservation status)

---

## Step 2: Data Flow Diagrams & Flowcharts

### 2.1 Data Flow Diagram (DFD) - Level 0 (Context Diagram)

```
                    ┌─────────────────┐
                    │  Frontend Guest │
                    │   (Browser)     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │    HTTP/HTTPS   │
                    │   Requests      │
                    └────────┬────────┘
                             │
┌─────────────┐     ┌────────▼────────┐     ┌─────────────┐
│   Admin     │────►│                 │────►│   MySQL     │
│   Staff     │◄────│  Hotel System   │◄────│  Database   │
│  (Browser)  │     │   (Backend)     │     │             │
└─────────────┘     └────────┬────────┘     └─────────────┘
                             │
                    ┌────────▼────────┐
                    │   HTTP/HTTPS    │
                    │   Responses     │
                    └─────────────────┘
```

**External Entities:**
- Frontend Guest: Public website visitors
- Admin Staff: Hotel employees (Receptionist, Manager, etc.)
- MySQL Database: Data storage

---

### 2.2 Data Flow Diagram (DFD) - Level 1

```
┌─────────────────────────────────────────────────────────────┐
│                      HOTEL SYSTEM                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │     1.0     │  │     2.0     │  │     3.0     │        │
│  │   Login &   │  │    Room     │  │  Reservation│        │
│  │   Auth      │  │  Management │  │  Management │        │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘        │
│         │                │                │                 │
│  ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐        │
│  │     4.0     │  │     5.0     │  │     6.0     │        │
│  │  Check-In   │  │  Check-Out  │  │   Billing   │        │
│  │  Processing │  │  Processing │  │  & Payment  │        │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘        │
│         │                │                │                 │
│  ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐        │
│  │     7.0     │  │     8.0     │  │     9.0     │        │
│  │ Housekeeping│  │ Maintenance │  │  Reporting  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Main Processes:**
1.0 Login & Authentication
2.0 Room Management
3.0 Reservation Management
4.0 Check-In Processing
5.0 Check-Out Processing
6.0 Billing & Payment
7.0 Housekeeping
8.0 Maintenance
9.0 Reporting

---

### 2.3 Data Flow Diagram (DFD) - Level 2 (Reservation Process Detail)

```
                        ┌─────────────┐
                        │   Guest     │
                        │   Request   │
                        └──────┬──────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│                  3.0 RESERVATION MANAGEMENT                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │    3.1      │  │    3.2      │  │    3.3      │        │
│  │   Search    │  │  Validate   │  │   Create    │        │
│  │  Available  │─►│   Room      │─►│ Reservation │        │
│  │    Rooms    │  │ Availability│  │             │        │
│  └─────────────┘  └─────────────┘  └──────┬──────┘        │
│                                           │                 │
│                              ┌────────────▼────────────┐   │
│                              │         3.4             │   │
│                              │   Generate Confirm      │   │
│                              │   Number & Save         │   │
│                              └────────────┬────────────┘   │
│                                           │                 │
└───────────────────────────────────────────┼─────────────────┘
                                            │
                               ┌────────────▼────────────┐
                               │      Reservation        │
                               │    Confirmation         │
                               └─────────────────────────┘
```

**Sub-processes:**
- 3.1: Search available rooms by dates and guest count
- 3.2: Validate room availability (check for overlaps)
- 3.3: Create reservation record with total calculation
- 3.4: Generate unique reservation number (RES-YYYYMMDD-NNNN)

---

### 2.4 Flowchart: Online Booking Process

```
┌─────────────┐
│   START     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Guest visits│
│ rooms.html  │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│ Select dates│     │ API calls   │
│ & guests    │────►│ rooms +     │
│             │     │ available   │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Display     │     │ Show room   │
│ available   │◄────│ cards with  │
│ rooms       │     │ prices      │
└──────┬──────┘     └─────────────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│ Click "Book │     │ Open booking│
│ Now"        │────►│ modal form  │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Fill form   │     │ Calculate   │
│ (name,      │────►│ total =     │
│  email,     │     │ nights ×    │
│  phone)     │     │ price       │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Submit      │     │ API POST    │
│ booking     │────►│ reservation │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Is room     │  ┌──│  YES        │
│ available?  │──┘  └──────┬──────┘
└──────┬──────┘            │
       │                   ▼
       │            ┌─────────────┐
       │ NO         │ Create      │
       │            │ reservation │
       ▼            │ + guest     │
┌─────────────┐     └──────┬──────┘
│ Show error  │            │
│ "No rooms"  │            ▼
└──────┬──────┘     ┌─────────────┐
       │            │ Return      │
       │            │ confirmation│
       ▼            │ number      │
┌─────────────┐     └──────┬──────┘
│   END       │            │
└─────────────┘            ▼
                    ┌─────────────┐
                    │ Show success│
                    │ message     │
                    └──────┬──────┘
                           │
                           ▼
                    ┌─────────────┐
                    │   END       │
                    └─────────────┘
```

---

### 2.5 Flowchart: Check-In Process

```
┌─────────────┐
│   START     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Receptionist│
│ opens       │
│ Check-In    │
│ page        │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ View ready  │
│ reservations│
│ (Pending/   │
│ Confirmed)  │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Select      │
│ reservation │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│ Click       │     │ BEGIN       │
│ "Check In"  │────►│ TRANSACTION │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Validate    │     │ INSERT INTO │
│ reservation │────►│ check_ins   │
│ exists      │     │ status=     │
└──────┬──────┘     │ "Active"    │
       │            └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Check no    │     │ UPDATE      │
│ active      │────►│ rooms SET   │
│ check-in    │     │ status=     │
│ exists      │     │ "Occupied"  │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ All checks  │     │   COMMIT    │
│ passed?     │────►│             │
└──────┬──────┘     └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│   YES       │     │ Show success│
└──────┬──────┘     │ message     │
       │            └──────┬──────┘
       ▼                   │
┌─────────────┐            ▼
│   END       │     ┌─────────────┐
└─────────────┘     │   END       │
                    └─────────────┘
```

---

### 2.6 Flowchart: Check-Out Process

```
┌─────────────┐
│   START     │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Receptionist│
│ opens       │
│ Check-Out   │
│ page        │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ View active │
│ stays with  │
│ computed    │
│ bills       │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Select stay │
│ to checkout │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Optionally  │
│ add extra   │
│ charges     │
│ (Room       │
│ Service,    │
│ Laundry)    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Select      │
│ payment     │
│ method      │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌─────────────┐
│ Click       │     │ BEGIN       │
│ "Process    │────►│ TRANSACTION │
│ Checkout"   │     └──────┬──────┘
└──────┬──────┘            │
       │                   ▼
       │            ┌─────────────┐
       │            │ Add extra   │
       │            │ charges if  │
       │            │ any         │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │ Recalculate │
       │            │ bill with   │
       │            │ tax         │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │ UPDATE      │
       │            │ check_ins   │
       │            │ status=     │
       │            │ "Checked    │
       │            │ Out"        │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │ UPDATE      │
       │            │ reservations│
       │            │ status=     │
       │            │ "Completed" │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │ UPDATE      │
       │            │ rooms SET   │
       │            │ status=     │
       │            │ "Cleaning"  │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │ INSERT INTO │
       │            │ payments    │
       │            │ (invoice#)  │
       │            └──────┬──────┘
       │                   │
       │                   ▼
       │            ┌─────────────┐
       │            │   COMMIT    │
       │            └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│ Show receipt│     │   END       │
└─────────────┘     └─────────────┘
```

---

## Step 3: Figma or Wireframes

### 3.1 Admin Login Wireframe

```
┌─────────────────────────────────────────────────┐
│                                                 │
│                    ┌─────────────┐              │
│                    │   HOTEL     │              │
│                    │   NAME      │              │
│                    │  (Logo)     │              │
│                    └─────────────┘              │
│                                                 │
│                    ┌─────────────┐              │
│                    │  Username   │              │
│                    │  [________] │              │
│                    └─────────────┘              │
│                                                 │
│                    ┌─────────────┐              │
│                    │  Password   │              │
│                    │  [________] │              │
│                    └─────────────┘              │
│                                                 │
│                    ┌─────────────┐              │
│                    │   LOGIN     │              │
│                    │   [BUTTON]  │              │
│                    └─────────────┘              │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

### 3.2 Admin Dashboard Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│ ☰ HOTEL NAME                              🔔 Notifications  👤 │
├─────────────┬───────────────────────────────────────────────────┤
│             │                                                   │
│  Dashboard  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐│
│  Rooms      │  │ Total   │ │ Available│ │ Occupied │ │ Revenue ││
│  Room Types │  │ Rooms   │ │ Rooms   │ │ Rooms   │ │ Today   ││
│  Guests     │  │   50    │ │   35    │ │   15    │ │ $2,500  ││
│  Reservatns │  └─────────┘ └─────────┘ └─────────┘ └─────────┘│
│  Check-In   │                                                   │
│  Check-Out  │  ┌─────────────────────┐ ┌─────────────────────┐ │
│  Billing    │  │  Revenue Chart      │ │  Room Types Chart   │ │
│  Users      │  │  ┌───┐              │ │     ┌───┐           │ │
│  Notificatn │  │  │   │ ┌───┐        │ │     │   │           │ │
│  Reports    │  │  │   │ │   │ ┌───┐  │ │  ┌──│   │──┐        │ │
│  Settings   │  │  │   │ │   │ │   │  │ │  │  └───┘  │        │ │
│             │  │  └───┘ └───┘ └───┘  │ │  └─────────┘        │ │
│             │  └─────────────────────┘ └─────────────────────┘ │
│             │                                                   │
│             │  ┌─────────────────────┐ ┌─────────────────────┐ │
│             │  │ Today's Arrivals    │ │ Today's Departures  │ │
│             │  │ ┌─────────────────┐ │ │ ┌─────────────────┐ │ │
│             │  │ │ Room 101 - John │ │ │ │ Room 202 - Maria│ │ │
│             │  │ │ Room 301 - Chen │ │ │ │ Room 103 - Sarah│ │ │
│             │  │ └─────────────────┘ │ │ └─────────────────┘ │ │
│             │  └─────────────────────┘ └─────────────────────┘ │
│             │                                                   │
│             │  ┌─────────────────────────────────────────────┐ │
│             │  │ Room Status Grid                            │ │
│             │  │ [101][102][103] [201][202][203] [V01][V02] │ │
│             │  │  ✓    ✓    ✗     ✗    ✓    ✓     ✗    ✗   │ │
│             │  │ ✓ = Available  ✗ = Occupied                 │ │
│             │  └─────────────────────────────────────────────┘ │
│             │                                                   │
└─────────────┴───────────────────────────────────────────────────┘
```

---

### 3.3 Admin Reservations Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│ ☰ HOTEL NAME                              🔔 Notifications  👤 │
├─────────────┬───────────────────────────────────────────────────┤
│             │                                                   │
│  Dashboard  │  Reservations                                     │
│  Rooms      │  ┌─────────────────────────────────────────────┐ │
│  Room Types │  │ Search: [____________] Filter: [All ▼]      │ │
│  Guests     │  │                                    [+ Add]  │ │
│  Reservatns │  └─────────────────────────────────────────────┘ │
│  Check-In   │                                                   │
│  Check-Out  │  ┌─────────────────────────────────────────────┐ │
│  Billing    │  │ # │ Guest    │ Room │ Dates      │ Status   │ │
│  Users      │  │───┼──────────┼──────┼────────────┼──────────│ │
│  Notificatn │  │ 1 │ John S.  │ 101  │ Sep 1-5   │ Confirmed│ │
│  Reports    │  │ 2 │ Maria G. │ 202  │ Sep 3-7   │ Pending  │ │
│  Settings   │  │ 3 │ Chen W.  │ VIP1 │ Sep 5-10  │ Completed│ │
│             │  │   │          │      │           │          │ │
│             │  │   │          │      │      [Edit][Cancel]  │ │
│             │  └─────────────────────────────────────────────┘ │
│             │                                                   │
│             │  ┌─────────────────────────────────────────────┐ │
│             │  │ Pagination: < 1 2 3 ... 10 >                │ │
│             │  └─────────────────────────────────────────────┘ │
│             │                                                   │
└─────────────┴───────────────────────────────────────────────────┘
```

---

### 3.4 Admin Add/Edit Reservation Modal

```
┌─────────────────────────────────────────┐
│           Add New Reservation           │
├─────────────────────────────────────────┤
│                                         │
│  Guest: [Select Guest ▼]               │
│                                         │
│  Room: [Select Room ▼]                 │
│                                         │
│  Check-In Date: [__/__/____]           │
│                                         │
│  Check-Out Date: [__/__/____]          │
│                                         │
│  Number of Guests: [__]                │
│                                         │
│  Special Requests:                      │
│  ┌─────────────────────────────────┐   │
│  │                                 │   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Total: $XXX.XX                         │
│                                         │
│         [Cancel]  [Save Reservation]    │
│                                         │
└─────────────────────────────────────────┘
```

---

### 3.5 Frontend Homepage Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│ Logo    Home   Rooms   Gallery   About   Contact   Book Now     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                    ┌─────────────────────┐                     │
│                    │   WELCOME TO        │                     │
│                    │   HOTEL NAME        │                     │
│                    │                     │                     │
│                    │   Experience luxury │                     │
│                    │   in Siem Reap      │                     │
│                    │                     │                     │
│                    │   [Book Now]        │                     │
│                    └─────────────────────┘                     │
│                                                                 │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐      │
│  │   Pool    │ │  Dining   │ │ 24/7 Svc  │ │   Spa     │      │
│  │   🏊     │ │   🍽️     │ │   📞     │ │   💆     │      │
│  └───────────┘ └───────────┘ └───────────┘ └───────────┘      │
│                                                                 │
│                    ┌─────────────────────┐                     │
│                    │   OUR ROOMS         │                     │
│                    └─────────────────────┘                     │
│                                                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐              │
│  │  Sweet Room │ │  VIP Room   │ │   Deluxe    │              │
│  │  ┌───────┐  │ │  ┌───────┐  │ │  ┌───────┐  │              │
│  │  │ IMAGE │  │ │  │ IMAGE │  │ │  │ IMAGE │  │              │
│  │  └───────┘  │ │  └───────┘  │ │  └───────┘  │              │
│  │  $50/night  │ │  $100/night │ │  $55/night  │              │
│  │  [Book Now] │ │  [Book Now] │ │  [Book Now] │              │
│  └─────────────┘ └─────────────┘ └─────────────┘              │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    FOOTER                               │   │
│  │  Address: Siem Reap, Cambodia                           │   │
│  │  Phone: +855 123 456 789                               │   │
│  │  Email: info@hotel.com                                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 3.6 Frontend Rooms Page Wireframe

```
┌─────────────────────────────────────────────────────────────────┐
│ Logo    Home   Rooms   Gallery   About   Contact   Book Now     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                    ┌─────────────────────┐                     │
│                    │   OUR ROOMS         │                     │
│                    └─────────────────────┘                     │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Search Form                                            │   │
│  │  Check-In: [__/__/____]  Check-Out: [__/__/____]       │   │
│  │  Guests: [1 ▼]              [Search Available]          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐              │
│  │  Sweet Room │ │  VIP Room   │ │   Deluxe    │              │
│  │  ┌───────┐  │ │  ┌───────┐  │ │  ┌───────┐  │              │
│  │  │ IMAGE │  │ │  │ IMAGE │  │ │  │ IMAGE │  │              │
│  │  └───────┘  │ │  └───────┘  │ │  └───────┘  │              │
│  │  $50/night  │ │  $100/night │ │  $55/night  │              │
│  │  Capacity: 2│ │  Capacity: 4│ │  Capacity: 2│              │
│  │  [Book Now] │ │  [Book Now] │ │  [Book Now] │              │
│  └─────────────┘ └─────────────┘ └─────────────┘              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 3.7 Frontend Booking Modal Wireframe

```
┌─────────────────────────────────────────┐
│           Book Your Stay                │
├─────────────────────────────────────────┤
│                                         │
│  Room: Sweet Room - $50/night          │
│                                         │
│  Full Name: [________________]         │
│                                         │
│  Email: [________________]             │
│                                         │
│  Phone: [________________]             │
│                                         │
│  Check-In: [__/__/____]               │
│                                         │
│  Check-Out: [__/__/____]              │
│                                         │
│  Guests: [1 ▼]                         │
│                                         │
│  Special Requests:                      │
│  ┌─────────────────────────────────┐   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ────────────────────────────────────   │
│  5 nights × $50 = $250.00             │
│  Tax (10%) = $25.00                   │
│  ────────────────────────────────────   │
│  Total: $275.00                         │
│                                         │
│         [Cancel]  [Confirm Booking]     │
│                                         │
└─────────────────────────────────────────┘
```

---

## Step 4: System Architecture

### 4.1 MVC Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      USER (Browser)                             │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           │ HTTP Request
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                     ROUTER                                      │
│                   (Core/Router.php)                             │
│                                                                │
│  URL: ?r=controller/action                                     │
│  Example: ?r=reservation/index                                 │
│                                                                │
│  Parses URL → Controller Class → Action Method                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                   CONTROLLER                                    │
│              (Controllers/*.php)                                │
│                                                                │
│  - Handles user input                                         │
│  - Validates data                                             │
│  - Calls Model methods                                        │
│  - Selects View to render                                     │
└──────────────────────────┬──────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              │                         │
              ▼                         ▼
┌─────────────────────┐     ┌─────────────────────┐
│       MODEL         │     │        VIEW         │
│   (Models/*.php)    │     │   (Views/*.php)     │
│                     │     │                     │
│ - Database queries  │     │ - HTML templates    │
│ - CRUD operations   │     │ - Forms             │
│ - Data validation   │     │ - Tables            │
└──────────┬──────────┘     │ - Charts            │
           │                └──────────┬──────────┘
           │                           │
           ▼                           │
┌─────────────────────┐                │
│     DATABASE        │                │
│    (MySQL)          │                │
│                     │                │
│ - 13 tables         │                │
│ - CRUD data         │                │
└──────────┬──────────┘                │
           │                           │
           │     ◄─────────────────────┘
           │         Data returned
           ▼
┌─────────────────────────────────────────────────────────────────┐
│                  HTTP Response                                  │
│                                                                │
│  HTML Page sent back to browser                                │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.2 Request-Response Cycle

```
┌─────────────────────────────────────────────────────────────────┐
│                        REQUEST                                  │
├─────────────────────────────────────────────────────────────────┤
│ 1. User clicks link or submits form                            │
│ 2. Browser sends HTTP request to server                        │
│ 3. PHP receives request (index.php)                            │
│ 4. Router parses URL (?r=controller/action)                    │
│ 5. Controller method is called                                 │
│ 6. Controller calls Model methods                              │
│ 7. Model executes SQL queries                                  │
│ 8. Database returns data                                       │
│ 9. Model returns data to Controller                            │
│10. Controller passes data to View                               │
│11. View renders HTML with data                                 │
│12. Response sent back to browser                               │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.3 File Structure Architecture

```
arizu-arimato/
│
├── frontend/                    # PUBLIC WEBSITE (Guest-Facing)
│   ├── index.html              # Homepage
│   ├── rooms.html              # Room browsing + booking
│   ├── about.html              # About hotel
│   ├── contact.html            # Contact form
│   ├── gallery.html            # Photo gallery
│   ├── css/style.css           # Styles
│   └── js/                     # JavaScript
│       ├── api.js              # API client
│       └── main.js             # UI behaviors
│
├── backend/                    # ADMIN PANEL (Staff-Facing)
│   ├── index.php               # Entry point (Front Controller)
│   ├── config.php              # Database config, helpers
│   ├── bootstrap.php           # Autoloader
│   ├── cors.php                # CORS headers
│   │
│   ├── Core/                   # FRAMEWORK CORE
│   │   ├── Router.php          # URL routing
│   │   ├── Controller.php      # Base controller
│   │   └── Model.php           # Base model (PDO)
│   │
│   ├── Controllers/            # BUSINESS LOGIC
│   │   ├── LoginController.php
│   │   ├── DashboardController.php
│   │   ├── RoomController.php
│   │   ├── ReservationController.php
│   │   ├── CheckInController.php
│   │   ├── CheckOutController.php
│   │   ├── BillingController.php
│   │   ├── ApiController.php
│   │   └── ... (18 controllers)
│   │
│   ├── Models/                 # DATABASE QUERIES
│   │   ├── Room.php
│   │   ├── RoomType.php
│   │   ├── Guest.php
│   │   ├── Reservation.php
│   │   ├── CheckIn.php
│   │   └── User.php
│   │
│   ├── Views/                  # HTML TEMPLATES
│   │   ├── layout/
│   │   │   ├── header.php      # Sidebar navigation
│   │   │   └── footer.php      # Scripts + utilities
│   │   ├── dashboard/
│   │   ├── rooms/
│   │   ├── reservations/
│   │   └── ... (20 views)
│   │
│   └── database/               # SQL FILES
│       ├── hotel_clean.sql     # Main schema + seed
│       └── infinityfree_import.sql
│
├── CODE_EXPLANATIONS/          # DOCUMENTATION
│   └── ... (13 files)
│
└── SESSION_SUMMARY.md          # Project summary
```

---

## Step 5: Implementation/Coding (20 Points)

### 5.1 Functionality Checklist

| Feature | Status | Location |
|---------|--------|----------|
| User Login/Logout | ✅ | LoginController.php |
| Dashboard Analytics | ✅ | DashboardController.php |
| Room Management (CRUD) | ✅ | RoomController.php |
| Room Type Management | ✅ | RoomTypeController.php |
| Guest Management (CRUD) | ✅ | GuestController.php |
| Reservation Management | ✅ | ReservationController.php |
| Check-In Processing | ✅ | CheckInController.php |
| Check-Out Processing | ✅ | CheckOutController.php |
| Payment/Billing | ✅ | BillingController.php |
| Staff User Management | ✅ | UserController.php |
| Housekeeping Tasks | ✅ | HousekeepingController.php |
| Maintenance Requests | ✅ | MaintenanceController.php |
| Notifications | ✅ | NotificationController.php |
| Reports & Analytics | ✅ | ReportController.php |
| Settings Management | ✅ | SettingController.php |
| Public API | ✅ | ApiController.php |
| Online Booking | ✅ | frontend/rooms.html |
| Contact Form | ✅ | frontend/contact.html |

---

### 5.2 Code Quality

| Criteria | Implementation |
|----------|----------------|
| **MVC Pattern** | Separate Controllers, Models, Views |
| **Autoloading** | PSR-0 autoloader in bootstrap.php |
| **Separation of Concerns** | Each file has single responsibility |
| **Consistent Naming** | PascalCase classes, camelCase methods |
| **No Code Duplication** | Base Controller/Model with inheritance |
| **Helper Functions** | config.php has formatCurrency, formatDate, etc. |

---

### 5.3 Security

| Security Measure | Implementation |
|------------------|----------------|
| **Password Hashing** | password_hash() with bcrypt |
| **SQL Injection Prevention** | PDO prepared statements everywhere |
| **XSS Prevention** | htmlspecialchars() on output |
| **CSRF Protection** | Session-based auth checks |
| **Session Security** | session_start(), secure cookies |
| **Role-Based Access** | roleRequired() in Controller constructors |
| **Input Validation** | Server-side validation in Controllers |

---

### 5.4 Responsive UI

| Device | Implementation |
|--------|----------------|
| **Mobile** | Bootstrap responsive grid, mobile sidebar |
| **Tablet** | Flexible layouts, touch-friendly buttons |
| **Desktop** | Full sidebar, large tables, charts |

**CSS Features:**
- CSS Flexbox and Grid
- Media queries for breakpoints
- Responsive tables with horizontal scroll
- Collapsible sidebar navigation

---

### 5.5 Validation

| Validation Type | Location |
|-----------------|----------|
| **Required Fields** | Controller POST handlers |
| **Email Format** | filter_var(FILTER_VALIDATE_EMAIL) |
| **Phone Format** | Regex pattern matching |
| **Number Range** | Comparison checks |
| **Date Format** | strtotime() validation |
| **Unique Constraints** | Database queries before insert |
| **Password Length** | Minimum 4 characters |

---

## Summary: Complete Project Checklist

| Step | Item | Points | Status |
|------|------|--------|--------|
| 1 | ERD Diagram | - | ⬜ Create |
| 2 | Database Design | 10 | ✅ Done |
| 3 | Data Flow Diagrams | - | ⬜ Create |
| 4 | Flowcharts | - | ⬜ Create |
| 5 | Wireframes | - | ⬜ Create |
| 6 | System Architecture | - | ⬜ Create |
| 7 | Functionality | 20 | ✅ Done |
| 8 | Code Quality | - | ✅ Done |
| 9 | Security | - | ✅ Done |
| 10 | Responsive UI | - | ✅ Done |
| 11 | Validation | - | ✅ Done |

---

**Next Steps:**
1. Create ERD diagram (use Draw.io, Lucidchart, or hand-drawn)
2. Create Data Flow Diagrams (Levels 0, 1, 2)
3. Create Flowcharts for key processes
4. Create Wireframes for all pages
5. Create System Architecture diagram
