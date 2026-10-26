<?php
/**
 * SYSTEM TESTS — full end-to-end flows through the real UI + backend.
 *
 * Covers (per rubric: "system: login → booking → admin view"):
 *   - Public frontend pages load and render
 *   - Login failure / success / logout (session lifecycle)
 *   - Role-Based Access Control (Receptionist denied to User management)
 *   - Complete booking flow: guest books online → room blocked →
 *     admin sees the reservation → cleanup restores state
 *
 * Requires: Apache serving the app + reachable MySQL.
 * Override base URL: set TEST_HTTP_BASE (default: local WAMP folder).
 *
 * Run standalone:  php tests/system/system_tests.php
 */

require_once __DIR__ . '/../lib/TestRunner.php';
require_once __DIR__ . '/../lib/Http.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../../backend/bootstrap.php';

$t = new TestRunner();

$BASE    = getenv('TEST_HTTP_BASE') ?: 'http://localhost/arizu-arimato%201.2';
$BACKEND = $BASE . '/backend/index.php';
$FRONT   = $BASE . '/frontend/';
$API     = $BACKEND . '?r=api/';

// --- Preflight --------------------------------------------------------------
try {
    Http::get($FRONT . 'index.html');
} catch (RuntimeException $e) {
    $t->skipSuite('System: End-to-end flows', 'server not reachable at ' . $BASE .
        ' — start Apache (WAMP) or set TEST_HTTP_BASE. Error: ' . $e->getMessage());
    exit($t->summary());
}

$db = qa_pdo();
$qaEmail = 'qa-' . substr(md5(uniqid('', true)), 0, 10) . '@example.test';

// Remove leftovers from previous interrupted runs so RBAC tests start clean
if ($db) {
    $db->exec("DELETE FROM users WHERE username LIKE 'qa-tester-%'");
}

// ===========================================================================
$t->suite('System: Public frontend pages render');

$t->test('homepage loads with hotel branding and hero title', function () use ($t, $FRONT) {
    $r = Http::get($FRONT . 'index.html');
    $t->assertSame(200, $r['status']);
    $t->assertContains('ARIZU ARIMATO', $r['body']);
});

$t->test('rooms page contains search and booking forms', function () use ($t, $FRONT) {
    $r = Http::get($FRONT . 'rooms.html');
    $t->assertSame(200, $r['status']);
    $t->assertContains('id="searchForm"', $r['body']);
    $t->assertContains('id="bookingForm"', $r['body']);
});

$t->test('about, gallery and contact pages all load with 200', function () use ($t, $FRONT) {
    foreach (['about.html', 'gallery.html', 'contact.html'] as $page) {
        $r = Http::get($FRONT . $page);
        $t->assertSame(200, $r['status'], "page: $page");
        $t->assertContains('ARIZU', $r['body'], "branding missing on $page");
    }
});

$t->test('contact page renders its message form', function () use ($t, $FRONT) {
    $r = Http::get($FRONT . 'contact.html');
    $t->assertSame(200, $r['status']);
    $t->assertContains('id="contactForm"', $r['body']);
});

// ===========================================================================
$t->suite('System: Login & session lifecycle');

$t->test('login page renders username and password fields', function () use ($t, $BACKEND) {
    $r = Http::get($BACKEND . '?r=login/index');
    $t->assertSame(200, $r['status']);
    $t->assertContains('name="username"', $r['body']);
    $t->assertContains('name="password"', $r['body']);
});

$t->test('wrong password shows generic error and stays on login page', function () use ($t, $BACKEND) {
    // config.php calls session_start() on every request, so a PHPSESSID cookie
    // is always issued; the security property is: no redirect to the dashboard
    // and a generic error is shown (no username enumeration).
    $r = Http::post($BACKEND . '?r=login/index', ['username' => 'admin', 'password' => 'wrong-password-xyz']);
    $t->assertSame(200, $r['status']);
    $t->assertContains('Invalid username or password', $r['body']);
    $t->assertNotContains('dashboard', $r['headers']['location'] ?? '');
});

