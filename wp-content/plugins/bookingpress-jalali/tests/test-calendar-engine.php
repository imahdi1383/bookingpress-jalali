<?php
/**
 * Tests for BPJalali_Calendar_Engine.
 *
 * Run with: php tests/test-calendar-engine.php
 *
 * Covers:
 *   - Gregorian ↔ Jalali conversions
 *   - Jalali leap years
 *   - Month lengths
 *   - Farvardin 1 boundaries
 *   - Esfand 29/30 handling
 *   - Year boundaries
 *   - Round-trip conversions
 *   - Date validation
 *   - Date formatting
 *
 * @package BookingPress_Jalali
 */

// Minimal bootstrap — no WordPress needed for pure engine tests.
define( 'ABSPATH', __DIR__ . '/../' );
require_once __DIR__ . '/../includes/class-bpjalali-calendar-engine.php';

$pass = 0;
$fail = 0;

function assert_equals( $expected, $actual, string $label ): void {
    global $pass, $fail;
    if ( $expected === $actual ) {
        $pass++;
        echo "  ✓ {$label}\n";
    } else {
        $fail++;
        $expected_str = is_array( $expected ) ? json_encode( $expected ) : var_export( $expected, true );
        $actual_str   = is_array( $actual ) ? json_encode( $actual ) : var_export( $actual, true );
        echo "  ✗ {$label}\n    Expected: {$expected_str}\n    Actual:   {$actual_str}\n";
    }
}

function assert_true( bool $actual, string $label ): void {
    assert_equals( true, $actual, $label );
}

function assert_false( bool $actual, string $label ): void {
    assert_equals( false, $actual, $label );
}

// ── Gregorian to Jalali ────────────────────────────────────────

echo "\n=== Gregorian → Jalali ===\n";

assert_equals(
    array( 1399, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2020, 3, 20 ),
    'Farvardin 1, 1399 (2020-03-20)'
);

assert_equals(
    array( 1400, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2021, 3, 21 ),
    'Farvardin 1, 1400 (2021-03-21)'
);

assert_equals(
    array( 1401, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2022, 3, 21 ),
    'Farvardin 1, 1401 (2022-03-21)'
);

assert_equals(
    array( 1403, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2024, 3, 20 ),
    'Farvardin 1, 1403 (2024-03-20)'
);

assert_equals(
    array( 1404, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2025, 3, 21 ),
    'Farvardin 1, 1404 (2025-03-21)'
);

assert_equals(
    array( 1405, 7, 17 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 2026, 10, 9 ),
    'Today (2026-10-09) → Mehr 17, 1405'
);

assert_equals(
    array( 1357, 11, 22 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 1979, 2, 11 ),
    'Iranian Revolution (1979-02-11) → Bahman 22, 1357'
);

assert_equals(
    array( 1370, 1, 1 ),
    BPJalali_Calendar_Engine::gregorian_to_jalali( 1991, 3, 21 ),
    'Farvardin 1, 1370 (1991-03-21)'
);

// ── Jalali to Gregorian ────────────────────────────────────────

echo "\n=== Jalali → Gregorian ===\n";

assert_equals(
    array( 2020, 3, 20 ),
    BPJalali_Calendar_Engine::jalali_to_gregorian( 1399, 1, 1 ),
    '1399/1/1 → 2020-03-20'
);

assert_equals(
    array( 2021, 3, 21 ),
    BPJalali_Calendar_Engine::jalali_to_gregorian( 1400, 1, 1 ),
    '1400/1/1 → 2021-03-21'
);

assert_equals(
    array( 2024, 3, 20 ),
    BPJalali_Calendar_Engine::jalali_to_gregorian( 1403, 1, 1 ),
    '1403/1/1 → 2024-03-20'
);

assert_equals(
    array( 1979, 2, 11 ),
    BPJalali_Calendar_Engine::jalali_to_gregorian( 1357, 11, 22 ),
    '1357/11/22 → 1979-02-11'
);

// ── Round-trip Conversions ─────────────────────────────────────

echo "\n=== Round-trip Conversions ===\n";

$round_trip_dates = array(
    array( 1399, 1, 1 ),
    array( 1400, 12, 30 ),
    array( 1399, 12, 30 ), // Leap year, Esfand 30.
    array( 1398, 12, 29 ), // Non-leap year, Esfand 29.
    array( 1401, 6, 31 ),
    array( 1401, 7, 1 ),
    array( 1405, 7, 17 ),
    array( 1350, 1, 1 ),
    array( 1300, 1, 1 ),
    array( 1450, 12, 29 ),
);

foreach ( $round_trip_dates as $jdate ) {
    $g = BPJalali_Calendar_Engine::jalali_to_gregorian( $jdate[0], $jdate[1], $jdate[2] );
    $j = BPJalali_Calendar_Engine::gregorian_to_jalali( $g[0], $g[1], $g[2] );
    assert_equals(
        $jdate,
        $j,
        sprintf( 'Round-trip: %d/%d/%d → %d-%02d-%02d → %d/%d/%d', $jdate[0], $jdate[1], $jdate[2], $g[0], $g[1], $g[2], $j[0], $j[1], $j[2] )
    );
}

// ── Leap Years ─────────────────────────────────────────────────

echo "\n=== Jalali Leap Years ===\n";

$known_leap_years = array( 1399, 1403, 1408, 1391, 1395, 1375, 1370, 1354, 1358, 1362, 1366 );
foreach ( $known_leap_years as $ly ) {
    assert_true(
        BPJalali_Calendar_Engine::is_jalali_leap_year( $ly ),
        "Year {$ly} is leap"
    );
}

