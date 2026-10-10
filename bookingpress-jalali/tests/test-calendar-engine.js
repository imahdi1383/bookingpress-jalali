/**
 * Tests for BPJalaliEngine (JavaScript Calendar Engine).
 *
 * Run with: node tests/test-calendar-engine.js
 *
 * Covers:
 *   - Gregorian ↔ Jalali conversions
 *   - Jalali leap years
 *   - Month lengths
 *   - Farvardin 1 boundaries
 *   - Esfand 29/30 handling
 *   - Round-trip conversions
 *   - Date validation
 *   - Date formatting
 *   - String conversion
 */

// Load the engine (simulate browser global).
var window = {};
var engineScript = require('fs').readFileSync(__dirname + '/../assets/js/bpjalali-calendar-engine.js', 'utf8');
eval(engineScript);
var Engine = window.BPJalaliEngine;

var pass = 0;
var fail = 0;

function assertEquals(expected, actual, label) {
    var eq = JSON.stringify(expected) === JSON.stringify(actual);
    if (eq) {
        pass++;
        console.log('  ✓ ' + label);
    } else {
        fail++;
        console.log('  ✗ ' + label);
        console.log('    Expected: ' + JSON.stringify(expected));
        console.log('    Actual:   ' + JSON.stringify(actual));
    }
}

function assertTrue(actual, label) {
    assertEquals(true, actual, label);
}

function assertFalse(actual, label) {
    assertEquals(false, actual, label);
}

// ── Gregorian to Jalali ──────────────────────────────────────

console.log('\n=== Gregorian → Jalali ===');

assertEquals([1399, 1, 1], Engine.gregorianToJalali(2020, 3, 20), 'Farvardin 1, 1399 (2020-03-20)');
assertEquals([1400, 1, 1], Engine.gregorianToJalali(2021, 3, 21), 'Farvardin 1, 1400 (2021-03-21)');
assertEquals([1401, 1, 1], Engine.gregorianToJalali(2022, 3, 21), 'Farvardin 1, 1401 (2022-03-21)');
assertEquals([1403, 1, 1], Engine.gregorianToJalali(2024, 3, 20), 'Farvardin 1, 1403 (2024-03-20)');
assertEquals([1404, 1, 1], Engine.gregorianToJalali(2025, 3, 21), 'Farvardin 1, 1404 (2025-03-21)');
assertEquals([1405, 7, 17], Engine.gregorianToJalali(2026, 10, 9), 'Today (2026-10-09) → Mehr 17, 1405');
assertEquals([1357, 11, 22], Engine.gregorianToJalali(1979, 2, 11), 'Iranian Revolution');
assertEquals([1370, 1, 1], Engine.gregorianToJalali(1991, 3, 21), 'Farvardin 1, 1370');

// ── Jalali to Gregorian ──────────────────────────────────────

console.log('\n=== Jalali → Gregorian ===');

assertEquals([2020, 3, 20], Engine.jalaliToGregorian(1399, 1, 1), '1399/1/1 → 2020-03-20');
assertEquals([2021, 3, 21], Engine.jalaliToGregorian(1400, 1, 1), '1400/1/1 → 2021-03-21');
assertEquals([2024, 3, 20], Engine.jalaliToGregorian(1403, 1, 1), '1403/1/1 → 2024-03-20');
assertEquals([1979, 2, 11], Engine.jalaliToGregorian(1357, 11, 22), '1357/11/22 → 1979-02-11');

// ── Round-trip Conversions ───────────────────────────────────

console.log('\n=== Round-trip Conversions ===');

var roundTripDates = [
    [1399, 1, 1],
    [1400, 12, 30],
    [1399, 12, 30],
    [1398, 12, 29],
    [1401, 6, 31],
    [1401, 7, 1],
    [1405, 7, 17],
    [1350, 1, 1],
    [1300, 1, 1],
    [1450, 12, 29],
];

roundTripDates.forEach(function (jdate) {
    var g = Engine.jalaliToGregorian(jdate[0], jdate[1], jdate[2]);
    var j = Engine.gregorianToJalali(g[0], g[1], g[2]);
    assertEquals(jdate, j, 'Round-trip: ' + jdate.join('/') + ' → ' + g.join('-') + ' → ' + j.join('/'));
});

// ── Leap Years ───────────────────────────────────────────────

console.log('\n=== Jalali Leap Years ===');

[1399, 1403, 1408, 1391, 1395, 1375, 1370, 1354, 1358, 1362, 1366].forEach(function (y) {
    assertTrue(Engine.isJalaliLeapYear(y), 'Year ' + y + ' is leap');
});

[1400, 1401, 1402, 1404, 1405, 1397, 1398, 1393, 1394, 1396].forEach(function (y) {
    assertFalse(Engine.isJalaliLeapYear(y), 'Year ' + y + ' is not leap');
});

