<?php
/**
 * Jalali (Solar Hijri) Calendar Engine.
 *
 * Pure calendar calculations — no WordPress, BookingPress, or DOM dependencies.
 * Implements accurate Jalali ↔ Gregorian conversion using the 33-year cycle
 * algorithm (same as jdf.scr.ir / jalaali-js).
 *
 * @package BookingPress_Jalali
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BPJalali_Calendar_Engine
 *
 * Provides static methods for Jalali calendar calculations.
 */
class BPJalali_Calendar_Engine {

    /**
     * Jalali month names in English transliteration.
     *
     * @var string[]
     */
    const MONTH_NAMES = array(
        1  => 'Farvardin',
        2  => 'Ordibehesht',
        3  => 'Khordad',
        4  => 'Tir',
        5  => 'Mordad',
        6  => 'Shahrivar',
        7  => 'Mehr',
        8  => 'Aban',
        9  => 'Azar',
        10 => 'Dey',
        11 => 'Bahman',
        12 => 'Esfand',
    );

    /**
     * Short month names.
     *
     * @var string[]
     */
    const MONTH_NAMES_SHORT = array(
        1  => 'Far',
        2  => 'Ord',
        3  => 'Kho',
        4  => 'Tir',
        5  => 'Mor',
        6  => 'Sha',
        7  => 'Meh',
        8  => 'Aba',
        9  => 'Aza',
        10 => 'Dey',
        11 => 'Bah',
        12 => 'Esf',
    );

    /**
     * Weekday names in English (Saturday-first for Jalali convention).
     *
     * @var string[]
     */
    const WEEKDAY_NAMES = array(
        'Saturday',
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
    );

    /**
     * Short weekday names.
     *
     * @var string[]
     */
    const WEEKDAY_NAMES_SHORT = array(
        'Sat',
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
    );

    /**
     * The 33-year cycle break points for leap year calculation.
     * These are the years within each 2820-year grand cycle at which
     * leap years occur in the Jalali calendar.
     *
     * @var int[]
     */
    private static $breaks = array(
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
        1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    );

    /**
     * Convert Jalali date to Gregorian.
     *
     * @param int $jy Jalali year.
     * @param int $jm Jalali month (1-12).
     * @param int $jd Jalali day.
     * @return array{0: int, 1: int, 2: int} [gy, gm, gd]
     */
    public static function jalali_to_gregorian( int $jy, int $jm, int $jd ): array {
        $jdn = self::jalali_to_jdn( $jy, $jm, $jd );
        return self::jdn_to_gregorian( $jdn );
    }

    /**
     * Convert Gregorian date to Jalali.
     *
     * @param int $gy Gregorian year.
     * @param int $gm Gregorian month (1-12).
     * @param int $gd Gregorian day.
     * @return array{0: int, 1: int, 2: int} [jy, jm, jd]
     */
    public static function gregorian_to_jalali( int $gy, int $gm, int $gd ): array {
        $jdn = self::gregorian_to_jdn( $gy, $gm, $gd );
        return self::jdn_to_jalali( $jdn );
    }

    /**
     * Check if a Jalali year is a leap year.
     *
     * Uses the 2820-year cycle with specific break points.
     *
     * @param int $jy Jalali year.
     * @return bool
     */
    public static function is_jalali_leap_year( int $jy ): bool {
        return self::jalali_cal( $jy )['leap'] === 0;
    }

    /**
     * Get the number of days in a Jalali month.
     *
     * @param int $jy Jalali year.
     * @param int $jm Jalali month (1-12).
     * @return int Number of days (29, 30, or 31).
     */
    public static function jalali_month_length( int $jy, int $jm ): int {
        if ( $jm <= 6 ) {
            return 31;
        }
        if ( $jm <= 11 ) {
            return 30;
        }
        // Month 12 (Esfand): 30 in leap years, 29 otherwise.
        return self::is_jalali_leap_year( $jy ) ? 30 : 29;
    }

    /**
     * Validate a Jalali date.
     *
     * @param int $jy Jalali year.
     * @param int $jm Jalali month (1-12).
     * @param int $jd Jalali day.
     * @return bool
     */
    public static function is_valid_jalali_date( int $jy, int $jm, int $jd ): bool {
        if ( $jy < -61 || $jy > 3177 ) {
            return false;
        }
        if ( $jm < 1 || $jm > 12 ) {
            return false;
        }
        if ( $jd < 1 || $jd > self::jalali_month_length( $jy, $jm ) ) {
            return false;
        }
        return true;
    }

