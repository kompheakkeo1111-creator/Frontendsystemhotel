# Arizu Arimato - Hotel Management System
## Complete Code Explanation & Process Guide

---

## 1. PROJECT STRUCTURE

```
arizu-arimato/
├── frontend/                         # PUBLIC WEBSITE (static HTML)
│   ├── index.html                    # Homepage
│   ├── about.html                    # About page
│   ├── rooms.html                    # Rooms + booking
│   ├── gallery.html                  # Photo gallery
│   ├── contact.html                  # Contact form
│   ├── css/style.css                 # Custom CSS
│   ├── js/api.js                     # API helper functions
│   ├── js/main.js                    # Animations, menu, lightbox
│   └── images/                       # Hotel photos
│
└── backend/                          # ADMIN PANEL + API (PHP MVC)
    ├── index.php                     # Entry point (front controller)
    ├── config.php                    # Database config + helpers
    ├── bootstrap.php                 # Autoloader
    ├── cors.php                      # Cross-origin support
    ├── Core/                         # Framework core
    │   ├── Router.php                # URL routing
    │   ├── Controller.php            # Base controller
    │   └── Model.php                 # Base model (PDO)
    ├── Controllers/                  # 16 controllers
    ├── Models/                       # 6 models
    ├── Views/                        # PHP templates
    └── database/                     # SQL schema
```

---

## 2. ARCHITECTURE: MVC PATTERN

The backend uses **MVC (Model-View-Controller)**:

- **Model** = Database logic (SQL queries)
- **View** = HTML templates (PHP files)
- **Controller** = Business logic (handles requests)

### Request Flow:
```
Browser Request
    ↓
index.php (Entry Point)
    ↓
Router (?r=controller/action)
    ↓
Controller Method
    ↓
Model (Database Query)
    ↓
View (HTML Response)
    ↓
Browser Display
```

---

## 3. FRONTEND CODE EXPLANATION

### 3.1 index.html - Homepage

```html
<!-- Hero section with background image -->
<section class="hero">
    <h1>ARIZU ARIMATO</h1>
    <a href="rooms.html">Our Rooms</a>      <!-- Button to rooms -->
    <a href="contact.html">Contact Us</a>    <!-- Button to contact -->
</section>

<!-- Features section -->
<div class="features">
    <!-- 6 feature cards: Pool, Dining, 24/7 Service, Spa, Wi-Fi, Airport -->
</div>

<!-- Stats counter (animated numbers) -->
<div class="stat-number" data-target="50">0</div>  <!-- 50+ Rooms -->

<!-- Rooms preview (loads from API) -->
<div id="roomsGrid"></div>  <!-- JavaScript fills this with room cards -->
```

**How it works:**
1. Page loads HTML structure
2. `main.js` adds scroll effects and animations
3. `api.js` calls backend API to get rooms
4. JavaScript renders room cards dynamically

### 3.2 rooms.html - Room Booking

```javascript
// Load rooms from API on page load
async function loadRooms() {
    const res = await apiGet('rooms');  // GET /backend/index.php?r=api/rooms
    if (res.success) {
        renderRooms(res.data);  // Create room cards
    }
}

// Search available rooms
document.getElementById('searchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const checkIn = document.getElementById('checkIn').value;
    const checkOut = document.getElementById('checkOut').value;
    const guests = document.getElementById('guests').value;

    // Call API with dates
    const res = await apiGet(`availableRooms&check_in=${checkIn}&check_out=${checkOut}&guests=${guests}`);
    renderRooms(res.data);  // Show available rooms
});

// Book a room
async function submitBooking() {
    const data = {
        room_type_id: selectedRoom,
        full_name: name,
        email: email,
        phone: phone,
        check_in_date: checkIn,
        check_out_date: checkOut
    };
    const res = await apiPost('reservation', data);  // POST to API
    // Show confirmation with reservation number
}
```

### 3.3 js/api.js - API Helper

```javascript
// Backend URL
const API_BASE = 'http://localhost/arizu-arimato/backend/index.php';

// GET request
async function apiGet(endpoint) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url);
    return await res.json();
}

// POST request
async function apiPost(endpoint, data) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}
```

### 3.4 js/main.js - Animations

```javascript
// Navbar changes color on scroll
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

// Mobile menu toggle
hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Fade-in animation on scroll (IntersectionObserver)
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
});

// Stats counter animation (counts up from 0 to target)
// Gallery lightbox (click image to view full screen)
```

