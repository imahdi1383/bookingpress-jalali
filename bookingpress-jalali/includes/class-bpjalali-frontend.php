<?php
/**
 * Frontend asset loading for BookingPress Jalali.
 *
 * Enqueues the Jalali calendar engine JS, adapter JS, and CSS
 * on pages where BookingPress is loaded.
 *
 * @package BookingPress_Jalali
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BPJalali_Frontend
 */
class BPJalali_Frontend {

    /**
     * Singleton instance.
     *
     * @var BPJalali_Frontend|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return BPJalali_Frontend
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
        // Hook into BookingPress frontend JS loading.
        add_action( 'bookingpress_add_frontend_js', array( $this, 'enqueue_frontend_assets' ) );

        // Also enqueue on admin pages where BookingPress is loaded.
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_admin_assets' ) );

        // Enqueue frontend CSS.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_css' ) );
    }

    /**
     * Enqueue frontend JS assets.
     *
     * Called via the bookingpress_add_frontend_js action.
     */
    public function enqueue_frontend_assets(): void {
        $this->enqueue_jalali_scripts();
    }

    /**
     * Enqueue CSS on frontend.
     */
    public function enqueue_frontend_css(): void {
        wp_register_style(
            'bpjalali-style',
            BPJALALI_URL . 'assets/css/bpjalali-style.css',
            array(),
            BPJALALI_VERSION
        );
        wp_enqueue_style( 'bpjalali-style' );
    }

    /**
     * Conditionally enqueue on BookingPress admin pages.
     *
     * @param string $hook The current admin page hook.
     */
    public function maybe_enqueue_admin_assets( string $hook ): void {
        if ( ! isset( $_REQUEST['page'] ) ) {
            return;
        }

        $page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );

        // Only load on BookingPress admin pages.
        if ( strpos( $page, 'bookingpress' ) === false ) {
            return;
        }

        $this->enqueue_jalali_scripts();

        wp_register_style(
            'bpjalali-style',
            BPJALALI_URL . 'assets/css/bpjalali-style.css',
            array(),
            BPJALALI_VERSION
        );
        wp_enqueue_style( 'bpjalali-style' );
    }

    /**
     * Enqueue the Jalali calendar engine and adapter scripts.
     */
    private function enqueue_jalali_scripts(): void {
        // Calendar engine — pure functions, no dependencies.
        wp_register_script(
            'bpjalali-calendar-engine',
            BPJALALI_URL . 'assets/js/bpjalali-calendar-engine.js',
            array(),
            BPJALALI_VERSION,
            true
        );
        wp_enqueue_script( 'bpjalali-calendar-engine' );

        // Adapter — depends on calendar engine and BookingPress Vue.
        wp_register_script(
            'bpjalali-adapter',
            BPJALALI_URL . 'assets/js/bpjalali-adapter.js',
            array( 'bpjalali-calendar-engine' ),
            BPJALALI_VERSION,
            true
        );
        wp_enqueue_script( 'bpjalali-adapter' );

        // Pass configuration to JS.
        $config = array(
            'defaultMode' => BPJalali_Admin::get_default_calendar_mode(),
            'monthNames'  => array_values( BPJalali_Calendar_Engine::MONTH_NAMES ),
            'monthNamesShort' => array_values( BPJalali_Calendar_Engine::MONTH_NAMES_SHORT ),
            'weekdayNames' => BPJalali_Calendar_Engine::WEEKDAY_NAMES,
            'weekdayNamesShort' => BPJalali_Calendar_Engine::WEEKDAY_NAMES_SHORT,
            'i18n' => array(
                'jalali'    => __( 'Jalali', 'bookingpress-jalali' ),
                'gregorian' => __( 'Gregorian', 'bookingpress-jalali' ),
                'toggleCalendar' => __( 'Switch Calendar', 'bookingpress-jalali' ),
            ),
        );

        wp_localize_script( 'bpjalali-calendar-engine', 'bpjalaliConfig', $config );
    }
}
