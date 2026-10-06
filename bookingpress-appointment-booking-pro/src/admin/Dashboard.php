<?php

namespace BookingPressPro\admin;

if( !defined( 'ABSPATH' ) ){
    exit;
}

class Dashboard extends Base{

    public static function init(){
        parent::init();
        add_filter( 'script_module_data_bookingpress-dashboard-loader', array( __CLASS__, 'dashboard_loader_script_data' ) );
    }

    public static function enqueue_assets( $hook ){

        if( empty( $_REQUEST['page'] ) || ( 'bookingpress' != $_REQUEST['page'] ) ){
            return;
        }

        wp_register_script_module(
            'bookingpress-pro-main',
            BOOKINGPRESS_PRO_URL .'/src/assets/js/main.js',
            [],
            BOOKINGPRESS_PRO_VERSION
        );
        wp_enqueue_script_module( 'bookingpress-pro-main' );

        wp_register_script_module(
            'bookingpress-dashboard',
            BOOKINGPRESS_PRO_URL . '/src/assets/js/dashboard.js',
            ['bookingpress-ui'],
            BOOKINGPRESS_PRO_VERSION
        );

        wp_enqueue_script_module( 'bookingpress-dashboard' );

        //bookingpress_dashboard_config_data
    }

    public static function dashboard_loader_script_data( $dashboard_data ){

        global $bookingpress_dashboard_vue_data_fields, $bookingpress_slugs;

        $dashboard_data['bpa_pro_dashboard_data'] = apply_filters( 'bookingpress_modify_dashboard_data_fields', $bookingpress_dashboard_vue_data_fields );

        $dashboard_data['bpa_pro_dashboard_data']['currently_selected_filter'] = 'custom';

        $dashboard_data['redirect_urls']['staffmember'] = add_query_arg('page', esc_html($bookingpress_slugs->bookingpress_staff_members), esc_url(admin_url() . 'admin.php?page=bookingpress'));

        $dashboard_data['redirect_urls']['commission'] = add_query_arg('page', esc_html($bookingpress_slugs->bookingpress_commission), esc_url(admin_url() . 'admin.php?page=bookingpress'));

        return $dashboard_data;
    }

