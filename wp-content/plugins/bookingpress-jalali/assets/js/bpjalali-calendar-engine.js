/**
 * BPJalali Calendar Engine (JavaScript)
 *
 * Pure Jalali (Solar Hijri) calendar calculations.
 * No DOM, no framework, no BookingPress dependency.
 *
 * Algorithm: 33-year cycle (same as jalaali-js / jdf.scr.ir).
 *
 * @global {object} window.BPJalaliEngine
 */
(function (root) {
    'use strict';

    /**
     * Break points for the 2820-year grand cycle.
     * @type {number[]}
     */
    var breaks = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
        1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178
    ];

    /**
     * Jalali month names in English transliteration.
     * @type {string[]}
     */
    var monthNames = [
        'Farvardin', 'Ordibehesht', 'Khordad',
        'Tir', 'Mordad', 'Shahrivar',
        'Mehr', 'Aban', 'Azar',
        'Dey', 'Bahman', 'Esfand'
    ];

    /**
     * Short Jalali month names.
     * @type {string[]}
     */
    var monthNamesShort = [
        'Far', 'Ord', 'Kho',
        'Tir', 'Mor', 'Sha',
        'Meh', 'Aba', 'Aza',
        'Dey', 'Bah', 'Esf'
    ];

    /**
     * Weekday names (Saturday-first for Jalali convention).
     * @type {string[]}
     */
    var weekdayNames = [
        'Saturday', 'Sunday', 'Monday',
        'Tuesday', 'Wednesday', 'Thursday', 'Friday'
    ];

    var weekdayNamesShort = [
        'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'
    ];

    // ── Utility ────────────────────────────────────────────────

    /**
     * Integer division (floor towards zero for positive).
     */
    function div(a, b) {
        return ~~(a / b);
    }

    /**
     * Modulo that always returns non-negative for positive b.
     */
    function mod(a, b) {
        return ((a % b) + b) % b;
    }

    /**
     * Zero-pad a number to given width.
     */
    function pad(n, width) {
        var s = String(n);
        while (s.length < width) {
            s = '0' + s;
        }
        return s;
    }

    // ── Core Algorithm ─────────────────────────────────────────

    /**
     * Calculate Jalali calendar data for a given Jalali year.
     *
     * @param {number} jy - Jalali year (-61 to 3177).
     * @returns {{ leap: number, gy: number, march: number }}
     */
    function jalaliCal(jy) {
        var bl = breaks.length;
        var gy = jy + 621;
        var leapJ = -14;
        var jp = breaks[0];
        var jm2, jump, i, n;

        jump = 0;
        for (i = 1; i < bl; i++) {
            jm2 = breaks[i];
            jump = jm2 - jp;
            if (jy < jm2) {
                break;
            }
            leapJ += div(jump, 33) * 8 + div(mod(jump, 33), 4);
            jp = jm2;
        }
        n = jy - jp;

        leapJ += div(n, 33) * 8 + div(mod(n, 33) + 3, 4);
        if (mod(jump, 33) === 4 && (jump - n) === 4) {
            leapJ++;
        }

        var leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
        var march = 20 + leapJ - leapG;

        // Determine leap.
        if ((jump - n) < 6) {
            n = n - jump + div(jump + 4, 33) * 33;
        }
        var leap = mod(mod(n + 1, 33) - 1, 4);
        if (leap === -1) {
            leap = 4;
        }

        return { leap: leap, gy: gy, march: march };
    }

    // ── Gregorian ↔ JDN ────────────────────────────────────────

    /**
     * Gregorian to Julian Day Number.
     */
    function g2d(gy, gm, gd) {
        var d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4)
            + div(153 * mod(gm + 9, 12) + 2, 5)
            + gd - 34840408;
        d = d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
        return d;
    }

    /**
     * Julian Day Number to Gregorian.
     * @returns {number[]} [gy, gm, gd]
     */
    function d2g(jdn) {
        var j = 4 * jdn + 139361631;
        j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        var i = div(mod(j, 1461), 4) * 5 + 308;
        var gd = div(mod(i, 153), 5) + 1;
        var gm = mod(div(i, 153), 12) + 1;
        var gy = div(j, 1461) - 100100 + div(8 - gm, 6);
        return [gy, gm, gd];
    }

    // ── Jalali ↔ JDN ──────────────────────────────────────────

    /**
     * Jalali to Julian Day Number.
     */
    function j2d(jy, jm, jd) {
        var r = jalaliCal(jy);
        return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
    }

    /**
     * Julian Day Number to Jalali.
     * @returns {number[]} [jy, jm, jd]
     */
    function d2j(jdn) {
        var g = d2g(jdn);
        var gy = g[0];
        var jy = gy - 621;
        var r = jalaliCal(jy);
        var jdn1f = g2d(gy, 3, r.march);
        var k = jdn - jdn1f;

        if (k >= 0) {
            if (k <= 185) {
                var jm = 1 + div(k, 31);
                var jd = mod(k, 31) + 1;
                return [jy, jm, jd];
            } else {
                k -= 186;
            }
        } else {
            jy--;
            r = jalaliCal(jy);
            jdn1f = g2d(gy, 3, r.march);
            k = jdn - jdn1f;
            if (k < 0) {
                jy--;
                r = jalaliCal(jy);
                jdn1f = g2d(r.gy, 3, r.march);
                k = jdn - jdn1f;
            }
            if (k <= 185) {
                var jm2 = 1 + div(k, 31);
                var jd2 = mod(k, 31) + 1;
                return [jy, jm2, jd2];
            } else {
                k -= 186;
            }
        }

        var jm3 = 7 + div(k, 30);
        var jd3 = mod(k, 30) + 1;
        return [jy, jm3, jd3];
    }

    // ── Public API ─────────────────────────────────────────────

    /**
     * Convert Gregorian to Jalali.
     *
     * @param {number} gy - Gregorian year.
     * @param {number} gm - Gregorian month (1-12).
     * @param {number} gd - Gregorian day.
     * @returns {number[]} [jy, jm, jd]
     */
    function gregorianToJalali(gy, gm, gd) {
        return d2j(g2d(gy, gm, gd));
    }

    /**
     * Convert Jalali to Gregorian.
     *
     * @param {number} jy - Jalali year.
     * @param {number} jm - Jalali month (1-12).
     * @param {number} jd - Jalali day.
     * @returns {number[]} [gy, gm, gd]
     */
    function jalaliToGregorian(jy, jm, jd) {
        return d2g(j2d(jy, jm, jd));
    }

    /**
     * Check if a Jalali year is a leap year.
     *
     * @param {number} jy - Jalali year.
     * @returns {boolean}
     */
    function isJalaliLeapYear(jy) {
        return jalaliCal(jy).leap === 0;
    }

    /**
     * Get the number of days in a Jalali month.
     *
     * @param {number} jy - Jalali year.
     * @param {number} jm - Jalali month (1-12).
     * @returns {number}
     */
    function jalaliMonthLength(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        return isJalaliLeapYear(jy) ? 30 : 29;
    }

    /**
     * Validate a Jalali date.
     *
     * @param {number} jy
     * @param {number} jm
     * @param {number} jd
     * @returns {boolean}
     */
    function isValidJalaliDate(jy, jm, jd) {
        if (jy < -61 || jy > 3177) return false;
        if (jm < 1 || jm > 12) return false;
        if (jd < 1 || jd > jalaliMonthLength(jy, jm)) return false;
        return true;
    }

    /**
     * Get the Jalali month name.
     *
     * @param {number}  month - Month number (1-12).
     * @param {boolean} [short=false] - Whether to return short name.
     * @returns {string}
     */
    function getMonthName(month, short) {
        var names = short ? monthNamesShort : monthNames;
        return names[month - 1] || '';
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
     * @param {number} jy
     * @param {number} jm
     * @param {number} jd
     * @param {string} [format='YYYY-MM-DD']
     * @returns {string}
     */
    function formatJalaliDate(jy, jm, jd, format) {
        if (!format) format = 'YYYY-MM-DD';

        var result = format;
        result = result.replace(/YYYY/g, pad(jy, 4));
        result = result.replace(/YY/g, pad(jy, 4).slice(-2));
        result = result.replace(/MMMM/g, getMonthName(jm));
        result = result.replace(/MMM/g, getMonthName(jm, true));
        result = result.replace(/MM/g, pad(jm, 2));
        result = result.replace(/DD/g, pad(jd, 2));

        // Single M and D — avoid matching inside already-replaced text.
        // Use negative lookbehind/ahead simulation.
        result = result.replace(/(?<![A-Za-z])M(?![A-Za-z])/g, String(jm));
        result = result.replace(/(?<![A-Za-z])D(?![A-Za-z])/g, String(jd));

        return result;
    }

    /**
     * Convert a Gregorian date string (YYYY-MM-DD) to a Jalali formatted string.
     *
     * @param {string} dateStr - Gregorian date in 'YYYY-MM-DD' or 'YYYY/MM/DD' format.
     * @param {string} [format='YYYY-MM-DD']
     * @returns {string}
     */
    function gregorianStringToJalali(dateStr, format) {
        if (!dateStr) return dateStr;

        var normalized = String(dateStr).replace(/\//g, '-').substring(0, 10);
        var parts = normalized.split('-');
        if (parts.length !== 3) return dateStr;

        var gy = parseInt(parts[0], 10);
        var gm = parseInt(parts[1], 10);
        var gd = parseInt(parts[2], 10);

        if (isNaN(gy) || isNaN(gm) || isNaN(gd)) return dateStr;

        var j = gregorianToJalali(gy, gm, gd);
        return formatJalaliDate(j[0], j[1], j[2], format);
    }

    /**
     * Get the day of week for a Jalali date (0=Saturday, 6=Friday).
     *
     * @param {number} jy
     * @param {number} jm
     * @param {number} jd
     * @returns {number}
     */
    function jalaliDayOfWeek(jy, jm, jd) {
        var g = jalaliToGregorian(jy, jm, jd);
        var d = new Date(g[0], g[1] - 1, g[2]);
        // JS: 0=Sun, 6=Sat. We want: 0=Sat, 6=Fri.
        return (d.getDay() + 1) % 7;
    }

    /**
     * Build a calendar grid for a Jalali month.
     * Returns an array of week-rows, each containing 7 day objects.
     *
     * @param {number} jy - Jalali year.
     * @param {number} jm - Jalali month (1-12).
     * @param {number} [firstDayOfWeek=6] - 0=Sat, 6=Fri. Default Saturday.
     * @returns {Array<Array<{jy:number, jm:number, jd:number, gy:number, gm:number, gd:number, isCurrentMonth:boolean, dayOfWeek:number}|null>>}
     */
    function buildMonthGrid(jy, jm, firstDayOfWeek) {
        if (typeof firstDayOfWeek === 'undefined') firstDayOfWeek = 6;

        var daysInMonth = jalaliMonthLength(jy, jm);
        var startDow = jalaliDayOfWeek(jy, jm, 1);
        var offset = mod(startDow - firstDayOfWeek, 7);

        var grid = [];
        var week = [];

        // Leading empty cells.
        for (var e = 0; e < offset; e++) {
            week.push(null);
        }

        for (var day = 1; day <= daysInMonth; day++) {
            var g = jalaliToGregorian(jy, jm, day);
            week.push({
                jy: jy,
                jm: jm,
                jd: day,
                gy: g[0],
                gm: g[1],
                gd: g[2],
                isCurrentMonth: true,
                dayOfWeek: jalaliDayOfWeek(jy, jm, day)
            });

            if (week.length === 7) {
                grid.push(week);
                week = [];
            }
        }

        // Trailing empty cells.
        if (week.length > 0) {
            while (week.length < 7) {
                week.push(null);
            }
            grid.push(week);
        }

        return grid;
    }

    /**
     * Get the Jalali date for today.
     *
     * @returns {number[]} [jy, jm, jd]
     */
    function todayJalali() {
        var now = new Date();
        return gregorianToJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    }

    // ── Export ──────────────────────────────────────────────────

    var engine = {
        gregorianToJalali: gregorianToJalali,
        jalaliToGregorian: jalaliToGregorian,
        isJalaliLeapYear: isJalaliLeapYear,
        jalaliMonthLength: jalaliMonthLength,
        isValidJalaliDate: isValidJalaliDate,
        getMonthName: getMonthName,
        formatJalaliDate: formatJalaliDate,
        gregorianStringToJalali: gregorianStringToJalali,
        jalaliDayOfWeek: jalaliDayOfWeek,
        buildMonthGrid: buildMonthGrid,
        todayJalali: todayJalali,
        monthNames: monthNames,
        monthNamesShort: monthNamesShort,
        weekdayNames: weekdayNames,
        weekdayNamesShort: weekdayNamesShort
    };

    // Expose globally.
    root.BPJalaliEngine = engine;

})(typeof window !== 'undefined' ? window : this);
