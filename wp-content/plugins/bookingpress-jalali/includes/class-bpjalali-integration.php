<?php
/**
 * BookingPress Integration Layer for Jalali.
 *
 * Hooks into BookingPress PHP filters to convert date displays
 * and pass Jalali-related data to the frontend.
 *
 * @package BookingPress_Jalali
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BPJalali_Integration
 */
class BPJalali_Integration {

    /**
     * Singleton instance.
     *
     * @var BPJalali_Integration|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return BPJalali_Integration
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Hook into the frontend booking form Vue data injection.
        add_filter( 'bookingpress_front_booking_dynamic_data_fields', array( $this, 'inject_jalali_data_fields' ), 10, 5 );

        // Hook into the frontend helper vars for inline script injection.
        add_filter( 'bookingpress_front_booking_dynamic_helper_vars', array( $this, 'inject_jalali_helper_vars' ) );

        // Hook into the dynamic Vue methods.
        add_filter( 'bookingpress_front_booking_dynamic_vue_methods', array( $this, 'inject_jalali_vue_methods' ) );

        // Hook into the onLoad methods.
        add_filter( 'bookingpress_front_booking_dynamic_on_load_methods', array( $this, 'inject_jalali_on_load' ) );
    }

    /**
     * Inject Jalali-related data fields into the Vue instance.
     *
     * @param string $data_fields     Existing data fields JS string.
     * @param mixed  $form_category   Category param.
     * @param mixed  $form_service    Service param.
     * @param mixed  $selected_service Selected service.
     * @param mixed  $selected_category Selected category.
     * @return string
     */
    public function inject_jalali_data_fields( string $data_fields, $form_category = '', $form_service = '', $selected_service = '', $selected_category = '' ): string {
        $default_mode = BPJalali_Admin::get_default_calendar_mode();

        // Append Jalali-specific data fields to the existing ones.
        $jalali_fields = "
            if ( typeof bookingpress_return_data !== 'undefined' ) {
                bookingpress_return_data['bpjalali_calendar_mode'] = '" . esc_js( $default_mode ) . "';
                bookingpress_return_data['bpjalali_initialized'] = false;
            }
        ";

        return $data_fields . $jalali_fields;
    }

    /**
     * Inject helper variables for Jalali.
     *
     * @param string $helper_vars Existing helper vars JS string.
     * @return string
     */
    public function inject_jalali_helper_vars( string $helper_vars ): string {
        return $helper_vars;
    }

    /**
     * Inject Vue methods for Jalali.
     *
     * @param string $vue_methods Existing Vue methods JS string.
     * @return string
     */
    public function inject_jalali_vue_methods( string $vue_methods ): string {
        $methods = "
            bpjalali_toggle_calendar: function() {
                var vm = this;
                if (vm.bpjalali_calendar_mode === 'jalali') {
                    vm.bpjalali_calendar_mode = 'gregorian';
                } else {
                    vm.bpjalali_calendar_mode = 'jalali';
                }
                // Trigger adapter refresh.
                if (typeof window.BPJalali !== 'undefined' && typeof window.BPJalali.onModeChange === 'function') {
                    window.BPJalali.onModeChange(vm.bpjalali_calendar_mode, vm.\$el);
                }
            },
            bpjalali_format_date: function(dateStr) {
                if (typeof window.BPJalaliEngine === 'undefined') {
                    return dateStr;
                }
                if (this.bpjalali_calendar_mode !== 'jalali') {
                    return dateStr;
                }
                return window.BPJalaliEngine.gregorianStringToJalali(dateStr, 'MMMM D, YYYY');
            },
        ";

        return $vue_methods . $methods;
    }

    /**
     * Inject onLoad logic for Jalali.
     *
     * @param string $on_load Existing onLoad JS string.
     * @return string
     */
    public function inject_jalali_on_load( string $on_load ): string {
        $load = "
            // Initialize Jalali adapter on this form instance.
            if (typeof window.BPJalali !== 'undefined' && typeof window.BPJalali.init === 'function') {
                var bpjalali_vm = this;
                vm_onload.\$nextTick(function() {
                    window.BPJalali.init(bpjalali_vm.\$el, bpjalali_vm.bpjalali_calendar_mode);
                });
            }
        ";

        return $on_load . $load;
    }

    /**
     * Convert a Gregorian date string to Jalali for display.
     *
     * Utility method for PHP-side date conversion.
     *
     * @param string $gregorian_date Gregorian date in Y-m-d format.
     * @param string $format         Output format.
     * @return string
     */
    public static function format_date_jalali( string $gregorian_date, string $format = 'MMMM D, YYYY' ): string {
        if ( BPJalali_Admin::get_default_calendar_mode() !== 'jalali' ) {
            return $gregorian_date;
        }
        return BPJalali_Calendar_Engine::gregorian_string_to_jalali( $gregorian_date, $format );
    }
}
