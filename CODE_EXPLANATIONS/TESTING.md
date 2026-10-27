# TESTING.md — Test Plan & Test Results

> Matches assignment Criterion 5 (Testing, 5 pts): a testing table with
> *Test case → Steps → Expected → Actual → Pass/Fail* for **Unit**,
> **Integration** and **System** testing, plus the bugs found and fixed.

---

## 1. Overview

| Item | Value |
|------|-------|
| Test framework | Custom dependency-free runner (`tests/lib/TestRunner.php`) — the project has no Composer/PHPUnit, so tests run with plain `php` |
| Test levels | Unit (PHP + JS), Integration (HTTP API), System (end-to-end) |
| Total tests | **71** (23 PHP unit + 10 JS unit + 22 integration + 16 system) |
| Result | **71 passed, 0 failed, 0 skipped** |
| Runtime | ~3 seconds |
| Last run | 2026-10-26, PHP 8.3.35, MySQL (WAMP), Apache |

### How to run

```bash
php tests/run_all.php               # run everything (unit + integration + system)
php tests/run_all.php --unit        # only unit suites
php tests/run_all.php --integration # only API integration suite
php tests/run_all.php --system      # only end-to-end suite
```

* Unit tests need only PHP (and Node for the JS suite) — no server or DB.
* Integration/System tests need Apache + MySQL running; if the server is not
  reachable they **skip with an explanation** instead of failing.
* Environment override: `TEST_HTTP_BASE=http://host/folder php tests/run_all.php`

### Test-data safety

Every test that creates data uses marker emails `qa-…@example.test` and
usernames `qa-tester-…`. A cleanup pass (`tests/lib/Db.php`) deletes these rows
after each run, so developer data is never touched and runs are repeatable.

---

## 2. Unit Testing

Individual functions in isolation (no HTTP, no DB).

### 2.1 PHP — `ReservationController::computeNights()` (stay length)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| 3-night stay | Call `computeNights('2026-01-01','2026-01-04')` via Reflection | `3` | `3` | ✅ Pass |
| 1-night stay | Call `computeNights('2026-01-01','2026-01-02')` | `1` | `1` | ✅ Pass |
| Same-day stay | Call `computeNights('2026-01-01','2026-01-01')` | `1` (minimum enforced) | `1` | ✅ Pass |
| Reversed dates | Call `computeNights('2026-01-04','2026-01-01')` | `1`, never 0/negative | `1` | ✅ Pass |
| Month boundary | Call `computeNights('2026-01-30','2026-02-02')` | `3` | `3` | ✅ Pass |
| Leap-day span | Call `computeNights('2028-02-27','2028-03-01')` | `3` (Feb 29 counted) | `3` | ✅ Pass |

### 2.2 PHP — `ReservationController::computeTotal()` (price calculation with tax)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| 10% tax on $300 | Set `taxRate=10`, call `computeTotal(3, 100.0)` | `330.0` | `330.0` | ✅ Pass |
| 10% tax on $110 | Set `taxRate=10`, call `computeTotal(2, 55.0)` | `121.0` | `121.0` | ✅ Pass |
| Rounding to 2 decimals | Set `taxRate=7`, call `computeTotal(1, 33.33)` | `35.66` (35.6631 rounded) | `35.66` | ✅ Pass |
| 0% tax | Set `taxRate=0`, call `computeTotal(3, 55.0)` | `165.0` exact subtotal | `165.0` | ✅ Pass |

### 2.3 PHP — Router (MVC dispatching)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| URL builder | `Router::url('rooms/index')` | `index.php?r=rooms/index` | `index.php?r=rooms/index` | ✅ Pass |
| Unknown controller | `dispatch('nonexistent_thing')` with output buffering | Message `Controller not found: App\Controllers\NonexistentThingController` | Same message (status code is also set to 404, verified via HTTP in §3) | ✅ Pass |
| camelCase fix (checkout) | `dispatch('checkout_foo')` | Resolves to `CheckOutFooController` | `App\Controllers\CheckOutFooController` | ✅ Pass |
| camelCase fix (checkin) | `dispatch('checkin_foo')` | Resolves to `CheckInFooController` | `App\Controllers\CheckInFooController` | ✅ Pass |
| Input sanitization | `dispatch('<script>alert(1)</script>')` | No raw `<script>` in output | `<script>` absent from output | ✅ Pass |