$known_non_leap_years = array( 1400, 1401, 1402, 1404, 1405, 1397, 1398, 1393, 1394, 1396 );
foreach ( $known_non_leap_years as $nly ) {
    assert_false(
        BPJalali_Calendar_Engine::is_jalali_leap_year( $nly ),
        "Year {$nly} is not leap"
    );
}

// ── Month Lengths ──────────────────────────────────────────────

echo "\n=== Month Lengths ===\n";

for ( $m = 1; $m <= 6; $m++ ) {
    assert_equals( 31, BPJalali_Calendar_Engine::jalali_month_length( 1400, $m ), "Month {$m} = 31 days" );
}

for ( $m = 7; $m <= 11; $m++ ) {
    assert_equals( 30, BPJalali_Calendar_Engine::jalali_month_length( 1400, $m ), "Month {$m} = 30 days" );
}

// Esfand in non-leap year.
assert_equals( 29, BPJalali_Calendar_Engine::jalali_month_length( 1400, 12 ), 'Esfand 1400 (non-leap) = 29 days' );
assert_equals( 29, BPJalali_Calendar_Engine::jalali_month_length( 1401, 12 ), 'Esfand 1401 (non-leap) = 29 days' );

// Esfand in leap year.
assert_equals( 30, BPJalali_Calendar_Engine::jalali_month_length( 1399, 12 ), 'Esfand 1399 (leap) = 30 days' );
assert_equals( 30, BPJalali_Calendar_Engine::jalali_month_length( 1403, 12 ), 'Esfand 1403 (leap) = 30 days' );

// ── Date Validation ────────────────────────────────────────────

echo "\n=== Date Validation ===\n";

assert_true( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 1, 1 ), 'Valid: 1400/1/1' );
assert_true( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 6, 31 ), 'Valid: 1400/6/31' );
assert_true( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 7, 30 ), 'Valid: 1400/7/30' );
assert_true( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 12, 29 ), 'Valid: 1400/12/29 (non-leap)' );
assert_true( BPJalali_Calendar_Engine::is_valid_jalali_date( 1399, 12, 30 ), 'Valid: 1399/12/30 (leap)' );

assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 12, 30 ), 'Invalid: 1400/12/30 (non-leap, no Esfand 30)' );
assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 0, 1 ), 'Invalid: month 0' );
assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 13, 1 ), 'Invalid: month 13' );
assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 1, 32 ), 'Invalid: day 32' );
assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 7, 31 ), 'Invalid: 1400/7/31 (month 7 has 30 days)' );
assert_false( BPJalali_Calendar_Engine::is_valid_jalali_date( 1400, 1, 0 ), 'Invalid: day 0' );

// ── Date Formatting ────────────────────────────────────────────

echo "\n=== Date Formatting ===\n";

assert_equals(
    '1405-07-17',
    BPJalali_Calendar_Engine::format_jalali_date( 1405, 7, 17, 'YYYY-MM-DD' ),
    'Format YYYY-MM-DD'
);

assert_equals(
    'Mehr 17, 1405',
    BPJalali_Calendar_Engine::format_jalali_date( 1405, 7, 17, 'MMMM D, YYYY' ),
    'Format MMMM D, YYYY'
);

assert_equals(
    'Meh 17',
    BPJalali_Calendar_Engine::format_jalali_date( 1405, 7, 17, 'MMM DD' ),
    'Format MMM DD'
);

assert_equals(
    'Farvardin 1, 1400',
    BPJalali_Calendar_Engine::format_jalali_date( 1400, 1, 1, 'MMMM D, YYYY' ),
    'Farvardin 1 formatting'
);

// ── String Conversion ──────────────────────────────────────────

echo "\n=== Gregorian String to Jalali ===\n";

assert_equals(
    '1405-07-17',
    BPJalali_Calendar_Engine::gregorian_string_to_jalali( '2026-10-09' ),
    'String conversion: 2026-10-09'
);

assert_equals(
    'Farvardin 1, 1400',
    BPJalali_Calendar_Engine::gregorian_string_to_jalali( '2021-03-21', 'MMMM D, YYYY' ),
    'String conversion with format'
);

// ── Month Names ────────────────────────────────────────────────

echo "\n=== Month Names ===\n";

assert_equals( 'Farvardin', BPJalali_Calendar_Engine::get_month_name( 1 ), 'Month 1' );
assert_equals( 'Esfand', BPJalali_Calendar_Engine::get_month_name( 12 ), 'Month 12' );
assert_equals( 'Meh', BPJalali_Calendar_Engine::get_month_name( 7, true ), 'Month 7 short' );
assert_equals( 'Esf', BPJalali_Calendar_Engine::get_month_name( 12, true ), 'Month 12 short' );

// ── Year Length ────────────────────────────────────────────────

echo "\n=== Year Length ===\n";

assert_equals( 366, BPJalali_Calendar_Engine::jalali_year_length( 1399 ), '1399 (leap) = 366 days' );
assert_equals( 365, BPJalali_Calendar_Engine::jalali_year_length( 1400 ), '1400 (non-leap) = 365 days' );

// ── Summary ────────────────────────────────────────────────────

echo "\n" . str_repeat( '─', 50 ) . "\n";
echo "Results: {$pass} passed, {$fail} failed\n";
echo str_repeat( '─', 50 ) . "\n";

exit( $fail > 0 ? 1 : 0 );
