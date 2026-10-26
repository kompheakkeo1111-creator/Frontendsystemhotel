<?php
/**
 * INTEGRATION TESTS — API endpoints over real HTTP against the local server.
 *
 * Covers (per rubric: "integration: booking → room availability → billing"):
 *   - GET  api/rooms            room type catalog
 *   - GET  api/view             room type detail (400/404 error paths)
 *   - GET  api/availableRooms   date validation + availability list
 *   - POST api/reservation      booking creation, total calculation,
 *                               double-booking prevention, availability impact
 *   - POST api/contact          contact form validation + persistence
 *   - GET  api/settings         public hotel settings
 *   - Auth guards on protected admin routes
 *
 * Requires: Apache serving the app + reachable MySQL.
 * Override base URL: set TEST_HTTP_BASE (default: local WAMP folder).
 *
 * Run standalone:  php tests/integration/api_tests.php
 */

require_once __DIR__ . '/../lib/TestRunner.php';
require_once __DIR__ . '/../lib/Http.php';
require_once __DIR__ . '/../lib/Db.php';
require_once __DIR__ . '/../../backend/bootstrap.php'; // DB constants for qa_pdo()

$t = new TestRunner();

$BASE = getenv('TEST_HTTP_BASE') ?: 'http://localhost/arizu-arimato%201.2';
$API  = $BASE . '/backend/index.php?r=api/';

// --- Preflight: server must be reachable, otherwise skip the suite ----------
try {
    Http::get($API . 'settings');
} catch (RuntimeException $e) {
    $t->skipSuite('Integration: Backend API over HTTP', 'server not reachable at ' . $BASE .
        ' — start Apache (WAMP) or set TEST_HTTP_BASE. Error: ' . $e->getMessage());
    exit($t->summary());
}

// Unique QA marker so cleanup can never touch real data
$qaEmail = 'qa-' . substr(md5(uniqid('', true)), 0, 10) . '@example.test';

// ===========================================================================
$t->suite('Integration: GET api/rooms (room type catalog)');