    /**
     * Get the Jalali month name.
     *
     * @param int  $month Month number (1-12).
     * @param bool $short Whether to return short name.
     * @return string
     */
    public static function get_month_name( int $month, bool $short = false ): string {
        $names = $short ? self::MONTH_NAMES_SHORT : self::MONTH_NAMES;
        return $names[ $month ] ?? '';
    }

    /**
     * Format a Jalali date.
     *
     * Supported tokens:
     *   YYYY - 4-digit year
     *   YY   - 2-digit year
     *   MMMM - Full month name
     *   MMM  - Short month name
     *   MM   - Zero-padded month
     *   M    - Month number
     *   DD   - Zero-padded day
     *   D    - Day number
     *
     * @param int    $jy     Jalali year.
     * @param int    $jm     Jalali month.
     * @param int    $jd     Jalali day.
     * @param string $format Format string.
     * @return string
     */
    public static function format_jalali_date( int $jy, int $jm, int $jd, string $format = 'YYYY-MM-DD' ): string {
        $replacements = array(
            'YYYY' => str_pad( (string) $jy, 4, '0', STR_PAD_LEFT ),
            'YY'   => substr( str_pad( (string) $jy, 4, '0', STR_PAD_LEFT ), -2 ),
            'MMMM' => self::get_month_name( $jm ),
            'MMM'  => self::get_month_name( $jm, true ),
            'MM'   => str_pad( (string) $jm, 2, '0', STR_PAD_LEFT ),
            'DD'   => str_pad( (string) $jd, 2, '0', STR_PAD_LEFT ),
        );

        // Replace longer tokens first to avoid partial matches.
        $result = $format;
        foreach ( $replacements as $token => $value ) {
            $result = str_replace( $token, $value, $result );
        }

        // Replace single-character tokens after multi-char ones.
        $result = preg_replace( '/(?<!M)M(?!M)/', (string) $jm, $result );
        $result = preg_replace( '/(?<!D)D(?!D)/', (string) $jd, $result );

        return $result;
    }

    /**
     * Convert a Gregorian date string (Y-m-d) to a Jalali formatted string.
     *
     * @param string $gregorian_date Date in Y-m-d format.
     * @param string $format         Output format.
     * @return string
     */
    public static function gregorian_string_to_jalali( string $gregorian_date, string $format = 'YYYY-MM-DD' ): string {
        $parts = explode( '-', $gregorian_date );
        if ( count( $parts ) !== 3 ) {
            return $gregorian_date;
        }

        list( $gy, $gm, $gd ) = array_map( 'intval', $parts );
        list( $jy, $jm, $jd ) = self::gregorian_to_jalali( $gy, $gm, $gd );

        return self::format_jalali_date( $jy, $jm, $jd, $format );
    }

    /**
     * Get the number of days in a Jalali year.
     *
     * @param int $jy Jalali year.
     * @return int 365 or 366.
     */
    public static function jalali_year_length( int $jy ): int {
        return self::is_jalali_leap_year( $jy ) ? 366 : 365;
    }

    /**
     * Calculate Jalali calendar data for a given year.
     *
     * Returns leap status and the Julian Day Number of Farvardin 1.
     *
     * @param int $jy Jalali year (-61 to 3177).
     * @return array{leap: int, gy: int, march: int}
     */
    private static function jalali_cal( int $jy ): array {
        $breaks = self::$breaks;
        $bl     = count( $breaks );
        $gy     = $jy + 621;
        $leapJ  = -14;
        $jp     = $breaks[0];

        $jump = 0;
        for ( $i = 1; $i < $bl; $i++ ) {
            $jm2 = $breaks[ $i ];
            $jump = $jm2 - $jp;
            if ( $jy < $jm2 ) {
                break;
            }
            $leapJ += self::div( $jump, 33 ) * 8 + self::div( self::mod( $jump, 33 ), 4 );
            $jp = $jm2;
        }
        $n = $jy - $jp;

        $leapJ += self::div( $n, 33 ) * 8 + self::div( self::mod( $n, 33 ) + 3, 4 );
        if ( self::mod( $jump, 33 ) === 4 && ( $jump - $n ) === 4 ) {
            $leapJ++;
        }

        $leapG = self::div( $gy, 4 ) - self::div( ( self::div( $gy, 100 ) + 1 ) * 3, 4 ) - 150;

        $march = 20 + $leapJ - $leapG;

        // Determine if this year is a leap year.
        if ( ( $jump - $n ) < 6 ) {
            $n = $n - $jump + self::div( $jump + 4, 33 ) * 33;
        }
        $leap = self::mod( self::mod( $n + 1, 33 ) - 1, 4 );
        if ( $leap === -1 ) {
            $leap = 4;
        }

        return array(
            'leap'  => $leap,
            'gy'    => $gy,
            'march' => $march,
        );
    }