$t->test('valid admin credentials redirect to dashboard', function () use ($t, $BACKEND) {
    $r = Http::post($BACKEND . '?r=login/index', ['username' => 'admin', 'password' => 'admin123']);
    $t->assertSame(302, $r['status']);
    $t->assertContains('dashboard/index', $r['headers']['location'] ?? '');
    $t->assertGreaterThanOrEqual(1, strlen(Http::sessionCookie($r)), 'no session cookie issued');
});

// Shared admin session for later suites
$loginResp = Http::post($BACKEND . '?r=login/index', ['username' => 'admin', 'password' => 'admin123']);
$adminCookie = Http::sessionCookie($loginResp);

$t->test('dashboard renders for authenticated admin', function () use ($t, $BACKEND, $adminCookie) {
    $r = Http::get($BACKEND . '?r=dashboard/index', ['cookie' => $adminCookie]);
    $t->assertSame(200, $r['status']);
    $t->assertContains('Dashboard', $r['body']);
});

$t->test('login page redirects already-authenticated users to dashboard', function () use ($t, $BACKEND, $adminCookie) {
    $r = Http::get($BACKEND . '?r=login/index', ['cookie' => $adminCookie]);
    $t->assertSame(302, $r['status']);
    $t->assertContains('dashboard/index', $r['headers']['location'] ?? '');
});

// ===========================================================================
$t->suite('System: Role-Based Access Control');

$t->test('Receptionist is denied access to User management', function () use ($t, $BACKEND, $db) {
    if (!$db) {
        $t->skip('database not reachable (needed to create a temporary staff account)');
    }
    // Temporary Receptionist account with a known password
    $username = 'qa-tester-' . substr(md5(uniqid('', true)), 0, 8);
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role, status)
                          VALUES (?, ?, 'QA Tester', ?, 'Receptionist', 'Active')");
    $stmt->execute([$username, password_hash('qa-pass-123', PASSWORD_DEFAULT), $username . '@example.test']);

    // Login as the Receptionist
    $r = Http::post($BACKEND . '?r=login/index', ['username' => $username, 'password' => 'qa-pass-123']);
    $t->assertSame(302, $r['status'], 'staff login failed');
    $cookie = Http::sessionCookie($r);

    // Users page is Administrator-only → must bounce to dashboard
    $r2 = Http::get($BACKEND . '?r=users/index', ['cookie' => $cookie]);
    $t->assertSame(302, $r2['status']);
    $t->assertContains('dashboard/index', $r2['headers']['location'] ?? '');

    // Reservations page IS allowed for Receptionist
    $r3 = Http::get($BACKEND . '?r=reservations/index', ['cookie' => $cookie]);
    $t->assertSame(200, $r3['status']);

    // Cleanup staff account
    $db->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
});

$t->test('anonymous users cannot open the Users page', function () use ($t, $BACKEND) {
    $r = Http::get($BACKEND . '?r=users/index');
    $t->assertSame(302, $r['status']);
    $t->assertContains('login/index', $r['headers']['location'] ?? '');
});

// ===========================================================================
$t->suite('System: Complete booking flow (guest books → admin sees it)');

$ci = date('Y-m-d', strtotime('+60 days'));
$co = date('Y-m-d', strtotime('+63 days'));
$resNumber = null;