### 2.4 PHP — Session & role helpers (access-control logic)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Not logged in | Empty `$_SESSION`, call `isLoggedIn()` | `false` | `false` | ✅ Pass |
| Logged in | Set `$_SESSION['user_id']=1`, call `isLoggedIn()` | `true` | `true` | ✅ Pass |
| Role check without login | `$_SESSION['user_role']` set but no `user_id`, call `hasRole(['Administrator'])` | `false` (must log in first) | `false` | ✅ Pass |
| Role allowed | Role `Receptionist`, `hasRole(['Administrator','Receptionist','Manager'])` | `true` | `true` | ✅ Pass |
| Role denied | Role `Receptionist`, `hasRole(['Administrator'])` | `false` | `false` | ✅ Pass |

### 2.5 PHP — Config helpers

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Reservation number format | Call `generateReservationNumber()` | Matches `RES-YYYYMMDD-####` | `RES-20261026-####` pattern matched | ✅ Pass |
| Reservation number uniqueness | Call twice, 1.1 s apart | Two different values | Two different values | ✅ Pass |
| Currency formatting | `formatCurrency(1234.5)` (reads currency from DB settings) | `USD 1,234.50` (3-letter code + thousands + 2 decimals) | `USD 1,234.50` | ✅ Pass |

### 2.6 JavaScript — `frontend/js/api.js` (runs real browser code inside Node `vm`)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| calcNights 3 nights | `calcNights('2026-01-01','2026-01-04')` | `3` | `3` | ✅ Pass |
| calcNights 1 night | `calcNights('2026-01-01','2026-01-02')` | `1` | `1` | ✅ Pass |
| calcNights same day | `calcNights('2026-01-01','2026-01-01')` | `1` (min enforced) | `1` | ✅ Pass |
| calcNights reversed dates | `calcNights('2026-01-04','2026-01-01')` | `1`, never negative | `1` | ✅ Pass |
| calcNights month boundary | `calcNights('2026-01-30','2026-02-02')` | `3` | `3` | ✅ Pass |
| calcNights leap-year span | `calcNights('2028-02-27','2028-03-01')` | `3` | `3` | ✅ Pass |
| formatPrice default USD | `formatPrice(55)` | `USD 55.00` | `USD 55.00` | ✅ Pass |
| formatPrice explicit currency | `formatPrice('1234.5','KHR')` | `KHR 1234.50` | `KHR 1234.50` | ✅ Pass |
| formatPrice rounding | `formatPrice(49.999)` | `USD 50.00` | `USD 50.00` | ✅ Pass |
| formatPrice zero | `formatPrice(0)` | `USD 0.00` | `USD 0.00` | ✅ Pass |

---

## 3. Integration Testing

Modules working together over real HTTP against the running Apache server,
with database cross-checks (`backend/index.php?r=api/…`).

### 3.1 Room catalog & detail (`api/rooms`, `api/view`)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Room type catalog | `GET api/rooms` | HTTP 200, `success=true`, non-empty list | 200, 5 room types returned | ✅ Pass |
| Prices and capacity valid | Inspect every room type in the response | Numeric `price_per_night` > 0 and numeric `capacity` | All types valid | ✅ Pass |
| Room type detail | `GET api/view&id=1` | HTTP 200, type name + non-empty `rooms[]` | 200, type with attached rooms | ✅ Pass |
| Invalid id rejected | `GET api/view&id=0` | HTTP 400, `success=false` | 400, `success=false` | ✅ Pass |
| Unknown type 404 | `GET api/view&id=999999` | HTTP 404, `success=false` | 404, `success=false` | ✅ Pass |

