# BACKEND CORE FILES EXPLAINED
## What Each File Does

---

## 1. index.php - Entry Point

**Purpose:** Start the whole system

**What It Does:**
1. Loads CORS headers (allows frontend to call API)
2. Loads config.php (database settings)
3. Loads bootstrap.php (autoloader)
4. Checks if database is installed
5. If not installed → redirects to setup page
6. Gets URL route (?r=controller/action)
7. Sends route to Router

**Code Flow:**
```
User visits: index.php?r=dashboard/index
                    ↓
index.php loads config + bootstrap
                    ↓
Checks if installed
                    ↓
Router dispatches to DashboardController
                    ↓
DashboardController runs indexAction()
                    ↓
Dashboard page is displayed
```

**Lines:** 24

---

## 2. config.php - Configuration

**Purpose:** Database connection and helper functions

**What It Contains:**

### Database Settings
```php
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');           // Database username
define('DB_PASS', '');               // Database password
define('DB_NAME', 'hotelsystem');    // Database name
```

### Helper Functions

**getDB()** - Get database connection
```php
function getDB() {
    // Returns PDO connection to MySQL
    // Used by every controller and model
}
```

**isInstalled()** - Check if tables exist
```php
function isInstalled() {
    // Returns true if users table exists
    // Used by index.php to redirect to setup
}
```

**isLoggedIn()** - Check if user is logged in
```php
function isLoggedIn() {
    // Returns true if user_id in session
}
```

**hasRole($roles)** - Check user role
```php
function hasRole($roles) {
    // Returns true if user has required role
    // Example: hasRole(['Administrator'])
}
```

**requireLogin()** - Force login
```php
function requireLogin() {
    // Redirects to login page if not logged in
}
```

**requireRole($roles)** - Force specific role
```php
function requireRole($roles) {
    // Requires login AND specific role
}
```

**generateReservationNumber()** - Create booking code
```php
function generateReservationNumber() {
    // Returns: "RES-20260909-1234"
}
```

**formatCurrency($amount)** - Format money
```php
function formatCurrency($amount) {
    // Returns: "USD 50.00"
}
```

**getSystemSettings()** - Get hotel settings
```php
function getSystemSettings() {
    // Returns: hotel_name, currency, tax_rate, etc.
}
```

**Lines:** 131

---

## 3. bootstrap.php - Autoloader

**Purpose:** Load files automatically

**What It Does:**
- Defines namespace for controllers
- Loads Core files (Router, Controller, Model)
- Loads Config file

**How Autoloading Works:**
```php
spl_autoload_register(function ($class) {
    // When you use a class, PHP automatically loads the file
    // Example: use App\Controllers\DashboardController;
    // PHP loads: Controllers/DashboardController.php
});
```

**Lines:** 19

---

## 4. cors.php - Cross-Origin Headers

**Purpose:** Allow frontend to talk to backend

**What It Does:**
```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
```

**Why Needed:**
- Frontend is on GitHub Pages
- Backend is on InfinityFree
- Different domains = need CORS headers

**Lines:** 10

---

## 5. .htaccess - URL Rewriting

**Purpose:** Make URLs pretty

**What It Does:**
```
Before: index.php?r=dashboard/index
After:  index.php/r/dashboard/index
```

**Code:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?r=$1 [QSA,L]
```

**Lines:** 8

---

## 6. Core/Router.php - URL Routing

**Purpose:** Send URLs to correct controller

**How Routing Works:**
```
URL: index.php?r=room_types/index
                    ↓
Route: "room_types/index"
                    ↓
Split: controller="room_types", action="index"
                    ↓
Convert: "room_types" → "RoomType"
                    ↓
Add suffix: "RoomType" → "RoomTypeController"
                    ↓
Full class: "App\Controllers\RoomTypeController"
                    ↓
Method: "index" → "indexAction()"
```

**Code Flow:**
```php
class Router {
    public function dispatch($route) {
        // 1. Clean route
        // 2. Split into parts
        // 3. Convert to PascalCase
        // 4. Remove trailing 's'
        // 5. Fix common names (checkin → CheckIn)
        // 6. Build class name
        // 7. Check if class exists
        // 8. Create controller instance
        // 9. Check authentication
        // 10. Call action method
    }
}
```

**Lines:** 61

---

## 7. Core/Controller.php - Base Controller

**Purpose:** Parent class for all controllers

**What It Provides:**
```php
abstract class Controller {
    public $authRequired = true;
    
    // Render a view
    protected function view($view, $data, $active, $pageTitle) {
        // Extracts data into variables
        // Loads header.php
        // Loads view file
        // Loads footer.php
    }
    
    // Redirect to another page
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    // Send JSON response (for API)
    protected function jsonResponse($data, $statusCode) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    // Require login
    protected function loginRequired() {
        requireLogin();
    }
    
    // Require specific role
    protected function roleRequired($roles) {
        requireRole($roles);
    }
}
```

**Lines:** 65

---

## 8. Core/Model.php - Base Model

**Purpose:** Parent class for all models

**What It Provides:**
```php
class Model {
    protected $db;
    
    public function __construct() {
        $this->db = getDB();  // Get database connection
    }
}
```

**Lines:** 15

---

## SUMMARY

| File | Purpose | Lines |
|------|---------|-------|
| index.php | Entry point | 24 |
| config.php | Configuration + helpers | 131 |
| bootstrap.php | Autoloader | 19 |
| cors.php | CORS headers | 10 |
| .htaccess | URL rewriting | 8 |
| Core/Router.php | URL routing | 61 |
| Core/Controller.php | Base controller | 65 |
| Core/Model.php | Base model | 15 |
