# FILE GUIDE - Every File Explained

Plain-language explanation of every file in this project, grouped by folder.
Use this together with `CODE_EXPLANATIONS/` (deep dives) and `USER_MANUAL.md` (how to use the system).

---

## 1. Project Overview - How the Pieces Fit

```
Browser
  |
  |-- Guest opens the public website (frontend/) -- static HTML/CSS/JS
  |        \-- js/api.js sends JSON requests ---> backend/index.php?r=api/rooms ...
  |
  \-- Staff logs into the admin panel (backend/) -- PHP pages rendered on the server
           \-- index.php -> Router -> Controller -> Model -> MySQL -> View (HTML)
```

There are **3 main parts**:

1. **Public website** (`frontend/`) - what guests see.
2. **Admin system** (`backend/`) - what staff use.
3. **Tests + docs** (`tests/`, `*.md`) - the assignment deliverables.

---

## 2. Root Files (documentation and config)

| File | What it does |
|------|--------------|
| `index.html` | Tiny page at the site root. It instantly **redirects visitors to `frontend/`** so GitHub Pages shows the website instead of a blank page. |
| `_config.yml` | GitHub Pages (Jekyll) config. Its `exclude:` list keeps `backend/` (with database passwords), `tests/`, `backups/` and the SQL dump **off the public website** - important for security. |
| `README.md` | The project's front page on GitHub: live demo link, install steps (WAMP, login admin/admin123), and the team branch workflow. |
| `TESTING.md` | **Testing deliverable (5 pts).** Test tables in the assignment format (Test case -> Steps -> Expected -> Actual -> Pass/Fail), covering Unit, Integration and System tests, plus the bug log. |
| `USER_MANUAL.md` | **Part of the Documentation deliverable.** Step-by-step guide for guests (booking a room) and staff (check-in, billing, etc.). |
| `FILE_GUIDE.md` | This file - a short explanation of every file in the repo. |
| `ASSIGNMENT_CRITERIA_P19.md` | The teacher's rubric copied into the repo, with the self-scoring checklist (Testing/Deployment/Documentation). |
| `CONTRIBUTING.md` | Team rules for 4 members: branch model (`main` <- `develop` <- feature branches), how to make a Pull Request, code style. |
| `CLIENT_DEPLOYMENT.md` | Guide for deploying to **paid hosting** (Hostinger etc.) - for when a real hotel client needs the system online. |
| `FREE_HOSTING.md` | Guide for deploying **free** (InfinityFree), which is what is used for the PHP backend. |
| `CODE_EXPLANATION.md` | One big document explaining the whole codebase: structure, every controller/model function, database tables, process flows. |
| `CODE_EXPLANATIONS/` | The same material **split into 15 short files** (see below) - easier to study piece by piece. |
| `hotel_erd.html` | The **Entity-Relationship Diagram** of the database, drawn in HTML - open it in a browser to see tables and their relationships. |
| `hotel_management_1_2.sql` | The full database **schema + sample data** as one SQL file. Import this into phpMyAdmin to create the database. |

### Inside `CODE_EXPLANATIONS/`

| File | Topic |
|------|-------|
| `00_INDEX.md` | Table of contents / links to all others |
| `01_FRONTEND.md` | The public website files |
| `02_BACKEND_CORE.md` | Router, Controller, Model base classes |
| `03_CONTROLLERS.md` | Every controller function explained |
| `04_MODELS.md` | Every model function explained |
| `05_VIEWS.md` | The PHP page templates |
| `06_DATABASE.md` | Every table and column |
| `07_API.md` | The JSON API endpoints used by the frontend |
| `08_ROUTING.md` | How a URL like `?r=rooms/index` finds the right code |
| `09_CONFIG.md` | Configuration and database connection |
| `10_PROCESS_FLOWS.md` | Booking -> Check-in -> Checkout -> Billing flows |
| `11_SIMPLE_GUIDE.md` | Beginner-friendly explanation |
| `12_URL_GUIDE.md` | Examples of every URL in the system |
| `13_GRADING_GUIDE.md` | Maps files to assignment grading criteria |
| `14_GITHUB_GUIDE.md` | How to use GitHub with this project |

---