// ── Month Lengths ────────────────────────────────────────────

console.log('\n=== Month Lengths ===');

for (var m = 1; m <= 6; m++) {
    assertEquals(31, Engine.jalaliMonthLength(1400, m), 'Month ' + m + ' = 31 days');
}
for (var m = 7; m <= 11; m++) {
    assertEquals(30, Engine.jalaliMonthLength(1400, m), 'Month ' + m + ' = 30 days');
}

assertEquals(29, Engine.jalaliMonthLength(1400, 12), 'Esfand 1400 (non-leap) = 29 days');
assertEquals(29, Engine.jalaliMonthLength(1401, 12), 'Esfand 1401 (non-leap) = 29 days');
assertEquals(30, Engine.jalaliMonthLength(1399, 12), 'Esfand 1399 (leap) = 30 days');
assertEquals(30, Engine.jalaliMonthLength(1403, 12), 'Esfand 1403 (leap) = 30 days');

// ── Date Validation ──────────────────────────────────────────

console.log('\n=== Date Validation ===');

assertTrue(Engine.isValidJalaliDate(1400, 1, 1), 'Valid: 1400/1/1');
assertTrue(Engine.isValidJalaliDate(1400, 6, 31), 'Valid: 1400/6/31');
assertTrue(Engine.isValidJalaliDate(1400, 7, 30), 'Valid: 1400/7/30');
assertTrue(Engine.isValidJalaliDate(1400, 12, 29), 'Valid: 1400/12/29 (non-leap)');
assertTrue(Engine.isValidJalaliDate(1399, 12, 30), 'Valid: 1399/12/30 (leap)');

assertFalse(Engine.isValidJalaliDate(1400, 12, 30), 'Invalid: 1400/12/30 (non-leap)');
assertFalse(Engine.isValidJalaliDate(1400, 0, 1), 'Invalid: month 0');
assertFalse(Engine.isValidJalaliDate(1400, 13, 1), 'Invalid: month 13');
assertFalse(Engine.isValidJalaliDate(1400, 1, 32), 'Invalid: day 32');
assertFalse(Engine.isValidJalaliDate(1400, 7, 31), 'Invalid: 1400/7/31');
assertFalse(Engine.isValidJalaliDate(1400, 1, 0), 'Invalid: day 0');

// ── Date Formatting ──────────────────────────────────────────

console.log('\n=== Date Formatting ===');

assertEquals('1405-07-17', Engine.formatJalaliDate(1405, 7, 17, 'YYYY-MM-DD'), 'Format YYYY-MM-DD');
assertEquals('Mehr 17, 1405', Engine.formatJalaliDate(1405, 7, 17, 'MMMM D, YYYY'), 'Format MMMM D, YYYY');
assertEquals('Farvardin 1, 1400', Engine.formatJalaliDate(1400, 1, 1, 'MMMM D, YYYY'), 'Farvardin 1 formatting');

// ── String Conversion ────────────────────────────────────────

console.log('\n=== Gregorian String to Jalali ===');

assertEquals('1405-07-17', Engine.gregorianStringToJalali('2026-10-09'), 'String: 2026-10-09');
assertEquals('Farvardin 1, 1400', Engine.gregorianStringToJalali('2021-03-21', 'MMMM D, YYYY'), 'String with format');
assertEquals('1399-01-01', Engine.gregorianStringToJalali('2020/03/20'), 'Slash separator');

// ── Month Names ──────────────────────────────────────────────

console.log('\n=== Month Names ===');

assertEquals('Farvardin', Engine.getMonthName(1), 'Month 1');
assertEquals('Esfand', Engine.getMonthName(12), 'Month 12');
assertEquals('Meh', Engine.getMonthName(7, true), 'Month 7 short');
assertEquals('Esf', Engine.getMonthName(12, true), 'Month 12 short');

// ── PHP ↔ JS Parity Check ───────────────────────────────────

console.log('\n=== Cross-check: Known Dates ===');

// These are verified against authoritative sources.
assertEquals([1402, 7, 13], Engine.gregorianToJalali(2023, 10, 5), '2023-10-05 → 1402/7/13');
assertEquals([1398, 10, 11], Engine.gregorianToJalali(2020, 1, 1), '2020-01-01 → 1398/10/11');
assertEquals([1404, 12, 29], Engine.gregorianToJalali(2026, 3, 20), '2026-03-20 → 1404/12/29');

// ── Summary ──────────────────────────────────────────────────

console.log('\n' + '─'.repeat(50));
console.log('Results: ' + pass + ' passed, ' + fail + ' failed');
console.log('─'.repeat(50));

process.exit(fail > 0 ? 1 : 0);
