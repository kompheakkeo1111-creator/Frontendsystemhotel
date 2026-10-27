<?php
/**
 * Minimal dependency-free test framework (no PHPUnit / Composer needed).
 *
 * Usage:
 *   $t = new TestRunner();
 *   $t->suite('Unit: ...');
 *   $t->test('computeNights returns 3 for 3 nights', function () use ($t) {
 *       $t->assertSame(3, $actual);
 *   });
 *   exit($t->summary());
 */

/** Thrown by TestRunner::skip() to mark a test as skipped (not failed). */
class SkipTest extends Exception {}

class TestRunner
{
    /** @var int */
    private $passed = 0;
    /** @var int */
    private $failed = 0;
    /** @var int */
    private $skipped = 0;
    /** @var string[] */
    private $failures = [];
    /** @var string */
    private $currentSuite = '';

    public function suite($name)
    {
        $this->currentSuite = $name;
        $this->line('');
        $this->line('== ' . $name . ' ==');
    }

    /**
     * Run a single test case. Pass/fail/skip is recorded from the outcome.
     * @param string   $name Test case name (goes into TESTING.md table)
     * @param callable $fn   function (TestRunner $t)
     */
    public function test($name, $fn)
    {
        try {
            $fn($this);
            $this->passed++;
            $this->line('  [PASS] ' . $name);
        } catch (SkipTest $e) {
            $this->skipped++;
            $this->line('  [SKIP] ' . $name . ' -- ' . $e->getMessage());
        } catch (Throwable $e) {
            $this->failed++;
            $this->failures[] = $this->currentSuite . ' > ' . $name . ': ' . $e->getMessage();
            $this->line('  [FAIL] ' . $name);
            $this->line('         ' . $e->getMessage());
        }
    }

    /** Record a whole suite as skipped (e.g. server/DB not reachable). */
    public function skipSuite($name, $reason)
    {
        $this->suite($name);
        $this->skipped++;
        $this->line('  [SKIP] suite skipped -- ' . $reason);
    }

    /** Assert two values are identical (===). */
    public function assertSame($expected, $actual, $context = '')
    {
        if ($expected !== $actual) {
            throw new Exception(
                ($context ? $context . ' | ' : '') .
                'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
            );
        }
    }

    /** Assert a value equals another (==, type-juggling allowed). */
    public function assertEquals($expected, $actual, $context = '')
    {
        if ($expected != $actual) {
            throw new Exception(
                ($context ? $context . ' | ' : '') .
                'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
            );
        }
    }

    /** Assert haystack contains needle (string). */
    public function assertContains($needle, $haystack, $context = '')
    {
        if (strpos((string)$haystack, (string)$needle) === false) {
            $snippet = is_string($haystack) ? substr($haystack, 0, 300) : var_export($haystack, true);
            throw new Exception(
                ($context ? $context . ' | ' : '') .
                'expected to find "' . $needle . '" in: ' . $snippet
            );
        }
    }

    /** Assert haystack does NOT contain needle (string). */
    public function assertNotContains($needle, $haystack, $context = '')
    {
        if (strpos((string)$haystack, (string)$needle) !== false) {
            throw new Exception(($context ? $context . ' | ' : '') . 'did not expect "' . $needle . '" to be present');
        }
    }

    /** Assert a string matches a regex pattern. */
    public function assertMatches($pattern, $subject, $context = '')
    {
        if (!preg_match($pattern, $subject)) {
            throw new Exception(($context ? $context . ' | ' : '') . 'pattern ' . $pattern . ' did not match: ' . substr((string)$subject, 0, 200));
        }
    }

    /** Assert a value is strictly greater than another. */
    public function assertGreaterThan($min, $actual, $context = '')
    {
        if ($actual <= $min) {
            throw new Exception(($context ? $context . ' | ' : '') . 'expected > ' . var_export($min, true) . ', got ' . var_export($actual, true));
        }
    }

    /** Assert a value is greater than or equal to another. */
    public function assertGreaterThanOrEqual($min, $actual, $context = '')
    {
        if ($actual < $min) {
            throw new Exception(($context ? $context . ' | ' : '') . 'expected >= ' . var_export($min, true) . ', got ' . var_export($actual, true));
        }
    }

    /** Assert a boolean is true. */
    public function assertTrue($actual, $context = '')
    {
        if ($actual !== true) {
            throw new Exception(($context ? $context . ' | ' : '') . 'expected true, got ' . var_export($actual, true));
        }
    }

    /** Unconditionally fail. */
    public function fail($message)
    {
        throw new Exception($message);
    }

    /** Mark the current test as skipped. */
    public function skip($reason)
    {
        throw new SkipTest($reason);
    }

    /** Print totals and return process exit code (0 = all good). */
    public function summary()
    {
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line(sprintf(
            'TOTAL: %d passed, %d failed, %d skipped',
            $this->passed,
            $this->failed,
            $this->skipped
        ));
        if ($this->failures) {
            $this->line('');
            $this->line('FAILURES:');
            foreach ($this->failures as $f) {
                $this->line('  - ' . $f);
            }
        }
        $this->line(str_repeat('=', 60));
        return $this->failed > 0 ? 1 : 0;
    }

    private function line($text)
    {
        echo $text . PHP_EOL;
    }
}