### 3.2 Availability search (`api/availableRooms`)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Missing dates rejected | `GET api/availableRooms` (no parameters) | HTTP 400, message mentions `check_in and check_out` | 400, correct message | ✅ Pass |
| Same-day range rejected | `check_out == check_in` | HTTP 400, `check_out must be after check_in` | 400, correct message | ✅ Pass |
| Valid future range | `check_in=+35d, check_out=+37d, guests=2` | HTTP 200, rooms with numeric price and room number | 200, valid room list | ✅ Pass |
| Maintenance rooms excluded | Search same dates, cross-check with DB `rooms.status='Maintenance'` | No maintenance room appears in results | No maintenance room offered | ✅ Pass |

### 3.3 Booking & billing (`api/reservation`)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Missing fields rejected | `POST api/reservation` with only `full_name` | HTTP 400, `Missing required fields` | 400, correct message | ✅ Pass |
| Invalid date range rejected | `check_out` before `check_in` | HTTP 400, `check_out must be after check_in` | 400, correct message | ✅ Pass |
| GET method rejected | `GET api/reservation` | HTTP 405 | 405 | ✅ Pass |
| Valid booking created | Pick an available room type, `POST` full booking for 3 nights | HTTP 200, `success=true`, number matches `RES-YYYYMMDD-####`, `nights=3`, total > 0 | 200, number `RES-2026…-####`, 3 nights, total = 3 × rate | ✅ Pass |
| Booking persisted correctly | Check DB: reservation + guest for `qa-…` email | Status `Pending`, room matches response, `total_amount = nights × price_per_night` (rounded 2 dp) | Row found, `Pending`, total matches `3 × nightly rate` exactly | ✅ Pass |
| **Booking → availability** (rubric flow) | After booking, repeat `api/availableRooms` for the same dates | The booked room number no longer appears | Booked room absent from results | ✅ Pass |
| **Double-booking prevented** | Immediately book the same room type and dates again | Either HTTP 400 `No rooms available`, or a *different* room is assigned — never the same room | Rejected/assigned a different room; the booked room is never reused | ✅ Pass |

### 3.4 Contact form (`api/contact`)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Invalid email rejected | `POST api/contact` with `email=not-an-email` | HTTP 400, `Invalid email address` | 400, correct message | ✅ Pass |
| Missing fields rejected | `POST api/contact` with only `full_name` | HTTP 400 | 400 | ✅ Pass |
| Valid message stored | POST complete message, then query DB by marker email | HTTP 200, `success=true`, exactly 1 row in `contacts` | 200, 1 row stored | ✅ Pass |

### 3.5 Public settings & auth guards (`api/settings`, protected routes)

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Public settings | `GET api/settings` | HTTP 200, hotel name + 3-letter currency code | 200, `Arizu Arimato` + `USD` | ✅ Pass |
| Anonymous blocked (reservations) | `GET index.php?r=reservations/index` without session | HTTP 302 → `login/index` | 302, redirect to login | ✅ Pass |
| Anonymous blocked (dashboard) | `GET index.php?r=dashboard/index` without session | HTTP 302 → `login/index` | 302, redirect to login | ✅ Pass |

---

## 4. System Testing

Whole application end-to-end: public frontend, staff login, RBAC, complete
booking flow and logout, all through real HTTP.

### 4.1 Public frontend pages

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Homepage renders | `GET frontend/index.html` | HTTP 200, hotel branding `ARIZU ARIMATO` | 200, hero title present | ✅ Pass |
| Rooms page has forms | `GET frontend/rooms.html` | HTTP 200, contains `searchForm` and `bookingForm` | 200, both forms present | ✅ Pass |
| All static pages load | `GET` about.html, gallery.html, contact.html | All HTTP 200 with branding | All 200 | ✅ Pass |
| Contact form renders | `GET frontend/contact.html` | Contains `contactForm` | Present | ✅ Pass |

### 4.2 Login & session lifecycle

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Login page renders | `GET index.php?r=login/index` | HTTP 200 with `username` + `password` fields | 200, both fields present | ✅ Pass |
| Wrong password rejected | POST `admin` + wrong password | Stays on login page (200), generic error `Invalid username or password` (no username enumeration), no redirect to dashboard | 200, generic error shown, no redirect | ✅ Pass |
| Correct credentials accepted | POST `admin`/`admin123` | HTTP 302 → `dashboard/index`, session cookie issued | 302, `PHPSESSID` cookie set | ✅ Pass |
| Dashboard accessible | `GET index.php?r=dashboard/index` with session | HTTP 200, page contains `Dashboard` | 200 | ✅ Pass |
| Already-logged-in user visiting login | `GET login/index` with session | HTTP 302 → dashboard (no re-login) | 302 → dashboard | ✅ Pass |

