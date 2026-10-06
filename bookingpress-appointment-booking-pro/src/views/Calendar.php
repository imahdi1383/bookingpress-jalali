<div class="bpa-back-loader-container calendar-page-loader" id="bpa-page-loading-loader">
    <div class="bpa-back-loader"></div>
</div>
<?php
    global $BookingPressPro, $bookingpress_roles;
    $custom_role = "";
    if(!empty($bookingpress_roles)){
        $custom_role = $bookingpress_roles->get_users_custom_role();	
    }
    if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
        require_once __DIR__ . '/components/StaffMenuBar.php';
    }

?>
<div id="bookingpress-calendar"></div>
<?php require_once __DIR__ . '/components/AppointmentModel.php'; ?>
<?php require_once __DIR__ . '/components/CustomerModel.php'; ?>
<?php require_once __DIR__ . '/components/RescheduleModel.php'; ?>
<?php
    if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
        require_once __DIR__ . '/components/StaffSideMenuDrawer.php';
    } else {
        require_once __DIR__ . '/components/SideMenuDrawer.php';
    }
?>