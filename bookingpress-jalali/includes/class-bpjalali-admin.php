<?php
/**
 * Admin settings for BookingPress Jalali.
 *
 * Registers a settings page under WordPress admin and provides
 * a default calendar mode option (jalali/gregorian).
 *
 * @package BookingPress_Jalali
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BPJalali_Admin
 */
class BPJalali_Admin {

    /**
     * Singleton instance.
     *
     * @var BPJalali_Admin|null
     */
    private static $instance = null;

    /**
     * Option key for default calendar mode.
     *
     * @var string
     */
    const OPTION_CALENDAR_MODE = 'bpjalali_default_calendar_mode';

    /**
     * Get singleton instance.
     *
     * @return BPJalali_Admin
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . BPJALALI_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Add submenu page under Settings.
     */
    public function add_admin_menu(): void {
        add_options_page(
            __( 'BookingPress Jalali', 'bookingpress-jalali' ),
            __( 'BP Jalali', 'bookingpress-jalali' ),
            'manage_options',
            'bpjalali-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings and fields.
     */
    public function register_settings(): void {
        register_setting(
            'bpjalali_settings_group',
            self::OPTION_CALENDAR_MODE,
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_calendar_mode' ),
                'default'           => 'jalali',
            )
        );

        add_settings_section(
            'bpjalali_general_section',
            __( 'Calendar Settings', 'bookingpress-jalali' ),
            array( $this, 'render_section_description' ),
            'bpjalali-settings'
        );

        add_settings_field(
            'bpjalali_default_mode',
            __( 'Default Calendar', 'bookingpress-jalali' ),
            array( $this, 'render_calendar_mode_field' ),
            'bpjalali-settings',
            'bpjalali_general_section'
        );
    }

    /**
     * Sanitize the calendar mode option.
     *
     * @param mixed $value Input value.
     * @return string
     */
    public function sanitize_calendar_mode( $value ): string {
        $allowed = array( 'jalali', 'gregorian' );
        return in_array( $value, $allowed, true ) ? $value : 'jalali';
    }

    /**
     * Render the settings section description.
     */
    public function render_section_description(): void {
        echo '<p>' . esc_html__( 'Configure the default calendar mode for all BookingPress calendars. Visitors can still toggle between Jalali and Gregorian per calendar instance.', 'bookingpress-jalali' ) . '</p>';
    }

    /**
     * Render the calendar mode radio field.
     */
    public function render_calendar_mode_field(): void {
        $current = get_option( self::OPTION_CALENDAR_MODE, 'jalali' );
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo esc_attr( self::OPTION_CALENDAR_MODE ); ?>"
                       value="jalali" <?php checked( $current, 'jalali' ); ?> />
                <?php esc_html_e( 'Jalali (Solar Hijri)', 'bookingpress-jalali' ); ?>
            </label>
            <br />
            <label>
                <input type="radio" name="<?php echo esc_attr( self::OPTION_CALENDAR_MODE ); ?>"
                       value="gregorian" <?php checked( $current, 'gregorian' ); ?> />
                <?php esc_html_e( 'Gregorian', 'bookingpress-jalali' ); ?>
            </label>
        </fieldset>
        <p class="description">
            <?php esc_html_e( 'This sets the default calendar for all visitors. Each visitor can switch individually via the toggle button on the booking form.', 'bookingpress-jalali' ); ?>
        </p>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BookingPress Jalali Calendar Settings', 'bookingpress-jalali' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'bpjalali_settings_group' );
                do_settings_sections( 'bpjalali-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add a Settings link to the plugins page.
     *
     * @param array $links Existing links.
     * @return array
     */
    public function add_settings_link( array $links ): array {
        $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=bpjalali-settings' ) ) . '">'
            . esc_html__( 'Settings', 'bookingpress-jalali' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Get the current default calendar mode.
     *
     * @return string 'jalali' or 'gregorian'.
     */
    public static function get_default_calendar_mode(): string {
        return get_option( self::OPTION_CALENDAR_MODE, 'jalali' );
    }
}
