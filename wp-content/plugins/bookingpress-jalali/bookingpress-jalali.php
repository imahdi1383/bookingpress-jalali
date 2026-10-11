<?php
/**
 * Plugin Name: BookingPress Jalali Calendar
 * Plugin URI: https://github.com/imahdi1383/bookingpress-jalali
 * Description: Adds full Jalali (Persian/Solar Hijri) calendar support to BookingPress. Works alongside BookingPress Lite and Pro without modifying their core files.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: imahdi1383
 * Text Domain: bookingpress-jalali
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants.
 */
define( 'BPJALALI_VERSION', '1.0.0' );
define( 'BPJALALI_FILE', __FILE__ );
define( 'BPJALALI_DIR', plugin_dir_path( __FILE__ ) );
define( 'BPJALALI_URL', plugin_dir_url( __FILE__ ) );
define( 'BPJALALI_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if BookingPress is active.
 *
 * @return bool
 */
function bpjalali_is_bookingpress_active() {
    return defined( 'BOOKINGPRESS_VERSION' ) || 
           is_plugin_active( 'bookingpress-appointment-booking/bookingpress-appointment-booking.php' );
}

/**
 * Show admin notice if BookingPress is not active.
 */
function bpjalali_dependency_notice() {
    if ( bpjalali_is_bookingpress_active() ) {
        return;
    }
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'BookingPress Jalali Calendar', 'bookingpress-jalali' ); ?></strong>:
            <?php esc_html_e( 'This plugin requires BookingPress Appointment Booking to be installed and activated.', 'bookingpress-jalali' ); ?>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'bpjalali_dependency_notice' );

/**
 * Initialize the plugin after all plugins are loaded.
 */
function bpjalali_init() {
    // Graceful fail if BookingPress is not active.
    if ( ! bpjalali_is_bookingpress_active() ) {
        return;
    }

    // Load text domain.
    load_plugin_textdomain( 'bookingpress-jalali', false, dirname( BPJALALI_BASENAME ) . '/languages' );

    // Include core files.
    require_once BPJALALI_DIR . 'includes/class-bpjalali-calendar-engine.php';
    require_once BPJALALI_DIR . 'includes/class-bpjalali-admin.php';
    require_once BPJALALI_DIR . 'includes/class-bpjalali-frontend.php';
    require_once BPJALALI_DIR . 'includes/class-bpjalali-integration.php';

    // Boot components.
    BPJalali_Admin::get_instance();
    BPJalali_Frontend::get_instance();
    BPJalali_Integration::get_instance();
}
add_action( 'plugins_loaded', 'bpjalali_init', 20 );

/**
 * Plugin activation hook.
 */
function bpjalali_activate() {
    // Set default options.
    if ( false === get_option( 'bpjalali_default_calendar_mode' ) ) {
        add_option( 'bpjalali_default_calendar_mode', 'jalali' );
    }
}
register_activation_hook( __FILE__, 'bpjalali_activate' );

/**
 * Plugin deactivation hook.
 */
function bpjalali_deactivate() {
    // Cleanup is intentionally minimal — settings are preserved.
}
register_deactivation_hook( __FILE__, 'bpjalali_deactivate' );