---

## 4. BACKEND CODE EXPLANATION

### 4.1 index.php - Entry Point

```php
<?php
// Enable CORS (allows frontend to call API)
require __DIR__ . '/cors.php';

// Load config and autoloader
require __DIR__ . '/bootstrap.php';

// Check if system is installed
if (!isInstalled() && $route !== 'setup/index') {
    header('Location: index.php?r=setup/index');
    exit();
}

// Get route from URL (?r=dashboard/index)
$route = isset($_GET['r']) ? $_GET['r'] : '';

// Route to controller
$router = new Router();
$router->dispatch($route);
```

### 4.2 config.php - Database Connection

```php
<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_management');

// Get database connection (PDO)
function getDB() {
    static $conn = null;
    if ($conn !== null) return $conn;
    
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($roles) {
    return in_array($_SESSION['user_role'], $roles);
}
```

### 4.3 Core/Router.php - URL Routing

```php
<?php
public function dispatch($route) {
    // Empty route -> dashboard
    if (empty($route)) {
        $controller = 'Dashboard';
        $action = 'index';
    } else {
        // Split "room_types/index" -> controller="RoomType", action="index"
        $parts = explode('/', $route);
        $controller = $this->parseController($parts[0]);
        $action = $parts[1] ?? 'index';
    }
    
    // Check if controller exists
    $class = "App\\Controllers\\{$controller}Controller";
    if (!class_exists($class)) {
        http_response_code(404);
        die("Controller not found");
    }
    
    // Create controller and check auth
    $controllerObj = new $class();
    if ($controllerObj->authRequired && !isLoggedIn()) {
        header('Location: index.php?r=login/index');
        exit();
    }
    
    // Call action method
    $actionMethod = $action . 'Action';
    $controllerObj->$actionMethod();
}
```

### 4.4 Controllers/ApiController.php - Public API

```php
<?php
class ApiController extends Controller {
    public $authRequired = false;  // Public - no login needed

    // GET ?r=api/rooms - Get all room types
    public function roomsAction() {
        $stmt = $this->db->query("
            SELECT rt.*, COUNT(r.id) as total_rooms,
                   SUM(CASE WHEN r.status='Available' THEN 1 ELSE 0 END) as available_rooms
            FROM room_types rt
            LEFT JOIN rooms r ON r.room_type_id = rt.id
            GROUP BY rt.id
        ");
        $roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['success' => true, 'data' => $roomTypes]);
    }

    // GET ?r=api/availableRooms - Search available rooms
    public function availableRoomsAction() {
        $checkIn = $_GET['check_in'];
        $checkOut = $_GET['check_out'];
        $guests = $_GET['guests'];
        
        // Find rooms not reserved and not occupied
        $stmt = $this->db->prepare("
            SELECT r.*, rt.type_name
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.status NOT IN ('Maintenance')
            AND r.capacity >= ?
            AND NOT EXISTS (
                SELECT 1 FROM reservations
                WHERE room_id = r.id AND status IN ('Pending','Confirmed')
                AND check_in_date < ? AND check_out_date > ?
            )
            AND NOT EXISTS (
                SELECT 1 FROM check_ins
                WHERE room_id = r.id AND status = 'Active'
                AND expected_check_out > ?
            )
        ");
        $stmt->execute([$guests, $checkOut, $checkIn, $checkIn]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['success' => true, 'data' => $rooms]);
    }

    // POST ?r=api/reservation - Create reservation
    public function reservationAction() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Find available room
        $room = $this->findAvailableRoom($input['room_type_id'], $input['check_in_date'], $input['check_out_date']);
        
        // Create or find guest
        $guest = $this->findOrCreateGuest($input['email'], $input['full_name']);
        
        // Calculate total
        $nights = (strtotime($input['check_out_date']) - strtotime($input['check_in_date'])) / 86400;
        $total = $nights * $room['price_per_night'];
        
        // Create reservation
        $resNumber = 'RES-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt = $this->db->prepare("INSERT INTO reservations ...");
        $stmt->execute([$resNumber, $guest['id'], $room['id'], ...]);
        
        $this->jsonResponse(['success' => true, 'data' => ['reservation_number' => $resNumber, 'total' => $total]]);
    }
}
```

---

## 5. DATABASE STRUCTURE

### Tables Overview:

