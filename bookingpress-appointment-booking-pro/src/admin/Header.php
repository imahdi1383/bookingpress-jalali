<?php

namespace BookingPressPro\admin;

class Header extends Base{

    private static $hooks_slugs = [
        'bookingpress',
        'bookingpress_customers',
        'bookingpress_addons'
    ];

    public static function init(){
        parent::init();
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'bookingpress_print_script_data' ] );
        add_action( 'script_module_data_bookingpress-sidemenu-drawer', [ __CLASS__, 'bookingpress_add_script_module_data' ] );
    }

    public static function bookingpress_add_script_module_data( $header_data ){

        $header_data['staffmember_module'] = get_option( 'bookingpress_staffmember_module' );
        $header_data['coupon_module'] = get_option( 'bookingpress_coupon_module' );

        return $header_data;
    }

    public static function enqueue_assets( $hook ){
        $requested_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        wp_register_script_module(
            'vue',
            BOOKINGPRESS_URL .'/src/assets/js/vue.min.js',
            [],
            BOOKINGPRESS_VERSION
        );

        wp_register_script_module(
            'bookingpress-ui',
            BOOKINGPRESS_URL . '/src/assets/js/bookingpress-ui.min.js',
            ['vue'],
            BOOKINGPRESS_VERSION
        );

        wp_register_script_module(
            'bookingpress_header',
            BOOKINGPRESS_PRO_URL .'/src/assets/js/header.js',
            [],
            BOOKINGPRESS_VERSION
        );

        wp_register_script_module(
            'bookingpress-sidemenu-drawer',
            BOOKINGPRESS_URL . '/src/assets/js/drawer-loader.js',
            [ 'bookingpress-ui' ],
            BOOKINGPRESS_VERSION
        );
        
        if( in_array( $requested_page, self::$hooks_slugs ) ){
            wp_enqueue_script_module( 'bookingpress-sidemenu-drawer' );
            wp_enqueue_script_module( 'bookingpress_header' );
        }
    }

    public static function bookingpress_scoped_pages(){

        $scoped_hooks = [
            'bookingpress-calendar'
        ];

        return apply_filters( 'bookingpress_scoped_pages', $scoped_hooks );

    }

    public static function bookingpress_scoped_nonces(){
        $scoped_nonces = [
            'bookingpress_page_bookingpress-calendar'   => 'bpa_calendar_wp_nonce'
        ];

        return apply_filters( 'bookingpress_scoped_nonces', $scoped_nonces );
    }

    public static function bookingpress_print_script_data( $hook ){
        
        $scoped_pages = self::bookingpress_scoped_pages();

        $is_scoped_page = array_map( function( $page ) use ( $hook ) {
            return strpos( $hook, $page ) === 0;
        }, $scoped_pages );

        if( !$is_scoped_page ){
            return;
        }

        $nonces = self::bookingpress_scoped_nonces();

        $config = [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'rest_url' => rest_url( 'bookingpress-app/v1' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'notification_timeout' => 1500, //1.5 seconds
            'is_rtl' => is_rtl(),
            'nonce'    => !empty( $nonces[$hook] ) ? wp_create_nonce( $nonces[$hook] ) : wp_create_nonce( 'bpa_wp_nonce' ),
            '_wpnonce' => wp_create_nonce( 'bpa_wp_nonce' ),
        ];

        $config = apply_filters( 'bookingpress_modify_global_config_data', $config );

        wp_print_inline_script_tag(
            'window.BookingPressConfig = ' . wp_json_encode( $config ) . ';',
        );
    }

    public static function verify_capability( $capability ){
        global $bookingpress_pro_staff_members;

        $return = false;
        if ( ! empty( $capability ) ) {
            $user_id    = get_current_user_id();
            $user_info  = get_userdata( $user_id );
            $user_roles = $user_info->roles;
            if ( in_array( 'bookingpress-staffmember', $user_roles ) && ! in_array( 'administrator', $user_roles ) && $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ) {
                $return = true;
            } else {
                $return = true;
            }
        }
        return $return;
    }

    public static function render_admin_notices(){
        do_action( 'bookingpress_page_admin_notices' );
    }

    public static function render_header_components( $request_module, $request_action ){
        global $bookingpress_slugs, $BookingPressPro, $BookingPress;
        
        $bookingpress_staffmember_plural_name = $BookingPress->bookingpress_get_settings('bookingpress_staffmember_module_plural_name', 'staffmember_setting');

        do_action('bookingpress_add_dynamic_menu_item_to_top');

        $is_staff_enabled = get_option( 'bookingpress_staffmember_module' );
        $is_coupon_enabled = get_option( 'bookingpress_coupon_module' );

        if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_staff_members' ) ) {
            ?>
            <li class="bpa-nav-item <?php echo ( 'staff_members' == $request_module ) ? '__active' : ''; ?>" v-if="staffmember_module == 1" v-cloak>
            <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - URL is escaped properly ?>
                <a href="<?php echo add_query_arg( 'page', esc_html($bookingpress_slugs->bookingpress_staff_members), esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-nav-link">		
                    <div class="bpa-nav-link--icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/></svg>
                    </div>
                    <?php echo esc_html(stripslashes_deep($bookingpress_staffmember_plural_name)); ?>
                </a>
            </li>				
                <?php
            }
            if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_coupons' ) && 'true' == $is_coupon_enabled ) {
                ?>
                
            <li class="bpa-nav-item <?php echo ( 'coupons' == $request_module ) ? '__active' : ''; ?>" v-if="coupon_module == 1">
            <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - URL is escaped properly ?>
                <a href="<?php echo add_query_arg( 'page', esc_html($bookingpress_slugs->bookingpress_coupons), esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-nav-link">
                    <div class="bpa-nav-link--icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="m21.41 11.58-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                    </div>
                    <?php esc_html_e( 'Discounts', 'bookingpress-appointment-booking' ); ?>
                </a>
            </li>
                <?php
            }
            if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_reports' ) ) {
                ?>
                <li class="bpa-nav-item <?php echo ( 'reports' == $request_module ) ? '__active' : ''; ?>">
                <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason - URL is escaped properly ?>
                    <a href="<?php echo add_query_arg( 'page', esc_html($bookingpress_slugs->bookingpress_reports), esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-nav-link">								
                        <div class="bpa-nav-link--icon">
                            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24"><g><path d="M0,0h24v24H0V0z" fill="none"/></g><g><g><path d="M15.59,3.59C15.21,3.21,14.7,3,14.17,3H5C3.9,3,3.01,3.9,3.01,5L3,19c0,1.1,0.89,2,1.99,2H19c1.1,0,2-0.9,2-2V9.83 c0-0.53-0.21-1.04-0.59-1.41L15.59,3.59z M8,17c-0.55,0-1-0.45-1-1s0.45-1,1-1s1,0.45,1,1S8.55,17,8,17z M8,13c-0.55,0-1-0.45-1-1 s0.45-1,1-1s1,0.45,1,1S8.55,13,8,13z M8,9C7.45,9,7,8.55,7,8s0.45-1,1-1s1,0.45,1,1S8.55,9,8,9z M14,9V4.5l5.5,5.5H15 C14.45,10,14,9.55,14,9z"/></g></g></svg>
                        </div>
                        <?php esc_html_e( 'Reports', 'bookingpress-appointment-booking' ); ?>
                    </a>
                </li>
                <?php
            }
    }

    public static function render_header_settings( $request_module, $request_action ){
        global $bookingpress_slugs,$BookingPressPro;
        ?>
        <li class="bpa-nav-item <?php echo ( 'settings' == $request_module || 'notifications' == $request_module || 'addons' == $request_module  ) ? '__active' : ''; ?>">
            <bp-ui-dropdown class="bpa-nav-item-dropdown" trigger="hover">
                <a href="#" class="bpa-nav-item-dropdown__link">
                    <div class="bpa-nav-link--icon">	
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                    </div>
                    <?php esc_html_e('More', 'bookingpress-appointment-booking'); ?>
                </a>
                <template #dropdown>
                    <bp-ui-dropdown-menu slot="dropdown" class="bpa-ni-dropdown-menu" v-cloak>								
                        <?php if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_settings' ) ) { ?>
                        <bp-ui-dropdown-item class="bpa-ni-dropdown-menu--item <?php echo ( 'settings' == $request_module ) ? '__active' : ''; ?>">
                            <a href="<?php echo add_query_arg( 'page', $bookingpress_slugs->bookingpress_settings, esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-dm--item-link">
                                <span>
                                    <svg width="18px" height="18px" fill="none" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24"><rect fill="none" height="24" width="24"/><path d="M19.5,12c0-0.23-0.01-0.45-0.03-0.68l1.86-1.41c0.4-0.3,0.51-0.86,0.26-1.3l-1.87-3.23c-0.25-0.44-0.79-0.62-1.25-0.42 l-2.15,0.91c-0.37-0.26-0.76-0.49-1.17-0.68l-0.29-2.31C14.8,2.38,14.37,2,13.87,2h-3.73C9.63,2,9.2,2.38,9.14,2.88L8.85,5.19 c-0.41,0.19-0.8,0.42-1.17,0.68L5.53,4.96c-0.46-0.2-1-0.02-1.25,0.42L2.41,8.62c-0.25,0.44-0.14,0.99,0.26,1.3l1.86,1.41 C4.51,11.55,4.5,11.77,4.5,12s0.01,0.45,0.03,0.68l-1.86,1.41c-0.4,0.3-0.51,0.86-0.26,1.3l1.87,3.23c0.25,0.44,0.79,0.62,1.25,0.42 l2.15-0.91c0.37,0.26,0.76,0.49,1.17,0.68l0.29,2.31C9.2,21.62,9.63,22,10.13,22h3.73c0.5,0,0.93-0.38,0.99-0.88l0.29-2.31 c0.41-0.19,0.8-0.42,1.17-0.68l2.15,0.91c0.46,0.2,1,0.02,1.25-0.42l1.87-3.23c0.25-0.44,0.14-0.99-0.26-1.3l-1.86-1.41 C19.49,12.45,19.5,12.23,19.5,12z M12.04,15.5c-1.93,0-3.5-1.57-3.5-3.5s1.57-3.5,3.5-3.5s3.5,1.57,3.5,3.5S13.97,15.5,12.04,15.5z"/></svg>
                                </span>
                                <?php esc_html_e( 'Settings', 'bookingpress-appointment-booking' ); ?>
                            </a>
                        </bp-ui-dropdown-item>
                        <?php } ?>
                        <?php
                        if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_notifications' ) ) {
                        ?>
                        <bp-ui-dropdown-item class="bpa-ni-dropdown-menu--item <?php echo ( 'notifications' == $request_module ) ? '__active' : ''; ?>">
                            <a href="<?php echo add_query_arg( 'page', $bookingpress_slugs->bookingpress_notifications, esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-dm--item-link">
                                <span>
                                    <svg width="18px" height="18px" fill="none" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24"><g><rect fill="none" height="24" width="24" x="0"/><path d="M19,10c1.13,0,2.16-0.39,3-1.02V18c0,1.1-0.9,2-2,2H4c-1.1,0-2-0.9-2-2V6c0-1.1,0.9-2,2-2h10.1C14.04,4.32,14,4.66,14,5 c0,1.48,0.65,2.79,1.67,3.71L12,11L5.3,6.81C4.73,6.46,4,6.86,4,7.53c0,0.29,0.15,0.56,0.4,0.72l7.07,4.42 c0.32,0.2,0.74,0.2,1.06,0l4.77-2.98C17.84,9.88,18.4,10,19,10z M16,5c0,1.66,1.34,3,3,3s3-1.34,3-3s-1.34-3-3-3S16,3.34,16,5z"/></g></svg>
                                </span>
                                <?php esc_html_e( 'Notifications', 'bookingpress-appointment-booking' ); ?>
                            </a>
                        </bp-ui-dropdown-item>
                        <?php } ?>
                        <?php if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_addons' ) ) { ?>
                        <bp-ui-dropdown-item class="bpa-ni-dropdown-menu--item bpa-dm__addon-item <?php echo ( 'addons' == $request_module ) ? '__active' : ''; ?>">
                            <a href="<?php echo add_query_arg( 'page', $bookingpress_slugs->bookingpress_addons, esc_url( admin_url() . 'admin.php?page=bookingpress' ) );  // phpcs:ignore ?>" class="bpa-dm--item-link">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 0 24 24" width="18px" fill="none"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M20.5 11H19V7c0-1.1-.9-2-2-2h-4V3.5C13 2.12 11.88 1 10.5 1S8 2.12 8 3.5V5H4c-1.1 0-1.99.9-1.99 2v3.8H3.5c1.49 0 2.7 1.21 2.7 2.7s-1.21 2.7-2.7 2.7H2V20c0 1.1.9 2 2 2h3.8v-1.5c0-1.49 1.21-2.7 2.7-2.7s2.7 1.21 2.7 2.7V22H17c1.1 0 2-.9 2-2v-4h1.5c1.38 0 2.5-1.12 2.5-2.5S21.88 11 20.5 11z"/></svg>
                                </span>
                                <?php esc_html_e( 'Add-ons', 'bookingpress-appointment-booking' ); ?>
                            </a>
                        </bp-ui-dropdown-item>
                        <?php } ?>
                    </bp-ui-dropdown-menu>
                </template>
            </bp-ui-dropdown>
        </li>
        <?php
    }
}