$t->test('roomsAction returns success with a non-empty room type list', function () use ($t, $API) {
    $r = Http::get($API . 'rooms');
    $t->assertSame(200, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success']);
    $t->assertGreaterThanOrEqual(1, count($j['data']));
});

$t->test('every room type exposes a numeric price and capacity', function () use ($t, $API) {
    $j = json_decode(Http::get($API . 'rooms')['body'], true);
    foreach ($j['data'] as $type) {
        $t->assertTrue(is_numeric($type['price_per_night']), 'price not numeric: ' . var_export($type, true));
        $t->assertTrue(is_numeric($type['capacity']), 'capacity not numeric');
        $t->assertGreaterThan(0, (float) $type['price_per_night']);
    }
});

// ===========================================================================
$t->suite('Integration: GET api/view (room type detail + error paths)');

$t->test('view returns room type with attached rooms array', function () use ($t, $API) {
    $r = Http::get($API . 'view&id=1');
    $t->assertSame(200, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success']);
    $t->assertGreaterThanOrEqual(1, strlen($j['data']['type_name']));
    $t->assertTrue(is_array($j['data']['rooms']));
    $t->assertGreaterThanOrEqual(1, count($j['data']['rooms']));
    $t->assertGreaterThanOrEqual(1, strlen($j['data']['rooms'][0]['room_number']));
});

$t->test('view rejects missing/invalid id with 400', function () use ($t, $API) {
    $r = Http::get($API . 'view&id=0');
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(false, $j['success']);
});

$t->test('view returns 404 for a non-existent room type', function () use ($t, $API) {
    $r = Http::get($API . 'view&id=999999');
    $t->assertSame(404, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(false, $j['success']);
});

// ===========================================================================
$t->suite('Integration: GET api/availableRooms (date validation)');

$t->test('missing dates are rejected with 400', function () use ($t, $API) {
    $r = Http::get($API . 'availableRooms');
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertContains('check_in and check_out', $j['message']);
});

$t->test('check_out equal to check_in is rejected with 400', function () use ($t, $API) {
    $d = date('Y-m-d', strtotime('+30 days'));
    $r = Http::get($API . "availableRooms&check_in=$d&check_out=$d");
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertContains('check_out must be after check_in', $j['message']);
});

$t->test('valid future dates return available rooms with prices', function () use ($t, $API) {
    $ci = date('Y-m-d', strtotime('+35 days'));
    $co = date('Y-m-d', strtotime('+37 days'));
    $r = Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=2");
    $t->assertSame(200, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success']);
    $t->assertTrue(is_array($j['data']));
    foreach ($j['data'] as $room) {
        $t->assertTrue(is_numeric($room['price_per_night']));
        $t->assertGreaterThanOrEqual(1, strlen($room['room_number']));
    }
});

$t->test('rooms in Maintenance status are never offered (DB cross-check)', function () use ($t, $API) {
    $db = qa_pdo();
    if (!$db) {
        $t->skip('database not reachable for cross-check');
    }
    $ci = date('Y-m-d', strtotime('+35 days'));
    $co = date('Y-m-d', strtotime('+37 days'));
    $j = json_decode(Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=1")['body'], true);
    $offered = array_column($j['data'], 'room_number');
    $maint = $db->query("SELECT room_number FROM rooms WHERE status = 'Maintenance'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($maint as $rn) {
        $t->assertNotContains($rn, json_encode($offered), "Maintenance room $rn was offered as available");
    }
});

// ===========================================================================
$t->suite('Integration: POST api/reservation (booking creation & billing)');

$t->test('reservation rejects missing fields with 400', function () use ($t, $API) {
    $r = Http::post($API . 'reservation', ['full_name' => 'QA']);
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertContains('Missing required fields', $j['message']);
});

$t->test('reservation rejects check_out before check_in with 400', function () use ($t, $API, $qaEmail) {
    $ci = date('Y-m-d', strtotime('+40 days'));
    $co = date('Y-m-d', strtotime('+39 days'));
    $r = Http::post($API . 'reservation', [
        'room_type_id' => 1, 'check_in_date' => $ci, 'check_out_date' => $co,
        'full_name' => 'QA Guest', 'email' => $qaEmail, 'phone' => '012345678', 'guests' => 1,
    ]);
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertContains('check_out must be after check_in', $j['message']);
});

$t->test('reservation rejects GET method with 405', function () use ($t, $API) {
    $r = Http::get($API . 'reservation');
    $t->assertSame(405, $r['status']);
});

// --- The core happy path: book a real available room ------------------------
$ci = date('Y-m-d', strtotime('+45 days'));
$co = date('Y-m-d', strtotime('+48 days'));
$bookedRoom = null;

$t->test('valid booking succeeds with correct nights and positive total', function () use ($t, $API, $ci, $co, $qaEmail, &$bookedRoom) {
    // Pick a room type that actually has an available room for the dates
    $avail = json_decode(Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=2")['body'], true);
    if (empty($avail['data'])) {
        $t->skip('no rooms available for test dates');
    }
    $roomTypeId = (int) $avail['data'][0]['room_type_id'];

    $r = Http::post($API . 'reservation', [
        'room_type_id'   => $roomTypeId,
        'check_in_date'  => $ci,
        'check_out_date' => $co,
        'full_name'      => 'QA Test Guest',
        'email'          => $qaEmail,
        'phone'          => '0987654321',
        'guests'         => 2,
        'special_requests' => 'QA automated test booking',
    ]);
    $t->assertSame(200, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success'], 'body: ' . $r['body']);
    $t->assertMatches('/^RES-\d{8}-\d{4}$/', $j['data']['reservation_number']);
    $t->assertSame(3, $j['data']['nights']);
    $t->assertGreaterThan(0, (float) $j['data']['total']);
    $bookedRoom = $j['data']['room_number'];
    $t->assertGreaterThanOrEqual(1, strlen($bookedRoom));
});

$t->test('booking is persisted with status Pending and matching total (DB)', function () use ($t, $qaEmail, $ci, $co, &$bookedRoom) {
    $db = qa_pdo();
    if (!$db) {
        $t->skip('database not reachable');
    }
    if (!$bookedRoom) {
        $t->skip('previous booking test did not complete');
    }
    $stmt = $db->prepare("
        SELECT res.status, res.total_amount, res.check_in_date, res.check_out_date, rm.room_number
        FROM reservations res JOIN rooms rm ON rm.id = res.room_id
        JOIN guests g ON g.id = res.guest_id
        WHERE g.email = ? AND res.check_in_date = ? AND res.check_out_date = ?
    ");
    $stmt->execute([$qaEmail, $ci, $co]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $t->assertTrue((bool) $row, 'reservation row not found in DB');
    $t->assertSame('Pending', $row['status']);
    $t->assertSame($bookedRoom, $row['room_number']);
    // Billing: total must equal nights (3) x nightly rate, 2 decimals
    $price = (float) $db->prepare("SELECT price_per_night FROM rooms WHERE room_number = ?")
        ->execute([$bookedRoom]) ?: null;
    $stmt2 = $db->prepare("SELECT price_per_night FROM rooms WHERE room_number = ?");
    $stmt2->execute([$bookedRoom]);
    $nightly = (float) $stmt2->fetchColumn();
    $t->assertSame(round(3 * $nightly, 2), round((float) $row['total_amount'], 2));
});

$t->test('booked room disappears from availableRooms for the same dates', function () use ($t, $API, $ci, $co, &$bookedRoom) {
    if (!$bookedRoom) {
        $t->skip('previous booking test did not complete');
    }
    $j = json_decode(Http::get($API . "availableRooms&check_in=$ci&check_out=$co&guests=2")['body'], true);
    $offered = array_column($j['data'], 'room_number');
    $t->assertNotContains($bookedRoom, json_encode($offered),
        "room $bookedRoom was booked but still offered as available");
});

$t->test('double-booking the same room and dates is prevented', function () use ($t, $API, $ci, $co, $qaEmail, &$bookedRoom) {
    if (!$bookedRoom) {
        $t->skip('previous booking test did not complete');
    }
    $db = qa_pdo();
    if (!$db) {
        $t->skip('database not reachable');
    }
    $stmt = $db->prepare("SELECT room_type_id FROM rooms WHERE room_number = ?");
    $stmt->execute([$bookedRoom]);
    $roomTypeId = (int) $stmt->fetchColumn();

    $r = Http::post($API . 'reservation', [
        'room_type_id'   => $roomTypeId,
        'check_in_date'  => $ci,
        'check_out_date' => $co,
        'full_name'      => 'QA Double Book',
        'email'          => 'qa-double-' . substr(md5(uniqid('', true)), 0, 8) . '@example.test',
        'phone'          => '0987654322',
        'guests'         => 2,
    ]);
    if ($r['status'] === 200) {
        // Another room of the same type was available — must be a DIFFERENT room
        $j = json_decode($r['body'], true);
        $t->assertNotContains($bookedRoom, $j['data']['room_number'], 'same room assigned twice!');
    } else {
        // No room left for this type — clean rejection
        $t->assertSame(400, $r['status']);
        $j = json_decode($r['body'], true);
        $t->assertContains('No rooms available', $j['message']);
    }
});

// ===========================================================================
$t->suite('Integration: POST api/contact (message validation & storage)');

$t->test('contact rejects invalid email with 400', function () use ($t, $API) {
    $r = Http::post($API . 'contact', [
        'full_name' => 'QA', 'email' => 'not-an-email', 'subject' => 'hi', 'message' => 'test',
    ]);
    $t->assertSame(400, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertContains('Invalid email address', $j['message']);
});

$t->test('contact rejects missing required fields with 400', function () use ($t, $API) {
    $r = Http::post($API . 'contact', ['full_name' => 'QA']);
    $t->assertSame(400, $r['status']);
});

$t->test('valid contact message is stored in DB', function () use ($t, $API, $qaEmail) {
    $r = Http::post($API . 'contact', [
        'full_name' => 'QA Contact', 'email' => $qaEmail, 'phone' => '011111111',
        'subject' => 'QA automated test', 'message' => 'Integration test message.',
    ]);
    $t->assertSame(200, $r['status']);
    $j = json_decode($r['body'], true);
    $t->assertSame(true, $j['success']);
    $db = qa_pdo();
    if (!$db) {
        $t->skip('database not reachable for persistence check');
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM contacts WHERE email = ? AND subject = ?");
    $stmt->execute([$qaEmail, 'QA automated test']);
    $t->assertSame(1, (int) $stmt->fetchColumn());
});

// ===========================================================================
$t->suite('Integration: GET api/settings + auth guards');

$t->test('settings returns hotel name and currency code', function () use ($t, $API) {
    $j = json_decode(Http::get($API . 'settings')['body'], true);
    $t->assertSame(true, $j['success']);
    $t->assertGreaterThanOrEqual(1, strlen($j['data']['hotel_name']));
    $t->assertMatches('/^[A-Z]{3}$/', $j['data']['currency']);
    // Note: tax_rate is intentionally NOT part of the public settings payload.
});

$t->test('protected admin route redirects anonymous users to login', function () use ($t, $BASE) {
    $r = Http::get($BASE . '/backend/index.php?r=reservations/index');
    $t->assertSame(302, $r['status']);
    $t->assertContains('login/index', $r['headers']['location'] ?? '');
});

$t->test('dashboard redirects anonymous users to login', function () use ($t, $BASE) {
    $r = Http::get($BASE . '/backend/index.php?r=dashboard/index');
    $t->assertSame(302, $r['status']);
    $t->assertContains('login/index', $r['headers']['location'] ?? '');
});

// ===========================================================================
// Cleanup: remove every qa-* row created by this run
qa_cleanup_test_data($qaEmail);
$db = qa_pdo();
if ($db) {
    $db->exec("DELETE FROM contacts WHERE email LIKE 'qa-%@example.test'");
}

exit($t->summary());
