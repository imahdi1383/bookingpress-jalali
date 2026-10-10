/**
 * BPJalali Adapter
 *
 * Intercepts BookingPress calendar rendering and overlays Jalali dates
 * on the existing v-calendar / v-date-picker UI.
 *
 * Pattern:
 *   User sees Jalali → adapter converts to Gregorian → BookingPress processes
 *   BookingPress responds Gregorian → adapter converts to Jalali → User sees
 *
 * Each booking form instance maintains its own calendar mode state.
 *
 * @global {object} window.BPJalali
 * @requires window.BPJalaliEngine
 * @requires window.bpjalaliConfig (from wp_localize_script)
 */
(function (root) {
    'use strict';

    var Engine = root.BPJalaliEngine;
    var config = root.bpjalaliConfig || {};

    if (!Engine) {
        if (typeof console !== 'undefined') {
            console.warn('[BPJalali] Calendar engine not loaded.');
        }
        return;
    }

    // ── State Management ───────────────────────────────────────

    /**
     * Per-instance state registry.
     * Keys are DOM element IDs or generated keys.
     * @type {Object<string, {mode: string, observer: MutationObserver|null}>}
     */
    var instances = {};
    var instanceCounter = 0;

    /**
     * Get or create instance state for a form element.
     *
     * @param {HTMLElement} el - The booking form root element.
     * @param {string} [defaultMode] - Initial calendar mode.
     * @returns {{ mode: string, observer: MutationObserver|null, key: string }}
     */
    function getInstance(el, defaultMode) {
        var key = el.getAttribute('data-bpjalali-key');
        if (!key) {
            key = 'bpjalali_' + (++instanceCounter);
            el.setAttribute('data-bpjalali-key', key);
        }
        if (!instances[key]) {
            instances[key] = {
                mode: defaultMode || config.defaultMode || 'jalali',
                observer: null,
                key: key
            };
        }
        return instances[key];
    }

    // ── Calendar Header Transformation ─────────────────────────

    /**
     * Transform the v-calendar header to show Jalali month/year.
     *
     * The v-calendar header typically contains elements with classes like
     * .vc-title showing "Month Year" in Gregorian. We overlay Jalali text.
     *
     * @param {HTMLElement} calendarEl - A calendar container element.
     * @param {string} mode - 'jalali' or 'gregorian'.
     */
    function transformCalendarHeader(calendarEl, mode) {
        if (mode !== 'jalali') {
            // Restore original headers if switching back.
            restoreOriginalHeaders(calendarEl);
            return;
        }

        var titleEls = calendarEl.querySelectorAll('.vc-title, .vc-nav-title');
        for (var i = 0; i < titleEls.length; i++) {
            var titleEl = titleEls[i];
            var originalText = titleEl.getAttribute('data-bpjalali-original');
            if (!originalText) {
                originalText = titleEl.textContent.trim();
                titleEl.setAttribute('data-bpjalali-original', originalText);
            }

            // Parse the Gregorian date from the title.
            var jalaliTitle = convertHeaderToJalali(originalText);
            if (jalaliTitle) {
                titleEl.textContent = jalaliTitle;
            }
        }
    }

    /**
     * Convert a header text like "October 2026" to "Mehr 1405".
     *
     * @param {string} text - Gregorian month/year text.
     * @returns {string|null}
     */
    function convertHeaderToJalali(text) {
        if (!text) return null;

        // Try to parse "Month Year" format.
        var gregorianMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        var parts = text.trim().split(/\s+/);
        if (parts.length < 2) return null;

        var monthName = parts[0];
        var year = parseInt(parts[parts.length - 1], 10);
        if (isNaN(year)) return null;

        var gm = -1;
        for (var i = 0; i < gregorianMonths.length; i++) {
            if (gregorianMonths[i].toLowerCase() === monthName.toLowerCase() ||
                gregorianMonths[i].substring(0, 3).toLowerCase() === monthName.toLowerCase()) {
                gm = i + 1;
                break;
            }
        }

        if (gm < 1) return null;

        // Convert the 15th of that Gregorian month to get approximate Jalali month.
        var j = Engine.gregorianToJalali(year, gm, 15);
        return Engine.getMonthName(j[1]) + ' ' + j[0];
    }

    /**
     * Restore original Gregorian headers.
     *
     * @param {HTMLElement} calendarEl
     */
    function restoreOriginalHeaders(calendarEl) {
        var titleEls = calendarEl.querySelectorAll('[data-bpjalali-original]');
        for (var i = 0; i < titleEls.length; i++) {
            titleEls[i].textContent = titleEls[i].getAttribute('data-bpjalali-original');
        }
    }

    // ── Day Cell Transformation ────────────────────────────────

    /**
     * Transform day cells in the calendar to show Jalali day numbers.
     *
     * @param {HTMLElement} calendarEl - Calendar container.
     * @param {string} mode - 'jalali' or 'gregorian'.
     */
    function transformDayCells(calendarEl, mode) {
        if (mode !== 'jalali') {
            restoreOriginalDays(calendarEl);
            return;
        }

        var dayCells = calendarEl.querySelectorAll('.vc-day-content, .vc-day .vc-day-content');
        for (var i = 0; i < dayCells.length; i++) {
            var cell = dayCells[i];
            var dayContainer = cell.closest('.vc-day');
            if (!dayContainer) continue;

            // v-calendar stores the date in an attribute or data.
            var dateId = dayContainer.getAttribute('data-date') || dayContainer.id;
            if (!dateId) continue;

            // Attempt to parse the date from the id (format: YYYY-MM-DD).
            var dateParts = dateId.match(/(\d{4})-(\d{2})-(\d{2})/);
            if (!dateParts) continue;

            var gy = parseInt(dateParts[1], 10);
            var gm = parseInt(dateParts[2], 10);
            var gd = parseInt(dateParts[3], 10);

            // Save original.
            if (!cell.getAttribute('data-bpjalali-original-day')) {
                cell.setAttribute('data-bpjalali-original-day', cell.textContent.trim());
            }

            var j = Engine.gregorianToJalali(gy, gm, gd);
            cell.textContent = j[2]; // Jalali day number.
        }
    }

    /**
     * Restore original Gregorian day numbers.
     *
     * @param {HTMLElement} calendarEl
     */
    function restoreOriginalDays(calendarEl) {
        var cells = calendarEl.querySelectorAll('[data-bpjalali-original-day]');
        for (var i = 0; i < cells.length; i++) {
            cells[i].textContent = cells[i].getAttribute('data-bpjalali-original-day');
        }
    }

    // ── Date Display Transformation ────────────────────────────

    /**
     * Transform displayed dates in the booking form summary and elsewhere.
     *
     * Hooks into the moment.js format filter used by BookingPress.
     *
     * @param {HTMLElement} formEl - Booking form root element.
     * @param {string} mode - Calendar mode.
     */
    function transformDisplayedDates(formEl, mode) {
        if (mode !== 'jalali') return;

        // Find elements that display formatted dates (summary step, etc.).
        var dateDisplays = formEl.querySelectorAll(
            '.bpa-front-bs-sm__item-val, .bpa-front-dc__day-label, [data-bpjalali-date]'
        );

        for (var i = 0; i < dateDisplays.length; i++) {
            var el = dateDisplays[i];
            // Only transform if it contains a recognizable date pattern.
            // We mark already-transformed elements to avoid double-conversion.
            if (el.getAttribute('data-bpjalali-transformed') === 'true') continue;
        }
    }

    // ── Toggle Button ──────────────────────────────────────────

    /**
     * Inject a Jalali ↔ Gregorian toggle button into the booking form.
     *
     * @param {HTMLElement} formEl - Booking form root element.
     * @param {{ mode: string }} state - Instance state.
     */
    function injectToggleButton(formEl, state) {
        // Check if toggle already exists.
        if (formEl.querySelector('.bpjalali-toggle-btn')) return;

        // Find the calendar section to place the toggle near it.
        var calendarSection = formEl.querySelector(
            '.vc-container, .bpa-front-tabs-date-time-sec, .bpa-front-module--booking-form'
        );

        if (!calendarSection) {
            // Fallback: insert at top of form.
            calendarSection = formEl;
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'bpjalali-toggle-btn';
        btn.setAttribute('aria-label', config.i18n ? config.i18n.toggleCalendar : 'Switch Calendar');
        btn.setAttribute('title', config.i18n ? config.i18n.toggleCalendar : 'Switch Calendar');

        updateToggleButtonText(btn, state.mode);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Toggle mode.
            state.mode = (state.mode === 'jalali') ? 'gregorian' : 'jalali';
            updateToggleButtonText(btn, state.mode);

            // Re-apply transformations.
            applyTransformations(formEl, state.mode);

            // Dispatch custom event for any external listeners.
            var event;
            try {
                event = new CustomEvent('bpjalali:modechange', {
                    detail: { mode: state.mode },
                    bubbles: true
                });
            } catch (ex) {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('bpjalali:modechange', true, true, { mode: state.mode });
            }
            formEl.dispatchEvent(event);
        });

        // Insert before the calendar.
        var targetParent = calendarSection.parentNode;
        if (targetParent) {
            targetParent.insertBefore(btn, calendarSection);
        } else {
            formEl.insertBefore(btn, formEl.firstChild);
        }
    }

    /**
     * Update toggle button text and icon.
     *
     * @param {HTMLElement} btn
     * @param {string} mode
     */
    function updateToggleButtonText(btn, mode) {
        var i18n = config.i18n || {};
        var jalaliLabel = i18n.jalali || 'Jalali';
        var gregorianLabel = i18n.gregorian || 'Gregorian';

        if (mode === 'jalali') {
            btn.innerHTML = '<span class="bpjalali-toggle-icon">&#x1F4C5;</span> '
                + '<span class="bpjalali-toggle-active">' + jalaliLabel + '</span>'
                + ' <span class="bpjalali-toggle-sep">↔</span> '
                + '<span class="bpjalali-toggle-inactive">' + gregorianLabel + '</span>';
        } else {
            btn.innerHTML = '<span class="bpjalali-toggle-icon">&#x1F4C5;</span> '
                + '<span class="bpjalali-toggle-inactive">' + jalaliLabel + '</span>'
                + ' <span class="bpjalali-toggle-sep">↔</span> '
                + '<span class="bpjalali-toggle-active">' + gregorianLabel + '</span>';
        }
    }

    // ── Moment.js Filter Override ──────────────────────────────

    /**
     * Override the BookingPress bookingpress_format_date Vue filter.
     *
     * We hook into wp.hooks if available to intercept date formatting.
     */
    function hookIntoDateFormatting() {
        if (typeof wp === 'undefined' || !wp.hooks) return;

        // Add a filter on the format_date output if the hook exists.
        wp.hooks.addFilter(
            'bookingpress_formatted_date',
            'bpjalali',
            function (formatted, rawDate) {
                // Check if any instance is in Jalali mode.
                var isJalali = false;
                for (var key in instances) {
                    if (instances[key].mode === 'jalali') {
                        isJalali = true;
                        break;
                    }
                }
                if (!isJalali) return formatted;

                return Engine.gregorianStringToJalali(rawDate, 'MMMM D, YYYY');
            }
        );
    }

    // ── MutationObserver ───────────────────────────────────────

    /**
     * Set up a MutationObserver to re-apply Jalali transformations
     * whenever the calendar DOM updates (month navigation, etc.).
     *
     * @param {HTMLElement} formEl
     * @param {{ mode: string, observer: MutationObserver|null }} state
     */
    function setupObserver(formEl, state) {
        if (state.observer) {
            state.observer.disconnect();
        }

        if (typeof MutationObserver === 'undefined') return;

        var debounceTimer = null;

        state.observer = new MutationObserver(function () {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                applyTransformations(formEl, state.mode);
            }, 50);
        });

        state.observer.observe(formEl, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    // ── Apply All Transformations ──────────────────────────────

    /**
     * Apply all Jalali transformations to a form element.
     *
     * @param {HTMLElement} formEl
     * @param {string} mode
     */
    function applyTransformations(formEl, mode) {
        // Find all calendar containers within this form.
        var calendars = formEl.querySelectorAll('.vc-container');
        for (var i = 0; i < calendars.length; i++) {
            transformCalendarHeader(calendars[i], mode);
            transformDayCells(calendars[i], mode);
        }
        transformDisplayedDates(formEl, mode);
    }

    // ── Public API ─────────────────────────────────────────────

    var BPJalali = {
        /**
         * Initialize Jalali adapter for a booking form instance.
         *
         * @param {HTMLElement} formEl - The booking form root element.
         * @param {string} [defaultMode] - Initial calendar mode.
         */
        init: function (formEl, defaultMode) {
            if (!formEl) return;

            var state = getInstance(formEl, defaultMode);

            // Inject toggle button.
            injectToggleButton(formEl, state);

            // Apply initial transformations.
            applyTransformations(formEl, state.mode);

            // Set up observer for dynamic updates.
            setupObserver(formEl, state);
        },

        /**
         * Handle mode change from Vue methods.
         *
         * @param {string} mode
         * @param {HTMLElement} formEl
         */
        onModeChange: function (mode, formEl) {
            if (!formEl) return;

            var state = getInstance(formEl);
            state.mode = mode;

            var btn = formEl.querySelector('.bpjalali-toggle-btn');
            if (btn) {
                updateToggleButtonText(btn, mode);
            }

            applyTransformations(formEl, mode);
        },

        /**
         * Get the current mode for a form instance.
         *
         * @param {HTMLElement} formEl
         * @returns {string} 'jalali' or 'gregorian'
         */
        getMode: function (formEl) {
            var state = getInstance(formEl);
            return state.mode;
        },

        /**
         * Format a Gregorian date according to current instance mode.
         *
         * @param {string} dateStr - Gregorian date string (YYYY-MM-DD).
         * @param {HTMLElement} [formEl] - Form element for instance-specific mode.
         * @param {string} [format] - Jalali format string.
         * @returns {string}
         */
        formatDate: function (dateStr, formEl, format) {
            var mode = formEl ? getInstance(formEl).mode : (config.defaultMode || 'jalali');
            if (mode !== 'jalali') return dateStr;
            return Engine.gregorianStringToJalali(dateStr, format || 'MMMM D, YYYY');
        },

        /**
         * Access the calendar engine directly.
         */
        engine: Engine
    };

    // ── Auto-Initialize ────────────────────────────────────────

    /**
     * Auto-detect and initialize BookingPress booking forms.
     *
     * Runs after DOM ready and also observes for dynamically-added forms.
     */
    function autoInit() {
        hookIntoDateFormatting();

        // Find all existing booking forms.
        var forms = document.querySelectorAll(
            '[id^="bookingpress_booking_form_"], .bpa-frontend-main-booking-calendar'
        );

        for (var i = 0; i < forms.length; i++) {
            BPJalali.init(forms[i], config.defaultMode);
        }

        // Observe for dynamically-added forms.
        if (typeof MutationObserver !== 'undefined') {
            var bodyObserver = new MutationObserver(function (mutations) {
                for (var m = 0; m < mutations.length; m++) {
                    var addedNodes = mutations[m].addedNodes;
                    for (var n = 0; n < addedNodes.length; n++) {
                        var node = addedNodes[n];
                        if (node.nodeType !== 1) continue;

                        if (node.id && node.id.indexOf('bookingpress_booking_form_') === 0) {
                            BPJalali.init(node, config.defaultMode);
                        }

                        // Check children.
                        var childForms = node.querySelectorAll
                            ? node.querySelectorAll('[id^="bookingpress_booking_form_"]')
                            : [];
                        for (var c = 0; c < childForms.length; c++) {
                            BPJalali.init(childForms[c], config.defaultMode);
                        }
                    }
                }
            });

            bodyObserver.observe(document.body || document.documentElement, {
                childList: true,
                subtree: true
            });
        }
    }

    // Run when DOM is ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        // DOM already loaded; defer slightly to let BookingPress initialize first.
        setTimeout(autoInit, 100);
    }

    // Expose globally.
    root.BPJalali = BPJalali;

})(typeof window !== 'undefined' ? window : this);
