<?php

namespace BookingPressPro\api;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class TimeRoutes extends Base {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes'] ); 
    }

    public function register_routes() {
        register_rest_route( 'bookingpress-app/v1', '/time', [
            'methods'  => 'POST',
            'callback' => [ $this, 'get_time' ],
            'permission_callback' => $this->permission_callback_for( '', true ),            
        ] );

        register_rest_route( 'bookingpress-app/v1', '/dates', [
            'methods' => 'POST',
            'callback' => [ $this, 'get_dates' ],
            'permission_callback' => $this->permission_callback_for( '', true ),            
        ] );

        register_rest_route ( 'bookingpress-app/v1', '/check_holiday', [
            'methods' => 'POST',
            'callback' => [ $this, 'check_holiday' ],
            'permission_callback' => $this->permission_callback_for( '', true),
        ]);
    }
    
    public function check_holiday( $request ){
        
        global $BookingPress, $bookingpress_appointment_bookings, $bookingpress_pro_appointment_bookings;
        

        $selected_date = $request->get_param( 'selected_date' );
        $service_id = $request->get_param( 'service_id' );
        $staff_member_id = $request->get_param( 'staff_member_id' ) ?? 0;
        $location_id = $request->get_param( 'location_id' ) ?? 0;

        $is_rescheduling = $request->get_param( 'is_rescheduling' );
        
        $service_duration_unit = $request->get_param( 'service_duration_unit' );
        
        $resopnse = [];

        $_POST['appointment_data_obj']['appointment_update_id'] = $_POST['appointment_id'];
        $_POST['service_id'] = $_REQUEST['selected_service'] = $service_id;
        $_POST['selected_date'] = $_REQUEST['selected_date'] = $selected_date;

        $_POST['staffmember_id'] = $_POST['appointment_data_obj']['bookingpress_selected_staff_member_details']['selected_staff_member_id'] = $staff_member_id;

        $_POST['appointment_data_obj']['selected_location'] = $location_id;

        $_REQUEST['appointment_data']['appointment_custom_timing'] = 'true';

        $_REQUEST['is_rescheduling_event'] = $is_rescheduling;

        if( !empty( $service_duration_unit ) && 'd' == $service_duration_unit ){
            $timeslots = $bookingpress_pro_appointment_bookings->bookingpress_get_disable_dates_for_days_services( $selected_date, true, true );
        } else {
            $timeslots = $bookingpress_appointment_bookings->bookingpress_retrieve_timeslots( $selected_date, true, true, true, [], false );
        }


        $response = ['success' => true ];

        if( empty( $timeslots ) ){
            $response['success'] = false;
        }

        return new \WP_REST_Response( $response, 200 );
    }

    public function get_dates( $request ){

        
        $appointment_data_obj = $request->get_param( 'appointment_data_obj' );

        $appointment_data_obj['bookingpress_selected_staff_member_details']['selected_staff_member_id'] = $appointment_data_obj['selected_staffmember'];

        $appointment_data_obj['appointment_selected_staff_member'] = $appointment_data_obj['selected_staffmember'];
        
        $_POST['appointment_data_obj'] = $_REQUEST['appointment_data_obj'] = wp_json_encode( $appointment_data_obj );
        
        $_POST['service_id'] = $_REQUEST['selected_service'] = $request->get_param( 'service_id' );
        $_POST['selected_date'] = $_REQUEST['selected_date'] = $request->get_param( 'selected_date' );
        
        $_POST['staffmember_id'] = $_POST['bookingpress_selected_staffmember']['selected_staff_member_id'] = $_REQUEST['bookingpress_selected_staffmember']['selected_staff_member_id'] = $appointment_data_obj['selected_staffmember'];
        
        $_POST['bookingpress_selected_staff_member_details']['selected_staff_member_id'] = $appointment_data_obj['selected_staffmember'];

        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' );
        
        $_POST['action'] = 'bookingpress_get_disable_date';
        
        if( !empty( $appointment_data_obj['appointment_update_id'] ) ){
            $_REQUEST['is_rescheduling_event'] = true;
        }
        $external_response = apply_filters( 'bookingpress_rest_get_dates_handler', null, $request );

        
        if ( null !== $external_response ) {
            return new \WP_REST_Response( [
                'success' => true,
                'data'    => $external_response,
            ], 200 );
        }
            
        
        global $bookingpress_appointment_bookings;
        $response = $bookingpress_appointment_bookings->bookingpress_get_disable_date_func_optimized( true );

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );

    }

    public function get_time( $request ){

        $service_id = $request->get_param( 'service_id' );
        $selected_date = $request->get_param( 'selected_date' );
        $appointment_data_obj = $request->get_param( 'appointment_data_obj' );
        global $bookingpress_appointment_bookings, $BookingPress, $wpdb, $tbl_bookingpress_appointment_bookings;

        $_POST['appointment_data_obj'] = $appointment_data_obj; //phpcs:ignore
        $_POST['service_id'] = $service_id; //phpcs:ignore
        $_POST['selected_date'] = $selected_date; //phpcs:ignore
        $_POST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore
        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore

        $bookingpress_shared_service_timeslot = $BookingPress->bookingpress_get_settings('share_timeslot_between_services', 'general_setting');
        $_POST['staffmember_id'] = $_POST['bookingpress_selected_staffmember']['selected_staff_member_id'] = $_REQUEST['bookingpress_selected_staffmember']['selected_staff_member_id'] = $appointment_data_obj['bookingpress_selected_staff_member_details']['selected_staff_member_id'];
        
         
        $where_clause = '';
        if( 'true' != $bookingpress_shared_service_timeslot ){
            $where_clause = $wpdb->prepare( ' AND bookingpress_service_id = %d ', $service_id );
            $where_clause = apply_filters( 'bookingpress_booked_appointment_where_clause', $where_clause );
        }else{                
            $where_clause = apply_filters( 'bookingpress_booked_appointment_with_share_timeslot_where_clause_check', $where_clause,$service_id);
        }

        $where_clause .= $wpdb->prepare( ' AND (bookingpress_appointment_status = %s OR bookingpress_appointment_status = %s)', '1', '2' );

        $bpa_appointment_edit_id = $appointment_data_obj['appointment_update_id'] ?? 0;

        if( !empty( $bpa_appointment_edit_id ) ){
            $where_clause .= $wpdb->prepare( ' AND bookingpress_appointment_booking_id != %d', $bpa_appointment_edit_id );
        }

        $total_booked_appiontments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE (bookingpress_appointment_date = %s) $where_clause", $selected_date), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm


        /** Allow External Plugins/add-on to handle this request-first */
        $external_response = apply_filters( 'bookingpress_rest_get_time_handler', null, $request );

        if ( null !== $external_response ) {
            return new \WP_REST_Response( [
                'success' => true,
                'data'    => $external_response,
            ], 200 );
        }

        $response = $bookingpress_appointment_bookings->bookingpress_retrieve_timeslots( $selected_date, true, false, false, $total_booked_appiontments, true );

        return new \WP_REST_Response( [
            'success' => true,
            'data' => $response
        ], 200 );
    }
}