<?php
namespace BookingPressPro\admin;

use BookingPress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AdminMenu {
    
    public static function init() {
        // Higher priority (25) ensures it runs before individual page initializations if needed
        //add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 27 );
    }

    public static function register_menus() {
        global $bookingpress_slugs, $BookingPress;
        
        // Define all menus here

        $place = $BookingPress->get_free_menu_position(26.1, 0.3);

        $bookingpress_is_wizard_complete = get_option('bookingpress_wizard_complete');
        $bookingpress_is_download_lite_automatically = get_option('bookingpress_lite_download_automatic');

        if((empty($bookingpress_is_wizard_complete) || $bookingpress_is_wizard_complete == 0) && ($bookingpress_is_download_lite_automatically == 1) ){
        } else {
            add_menu_page(
                esc_html__('BookingPress', 'bookingpress-appointment-booking'), 
                esc_html__('BookingPress', 'bookingpress-appointment-booking'), 
                'bookingpress', 
                $bookingpress_slugs->bookingpress, 
                [ BookingPress\admin\Dashboard::class, 'render_page' ],
                BOOKINGPRESS_IMAGES_URL . '/bookingpress_menu_icon.png',
                $place
            );

            add_submenu_page($bookingpress_slugs->bookingpress, esc_html__('Dashboard', 'bookingpress-appointment-booking'), esc_html__('Dashboard', 'bookingpress-appointment-booking'), 'bookingpress', $bookingpress_slugs->bookingpress);
        }
        
        $menus = [
            'calendar' => [
                'parent'        => $bookingpress_slugs->bookingpress,
                'page_title'    => esc_html__( 'Calendar', 'bookingpress-appointment-booking' ),
                'menu_title'    => esc_html__( 'Calendar', 'bookingpress-appointment-booking' ),
                'capability'    => 'bookingpress_calendar',
                'menu_slug'     => 'bookingpress-calendar',
                'callback'      => [ Calendar::class, 'render_page' ],
                'position'      => 1
            ],
            // Add other pages like 'appointments', 'services' etc. here
        ];

        foreach ( $menus as $menu ) {
            add_submenu_page( 
                $menu['parent'], 
                $menu['page_title'], 
                $menu['menu_title'], 
                $menu['capability'], 
                $menu['menu_slug'], 
                $menu['callback'],
                $menu['position'] ?? null
            );
        }
    }
}
