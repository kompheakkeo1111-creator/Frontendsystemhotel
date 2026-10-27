# Arizu Arimato - Hotel Management System
## Complete Code Explanation (Every Function & Process)

---

## TABLE OF CONTENTS
1. [Project Structure](#1-project-structure)
2. [Frontend Files](#2-frontend-files)
3. [Backend Core Files](#3-backend-core-files)
4. [Controllers - Every Function](#4-controllers)
5. [Models - Every Function](#5-models)
6. [Views - Templates](#6-views)
7. [Database Tables](#7-database)
8. [Process Flows](#8-process-flows)

---

## 1. PROJECT STRUCTURE

```
arizu-arimato/
├── frontend/                    # PUBLIC WEBSITE
│   ├── index.html               # Homepage
│   ├── about.html               # About hotel
│   ├── rooms.html               # Room listing + booking
│   ├── gallery.html             # Photo gallery
│   ├── contact.html             # Contact form
│   ├── css/style.css            # Styling (830 lines)
│   ├── js/api.js                # API calls to backend
│   ├── js/main.js               # Animations + UI
│   └── images/                  # Hotel photos (6 images)
│
└── backend/                     # ADMIN PANEL + API
    ├── index.php                # Entry point (24 lines)
    ├── config.php               # Database + helpers (128 lines)
    ├── bootstrap.php            # Autoloader (19 lines)
    ├── cors.php                 # CORS headers (10 lines)
    ├── .htaccess                # URL rewriting (8 lines)
    ├── Core/
    │   ├── Router.php           # URL routing (61 lines)
    │   ├── Controller.php       # Base controller (65 lines)
    │   └── Model.php            # Base model (15 lines)
    ├── Controllers/             # 16 controllers (business logic)
    ├── Models/                  # 6 models (database queries)
    ├── Views/                   # 20+ PHP templates
    └── database/                # SQL schema files
```

---

## 2. FRONTEND FILES

### 2.1 index.html - Homepage

**Purpose:** Main landing page for hotel website

**Sections:**
- **Navbar**: Fixed navigation bar with links to all pages. Changes from transparent to dark on scroll.
- **Hero**: Full-screen background image with hotel name and "Book Now" / "Contact" buttons.
- **Features**: 6 cards showing amenities (Pool, Dining, 24/7 Service, Spa, Wi-Fi, Airport Transfer).
- **Stats**: Animated counters (50+ Rooms, 10K+ Guests, 15 Years, 20 Awards).
- **Parallax**: Two decorative image sections (pool, restaurant).
- **Rooms Preview**: Loads room types from API and displays them as cards.
- **Footer**: Hotel info, quick links, contact details.

**How it works:**
1. HTML loads structure and styling
2. `main.js` adds scroll effects, animations, counters
3. On page load, JavaScript calls `apiGet('rooms')` to fetch room data
4. Room cards are dynamically created and inserted into `#roomsGrid`

---

### 2.2 rooms.html - Room Booking Page

**Purpose:** Display all rooms with search and booking functionality

**Sections:**
- **Search Form**: Date pickers (check-in, check-out), guest dropdown, search button
- **Room Grid**: Dynamically loaded from API
- **Booking Modal**: Popup form for making reservations
- **Amenities Section**: Static cards showing room features

**Key Functions:**

```javascript
// Function: loadRooms()
// Purpose: Fetch all room types from API and display them
// Called: On page load
// API: GET /api/rooms
// Returns: Array of room types with prices and availability
async function loadRooms() {
    try {
        const res = await apiGet('rooms');
        if (res.success && res.data.length > 0) {
            renderRooms(res.data);
            return;
        }
    } catch(e) {
        console.error('API failed, using local data:', e);
    }
    // Fallback: local room data if API fails
    renderRooms([...]);
}

// Function: renderRooms(roomTypes)
// Purpose: Create HTML cards for each room type
// Input: Array of room type objects
// Output: HTML inserted into #roomsGrid
function renderRooms(roomTypes) {
    const grid = document.getElementById('roomsGrid');
    grid.innerHTML = roomTypes.map(type => `
        <div class="room-card fade-in">
            <img src="${type.image}" alt="${type.type_name}">
            <div class="room-info">
                <h3>${type.type_name}</h3>
                <div class="price">$${type.price_per_night} / night</div>
                <p>${type.description}</p>
                <div class="room-amenities">
                    <span><i class="fas fa-users"></i> ${type.capacity} Guests</span>
                    <span><i class="fas fa-door-open"></i> ${type.available_rooms} Available</span>
                </div>
                <button onclick="openBooking(...)">Book Now</button>
            </div>
        </div>
    `).join('');
}

// Function: openBooking(typeId, typeName, price)
// Purpose: Open booking modal with room details
// Called: When user clicks "Book Now" button
// Sets: Room type ID, name, price in hidden form fields
function openBooking(typeId, typeName, price) {
    document.getElementById('bookRoomTypeId').value = typeId;
    document.getElementById('bookPrice').value = price;
    document.getElementById('modalRoomType').textContent = typeName;
    document.getElementById('bookingModal').style.display = 'flex';
}

// Function: closeBookingModal()
// Purpose: Close the booking modal
function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

// Function: updateBookingTotal()
// Purpose: Calculate and display total price based on dates
// Called: When check-in or check-out date changes
// Formula: nights x price_per_night
function updateBookingTotal() {
    const ci = document.getElementById('bookCheckIn').value;
    const co = document.getElementById('bookCheckOut').value;
    if (ci && co) {
        const nights = calcNights(ci, co);
        const price = parseFloat(document.getElementById('bookPrice').value) || 0;
        const total = (nights * price).toFixed(2);
        document.getElementById('bookTotal').textContent = '$' + total;
        document.getElementById('bookingSummary').style.display = 'block';
    }
}

// Event: Search form submit
// Purpose: Find available rooms for selected dates
// API: GET /api/availableRooms?check_in=...&check_out=...&guests=...
document.getElementById('searchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const ci = document.getElementById('checkIn').value;
    const co = document.getElementById('checkOut').value;
    const g = document.getElementById('guests').value;
    const res = await apiGet(`availableRooms&check_in=${ci}&check_out=${co}&guests=${g}`);
    renderRooms(res.data.map(r => ({
        ...r,
        image: r.image || 'images/room-deluxe.jpg',
        available_rooms: 1,
    })));
});

// Event: Booking form submit
// Purpose: Create a reservation
// API: POST /api/reservation
// Input: room_type_id, full_name, email, phone, guests, check_in_date, check_out_date
document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        room_type_id: document.getElementById('bookRoomTypeId').value,
        full_name: document.getElementById('bookName').value,
        email: document.getElementById('bookEmail').value,
        phone: document.getElementById('bookPhone').value,
        guests: document.getElementById('bookGuests').value,
        check_in_date: document.getElementById('bookCheckIn').value,
        check_out_date: document.getElementById('bookCheckOut').value,
        special_requests: document.getElementById('bookRequests').value
    };
    const res = await apiPost('reservation', data);
    if (res.success) {
        // Show confirmation with reservation number
    }
});
```

---

### 2.3 js/api.js - API Helper Functions

**Purpose:** Communication between frontend and backend

```javascript
// Constant: API_BASE
// Purpose: Backend server URL
// Value: https://arizu-arimato.free.nf/backend/index.php
const API_BASE = 'https://arizu-arimato.free.nf/backend/index.php';

// Function: apiGet(endpoint)
// Purpose: Send GET request to backend API
// Input: endpoint name (e.g., 'rooms', 'availableRooms&check_in=...')
// Output: JSON response from backend
// Used by: loadRooms(), search form, settings
async function apiGet(endpoint) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url);
    return await res.json();
}

// Function: apiPost(endpoint, data)
// Purpose: Send POST request to backend API
// Input: endpoint name, data object
// Output: JSON response from backend
// Used by: booking form, contact form
async function apiPost(endpoint, data) {
    const url = `${API_BASE}?r=api/${endpoint}`;
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

// Function: formatPrice(amount, currency)
// Purpose: Format number as currency string
// Input: amount (50), currency ('USD')
// Output: "USD 50.00"
function formatPrice(amount, currency = 'USD') {
    return `${currency} ${parseFloat(amount).toFixed(2)}`;
}

// Function: calcNights(checkIn, checkOut)
// Purpose: Calculate number of nights between two dates
// Input: check-in date, check-out date
// Output: number of nights (minimum 1)
function calcNights(checkIn, checkOut) {
    const diff = new Date(checkOut) - new Date(checkIn);
    return Math.max(1, Math.ceil(diff / 86400000));
}
```

---

### 2.4 js/main.js - Animations & UI

**Purpose:** User interface effects and interactions

```javascript
// Event: Scroll
// Purpose: Change navbar appearance on scroll
// Trigger: User scrolls page
// Effect: Adds/removes 'scrolled' class after 50px
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

// Event: Hamburger click
// Purpose: Toggle mobile menu
// Trigger: User clicks hamburger icon
// Effect: Shows/hides navigation links
const hamburger = document.querySelector('.hamburger');
const navLinks = document.querySelector('.nav-links');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
}

// Event: Nav link click
// Purpose: Close mobile menu after navigation
// Trigger: User clicks a nav link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
    });
});

// Feature: Fade-in animation on scroll
// Purpose: Animate elements when they enter viewport
// Uses: IntersectionObserver API
// Effect: Elements fade in and slide up
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

// Feature: Gallery lightbox
// Purpose: Full-screen image view on click
// Trigger: User clicks gallery image
// Effect: Creates overlay with enlarged image
document.querySelectorAll('.gallery-item').forEach(item => {
    item.addEventListener('click', () => {
        const img = item.querySelector('img');
        const overlay = document.createElement('div');
        overlay.className = 'lightbox';
        overlay.innerHTML = `<img src="${img.src}" alt="${img.alt}">`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', () => overlay.remove());
    });
});

// Feature: Stats counter animation
// Purpose: Animate numbers counting up
// Trigger: Stats section enters viewport
// Effect: Numbers count from 0 to target value
const counters = document.querySelectorAll('.stat-number');
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const target = +entry.target.getAttribute('data-target');
            const suffix = entry.target.getAttribute('data-suffix') || '';
            let count = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                count += increment;
                if (count >= target) {
                    entry.target.textContent = target + suffix;
                    clearInterval(timer);
                } else {
                    entry.target.textContent = Math.floor(count) + suffix;
                }
            }, 30);
            counterObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });
counters.forEach(c => counterObserver.observe(c));
```

---

## 3. BACKEND CORE FILES

### 3.1 index.php - Entry Point

**Purpose:** Main entry point for all backend requests

**Flow:**
```
1. Load CORS headers (allows frontend to call API)
2. Load bootstrap.php (config + autoloader)
3. Check if system is installed (database tables exist)
4. If not installed, redirect to setup page
5. Get route from URL parameter (?r=controller/action)
6. Pass route to Router for dispatch
```

**Code:**
```php
<?php
// Step 1: Enable CORS
require __DIR__ . '/cors.php';

// Step 2: Load config and autoloader
require __DIR__ . '/bootstrap.php';

// Step 3: Check installation
$route = isset($_GET['r']) ? $_GET['r'] : '';
if (!isInstalled() && $route !== 'setup/index') {
    header('Location: index.php?r=setup/index');
    exit();
}

// Step 4: Route to controller
$router = new Router();
$router->dispatch($route);
```

---

### 3.2 config.php - Configuration & Helpers

**Purpose:** Database connection, authentication, utility functions

**Functions:**

```php
// Function: getDB()
// Purpose: Get database connection (PDO)
// Returns: PDO connection object (singleton)
// Used by: Every controller and model
function getDB() {
    static $conn = null;
    if ($conn !== null) return $conn;
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
}

// Function: isInstalled()
// Purpose: Check if database tables exist
// Returns: true if users table exists, false otherwise
// Used by: index.php to redirect to setup
function isInstalled() {
    try {
        $db = getDB();
        $db->query("SELECT 1 FROM users LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function: isLoggedIn()
// Purpose: Check if user is logged in
// Returns: true if user_id exists in session
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function: hasRole($roles)
// Purpose: Check if user has required role
// Input: role name or array of role names
// Returns: true if user's role matches
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (!is_array($roles)) $roles = [$roles];
    return in_array($_SESSION['user_role'], $roles);
}

// Function: requireLogin()
// Purpose: Redirect to login page if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?r=login/index');
        exit();
    }
}

// Function: requireRole($roles)
// Purpose: Require login AND specific role
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: index.php?r=dashboard/index');
        exit();
    }
}

// Function: generateReservationNumber()
// Purpose: Create unique reservation number
// Format: RES-YYYYMMDD-XXXX (e.g., RES-20260909-1234)
function generateReservationNumber() {
    return 'RES-' . date('Ymd') . '-' . rand(1000, 9999);
}

// Function: formatCurrency($amount)
// Purpose: Format amount as currency
// Input: 50.00
// Output: "USD 50.00"
function formatCurrency($amount) {
    $settings = getSystemSettings();
    $currency = $settings['currency'] ?? 'USD';
    return $currency . ' ' . number_format($amount, 2);
}

// Function: computeCheckinBill($db, $check_in_id, $tax_rate)
// Purpose: Calculate total bill for a stay
// Formula: (nights x nightly_rate) + extra_charges + tax
// Returns: Array with breakdown (nights, rate, room_charge, extra, tax, total)
function computeCheckinBill($db, $check_in_id, $tax_rate = 0) {
    // Get room rate and dates
    // Calculate nights
    // Get extra charges sum
    // Apply tax
    // Return bill breakdown
}

// Function: getSystemSettings()
// Purpose: Get hotel settings from database
// Returns: Array of settings (hotel_name, currency, tax_rate, etc.)
// Caches result for performance
function getSystemSettings() {
    static $settings = null;
    if ($settings !== null) return $settings;
    try {
        $db = getDB();
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $settings = ['currency' => 'USD', 'hotel_name' => 'Hotel Management System'];
    }
    return $settings;
}
```

---

### 3.3 Core/Router.php - URL Routing

**Purpose:** Convert URLs to controller methods

**How Routing Works:**
```
URL: index.php?r=room_types/index
Route: "room_types/index"
Controller: RoomTypeController
Action: indexAction()

URL: index.php?r=checkin/index
Route: "checkin/index"
Controller: CheckInController
Action: indexAction()
```

**Code:**
```php
class Router {
    public function dispatch($route) {
        // Step 1: Clean route
        $route = trim($route, '/');
        if ($route === '') $route = 'dashboard/index';
        
        // Step 2: Split route into parts
        $parts = explode('/', $route);
        $controllerRaw = $parts[0];  // e.g., "room_types"
        
        // Step 3: Convert to PascalCase
        // "room_types" -> "RoomType"
        // "checkin" -> "Checkin" -> "CheckIn" (fixed)
        $controller = ucwords(str_replace('_', ' ', strtolower($controllerRaw)));
        
        // Step 4: Remove trailing 's'
        // "RoomTypes" -> "RoomType"
        if (substr($controller, -1) === 's') {
            $controller = substr($controller, 0, -1);
        }
        
        // Step 5: Fix common names
        $controller = str_replace(['Checkin','Checkout'], ['CheckIn','CheckOut'], $controller);
        
        // Step 6: Build class name
        $class = 'App\\Controllers\\' . $controller . 'Controller';
        
        // Step 7: Check if class exists
        if (!class_exists($class)) {
            echo 'Controller not found: ' . $class;
            return;
        }
        
        // Step 8: Create controller instance
        $instance = new $class();
        
        // Step 9: Check authentication
        if ($instance->authRequired && !isLoggedIn()) {
            $instance->redirect('index.php?r=login/index');
        }
        
        // Step 10: Call action method
        $action = isset($parts[1]) ? $parts[1] : 'index';
        $method = $action . 'Action';
        $instance->$method();
    }
}
```

---

### 3.4 Core/Controller.php - Base Controller

**Purpose:** Parent class for all controllers, provides common methods

```php
abstract class Controller {
    public $authRequired = true;
    
    // Method: view($view, $data, $active, $pageTitle)
    // Purpose: Render a view with layout
    // Input: view name, data array, active menu item, page title
    // Effect: Outputs HTML with header, content, footer
    protected function view($view, array $data = [], $active = '', $pageTitle = '') {
        extract($data);  // Makes $data keys available as variables
        require __DIR__ . '/../Views/layout/header.php';
        require __DIR__ . '/../Views/' . $view . '.php';
        require __DIR__ . '/../Views/layout/footer.php';
    }
    
    // Method: redirect($url)
    // Purpose: Redirect to another page
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    // Method: jsonResponse($data, $statusCode)
    // Purpose: Send JSON response (for API)
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
        exit;
    }
    
    // Method: loginRequired()
    // Purpose: Require user to be logged in
    protected function loginRequired() {
        requireLogin();
    }
    
    // Method: roleRequired($roles)
    // Purpose: Require specific role
    protected function roleRequired($roles) {
        requireRole($roles);
    }
}
```

---

### 3.5 Core/Model.php - Base Model

**Purpose:** Parent class for all models, provides database connection

```php
class Model {
    protected $db;
    
    public function __construct() {
        $this->db = getDB();  // Get PDO connection
    }
}
```

---

## 4. CONTROLLERS - EVERY FUNCTION

### 4.1 LoginController

**Purpose:** Handle staff login/logout

```php
class LoginController extends Controller {
    public $authRequired = false;  // No login needed to access login page
    
    // Method: indexAction()
    // Purpose: Show login form (GET) or process login (POST)
    // GET: Renders login form
    // POST: Validates credentials, creates session, redirects to dashboard
    public function indexAction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            // Find user in database
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Redirect to dashboard
                header('Location: index.php?r=dashboard/index');
                exit();
            } else {
                $error = "Invalid username or password";
            }
        }
        
        require __DIR__ . '/../Views/login/index.php';
    }
}
```

---

### 4.2 LogoutController

**Purpose:** Destroy session and redirect to login

```php
class LogoutController extends Controller {
    public $authRequired = false;
    
    public function indexAction() {
        session_destroy();  // Remove all session data
        header('Location: index.php?r=login/index');
        exit();
    }
}
```

---

### 4.3 DashboardController

**Purpose:** Main admin dashboard with stats and charts

```php
class DashboardController extends Controller {
    // Method: indexAction()
    // Purpose: Show dashboard with statistics
    // Queries:
    //   - Total rooms count
    //   - Available rooms count
    //   - Occupied rooms count
    //   - Current guests count
    //   - Today's check-ins count
    //   - Today's check-outs count
    //   - Today's revenue
    //   - Monthly revenue
    //   - 7-day revenue (for bar chart)
    //   - Revenue by room type (for pie chart)
    //   - Recent reservations
    //   - Room status board
    public function indexAction() {
        $db = getDB();
        
        // Stats queries
        $totalRooms = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $available = $db->query("SELECT COUNT(*) FROM rooms WHERE status='Available'")->fetchColumn();
        $occupied = $db->query("SELECT COUNT(*) FROM rooms WHERE status='Occupied'")->fetchColumn();
        // ... more queries
        
        // Revenue chart data (last 7 days)
        $revenueChart = $db->query("SELECT DATE(payment_date), SUM(amount) FROM payments GROUP BY DATE(payment_date)")->fetchAll();
        
        // Room type distribution (pie chart)
        $roomTypeChart = $db->query("SELECT rt.type_name, COUNT(r.id) FROM room_types rt JOIN rooms r ON rt.id=r.room_type_id GROUP BY rt.id")->fetchAll();
        
        // Render view with data
        $this->view('dashboard/index', [
            'totalRooms' => $totalRooms,
            'available' => $available,
            // ... more data
        ], 'dashboard', 'Dashboard');
    }
}
```

---

### 4.4 RoomController

**Purpose:** Manage rooms (CRUD)

```php
class RoomController extends Controller {
    // Method: indexAction()
    // Purpose: List all rooms with filters
    // GET: Show rooms table
    // POST (action=add): Add new room
    // POST (action=edit): Update room
    // POST (action=delete): Delete room
    public function indexAction() {
        $db = getDB();
        
        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'];
            
            if ($action === 'add') {
                // Validate inputs
                // Insert into rooms table
                // Set status to 'Available'
            }
            
            if ($action === 'edit') {
                // Update room details
                // Update price, capacity, status
            }
            
            if ($action === 'delete') {
                // Check if room has active reservations
                // Delete if safe
            }
        }
        
        // Get all rooms with type names
        $rooms = $db->query("SELECT r.*, rt.type_name FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id")->fetchAll();
        
        // Get room types for dropdown
        $roomTypes = $db->query("SELECT * FROM room_types")->fetchAll();
        
        $this->view('rooms/index', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
        ], 'rooms', 'Room Management');
    }
}
```

---

### 4.5 RoomTypeController

**Purpose:** Manage room types (CRUD)

```php
class RoomTypeController extends Controller {
    // Method: indexAction()
    // Purpose: List all room types with room counts
    // POST (action=add): Add new room type
    // POST (action=edit): Update room type
    // POST (action=delete): Delete room type
    public function indexAction() {
        $db = getDB();
        
        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'];
            
            if ($action === 'add') {
                // Insert room type with name, description, price, capacity
            }
            
            if ($action === 'edit') {
                // Update room type details
                // Also update all rooms of this type
            }
            
            if ($action === 'delete') {
                // Check if rooms exist for this type
                // Delete if safe
            }
        }
        
        // Get room types with room counts
        $types = $db->query("SELECT rt.*, COUNT(r.id) as room_count FROM room_types rt LEFT JOIN rooms r ON rt.id=r.room_type_id GROUP BY rt.id")->fetchAll();
        
        $this->view('room_types/index', [
            'types' => $types,
        ], 'room_types', 'Room Types');
    }
}
```

---

### 4.6 GuestController

**Purpose:** Manage guest profiles (CRUD)

```php
class GuestController extends Controller {
    // Method: indexAction()
    // Purpose: List all guests
    // POST: Add/Edit/Delete guest
    
    // Method: viewAction()
    // Purpose: View guest details and stay history
    // Called via AJAX when clicking "View" button
    public function viewAction() {
        $id = (int)$_GET['id'];
        $guest = $db->prepare("SELECT * FROM guests WHERE id=?")->execute([$id])->fetch();
        $stays = $db->prepare("SELECT * FROM check_ins WHERE guest_id=?")->execute([$id])->fetchAll();
        
        // Return HTML fragment
        require __DIR__ . '/../Views/guests/view.php';
    }
}
```

---

### 4.7 ReservationController

**Purpose:** Manage reservations (CRUD)

```php
class ReservationController extends Controller {
    // Method: indexAction()
    // Purpose: List all reservations with filters
    // Filters: status (Pending/Confirmed/Cancelled/Completed), search by name/number
    
    // POST actions:
    // - add: Create new reservation
    //   1. Check room availability
    //   2. Calculate nights and total
    //   3. Insert reservation record
    //   
    // - edit: Update reservation
    //   1. Update dates, guests, status
    //   2. Recalculate total
    //   
    // - cancel: Cancel reservation
    //   1. Set status to 'Cancelled'
    //   2. Release room
    //   
    // - delete: Delete reservation
    //   1. Check for active check-in
    //   2. Delete if safe
}
```

---

### 4.8 CheckInController

**Purpose:** Handle guest check-in

```php
class CheckInController extends Controller {
    // Method: indexAction()
    // Purpose: Show check-in page with ready reservations and active stays
    // Two sections:
    //   1. Reservations ready for check-in (have room, no active check-in)
    //   2. Currently checked-in guests
    
    // POST actions:
    // - checkin: Check in a reservation
    //   1. BEGIN TRANSACTION
    //   2. Create check_ins record (status='Active')
    //   3. Update room status to 'Occupied'
    //   4. COMMIT
    //   
    // - walkin: Walk-in check-in
    //   1. Create guest record (if new)
    //   2. Create reservation (status='Confirmed')
    //   3. Create check_ins record
    //   4. Update room status to 'Occupied'
    
    // Method: availRooms()
    // Purpose: Get available rooms for walk-in modal
    private function availRooms() {
        return $db->query("SELECT id, room_number, price_per_night FROM rooms WHERE status IN ('Available','Cleaning')")->fetchAll();
    }
}
```

---

### 4.9 CheckOutController

**Purpose:** Handle guest check-out and payment

```php
class CheckOutController extends Controller {
    // Method: indexAction()
    // Purpose: Show active stays with bills
    // Each stay shows:
    //   - Guest name
    //   - Room number
    //   - Check-in/out dates
    //   - Bill breakdown (room charge + extras + tax)
    
    // POST (action=checkout):
    //   1. Add extra charge (if any)
    //   2. BEGIN TRANSACTION
    //   3. Update check_ins status to 'Checked Out'
    //   4. Update reservation status to 'Completed'
    //   5. Update room status to 'Cleaning'
    //   6. Create payment record
    //   7. Generate invoice number
    //   8. COMMIT
    //   9. Return receipt data
}
```

---

### 4.10 BillingController

**Purpose:** Payment history and receipts

```php
class BillingController extends Controller {
    // Method: indexAction()
    // Purpose: List all payments with filters
    // Filters: search, payment method, date range
    // Shows: paginated list (15 per page)
    
    // Method: receiptAction()
    // Purpose: Generate receipt HTML (AJAX)
    // Shows: full billing breakdown
    
    // Method: printAction()
    // Purpose: Printable receipt page
    
    // Method: exportAction()
    // Purpose: Export payments to CSV
}
```

---

### 4.11 ReportController

**Purpose:** Revenue and occupancy reports

```php
class ReportController extends Controller {
    // Method: indexAction()
    // Purpose: Generate reports with charts
    // Metrics:
    //   - Occupancy rate
    //   - ADR (Average Daily Rate)
    //   - RevPAR (Revenue Per Available Room)
    //   - Period revenue
    //   - Total revenue
    // Charts:
    //   - 6-month revenue line chart
    //   - Revenue by room type pie chart
    //   - Top 5 guests table
}
```

---

### 4.12 HousekeepingController

**Purpose:** Room cleaning management

```php
class HousekeepingController extends Controller {
    // Method: indexAction()
    // Purpose: List rooms needing cleaning
    // Shows: rooms with status='Cleaning'
    // Action: Mark room as 'Available' when cleaned
}
```

---

### 4.13 MaintenanceController

**Purpose:** Room maintenance management

```php
class MaintenanceController extends Controller {
    // Method: indexAction()
    // Purpose: Toggle maintenance status
    // Shows: all rooms with maintenance toggle
    // Action: Set room status to 'Maintenance' or 'Available'
}
```

---

### 4.14 UserController

**Purpose:** Staff user management (admin only)

```php
class UserController extends Controller {
    // Roles: Administrator only
    
    // Method: indexAction()
    // Purpose: List all staff users
    // POST: Add/Edit/Delete users
    // Features:
    //   - Password hashing (password_hash/password_verify)
    //   - Role assignment (Administrator, Receptionist, Manager, etc.)
    //   - Prevent self-deletion
    //   - Username/email uniqueness validation
}
```

---

### 4.15 SettingController

**Purpose:** Hotel settings and database management

```php
class SettingController extends Controller {
    // Roles: Administrator only
    
    // Method: indexAction()
    // Purpose: Hotel settings form
    // Settings: name, address, phone, email, currency, tax_rate
    
    // POST actions:
    // - save: Update settings in system_settings table
    // - backup: Generate .sql backup file
    // - restore: Upload and execute .sql file
    // - delete_backup: Remove backup file
}
```

---

### 4.16 NotificationController

**Purpose:** Generate activity alerts

```php
class NotificationController extends Controller {
    // Method: indexAction()
    // Purpose: Show notifications based on current data
    // Notifications:
    //   - Today's check-ins (reservations with check_in_date = today)
    //   - Today's check-outs
    //   - Rooms needing cleaning
    //   - Rooms under maintenance
    //   - Pending reservations
    //   - Today's revenue total
}
```

---

### 4.17 ApiController - Public API

**Purpose:** Bridge between frontend website and backend database

```php
class ApiController extends Controller {
    public $authRequired = false;  // Public access
    
    // Method: roomsAction()
    // Purpose: Get all room types
    // GET ?r=api/rooms
    // Returns: JSON with room types, prices, availability
    // SQL: JOIN room_types with rooms, count available
    
    // Method: viewAction()
    // Purpose: Get single room type with rooms
    // GET ?r=api/rooms/view&id=X
    
    // Method: availableRoomsAction()
    // Purpose: Search available rooms for dates
    // GET ?r=api/availableRooms&check_in=...&check_out=...&guests=...
    // SQL: Complex query checking reservations + check_ins for overlaps
    
    // Method: contactAction()
    // Purpose: Submit contact form
    // POST ?r=api/contact
    // Input: full_name, email, phone, subject, message
    
    // Method: reservationAction()
    // Purpose: Create guest reservation
    // POST ?r=api/reservation
    // Input: room_type_id, full_name, email, phone, guests, dates
    // Process:
    //   1. Find available room
    //   2. Create/find guest
    //   3. Calculate total
    //   4. Create reservation
    
    // Method: settingsAction()
    // Purpose: Get public hotel settings
    // GET ?r=api/settings
    
    // Method: addRoomAction()
    // Purpose: Add room type + room (admin)
    // POST ?r=api/addRoom
    
    // Method: editRoomAction()
    // Purpose: Edit room type (admin)
    
    // Method: deleteRoomAction()
    // Purpose: Delete room type (admin)
    
    // Private: getRoomTypeImage($typeName)
    // Purpose: Map room type name to image file
    // "VIP" -> images/room-vip.jpg
    // "Suite/Family" -> images/room-suite.jpg
    // "Deluxe/Premier" -> images/room-deluxe.jpg
    
    // Private: getRoomDescription($typeName, $capacity)
    // Purpose: Generate description based on room type
}
```

---

### 4.18 SetupController

**Purpose:** First-time database installation

```php
class SetupController extends Controller {
    public $authRequired = false;
    
    // Method: indexAction()
    // Purpose: Install database tables
    // Process:
    //   1. Connect to database
    //   2. Read hotel_clean.sql file
    //   3. Execute each CREATE TABLE statement
    //   4. Insert default admin user (admin/admin123)
    //   5. Insert default settings
    //   6. Show success message
}
```

---

## 5. MODELS - EVERY FUNCTION

### 5.1 Room Model
```php
class Room extends Model {
    // setStatus($roomId, $status)
    // Purpose: Update room status
    // Statuses: Available, Reserved, Occupied, Cleaning, Maintenance
}
```

### 5.2 RoomType Model
```php
class RoomType extends Model {
    // All functions inherited from Model
    // Uses $this->db for queries
}
```

### 5.3 Guest Model
```php
class Guest extends Model {
    // all()
    // Purpose: Get all guests
    
    // find($id)
    // Purpose: Get single guest
}
```

### 5.4 Reservation Model
```php
class Reservation extends Model {
    // roomIsAvailable($roomId, $checkIn, $checkOut)
    // Purpose: Check if room is free for dates
    // Checks: reservations table + check_ins table
    
    // roomNightlyRate($roomId)
    // Purpose: Get room price per night
}
```

### 5.5 CheckIn Model
```php
class CheckIn extends Model {
    // readyReservations()
    // Purpose: Get reservations ready for check-in
    // Criteria: Pending/Confirmed, has room, no active check-in
    
    // activeStays()
    // Purpose: Get currently checked-in guests
    
    // activeStaysForCheckout()
    // Purpose: Get stays with bill info for checkout
    
    // insert($reservationId, $guestId, $roomId, $expectedOut, $userId)
    // Purpose: Create check-in record
    
    // markCheckedOut($checkInId)
    // Purpose: Update check-in status to 'Checked Out'
    
    // extraChargesTotal($checkInId)
    // Purpose: Get sum of extra charges
    
    // addExtraCharge($checkInId, $type, $desc, $amount)
    // Purpose: Add room service, laundry, etc.
    
    // checkoutDetail($checkInId)
    // Purpose: Get full checkout info with guest, room, reservation details
}
```

### 5.6 User Model
```php
class User extends Model {
    // All functions inherited from Model
}
```

---

## 6. VIEWS - TEMPLATES

### Layout Files:
- **header.php**: Sidebar navigation, Bootstrap CSS, logout modal
- **footer.php**: Closes HTML, loads Bootstrap JS

### Page Views:
- **login/index.php**: Standalone login form (no sidebar)
- **setup/index.php**: Database installer
- **dashboard/index.php**: Stats cards, Chart.js charts, room board
- **rooms/index.php**: Room table/board, add/edit modals
- **room_types/index.php**: Room type CRUD table
- **guests/index.php**: Guest CRUD table, view modal
- **reservations/index.php**: Reservation CRUD table
- **checkin/index.php**: Ready reservations + walk-in modal
- **checkout/index.php**: Active stays + checkout payment modal
- **billing/index.php**: Payment list, receipt modal
- **reports/index.php**: Revenue charts, CSV export
- **housekeeping/index.php**: Cleaning rooms list
- **maintenance/index.php**: Maintenance toggle
- **users/index.php**: Staff CRUD table
- **settings/index.php**: Hotel settings + backup/restore
- **notifications/index.php**: Activity alerts

---

## 7. DATABASE TABLES

| Table | Columns | Purpose |
|-------|---------|---------|
| users | id, username, password, full_name, email, phone, role, status | Staff accounts |
| room_types | id, type_name, description, price_per_night, capacity | Room categories |
| rooms | id, room_number, room_type_id, floor, capacity, price_per_night, status | Individual rooms |
| guests | id, full_name, gender, date_of_birth, nationality, phone, email, address, identification_number | Guest profiles |
| reservations | id, reservation_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, status, total_amount | Bookings |
| check_ins | id, reservation_id, guest_id, room_id, check_in_time, expected_check_out, actual_check_out, status | Physical stays |
| payments | id, reservation_id, check_in_id, amount, payment_method, transaction_id, invoice_number | Transactions |
| extra_charges | id, check_in_id, charge_type, description, amount | Additional fees |
| system_settings | id, setting_key, setting_value | Hotel config |
| contacts | id, full_name, email, phone, subject, message | Contact form |

---

## 8. PROCESS FLOWS

### Flow 1: Guest Books Room (Frontend)
```
1. Guest opens rooms.html
2. JavaScript calls apiGet('rooms')
3. API returns room types
4. Guest selects dates, clicks Search
5. JavaScript calls apiGet('availableRooms&...')
6. API checks availability
7. Guest clicks "Book Now"
8. Guest fills form, submits
9. JavaScript calls apiPost('reservation', data)
10. API creates reservation
11. Guest sees confirmation
```

### Flow 2: Staff Check-In
```
1. Staff logs in
2. Goes to Check-in page
3. Sees ready reservations
4. Clicks "Check In"
5. Backend creates check_in record
6. Room status changes to "Occupied"
```

### Flow 3: Staff Check-Out
```
1. Staff goes to Check-out page
2. Sees active stays with bills
3. Clicks "Payment & Check-out"
4. Adds extra charges (if any)
5. Selects payment method
6. Backend processes payment
7. Room status changes to "Cleaning"
8. Receipt is generated
```

### Flow 4: Database Installation
```
1. User visits backend
2. System detects no tables
3. Redirects to setup page
4. User clicks "Install Database"
5. System creates all tables
6. Default admin account created
7. User can now login
```

---

## 9. SIMPLE FILE GUIDE (For Beginners)

### What is Each File?

#### 📁 Frontend Files (What Users See)

| File | Lines | What It Does (Simple) |
|------|-------|----------------------|
| `index.html` | 500+ | Homepage - shows hotel logo, rooms, contact info |
| `about.html` | 200+ | About page - tells hotel story |
| `rooms.html` | 400+ | Rooms page - shows prices, lets users book |
| `gallery.html` | 150+ | Photos page - shows hotel pictures |
| `contact.html` | 200+ | Contact page - form to send messages |
| `css/style.css` | 830+ | Styling - makes website look nice |
| `js/api.js` | 100+ | API calls - talks to backend server |
| `js/main.js` | 200+ | Animations - scroll effects, mobile menu |

#### 📁 Backend Files (Admin Panel)

| File | Lines | What It Does (Simple) |
|------|-------|----------------------|
| `index.php` | 24 | Entry point - starts the whole system |
| `config.php` | 131 | Settings - database password, helper functions |
| `bootstrap.php` | 19 | Autoloader - loads files when needed |
| `cors.php` | 10 | Security - allows website to talk to server |
| `.htaccess` | 8 | URL rewriting - makes URLs pretty |

#### 📁 Core Files (Framework)

| File | Lines | What It Does (Simple) |
|------|-------|----------------------|
| `Core/Router.php` | 61 | Traffic cop - sends URLs to correct page |
| `Core/Controller.php` | 65 | Parent class - gives all controllers superpowers |
| `Core/Model.php` | 15 | Database parent - connects to MySQL |

#### 📁 Controllers (Business Logic)

| File | Lines | What It Does (Simple) |
|------|-------|----------------------|
| `LoginController.php` | 50 | Login page - checks username/password |
| `LogoutController.php` | 20 | Logout - ends user session |
| `DashboardController.php` | 150 | Dashboard - shows stats, charts, room board |
| `RoomController.php` | 100 | Rooms - add/edit/delete rooms |
| `RoomTypeController.php` | 80 | Room types - manage room categories |
| `GuestController.php` | 100 | Guests - manage customer profiles |
| `ReservationController.php` | 120 | Reservations - manage bookings |
| `CheckInController.php` | 130 | Check-in - register guest arrival |
| `CheckOutController.php` | 140 | Check-out - process payments, release rooms |
| `BillingController.php` | 100 | Billing - payment history, receipts |
| `ReportController.php` | 100 | Reports - revenue charts, occupancy rates |
| `HousekeepingController.php` | 50 | Housekeeping - room cleaning status |
| `MaintenanceController.php` | 50 | Maintenance - room repairs |
| `UserController.php` | 100 | Users - manage staff accounts |
| `SettingController.php` | 100 | Settings - hotel name, currency, backup |
| `NotificationController.php` | 60 | Notifications - alerts for staff |
| `ApiController.php` | 200 | API - public interface for website |
| `SetupController.php` | 80 | Setup - first-time database installation |

#### 📁 Models (Database Queries)

| File | Lines | What It Does (Simple) |
|------|-------|----------------------|
| `Room.php` | 30 | Room queries - get/set room data |
| `RoomType.php` | 20 | Room type queries |
| `Guest.php` | 40 | Guest queries - find/add guests |
| `Reservation.php` | 60 | Reservation queries - check availability |
| `CheckIn.php` | 100 | Check-in queries - manage stays |
| `User.php` | 20 | User queries |

#### 📁 Views (HTML Templates)

| File | What It Does (Simple) |
|------|----------------------|
| `layout/header.php` | Sidebar menu, top bar, logout button |
| `layout/footer.php` | Closes HTML, loads Bootstrap JS |
| `login/index.php` | Login form page |
| `setup/index.php` | Database installer page |
| `dashboard/index.php` | Stats cards, charts, room board |
| `rooms/index.php` | Room table with add/edit buttons |
| `room_types/index.php` | Room type table |
| `guests/index.php` | Guest list table |
| `reservations/index.php` | Reservation list |
| `checkin/index.php` | Check-in page with walk-in modal |
| `checkout/index.php` | Check-out with payment modal |
| `billing/index.php` | Payment list |
| `reports/index.php` | Revenue charts |
| `housekeeping/index.php` | Cleaning rooms list |
| `maintenance/index.php` | Maintenance toggle |
| `users/index.php` | Staff user table |
| `settings/index.php` | Hotel settings form |
| `notifications/index.php` | Activity alerts |

---

## 10. DATABASE EXPLAINED (Table by Table)

### Table: `users` (Staff Accounts)
```
id              - Unique number for each staff
username        - Login name (e.g., "admin")
password        - Encrypted password (never plain text!)
full_name       - Staff member's full name
email           - Email address
phone           - Phone number
role            - Job title (Administrator, Receptionist, etc.)
status          - Active or Inactive
created_at      - When account was created
updated_at      - When account was last changed
```

### Table: `room_types` (Room Categories)
```
id              - Unique number
type_name       - Category name (e.g., "VIP Room")
description     - What's included in this room
price_per_night - Cost per night (e.g., 100.00)
capacity        - How many guests (e.g., 5)
created_at      - When category was added
```

### Table: `rooms` (Individual Rooms)
```
id              - Unique number
room_number     - Room number (e.g., "101", "VIP001")
room_type_id    - Which category (links to room_types.id)
floor           - Which floor (1, 2, 3...)
capacity        - Max guests
price_per_night - Cost per night
description     - Room details
status          - Available, Reserved, Occupied, Cleaning, Maintenance
image           - Photo filename
created_at      - When room was added
```

### Table: `guests` (Customer Profiles)
```
id              - Unique number
full_name       - Guest's full name
gender          - Male, Female, Other
date_of_birth   - Birthday
nationality     - Country
phone           - Phone number
email           - Email address
address         - Home address
identification_number - Passport or ID number
created_at      - When profile was created
```

### Table: `reservations` (Bookings)
```
id              - Unique number
reservation_number - Booking code (e.g., "RES-20260909-1234")
guest_id        - Which guest (links to guests.id)
room_id         - Which room (links to rooms.id)
check_in_date   - Arrival date
check_out_date  - Departure date
number_of_guests - How many people
status          - Pending, Confirmed, Cancelled, Completed
special_requests - Special notes (e.g., "extra pillows")
total_amount    - Total cost
created_by      - Staff who created booking
created_at      - When booking was made
```

### Table: `check_ins` (Physical Check-ins)
```
id              - Unique number
reservation_id  - Which booking (links to reservations.id)
guest_id        - Which guest
room_id         - Which room
check_in_time   - Actual arrival time
expected_check_out - When they should leave
actual_check_out - When they actually left
status          - Active or Checked Out
created_by      - Staff who checked them in
created_at      - When check-in was processed
```

### Table: `payments` (Transactions)
```
id              - Unique number
reservation_id  - Which booking
check_in_id     - Which stay
amount          - Amount paid
payment_method  - Cash, Card, QR Payment, Bank Transfer
transaction_id  - Bank transaction reference
payment_date    - When payment was made
invoice_number  - Receipt number
```

### Table: `extra_charges` (Additional Fees)
```
id              - Unique number
check_in_id     - Which stay
charge_type     - Room Service, Laundry, Restaurant, Mini Bar, Damage Fee, Other
description     - What was charged
amount          - How much
created_at      - When charge was added
```

### Table: `contacts` (Contact Form)
```
id              - Unique number
full_name       - Sender's name
email           - Sender's email
phone           - Sender's phone
subject         - Message subject
message         - Message content
is_read         - 0 = unread, 1 = read
created_at      - When message was sent
```

### Table: `system_settings` (Hotel Config)
```
id              - Unique number
setting_key     - Setting name (e.g., "hotel_name")
setting_value   - Setting value (e.g., "Arizu Arimato")
updated_at      - When setting was changed
```

---

## 11. URL ROUTING GUIDE

### How URLs Work

```
https://arizu-arimato.free.nf/backend/index.php?r=ROOMS/INDEX
                                               └─────────────┘
                                                    │
                                            What page to show

Examples:
?r=dashboard/index     → Shows Dashboard
?r=rooms/index         → Shows Room Management
?r=room_types/index    → Shows Room Types
?r=guests/index        → Shows Guest Management
?r=reservations/index  → Shows Reservations
?r=checkin/index       → Shows Check-in Page
?r=checkout/index      → Shows Check-out Page
?r=billing/index       → Shows Billing
?r=reports/index       → Shows Reports
?r=users/index         → Shows Staff Users
?r=settings/index      → Shows Settings
?r=login/index         → Shows Login Page
?r=setup/index         → Shows Database Setup
?r=api/rooms           → API: Get Room Types
?r=api/reservation     → API: Create Booking
?r=api/contact         → API: Submit Contact Form
```

---

## 12. API ENDPOINTS

### Public API (No Login Required)

| Endpoint | Method | What It Does |
|----------|--------|--------------|
| `?r=api/rooms` | GET | Get all room types |
| `?r=api/rooms/view&id=X` | GET | Get single room type |
| `?r=api/availableRooms&check_in=...&check_out=...&guests=...` | GET | Search available rooms |
| `?r=api/settings` | GET | Get hotel settings |
| `?r=api/reservation` | POST | Create a booking |
| `?r=api/contact` | POST | Submit contact form |
| `?r=api/addRoom` | POST | Add room type (admin) |
| `?r=api/editRoom` | POST | Edit room type (admin) |
| `?r=api/deleteRoom` | POST | Delete room type (admin) |

### Request/Response Format

**Request (JSON):**
```json
{
    "room_type_id": 2,
    "full_name": "John Smith",
    "email": "john@email.com",
    "phone": "1234567890",
    "guests": 2,
    "check_in_date": "2026-09-15",
    "check_out_date": "2026-09-18"
}
```

**Response (JSON):**
```json
{
    "success": true,
    "message": "Reservation created successfully",
    "data": {
        "reservation_number": "RES-20260909-1234",
        "total_amount": 300.00
    }
}
```

---

## 13. CONFIGURATION GUIDE

### Database Config (config.php)

```php
// Local Development (WAMP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotelsystem');

// Production (InfinityFree)
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_42865773');
define('DB_PASS', 'YIfvzeJH4d');
define('DB_NAME', 'if0_42865773_hotel_management_system');
```

### How to Switch Between Local and Production

1. Open `backend/config.php`
2. Change database credentials
3. Save file

---

## 14. COMMON TASKS

### How to Add a New Room Type
1. Login to admin panel
2. Go to Room Types page
3. Click "Add Room Type"
4. Fill in name, description, price, capacity
5. Click Save

### How to Add a New Room
1. Login to admin panel
2. Go to Rooms page
3. Click "Add Room"
4. Enter room number, select type, floor
5. Click Save

### How to Check In a Guest
1. Login to admin panel
2. Go to Check-in page
3. Find the reservation
4. Click "Check In"
5. Confirm details

### How to Check Out a Guest
1. Login to admin panel
2. Go to Check-out page
3. Find the active stay
4. Add any extra charges
5. Select payment method
6. Click "Payment & Check-out"

### How to Backup Database
1. Login to admin panel
2. Go to Settings page
3. Click "Backup Database"
4. Download the .sql file

### How to Restore Database
1. Login to admin panel
2. Go to Settings page
3. Click "Choose File"
4. Select your .sql backup file
5. Click "Restore"