## 3. `frontend/` - The Public Website (guests)

Static HTML/CSS/JS. Guests can browse rooms, check availability, book, and send a message - **no PHP needed**, which is why it runs on GitHub Pages.

| File | What it does |
|------|--------------|
| `index.html` | Homepage: hero banner, amenities, featured rooms (loaded live from the API), statistics counters, footer. |
| `rooms.html` | **The booking page.** Lists all rooms, has a date-range search ("available rooms"), and the booking form that sends reservations to the backend. |
| `about.html` | Static "About Us" page (hotel story, mission). No API calls. |
| `gallery.html` | Photo gallery grid with a lightbox (click photo -> fullscreen). |
| `contact.html` | Contact info + message form. Loads hotel info from the API (`settings`) and submits messages via `apiPost('contact', ...)`. |
| `css/style.css` | The luxury gold/cream theme: fonts (Playfair Display + Poppins), navbar, hero sections, room cards, gallery, forms, footer. |
| `js/api.js` | **The bridge to the backend.** Defines `API_BASE` and the helpers `apiGet()` / `apiPost()` that call `backend/index.php?r=api/...` and return JSON. Also `formatPrice()` and `calcNights()`. |
| `js/main.js` | Visual behaviors: navbar changes color on scroll, hamburger menu for mobile, fade-in animations (IntersectionObserver), gallery lightbox, animated counters. |
| `images/*.jpg` | Photos used by the site (exterior, pool, restaurant, room types). |

---

## 4. `backend/` - The Admin System (staff)

A hand-written **MVC** (Model-View-Controller) PHP application. No frameworks, no Composer - everything is written by hand, which is why the CODE_EXPLANATIONS folder matters.

### 4.1 Entry and Core files

| File | What it does |
|------|--------------|
| `index.php` | **The front door.** Every admin URL goes through here. It loads `bootstrap.php`, reads the `?r=` route, and asks the Router to find the controller. |
| `bootstrap.php` | Starts everything: loads `config.php` and registers the **autoloader** - when a class like `App\Controllers\RoomController` is used, PHP automatically finds and includes its file. |
| `config.php` | Configuration: database connection (`getDB()` returning one shared PDO object), `session_start()`, and shared helpers: `hasRole()`, `loginRequired()`, `roleRequired()`, `redirect()`, `e()` (XSS escaping). Tries `config_local.php` first, falls back to the hardcoded InfinityFree credentials. |
| `cors.php` | Sends CORS headers (`Access-Control-Allow-Origin: *` etc.) so the API can be called from other domains; answers OPTIONS preflight requests. |
| `.htaccess` | Apache rules: clean URLs / routing everything through `index.php`, and blocking direct web access to sensitive files. |
| `.gitignore` | Tells git to ignore `config_local.php` (your local database secrets) so it is never uploaded to GitHub. |
| `Core/Router.php` | **The traffic policeman.** Takes a route string like `room_types/index`, converts it to class + method names (`RoomTypeController::indexAction`), calls it, or returns a 404 if it does not exist. It even singularizes names (`rooms` -> `RoomController`). |
| `Core/Controller.php` | Base class for all controllers. Provides `view()` (renders a template between the layout header/footer), `redirect()`, `loginRequired()`/`roleRequired()` guards, and `jsonResponse()` for the API. |
| `Core/Model.php` | Base class for all models. Its constructor hands the shared database connection (`getDB()`) to every model - so all models reuse **one** connection. |
| `Helpers/GoogleAuth.php` | Verifies **Google sign-in** tokens by hand: downloads Google's public keys (cached 24h in `cache/`), checks the RSA signature with `openssl_verify`, validates issuer/audience/expiry. Used by the login page's "Sign in with Google" button. |

### 4.2 Controllers (`backend/Controllers/`) - "the brains"

Each controller = one module of the hotel. All extend `Core\Controller`.