### 4.3 Role-Based Access Control

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Receptionist denied user management | Create temporary `Receptionist` account, log in, `GET index.php?r=users/index` | HTTP 302 → dashboard (Administrator-only) | 302 → dashboard | ✅ Pass |
| Receptionist can open reservations | Same session, `GET index.php?r=reservations/index` | HTTP 200 (allowed role) | 200 | ✅ Pass |
| Anonymous blocked from users page | `GET index.php?r=users/index` without session | HTTP 302 → login | 302 → login | ✅ Pass |

### 4.4 Complete booking flow (guest books → admin sees it) — rubric's system flow

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Guest books online | Search availability for +60d…+63d, `POST api/reservation` as public guest | HTTP 200, `success=true`, reservation number `RES-YYYYMMDD-####` | 200, number issued | ✅ Pass |
| Admin sees the booking | Log in as admin, open reservations list | Page contains the new reservation number | Number visible in list | ✅ Pass |
| Guest + billing visible to admin | Open the same reservations page | Guest name (`QA System Guest`) rendered in the table and present in the page's embedded reservation data (details open in a client-side modal) | Name and number both present | ✅ Pass |
| Admin cancels → room released | `POST action=delete&id=…` as admin, then re-check DB and availability | Page re-renders with `Reservation deleted`, DB row gone, room bookable again for those dates | Message shown, row deleted, room back in availability | ✅ Pass |

### 4.5 Logout

| Test case | Steps | Expected | Actual | Pass/Fail |
|-----------|-------|----------|--------|-----------|
| Logout destroys session | `GET index.php?r=logout/index` with session, then reuse the same cookie on the dashboard | Redirect to login; the old cookie no longer grants access (dashboard → login) | 302 → login; old session rejected | ✅ Pass |

---

## 5. Bugs found while testing → fixed → re-tested

Testing was not just a formality: it found **one real production bug**.

| # | Bug | How testing found it | Fix | Re-test |
|---|-----|---------------------|-----|---------|
| 1 | **Contact form crashed on submit.** The live DB table `contacts` had a typo'd column `ull_name` instead of `full_name`, so `api/contact` failed with `Unknown column 'full_name' in 'field list'` — every contact message from guests was lost. | Integration test *“valid contact message is stored in DB”* failed: the API returned a PHP fatal error instead of JSON | Renamed the column: `ALTER TABLE contacts CHANGE ull_name full_name VARCHAR(100) NOT NULL`. The canonical schemas (`backend/database/infinityfree_import.sql`, `backups/*.sql`) already used the correct name — only the imported DB was wrong | Test now passes; contact messages are stored correctly |

Two further discrepancies were found between tests and the system and resolved
in favour of the system's documented behaviour (they were test-expectation bugs,
not application bugs):

* `handlePost()` re-renders the reservations list with a success message
  (HTTP 200) instead of redirecting — the system test now asserts the message.
* There is no `reservations/view` action: reservation details open in a
  client-side modal built from JSON embedded in the list page — the system test
  now asserts the guest name and number on that page.

---

## 6. Notes / known limitations

* **PHP session cookie**: every request issues a `PHPSESSID` cookie (sessions
  start globally in `config.php`). Security is enforced by *session contents*,
  not by cookie presence — tests assert redirects and page content instead.
* **CLI status codes**: under the PHP CLI SAPI a status code cannot be set once
  output starts, so unit tests assert routing messages; HTTP status codes are
  verified in the integration suite (e.g. `api/view&id=999999` → 404).
* **No automated browser (Selenium/Playwright) tests** — frontend JS behaviour
  is covered by the JS unit suite plus system-level HTTP checks of the pages.
* UAT (Criterion 5, optional) is not automated; see `USER_MANUAL.md` for the
  manual test script a real user can follow.
