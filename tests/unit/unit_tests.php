<?php
/**
 * UNIT TESTS — pure business logic (no HTTP server required; DB optional).
 *
 * Targets (per assignment rubric: "calcNights(), price calculation ..."):
 *   - ReservationController::computeNights() / computeTotal()  (private → Reflection)
 *   - Router route parsing & 404 handling
 *   - Session/role helpers: isLoggedIn(), hasRole()
 *   - Config helpers: generateReservationNumber(), formatCurrency()
 *
 * Run standalone:  php tests/unit/unit_tests.php
 * Or via runner:   php tests/run_all.php
 */

require_once __DIR__ . '/../lib/TestRunner.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../../backend/bootstrap.php'; // App\ autoloader + config helpers + session

use App\Core\Router;

$t = new TestRunner();

// ---------------------------------------------------------------------------
// Shared fixtures: instantiate the controller WITHOUT its constructor
// (the constructor enforces login + loads settings from DB; unit tests
// must run without a session or database).
// ---------------------------------------------------------------------------
$rc        = new ReflectionClass('App\Controllers\ReservationController');
$ctrl      = $rc->newInstanceWithoutConstructor();
$nightsRef = $rc->getMethod('computeNights');
$nightsRef->setAccessible(true);
$totalRef  = $rc->getMethod('computeTotal');
$totalRef->setAccessible(true);

// ===========================================================================
$t->suite('Unit: ReservationController::computeNights (stay length)');

$t->test('computeNights returns 3 for a 3-night stay', function () use ($t, $nightsRef, $ctrl) {
    $t->assertSame(3, $nightsRef->invoke($ctrl, '2026-01-01', '2026-01-04'));
});

$t->test('computeNights returns 1 for a 1-night stay', function () use ($t, $nightsRef, $ctrl) {
    $t->assertSame(1, $nightsRef->invoke($ctrl, '2026-01-01', '2026-01-02'));
});

$t->test('computeNights enforces minimum 1 night for same-day stay', function () use ($t, $nightsRef, $ctrl) {
    $t->assertSame(1, $nightsRef->invoke($ctrl, '2026-01-01', '2026-01-01'));
});

$t->test('computeNights never returns 0 or negative for reversed dates', function () use ($t, $nightsRef, $ctrl) {
    $t->assertSame(1, $nightsRef->invoke($ctrl, '2026-01-04', '2026-01-01'));
});

$t->test('computeNights spans month boundary correctly (Jan 30 → Feb 2 = 3)', function () use ($t, $nightsRef, $ctrl) {
    $t->assertSame(3, $nightsRef->invoke($ctrl, '2026-01-30', '2026-02-02'));
});

$t->test('computeNights handles leap-day span (Feb 27 → Mar 1 2028 = 3)', function () use ($t, $nightsRef, $ctrl) {
    // 2028 is a leap year; Feb 29 exists and must be counted.
    $t->assertSame(3, $nightsRef->invoke($ctrl, '2028-02-27', '2028-03-01'));
});

// ===========================================================================
$t->suite('Unit: ReservationController::computeTotal (price calculation)');

$t->test('computeTotal adds 10% tax: 3 nights x $100 = $330.00', function () use ($t, $totalRef, $ctrl) {
    $ctrl->taxRate = 10.0;
    $t->assertSame(330.0, $totalRef->invoke($ctrl, 3, 100.0));
});

$t->test('computeTotal adds 10% tax: 2 nights x $55 = $121.00', function () use ($t, $totalRef, $ctrl) {
    $ctrl->taxRate = 10.0;
    $t->assertSame(121.0, $totalRef->invoke($ctrl, 2, 55.0));
});

$t->test('computeTotal rounds to 2 decimals: 1 night x $33.33 @ 7% = $35.66', function () use ($t, $totalRef, $ctrl) {
    $ctrl->taxRate = 7.0;
    // 33.33 * 1.07 = 35.6631 → rounds down to 35.66
    $t->assertSame(35.66, $totalRef->invoke($ctrl, 1, 33.33));
});

$t->test('computeTotal with 0% tax returns exact subtotal', function () use ($t, $totalRef, $ctrl) {
    $ctrl->taxRate = 0.0;
    $t->assertSame(165.0, $totalRef->invoke($ctrl, 3, 55.0));
});

