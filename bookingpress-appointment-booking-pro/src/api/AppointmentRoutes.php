<?php

namespace BookingPressPro\api;

if( !defined( 'ABSPATH' ) ){ exit; }

use BookingPressPro\data\ServicesProviders;
use BookingPressPro\api\TimeRoutes;

use BookingPressPro\api\CalendarRoutes;

class AppointmentRoutes extends Base {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }

    public function register_routes() {
        register_rest_route( 'bookingpress-app/v1', '/appointment/create', [
            'methods'  => 'POST',
            'callback' => [ $this, 'create_appointment' ],
            'permission_callback' => $this->permission_callback_for('add_calendar_appointments')
        ] );
        register_rest_route( 'bookingpress-app/v1', '/appointment/update-status', [
            'methods'  => 'POST',
            'callback' => [ $this, 'update_appointment_status' ],
            'permission_callback' => $this->permission_callback_for( 'update_upcoming_appointments' )
        ] );
        register_rest_route( 'bookingpress-app/v1', '/appointment/fetch', [
            'methods'  => 'POST',
            'callback' => [ $this, 'fetch_appointment_data' ],
            'permission_callback' => $this->permission_callback_for( 'retrieve_calendar_appointments' )
        ] );
        register_rest_route( 'bookingpress-app/v1', '/appointment/reschedule', [
            'methods'  => 'POST',
            'callback' => [ $this, 'reschedule_appointment' ],
            'permission_callback' => $this->permission_callback_for( 'update_upcoming_appointments' )
        ] );
        register_rest_route( 'bookingpress-app/v1', '/appointment/can_reschedule', [
            'methods' => 'POST',
            'callback' => [ $this, 'reschedule_verification' ],
            'permission_callback' => $this->permission_callback_for( 'update_upcoming_appointments' )
        ]);

        register_rest_route( 'bookingpress-app/v1', '/appointment/fetch-meta-data',[
            'methods' => 'POST',
            'callback' => [ $this, 'retrieve_appointment_metadata' ],
            'permission_callback' => $this->permission_callback_for( 'get_appointment_meta_value' )
        ]);

        register_rest_route( 'bookingpress-app/v1', '/appointment/fetch-admin-note', [
            'methods' => 'POST',
            'callback' => [ $this, 'retrieve_admin_note'],
            'permission_callback' => $this->permission_callback_for( 'bookingpress_add_admin_note' )
        ]);

        register_rest_route( 'bookingpress-app/v1', '/appointment/add-admin-note', [
            'methods' => 'POST',
            'callback' => [ $this, 'add_admin_note' ],
            'permission_callback' => $this->permission_callback_for( 'bookingpress_add_admin_note' )
        ]);

        register_rest_route( 'bookingpress-app/v1', '/appointment/validatebeforesave', [
            'methods' => 'POST',
            'callback' => [ $this, 'bookingpress_validate_before_save' ],
            'permission_callback' => $this->permission_callback_for( 'add_calendar_appointments' )
        ]);

        register_rest_route( 'bookingpress-app/v1', '/appointment/refund-before-save', [
            'methods' => 'POST',
            'callback' => [ $this, 'bookingpress_refund_before_save_booking' ],
            'permission_callback' => $this->permission_callback_for( 'apply_for_refund' )
        ]);

    }

    public function retrieve_appointment_metadata( $request ){
        global $wpdb, $tbl_bookingpress_form_fields, $bookingpress_pro_appointment;

        $response['variant'] = 'error';
        $response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
        $response['msg'] = esc_html__('Something went wrong', 'bookingpress-appointment-booking');

        $bookingpress_appointment_booking_id = !empty($request->get_param('bookingpress_appointment_id')) ? intval($request->get_param('bookingpress_appointment_id')) : 0; // phpcs:ignore

        if(!empty($bookingpress_appointment_booking_id)){
            $bookingpress_form_field_value = $bookingpress_pro_appointment->bookingpress_get_appointment_form_field_data($bookingpress_appointment_booking_id);

            $form_field_keys = array_keys( $bookingpress_form_field_value );

            $form_field_keys_string = implode( '\',\'', $form_field_keys );

            $custom_form_fields = $wpdb->get_results( $wpdb->prepare( "SELECT bookingpress_field_meta_key, bookingpress_field_type FROM {$tbl_bookingpress_form_fields} WHERE bookingpress_field_is_default = %d AND bookingpress_is_customer_field = %d AND bookingpress_field_meta_key NOT IN ( '{$form_field_keys_string}' )", 0, 0 ) ); // phpcs:ignore

            if( !empty( $custom_form_fields ) ){
                foreach( $custom_form_fields as $form_field_data ){
                    $field_meta_key = $form_field_data->bookingpress_field_meta_key;
                    $field_type = $form_field_data->bookingpress_field_type;

                    if( 'checkbox' == $field_type ){
                        $bookingpress_form_field_value[ $field_meta_key ] = array();
                    } else {
                        $bookingpress_form_field_value[ $field_meta_key ] = '';
                    }
                }
            }
            
            $bookingpress_form_field_value = apply_filters('bookingpress_get_appointment_meta_value_filter',$bookingpress_form_field_value);
            $response['variant']            = 'success';
            $response['title']              = esc_html__( 'Success', 'bookingpress-appointment-booking' );
            $response['msg']                = esc_html__( 'Custom fields retrieved successfully.', 'bookingpress-appointment-booking' );
            $response['custom_fields_values'] = $bookingpress_form_field_value;
        }

        return new \WP_REST_Response([
            'success' => $response['variant'] == 'success',
            'data' => $response
        ], 200);

    }

    public function add_admin_note( $request ){
        global $wpdb, $tbl_bookingpress_appointment_meta;
        $response['variant'] = 'error';
        $response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
        $response['msg'] = esc_html__('Something went wrong', 'bookingpress-appointment-booking');
        
        $bookingpress_appointment_id = ! empty( $request->get_param('bookingpress_appointment_id') ) ? intval( $request->get_param('bookingpress_appointment_id') ) : '';
        $bookingpress_payment_id = ! empty( $request->get_param('bookingpress_payment_id') ) ? intval( $request->get_param('bookingpress_payment_id') ) : '';
        $bookingpress_admin_note = ! empty( $request->get_param('admin_note') ) ? sanitize_text_field( $request->get_param('admin_note') ) : '';

        if(!empty($bookingpress_appointment_id) && !empty($bookingpress_payment_id) ) {

            global $tbl_bookingpress_appointment_meta;

            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT bookingpress_appointment_meta_id FROM $tbl_bookingpress_appointment_meta WHERE bookingpress_appointment_id = %d AND bookingpress_appointment_meta_key = %s",	$bookingpress_appointment_id,'bookingpress_admin_note'));
        
            if ($existing) {

                $wpdb->update(
                    $tbl_bookingpress_appointment_meta,
                    array(
                        'bookingpress_appointment_meta_value' => $bookingpress_admin_note
                    ),
                    array(
                        'bookingpress_appointment_meta_id' => $existing
                    )
                );

            } else {
                
                $wpdb->insert(
                    $tbl_bookingpress_appointment_meta,
                    array(
                        'bookingpress_appointment_meta_key' => 'bookingpress_admin_note',
                        'bookingpress_appointment_meta_value' => $bookingpress_admin_note,
                        'bookingpress_appointment_id' => $bookingpress_appointment_id
                    )
                );
            }

                
            $response['variant'] = 'success';
            $response['title'] = esc_html__('Success', 'bookingpress-appointment-booking');
            $response['msg'] = esc_html__('Admin note added successfully', 'bookingpress-appointment-booking');
        }

        return new \WP_REST_Response([
            'success' => $response['variant'] == 'success',
            'data' => $response
        ],200);
    }

    public function retrieve_admin_note( $request ){

        $response['variant'] = 'error';
        $response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
        $response['msg'] = esc_html__('Something went wrong', 'bookingpress-appointment-booking');
        
        $bookingpress_appointment_id = ! empty( $request->get_param('bookingpress_appointment_id') ) ? intval( $request->get_param('bookingpress_appointment_id') ) : '';
        $bookingpress_payment_id = ! empty( $request->get_param('bookingpress_payment_id') ) ? intval( $request->get_param('bookingpress_payment_id') ) : '';

        if(!empty($bookingpress_appointment_id) && !empty($bookingpress_payment_id) ) {

            global $tbl_bookingpress_appointment_meta, $wpdb;

            $bookingpress_appointment_value = $wpdb->get_row($wpdb->prepare("SELECT bookingpress_appointment_meta_value FROM {$tbl_bookingpress_appointment_meta} WHERE bookingpress_appointment_id = %d AND bookingpress_appointment_meta_key = %s", $bookingpress_appointment_id, 'bookingpress_admin_note'), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_meta is a table name.

            if( !empty($bookingpress_appointment_value['bookingpress_appointment_meta_value']) ){

                $response['bookingpress_admin_note'] = !empty( $bookingpress_appointment_value['bookingpress_appointment_meta_value']) ? sanitize_text_field( stripslashes_deep( $bookingpress_appointment_value['bookingpress_appointment_meta_value']))	 : '';
                $response['variant'] = 'success';
                $response['title'] = esc_html__('Success', 'bookingpress-appointment-booking');
                $response['msg'] = esc_html__('Data retrieved successfully', 'bookingpress-appointment-booking');
        
            } else {

                $response['bookingpress_admin_note'] = '';
                $response['variant'] = 'success';
                $response['title'] = esc_html__('Success', 'bookingpress-appointment-booking');
                $response['msg'] = esc_html__('Data retrieved successfully', 'bookingpress-appointment-booking');
            }
            
        } 

        return new \WP_REST_Response([
            'success' => $response['variant'] == 'success',
            'data' => $response
        ],200);
    }

    function reschedule_verification( $request ){

        global $BookingPress;

        $form_data = $request->get_params( 'reschedule_formdata' );

        $appointment_booking_id = $form_data['id'] ?? $form_data['booking_id'];

        $fetch_appointment_data = new \WP_REST_Request( 'POST', '/bookingpress-app/v1/appointment/fetch' );
        $fetch_appointment_data->set_param( 'appointment_id', $appointment_booking_id );
	    $fetch_appointment_data->set_param( 'action', 'bookingpress_get_edit_appointment_data' );

        $response = rest_do_request( $fetch_appointment_data );

        $response_data = $response->get_data();

        /** Check For Timings */
        $service_id = $response_data['data']['bookingpress_service_id'] ?? $form_data['booking_service_id'];
        $fetch_appointment_time = new \WP_REST_Request( 'POST', '/bookingpress-app/v1/check_holiday' );
        $fetch_appointment_time->set_param( 'selected_date', ( $form_data['start_date'] ?? $form_data['booking_date'] ) );
        $fetch_appointment_time->set_param( 'service_id', ( $service_id ) );
        if( !empty( $response_data['data']['bookingpress_staff_member_id'] ) ){
            $fetch_appointment_time->set_param( 'staff_member_id', $response_data['data']['bookingpress_staff_member_id'] );
        }
        if( !empty( $response_data['data']['bookingpress_location_id'] ) ){
            $fetch_appointment_time->set_param( 'location_id', $response_data['data']['bookingpress_location_id'] );
        }

        $fetch_appointment_time->set_param( 'is_rescheduling', true );

        $service_duration = ServicesProviders::get_booking_service_duration( $appointment_booking_id );

        $fetch_appointment_time->set_param( 'service_duration_unit', $service_duration );

        $fetch_appointment_time_response = rest_do_request( $fetch_appointment_time );
        $fetch_appointment_time_response_data = $fetch_appointment_time_response->get_data();



        
        if( false == $fetch_appointment_time_response_data['success'] ){
            $final_response['variant'] = 'error';
            $final_response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
            $final_response['msg']     = esc_html__( 'Unable to reschedule this appointment. The selected date & timeslot is unavailable.', 'bookingpress-appointment-booking');
            return new \WP_REST_Response( $final_response, 400 );
        }
        /** Check For Timings */

        if ( isset($response_data['success']) && $response_data['success'] == 1 ) {
            $_REQUEST['appointment_data'] = $_POST['appointment_data'] = $response_data['data'];
        }

        $bookingpress_appointment_selected_services = $form_data['serviceId'] ?? $form_data['booking_service_id'];
        $bookingpress_appointment_booked_date = $form_data['start_date'] ?? $form_data['booking_date'];
        $bookingpress_appointment_booked_time = $form_data['start_time'] ?? $form_data['booking_time'];
        $bookingpress_appointment_end_time = $form_data['end_time'] ?? $form_data['booking_end_time'];

        $_REQUEST['appointment_data']['appointment_custom_timing'] = 'true';

        $_REQUEST['appointment_data']['appointment_selected_service'] = $_REQUEST['appointment_data']['selected_service'] = $_REQUEST['appointment_data']['bookingpress_service_id'];
        $_REQUEST['appointment_data']['selected_staffmember'] = $_REQUEST['appointment_data']['bookingpress_selected_staff_member_details']['selected_staff_member_id'] = !empty( $_REQUEST['appointment_data']['bookingpress_staff_member_id'] ) ? $_REQUEST['appointment_data']['bookingpress_staff_member_id'] : 0;
        $_REQUEST['appointment_data']['selected_location'] = !empty( $_REQUEST['appointment_data']['bookingpress_location_id'] ) ? $_REQUEST['appointment_data']['bookingpress_location_id'] : 0;
        
        do_action('bookingpress_modified_appointment_data_for_backend_appointment_booking');

        $customize_timeing_bookingpress_validation = apply_filters('bookingpress_customize_timeing_bookingpress_validation',array(),$bookingpress_appointment_selected_services,$bookingpress_appointment_booked_date,$bookingpress_appointment_booked_time, $bookingpress_appointment_end_time, $appointment_booking_id, true, $_REQUEST);

       

        if(!empty($customize_timeing_bookingpress_validation)){

            
            $final_response['variant'] = 'error';
            $final_response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');


            $final_response['msg']     = esc_html__( 'Unable to reschedule this appointment. The selected date & timeslot is unavailable.', 'bookingpress-appointment-booking');

            return new \WP_REST_Response( $final_response, 400 );
        }
       
       

        return new \WP_REST_Response([
            'success' => true,
        ],200);
    }

    public function reschedule_appointment( $request ) {
        //$reschedule_data = $request->get_param( 'reschedule_data' );

        global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_payment_logs,$tbl_bookingpress_customers,$bookingpress_email_notifications, $bookingpress_other_debug_log_id, $tbl_bookingpress_reschedule_history,$tbl_bookingpress_appointment_meta, $bookingpress_services;

        $appointment_id = $reschedule_id = $request->get_param('appointment_update_id');

        $appointment_log_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

        $appointment_service_id    = $service_id = $request->get_param( 'appointment_selected_service' );

        $bookingpress_booking_timestamp = strtotime( $appointment_log_data['bookingpress_appointment_date'] . ' ' . $appointment_log_data['bookingpress_appointment_time'] );
        $is_past_time = current_time('timestamp') > $bookingpress_booking_timestamp;

        /** Block if appointment is in past */

        if( 1 == $is_past_time ) {
            return new \WP_REST_Response(
                [
                    'variant' => 'error',
                    'success' => false,
                    'message' => esc_html__( 'Sorry, past appontment can not be rescheduled', 'bookingpress-appointment-booking')
                ],
                400
            );
        }
        /** Block if appointment is in past */

        $appointment_selected_date  = $request->get_param( 'appointment_booked_date' );
        $appointment_start_time     = $request->get_param( 'appointment_booked_time' );
        $appointment_end_time       = $request->get_param( 'appointment_booked_end_time' );
        if( '24:00' != $appointment_end_time ){
            $appointment_end_time = date( 'H:i:s', strtotime( $appointment_end_time ) );
        } else if( '24:00' == $appointment_end_time ){
            $appointment_end_time = '24:00:00';
        }

        $appointment_selected_date_time = strtotime( $appointment_selected_date . ' ' . $appointment_start_time );

        if( current_time( 'timestamp' ) > $appointment_selected_date_time ){
            return new \WP_REST_Response(
                [
                    'variant' => 'error',
                    'success' => false,
                    'message' => esc_html__( 'Sorry, Appointment can not be rescheduled as the selected time has been passed.', 'bookingpress-appointment-booking')
                ],
                400
            );
        }

        $updated_data = [
            'bookingpress_appointment_date' => $appointment_selected_date,
            'bookingpress_appointment_end_date' => $appointment_selected_date,
            'bookingpress_appointment_time' => $appointment_start_time,
            'bookingpress_appointment_end_time' => $appointment_end_time,
            'bookingpress_is_reschedule' => 1
        ];

        $reschedule_apt_update_data_where = array(
            'bookingpress_appointment_booking_id' => $appointment_id,
        );

        $is_appointment_exists            = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(bookingpress_appointment_booking_id) as total FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_service_id = %d AND bookingpress_appointment_date = %s AND bookingpress_appointment_time LIKE %s AND (bookingpress_appointment_status = '1' OR bookingpress_appointment_status = '2')", $appointment_service_id, $appointment_selected_date, $appointment_start_time ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

		$is_appointment_exists = apply_filters('bookingpress_check_rescheduled_is_appointment_already_booked',$is_appointment_exists,$reschedule_id);

        if ( $is_appointment_exists > 0 ) {
        } else {
            $bookingpress_logged_in_user_id = get_current_user_id();
            $bookingpress_customer_id = $appointment_log_data['bookingpress_customer_id'];

            $bookingpress_appointment_original_date = $appointment_log_data['bookingpress_appointment_date'];
            $bookingpress_appointment_original_start_time = $appointment_log_data['bookingpress_appointment_time'];
            $bookingpress_appointment_original_end_time = $appointment_log_data['bookingpress_appointment_end_time'];

            $bookingpress_old_service_id = !empty( $appointment_log_data['bookingpress_service_id'] ) ? $appointment_log_data['bookingpress_service_id'] : 0;
            $bookingpress_old_staff_member_id = !empty( $appointment_log_data['bookingpress_staff_member_id'] ) ? $appointment_log_data['bookingpress_staff_member_id'] : 0;

            $bookingpress_appointment_new_date = $appointment_selected_date;
            $bookingpress_appointment_new_start_time = $appointment_start_time;
            $bookingpress_appointment_new_end_time = $appointment_end_time;

            $bookingpress_reschedule_from = 2; //which means frontend
            
            $bookingpress_reschedule_history_data = array(
                'bookingpress_appointment_id' => $reschedule_id,
                'bookingpress_appointment_original_date' => $bookingpress_appointment_original_date,
                'bookingpress_appointment_original_start_time' => $bookingpress_appointment_original_start_time,
                'bookingpress_appointment_original_end_time' => $bookingpress_appointment_original_end_time,
                'bookingpress_appointment_original_service_id' => $bookingpress_old_service_id,
                'bookingpress_appointment_original_staff_member_id' => $bookingpress_old_staff_member_id,
                'bookingpress_appointment_new_date' => $bookingpress_appointment_new_date,
                'bookingpress_appointment_new_start_time' => $bookingpress_appointment_new_start_time,
                'bookingpress_appointment_new_end_time' => $bookingpress_appointment_new_end_time,
                'bookingpress_reschedule_from' => $bookingpress_reschedule_from,
                'bookingpress_wp_user_id' => $bookingpress_logged_in_user_id,
                'bookingpress_customer_id' => $bookingpress_customer_id,
            );

            if( !empty( $appointment_log_data ) ) {
                
                $get_last_appointment_data = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_bookingpress_appointment_meta} WHERE bookingpress_appointment_meta_key = %s AND bookingpress_appointment_id = %d", '_bpa_last_appointment_data', $reschedule_id) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_meta is a table name. false alarm

                if( 1 > $get_last_appointment_data ){
                    $wpdb->insert(
                        $tbl_bookingpress_appointment_meta,
                        array(
                            'bookingpress_appointment_meta_key' => '_bpa_last_appointment_data',
                            'bookingpress_appointment_meta_value' => wp_json_encode( $appointment_log_data ),
                            'bookingpress_appointment_id' => $reschedule_id
                        )
                    );
                } else {
                    $bookingpress_db_fields = array(
                        'bookingpress_appointment_meta_value' => wp_json_encode( $appointment_log_data )
                    );	
                    $wpdb->update( $tbl_bookingpress_appointment_meta, $bookingpress_db_fields, array( 'bookingpress_appointment_id' => $reschedule_id, 'bookingpress_appointment_meta_key' => '_bpa_last_appointment_data' ) );
                }
            }

            $wpdb->insert($tbl_bookingpress_reschedule_history, $bookingpress_reschedule_history_data);
        }

        $update = $wpdb->update( $tbl_bookingpress_appointment_bookings, $updated_data, array( 'bookingpress_appointment_booking_id' => $appointment_id ) );

        if ( $update > 0 ) {

            $bookingpress_reschedule_appointment_success_msg = $BookingPress->bookingpress_get_customize_settings('reschedule_appointment_success_msg', 'booking_my_booking');
            
            if ( ! empty( $reschedule_id ) ) {
                
                do_action( 'bookingpress_after_rescheduled_appointment', $reschedule_id );

                $appointment_log_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $reschedule_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm
                if ( ! empty( $appointment_log_data ) ) {
                    $bookingpress_customer_data = $BookingPress->get_customer_details( $appointment_log_data['bookingpress_customer_id'] );
                    $bookingpress_wpuser_id     = $bookingpress_customer_data['bookingpress_wpuser_id'];
                    

                    if ( !empty( $bookingpress_customer_data ) ) {
                        $bookingpress_customer_email = $bookingpress_customer_data['bookingpress_user_email'];
                        // Send customer email notification
                        $bookingpress_cc_emails = array();
                        $bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, 'Appointment Rescheduled', $reschedule_id);

                        $bookingpress_email_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', 'Appointment Rescheduled', $reschedule_id, $bookingpress_customer_email, $bookingpress_cc_emails );
                        $is_email_sent          = $bookingpress_email_res['is_mail_sent'];
                        // Send admin email notification

                        $bookingpress_admin_emails = $BookingPress->bookingpress_get_settings( 'admin_email', 'notification_setting' );
                        $bookingpress_admin_emails = apply_filters('bookingpress_filter_admin_email_data', $bookingpress_admin_emails, $reschedule_id,'Appointment Rescheduled');
                        if ( ! empty( $bookingpress_admin_emails ) ) {
                            $bookingpress_cc_emails = array();
                            $bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, 'Appointment Rescheduled');

                            $bookingpress_admin_emails = explode( ',', $bookingpress_admin_emails );
                            foreach ( $bookingpress_admin_emails as $admin_email_key => $admin_email_val ) {
                                $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', 'Appointment Rescheduled', $reschedule_id, $admin_email_val, $bookingpress_cc_emails );
                            }
                        }
                    }
                }						
            }

            $response['variant']     = 'success';
            $response['title']       = esc_html__('Success', 'bookingpress-appointment-booking');
            $response['msg']         = stripslashes_deep($bookingpress_reschedule_appointment_success_msg);
            $response['update_data'] = $update;
            
            return new \WP_REST_Response(
                $response,
                200
            );
        } else {
            return new \WP_REST_Response(
                [
                    'variant' => 'error',
                    'title' => esc_html__( 'Error', 'bookingpress-appointment-booking'),
                    'msg' => esc_html__( 'Something went wrong', 'bookingpress-appointment-booking'),
                ],
                400
            );
        }
        
	    $appointment_data = CalendarRoutes::get_single_appointment( $appointment_id );
	
        return new \WP_REST_Response(
            [
                'variant' => 'success',
                'success' => true,
                'appointment_details' => $appointment_data,
                'message' => esc_html__( 'Appointment rescheduled successfully', 'bookingpress-appointment-booking')
            ]
        );

        //return $this->create_appointment( $request );
    }

    public function fetch_appointment_data( $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );

        global $bookingpress_calendar;

        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore
        $_POST['appointment_id'] = $appointment_id; //phpcs:ignore

        if( !empty( $request->get_param('action' ) ) ){
            $_POST['action'] = $request->get_param('action' );
        }

        $response = $bookingpress_calendar->bookingpress_get_edit_appointment_data_func( true );

        if( !empty( $response['variant'] ) && $response['variant'] != '1' ) {
            return new \WP_REST_Response( [
                'success' => false,
            ], 400 );
        } else {
            $result = [
                'success' => true,
                'data' => $response,
            ];
            return new \WP_REST_Response( $result, 200 );
        }
    }

    public function update_appointment_status( $request ) {
        $update_appointment_id = $request->get_param( 'appointment_id' );
        $new_status = $request->get_param( 'new_status' );

        global $wpdb, $bookingpress_dashboard;

        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore

        $response = $bookingpress_dashboard->bookingpress_change_upcoming_appointment_status( $update_appointment_id, $new_status, true );

        if( !empty( $response['variant'] ) && $response['variant'] != '1' ) {
            return new \WP_REST_Response( [
                'success' => false,
            ], 400 );
        } else {
            $result = [
                'success' => true,
            ];
            return new \WP_REST_Response( $result, 200 );
        }
    }

    public function create_appointment( $request ) {

      
        $appointment_data = $request->get_param( 'appointment_data' );
        $_REQUEST['action'] = $request->get_param('action');

        global $bookingpress_calendar, $wpdb, $tbl_bookingpress_appointment_bookings, $BookingPress, $bookingpress_global_options, $tbl_bookingpress_form_fields;

        $bookingpress_global_options_arr        = $bookingpress_global_options->bookingpress_global_options();
        $bookingpress_default_date_format       = $bookingpress_global_options_arr['wp_default_date_format'];
        $bookingpress_default_time_format       = $bookingpress_global_options_arr['wp_default_time_format'];
        $bookingpress_default_date_time_format  = $bookingpress_default_date_format . ' ' . $bookingpress_default_time_format;

        array_walk( $appointment_data, function( &$value ) {
            if ( is_string( $value ) && is_array( json_decode( $value, true ) ) && json_last_error() === JSON_ERROR_NONE ) {
                $value = json_decode( $value, true );
            }
        });
        
        $_POST['appointment_data'] = addslashes( wp_json_encode( $appointment_data ) ); //phpcs:ignore
        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore

        $response = $bookingpress_calendar->bookingpress_save_appointment_booking_func( false, true );

        if( 'error' == $response['variant'] ){
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'data' => $response
                ],
                400
            );
        }

        $where_clause = '';
        if( !empty( $appointment_data['appointment_update_id'] ) ){
            $where_clause = $wpdb->prepare( " AND bookingpress_appointment_booking_id = %d", $appointment_data['appointment_update_id'] );
        } else if( !empty( $response['payment_log_id'] ) ){
            $where_clause = $wpdb->prepare( " AND bookingpress_payment_id = %d", $response['payment_log_id'] );
        }

        $where_clause = apply_filters( 'bookingpress_calendar_fetch_appointment_where_clause', $where_clause );

        $appointment_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE (bookingpress_appointment_status = %d or bookingpress_appointment_status = %d) $where_clause", 1, 2 ), ARRAY_A ); // phpcs:ignore
        $all_appointment_details = [];

        $default_form_fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT bookingpress_form_field_id, bookingpress_field_label, bookingpress_form_field_name FROM {$tbl_bookingpress_form_fields} WHERE (bookingpress_form_field_name = %s OR bookingpress_form_field_name = %s) AND bookingpress_field_is_default = %d",
                'email_address',
                'phone_number',
                1
            )
        );

        $email_field_label = esc_html__( 'Email Address', 'bookingpress-appointment-booking' );
        $phone_field_label = esc_html__( 'Phone Number', 'bookingpress-appointment-booking' );

        $email_field_id = 4; // Default field id for email address
        $phone_field_id = 5; // Default field id for phone number

        foreach( $default_form_fields as $field_data ){
            if( $field_data->bookingpress_form_field_name == 'email_address' ){
                $email_field_id = $field_data->bookingpress_form_field_id;
                $email_field_label = $field_data->bookingpress_field_label;
            } else if( $field_data->bookingpress_form_field_name == 'phone_number' ){
                $phone_field_id = $field_data->bookingpress_form_field_id;
                $phone_field_label = $field_data->bookingpress_field_label;
            }
        }

        foreach( $appointment_details as $appointment_detail ){

            $bookingpress_customer_name = '';
            $bookingpress_cust_fnm = isset($appointment_detail['bookingpress_customer_firstname']) ? stripslashes_deep($appointment_detail['bookingpress_customer_firstname']) : '';
            $bookingpress_cust_lnm = isset($appointment_detail['bookingpress_customer_lastname']) ? stripslashes_deep($appointment_detail['bookingpress_customer_lastname']) : '';
            $bookingpress_cust_fullnm = isset($appointment_detail['bookingpress_customer_name']) ? stripslashes_deep($appointment_detail['bookingpress_customer_name']) : '';
            $bookingpress_cust_unm = isset($appointment_detail['bookingpress_username']) ? stripslashes_deep($appointment_detail['bookingpress_username']) : '';
            $bookingpress_cust_email = isset($appointment_detail['bookingpress_customer_email']) ? $appointment_detail['bookingpress_customer_email'] : '';
            $bookingpress_cust_phone = isset($appointment_detail['bookingpress_customer_phone'])  ? $appointment_detail['bookingpress_customer_phone'] : '';

            if(!empty($bookingpress_cust_fnm) || !empty($bookingpress_cust_lnm)) {
                $bookingpress_customer_name = !empty($bookingpress_cust_fnm) ? $bookingpress_cust_fnm : '';
                $bookingpress_customer_name .= !empty($bookingpress_customer_name) ? ' ' : '';
                $bookingpress_customer_name .= !empty($bookingpress_cust_lnm) ? $bookingpress_cust_lnm : '';
            } else if(!empty($bookingpress_cust_fullnm) && empty($bookingpress_customer_name)){
                $bookingpress_customer_name = $bookingpress_cust_fullnm;
            } else if(!empty($bookingpress_cust_unm) && empty($bookingpress_customer_name)){
                $bookingpress_customer_name = $bookingpress_cust_unm;
            } else if(!empty($bookingpress_cust_email) && empty($bookingpress_customer_name)){
                $bookingpress_customer_name = $bookingpress_cust_email;
            } else if(!empty($bookingpress_cust_phone) && empty($bookingpress_customer_name)){
                $bookingpress_customer_name = $bookingpress_cust_phone;
            }

            $service_color_scheme = ServicesProviders::get_service_color_scheme( $appointment_detail['bookingpress_service_id']);
            $color_scheme_data = ServicesProviders::get_color_scheme_data( $service_color_scheme );

            $booking_metadata = [
                'form_fields' => []
            ];

            if( !empty( $bookingpress_cust_email ) ){
                $booking_metadata['form_fields'][] = [
                    'id'        => $email_field_id,
                    'label'     => 'email',
                    'value'     => $bookingpress_cust_email
                ];
            }
            if( !empty( $bookingpress_cust_phone )){
                $booking_metadata['form_fields'][] = [
                    'id'        => $phone_field_id,
                    'label'     => 'phone',
                    'value'     => $bookingpress_cust_phone
                ];
            }

            $formatted_date = date_i18n($bookingpress_default_date_format, strtotime($appointment_detail['bookingpress_appointment_date']));
            $formatted_time = date_i18n($bookingpress_default_time_format, strtotime($appointment_detail['bookingpress_appointment_time'])) . ' - ' . date($bookingpress_default_time_format, strtotime($appointment_detail['bookingpress_appointment_end_time']));

            $booking_metadata['formatted_booking_date'] = $formatted_date;
            $booking_metadata['formatted_booking_time'] = $formatted_time;
            $booking_metadata['is_custom_timing'] = $appointment_detail['bookingpress_appointment_customize_timing'];

            $booking_metadata['booking_start_time_'] = date('H:i:s', strtotime($appointment_detail['bookingpress_appointment_time']) );
            $booking_metadata['booking_end_time_'] = date('H:i:s', strtotime($appointment_detail['bookingpress_appointment_end_time']) );

            $bookingpress_single_appointment_detail = [
                'id'            => $appointment_detail['bookingpress_appointment_booking_id'],
                'customerName'  => $bookingpress_customer_name,
                'customerId'    => $appointment_detail['bookingpress_customer_id'],
                'start_date'    => $appointment_detail['bookingpress_appointment_date'],
                'booking_date'  => date_i18n($bookingpress_default_date_format, strtotime($appointment_detail['bookingpress_appointment_date'])),
                'booking_time'  => date('H:i', strtotime($appointment_detail['bookingpress_appointment_time'])) . ' - ' . date('H:i', strtotime($appointment_detail['bookingpress_appointment_end_time'])),
                'end_date'      => ( !empty( $appointment_detail['bookingpress_appointment_end_date'] ) && $appointment_detail['bookingpress_appointment_end_date'] != '0000-00-00' ) ? $appointment_detail['bookingpress_appointment_end_date'] : $appointment_detail['bookingpress_appointment_date'],
                'start_time'    => date('H:i', strtotime($appointment_detail['bookingpress_appointment_time'])),
                'end_time'      => date('H:i', strtotime($appointment_detail['bookingpress_appointment_end_time'])),
                'serviceName'   => stripslashes_deep($appointment_detail['bookingpress_service_name']),
                'serviceId'     => $appointment_detail['bookingpress_service_id'],
                'status'        => $appointment_detail['bookingpress_appointment_status'],
                'isPast'        => strtotime( $appointment_detail['bookingpress_appointment_date'] . ' ' . $appointment_detail['bookingpress_appointment_time'] ) < current_time('timestamp') ? true : false,
                'category'      => ServicesProviders::get_service_category_id( $appointment_detail['bookingpress_service_id'] ),
                'price'         => $BookingPress->bookingpress_price_formatter_with_currency_symbol( $appointment_detail['bookingpress_paid_amount'] ),
                'theme'         => $color_scheme_data,
                'metadata'      => $booking_metadata
            ];

            if( $appointment_detail['bookingpress_service_duration_unit'] == 'd' ) {
                $bookingpress_single_appointment_detail['isDayService'] = true;
                $start_date = $bookingpress_single_appointment_detail['start_date'];
                $duration = $appointment_detail['bookingpress_service_duration_val'];
                if( $duration > 1 ){
                    $duration = $duration - 1;
                    $new_date = date("Y-m-d", strtotime($start_date . ' + '.$duration.' days'));
                    $bookingpress_single_appointment_detail['end_date'] = $new_date;
                }
            }

            $bookingpress_single_appointment_detail = apply_filters( 'bookingpress_modify_single_appointment_details_with_addional_data', $bookingpress_single_appointment_detail, $appointment_detail );

            $all_appointment_details[] = $bookingpress_single_appointment_detail;

            
        }
        $response['appointment_details'] = $all_appointment_details;
    

        $result = [
            'success' => true,
            'data'  => $response,
        ];

        return new \WP_REST_Response( $result, 200 );
    }

    public function bookingpress_validate_before_save($request ){
        global $bookingpress_calendar;        
        $appointment_data = $request->get_json_params();
        if( !empty( $appointment_data ) ) {
            if (isset($appointment_data['appointment_custom_timing']) && $appointment_data['appointment_custom_timing'] == 1) {
                $appointment_data['appointment_custom_timing'] = 'true';
            }
            $_POST['appointment_data']    = $appointment_data;
            $_REQUEST['appointment_data'] = $appointment_data;
        }
        $nonce = wp_create_nonce('bpa_wp_nonce');
        $_POST['_wpnonce']    = $nonce;
        $_REQUEST['_wpnonce'] = $nonce;
        $response = $bookingpress_calendar->bookingpress_save_appointment_booking_func(true, true);
        if( 'error' == $response['variant'] ){
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'data' => $response
                ],
                400
            );
        }
        $result = [
            'success' => true,
            'data'  => $response,
        ];
        return new \WP_REST_Response( $result, 200 );
    }

    public function bookingpress_refund_before_save_booking($request ){
        $params = $request->get_json_params();
        global $bookingpress_pro_calendar;
        //$appointment_data = $request->get_json_params();
        
        if (!empty($params['appointment_data'])) {
            $appointment_data_string = json_encode($params['appointment_data']);
            $_POST['appointment_data']    = $appointment_data_string;
            $_REQUEST['appointment_data'] = $appointment_data_string;
        }

        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' ); //phpcs:ignore
        $result = $bookingpress_pro_calendar->bookingpress_refund_before_save_appointment_booking_func( true );        
        if( 'error' == $result['variant'] ){
            return new \WP_REST_Response(
                [
                    'success' => false,
                    'data' => $result
                ],
                400
            );
        }
        return new \WP_REST_Response([
            'success' => true,
            'data'    => $result,
        ], 200);
    }
}