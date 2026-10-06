<?php

namespace BookingPressPro;

if( !defined( 'ABSPATH' ) ) {
    exit;
}

use BookingPressPro\admin\Header;
use BookingPressPro\admin\Dashboard as DashboardPro;
use BookingPressPro\admin\Calendar;
use BookingPressPro\admin\Addons;
use BookingPressPro\admin\Customer as CustomerPro;
use BookingPressPro\api\CalendarRoutes;
use BookingPressPro\api\CustomerRoutes;
use BookingPressPro\api\TimeRoutes;
use BookingPressPro\api\AppointmentRoutes;
use BookingPressPro\api\CustomerProRoutes;
use BookingPressPro\api\AddonRoutes;
use BookingPressPro\api\CommonRoutes;

use BookingPressPro\api\CouponRoutes;

use BookingPress\admin\Dashboard;
use BookingPress\admin\AddonsList;
use BookingPress\admin\Customer;
use BookingPress\api\DashboardRoutes;
use BookingPress\api\AddonsRoutes;
use BookingPress\api\CustomerPageRoutes;

class BookingPressLoader{

    public function __construct(){
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public static function init(){

        \BookingPressPro\admin\AdminMenu::init();

        Header::init();
        Calendar::init();
        
        /** From Base Plugin */
        if( class_exists( '\BookingPress\admin\Dashboard' ) ) {
            \BookingPress\admin\Dashboard::init();
            DashboardPro::init();
        }
        if( class_exists( '\BookingPress\admin\Customer' ) ) {
            \BookingPress\admin\Customer::init();
            CustomerPro::init();
        }
        if( class_exists( '\BookingPress\admin\AddonsList' ) ) {
            \BookingPress\admin\AddonsList::init();
        }
        Addons::init();
        
        /** From Base Plugin */
        
        
        new CalendarRoutes();
        new CustomerRoutes();
        new TimeRoutes();
        new AppointmentRoutes();
        new CustomerProRoutes();
        new CouponRoutes();
        if( class_exists( '\BookingPress\api\DashboardRoutes' ) ) {
            new \BookingPress\api\DashboardRoutes();
        }
        if( class_exists( '\BookingPress\api\AddonsRoutes' ) ) {
            new \BookingPress\api\AddonsRoutes();
        }
        if( class_exists( '\BookingPress\api\CustomerPageRoutes' ) ) {
            new \BookingPress\api\CustomerPageRoutes();
            new \BookingPressPro\api\AddonRoutes();
        }

        new CommonRoutes();

    }
}