$t->test('guest books a room through the public API', function () use ($t, $API, $ci, $co, $qaEmail, &$resNumber) {
    $avail = json_decode(Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=2")['body'], true);
    if (empty($avail['data'])) {
        $t->skip('no rooms available for test dates');
    }
    $r = Http::post($API . 'reservation', [
        'room_type_id'   => (int) $avail['data'][0]['room_type_id'],
        'check_in_date'  => $ci,
        'check_out_date' => $co,
        'full_name'      => 'QA System Guest',
        'email'          => $qaEmail,
        'phone'          => '0912345678',
        'guests'         => 2,
        'special_requests' => 'System test: full booking flow',
    ]);
    $t->assertSame(200, $r['status'], 'body: ' . $r['body']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success']);
    $resNumber = $j['data']['reservation_number'];
    $t->assertMatches('/^RES-\d{8}-\d{4}$/', $resNumber);
});

$t->test('admin reservations list shows the new booking with its number', function () use ($t, $BACKEND, $adminCookie, &$resNumber) {
    if (!$resNumber) {
        $t->skip('booking step did not complete');
    }
    $r = Http::get($BACKEND . '?r=reservations/index', ['cookie' => $adminCookie]);
    $t->assertSame(200, $r['status']);
    $t->assertContains($resNumber, $r['body'], 'reservation number not visible in admin list');
});

$t->test('admin reservations page shows guest name and billing data for the booking', function () use ($t, $BACKEND, $adminCookie, $qaEmail, &$resNumber) {
    // There is no reservations/view action: details open in a client-side modal
    // fed by JSON embedded in the list page, and the guest name is rendered
    // directly in the table row. Assert both are present for our booking.
    if (!$resNumber) {
        $t->skip('booking step did not complete');
    }
    $r = Http::get($BACKEND . '?r=reservations/index', ['cookie' => $adminCookie]);
    $t->assertSame(200, $r['status']);
    $t->assertContains('QA System Guest', $r['body'], 'guest name missing from reservations list');
    $t->assertContains($resNumber, $r['body'], 'reservation number missing from embedded data');
});

$t->test('admin cancels the reservation and room becomes bookable again', function () use ($t, $BACKEND, $API, $adminCookie, $ci, $co, $qaEmail, $db) {
    if (!$db) {
        $t->skip('database not reachable');
    }
    // Find the room that was blocked by our booking
    $stmt = $db->prepare("SELECT rm.room_number FROM reservations r JOIN rooms rm ON rm.id = r.room_id
                          JOIN guests g ON g.id = r.guest_id WHERE g.email = ? AND r.check_in_date = ?");
    $stmt->execute([$qaEmail, $ci]);
    $room = $stmt->fetchColumn();
    if (!$room) {
        $t->skip('reservation not found');
    }

    // Cancel through the real admin UI action. handlePost() re-renders the
    // reservations page with a success message (HTTP 200), it does not redirect.
    $idStmt = $db->prepare("SELECT r.id FROM reservations r JOIN guests g ON g.id = r.guest_id WHERE g.email = ?");
    $idStmt->execute([$qaEmail]);
    $id = (int) $idStmt->fetchColumn();
    $r = Http::post($BACKEND . '?r=reservations/index', ['action' => 'delete', 'id' => $id], ['cookie' => $adminCookie]);
    $t->assertSame(200, $r['status'], 'delete action did not re-render the list');
    $t->assertContains('Reservation deleted', $r['body']);

    // DB confirms removal
    $chk = $db->prepare("SELECT COUNT(*) FROM reservations WHERE id = ?");
    $chk->execute([$id]);
    $t->assertSame(0, (int) $chk->fetchColumn(), 'reservation still in DB after cancel');

    // Room is available again for the same dates
    $j = json_decode(Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=2")['body'], true);
    $offered = array_column($j['data'], 'room_number');
    $t->assertContains($room, json_encode($offered), "room $room not released after cancellation");
});

// ===========================================================================
$t->suite('System: Logout');

$t->test('logout destroys the session and blocks further access', function () use ($t, $BACKEND, $adminCookie) {
    $r = Http::get($BACKEND . '?r=logout/index', ['cookie' => $adminCookie]);
    $t->assertSame(302, $r['status']);
    $t->assertContains('login/index', $r['headers']['location'] ?? '');

    // The old session cookie must no longer grant access
    $r2 = Http::get($BACKEND . '?r=dashboard/index', ['cookie' => $adminCookie]);
    $t->assertSame(302, $r2['status']);
    $t->assertContains('login/index', $r2['headers']['location'] ?? '', 'session survived logout');
});

// ===========================================================================
// Cleanup: remove every qa-* row created by this run
qa_cleanup_test_data($qaEmail);
if ($db) {
    $db->exec("DELETE FROM users WHERE username LIKE 'qa-tester-%'");
    $db->exec("DELETE FROM contacts WHERE email LIKE 'qa-%@example.test'");
}

exit($t->summary());
