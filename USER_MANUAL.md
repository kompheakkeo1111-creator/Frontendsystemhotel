# USER MANUAL — Arizu Arimato Hotel Management System

> Version 1.2 · October 2026
> This manual covers both the **public hotel website** (guests) and the
> **staff admin panel** (reception, management). No technical knowledge needed.

---

## 1. System Overview

Arizu Arimato is a complete hotel management system with two parts:

| Part | Who uses it | Address |
|------|-------------|---------|
| **Public website** | Guests: browse rooms, check availability, book online, send messages | `https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/frontend/index.html` (or your hosting URL) |
| **Admin panel** | Staff: manage reservations, check-ins, billing, rooms, users, reports | `http://your-server/backend/index.php` |

---

## 2. For Guests — Public Website

### 2.1 Open the website
Open the homepage. Use the top menu: **Home · Rooms · Gallery · About · Contact**.
On a phone, tap the ☰ menu button.

### 2.2 Search for available rooms
1. Open **Rooms**.
2. In the search form choose:
   - **Check-in date** (arrival)
   - **Check-out date** (departure — must be after check-in)
   - **Guests** (number of people)
3. Click **Search**.
   - Available rooms appear as cards with photo, type, price per night and capacity.
   - If nothing is free for those dates, a "no rooms available" message is shown.

### 2.3 Book a room
1. Find a room you like in the search results and click **Book Now**.
2. A booking form opens. Fill in:
   - Full name, email address, phone number
   - Number of guests
   - Special requests (optional, e.g. "high floor, late arrival")
3. Check the summary: nights and total price are calculated automatically
   (price per night × number of nights).
4. Click **Confirm Booking**.
5. You receive a **reservation number** in the form `RES-YYYYMMDD-####`
   (example: `RES-20261026-4821`). **Save this number** — it is needed at
   reception and for any changes.

> Your reservation is created with status **Pending**. Reception will confirm it.

### 2.4 Send a message (Contact page)
1. Open **Contact**.
2. Enter your name, email, subject and message.
3. Click **Send**. A thank-you message confirms delivery.

---

## 3. For Staff — Admin Panel

### 3.1 Log in
1. Open the admin URL (`backend/index.php`).
2. Enter your **username** and **password**, then click **Login**.
   - Three wrong attempts with the wrong password simply show
     *"Invalid username or password"* — ask an administrator to reset your account.
3. The **Dashboard** opens with today's statistics: arrivals, departures,
   occupancy, and revenue.

### 3.2 Reservations (Reception / Manager / Administrator)
Open **Reservations** in the menu.

| Task | Steps |
|------|-------|
| **Create** | Click *Add Reservation* → choose room type, dates, guest details → the system picks a free room automatically → *Save* |
| **Search / filter** | Use the search box (reservation number or guest name) and the status filter (Pending / Confirmed / Cancelled / Completed) |
| **View details** | Click the 👁 button — a window shows guest, room, dates and the price breakdown (nights × rate + tax) |
| **Edit** | Click ✏ → change dates, guests, status or requests → *Update* |
| **Cancel / delete** | Click 🗑 → confirm. The room is released and becomes bookable again. (Cannot delete a guest who is currently checked in.) |

### 3.3 Check-in (front desk)
Open **Check-In**.

1. Find the reservation (by number or guest name) and click **Check In**.
2. Verify the guest's ID document, then confirm.
3. The room status changes to **Occupied** and the guest appears under
   current stays.

### 3.4 Check-out & Billing
Open **Check-Out**, then **Billing**.

1. Select the staying guest and click **Check Out**.
2. The bill is calculated automatically:
   `nights × price per night` + any extra charges + tax.
3. Review extra charges (minibar, laundry, …) — add items if needed.
4. Click **Record Payment**. A receipt/invoice is generated and can be printed.
5. The room status changes to **Cleaning**, then **Available** after housekeeping.

### 3.5 Rooms & Room Types (Administrator)
Open **Rooms** / **Room Types**.

* **Room Types**: add or edit a type (name, description, price per night, capacity).
* **Rooms**: add a room (number, floor, type) and set its status:
  `Available`, `Occupied`, `Cleaning`, `Maintenance`.
* A room in **Maintenance** never appears in online search results.

### 3.6 Housekeeping & Maintenance
* **Housekeeping**: daily task list per room (cleaning, linen change) — mark tasks as done.
* **Maintenance**: report a problem for a room; while a room is under
  maintenance it is blocked from new bookings.

### 3.7 Guests
Open **Guests** to see the guest database (contact details, ID numbers,
stay history). Used to look up returning customers.

### 3.8 Reports (Manager / Administrator)
Open **Reports** for:
* Occupancy rate per period
* Revenue per period
* Popular room types

Use the date pickers, then **Export/Print** for a paper copy.

### 3.9 Users & Roles (Administrator only)
Open **Users** to manage staff accounts.

| Role | Can access |
|------|------------|
| **Administrator** | Everything, including Users, Room Types, Settings |
| **Manager** | Reservations, Check-in/out, Billing, Reports |
| **Receptionist** | Reservations, Check-in/out, Billing |
| **Housekeeping Staff** | Housekeeping tasks |
| **Accountant** | Billing, Reports |

> If you open a page you are not allowed to see, the system automatically
> returns you to the Dashboard.

### 3.10 Settings (Administrator)
Open **Settings** to change the hotel name, address, phone/email, currency
and **tax rate** (used in every bill).

### 3.11 Log out
Always click **Logout** when finished — especially on a shared reception
computer. Your session is immediately closed.

---

## 4. Troubleshooting

| Problem | What to do |
|---------|------------|
| "Database connection failed" | The server cannot reach MySQL. Start MySQL in WAMP/XAMPP, or check `backend/config_local.php`. |
| Booking says "No rooms available" | All rooms of that type are booked for those dates. Try other dates or another type. |
| Contact form does not send | Check that all required fields are filled and the email address is valid. |
| Page looks wrong on phone | Pull down to refresh; the site is responsive but old browser caches can be stale. |
| Forgot staff password | An administrator must reset it in **Users**. |
| Live demo shows only the website | GitHub Pages hosts the public site only; the admin panel needs PHP hosting (see `FREE_HOSTING.md`). |

---

## 5. Need more?

* **API documentation** (for developers): `CODE_EXPLANATIONS/07_API.md`
* **Database schema**: `hotel_erd.html` or `CODE_EXPLANATIONS/06_DATABASE.md`
* **Deployment guides**: `FREE_HOSTING.md`, `CLIENT_DEPLOYMENT.md`
* **Testing evidence**: `TESTING.md`
