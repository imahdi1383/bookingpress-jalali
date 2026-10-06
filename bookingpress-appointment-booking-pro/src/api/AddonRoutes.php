<?php

namespace BookingPressPro\api;

if( !defined( 'ABSPATH' ) ){ exit; }

class AddonRoutes extends Base {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }

    public function register_routes() {

        register_rest_route( 'bookingpress-app/v1', '/addons/activate', [
            'methods'  => 'POST',
            'callback' => [ $this, 'bookingpress_activate_addon' ],
            'permission_callback' => $this->permission_callback_for('activate_default_module')
        ] );

        register_rest_route( 'bookingpress-app/v1', '/addons/deactivate', [
            'methods'  => 'POST',
            'callback' => [ $this, 'bookingpress_deactivate_addon' ],
            'permission_callback' => $this->permission_callback_for('deactivate_default_module')
        ] );

        register_rest_route( 'bookingpress-app/v1', '/addons/activate-plugin', [
            'methods'  => 'POST',
            'callback' => [ $this, 'bookingpress_activate_plugin' ],
            'permission_callback' => $this->permission_callback_for('bpa_activate_plugin', false, 'activate_plugins'),
        ] );

        register_rest_route( 'bookingpress-app/v1', '/addons/deactivate-plugin', [
            'methods'  => 'POST',
            'callback' => [ $this, 'bookingpress_deactivate_plugin' ],
            'permission_callback' => $this->permission_callback_for('deactivate_plugin', false, 'activate_plugins'),
        ] );
    }

    public function bookingpress_deactivate_plugin( $request ){
        $bookingpress_deactivate_plugin_name = ! empty( $request->get_param('plugin_name') ) ? sanitize_text_field( $request->get_param('plugin_name') ) : ''; // phpcs:ignore
        if ( ! empty( $bookingpress_deactivate_plugin_name ) ) {
            deactivate_plugins( $bookingpress_deactivate_plugin_name );

            $response['variant'] = 'success';
            $response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
            $response['msg']     = esc_html__( 'Addon Deactivated Successfully', 'bookingpress-appointment-booking' );
        }

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );
    }

    function bookingpress_activate_addon($request){

        $addon_key = $request->get_param('addon_key');

        do_action('bookingpress_before_activate_bookingpress_module',$addon_key);

        update_option( $addon_key, 'true' );
        $response['variant']      = 'success';
        $response['title']        = esc_html__( 'Success', 'bookingpress-appointment-booking' );
        $response['msg']          = esc_html__( 'Module activated successfully', 'bookingpress-appointment-booking' );
        $response['is_activated'] = 'true';

        do_action('bookingpress_after_activate_bookingpress_module', $addon_key);

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );
    }

    function bookingpress_deactivate_addon($request){

        global $wpdb, $tbl_bookingpress_appointment_bookings;

        $addon_key = $request->get_param('addon_key');

        if( 'bookingpress_staffmember_module' == $addon_key ){
            $bookingpress_current_date       = date( 'Y-m-d', strtotime( current_time( 'mysql' ) ) );
            $bookingpress_total_appointments = $wpdb->get_row( $wpdb->prepare( 'SELECT bookingpress_appointment_booking_id FROM ' . $tbl_bookingpress_appointment_bookings . ' WHERE bookingpress_staff_member_id != %s AND bookingpress_appointment_date >= %s AND ( bookingpress_appointment_status = %s OR bookingpress_appointment_status = %s ) order by bookingpress_appointment_booking_id DESC', '', $bookingpress_current_date, '1', '2' ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

            if( 0 < $bookingpress_total_appointments ){
                return new \WP_REST_Response([
                    'error'     => true,
                    'msg'       => esc_html__( 'Sorry, Staff member module could not be deactivated because one or more appointment is already booked with the staff member.', 'bookingpress-appointment-booking' )
                ]);
            }
        }

        update_option( $addon_key, '' );
        $response['variant']      = 'success';
        $response['title']        = esc_html__( 'Success', 'bookingpress-appointment-booking' );
        $response['msg']          = esc_html__( 'Module Deactivated successfully', 'bookingpress-appointment-booking' );
        $response['is_activated'] = 'false';

        do_action('bookingpress_after_deactive_module', $addon_key);

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );
    }

    function bookingpress_activate_plugin( $request ){
        $bookingpress_activate_plugin_name = ! empty( $request->get_param('plugin_name') ) ? sanitize_text_field( $request->get_param('plugin_name') ) : ''; // phpcs:ignore
        $addon_name = !empty( $request->get_param('addon_name') ) ? sanitize_text_field( $request->get_param('addon_name') ) : ''; //phpcs:ignore

        if ( ! empty( $bookingpress_activate_plugin_name ) ) {

            /** Check for the incompatible add-on confirmation */
            if( !empty( $request->get_param('incompatible_addons') ) && ( empty( $request->get_param('skip_confirmation') ) || 'true' != $request->get_param('skip_confirmation') ) ){
                $show_confirmation_box = false;
                $incompatible_addon_names = [];
                foreach( $request->get_param('incompatible_addons') as $incompatible_addon ){
                    $addon_key = $incompatible_addon['key'];
                    if( is_plugin_active( $addon_key ) ){
                        $show_confirmation_box = true;
                        $incompatible_addon_names[] = $incompatible_addon['name'];
                    }
                }
                
                if( true == $show_confirmation_box ){
                    $response['msg'] = sprintf( esc_html__( 'You are already using %1$s on your website. If you activate the %2$s, it will automatically deactivate the %1$s. Would you like to proceed?', 'bookingpress-appointment-booking' ), implode(', ', $incompatible_addon_names), $addon_name );
                    $response['variant'] = 'confirmation';
                    $response['title'] = esc_html__( 'Confirmation', 'bookingpress-appointment-booking' );
                    //wp_send_json( $response );
                    return new \WP_REST_Response( [
                        'success' => true,
                        'data' => $response
                    ], 200 );
                }
            }

            if ( $bookingpress_activate_plugin_name == 'bookingpress-woocommerce/bookingpress-woocommerce.php' ) {
                if ( class_exists( 'woocommerce' ) ) {
                    activate_plugin( $bookingpress_activate_plugin_name );
                    // Generate woocommerce product if module activated firsttime
                    do_action( 'bookingpress_generate_default_product' );
                    $response['variant'] = 'success';
                    $response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
                    $response['msg']     = esc_html__( 'Addon Activated Successfully', 'bookingpress-appointment-booking' );
                } else {
                    $response['variant'] = 'error';
                    $response['title']   = esc_html__( 'Error', 'bookingpress-appointment-booking' );
                    $response['msg']     = esc_html__( 'BookingPress WooCommerce payment gateway addon requires WooCommerce plugin installed and active', 'bookingpress-appointment-booking' );
                }
            } else {
                activate_plugin( $bookingpress_activate_plugin_name );
                $response['variant'] = 'success';
                $response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
                $response['msg']     = esc_html__( 'Addon Activated Successfully', 'bookingpress-appointment-booking' );
                if( !empty( $request->get_param('incompatible_addons') ) ){
                    foreach( $request->get_param('incompatible_addons') as $incompatible_addon ){
                        $addon_key = $incompatible_addon['key'];
                        if( is_plugin_active( $addon_key ) ){
                            deactivate_plugins( $addon_key );
                        }
                    }
                }
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );
    }
}