<?php

if( !defined( 'ABSPATH' ) ){
    die;
}

class bookingpress_pro_elementor_widget{

    function __construct(){
        add_action( 'elementor/widgets/register', array( $this, 'bookingpress_pro_register_elementor_widget' ));
    }

    function bookingpress_pro_register_elementor_widget( $widgets_manager ){
        require_once __DIR__ . '/bookingpress-elementor-booking-popup-widget.php';
        $widgets_manager->register( new \BookingPress_Elementor_Booking_Popup_Widget() );
    }

}

new bookingpress_pro_elementor_widget();
