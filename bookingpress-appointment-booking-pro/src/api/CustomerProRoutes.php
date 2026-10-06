<?php

namespace BookingPressPro\api;

if( !defined( 'ABSPATH' ) ){ exit; }

class CustomerProRoutes extends Base {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes'] );
    }

    public function register_routes() {
        
        register_rest_route( 'bookingpress-app/v1', '/customer/export_customer_fetch', [
            'methods'  => 'POST',
            'callback' => [ $this, 'bpa_export_customer_fetch' ],
            'permission_callback' => $this->permission_callback_for( 'export_customer_details' ),            
        ] ); 
    }

    function bpa_export_customer_fetch($request){
        global $bookingpress_pro_customers;
        $_REQUEST['_wpnonce'] = wp_create_nonce( 'bpa_wp_nonce' );
        $export_field = $request->get_param( 'export_field' ); 
        $search_data = $request->get_param( 'search_data' ); 
        $_REQUEST['export_field'] = $export_field;
        $_REQUEST['search_data'] = $search_data;

        $response = $bookingpress_pro_customers->bookingpress_export_customer_data_func();
        if( 'error' === $response['variant'] ) {
            return new \WP_Error( 'rest_error', $response['msg'], [ 'status' => 400 ] );
        }
        return new \WP_REST_Response( $json_data, 200 );
    }
}