| File | Responsibility |
|------|----------------|
| `ApiController.php` | **The only controller the public website talks to.** Serves JSON: room list, room details, available rooms by date, creating reservations, contact messages, hotel settings. No login required. |
| `DashboardController.php` | Home screen after login: stat cards (rooms, occupancy %, revenue), arrivals/departures today. |
| `LoginController.php` / `LogoutController.php` | Staff login (username + password, and Google sign-in) / ending the session. |
| `UserController.php` | **Administrator only:** manage staff accounts - create/edit/delete users, assign roles (Manager, Receptionist, Accountant, Housekeeping), block self-deletion. |
| `ReservationController.php` | Reservation list with search/filter, add/edit bookings, date validation, availability check, computes nights and total price. |
| `RoomController.php` | CRUD for individual rooms (number, floor, status). Blocks deleting a room that has reservations. |
| `RoomTypeController.php` | CRUD for room categories (Standard/Deluxe/Suite - name, price, capacity). |
| `CheckInController.php` | Guests arriving today: check them in, pick the room, start the stay. |
| `CheckOutController.php` | Active stays: add extra charges, take payment, check the guest out. |
| `BillingController.php` | Payment/invoice list with pagination and totals; shows receipts (`receipt.php`) and printable receipt (`print.php`). |
| `GuestController.php` | CRUD for guest profiles (name, phone, ID/passport), with stay history. |
| `HousekeepingController.php` | Rooms needing cleaning - housekeeping staff update status (Cleaning -> Available). |
| `MaintenanceController.php` | Rooms under maintenance - report issues and change status. |
| `NotificationController.php` | Alerts that need attention (e.g. overdue checkouts, maintenance requests), with links to the right module. |
| `ReportController.php` | Analytics: occupancy rate, revenue, performance metrics. |
| `SettingController.php` | Hotel configuration: name, address, tax rate, contact details. |
| `SetupController.php` | **First-run installer:** creates all tables and inserts sample data with one click - no phpMyAdmin needed. |

### 4.3 Models (`backend/Models/`) - "the data layer"

Every model talks to the database using **PDO prepared statements** (safe from SQL injection). Each extends `Core\Model`.

| File | Table | What it does |
|------|-------|--------------|
| `Room.php` | `rooms` | List rooms with filters (status/floor/type) + JOIN with room type and current occupant; create/update/delete; `setStatus()`; blocks deletion if reservations exist; `distinctFloors()`. |
| `RoomType.php` | `room_types` | Room categories with a count of how many rooms use each type. |
| `Reservation.php` | `reservations` | Booking list with computed nights; **`roomIsAvailable()`** - the date-overlap check that prevents double-booking; create/update/status changes; helpers used by check-in/out. |
| `CheckIn.php` | `check_ins` | Active stays: which guest is in which room, check-in/out dates, extra charges. |
| `Guest.php` | `guests` | Guest profiles with stay count and "currently staying" flag; blocks deleting guests who have reservations. |
| `User.php` | `users` | Staff accounts: `findByUsername()` for login, password **hashing** (`password_hash`), username/email uniqueness checks. |

### 4.4 Views (`backend/Views/`) - "the screens"

PHP templates. Each module has a folder with `index.php`; the layout wraps around them.