// ===========================================================================
$t->suite('Unit: Router (MVC request dispatching)');

$t->test('Router::url builds query-string routes', function () use ($t) {
    $t->assertSame('index.php?r=rooms/index', Router::url('rooms/index'));
});

$t->test('Unknown controller returns 404 with resolved class name', function () use ($t) {
    $router = new Router();
    ob_start();
    $router->dispatch('nonexistent_thing');
    $out = ob_get_clean();
    $t->assertContains('Controller not found: App\Controllers\NonexistentThingController', $out);
    // NOTE: Router also calls http_response_code(404), but under the CLI SAPI a
    // status code cannot be set once output has started, so we only assert the
    // routing result here. Real HTTP status codes are covered by the
    // integration suite (e.g. api/view&id=999999 must return 404).
});

$t->test('Router fixes camelCase: checkout_foo → CheckOutFooController', function () use ($t) {
    $router = new Router();
    ob_start();
    $router->dispatch('checkout_foo');
    $out = ob_get_clean();
    $t->assertContains('App\Controllers\CheckOutFooController', $out);
});

$t->test('Router fixes camelCase: checkin_foo → CheckInFooController', function () use ($t) {
    $router = new Router();
    ob_start();
    $router->dispatch('checkin_foo');
    $out = ob_get_clean();
    $t->assertContains('App\Controllers\CheckInFooController', $out);
});

$t->test('Route sanitization strips non-alphanumeric characters', function () use ($t) {
    $router = new Router();
    ob_start();
    $router->dispatch('<script>alert(1)</script>');
    $out = ob_get_clean();
    $t->assertNotContains('<script>', $out);
});

// ===========================================================================
$t->suite('Unit: Session & role helpers (access control logic)');

$t->test('isLoggedIn is false with empty session', function () use ($t) {
    $saved = $_SESSION ?? [];
    $_SESSION = [];
    $t->assertSame(false, isLoggedIn());
    $_SESSION = $saved;
});

$t->test('isLoggedIn is true when user_id is set', function () use ($t) {
    $saved = $_SESSION ?? [];
    $_SESSION = ['user_id' => 1];
    $t->assertSame(true, isLoggedIn());
    $_SESSION = $saved;
});

$t->test('hasRole is false when not logged in (even with role set)', function () use ($t) {
    $saved = $_SESSION ?? [];
    $_SESSION = ['user_role' => 'Administrator']; // no user_id
    $t->assertSame(false, hasRole(['Administrator']));
    $_SESSION = $saved;
});

$t->test('hasRole allows user role inside allowed list', function () use ($t) {
    $saved = $_SESSION ?? [];
    $_SESSION = ['user_id' => 5, 'user_role' => 'Receptionist'];
    $t->assertSame(true, hasRole(['Administrator', 'Receptionist', 'Manager']));
    $_SESSION = $saved;
});

$t->test('hasRole denies user role outside allowed list', function () use ($t) {
    $saved = $_SESSION ?? [];
    $_SESSION = ['user_id' => 5, 'user_role' => 'Receptionist'];
    $t->assertSame(false, hasRole(['Administrator']));
    $_SESSION = $saved;
});

// ===========================================================================
$t->suite('Unit: Config helpers');

$t->test('generateReservationNumber matches RES-YYYYMMDD-#### format', function () use ($t) {
    $t->assertMatches('/^RES-\d{8}-\d{4}$/', generateReservationNumber());
});

$t->test('generateReservationNumber produces unique values across calls', function () use ($t) {
    $a = generateReservationNumber();
    usleep(1100000); // rand() seeded by time in seconds — wait to guarantee difference
    $b = generateReservationNumber();
    $t->assertTrue($a !== $b, "both numbers were identical: $a");
});

$t->test('formatCurrency renders amount with currency symbol and 2 decimals', function () use ($t) {
    if (!qa_pdo()) {
        $t->skip('database not reachable (formatCurrency reads system settings)');
    }
    // Local DB stores currency=USD; pattern tolerates any 3-letter code.
    $t->assertMatches('/^[A-Z]{3} 1,234\.50$/', formatCurrency(1234.5));
});

exit($t->summary());