| Table | Purpose |
|-------|---------|
| users | Staff accounts (admin, receptionist, etc.) |
| room_types | Room categories (Deluxe, Suite, VIP) |
| rooms | Individual rooms (Room 101, 102, etc.) |
| guests | Guest information |
| reservations | Booking records |
| check_ins | Physical check-in records |
| payments | Payment transactions |
| extra_charges | Additional fees (room service, laundry) |
| system_settings | Hotel settings (name, currency, tax) |
| contacts | Contact form submissions |

### Entity Relationships:

```
room_types  1---< many  rooms
rooms       1---< many  reservations
rooms       1---< many  check_ins
guests      1---< many  reservations
guests      1---< many  check_ins
reservations 1---< many  payments
check_ins   1---< many  extra_charges
```

---

## 6. PROCESS FLOWS

### Process A: Guest Books a Room (Frontend)

```
1. Guest opens rooms.html
2. Page loads -> calls API: GET /api/rooms
3. API returns room types with prices
4. Guest selects dates and clicks Search
5. Page calls API: GET /api/availableRooms?check_in=...&check_out=...&guests=...
6. API checks:
   - Room not in Maintenance
   - Room capacity >= guests
   - No overlapping reservations
   - No active check-ins
7. Returns available rooms
8. Guest clicks "Book Now"
9. Guest fills form (name, email, phone, dates)
10. Page calls API: POST /api/reservation
11. API:
    - Finds available room
    - Creates/finds guest record
    - Calculates total (nights x price)
    - Creates reservation with status "Pending"
    - Returns reservation number
12. Guest sees confirmation with reservation number
```

### Process B: Staff Check-In

```
1. Staff logs in -> redirected to dashboard
2. Staff goes to Check-in page
3. Sees list of reservations ready for check-in
4. Clicks "Check In" on a reservation
5. Backend processes:
   - BEGIN TRANSACTION
   - Creates check_in record (status="Active")
   - Updates room status to "Occupied"
   - COMMIT
6. Guest is now checked in
```

### Process C: Staff Check-Out

```
1. Staff goes to Check-out page
2. Sees list of active stays with bills
3. Bill calculated: (nights x rate) + extra_charges + tax
4. Staff clicks "Payment & Check-out"
5. Staff can add extra charges (room service, laundry, etc.)
6. Staff selects payment method (Cash, Card, QR, Transfer)
7. Backend processes:
   - BEGIN TRANSACTION
   - Adds extra charge (if any)
   - Updates check_in status to "Checked Out"
   - Updates reservation status to "Completed"
   - Updates room status to "Cleaning"
   - Creates payment record with invoice number
   - COMMIT
8. Receipt is generated and displayed
```

---

## 7. ROLE-BASED ACCESS

| Role | Can Access |
|------|-----------|
| Administrator | Everything |
| Manager | Dashboard, Rooms, Guests, Reservations, Check-in/out, Billing, Reports |
| Receptionist | Dashboard, Rooms, Guests, Reservations, Check-in/out |
| Housekeeping | Dashboard, Rooms, Housekeeping, Maintenance |
| Accountant | Dashboard, Billing, Reports |

---

## 8. DEPLOYMENT

### Frontend (GitHub Pages):
- Static files deployed automatically via GitHub Actions
- URL: https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/

### Backend (PHP Hosting):
- Requires PHP + MySQL
- Upload backend/ folder to hosting
- Update config.php with database credentials
- Access to run installer (creates tables + admin user)

### Local Development (WAMP):
- Frontend: http://localhost/arizu-arimato/frontend/
- Backend: http://localhost/arizu-arimato/backend/

---

## 9. DEFAULT LOGIN

- **URL**: http://localhost/arizu-arimato/backend/
- **Username**: admin
- **Password**: admin123

---

## 10. KEY FEATURES

1. **Public Booking Website**: Guests can view rooms and make reservations
2. **Admin Dashboard**: Real-time stats, charts, room status
3. **Reservation Management**: Create, edit, cancel bookings
4. **Check-in/Check-out**: Walk-in and reservation-based
5. **Billing**: Multiple payment methods, receipts, CSV export
6. **Reports**: Revenue, occupancy, guest analytics
7. **Room Management**: CRUD, status tracking
8. **Guest Management**: Profile, stay history
9. **User Management**: Role-based access control
10. **Database Backup/Restore**: One-click backup and restore
