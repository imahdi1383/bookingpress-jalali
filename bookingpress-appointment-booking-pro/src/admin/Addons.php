<?php

namespace BookingPressPro\admin;

class Addons extends Base {

    protected static $slug = 'bookingpress_addons';

    public static function init(){
        parent::init();

        add_action( 'script_module_data_bookingpress-addons', [ __CLASS__, 'bookingpress_add_script_module_data' ] );
    }

    public static function bookingpress_add_script_module_data( $addons_data ){

        global $bookingpress_slugs;
        
        $addons_data['addon_image_url'] = BOOKINGPRESS_PRO_IMAGES_URL . '/dummy_70_70_image.png';

        $bookingpress_setting_page_url = add_query_arg( 'page', $bookingpress_slugs->bookingpress_settings, admin_url() . 'admin.php?page=bookingpress' );

        $addons_data['internal_modules'] = [
            'staffmember_module' => [
                'name'          => esc_html__( 'Staff Member', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Enable staff option throughout your website', 'bookingpress-appointment-booking' ),
                'config_url'    => add_query_arg('setting_page', 'staffmembers_settings', $bookingpress_setting_page_url),
                'status'        => get_option( 'bookingpress_staffmember_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/staff-member/',
            ],
            'service_extras_module' => [
                'name'          => esc_html__( 'Service Extra', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Enable extras for your services', 'bookingpress-appointment-booking' ),
                'status'        => get_option( 'bookingpress_service_extra_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/services/#extra-services',
            ],
            'coupon_module' => [
                'name'          => esc_html__( 'Coupon Management', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Give discount coupons while booking an appointment', 'bookingpress-appointment-booking' ),
                'config_url'    => add_query_arg( 'page', $bookingpress_slugs->bookingpress_coupons, admin_url() . 'admin.php?page=bookingpress' ),
                'status'        => get_option( 'bookingpress_coupon_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/coupons/',
            ],
            'deposit_module' => [
                'name'          => esc_html__( 'Deposit Payment', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Allow partial payments to be charged to a customer while booking an appointment', 'bookingpress-appointment-booking' ),
                'config_url'    => add_query_arg('setting_page', 'payment_settings', $bookingpress_setting_page_url),
                'status'        => get_option( 'bookingpress_deposit_payment_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/services/#deposit-payment',
            ],
            'multiple_quantity_module' => [
                'name'          => esc_html__( 'Multiple Quantity', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Allow customer to book an appointment for more than one person', 'bookingpress-appointment-booking' ),
                'status'        => get_option( 'bookingpress_bring_anyone_with_you_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/appointments/',
            ],
            'staff_commission_module' => [
                'name'          => esc_html__( 'Staff Commission', 'bookingpress-appointment-booking' ),
                'description'   => esc_html__( 'Set up staff commission and track appointment earnings', 'bookingpress-appointment-booking' ),
                'status'        => get_option( 'bookingpress_staffmember_commission_module' ),
                'documentation_url' => 'https://www.bookingpressplugin.com/documents/staff-member-commission/',
            ]
        ];


        return $addons_data;
    }

    public static function enqueue_assets( $hook ) {
        // Child classes implement specific enqueuing here

        if ( empty( $_REQUEST['page'] ) || $_REQUEST['page'] !== self::$slug ) {
            return;
        }

        wp_register_script_module(
            'bookingpress-addons',
            BOOKINGPRESS_PRO_URL . '/src/assets/js/addons.js',
            ['bookingpress-ui'],
            BOOKINGPRESS_PRO_VERSION
        );

        wp_enqueue_script_module( 'bookingpress-addons' );
        
    }

    protected static function render_view( $view_name, $data = [] ) {
        // Child classes implement specific view rendering here
    }

    public static function modify_addon_lists( $response ){
        return $response;
    }

    public static function render_addon_additional_buttons(){
        ?>
        <div class="bpa-ai-btns">
            <bp-ui-row type="flex">
                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">										
                    <bp-ui-button class="bpa-btn bpa-btn--primary bpa-btn--full-width" @click="bookingpress_open_addon_download_url(addons.addon_download_url)" v-if="addons.addon_isactive == '2'">
                        <span class="bpa-btn__label"><?php esc_html_e( 'Get', 'bookingpress-appointment-booking' ); ?></span>
                    </bp-ui-button>
                </bp-ui-col>
            </bp-ui-row>
            <bp-ui-row type="flex">
                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                    <bp-ui-button class="bpa-btn bpa-btn--primary bpa-btn--full-width" @click="bookingpress_activate_plugin(addons.addon_installer, addons.addon_key, addons.addon_name, addons, (addons.addon_incompatibility || []) )" :class="typeof is_display_activate_loader !== 'undefined' && is_display_activate_loader == addons.addon_installer ? 'bpa-btn--is-loader' : ''"  :disabled="is_disabled_activate == addons.addon_installer ? true : false" :id="`activate-${addons.addon_key}`" v-if="addons.addon_isactive == '0'" >
                        <span class="bpa-btn__label"><?php esc_html_e( 'Activate', 'bookingpress-appointment-booking' ); ?></span>
                        <div class="bpa-btn--loader__circles">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </bp-ui-button> 
                </bp-ui-col>
            </bp-ui-row>
            <bp-ui-row type="flex" :gutter="16">
                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                    <bp-ui-button class="bpa-btn bpa-btn__filled bpa-btn--full-width" :id="`deactivate-${addons.addon_key}`" @click="bookingpress_deactivate_plugin(addons.addon_installer, addons)" :class="typeof is_display_deactivate_loader !== 'undefined' && is_display_deactivate_loader == addons.addon_installer ? 'bpa-btn--is-loader' : ''"  :disabled="is_disabled_deactivate == addons.addon_installer ? true : false" v-if="addons.addon_isactive == '1'" >
                        <span class="bpa-btn__label"><?php esc_html_e( 'Deactivate', 'bookingpress-appointment-booking' ); ?></span>
                        <div class="bpa-btn--loader__circles">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </bp-ui-button> 
                </bp-ui-col>								
                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-if="addons.addon_isactive == '1' && addons.addon_is_configurable == '1'">
                    <bp-ui-button class="bpa-btn bpa-btn--full-width" @click="bookingpress_configure_redirection(addons.addon_configure_url)" v-if="addons.addon_isactive == '1' && addons.addon_is_configurable == '1'">
                        <?php esc_html_e( 'Configure', 'bookingpress-appointment-booking' ); ?>
                    </bp-ui-button>
                </bp-ui-col>
            </bp-ui-row>
        </div>
        <?php
    }

    public static function render_addon_additional_content(){
        ?>
        <div class="bpa-ai-ribbon" v-if="addons.addon_isactive == '1'">
            <span><?php esc_html_e( 'Active', 'bookingpress-appointment-booking' ); ?></span>
        </div>
        <?php
    }

    public static function render_additional_modules(){

        ?>
        <div class="bpa-addon-sub-list-wrapper">
            <bp-ui-row type="flex" class="bpa-mlc-head-wrap">
                <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="bpa-mlc-left-heading">
                    <h1 class="bpa-page-heading bpa-adddons-page-heading"><?php esc_html_e( 'Additional Modules', 'bookingpress-appointment-booking' ); ?></h1>
                </bp-ui-col>
            </bp-ui-row>
            <bp-ui-row :gutter="30" class="bpa-addons-items-row">
                <bp-ui-col :xs="12" :sm="12" :md="12" :lg="8" :xl="6" v-for="addons in bpa_internal_modules" class="bpa-addons-items-col">
                    <div class="bpa-addon-item">
                        <span class="bpa-ai-icon" :class="addons.icon_slug"></span>							
                        <div class="bpa-ai-name">
                            <h3>{{ addons.name }}</h3>
                        </div>
                        <div class="bpa-ai-desc">
                            <p>{{ addons.description }}</p>
                        </div>
                        <div class="bpa-ai-btns">
                            <bp-ui-row type="flex">
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                    <bp-ui-button class="bpa-btn bpa-btn--primary bpa-btn--full-width" @click="bookingpress_activate_addon(addons.key)" :class="typeof is_display_activate_loader !== 'undefined' && is_display_activate_loader  == addons.key ? 'bpa-btn--is-loader' : ''" :id="`activate-${addons.key}`" :disabled="is_disabled_activate == addons.key ? true : false" v-if="addons.is_active == ''" >
                                        <span class="bpa-btn__label"><?php esc_html_e( 'Activate', 'bookingpress-appointment-booking' ); ?></span>
                                        <div class="bpa-btn--loader__circles">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>
                                    </bp-ui-button> 
                                </bp-ui-col>
                            </bp-ui-row>
                            <bp-ui-row type="flex" :gutter="16">
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                    <bp-ui-button class="bpa-btn bpa-btn__filled bpa-btn--full-width" @click="bookingpress_deactivate_addon(addons.key)" :class="typeof is_display_deactivate_loader !== 'undefined' && is_display_deactivate_loader  == addons.key ? 'bpa-btn--is-loader' : ''" :id="`deactivate-${addons.key}`" :disabled="is_disabled_deactivate == addons.key ? true : false" v-if="addons.is_active == 'true'" >
                                        <span class="bpa-btn__label"><?php esc_html_e( 'Deactivate', 'bookingpress-appointment-booking' ); ?></span>
                                        <div class="bpa-btn--loader__circles">
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>
                                    </bp-ui-button> 
                                </bp-ui-col>
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12"v-if="addons.is_configurable == 1">
                                    <bp-ui-button class="bpa-btn bpa-btn--full-width" @click="bookingpress_configure_redirection(addons.configuration_url)" v-if="addons.is_active == 'true'">
                                        <?php esc_html_e( 'Configure', 'bookingpress-appointment-booking' ); ?>
                                    </bp-ui-button>
                                </bp-ui-col>
                            </bp-ui-row>
                        </div>
                        <div class="bpa-ai-doc-link">
                            <bp-ui-link :href="addons.documentation_url" target="_blank">
                                <i class="material-icons-round">description</i><?php esc_html_e( 'Read More', 'bookingpress-appointment-booking' ); ?>
                            </bp-ui-link>
                        </div>
                        <div class="bpa-ai-ribbon" v-if="addons.is_active == 'true'">
                            <span>Active</span>
                        </div>
                    </div>
                </bp-ui-col>
            </bp-ui-row>
        </div>
        <?php

    }

}