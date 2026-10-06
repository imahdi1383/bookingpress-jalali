<template id="bookingpress-calendar-mobile-navmenu" class="bpa-staff-sidebar-navigation">
    <div class="bpa-header-navbar bpa-header-navbar--v2">
        <div class="bpa-header-navbar-wrap">
            <div class="bpa-navbar-nav" id="bpa-navbar-nav">
                <ul class="bpa-ssn__navbar">
                    <?php
                        $staff_menu_item_path = __DIR__ . '/StaffMenuBarItems.php';
                        $staff_menu_item_path = apply_filters( 'bookingpress_staff_menu_item_path', $staff_menu_item_path );
                        if( file_exists( $staff_menu_item_path ) ){
                            require_once $staff_menu_item_path;
                        }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</template>