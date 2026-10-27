#!/usr/bin/env node
/**
 * UNIT TESTS — frontend pure functions in frontend/js/api.js
 * (per assignment rubric: "calcNights(), price calculation ...").
 *
 * The browser script is evaluated inside a sandbox (node:vm) and the two
 * pure functions are extracted; no DOM or fetch is needed.
 *
 * Run standalone:  node tests/unit/frontend_calc.test.js
 * Or via runner:   php tests/run_all.php
 */
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

// --- Load the real browser file and extract the pure helpers ----------------
const apiPath = path.join(__dirname, '..', '..', 'frontend', 'js', 'api.js');
const source = fs.readFileSync(apiPath, 'utf8');

const sandbox = { console };
vm.createContext(sandbox);
vm.runInContext(
    source + '\n;globalThis.__extracted = { calcNights, formatPrice };',
    sandbox,
    { filename: 'api.js' }
);
const { calcNights, formatPrice } = sandbox.__extracted;

// --- Minimal runner (same output shape as the PHP TestRunner) ---------------
let passed = 0;
let failed = 0;
const failures = [];

function test(name, fn) {
    try {
        fn();
        passed++;
        console.log('  [PASS] ' + name);
    } catch (err) {
        failed++;
        failures.push(name + ': ' + err.message);
        console.log('  [FAIL] ' + name);
        console.log('         ' + err.message);
    }
}

function check(name, actual, expected) {
    if (actual !== expected) {
        throw new Error(`expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
    }
}

// --- calcNights -------------------------------------------------------------
console.log('\n== Unit (JS): api.js calcNights (stay length) ==');

test('calcNights returns 3 for a 3-night stay', () => {
    check('calcNights 3 nights', calcNights('2026-01-01', '2026-01-04'), 3);
});

test('calcNights returns 1 for a 1-night stay', () => {
    check('calcNights 1 night', calcNights('2026-01-01', '2026-01-02'), 1);
});

test('calcNights enforces minimum 1 for same-day stay', () => {
    check('calcNights same day', calcNights('2026-01-01', '2026-01-01'), 1);
});

test('calcNights never returns 0 or negative for reversed dates', () => {
    check('calcNights reversed', calcNights('2026-01-04', '2026-01-01'), 1);
});

test('calcNights spans month boundary correctly (Jan 30 → Feb 2 = 3)', () => {
    check('calcNights month span', calcNights('2026-01-30', '2026-02-02'), 3);
});

test('calcNights handles leap-day span (Feb 27 → Mar 1 2028 = 3)', () => {
    check('calcNights leap year', calcNights('2028-02-27', '2028-03-01'), 3);
});

// --- formatPrice ------------------------------------------------------------
console.log('\n== Unit (JS): api.js formatPrice (price formatting) ==');

test('formatPrice uses USD and 2 decimals by default', () => {
    check('formatPrice default', formatPrice(55), 'USD 55.00');
});

test('formatPrice accepts explicit currency (KHR)', () => {
    check('formatPrice KHR', formatPrice('1234.5', 'KHR'), 'KHR 1234.50');
});

test('formatPrice rounds to 2 decimals', () => {
    check('formatPrice rounding', formatPrice(49.999), 'USD 50.00');
});

test('formatPrice handles 0', () => {
    check('formatPrice zero', formatPrice(0), 'USD 0.00');
});

// --- Summary ----------------------------------------------------------------
console.log('\n============================================================');
console.log(`TOTAL: ${passed} passed, ${failed} failed, 0 skipped`);
if (failures.length) {
    console.log('\nFAILURES:');
    for (const f of failures) console.log('  - ' + f);
}
console.log('============================================================');
process.exit(failed > 0 ? 1 : 0);