| File | Screen |
|------|--------|
| `layout/header.php` | Opens every admin page: loads Bootstrap, renders the **sidebar menu filtered by role** (an Administrator sees Users/Settings, a Housekeeper doesn't), highlights the active page, escapes output with `htmlspecialchars()`. |
| `layout/footer.php` | Closes the page and loads shared JS: mobile sidebar, auto-hiding alerts, delete-confirmation dialogs, `printReceipt()`, CSV export, currency/date formatting. |
| `login/index.php` | The login screen (standalone, no sidebar) with username/password + Google button. |
| `setup/index.php` | The installer page. |
| `dashboard/index.php` | Main control panel with stat cards. |
| `reservations/index.php` | Reservations table + search + add/edit modals + client-side price calculation (nights x rate + tax). |
| `rooms/index.php` | Room management in table **or** board (card) view with filters. |
| `room_types/index.php` | Room category CRUD table. |
| `checkin/index.php` | Today's arrivals + current stays. |
| `checkout/index.php` | Active stays with payment and extra charges. |
| `billing/index.php` | Payments list with summary stats. |
| `billing/receipt.php` | Receipt shown inside a modal. |
| `billing/print.php` | Full printable receipt (opens in a new tab -> print -> paper). |
| `guests/index.php` | Guest CRUD table. |
| `guests/view.php` | Guest detail modal, loaded by AJAX. |
| `housekeeping/index.php` | Cleaning queue with status buttons. |
| `maintenance/index.php` | Maintenance status screen. |
| `notifications/index.php` | Alert list with jump links. |
| `reports/index.php` | Occupancy/revenue analytics. |
| `users/index.php` | Staff account management (admin only). |
| `settings/index.php` | Hotel settings form. |

### 4.5 Assets and database files

| File | What it is |
|------|------------|
| `assets/css/style.css` | Admin panel stylesheet (separate from the public site's CSS). |
| `assets/img/*` | Logo files and background photos used by the admin login page. |
| `database/hotel_management_1_2.sql` | Database schema + seed data (local version) - import via phpMyAdmin. |
| `database/infinityfree_import.sql` | The same schema adjusted for **InfinityFree** hosting (it has import quirks like needing a table prefix). |

---

## 5. `tests/` - The Testing Deliverable (5 pts)

Run everything with: `php tests/run_all.php`

| File | What it does |
|------|--------------|
| `run_all.php` | **The commander.** Runs all three suites in order (unit -> integration -> system), each in its own PHP process, plus the JavaScript suite via Node. Flags: `--unit`, `--integration`, `--system`. Prints the final summary and exits with code 0/1. |
| `lib/TestRunner.php` | Mini testing framework (like PHPUnit, but free - no Composer needed). Provides `assertTrue`, `assertEquals`, `assertGreaterThan`, `assertContains`, `skip()`, colored output and the results summary. |
| `lib/Http.php` | Helper that sends real HTTP requests to the local site (`http://localhost/...`) - used by integration and system tests. |
| `lib/Db.php` | Helper that connects directly to MySQL for checks and cleanup. Only ever touches rows marked `qa-*` so your real data is safe. |
| `unit/unit_tests.php` | **23 PHP unit tests** using Reflection to test private functions - e.g. `computeNights()` and `computeTotal()` in `ReservationController`. |
| `unit/frontend_calc.test.js` | **10 JavaScript unit tests** run in Node: `calcNights()` and `formatPrice()` from `frontend/js/api.js`. |
| `integration/api_tests.php` | **22 integration tests** hitting the real API over HTTP: rooms list, room details, availability search, reservation POST, contact form, settings, plus error cases (missing fields -> errors). |
| `system/system_tests.php` | **16 system (end-to-end) tests**: the full booking flow (guest books -> appears in admin -> cancel), login as admin, **role permissions** (creates a temporary Receptionist and proves they cannot see the Users page), session security. |

---

## 6. Deployment and backups

| File | What it does |
|------|--------------|
| `.github/workflows/deploy.yml` | **GitHub Actions**: every time code is merged into `main`, it automatically uploads the `frontend/` folder to **GitHub Pages** - so the live site updates itself. |
| `backups/backup_*.sql` | Automatic database backups (safe copies of your data). |

---

## 7. Quick "which file do I edit?" cheat sheet

| I want to... | Edit this file |
|--------------|----------------|
| Change the public website's look | `frontend/css/style.css` |
| Change how the frontend talks to the API | `frontend/js/api.js` |
| Add a new admin page | New controller in `backend/Controllers/` + view in `backend/Views/` + menu item in `layout/header.php` |
| Change a database query | The model in `backend/Models/` |
| Add a database table/column | `backend/database/hotel_management_1_2.sql` (+ re-run setup) |
| Change who can access a page | `roleRequired()` call at the top of that controller's action |
| Add a test | `tests/unit/`, `tests/integration/` or `tests/system/`, then `php tests/run_all.php` |

---

## 8. One-Minute Script (if a teacher asks "explain your project")

> "The system has two parts. Guests use a static website in `frontend/` - it talks to the
> backend through a small JSON API in `ApiController`. Staff use the admin panel in `backend/`,
> which is a hand-written MVC: the URL goes to `index.php`, the `Router` finds the controller,
> the controller asks a model for data using PDO prepared statements, and a view renders the
> HTML. Everything is tested by a custom test suite in `tests/` - 71 tests across unit,
> integration and system levels - and the frontend deploys automatically to GitHub Pages
> through GitHub Actions when we merge to `main`."
