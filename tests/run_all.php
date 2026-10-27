<?php
/**
 * Unified test runner — executes all three testing levels in order:
 *
 *   1. Unit        pure logic (PHP reflection + JS functions via Node)
 *   2. Integration API endpoints over real HTTP
 *   3. System      end-to-end flows (login → booking → admin)
 *
 * Each suite runs in its own PHP process so sessions, constants and
 * output buffering never leak between levels.
 *
 * Usage:
 *   php tests/run_all.php                 # run everything
 *   php tests/run_all.php --unit          # only unit suites
 *   php tests/run_all.php --integration   # only integration suite
 *   php tests/run_all.php --system        # only system suite
 *
 * Environment:
 *   TEST_HTTP_BASE   base URL of the running app
 *                    (default: http://localhost/arizu-arimato%201.2)
 *
 * Exit code: 0 when all executed suites passed (skips are allowed), 1 otherwise.
 */

$suites = [
    'Unit (PHP: reservation logic, router, auth helpers)' => __DIR__ . '/unit/unit_tests.php',
    'Unit (JS: frontend calcNights / formatPrice)'       => __DIR__ . '/unit/frontend_calc.test.js',
    'Integration (API over HTTP)'                        => __DIR__ . '/integration/api_tests.php',
    'System (login → booking → admin)'                   => __DIR__ . '/system/system_tests.php',
];

// --- Argument filtering -----------------------------------------------------
$args = array_slice($argv, 1);
$only = [];
foreach ($args as $a) {
    if ($a === '--unit')        { $only[] = 'unit'; }
    elseif ($a === '--integration') { $only[] = 'integration'; }
    elseif ($a === '--system')  { $only[] = 'system'; }
    elseif ($a === '--help' || $a === '-h') {
        echo "Usage: php tests/run_all.php [--unit] [--integration] [--system]\n";
        exit(0);
    }
}
$filters = array_unique($only);

function matchesFilter($name, array $filters)
{
    if (!$filters) {
        return true;
    }
    foreach ($filters as $f) {
        if (stripos($name, $f) !== false) {
            return true;
        }
    }
    return false;
}

// --- Execute ----------------------------------------------------------------
$startTime = microtime(true);
$failedSuites = [];
$ran = 0;

echo str_repeat('=', 70) . PHP_EOL;
echo ' ARIZU ARIMATO HOTEL — TEST SUITE' . PHP_EOL;
echo ' Started: ' . date('Y-m-d H:i:s') . PHP_EOL;
echo ' PHP:     ' . PHP_VERSION . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

foreach ($suites as $name => $file) {
    if (!matchesFilter($name, $filters)) {
        continue;
    }
    $ran++;

    echo PHP_EOL . str_repeat('-', 70) . PHP_EOL;
    echo ' SUITE: ' . $name . PHP_EOL;
    echo str_repeat('-', 70) . PHP_EOL;

    if (substr($file, -3) === '.js') {
        // JavaScript unit tests run through Node.js
        $cmd = 'node ' . escapeshellarg($file);
        passthru($cmd, $code);
    } else {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file);
        passthru($cmd, $code);
    }

    if ($code !== 0) {
        $failedSuites[] = $name . ' (exit code ' . $code . ')';
    }
}

$elapsed = round(microtime(true) - $startTime, 1);

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
if ($failedSuites) {
    echo ' RESULT: FAILED — ' . count($failedSuites) . ' suite(s) had failures:' . PHP_EOL;
    foreach ($failedSuites as $f) {
        echo '   - ' . $f . PHP_EOL;
    }
} elseif ($ran === 0) {
    echo ' RESULT: no suites matched the given filters.' . PHP_EOL;
} else {
    echo ' RESULT: ALL ' . $ran . ' SUITE(S) PASSED' . PHP_EOL;
}
echo ' Elapsed: ' . $elapsed . 's' . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

exit($failedSuites ? 1 : 0);