    /**
     * Convert Jalali date to Julian Day Number.
     *
     * @param int $jy Jalali year.
     * @param int $jm Jalali month.
     * @param int $jd Jalali day.
     * @return int Julian Day Number.
     */
    private static function jalali_to_jdn( int $jy, int $jm, int $jd ): int {
        $r = self::jalali_cal( $jy );
        return self::gregorian_to_jdn( $r['gy'], 3, $r['march'] ) + ( $jm - 1 ) * 31 - self::div( $jm, 7 ) * ( $jm - 7 ) + $jd - 1;
    }

    /**
     * Convert Julian Day Number to Jalali date.
     *
     * @param int $jdn Julian Day Number.
     * @return array{0: int, 1: int, 2: int} [jy, jm, jd]
     */
    private static function jdn_to_jalali( int $jdn ): array {
        list( $gy, $gm, $gd ) = self::jdn_to_gregorian( $jdn );

        $jy = $gy - 621;
        $r  = self::jalali_cal( $jy );
        $jdn1f = self::gregorian_to_jdn( $gy, 3, $r['march'] );
        $k     = $jdn - $jdn1f;

        // Determine Jalali year.
        if ( $k >= 0 ) {
            if ( $k <= 185 ) {
                $jm = 1 + self::div( $k, 31 );
                $jd = self::mod( $k, 31 ) + 1;
                return array( $jy, $jm, $jd );
            } else {
                $k -= 186;
            }
        } else {
            $jy--;
            $r     = self::jalali_cal( $jy );
            $jdn1f = self::gregorian_to_jdn( $gy, 3, $r['march'] );
            $k     = $jdn - $jdn1f;
            // Handle negative offset (should not occur, but safeguard).
            if ( $k < 0 ) {
                $jy--;
                $r     = self::jalali_cal( $jy );
                $jdn1f = self::gregorian_to_jdn( $r['gy'], 3, $r['march'] );
                $k     = $jdn - $jdn1f;
            }
            if ( $k <= 185 ) {
                $jm = 1 + self::div( $k, 31 );
                $jd = self::mod( $k, 31 ) + 1;
                return array( $jy, $jm, $jd );
            } else {
                $k -= 186;
            }
        }

        $jm = 7 + self::div( $k, 30 );
        $jd = self::mod( $k, 30 ) + 1;

        return array( $jy, $jm, $jd );
    }

    /**
     * Convert Gregorian date to Julian Day Number.
     *
     * @param int $gy Gregorian year.
     * @param int $gm Gregorian month.
     * @param int $gd Gregorian day.
     * @return int
     */
    private static function gregorian_to_jdn( int $gy, int $gm, int $gd ): int {
        $d = self::div( ( $gy + self::div( $gm - 8, 6 ) + 100100 ) * 1461, 4 )
            + self::div( 153 * self::mod( $gm + 9, 12 ) + 2, 5 )
            + $gd - 34840408;
        $d = $d - self::div( self::div( $gy + 100100 + self::div( $gm - 8, 6 ), 100 ) * 3, 4 ) + 752;
        return $d;
    }

    /**
     * Convert Julian Day Number to Gregorian date.
     *
     * @param int $jdn Julian Day Number.
     * @return array{0: int, 1: int, 2: int} [gy, gm, gd]
     */
    private static function jdn_to_gregorian( int $jdn ): array {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::div( self::div( 4 * $jdn + 183187720, 146097 ) * 3, 4 ) * 4 - 3908;
        $i = self::div( self::mod( $j, 1461 ), 4 ) * 5 + 308;
        $gd = self::div( self::mod( $i, 153 ), 5 ) + 1;
        $gm = self::mod( self::div( $i, 153 ), 12 ) + 1;
        $gy = self::div( $j, 1461 ) - 100100 + self::div( 8 - $gm, 6 );
        return array( $gy, $gm, $gd );
    }

    /**
     * Integer division (floor).
     *
     * @param int $a Dividend.
     * @param int $b Divisor.
     * @return int
     */
    private static function div( int $a, int $b ): int {
        return intdiv( $a, $b );
    }

    /**
     * Modulo that always returns non-negative for positive divisor.
     *
     * @param int $a Dividend.
     * @param int $b Divisor.
     * @return int
     */
    private static function mod( int $a, int $b ): int {
        return ( ( $a % $b ) + $b ) % $b;
    }
}