    public static function render_dashboard_summary_filters(){
        global $BookingPress, $bookingpress_common_date_format, $BookingPressPro, $bookingpress_global_options, $bookingpress_pro_staff_members;	
        $bookingpress_common_datetime_format = $bookingpress_common_date_format . ' HH:mm';
        $bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
        $bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
        $bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

        $staff_commission_module_active = $bookingpress_pro_staff_members->bookingpress_check_staffmember_commission_module_activation();
        ?>
            <div class="bpa-dashboard-summary">
                <?php if ($BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) && $staff_commission_module_active == 1 && $BookingPressPro->bookingpress_check_capability( 'bookingpress_commission' ) ) { ?>
                    <div class="bpa-dash-summary-item" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'appointment','6')">
                        <h3 v-text="summary_data.total_completed_appointment"></h3>
                        <p><?php esc_html_e('Completed Appointments', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__primary">
                        <h3 v-text="summary_data.total_upcoming_appointment"></h3>
                        <p><?php esc_html_e('Upcoming Appointments', 'bookingpress-appointment-booking'); ?></p>
                    </div>						
                <?php } 			
                else { ?>
                    <div class="bpa-dash-summary-item" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'appointment','total')">
                        <h3 v-text="summary_data.total_appoint"></h3>
                        <p><?php esc_html_e('Total Appointments', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__primary" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'appointment','1')">
                        <h3 v-text="summary_data.approved_appoint"></h3>
                        <p><?php esc_html_e('Approved Appointments', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__secondary" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'appointment','2')">
                        <h3 v-text="summary_data.pending_appoint"></h3>
                        <p><?php esc_html_e('Pending Appointments', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                <?php } ?>
                
                <?php
                if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) { ?>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__royal-blue" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'payment', '1')">
                        <h3 v-text="summary_data.total_revenue"></h3>
                        <p><?php esc_html_e('Revenue', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                <?php
                }
                if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_customers' ) ) { ?>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__purple" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'customer')">
                        <h3 v-text="summary_data.total_customers"></h3>
                        <p><?php esc_html_e('Customers', 'bookingpress-appointment-booking'); ?></p>
                    </div>
                <?php 
                }
                if ( ! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
                    <div class="bpa-dash-summary-item bpa-dash-summary-item__brown" v-if="is_staffmember_activated == 1" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'staffmember')">
                        <h3 v-text="summary_data.total_staffmembers"></h3>
                        <p><?php echo esc_html($bookingpress_plural_staffmember_name); ?></p>
                    </div>
                <?php } ?>
                <?php if (  $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) && $staff_commission_module_active == 1 && $BookingPressPro->bookingpress_check_capability( 'bookingpress_commission' )) { ?>
                <div class="bpa-dash-summary-item bpa-dash-summary-item__commission" @click="bookingpress_dashboard_redirect_filter(currently_selected_filter,'commission')">
                    <h3 v-text="summary_data.total_staff_commission"></h3>
                    <p><?php esc_html_e('Total Commission', 'bookingpress-appointment-booking'); ?></p>
                </div>
                <?php } ?>
            </div>
        <?php
    }

    public static function render_dashboard_appointments_table(){
        global $BookingPress, $bookingpress_common_date_format, $BookingPressPro, $bookingpress_global_options, $bookingpress_pro_staff_members;	
        $bookingpress_common_datetime_format = $bookingpress_common_date_format . ' HH:mm';
        $bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
        $bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
        $bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

        $staff_commission_module_active = $bookingpress_pro_staff_members->bookingpress_check_staffmember_commission_module_activation();
        ?>
        <div class="bpa-tc__wrapper" v-if="current_screen_size == 'desktop'">
            <bp-ui-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
                <bp-ui-table-column type="expand" :expand-icon="CirclePlusFilled" :collapse-icon="RemoveFilled">
                    <template #default="scope">
                        <div class="bpa-view-appointment-card">
                            <div class="bpa-vac--head">
                                <div class="bpa-vac--head__left">

                                    <span v-if="scope.row.is_multi_service_booking != '' && scope.row.is_multi_service_booking == 1"> <h2 class="bpa-multiservice-heading"> <?php echo esc_html__('Appointment Details','bookingpress-appointment-booking'); ?> </h2> <?php esc_html_e('Booking ID', 'bookingpress-appointment-booking'); ?>: #{{ scope.row.booking_id }}</span>
                                    <span v-else><?php esc_html_e('Booking ID', 'bookingpress-appointment-booking'); ?>: #{{ scope.row.booking_id }}</span>
                                    <div class="bpa-left__service-detail" v-show="scope.row.is_multi_service_booking != '' && scope.row.is_multi_service_booking != 1">
                                        <h2> {{ scope.row.service_name }}</h2>
                                        <span class="bpa-sd__price" v-if="scope.row.bookingpress_is_deposit_enable == '1'">{{ scope.row.bookingpress_deposit_amt_with_currency }}</span>
                                        <span class="bpa-sd__price" v-else>{{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
                                    </div>
                                        
                                    <?php  
                                        do_action('bookingpress_backend_appointment_list_after_appointment_price_v3','desktop');
                                    ?>														
                                </div>
                                <div class="bpa-hw-right-btn-group bpa-vac--head__right">
                                    <?php 
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                    ?>	
                                        <bp-ui-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="(( bpa_chk_staff_role != 1 && scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3') || ( bpa_chk_staff_role == 1 && bpa_staff_refund_cap == 1 && scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3' ) )">
                                            <span class="material-icons-round">close</span>
                                            <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                        </bp-ui-button>
                                        <bp-ui-popconfirm 
                                            cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
                                            confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
                                            icon="false" 
                                            title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
                                            @confirm="bookingpress_change_status(scope.row.appointment_id, '3', scope.row)" 
                                            confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
                                            cancel-button-type="bpa-btn bpa-btn__small"
                                            v-else-if="scope.row.appointment_status != '3'">
                                            <bp-ui-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
                                                <span class="material-icons-round">close</span>
                                                <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                            </bp-ui-button>
                                        </bp-ui-popconfirm>&nbsp;
                                    <?php } ?>
                                    <?php
                                        do_action('bookingpress_add_dynamic_buttons_for_view_appointments_v3');
                                    ?>
                                </div>
                            </div>
                            <div class="bpa-vac--body">
                                <bp-ui-row :gutter="56">
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
                                        
                                        <div class="bpa-vac-body--appointment-details">
                                            <bp-ui-row :gutter="40">
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__basic-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Basic Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_date }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Time', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_time }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.appointment_note != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.note}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.appointment_note }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_staff_enable == 1 && ( (scope.row.staff_member_name != '') || (scope.row.bookingpress_staff_firstname != '') || (scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != '') || (scope.row.bookingpress_staff_email_address != '') )">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php echo esc_html($bookingpress_singular_staffmember_name); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-if="scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != ''">{{ scope.row.bookingpress_staff_firstname }} {{ scope.row.bookingpress_staff_lastname }}</h4>
                                                                <h4 v-else-if="scope.row.staff_member_name != ''">{{ scope.row.staff_member_name }}</h4>
                                                                <h4 v-else>{{ scope.row.bookingpress_staff_email_address }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_bring_anyone_with_you_enable == 1">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('No. Of Person', 'bookingpress-appointment-booking');?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.bookingpress_selected_extra_members }}</h4>
                                                            </div>
                                                        </div>
                                                        <?php do_action('add_bookingpress_appointment_details_outside_v3'); ?>
                                                    </div>
                                                </bp-ui-col>
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__customer-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Customer Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item"  v-if="scope.row.customer_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.fullname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_first_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                            <span>{{form_field_data.firstname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_first_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head" v-if="scope.row.customer_last_name != ''">
                                                                <span>{{form_field_data.lastname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body" >
                                                                <h4>{{ scope.row.customer_last_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.email_address}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_email }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_phone != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.phone_number}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_phone }}</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </bp-ui-col>
                                            </bp-ui-row>
                                        </div>
                                        <div class="bpa-vac-body--service-extras" v-if="'undefined' != typeof scope.row.bookingpress_extra_service_data && scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Extras', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-se__items">
                                                <div class="bpa-se__item" v-for="extra_details in scope.row.bookingpress_extra_service_data">
                                                    <p>{{ extra_details.extra_name }}</p>
                                                    <p class="bpa-se__item-duration"><span class="material-icons-round">schedule</span> {{ extra_details.extra_service_duration }}</p>
                                                    <p class="bpa-se__item-qty"><span><?php esc_html_e('Qty:', 'bookingpress-appointment-booking'); ?></span> {{ extra_details.selected_qty }}</p>
                                                    <p>{{ extra_details.extra_service_price_with_currency }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bpa-vac-body--custom-fields" v-if="'undefined' != typeof scope.row.custom_fields_values && scope.row.custom_fields_values.length > 0">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-cf__body">
                                                <bp-ui-row>
                                                    <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span v-html="custom_fields.label"></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-html="custom_fields.value"></h4>
                                                            </div>
                                                        </div>																
                                                    </bp-ui-col>
                                                </bp-ui-row>
                                            </div>
                                        </div>
                                        <?php do_action('bookingpress_backend_display_guest_data_v3'); ?>
                                    </bp-ui-col>
                                    <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                                    ?>
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
                                        <div class="bpa-vac-body--payment-details">
                                            <h4><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-pd__body">
                                                <div class="bpa-pd__item bpa-pd-method__item">
                                                    <span><?php esc_html_e('Payment Method', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.payment_method_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item">
                                                    <span><?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?></span>
                                                    <p :class="((scope.row.appointment_status == '2') ? 'bpa-cl-pt-orange' : '') || (scope.row.appointment_status == '3' ? 'bpa-cl-black-200' : '') || (scope.row.appointment_status == '1' ? 'bpa-cl-pt-blue' : '') || (scope.row.appointment_status == '4' ? 'bpa-cl-danger' : '') || (scope.row.appointment_status == '5' ? 'bpa-cl-pt-brown' : '') || (scope.row.appointment_status == '6' ? 'bpa-cl-pt-main-green' : '')">{{ scope.row.appointment_status_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_deposit_amt != '0'">
                                                    <span><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_deposit_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_tax_amt != '0' && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_amount_in_order_summary == 'true' ) )">
                                                    <span><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_tax_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_applied_coupon_code != ''">
                                                    <span><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?> ( {{ scope.row.bookingpress_applied_coupon_code }} )</span>
                                                    <p>{{ scope.row.bookingpress_coupon_discount_amt_with_currency }}</p>
                                                </div>
                                                <!-- for tip addon add do_action for fornt-end add appointment -->
                                                <?php do_action('bookingpress_modify_payment_appointment_section_v3') ?>
                                                <div class="bpa-pd__item bpa-pd-total__item">
                                                    <span>
                                                        <?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
                                                        <div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
                                                    </span>
                                                    <p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
                                                </div>
                                            </div>									
                                        </div>
                                    </bp-ui-col>
                                    <?php } ?>
                                </bp-ui-row>										
                            </div>
                            <?php do_action("bookingpress_manage_appointment_expnd_additional_content_add_v3"); ?>
                        </div>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column prop="booking_id" min-width="30" label="<?php esc_html_e( 'ID', 'bookingpress-appointment-booking' ); ?>">
                    <template #default="scope">
                        <span>#{{ scope.row.booking_id }}</span>
                    </template>
                </bp-ui-table-column>
                <?php do_action('bookingpress_add_column_outsite_v3'); ?>
                <bp-ui-table-column prop="appointment_date" min-width="120" label="<?php esc_html_e( 'Date', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <template #default="scope">
                        <label class="bpa-item__date-col">{{ scope.row.appointment_date }}</label>
                        <bp-ui-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
                            <span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
                        </bp-ui-tooltip>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column prop="customer_name" min-width="90" label="<?php esc_html_e( 'Customer', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <template #default="scope">
                        <span v-if="scope.row.customer_name != ''">{{ scope.row.customer_name }}</span>
                        <span v-else>{{ scope.row.customer_first_name }} {{ scope.row.customer_last_name }}</span>
                    </template>
                </bp-ui-table-column>
                <?php if ( ! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
                    <bp-ui-table-column prop="staff_member_name" min-width="90" label="<?php echo esc_html($bookingpress_singular_staffmember_name); ?>" sortable v-if="(is_staffmember_activated == 1 && typeof is_multi_staffmember_activated == 'undefined')"></bp-ui-table-column>
                    <?php do_action('bookingpress_after_staff_members_column_v3', $bookingpress_singular_staffmember_name); ?>
                <?php } ?>
                <bp-ui-table-column prop="service_name" min-width="110" label="<?php esc_html_e( 'Service', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <?php do_action('bookingpress_multiple_service_name_dashboard_section_fetch_v3'); ?>
                </bp-ui-table-column>
                <bp-ui-table-column prop="appointment_duration" min-width="70" label="<?php esc_html_e( 'Duration', 'bookingpress-appointment-booking' ); ?>" sortable sort-by="bookingpress_service_duration_sortable"></bp-ui-table-column>
                <bp-ui-table-column prop="appointment_status" min-width="80" label="<?php esc_html_e( 'Status', 'bookingpress-appointment-booking' ); ?>">
                    <template #default="scope">
                        <?php
                        if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                            ?>
                            
                            <div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''">
                                <div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
                                    <div class="bpa-btn--loader__circles">
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>
                                </div>
                                <bp-ui-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event, scope.row)" popper-class="bpa-appointment-status-dropdown-popper" append-to="body" teleported="true">
                                    <bp-ui-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
                                        <bp-ui-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></bp-ui-option>
                                    </bp-ui-option-group>
                                </bp-ui-select>
                            </div>
                            
                            <?php
                        } else {
                            ?>
                        <bp-ui-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</bp-ui-tag>
                            <?php
                        }?>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column prop="appointment_payment" min-width="100" label="<?php esc_html_e( 'Payment', 'bookingpress-appointment-booking' ); ?>" sortable sort-by="payment_numberic_amount">
                    <template #default="scope">
                        <div class="bpa-apc__amount-row">
                            <div class="bpa-apc__ar-body">
                                <span class="bpa-apc__amount" v-if="scope.row.bookingpress_is_deposit_enable == 1 && scope.row.bookingpress_payment_status == '4'">{{scope.row.appointment_payment}}</span>											
                                <span v-else class="bpa-apc__amount">{{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
                                <span v-if="scope.row.bookingpress_is_deposit_enable == 1 && scope.row.bookingpress_payment_status == '4'" class="bpa-is-deposit-payment-val"><?php esc_html_e('of', 'bookingpress-appointment-booking'); ?> {{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
                            </div>
                            <div class="bpa-apc__ar-icons">
                                <?php do_action('bookingpress_backend_appointment_list_type_icons_v3'); ?>
                                <bp-ui-tooltip content="<?php esc_html_e('Cart Transaction', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_cart == 1">
                                    <span class="material-icons-round bpa-appointment-cart-icon" v-if="scope.row.bookingpress_is_cart == 1">shopping_cart</span>
                                </bp-ui-tooltip>
                                <bp-ui-tooltip content="<?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_deposit_enable == 1">
                                    <span class="bpa-apc__deposit-icon" v-if="scope.row.bookingpress_is_deposit_enable == 1">
                                        <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16.9596 12.2237C16.8902 12.0273 16.746 11.8662 16.5583 11.7756C16.3706 11.685 16.1548 11.6723 15.9578 11.7402L13.7872 12.4116C13.2376 12.9125 13.0288 12.7838 9.00068 12.7838C8.90842 12.7838 8.81994 12.7471 8.75471 12.6819C8.68947 12.6167 8.65282 12.5282 8.65282 12.4359C8.65282 12.3437 8.68947 12.2552 8.75471 12.1899C8.81994 12.1247 8.90842 12.0881 9.00068 12.0881C13.1749 12.0881 13.0323 12.1681 13.3384 11.862C13.4551 11.7331 13.5206 11.5661 13.5228 11.3923C13.5228 11.2078 13.4495 11.0309 13.319 10.9004C13.1886 10.7699 13.0116 10.6966 12.8271 10.6966H9.10504C8.62152 10.6966 8.21801 10.0009 6.80919 10.0009H4.47856V13.6256L4.92729 13.8795C6.20362 14.6153 7.63128 15.0496 9.10115 15.149C10.571 15.2485 12.0442 15.0106 13.408 14.4535L16.5387 13.1908C16.7162 13.1104 16.8576 12.967 16.9354 12.7883C17.0132 12.6096 17.0218 12.4084 16.9596 12.2237ZM1 14.523H3.78285V9.30521H1V14.523ZM2.0714 12.9994C2.09103 12.9518 2.12099 12.9092 2.1591 12.8746C2.19722 12.84 2.24255 12.8142 2.29181 12.7993C2.34107 12.7843 2.39304 12.7805 2.44398 12.788C2.49491 12.7956 2.54353 12.8143 2.58633 12.8429C2.62913 12.8716 2.66504 12.9093 2.69147 12.9535C2.71791 12.9977 2.7342 13.0472 2.73919 13.0984C2.74417 13.1497 2.73771 13.2014 2.72028 13.2499C2.70285 13.2983 2.67489 13.3423 2.6384 13.3786C2.58145 13.4353 2.50661 13.4705 2.42662 13.4783C2.34663 13.4861 2.26641 13.4659 2.1996 13.4213C2.13279 13.3766 2.08351 13.3102 2.06014 13.2333C2.03677 13.1564 2.04074 13.0737 2.0714 12.9994ZM11.4357 8.95736C12.1237 8.95736 12.7962 8.75334 13.3683 8.37112C13.9403 7.98889 14.3862 7.44561 14.6494 6.80999C14.9127 6.17437 14.9816 5.47494 14.8474 4.80017C14.7132 4.1254 14.3819 3.50558 13.8954 3.01909C13.4089 2.53261 12.7891 2.20131 12.1143 2.06709C11.4395 1.93286 10.7401 2.00175 10.1045 2.26504C9.46886 2.52832 8.92558 2.97417 8.54336 3.54622C8.16113 4.11827 7.95711 4.79081 7.95711 5.4788C7.95711 6.40137 8.3236 7.28616 8.97596 7.93851C9.62831 8.59087 10.5131 8.95736 11.4357 8.95736ZM11.7835 5.82666H11.0878C10.811 5.82666 10.5456 5.71671 10.3499 5.521C10.1542 5.3253 10.0442 5.05986 10.0442 4.78309C10.0442 4.50632 10.1542 4.24088 10.3499 4.04518C10.5456 3.84947 10.811 3.73952 11.0878 3.73952V3.39167C11.0878 3.29941 11.1245 3.21093 11.1897 3.1457C11.2549 3.08046 11.3434 3.04381 11.4357 3.04381C11.5279 3.04381 11.6164 3.08046 11.6816 3.1457C11.7469 3.21093 11.7835 3.29941 11.7835 3.39167V3.73952H12.4792C12.5715 3.73952 12.66 3.77617 12.7252 3.84141C12.7904 3.90664 12.8271 3.99512 12.8271 4.08738C12.8271 4.17964 12.7904 4.26812 12.7252 4.33335C12.66 4.39859 12.5715 4.43524 12.4792 4.43524H11.0878C10.9956 4.43524 10.9071 4.47188 10.8418 4.53712C10.7766 4.60236 10.74 4.69083 10.74 4.78309C10.74 4.87535 10.7766 4.96383 10.8418 5.02906C10.9071 5.0943 10.9956 5.13095 11.0878 5.13095H11.7835C12.0603 5.13095 12.3257 5.24089 12.5214 5.4366C12.7171 5.63231 12.8271 5.89774 12.8271 6.17451C12.8271 6.45128 12.7171 6.71672 12.5214 6.91243C12.3257 7.10813 12.0603 7.21808 11.7835 7.21808V7.56594C11.7835 7.65819 11.7469 7.74667 11.6816 7.81191C11.6164 7.87714 11.5279 7.91379 11.4357 7.91379C11.3434 7.91379 11.2549 7.87714 11.1897 7.81191C11.1245 7.74667 11.0878 7.65819 11.0878 7.56594V7.21808H10.3921C10.2998 7.21808 10.2114 7.18143 10.1461 7.1162C10.0809 7.05096 10.0442 6.96248 10.0442 6.87022C10.0442 6.77797 10.0809 6.68949 10.1461 6.62425C10.2114 6.55902 10.2998 6.52237 10.3921 6.52237H11.7835C11.8758 6.52237 11.9643 6.48572 12.0295 6.42049C12.0947 6.35525 12.1314 6.26677 12.1314 6.17451C12.1314 6.08226 12.0947 5.99378 12.0295 5.92854C11.9643 5.86331 11.8758 5.82666 11.7835 5.82666Z" />
                                        </svg>
                                    </span>
                                </bp-ui-tooltip>
                            </div>
                        </div>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column prop="created_date" label="<?php esc_html_e( 'Created Date', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <template #default="scope">
                        <label>{{ scope.row.created_date }}</label>
                        <?php
                            if ( ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) || $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                            ?>
                            <div class="bpa-table-actions-wrap">
                                <div class="bpa-table-actions">
                                    <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                        ?>
                                        <bp-ui-tooltip effect="dark" content="" placement="top" open-delay="300">
                                            <template #content>
                                                <span><?php esc_html_e( 'Edit', 'bookingpress-appointment-booking' ); ?></span>
                                            </template>
                                            <bp-ui-button class="bpa-btn bpa-btn--icon-without-box" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
                                                <span class="material-icons-round">mode_edit</span>
                                            </bp-ui-button>
                                        </bp-ui-tooltip>

                                    <bp-ui-tooltip effect="dark" content="" placement="top" open-delay="300">
                                        <template #content>
                                            <span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
                                        </template>
                                        <bp-ui-button class="bpa-btn bpa-btn--icon-without-box bpa--admin_note" @click="open_admin_note($event, scope.row.appointment_id, scope.row.payment_id)">
                                            <span>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
                                                </svg>
                                            </span>
                                        </bp-ui-button>
                                    </bp-ui-tooltip>
                                        <?php
                                    }
                                        do_action('bookingpress_appointment_list_add_action_button_v3');
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                    </template>
                </bp-ui-table-column>
            </bp-ui-table>
        </div>
        <div class="bpa-tc__wrapper" v-if="current_screen_size == 'tablet'">
            <bp-ui-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
                <bp-ui-table-column type="expand">
                    <template #default="scope">
                        <div class="bpa-view-appointment-card">
                            <div class="bpa-vac--head">
                                <div class="bpa-vac--head__left">
                                    <span><?php esc_html_e('Booking ID', 'bookingpress-appointment-booking'); ?>: #{{ scope.row.booking_id }}</span>
                                    <div class="bpa-left__service-detail">
                                        <h2>{{ scope.row.service_name }}</h2>
                                        <span class="bpa-sd__price" v-if="scope.row.bookingpress_is_deposit_enable == '1'">{{ scope.row.bookingpress_deposit_amt_with_currency }}</span>
                                        <span class="bpa-sd__price" v-else>{{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
                                    </div>
                                    <?php  
                                        do_action('bookingpress_backend_appointment_list_after_appointment_price_v3','tablet');
                                    ?>														
                                </div>
                                <div class="bpa-hw-right-btn-group bpa-vac--head__right">
                                    <?php 
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                    ?>	
                                        <bp-ui-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3'">
                                            <span class="material-icons-round">close</span>
                                            <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                        </bp-ui-button>
                                        <bp-ui-popconfirm 
                                            cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
                                            confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
                                            icon="false" 
                                            title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
                                            @confirm="bookingpress_change_status(scope.row.appointment_id, '3', scope.row)" 
                                            confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
                                            cancel-button-type="bpa-btn bpa-btn__small"
                                            v-else-if="scope.row.appointment_status != '3'">
                                            <bp-ui-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
                                                <span class="material-icons-round">close</span>
                                                <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                            </bp-ui-button>
                                        </bp-ui-popconfirm>&nbsp;
                                    <?php } ?>
                                    <?php
                                        do_action('bookingpress_add_dynamic_buttons_for_view_appointments_v3');
                                    ?>
                                </div>
                            </div>
                            <div class="bpa-vac--body">
                                <bp-ui-row :gutter="56">
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
                                        <div class="bpa-vac-body--appointment-details">
                                            <bp-ui-row :gutter="40">
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__basic-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Basic Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_date }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Time', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_time }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.appointment_note != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.note}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.appointment_note }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_staff_enable == 1 && ( (scope.row.staff_member_name != '') || (scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != '') || (scope.row.bookingpress_staff_email_address != '') )">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php echo esc_html($bookingpress_singular_staffmember_name); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-if="scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != ''">{{ scope.row.bookingpress_staff_firstname }} {{ scope.row.bookingpress_staff_lastname }}</h4>
                                                                <h4 v-else-if="scope.row.staff_member_name != ''">{{ scope.row.staff_member_name }}</h4>
                                                                <h4 v-else>{{ scope.row.bookingpress_staff_email_address }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_bring_anyone_with_you_enable == 1">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('No. Of Person', 'bookingpress-appointment-booking');?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.bookingpress_selected_extra_members }}</h4>
                                                            </div>
                                                        </div>
                                                        <?php do_action('add_bookingpress_appointment_details_outside_v3'); ?>
                                                    </div>
                                                </bp-ui-col>
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__customer-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Customer Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item"  v-if="scope.row.customer_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.fullname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_first_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                            <span>{{form_field_data.firstname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_first_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head" v-if="scope.row.customer_last_name != ''">
                                                                <span>{{form_field_data.lastname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body" >
                                                                <h4>{{ scope.row.customer_last_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.email_address}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_email }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_phone != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.phone_number}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_phone }}</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </bp-ui-col>
                                            </bp-ui-row>
                                        </div>
                                        <div class="bpa-vac-body--service-extras" v-if="'undefined' != typeof scope.row.bookingpress_extra_service_data && scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Extras', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-se__items">
                                                <div class="bpa-se__item" v-for="extra_details in scope.row.bookingpress_extra_service_data">
                                                    <p>{{ extra_details.extra_name }}</p>
                                                    <p class="bpa-se__item-duration"><span class="material-icons-round">schedule</span> {{ extra_details.extra_service_duration }}</p>
                                                    <p class="bpa-se__item-qty"><span><?php esc_html_e('Qty:', 'bookingpress-appointment-booking'); ?></span> {{ extra_details.selected_qty }}</p>
                                                    <p>{{ extra_details.extra_service_price_with_currency }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bpa-vac-body--custom-fields" v-if="'undefined' != typeof scope.row.custom_fields_values && scope.row.custom_fields_values.length > 0">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-cf__body">
                                                <bp-ui-row>
                                                    <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span v-html="custom_fields.label"></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-html="custom_fields.value"></h4>
                                                            </div>
                                                        </div>																
                                                    </bp-ui-col>
                                                </bp-ui-row>
                                            </div>
                                        </div>
                                        <?php do_action('bookingpress_backend_display_guest_data_v3'); ?>
                                    </bp-ui-col>
                                    <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                                    ?>
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
                                        <div class="bpa-vac-body--payment-details">
                                            <h4><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-pd__body">
                                                <div class="bpa-pd__item bpa-pd-method__item">
                                                    <span><?php esc_html_e('Payment Method', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.payment_method_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item">
                                                    <span><?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?></span>
                                                    <p :class="((scope.row.appointment_status == '2') ? 'bpa-cl-pt-orange' : '') || (scope.row.appointment_status == '3' ? 'bpa-cl-black-200' : '') || (scope.row.appointment_status == '1' ? 'bpa-cl-pt-blue' : '') || (scope.row.appointment_status == '4' ? 'bpa-cl-danger' : '') || (scope.row.appointment_status == '5' ? 'bpa-cl-pt-brown' : '') || (scope.row.appointment_status == '6' ? 'bpa-cl-pt-main-green' : '')">{{ scope.row.appointment_status_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_deposit_amt != '0'">
                                                    <span><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_deposit_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_tax_amt != '0' && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_amount_in_order_summary == 'true' ) )">
                                                    <span><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_tax_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_applied_coupon_code != ''">
                                                    <span><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?> ( {{ scope.row.bookingpress_applied_coupon_code }} )</span>
                                                    <p>{{ scope.row.bookingpress_coupon_discount_amt_with_currency }}</p>
                                                </div>
                                                <!-- for tip addon add do_action for fornt-end add appointment -->
                                                <?php do_action('bookingpress_modify_payment_appointment_section_v3') ?>
                                                <div class="bpa-pd__item bpa-pd-total__item">
                                                    <span>
                                                        <?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
                                                        <div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
                                                    </span>
                                                    <p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
                                                </div>
                                            </div>									
                                        </div>
                                    </bp-ui-col>
                                    <?php } ?>
                                </bp-ui-row>										
                            </div>
                            <?php do_action("bookingpress_manage_appointment_expnd_additional_content_add_v3"); ?>
                        </div>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column prop="booking_id" min-width="30" label="<?php esc_html_e( 'ID', 'bookingpress-appointment-booking' ); ?>">
                    <template #default="scope">
                        <span>#{{ scope.row.booking_id }}</span>
                    </template>
                </bp-ui-table-column>
                <?php do_action('bookingpress_add_column_outsite_v3'); ?>
                <bp-ui-table-column prop="appointment_date" min-width="100" label="<?php esc_html_e( 'Date', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <template #default="scope">
                        <label class="bpa-item__date-col">{{ scope.row.appointment_date }}</label>
                        <label class="bpa-item__date-col bpa-item__dt-col-duration-md">
                            <span class="material-icons-round">schedule</span>
                            {{ scope.row.appointment_duration }}
                        </label>
                        <bp-ui-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
                            <span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
                        </bp-ui-tooltip>
                    </template>
                </bp-ui-table-column>									
                <bp-ui-table-column prop="service_name" min-width="100" label="<?php esc_html_e( 'Service', 'bookingpress-appointment-booking' ); ?>" sortable>
                    <?php do_action('bookingpress_multiple_service_name_dashboard_section_fetch_v3'); ?>
                </bp-ui-table-column>									
                <bp-ui-table-column prop="appointment_status" min-width="90" label="<?php esc_html_e( 'Status', 'bookingpress-appointment-booking' ); ?>">
                    <template #default="scope">
                        <?php
                        if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                            ?>
                            
                            <div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''">
                                <div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
                                    <div class="bpa-btn--loader__circles">
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>
                                </div>
                                <bp-ui-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event, scope.row)" popper-class="bpa-appointment-status-dropdown-popper">
                                    <bp-ui-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
                                        <bp-ui-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></bp-ui-option>
                                    </bp-ui-option-group>
                                </bp-ui-select>
                            </div>
                            
                            <?php
                        } else {
                            ?>
                        <bp-ui-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</bp-ui-tag>
                            <?php
                        }?>
                        <?php
                            if ( ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) || $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                            ?>
                            <div class="bpa-table-actions-wrap">
                                <div class="bpa-table-actions">
                                    <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                        ?>
                                    <bp-ui-tooltip effect="dark" content="" placement="top" open-delay="300">
                                        <template #content>
                                            <span><?php esc_html_e( 'Edit', 'bookingpress-appointment-booking' ); ?></span>
                                        </template>
                                        <bp-ui-button class="bpa-btn bpa-btn--icon-without-box" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
                                            <span class="material-icons-round">mode_edit</span>
                                        </bp-ui-button>
                                    </bp-ui-tooltip>

                                    <bp-ui-tooltip effect="dark" content="" placement="top" open-delay="300">
                                        <template #content>
                                            <span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
                                        </template>
                                        <bp-ui-button class="bpa-btn bpa-btn--icon-without-box bpa--admin_note" @click="addadminnote(event,scope.row.appointment_id,scope.row.payment_id)">
                                            <span>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
                                                </svg>
                                            </span>
                                        </bp-ui-button>
                                    </bp-ui-tooltip>
                                        <?php
                                    }
                                        do_action('bookingpress_appointment_list_add_action_button_v3');
                                    ?>
                                </div>
                            </div>
                        <?php } ?>
                    </template>
                </bp-ui-table-column>
            </bp-ui-table>
        </div>
        <div class="bpa-tc__wrapper bpa-manage-appointment-container--sm" v-if="current_screen_size == 'mobile'">
            <bp-ui-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" :show-header="false" @expand-change="bookingpress_row_expand">
                <bp-ui-table-column type="expand">
                    <template #default="scope">
                        <div class="bpa-view-appointment-card">
                            <div class="bpa-vac--head">
                                <div class="bpa-vac--head__left">
                                    <span><?php esc_html_e('Booking ID', 'bookingpress-appointment-booking'); ?>: #{{ scope.row.booking_id }}</span>
                                    <div class="bpa-left__service-detail">
                                        <h2>{{ scope.row.service_name }}</h2>
                                        <span class="bpa-sd__price" v-if="scope.row.bookingpress_is_deposit_enable == '1'">{{ scope.row.bookingpress_deposit_amt_with_currency }}</span>
                                        <span class="bpa-sd__price" v-else>{{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
                                    </div>
                                </div>
                                <div class="bpa-hw-right-btn-group bpa-vac--head__right">
                                    <?php 
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                    ?>	
                                        <bp-ui-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3'">
                                            <span class="material-icons-round">close</span>
                                            <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                        </bp-ui-button>
                                        <bp-ui-popconfirm 
                                            cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
                                            confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
                                            icon="false" 
                                            title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
                                            @confirm="bookingpress_change_status(scope.row.appointment_id, '3', scope.row)" 
                                            confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
                                            cancel-button-type="bpa-btn bpa-btn__small"
                                            v-else-if="scope.row.appointment_status != '3'">
                                            <bp-ui-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
                                                <span class="material-icons-round">close</span>
                                                <?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
                                            </bp-ui-button>
                                        </bp-ui-popconfirm>&nbsp;
                                    <?php } ?>
                                    <?php
                                        do_action('bookingpress_add_dynamic_buttons_for_view_appointments_v3');
                                    ?>
                                </div>
                            </div>
                            <?php  
                                do_action('bookingpress_backend_appointment_list_after_appointment_price_v3','mobile');
                            ?>												
                            <div class="bpa-vac--body">
                                <bp-ui-row :gutter="56">
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
                                        <div class="bpa-vac-body--appointment-details">
                                            <bp-ui-row :gutter="40">
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__basic-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Basic Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_date }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('Time', 'bookingpress-appointment-booking'); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.view_appointment_time }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.appointment_note != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.note}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.appointment_note }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_staff_enable == 1 && ( (scope.row.staff_member_name != '') || (scope.row.bookingpress_staff_firstname != '') || (scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != '') || (scope.row.bookingpress_staff_email_address != '') )">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php echo esc_html($bookingpress_singular_staffmember_name); ?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-if="scope.row.bookingpress_staff_firstname != '' && scope.row.bookingpress_staff_lastname != ''">{{ scope.row.bookingpress_staff_firstname }} {{ scope.row.bookingpress_staff_lastname }}</h4>
                                                                <h4 v-else-if="scope.row.staff_member_name != ''">{{ scope.row.staff_member_name }}</h4>
                                                                <h4 v-else>{{ scope.row.bookingpress_staff_email_address }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="is_bring_anyone_with_you_enable == 1">
                                                            <div class="bpa-bd__item-head">
                                                                <span><?php esc_html_e('No. Of Person', 'bookingpress-appointment-booking');?></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.bookingpress_selected_extra_members }}</h4>
                                                            </div>
                                                        </div>
                                                        <?php do_action('add_bookingpress_appointment_details_outside_v3'); ?>
                                                    </div>
                                                </bp-ui-col>
                                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                                    <div class="bpa-ad__customer-details">
                                                        <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Customer Details', 'bookingpress-appointment-booking'); ?></h4>
                                                        <div class="bpa-bd__item"  v-if="scope.row.customer_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.fullname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_first_name != ''">
                                                            <div class="bpa-bd__item-head">
                                                            <span>{{form_field_data.firstname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_first_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head" v-if="scope.row.customer_last_name != ''">
                                                                <span>{{form_field_data.lastname}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body" >
                                                                <h4>{{ scope.row.customer_last_name }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.email_address}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_email }}</h4>
                                                            </div>
                                                        </div>
                                                        <div class="bpa-bd__item" v-if="scope.row.customer_phone != ''">
                                                            <div class="bpa-bd__item-head">
                                                                <span>{{form_field_data.phone_number}}</span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4>{{ scope.row.customer_phone }}</h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </bp-ui-col>
                                            </bp-ui-row>
                                        </div>
                                        <div class="bpa-vac-body--service-extras" v-if="'undefined' != typeof scope.row.bookingpress_extra_service_data && scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Extras', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-se__items">
                                                <div class="bpa-se__item" v-for="extra_details in scope.row.bookingpress_extra_service_data">
                                                    <p>{{ extra_details.extra_name }}</p>
                                                    <p class="bpa-se__item-duration"><span class="material-icons-round">schedule</span> {{ extra_details.extra_service_duration }}</p>
                                                    <p class="bpa-se__item-qty"><span><?php esc_html_e('Qty:', 'bookingpress-appointment-booking'); ?></span> {{ extra_details.selected_qty }}</p>
                                                    <p>{{ extra_details.extra_service_price_with_currency }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bpa-vac-body--custom-fields" v-if="'undefined' != typeof scope.row.custom_fields_values && scope.row.custom_fields_values.length > 0">
                                            <h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-cf__body">
                                                <bp-ui-row>
                                                    <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
                                                        <div class="bpa-bd__item">
                                                            <div class="bpa-bd__item-head">
                                                                <span v-html="custom_fields.label"></span>
                                                            </div>
                                                            <div class="bpa-bd__item-body">
                                                                <h4 v-html="custom_fields.value"></h4>
                                                            </div>
                                                        </div>																
                                                    </bp-ui-col>
                                                </bp-ui-row>
                                            </div>
                                        </div>
                                        <?php do_action('bookingpress_backend_display_guest_data_v3'); ?>
                                    </bp-ui-col>
                                    <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                                    ?>
                                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
                                        <div class="bpa-vac-body--payment-details">
                                            <h4><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h4>
                                            <div class="bpa-pd__body">
                                                <div class="bpa-pd__item bpa-pd-method__item">
                                                    <span><?php esc_html_e('Payment Method', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.payment_method_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item">
                                                    <span><?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?></span>
                                                    <p :class="((scope.row.appointment_status == '2') ? 'bpa-cl-pt-orange' : '') || (scope.row.appointment_status == '3' ? 'bpa-cl-black-200' : '') || (scope.row.appointment_status == '1' ? 'bpa-cl-pt-blue' : '') || (scope.row.appointment_status == '4' ? 'bpa-cl-danger' : '') || (scope.row.appointment_status == '5' ? 'bpa-cl-pt-brown' : '') || (scope.row.appointment_status == '6' ? 'bpa-cl-pt-main-green' : '')">{{ scope.row.appointment_status_label }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_deposit_amt != '0'">
                                                    <span><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_deposit_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_tax_amt != '0' && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_amount_in_order_summary == 'true' ) )">
                                                    <span><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></span>
                                                    <p>{{ scope.row.bookingpress_tax_amt_with_currency }}</p>
                                                </div>
                                                <div class="bpa-pd__item" v-if="scope.row.bookingpress_applied_coupon_code != ''">
                                                    <span><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?> ( {{ scope.row.bookingpress_applied_coupon_code }} )</span>
                                                    <p>{{ scope.row.bookingpress_coupon_discount_amt_with_currency }}</p>
                                                </div>
                                                <!-- for tip addon add do_action for fornt-end add appointment -->
                                                <?php do_action('bookingpress_modify_payment_appointment_section_v3') ?>
                                                <div class="bpa-pd__item bpa-pd-total__item">
                                                    <span>
                                                        <?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
                                                        <div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
                                                    </span>
                                                    <p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
                                                </div>
                                            </div>									
                                        </div>
                                    </bp-ui-col>
                                    <?php } ?>
                                </bp-ui-row>										
                            </div>
                            <?php do_action("bookingpress_manage_appointment_expnd_additional_content_add_v3"); ?>
                        </div>
                    </template>
                </bp-ui-table-column>
                <bp-ui-table-column>
                    <template #default="scope">
                        <div class="bpa-ap-item__mob">
                            <div class="bpa-api--head">
                                <h4>{{ scope.row.service_name }}</h4>
                                <div class="bpa-api--head-apointment-details">
                                    <p>
                                        <span class="material-icons-round">today</span>{{ scope.row.appointment_date }} 
                                        <bp-ui-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
                                            <span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
                                        </bp-ui-tooltip>
                                    </p>
                                    <p><span class="material-icons-round">schedule</span>{{ scope.row.appointment_duration }}</p>
                                </div>
                            </div>
                            <div class="bpa-mpay-item--foot">
                                <?php
                                    if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                        ?>
                                        <div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''">
                                            <div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
                                                <div class="bpa-btn--loader__circles">
                                                    <div></div>
                                                    <div></div>
                                                    <div></div>
                                                </div>
                                            </div>
                                            <bp-ui-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event, scope.row)" popper-class="bpa-appointment-status-dropdown-popper">
                                                <bp-ui-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
                                                    <bp-ui-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></bp-ui-option>
                                                </bp-ui-option-group>
                                            </bp-ui-select>
                                        </div>
                                        <?php
                                    } else {
                                        ?>
                                    <bp-ui-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</bp-ui-tag>
                                    <?php
                                    }?>
                                <div class="bpa-mpay-fi__actions bpa-mac-fi__actions">
                                    <?php
                                        if ( ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) || $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
                                    ?>
                                    <?php
                                        if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
                                    ?>
                                    <bp-ui-button class="bpa-btn bpa-btn__small bpa-btn__filled-light" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
                                        <span class="material-icons-round">mode_edit</span>
                                    </bp-ui-button>

                                    <bp-ui-tooltip effect="dark" content="" placement="top" open-delay="300">
                                        <template #content>
                                            <span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
                                        </template>
                                        <bp-ui-button class="bpa-btn bpa-btn__small bpa-btn__filled-light bpa--admin_note" @click="addadminnote(event,scope.row.appointment_id,scope.row.payment_id)">
                                            <span>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
                                                </svg>
                                            </span>
                                        </bp-ui-button>
                                    </bp-ui-tooltip>
                                    <?php
                                        }
                                        do_action('bookingpress_appointment_list_add_action_button_v3');
                                    ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </template>
                </bp-ui-table-column>
            </bp-ui-table>
        </div>
        <?php
    }

    public static function getViewComponents(){

        global $BookingPressPro, $bookingpress_roles;
        $custom_role = "";
        if(!empty($bookingpress_roles)){
            $custom_role = $bookingpress_roles->get_users_custom_role();	
        }
        require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/AdminNoteModel.php';
        require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/AppointmentModel.php';
        require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/CustomerModel.php';
        require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/RescheduleModel.php';
        if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/StaffSideMenuDrawer.php';
        } else {
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/SideMenuDrawer.php';
        }
    
    }

    public static function render_dashboard_header(){
        global $BookingPressPro, $bookingpress_roles;
        $custom_role = "";
        if(!empty($bookingpress_roles)){
            $custom_role = $bookingpress_roles->get_users_custom_role();	
        }
        if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/StaffMenuBar.php';
        } else {
            require_once BOOKINGPRESS_DIR . '/src/views/components/Header.php';
        }
    }

}