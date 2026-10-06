<?php

namespace BookingPressPro\api;

if( !defined( 'ABSPATH' ) ) exit;

class CouponRoutes extends Base{

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }


    public function register_routes(){

        register_rest_route( 'bookingpress-app/v1', 'coupon/apply',[
            'methods' => 'POST',
            'callback' => [ $this, 'bpa_apply_coupon' ],
            'permission_callback' => $this->permission_callback_for('apply_coupon_code_backend')
        ] );

        register_rest_route( 'bookingpress-app/v1', 'coupon/remove',[
            'methods' => 'POST',
            'callback' => [ $this, 'bpa_remove_coupon' ],
            'permission_callback' => $this->permission_callback_for('apply_coupon_code_backend')
        ] );

    }

    public function bpa_apply_coupon($request){
        global $bookingpress_coupons;           

        $coupon_data = $request->get_param('bookingpress_apply_coupon_data');

        $_POST['appointment_formdata'] = $coupon_data['appointment_formdata'];
        $_POST['coupon_code']   = $coupon_data['coupon_code'];
        $_POST['selected_service']   = $coupon_data['selected_service'];
        $_POST['payable_amount']   = $coupon_data['payable_amount'];
        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' );

        $response = $bookingpress_coupons->bookingpress_apply_coupon_code_backend_func( true );
        

        if( !empty( $response['variant'] ) && $response['variant'] != 'success' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'data' => $response
            ], 400 );
        } else {
            $result = [
                'success' => true,
                'data' => $response
            ];
            return new \WP_REST_Response( $result, 200 );
        }
    }

    public function bpa_remove_coupon( $request ){
        global $bookingpress_coupons;

        $coupon_data = $request->get_param('coupon_code');

        $_POST['coupon_code'] = $coupon_data;
        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' );

        $response = $bookingpress_coupons->bookingpress_remove_coupon_code_func( true );

        if( !empty( $response['variant'] ) && $response['variant'] != 'success' ) {
            return new \WP_REST_Response( [
                'success' => false,
            ], 400 );
        } else {
            $result = [
                'success' => true
            ];
            return new \WP_REST_Response( $result, 200 );
        }
    }

}