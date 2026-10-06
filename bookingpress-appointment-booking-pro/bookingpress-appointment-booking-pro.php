<?php
/*
	Plugin Name: BookingPress Pro - Appointment Booking plugin
	Description: Book appointments, create bookings, and pay online with BookingPress. Easily create appointments, manage time, and send out customized emails.
	Version: 5.7.3
	Requires at least: 6.5
	Requires PHP:      5.6
	Plugin URI: https://www.bookingpressplugin.com/
	Author: Repute Infosystems
	Author URI: https://www.bookingpressplugin.com/
	Text Domain: bookingpress-appointment-booking
	Domain Path: /languages
 */
update_option( 'bkp_license_key', 'C6D0D7F8DB6898D6ACA49DC6C9F4B996' );
update_option( 'bkp_license_package', '4110' );
update_option( 'bkp_license_status', 'valid' );
update_option( 'bkp_license_data_activate_response', '{"success":true,"license":"valid","item_id":4110,"item_name":"Standard","license_limit":0,"site_count":1,"expires":"lifetime","activations_left":"unlimited","customer_name":"Licensed","customer_email":"","price_id":false,"payment_id":0}' );
update_option( hash_hmac( 'sha256', '_9112021029174191151512', home_url() ), 1 );
update_option( 'update_check_date', time() );
delete_option( 'bkp_license_invalid_license_type' );
delete_option( 'bkp_license_invalid_license_message' );
delete_option( 'bkp_license_data_validate_response' );

add_filter( 'pre_http_request', function( $pre, $args, $url ){
	if ( false === strpos( (string) $url, 'bookingpressplugin.com' ) ) { return $pre; }
	$body = is_array( $args['body'] ?? null ) ? http_build_query( $args['body'] ) : (string) ( $args['body'] ?? '' );
	if ( false !== strpos( $body, 'bpa_fetch_package_id' ) ) {
		return array( 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => '4110', 'headers' => array(), 'cookies' => array(), 'filename' => null );
	}
	if ( false !== strpos( $body, 'edd_action' ) || false !== strpos( $body, 'bpa_edd_action' ) ) {
		return array( 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => '{"success":true,"license":"valid","item_id":4110,"item_name":"Standard","license_limit":0,"site_count":1,"expires":"lifetime","activations_left":"unlimited","customer_name":"Licensed","customer_email":"","price_id":false,"payment_id":0}', 'headers' => array(), 'cookies' => array(), 'filename' => null );
	}
	return $pre;
}, 10, 3 );

if( !defined('BOOKINGPRESS_DIR') && file_exists(WP_PLUGIN_DIR.'/bookingpress-appointment-booking/bookingpress-appointment-booking.php') ){
	
	if( !defined('BOOKINGPRESS_DIR_NAME') ){	
		define('BOOKINGPRESS_DIR_NAME', 'bookingpress-appointment-booking');
	}
	define('BOOKINGPRESS_DIR', WP_PLUGIN_DIR . '/' . BOOKINGPRESS_DIR_NAME);

	require_once BOOKINGPRESS_DIR . '/autoload.php';
}

define( 'BOOKINGPRESS_DIR_PRO_NAME_PRO', dirname( plugin_basename( __FILE__ ) ) );
define( 'BOOKINGPRESS_DIR_PRO', WP_PLUGIN_DIR . '/' . BOOKINGPRESS_DIR_PRO_NAME_PRO );

if ( file_exists( WP_PLUGIN_DIR . '/bookingpress-appointment-booking/bookingpress-appointment-booking.php' ) ) {
	require_once WP_PLUGIN_DIR . '/bookingpress-appointment-booking/bookingpress-appointment-booking.php';
} else {
	add_action( 'admin_notices', 'bookingpress_pro_display_lite_version_notice');

	function bookingpress_pro_display_lite_version_notice(){
		$check_lite_update_notice = get_option( 'bookingpress_lite_show_update_failed_notice' );

		if( false == $check_lite_update_notice || 0 == $check_lite_update_notice ){
			$wp_plugin_update_notification = sprintf( esc_html__( 'To ensure full compatibility with the BookingPress Pro version, please update the BookingPress Lite to the latest version. It seems that the automatic update for BookingPress Lite has been failed due to some reasons. For manual update instructions, please refer to %s', 'bookingpress-appointment-booking'), '<a href="https://www.bookingpressplugin.com/documents/installing-updating-bookingpress/#bookingpress-manual-update" target="_blank">'.esc_html__('our documentation', 'bookingpress-appointment-booking').'</a>'); //phpcs:ignore
			?>
				<div class="notice notice-error">
					<p><?php echo $wp_plugin_update_notification; ?></p>
				</div>
			<?php
		}
	}
}

if( file_exists( WP_PLUGIN_DIR . '/bookingpress-appointment-booking-pro/core/classes/class.bookingpress_pro.php' ) ){
	require_once WP_PLUGIN_DIR . '/bookingpress-appointment-booking-pro/core/classes/class.bookingpress_pro.php';
}

if ( file_exists( BOOKINGPRESS_DIR_PRO . '/autoload.php' ) ) {
	require_once BOOKINGPRESS_DIR_PRO . '/autoload.php';
}


add_action( 'deactivate_bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php', 'bookingpress_deactivate_addons', 1 );

function bookingpress_deactivate_addons( $network_deactivate ){

	$active_plugins = get_option('active_plugins');

	$exclude = array(
		'bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php',
		'bookingpress-appointment-booking/bookingpress-appointment-booking.php'
	);
	$deactivated_plugins = false;
	
	if( !empty( $active_plugins ) ){
		foreach( $active_plugins as $plugin_name ){
			/** Place a filter if some 3rd party plugin gets deactivated then they can use this filter to prevent deactivating them */
			$deactivate_plugin = apply_filters( 'bookingpress_deactivate_addons', true, $plugin_name ); 
			if( !in_array( $plugin_name, $exclude ) && preg_match( '/^(bookingpress(\-.*?))(\/bookingpress(\-.*?)\.php)+$/', $plugin_name ) && true == $deactivate_plugin ){
				deactivate_plugins( $plugin_name, true, $network_deactivate );
				$deactivated_plugins = true;
			}
		}
	}

	if( true == $deactivated_plugins ){
		header('Location: ' . network_admin_url('plugins.php?deactivate=true&bpa_deactivate_pro=true'));
		die;
	}

}

add_action( 'admin_init', 'bpa_deactivate_true') ;

function bpa_deactivate_true(){
	if( !empty( $_GET['bpa_deactivate_pro'] ) && true == $_GET['bpa_deactivate_pro'] ){
		deactivate_plugins( 'bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php');
		header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
	}
}