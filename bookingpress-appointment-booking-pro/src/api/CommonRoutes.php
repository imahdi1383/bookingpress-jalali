<?php

namespace BookingPressPro\api;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class CommonRoutes extends Base {

    public function __construct(){
        
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(){
        register_rest_route( 'bookingpress-app/v1', '/dismiss-license-notice',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'dismiss_license_notice' ],
                'permission_callback' => function(){
                    return current_user_can( 'manage_options' );
                }
            ]
        );

        register_rest_route( 'bookingpress-app/v1', '/verify-license-key',
            [
                'methods' => 'POST',
                'callback' => [ $this, 'verify_license' ],
                'permission_callback' => $this->permission_callback_for( 'validate_activate_license' )
            ]
        );
    }

    public function verify_license( $request ){
        $posted_license_key = $request->get_param('license_key') ?? '';

        $params = [
            'bpa_action'  => 'bpa_fetch_package_id',
            'license_key' => $posted_license_key,
            'url'         => home_url(),
        ];

        $ch_parent = curl_init();

        curl_setopt( $ch_parent, CURLOPT_URL, BOOKINGPRESS_STORE_URL );
        curl_setopt( $ch_parent, CURLOPT_POST, true );
        curl_setopt( $ch_parent, CURLOPT_POSTFIELDS, http_build_query( $params ) );
        curl_setopt( $ch_parent, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch_parent, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $ch_parent, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ] );

        $curl_response = curl_exec( $ch_parent );
        if( 'error' == $curl_response ){
            $message =  ( is_wp_error( $fetch_package ) && ! empty( $fetch_package->get_error_message() ) ) ? $fetch_package->get_error_message() : __( 'An error occurred, please try again.','bookingpress-appointment-booking' );

            $response['variant'] = 'error';
            $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
            $response['msg'] = $message;

            return new \WP_REST_Response( [
                'success' => $response['variant'] === 'success' ? true : false,
                'data' => $response
            ], 200 );
        }

        $posted_license_package = $curl_response;
        
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $posted_license_key,
            'item_id'  => $posted_license_package,
            //'item_name'  => urlencode( BOOKINGPRESS_ITEM_NAME ), // the name of our product in EDD
            'url'        => home_url()
        );

        $ch_child = curl_init();

        curl_setopt( $ch_child, CURLOPT_URL, BOOKINGPRESS_STORE_URL );
        curl_setopt( $ch_child, CURLOPT_POST, true );
        curl_setopt( $ch_child, CURLOPT_POSTFIELDS, http_build_query( $api_params ) );
        curl_setopt( $ch_child, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch_child, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $ch_child, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ] );

        $curl_response_v2 = curl_exec( $ch_child );

        $license_data = json_decode( $curl_response_v2 );
        $license_data_string = $curl_response_v2;
        if($license_data->license === "valid"){
            update_option( 'bkp_license_key', $posted_license_key );
            update_option( 'bkp_license_package', $posted_license_package );
            update_option( 'bkp_license_status', $license_data->license );
            update_option( 'bkp_license_data_activate_response', $license_data_string );

            $valid_key = hash_hmac('sha256', '_9112021029174191151512', home_url() );
            update_option( $valid_key, 1 );
            update_option( 'update_check_date', current_time( 'timestamp' ) );

            $response['variant'] = 'success';
                $response['title'] = esc_html__( 'Success', 'bookingpress-appointment-booking');
                $response['msg'] = esc_html__( 'License activated successfully.', 'bookingpress-appointment-booking');

                return new \WP_REST_Response( [
                    'success' => $response['variant'] === 'success' ? true : false,
                    'data' => $response
                ], 200 );
        } else {
            $message = !empty( $license_data->error ) ? $license_data->error : __( 'License activation failed, please try again.','bookingpress-appointment-booking' );

            $response['variant'] = 'error';
            $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
            $response['msg'] = $message;

            return new \WP_REST_Response( [
                'success' => $response['variant'] === 'success' ? true : false,
                'data' => $response
            ], 200 );
        }

        

    }

    public function dismiss_license_notice( $request ){
        $bookingpress_dismiss_date = strtotime('+7 days',current_time( 'timestamp'));
		update_option('bookingpress_dismiss_notice',$bookingpress_dismiss_date);
        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

}