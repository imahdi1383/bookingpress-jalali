<?php
global $bookingpress_global_options, $BookingPress;
$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
$bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
$bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

$bookingpres_default_time_format = $BookingPress->bookingpress_get_settings('default_time_format','general_setting');
?>
<div v-cloak id="bookingpress-appointment-dialog" class="bookingpress-appointment-dialog-container">
    <bp-ui-dialog v-model="openAddNewAppointmentModel" fullscreen=true :close-on-press-escape="closeModelOnEscape" class="bpa-dialog bpa-dialog--fullscreen bpa-dialog--customer-modal bpa--is-page-non-scrollable-mob" :append-to-body="true" :class="openAddNewAppointmentModel ? '--bpa-active' : ''" :show-close="false" @open="OpenAppointmentModel" @close="ResetAppointmentModel">
        <div class="bpa-dialog-heading">
            <bp-ui-row class="row-bg" justify="space-between" type="flex">
                <bp-ui-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
                    <h1 class="bpa-page-heading" v-if="appointment_formdata.appointment_update_id == '0'"><?php esc_html_e('Add Appointment', 'bookingpress-appointment-booking'); ?></h1>
                    <h1 class="bpa-page-heading" v-else><?php esc_html_e('Edit Appointment', 'bookingpress-appointment-booking'); ?></h1>
                </bp-ui-col>
                <bp-ui-col :xs="12" :sm="12" :md="7" :lg="7" :xl="7" class="bpa-dh__btn-group-col">
                    <bp-ui-button class="bpa-btn bpa-btn--primary" :class="(is_display_save_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="saveProAppointmentBooking('appointment_formdata')"  :disabled="is_disabled">                    
                    <span class="bpa-btn__label"><?php esc_html_e('Save', 'bookingpress-appointment-booking'); ?></span>
                    <div class="bpa-btn--loader__circles">                    
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    </bp-ui-button>
                    <bp-ui-button class="bpa-btn bpa-btn-secondary" @click="closeAppointmentBookingModal"><?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?></bp-ui-button>
                </bp-ui-col>
            </bp-ui-row>
        </div>
        <div class="bpa-dialog-body">
            <div class="bpa-form-row">
                <bp-ui-row type="flex">
                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                        <div class="bpa-db-sec-heading">
                            <bp-ui-row type="flex" align="middle">
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                    <div class="db-sec-left">
                                        <h2 class="bpa-page-heading"><?php esc_html_e( 'Basic Details', 'bookingpress-appointment-booking' ); ?></h2>
                                    </div>
                                </bp-ui-col>							
                            </bp-ui-row>
                        </div>
                        <div class="bpa-default-card bpa-db-card">
                            <bp-ui-form ref="appointment_formdata" require-asterisk-position="right" :rules="rules" :model="appointment_formdata" label-position="top" @submit.native.prevent>
                                <div class="bpa-form-body-row">
                                    <bp-ui-row :gutter="24" type="flex">
                                        <!-- Select Service block -->
                                        <bp-ui-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8"  v-if="!allow_multi_select_service" :class="(is_extras_enable == 1) ? 'bpa-select-appointment-service' : ''">   
                                            <bp-ui-form-item prop="appointment_selected_service">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Select Service', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <div class="bpa-aaf__service-selection-col">
                                                    <bp-ui-select class="bpa-form-control" v-model="appointment_formdata.appointment_selected_service" name="appointment_selected_service" filterable  placeholder="<?php esc_html_e('Select service', 'bookingpress-appointment-booking'); ?>" popper-class="bpa-bp-ui-select--is-with-modal" @Change="bookingpress_appointment_change_service()">
                                                        <bp-ui-option-group v-for="service_cat_data in appointment_services_list" :key="service_cat_data.category_name" :label="service_cat_data.category_name">
                                                            <template v-for="service_data in service_cat_data.category_services">
                                                                <bp-ui-option v-if="service_data.service_id == 0" :key="service_data.service_id" :label="service_data.service_name" :value="''"></bp-ui-option>
                                                                <bp-ui-option v-else :key="service_data.service_id" :label="service_data.service_name+' ('+service_data.service_price+' )' + ' - ' +service_data.service_duration_formatted" :value="service_data.service_id"></bp-ui-option>
                                                            </template>
                                                        </bp-ui-option-group>
                                                    </bp-ui-select>
                                                    <bp-ui-popover trigger="click" popper-class="bpa-aaf--extra-popover" v-model="bookingpress_extras_popover_modal" v-if="is_extras_enable == 1" :disabled="(appointment_formdata.appointment_selected_service == '' || ( 'undefined' != typeof bookingpress_loaded_extras[appointment_formdata.appointment_selected_service] && bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length == 0)) ? true : false" :teleported="true">
                                                        <div class="bpa-aaf--service-extras">
                                                            <h4><?php esc_html_e('Select Extras', 'bookingpress-appointment-booking'); ?></h4>
                                                            <div class="bpa-aaf__extras-body" v-if="appointment_formdata.appointment_selected_service != ''">
                                                                <div class="bpa-aaf-extra__item" v-for="(extras_details, index) in bookingpress_loaded_extras[appointment_formdata.appointment_selected_service]" v-if="'undefined' != typeof bookingpress_loaded_extras[appointment_formdata.appointment_selected_service] && bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length > 0">
                                                                    <div class="bpa-aaf-ei__header">
                                                                        <div class="bpa-aaf-ei__left">
                                                                            <div class="bpa-aaf-ei__left-checkbox">
                                                                                <bp-ui-checkbox class="bpa-form-control--checkbox" v-model="bookingpress_loaded_extras[appointment_formdata.appointment_selected_service][index]['bookingpress_is_selected']" label=""></bp-ui-checkbox>
                                                                            </div>
                                                                            <div class="bpa-aaf-ei__left-body">
                                                                                <h5 class="bpa-aaf-ei__heading">{{ extras_details.bookingpress_extra_service_name }}</h5>
                                                                                <div class="bpa-aaf-ei--options">
                                                                                    <p>{{ extras_details.bookingpress_extra_service_price_with_currency }}</p>
                                                                                    <p class="bpa-aaf-ei__duration"><span class="material-icons-round">schedule</span> {{ extras_details.bookingpress_extra_service_duration }}{{ extras_details.bookingpress_extra_service_duration_unit }}</p>
                                                                                </div>
                                                                                <div class="bpa-aaf-ei__description" v-if="extras_details.bookingpress_service_description != ''">
                                                                                    <bp-ui-link class="bpa-aaf-ei__btn" @click="bookingpress_toggle_extra_address(extras_details.bookingpress_extra_services_id, 1)" v-if="extras_details.bookingpress_is_display_description == '0'">
                                                                                        <?php esc_html_e('View more', 'bookingpress-appointment-booking'); ?> 
                                                                                        <span class="material-icons-round">add</span>
                                                                                    </bp-ui-link>
                                                                                    <bp-ui-link class="bpa-aaf-ei__btn" @click="bookingpress_toggle_extra_address(extras_details.bookingpress_extra_services_id, 0)" v-if="extras_details.bookingpress_is_display_description == '1'">
                                                                                        <?php esc_html_e('View less', 'bookingpress-appointment-booking'); ?> 
                                                                                        <span class="material-icons-round">remove</span>
                                                                                    </bp-ui-link>
                                                                                    <p v-if="extras_details.bookingpress_is_display_description == '1'">{{ extras_details.bookingpress_service_description }}</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="bpa-aaf-ei__right bpa-aaf-el__quantity-selector">
                                                                            <bp-ui-select class="bpa-form-control" :teleported="false" :append-to="bpa-aaf-el__quantity-selector" popper-class="bpa-aaf-ei-quantity-dropdown bpa-sum-ei-quantity-dropdown" @change="bookingpress_pro_service_extra_quantity_change" v-model="bookingpress_loaded_extras[appointment_formdata.appointment_selected_service][index]['bookingpress_selected_qty']">
                                                                                <bp-ui-option v-for="n in parseInt(extras_details.bookingpress_extra_service_max_quantity)" :key="n" :label="String(n)" :value="n">{{ n }}</bp-ui-option>
                                                                            </bp-ui-select>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                            <div class="bpa-aaf__extras-foot">
                                                                <bp-ui-button class="bpa-btn bpa-btn__small" @click="bookingpress_close_extras_modal"><?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?></bp-ui-button>
                                                                <bp-ui-button class="bpa-btn bpa-btn--primary bpa-btn__small" @click="bookingpress_add_extras"><?php esc_html_e('Add', 'bookingpress-appointment-booking'); ?></bp-ui-button>
                                                            </div>
                                                        </div>
                                                        <template #reference>
                                                            <bp-ui-button class="bpa-btn bpa-btn__medium" :class="(appointment_formdata.appointment_selected_service == '' || ('undefined' != typeof bookingpress_loaded_extras[appointment_formdata.appointment_selected_service] && bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length == 0)) ? '__bpa-is-disabled' : ''">
                                                                <?php esc_html_e('Add Extra', 'bookingpress-appointment-booking'); ?>
                                                                <span class="bpa-ep__counter" v-if="appointment_formdata.selected_extra_services_ids.length > 0">{{ appointment_formdata.selected_extra_services_ids.length }}</span>
                                                            </bp-ui-button>
                                                        </template>
                                                    </bp-ui-popover>
                                                </div>
                                                
                                                <div class="bpa-aaf__extras-preview" v-if="appointment_formdata.selected_extra_services_ids.length > 0">
													<h4><?php esc_html_e('Extras', 'bookingpress-appointment-booking'); ?></h4>
													<div class="bpa-aaf-ep__items">
														<div class="bpa-aaf-ep__item" v-for="selected_extras in filterSelectedServiceExtras">
															<p>{{ selected_extras.bookingpress_extra_service_name }}</p>
															<span class="material-icons-round" @click="bookingpress_remove_extras(selected_extras.bookingpress_extra_services_id)">close</span>
														</div>
													</div>
												</div>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <!-- Select Service Block -->

                                        <?php do_action('bookingpress_appointment_model_section_after_select_service'); ?>

                                        <!-- Staff Member Block -->
                                        <bp-ui-col v-if="true == allow_staff_member_selection && allow_multi_staffmember == '0' && is_staff_enable == 1" :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
											<bp-ui-form-item  prop="selected_staffmember">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?></span>
												</template>
												<bp-ui-select class="bpa-form-control" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?>" filterable v-model="appointment_formdata.selected_staffmember" @change="bookingpress_change_staff" :disabled="typeof appointment_formdata.is_allow_edit_past_appointment != 'undefined' && appointment_formdata.is_allow_edit_past_appointment === 1">
													<bp-ui-option value=""><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?></bp-ui-option>
													<bp-ui-option :label="staff_member_details.profile_details.bookingpress_staffmember_firstname != '' || staff_member_details.profile_details.bookingpress_staffmember_lastname != '' ? staff_member_details.profile_details.bookingpress_staffmember_firstname+' '+staff_member_details.profile_details.bookingpress_staffmember_lastname+' ( '+staff_member_details.staff_price_with_currency+' )' : staff_member_details.profile_details.bookingpress_staffmember_email+' ( '+staff_member_details.staff_price_with_currency+' )'" :value="staff_member_details.profile_details.bookingpress_staffmember_id" v-for="staff_member_details in filterLoadedStaffMembers">
													</bp-ui-option>
												</bp-ui-select>
											</bp-ui-form-item>
										</bp-ui-col>

                                        <?php do_action( 'bookingpress_appointment_model_after_staff_select_box', $bookingpress_singular_staffmember_name) ?>
                                        <!-- Staff Member Block -->                                        


                                        <!-- Select No. of Person Block -->
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="is_bring_anyone_with_you_enable == 1 && ( true === bpa_allow_multiple_quantity || 'true' == bpa_allow_multiple_quantity )">
											<bp-ui-form-item>
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'No. of Person', 'bookingpress-appointment-booking' ); ?></span>
												</template>
                                                <bp-ui-select class="bpa-form-control" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?>" 
                                                    v-model="appointment_formdata.selected_bring_members" @change="bookingpress_change_bring_anyone()" 
                                                    :disabled="typeof appointment_formdata.is_allow_edit_past_appointment != 'undefined' && appointment_formdata.is_allow_edit_past_appointment === 1">
                                                    <bp-ui-option 
                                                        v-for="num in bookingpress_get_bring_anyone_options(appointment_formdata.bookingpress_bring_anyone_min_capacity, appointment_formdata.bookingpress_bring_anyone_max_capacity, appointment_formdata.bookingpress_bring_anyone_step)" 
                                                        :key="num" 
                                                        :label="num + ' ' + (num == 1 ? '<?php esc_html_e('Person', 'bookingpress-appointment-booking'); ?>' : '<?php esc_html_e('Persons', 'bookingpress-appointment-booking'); ?>')" 
                                                        :value="num">
                                                    </bp-ui-option>
                                                </bp-ui-select>
											</bp-ui-form-item>
										</bp-ui-col>
                                        <!-- Select No. of Person Block -->

                                        <!-- After Number of Person Block through Action -->
                                        <?php do_action( 'bookingpress_add_appointment_before_customer_section' ); ?>
                                        <!-- After Number of Person Block through Action -->

                                        <bp-ui-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                                            <bp-ui-form-item prop="appointment_selected_customer">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Select Customer', 'bookingpress-appointment-booking'); ?></span>
                                                </template>                        
                                                <bp-ui-select class="bpa-form-control" name="appointment_selected_customer" v-model="appointment_formdata.appointment_selected_customer" filterable placeholder="<?php esc_html_e( 'Start typing to fetch customer', 'bookingpress-appointment-booking' ); ?>" remote reserve-keyword :remote-method="bookingpress_get_customer_list" @change="bpa_select_customer($event)" popper-class="bpa-el-select--is-with-modal" v-cancel-read-only>
                                                    <bp-ui-option v-if="bookingpress_edit_customers == 1" value="add_new" label="Add New">
                                                        <i class="el-icon-plus"></i> <span><?php esc_html_e( 'Add New', 'bookingpress-appointment-booking' ); ?></span>
                                                    </bp-ui-option>
                                                    <bp-ui-option v-if="loading_from_server" value="__loading__" :label="bookingpress_loading" disabled>
                                                        <span>{{ bookingpress_loading }}</span>
                                                    </bp-ui-option>
                                                    <bp-ui-option v-for="item in appointment_customers_list" :key="item.value" :label="item.text" :value="item.value">
                                                        <span>{{ item.text }}</span>
                                                    </bp-ui-option>

                                                    <template v-slot:empty> <span v-if="bookingpress_edit_customers == 1">
                                                        <?php esc_html_e( 'Type to search or choose Add New', 'bookingpress-appointment-booking' ); ?> </span>
                                                        <span v-else><?php esc_html_e( 'Start typing to fetch customer', 'bookingpress-appointment-booking' ); ?> </span>
                                                    </template>
                                                </bp-ui-select>
                                            </bp-ui-form-item>
                                        </bp-ui-col>

                                        <?php do_action('bookingpress_add_appointment_section_after_customer_section'); ?>

                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="'true' == bpa_allow_custom_duration || true === bpa_allow_custom_duration">
                                            <bp-ui-form-item>
                                                <template #label>
                                                    <span class="bpa-form-label"></span>
                                                </template>
                                                <label class="bpa-form-label bpa-custom-checkbox--is-label"> <bp-ui-checkbox v-model="appointment_formdata.appointment_custom_timing" @change="handleCustomTimingChange($event)" label=""></bp-ui-checkbox><?php esc_html_e('Allow Custom Duration', 'bookingpress-appointment-booking'); ?></label>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == true">
                                            <bp-ui-form-item prop="appointment_booked_date">
                                                <template #label>
													<span v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '0'" class="bpa-form-label"><?php esc_html_e('Appointment Start Date', 'bookingpress-appointment-booking'); ?></span>
													<span v-else class="bpa-form-label"><?php esc_html_e('Appointment Date', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
												<!-- <bp-ui-date-picker class="bpa-form-control bpa-form-control--date-picker" type="date" :format="bookingpress_date_common_date_format" :picker-options="filter_pickerOptions" v-model="appointment_formdata.appointment_booked_date" name="appointment_booked_date" popper-class="bpa-el-datepicker-widget-wrapper" type="date" :clearable="false" @change="select_appointment_booking_date($event)" value-format="yyyy-MM-dd"></bp-ui-date-picker> -->

                                                <bp-ui-date-picker class="bpa-form-control bpa-form-control--date-picker" :first-day-of-week="firstDayOfWeek" type="date" :format="bookingpress_date_common_date_format" v-model="appointment_formdata.appointment_booked_date" name="appointment_booked_date" :clearable="false" :disabled-date="bookingpressDisabledDate" popper-class="bpa-bp-ui-select--is-with-modal bpa-el-datepicker-widget-wrapper" locale="<?php echo get_locale(); ?>" @change="select_appointment_booking_date($event)" value-format="YYYY-MM-DD"></bp-ui-date-picker>
                                            </bp-ui-form-item>
                                        </bp-ui-col>										
										<bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '0'">
											<bp-ui-form-item prop="appointment_booked_end_date">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Appointment End Date', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
												<bp-ui-date-picker class="bpa-form-control bpa-form-control--date-picker" :first-day-of-week="firstDayOfWeek" type="date" :format="bookingpress_date_common_date_format" v-model="appointment_formdata.appointment_booked_end_date" name="appointment_booked_end_date" locale="<?php echo get_locale(); ?>" popper-class="bpa-el-datepicker-widget-wrapper" :clearable="false"  value-format="YYYY-MM-DD"></bp-ui-date-picker> <!--@change="select_appointment_booking_end_date($event)" -->
                                            </bp-ui-form-item>
										</bp-ui-col>										
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '1'">
                                            <bp-ui-form-item prop="appointment_booked_time">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Start Time', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-select v-model="appointment_formdata.appointment_booked_time" @change="change_custom_start_time($event)" class="bpa-form-control bpa-form-control__left-icon" filterable placeholder="<?php esc_html_e('Start Time', 'bookingpress-appointment-booking'); ?>">
                                                    <bp-ui-option v-for="appointment_times in filteredAppointmentTiming" :key="appointment_times.start_time_val" :label="appointment_times.start_time_formatted" :value="appointment_times.start_time_val"></bp-ui-option>
                                                </bp-ui-select>
                                            </bp-ui-form-item>
                                        </bp-ui-col>		
										<bp-ui-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '1'">
                                            <bp-ui-form-item prop="appointment_booked_end_time">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('End Time', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-select v-model="appointment_formdata.appointment_booked_end_time" @change="change_custom_end_time($event)" class="bpa-form-control bpa-form-control__left-icon" filterable placeholder="<?php esc_html_e('End Time', 'bookingpress-appointment-booking'); ?>">
                                                    <!-- <span slot="prefix" class="material-icons-round">access_time</span> -->
                                                    <bp-ui-option v-for="appointment_times in filteredAppointmentEndTiming" :key="appointment_times.end_time_val" :label="appointment_times.end_time_formatted" :value="appointment_times.end_time_val"></bp-ui-option>
                                                </bp-ui-select>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <bp-ui-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == false">
                                            <bp-ui-form-item prop="appointment_booked_date">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Appointment Date', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-date-picker class="bpa-form-control bpa-form-control--date-picker" :first-day-of-week="firstDayOfWeek" type="date" :format="bookingpress_date_common_date_format" v-model="appointment_formdata.appointment_booked_date" name="appointment_booked_date" :clearable="false" :disabled-date="bookingpressDisabledDate" popper-class="bpa-bp-ui-select--is-with-modal bpa-el-datepicker-widget-wrapper" locale="<?php echo get_locale(); ?>" @change="select_appointment_booking_date($event)" value-format="YYYY-MM-DD"></bp-ui-date-picker>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == false && is_timeslot_display == '1'">
                                            <bp-ui-form-item prop="appointment_booked_time">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Appointment Time', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-select class="bpa-form-control" placeholder="<?php esc_html_e( 'Select Time', 'bookingpress-appointment-booking' ); ?>" v-model="appointment_formdata.appointment_booked_time" @Change="bookingpress_set_time($event,appointment_time_slot)" :no-data-text="no_timeslots_available_text" filterable popper-class="bpa-bp-ui-select--is-with-modal">
                                                    <bp-ui-option-group v-for="appointment_time_slot_data in appointment_time_slot" :key="appointment_time_slot_data.timeslot_label" :label="appointment_time_slot_data.timeslot_label">
                                                        <bp-ui-option v-for="appointment_time in appointment_time_slot_data.timeslots" :label="(appointment_time.formatted_start_time)+' to '+(appointment_time.formatted_end_time)" :value="appointment_time.store_start_time" :disabled="( appointment_time.is_disabled || appointment_time.max_capacity == 0 || appointment_time.is_booked == 1 )">
                                                            <span>
                                                                {{ appointment_time.formatted_start_time  }} to {{appointment_time.formatted_end_time}}
                                                                <span v-if="appointment_time.is_next_day == 'true' || appointment_time.is_next_day == true">(<?php esc_html_e( 'Next Day', 'bookingpress-appointment-booking' ); ?>)</span>
                                                            </span>
                                                        </bp-ui-option>	
                                                    </bp-ui-option-group>
                                                </bp-ui-select>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                                            <bp-ui-form-item>
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-select class="bpa-form-control" v-model="appointment_formdata.appointment_status" popper-class="bpa-bp-ui-select--is-with-model" :options="BookingPressAppointmentStatus"></bp-ui-select>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                                            <bp-ui-form-item>
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Internal note', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.appointment_internal_note"></bp-ui-input>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                    </bpa-ui-row>
                                </div>
                                <div class="bpa-form-body-row">
                                    <bp-ui-row :gutter="24" type="flex">
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                            <bp-ui-form-item>
                                                <label class="bpa-form-label bpa-custom-checkbox--is-label"> <bp-ui-checkbox v-model="appointment_formdata.appointment_send_notification" label=""></bp-ui-checkbox> <?php esc_html_e('Do Not Send Notification', 'bookingpress-appointment-booking'); ?></label>
                                            </bp-ui-form-item>
                                        </bp-ui-col>
                                    </bpa-ui-row>
                                </div>
                            </bp-ui-form>
                        </div>
                    </bp-ui-col>
                </bp-ui-row>
            </div>

            <?php do_action( 'bookingpress_add_appointment_model_row_section'); ?>

            <div class="bpa-form-row" v-if="bookingpress_form_fields.length > 0">
                <bp-ui-row>
                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="bookingpress_form_fields.length > 0">
                        <div class="bpa-db-sec-heading">
                            <bp-ui-row type="flex" align="middle">
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                    <div class="db-sec-left">
                                        <h2 class="bpa-page-heading"><?php esc_html_e( 'Custom Fields', 'bookingpress-appointment-booking' ); ?></h2>
                                    </div>
                                </bp-ui-col>
                            </bp-ui-row>
                        </div>
                        <div class="bpa-default-card bpa-db-card">
                            <bp-ui-form ref="appointment_custom_formdata" :rules="custom_field_rules" :model="appointment_formdata.bookingpress_appointment_meta_fields_value" label-position="top" @submit.native.prevent>
                                <div class="bpa-form-body-row">
                                    <bp-ui-row :gutter="34">
                                        <bp-ui-col :xs="24" :sm="24" :md="24" :lg="08" :xl="08" v-for="form_fields in bookingpress_form_fields" :class="(form_fields.is_separator == true) ? '--bpa-is-field-separator' : ''">
                                            <div v-if="form_fields.is_separator == false">
                                                <div v-if="'undefined' != typeof form_fields.selected_services && form_fields.selected_services.length > 0">
                                                    <bp-ui-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone") && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></bp-ui-input>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "textarea" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3"></bp-ui-input>
                                                    </bp-ui-form-item>									
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'checkbox' && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
                                                            <bp-ui-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :value="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></bp-ui-checkbox>
                                                        </bp-ui-checkbox-group>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'radio' && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-radio class="bpa-form-label bpa-custom-radio--is-label" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :value="chk_data.value" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.label" :key="chk_data.value"  @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id,chk_data.value, form_fields)">{{chk_data.label}}</bp-ui-radio>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "dropdown" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
                                                            <bp-ui-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value" ></bp-ui-option>
                                                        </bp-ui-select>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "date" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" prefix-icon="" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" locale="<?php echo get_locale(); ?>" :placeholder="form_fields.bookingpress_field_placeholder" :type="'true' == form_fields.bookingpress_field_options.enable_timepicker ? 'datetime' : 'date'" <?php if($bookingpres_default_time_format == 'H:i') { ?>:value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd HH:mm' : 'yyyy-MM-dd'" <?php } else {?> :value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd hh:mm a' : 'yyyy-MM-dd'"  <?php }?>  :picker-options="filter_pickerOptions"></bp-ui-date-picker> <!-- @change="bookingpress_custom_field_date_change($event,form_fields.bookingpress_field_meta_key,form_fields.bookingpress_field_options.enable_timepicker)" -->
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "file" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key" >
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-upload :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
                                                            <label for="bpa-file-upload-two" class="bpa-form-control--file-upload">
                                                                <span class="bpa-fu__placeholder">{{form_fields.bookingpress_field_placeholder}}</span>
                                                                <span class="bpa-fu__btn">{{ form_fields.bookingpress_field_options.browse_button_label }}</span>
                                                            </label> 
                                                        </bp-ui-upload>
                                                    </bp-ui-form-item>								
                                                </div>
                                                <div v-else-if="'undefined' != typeof form_fields.selected_staff && form_fields.selected_staff.length > 0">
                                                    <bp-ui-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone") && is_field_visibility_on_staff(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></bp-ui-input>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "textarea" && is_field_visibility_on_staff(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3"></bp-ui-input>
                                                    </bp-ui-form-item>									
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'checkbox' && is_field_visibility_on_staff(form_fields)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
                                                            <bp-ui-checkbox :value="chk_data.value" class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></bp-ui-checkbox>
                                                        </bp-ui-checkbox-group>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'radio' && is_field_visibility_on_staff(form_fields)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-radio class="bpa-form-label bpa-custom-radio--is-label" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :value="chk_data.value" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.label" :key="chk_data.value"  @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id,chk_data.value, form_fields)">{{chk_data.label}}</bp-ui-radio>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "dropdown" && is_field_visibility_on_staff(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
                                                            <bp-ui-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value" ></bp-ui-option>
                                                        </bp-ui-select>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "date" && is_field_visibility_on_staff(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" prefix-icon="" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" locale="<?php echo get_locale(); ?>" :placeholder="form_fields.bookingpress_field_placeholder" :type="'true' == form_fields.bookingpress_field_options.enable_timepicker ? 'datetime' : 'date'" <?php if($bookingpres_default_time_format == 'H:i') { ?>:value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd HH:mm' : 'yyyy-MM-dd'" <?php } else {?> :value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd hh:mm a' : 'yyyy-MM-dd'"  <?php }?>  :picker-options="filter_pickerOptions"></bp-ui-date-picker> <!-- @change="bookingpress_custom_field_date_change($event,form_fields.bookingpress_field_meta_key,form_fields.bookingpress_field_options.enable_timepicker)" -->
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "file" && is_field_visibility_on_staff(form_fields)' :prop="form_fields.bookingpress_field_meta_key" >
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-upload :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
                                                            <label for="bpa-file-upload-two" class="bpa-form-control--file-upload">
                                                                <span class="bpa-fu__placeholder">{{form_fields.bookingpress_field_placeholder}}</span>
                                                                <span class="bpa-fu__btn">{{ form_fields.bookingpress_field_options.browse_button_label }}</span>
                                                            </label> 
                                                        </bp-ui-upload>
                                                    </bp-ui-form-item>								
                                                </div>
                                                <div v-else-if="form_fields.bookingpress_field_options && form_fields.bookingpress_field_options.visibility == 'on_field_value'">
                                                    <bp-ui-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone") && check_field_value_validation(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></bp-ui-input>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "textarea" && check_field_value_validation(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3"></bp-ui-input>
                                                    </bp-ui-form-item>									
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'checkbox' && check_field_value_validation(form_fields)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
                                                            <bp-ui-checkbox :value="chk_data.value" class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></bp-ui-checkbox>
                                                        </bp-ui-checkbox-group>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'radio' && check_field_value_validation(form_fields)" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-radio class="bpa-form-label bpa-custom-radio--is-label" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :value="chk_data.value" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.label" :key="chk_data.value"  @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id,chk_data.value, form_fields)">{{chk_data.label}}</bp-ui-radio>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "dropdown" && check_field_value_validation(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
                                                            <bp-ui-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value" ></bp-ui-option>
                                                        </bp-ui-select>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "date" && check_field_value_validation(form_fields)' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" prefix-icon="" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" locale="<?php echo get_locale(); ?>" :placeholder="form_fields.bookingpress_field_placeholder" :type="'true' == form_fields.bookingpress_field_options.enable_timepicker ? 'datetime' : 'date'" <?php if($bookingpres_default_time_format == 'H:i') { ?>:value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd HH:mm' : 'yyyy-MM-dd'" <?php } else {?> :value-format="form_fields.bookingpress_field_options.enable_timepicker ? 'yyyy-MM-dd hh:mm a' : 'yyyy-MM-dd'"  <?php }?>  :picker-options="filter_pickerOptions"></bp-ui-date-picker> <!-- @change="bookingpress_custom_field_date_change($event,form_fields.bookingpress_field_meta_key,form_fields.bookingpress_field_options.enable_timepicker)" -->
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "file" && check_field_value_validation(form_fields)' :prop="form_fields.bookingpress_field_meta_key" >
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-upload :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
                                                            <label for="bpa-file-upload-two" class="bpa-form-control--file-upload">
                                                                <span class="bpa-fu__placeholder">{{form_fields.bookingpress_field_placeholder}}</span>
                                                                <span class="bpa-fu__btn">{{ form_fields.bookingpress_field_options.browse_button_label }}</span>
                                                            </label> 
                                                        </bp-ui-upload>
                                                    </bp-ui-form-item>								
                                                </div>
												<?php do_action('bookingpress_backend_visibility_outside_calendar'); ?>
                                                <div v-else>
                                                    <bp-ui-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone")' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></bp-ui-input>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "textarea"' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-input class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]"></bp-ui-input>
                                                    </bp-ui-form-item>									
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'checkbox'" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
                                                            <bp-ui-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :value="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></bp-ui-checkbox>
                                                        </bp-ui-checkbox-group>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if="form_fields.bookingpress_field_type == 'radio'" :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-radio class="bpa-form-label bpa-custom-radio--is-label" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.label" :key="chk_data.value" :value="chk_data.value" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]"  @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id,chk_data.value, form_fields)">{{chk_data.label}}</bp-ui-radio>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "dropdown"' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
                                                            <bp-ui-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value"></bp-ui-option>
                                                        </bp-ui-select>
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "date"' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" prefix-icon="" locale="<?php echo get_locale(); ?>" :placeholder="form_fields.bookingpress_field_placeholder" :type="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? 'datetime' : 'date'" :value-format="form_fields.bookingpress_field_options.enable_timepicker == 'true' ? 'yyyy-MM-dd HH:mm' : 'yyyy-MM-dd'" :picker-options="filter_pickerOptions"></bp-ui-date-picker> 
                                                    </bp-ui-form-item>
                                                    <bp-ui-form-item v-if='form_fields.bookingpress_field_type == "file"' :prop="form_fields.bookingpress_field_meta_key">
                                                        <template #label>
                                                            <span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
                                                        </template>
                                                        <bp-ui-upload class="bpa-form-control" :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :on-exceed="BPACustomerhandleFileExceed" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
                                                            <label for="bpa-file-upload-two" class="bpa-form-control--file-upload" >
                                                                <span class="bpa-fu__placeholder">{{form_fields.bookingpress_field_placeholder}}</span>
                                                                <span class="bpa-fu__btn"> {{ form_fields.bookingpress_field_options.browse_button_label }}</span>
                                                            </label> 
                                                        </bp-ui-upload>
                                                    </bp-ui-form-item>
                                                </div>
                                            </div>
                                        </bp-ui-col>
                                    </bp-ui-row>
                                </div>                            
                            </bp-ui-form>	
                        </div>
                    </bp-ui-col>
                </bp-ui-row>
            </div>

            <div class="bpa-form-row" v-if="bookingpress_payments == 1">
                <bp-ui-row>
                    <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                        <div class="bpa-db-sec-heading">
                            <bp-ui-row type="flex" align="middle">
                                <bp-ui-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
                                    <div class="db-sec-left">
                                        <h2 class="bpa-page-heading"><?php esc_html_e( 'Payment Details', 'bookingpress-appointment-booking' ); ?></h2>
                                    </div>
                                </bp-ui-col>
                            </bp-ui-row>
                        </div>
                        <div class="bpa-default-card bpa-db-card">
                            <div class="bpa-aaf--payment-details">
                                <div class="bpa-aaf-pd__base-price-row">
                                    <div class="bpa-bpr__item">
                                        <h4>
                                            <?php esc_html_e('Subtotal', 'bookingpress-appointment-booking'); ?> 
                                            <span v-if="appointment_formdata.selected_bring_members > 1">(<?php esc_html_e('No. Of Person', 'bookingpress-appointment-booking'); ?> x {{ appointment_formdata.selected_bring_members}})</span>
                                        </h4>
                                        <h4 v-if="typeof appointment_formdata.subtotal_temp != 'undefined' && appointment_formdata.subtotal_temp != 0">{{ appointment_formdata.subtotal_temp_with_currency }}</h4>
                                        <h4 v-else>{{ appointment_formdata.subtotal_with_currency }}</h4>
                                    </div>
                                    <?php do_action('bookingpress_backend_add_appointment_after_sub_total'); ?>
                                    <div class="bpa-bpr__item" v-if="bookingpress_is_extra_enable == '1'">
                                        <h4><?php esc_html_e('Service Extras', 'bookingpress-appointment-booking'); ?></h4>
                                        <h4>{{ appointment_formdata.extras_total_with_currency }}</h4>
                                    </div>
                                    <div class="bpa-bpr__item" v-if="appointment_formdata.tax != '0' && (appointment_formdata.tax_price_display_options != 'include_taxes' || (appointment_formdata.tax_price_display_options == 'include_taxes' && (appointment_formdata.display_tax_order_summary == 'true' || appointment_formdata.display_tax_order_summary == '1')) )">
                                        <h4><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></h4>
                                        <h4>+{{ appointment_formdata.tax_with_currency }}</h4>
                                    </div>								
                                </div>
                                <?php do_action('bookingpress_backend_add_appointment_after_sub_total_backend'); ?>
                                <div class="bpa-aaf-pd__coupon-module" v-if="is_coupon_enable == 1 && bookingpress_allow_coupon_code == 1">
                                    <div class="bpa-aaf--bs__coupon-module-textbox" v-if="is_coupon_enable == '1' && coupon_applied_status != 'success'">
                                        <bp-ui-row>
                                            <bp-ui-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                                <span class="bpa-form-label"><?php esc_html_e( 'Have a coupon code?', 'bookingpress-appointment-booking' ); ?></span>
                                                <bp-ui-input class="bpa-form-control" v-model="appointment_formdata.applied_coupon_code" placeholder="<?php esc_html_e( 'Enter your coupon code', 'bookingpress-appointment-booking' ); ?>" :disabled="bpa_coupon_apply_disabled || bpa_multi_appoitnment_coupon_apply_disabled == 1"></bp-ui-input>
                                                <div class="bpa-bs__coupon-validation --is-error" v-if="coupon_applied_status == 'error' && coupon_code_msg != ''">
                                                    <span class="material-icons-round">error_outline</span>
                                                    <p>{{ coupon_code_msg }}</p>
                                                </div>
                                                <div class="bpa-bs__coupon-validation --is-success" v-if="coupon_applied_status == 'success' && coupon_code_msg != ''">
                                                    <span class="material-icons-round">check_circle</span>
                                                    <p>{{ coupon_code_msg }}</p>
                                                </div>
                                                <bp-ui-button class="bpa-btn bpa-btn__medium bpa-btn--primary" @click="bookingpress_apply_coupon_code" :disabled="bpa_coupon_apply_disabled || bpa_multi_appoitnment_coupon_apply_disabled == 1">
                                                    <span class="bpa-btn__label" v-if="bpa_coupon_apply_disabled == 0"><?php esc_html_e( 'Apply', 'bookingpress-appointment-booking' ); ?></span>
                                                    <span class="bpa-btn__label" v-else><?php esc_html_e( 'Applied', 'bookingpress-appointment-booking' ); ?></span>
                                                    <div class="bpa-btn--loader__circles">
                                                        <div></div>
                                                        <div></div>
                                                        <div></div>
                                                    </div>
                                                </bp-ui-button>
                                            </bp-ui-col>
                                        </bp-ui-row>
                                    </div>
                                    <div class="bpa-fm--bs-amount-item bpa-is-coupon-applied bpa-is-hide-stroke" v-if="is_coupon_enable == '1' && coupon_applied_status == 'success'">
                                        <bp-ui-row>
                                            <bp-ui-col :xs="20" :sm="20" :md="24" :lg="22" :xl="22">
                                                <h4>
                                                    <?php esc_html_e( 'Coupon Applied', 'bookingpress-appointment-booking' ); ?>
                                                    <span>{{ appointment_formdata.applied_coupon_code }}<a class="material-icons-round" @click="bookingpress_remove_coupon_code">close</a></span>		
                                                </h4>
                                            </bp-ui-col>
                                            <bp-ui-col :xs="04" :sm="04" :md="24" :lg="2" :xl="2">
                                                <h4 class="is-price">-{{ appointment_formdata.coupon_discounted_amount_with_currency }}</h4>
                                            </bp-ui-col>
                                        </bp-ui-row>
                                    </div>
                                </div>
                                <!-- for tip addon add do_action for fornt-end add appointment -->
                                <!-- < ?php do_action('bookingpress_add_content_after_subtotal_data_backend'); ?> -->
                                <?php do_action('bookingpress_add_content_after_subtotal_data_backend_v3'); ?>
                                
                                <div v-if="(appointment_formdata.bookingpress_applied_deposit == '0' || appointment_formdata.bookingpress_deposit_payment_method == 'allow_customer_to_pay_full_amount' || appointment_formdata.bookingpress_remove_deposit == 1) || (typeof appointment_formdata.bookingpress_package_applied_data != 'undefined' && appointment_formdata.bookingpress_package_applied_data != '') || (typeof appointment_formdata.bookingpress_gift_card_details != 'undefined' && appointment_formdata.bookingpress_gift_card_details != '')" class="bpa-aaf-pd__base-price-row bpa-aaf-pd__total-row">
                                    <div class="bpa-bpr__item">
                                        <h4><?php esc_html_e('Total', 'bookingpress-appointment-booking'); ?> <span v-if="appointment_formdata.tax_price_display_options == 'include_taxes'">{{ appointment_formdata.included_tax_label }}</span></h4>
                                        <h4 class="bpa-text--primary-color">{{ appointment_formdata.total_amount_with_currency }}</h4>
                                    </div>								
                                </div>
                                <div class="bpa-aaf-pd__mark-paid-checkbox" v-if="(appointment_formdata.appointment_update_id == '')">
                                    <div>
                                        <h4><?php esc_html_e('Once appointment booked', 'bookingpress-appointment-booking'); ?></h4>
                                        <bp-ui-radio v-model="appointment_formdata.complete_payment_url_selection" value="send_payment_link" @change="handleMarkAsPaid( $event )"><?php esc_html_e( 'Send Payment Link', 'bookingpress-appointment-booking' ); ?></bp-ui-radio>
                                        <bp-ui-radio v-model="appointment_formdata.complete_payment_url_selection" value="mark_as_paid" @change="handleMarkAsPaid( $event )"><?php esc_html_e( 'Mark as paid', 'bookingpress-appointment-booking' ); ?></bp-ui-radio>
                                        <bp-ui-radio v-model="appointment_formdata.complete_payment_url_selection" value="do_nothing" @change="handleMarkAsPaid( $event )"><?php esc_html_e( 'Do Nothing', 'bookingpress-appointment-booking' ); ?></bp-ui-radio>
                                        <bp-ui-radio v-model="appointment_formdata.complete_payment_url_selection" value="mark_as_pending" @change="handleMarkAsPaid( $event )"><?php esc_html_e( 'Mark as pending', 'bookingpress-appointment-booking' ); ?></bp-ui-radio>
                                    </div>
                                    <div class="bpa-aaf-pd__custom-link-itemns" v-if="appointment_formdata.complete_payment_url_selection == 'send_payment_link'">
                                        <bp-ui-checkbox-group v-model="appointment_formdata.complete_payment_url_selected_method">
                                            <bp-ui-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" label="email" value="email"><?php esc_html_e( 'Through Email', 'bookingpress-appointment-booking' ); ?></bp-ui-checkbox>
                                            <?php
                                                do_action('bookingpress_external_complete_payment_link_option');
                                            ?>
                                        </bp-ui-checkbox-group>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </bp-ui-col>
                </bp-ui-row>
            </div>	
        </div>
    </bp-ui-dialog>

    <?php
    $atad_dezirohtuanu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dezirohtuanU'  );
    extract( $atad_dezirohtuanu );
    $gnirts_atad_dezirohtuanu = strrev( "{$d}{$e}{$z}{$i}{$r}{$o}{$h}{$t}{$u}{$a}{$n}{$U}" );

    $atad_noitallatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'noitallatsnI' );
    extract( $atad_noitallatsni );
    $gnirts_atad_noitallatsni = strrev( "{$n}{$o}{$i}{$t}{$a}{$l}{$l}{$a}{$t}{$s}{$n}{$I}" );

    $atad_detceted = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'detceteD' );
    extract( $atad_detceted );
    $gnirts_atad_detceted = strrev( "{$d}{$e}{$t}{$c}{$e}{$t}{$e}{$D}" );

    $atad_siht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sihT' );
    extract( $atad_siht );
    $gnirts_atad_siht = strrev( "{$s}{$i}{$h}{$T}" );

    $atad_etisbew = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etisbew' );
    extract( $atad_etisbew );
    $gnirts_atad_etisbew = strrev( "{$e}{$t}{$i}{$s}{$b}{$e}{$w}" );

    $atad_si = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'si' );
    extract( $atad_si );
    $gnirts_atad_si = strrev( "{$s}{$i}" );

    $atad_gnitarepo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitarepo' );
    extract( $atad_gnitarepo );
    $gnirts_atad_gnitarepo = strrev( "{$g}{$n}{$i}{$t}{$a}{$r}{$e}{$p}{$o}" );

    $atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
    extract( $atad_sserpgnikoob );
    $gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

    $atad_tuohtiw = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tuohtiw' );
    extract( $atad_tuohtiw );
    $gnirts_atad_tuohtiw = strrev( "{$t}{$u}{$o}{$h}{$t}{$i}{$w}" );

    $atad_a = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'a' );
    extract( $atad_a );
    $gnirts_atad_a = strrev( "{$a}" );

    $atad_dilav = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dilav' );
    extract( $atad_dilav );
    $gnirts_atad_dilav = strrev( "{$d}{$i}{$l}{$a}{$v}" );

    $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
    extract( $atad_esnecil );
    $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

    $atad_etipsed = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etipsed' );
    extract( $atad_etipsed );
    $gnirts_atad_etipsed = strrev( "{$e}{$t}{$i}{$p}{$s}{$e}{$d}" );

    $atad_tnacifingis = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tnacifingis' );
    extract( $atad_tnacifingis );
    $gnirts_atad_tnacifingis = strrev( "{$t}{$n}{$a}{$c}{$i}{$f}{$i}{$n}{$g}{$i}{$s}" );

    $atad_laicremmoc = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laicremmoc' );
    extract( $atad_laicremmoc );
    $gnirts_atad_laicremmoc = strrev( "{$l}{$a}{$i}{$c}{$r}{$e}{$m}{$m}{$o}{$c}" );
    
    $atad_egasu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egasu' );
    extract( $atad_egasu );
    $gnirts_atad_egasu = strrev( "{$e}{$g}{$a}{$s}{$u}" );

    $atad_esaelp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esaelP' );
    extract( $atad_esaelp );
    $gnirts_atad_esaelp = strrev( "{$e}{$s}{$a}{$e}{$l}{$P}" );

    $atad_eton = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eton' );
    extract( $atad_eton );
    $gnirts_atad_eton = strrev( "{$e}{$t}{$o}{$n}" );

    $atad_taht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'taht' );
    extract( $atad_taht );
    $gnirts_atad_taht = strrev( "{$t}{$a}{$h}{$t}" );

    $atad_erutuf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'erutuf' );
    extract( $atad_erutuf );
    $gnirts_atad_erutuf = strrev( "{$e}{$r}{$u}{$t}{$u}{$f}" );

    $atad_setadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'setadpu' );
    extract( $atad_setadpu );
    $gnirts_atad_setadpu = strrev( "{$s}{$e}{$t}{$a}{$d}{$p}{$u}" );

    $atad_yam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yam' );
    extract( $atad_yam );
    $gnirts_atad_yam = strrev( "{$y}{$a}{$m}" );

    $atad_yllacitamotua = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yllacitamotua' );
    extract( $atad_yllacitamotua );
    $gnirts_atad_yllacitamotua = strrev( "{$y}{$l}{$l}{$a}{$c}{$i}{$t}{$a}{$m}{$o}{$t}{$u}{$a}" );

    $atad_elbasid = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'elbasid' );
    extract( $atad_elbasid );
    $gnirts_atad_elbasid = strrev( "{$e}{$l}{$b}{$a}{$s}{$i}{$d}" );

    $atad_muimerp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'muimerp' );
    extract( $atad_muimerp );
    $gnirts_atad_muimerp = strrev( "{$m}{$u}{$i}{$m}{$e}{$r}{$p}" );

    $atad_serutaef = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'serutaef' );
    extract( $atad_serutaef );
    $gnirts_atad_serutaef = strrev( "{$s}{$e}{$r}{$u}{$t}{$a}{$e}{$f}" );

    $atad_dna = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dna' );
    extract( $atad_dna );
    $gnirts_atad_dna = strrev( "{$d}{$n}{$a}" );

    $atad_evomer = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'evomer' );
    extract( $atad_evomer );
    $gnirts_atad_evomer = strrev( "{$e}{$v}{$o}{$m}{$e}{$r}" );

    $atad_derots = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'derots' );
    extract( $atad_derots );
    $gnirts_atad_derots = strrev( "{$d}{$e}{$r}{$o}{$t}{$s}" );

    $atad_gnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnikoob' );
    extract( $atad_gnikoob );
    $gnirts_atad_gnikoob = strrev( "{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

    $atad_detaler = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'detaler' );
    extract( $atad_detaler );
    $gnirts_atad_detaler = strrev( "{$d}{$e}{$t}{$a}{$l}{$e}{$r}" );

    $atad_atad = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'atad' );
    extract( $atad_atad );
    $gnirts_atad_atad = strrev( "{$a}{$t}{$a}{$d}" );

    $atad_morf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'morf' );
    extract( $atad_morf );
    $gnirts_atad_morf = strrev( "{$m}{$o}{$r}{$f}" );

    $atad_non = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'non' );
    extract( $atad_non );
    $gnirts_atad_non = strrev( "{$n}{$o}{$n}" );

    $atad_etamigitel = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etamigitel' );
    extract( $atad_etamigitel );
    $gnirts_atad_etamigitel = strrev( "{$e}{$t}{$a}{$m}{$i}{$g}{$i}{$t}{$e}{$l}" );

    $atad_snoitallatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitallatsni' );
    extract( $atad_snoitallatsni );
    $gnirts_atad_snoitallatsni = strrev( "{$s}{$n}{$o}{$i}{$t}{$a}{$l}{$l}{$a}{$t}{$s}{$n}{$i}" );

    $atad_retfa = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'retfa' );
    extract( $atad_retfa );
    $gnirts_atad_retfa = strrev( "{$r}{$e}{$t}{$f}{$a}" );

    $atad_ecarg = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ecarg' );
    extract( $atad_ecarg );
    $gnirts_atad_ecarg = strrev( "{$e}{$c}{$a}{$r}{$g}" );

    $atad_doirep = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'doirep' );
    extract( $atad_doirep );
    $gnirts_atad_doirep = strrev( "{$d}{$o}{$i}{$r}{$e}{$p}" );

    $atad_esahcrup = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esahcruP' );
    extract( $atad_esahcrup );
    $gnirts_atad_esahcrup = strrev( "{$e}{$s}{$a}{$h}{$c}{$r}{$u}{$P}" );

    $atad_na = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'na' );
    extract( $atad_na );
    $gnirts_atad_na = strrev( "{$n}{$a}" );

    $atad_laiciffo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laiciffo' );
    extract( $atad_laiciffo );
    $gnirts_atad_laiciffo = strrev( "{$l}{$a}{$i}{$c}{$i}{$f}{$f}{$o}" );

    $atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
    extract( $atad_sserpgnikoob );
    $gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

    $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
    extract( $atad_esnecil );
    $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

    $atad_ot = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ot' );
    extract( $atad_ot );
    $gnirts_atad_ot = strrev( "{$o}{$t}" );

    $atad_tneverp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tneverp' );
    extract( $atad_tneverp );
    $gnirts_atad_tneverp = strrev( "{$t}{$n}{$e}{$v}{$e}{$r}{$p}" );

    $atad_noitpursid = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'noitpursid' );
    extract( $atad_noitpursid );
    $gnirts_atad_noitpursid = strrev( "{$n}{$o}{$i}{$t}{$p}{$u}{$r}{$s}{$i}{$d}" );

    $atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
    extract( $atad_ruoy );
    $gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

    $atad_ssenisub = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ssenisub' );
    extract( $atad_ssenisub );
    $gnirts_atad_ssenisub = strrev( "{$s}{$s}{$e}{$n}{$i}{$s}{$u}{$b}" );

    $atad_snoitarepo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitarepo' );
    extract( $atad_snoitarepo );
    $gnirts_atad_snoitarepo = strrev( "{$s}{$n}{$o}{$i}{$t}{$a}{$r}{$e}{$p}{$o}" );
    ?>
    <bp-ui-dialog class="bpa-dialog bpa-dialog--rprt bpa-dialog--rprt-wrapper" :show-close="true" :modal="true" :append-to-body="true" v-model="rprt_model" :close-on-press-escape="close_modal_on_esc" v-cloak style="margin-top:15vh">
        <div class="bpa-dialog-body bpa-rprt-mdl-popup-wrapper">
            <div class="bpa-dialog-rprt-model-inner_wrapper">
                <svg width="134" height="134" viewBox="0 0 134 134" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M74.9557 25.6444L116.848 98.2087C120.388 104.34 115.963 112 108.884 112H25.0995C18.0206 112 13.5963 104.34 17.1357 98.2087L59.0281 25.6444C62.5676 19.5184 71.4162 19.5184 74.9557 25.6444Z" fill="#D63638"/>
                    <path d="M108.884 104.523H25.0996C24.2776 104.523 23.8377 104.057 23.6126 103.664C23.3875 103.272 23.2042 102.654 23.6126 101.942L65.505 29.3829C65.9134 28.6708 66.5417 28.5242 66.992 28.5242C67.4475 28.5242 68.0706 28.6708 68.479 29.3829L110.371 101.942C110.78 102.649 110.596 103.267 110.371 103.659C110.146 104.057 109.706 104.523 108.884 104.523ZM25.0734 102.387L25.0996 103.455V102.387C25.0891 102.387 25.0839 102.387 25.0734 102.387ZM25.8169 102.387H108.162L66.992 31.0793L25.8169 102.387Z" fill="white"/>
                    <path d="M70.5104 88.0196C70.5104 88.7002 70.4581 89.2762 70.3481 89.7422C70.2382 90.2082 70.0497 90.5799 69.7722 90.8627C69.4999 91.1454 69.1386 91.3496 68.6884 91.4753C68.2381 91.6009 67.6726 91.6638 66.9919 91.6638C66.3113 91.6638 65.7405 91.6009 65.285 91.4753C64.8295 91.3496 64.4682 91.1454 64.2012 90.8627C63.9342 90.5799 63.7457 90.2082 63.6409 89.7422C63.531 89.2762 63.4786 88.7055 63.4786 88.0196C63.4786 87.3232 63.531 86.7368 63.6409 86.2603C63.7509 85.7839 63.9342 85.4016 64.2012 85.1137C64.4682 84.8257 64.8295 84.6163 65.285 84.4906C65.7405 84.3649 66.3113 84.3021 66.9919 84.3021C67.6726 84.3021 68.2381 84.3649 68.6884 84.4906C69.1386 84.6163 69.4999 84.8205 69.7722 85.1137C70.0444 85.4069 70.2382 85.7891 70.3481 86.2603C70.4528 86.7316 70.5104 87.318 70.5104 88.0196ZM69.7355 80.7103C69.7198 80.8778 69.6675 81.0297 69.5837 81.1606C69.4999 81.2915 69.3533 81.4067 69.1491 81.4957C68.9397 81.5899 68.6569 81.658 68.3009 81.7103C67.9396 81.7627 67.505 81.7836 66.9919 81.7836C66.4579 81.7836 66.018 81.7575 65.6672 81.7103C65.3164 81.658 65.0389 81.5899 64.8295 81.4957C64.6201 81.4067 64.4787 81.2915 64.3949 81.1606C64.3111 81.0297 64.264 80.8778 64.2483 80.7103L63.5991 58.065C63.5991 57.8504 63.6514 57.6566 63.7509 57.4891C63.8504 57.3215 64.0284 57.1854 64.2902 57.0755C64.5468 56.9707 64.8976 56.887 65.3374 56.8398C65.7772 56.7875 66.3322 56.7665 66.9972 56.7665C67.6621 56.7665 68.2119 56.798 68.6412 56.8555C69.0758 56.9131 69.4161 56.9969 69.6779 57.1069C69.9345 57.2168 70.1178 57.3477 70.2277 57.5048C70.3377 57.6619 70.39 57.8504 70.39 58.065L69.7355 80.7103Z" fill="white"/>
                </svg>
                <div class="bpa-dialog-rprt-model-content-wrapper">
                    <h2><?php echo $gnirts_atad_dezirohtuanu .' '. $gnirts_atad_noitallatsni . ' ' . $gnirts_atad_detceted; ?></h2>
                    
                    <p><?php echo $gnirts_atad_siht. ' ' . $gnirts_atad_etisbew . ' ' . $gnirts_atad_si . ' ' . $gnirts_atad_gnitarepo . ' '. $gnirts_atad_sserpgnikoob . ' ' . $gnirts_atad_tuohtiw.' ' . $gnirts_atad_a.' ' .$gnirts_atad_dilav. ' ' . $gnirts_atad_esnecil. ' ' . $gnirts_atad_etipsed. ' ' . $gnirts_atad_tnacifingis . ' ' . $gnirts_atad_laicremmoc . ' ' . $gnirts_atad_egasu . '.'; ?></p>
                    
                    <p><?php echo $gnirts_atad_esaelp .' ' . $gnirts_atad_eton .' ' . $gnirts_atad_taht .' ' . $gnirts_atad_erutuf .' ' . $gnirts_atad_setadpu .' ' . $gnirts_atad_yam .' ' . $gnirts_atad_yllacitamotua .' ' . $gnirts_atad_elbasid .' ' . $gnirts_atad_muimerp .' ' . $gnirts_atad_serutaef .' ' . $gnirts_atad_dna .' ' . $gnirts_atad_evomer .' ' . $gnirts_atad_derots .' ' . $gnirts_atad_gnikoob .'-' . $gnirts_atad_detaler .' ' . $gnirts_atad_atad .' ' . $gnirts_atad_morf .' ' . $gnirts_atad_non .' ' . $gnirts_atad_etamigitel .' ' . $gnirts_atad_snoitallatsni .' ' . $gnirts_atad_retfa .' ' . $gnirts_atad_a .' ' . $gnirts_atad_ecarg .' ' . $gnirts_atad_doirep . '.'; ?></p>

                    <p><?php echo $gnirts_atad_esahcrup . ' ' . $gnirts_atad_na . ' ' . $gnirts_atad_laiciffo . ' ' . $gnirts_atad_sserpgnikoob . ' ' . $gnirts_atad_esnecil . ' ' . $gnirts_atad_ot . ' ' . $gnirts_atad_tneverp . ' ' . $gnirts_atad_noitpursid . ' ' . $gnirts_atad_ot . ' ' . $gnirts_atad_ruoy . ' ' . $gnirts_atad_ssenisub . ' ' . $gnirts_atad_snoitarepo ?></p>
                </div>
                <div class="bpa-dialog-rprt-model-content-btns">
                    <?php
                        $atad_yub = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yuB' );
                        extract( $atad_yub );
                        $gnirts_atad_yub = strrev( "{$y}{$u}{$B}" );

                        $atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
                        extract( $atad_ruoy );
                        $gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

                        $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
                        extract( $atad_esnecil );
                        $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );
                    ?>
                    <el-button class="bpa-btn bpa-btn--primary" @click="purchase_bookingpress"> 
                        <?php echo $gnirts_atad_yub . ' ' . $gnirts_atad_ruoy . ' ' . $gnirts_atad_esnecil; ?>
                    </el-button>
                    <?php
                        $atad_fi = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'fi' );
                        extract( $atad_fi );
                        $gnirts_atad_fi = strrev( "{$f}{$i}" );

                        $atad_uoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'uoy' );
                        extract( $atad_uoy );
                        $gnirts_atad_uoy = strrev( "{$u}{$o}{$y}" );

                        $atad_evah = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'evah' );
                        extract( $atad_evah );
                        $gnirts_atad_evah = strrev( "{$e}{$v}{$a}{$h}" );

                        $atad_na = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'na' );
                        extract( $atad_na );
                        $gnirts_atad_na = strrev( "{$n}{$a}" );

                        $atad_gnitsixe = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitsixe' );
                        extract( $atad_gnitsixe );
                        $gnirts_atad_gnitsixe = strrev( "{$g}{$n}{$i}{$t}{$s}{$i}{$x}{$e}" );

                        $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
                        extract( $atad_esnecil );
                        $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

                        $atad_yek = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yek' );
                        extract( $atad_yek );
                        $gnirts_atad_yek = strrev( "{$y}{$e}{$k}" );

                        $atad_etacol = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etacol' );
                        extract( $atad_etacol );
                        $gnirts_atad_etacol = strrev( "{$e}{$t}{$a}{$c}{$o}{$l}" );

                        $atad_ti = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ti' );
                        extract( $atad_ti );
                        $gnirts_atad_ti = strrev( "{$t}{$i}" );

                        $atad_no = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'no' );
                        extract( $atad_no );
                        $gnirts_atad_no = strrev( "{$n}{$o}" );

                        $atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
                        extract( $atad_ruoy );
                        $gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

                        $atad_dna = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dna' );
                        extract( $atad_dna );
                        $gnirts_atad_dna = strrev( "{$d}{$n}{$a}" );

                        $atad_retne = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'retne' );
                        extract( $atad_retne );
                        $gnirts_atad_retne = strrev( "{$r}{$e}{$t}{$n}{$e}" );

                        $atad_woleb = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'woleb' );
                        extract( $atad_woleb );
                        $gnirts_atad_woleb = strrev( "{$w}{$o}{$l}{$e}{$b}" );

                        $atad_tnuocca = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tnuoccA' );
                        extract( $atad_tnuocca );
                        $gnirts_atad_tnuocca = strrev( "{$t}{$n}{$u}{$o}{$c}{$c}{$A}" );

                        $atad_egap = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egaP' );
                        extract( $atad_egap );
                        $gnirts_atad_egap = strrev( "{$e}{$g}{$a}{$P}" );

                        $atad_sptth = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sptth' );
                        extract( $atad_sptth );
                        $gnirts_atad_sptth = strrev( "{$s}{$p}{$t}{$t}{$h}" );

                        $atad_www = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'www' );
                        extract( $atad_www );
                        $gnirts_atad_www = strrev( "{$w}{$w}{$w}" );

                        $atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserpgnikoob' );
                        extract( $atad_sserpgnikoob );
                        $gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$p}{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

                        $atad_nigulp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'nigulp' );
                        extract( $atad_nigulp );
                        $gnirts_atad_nigulp = strrev( "{$n}{$i}{$g}{$u}{$l}{$p}" );

                        $atad_moc = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'moc' );
                        extract( $atad_moc );
                        $gnirts_atad_moc = strrev( "{$m}{$o}{$c}" );

                        $atad_eganam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eganam' );
                        extract( $atad_eganam );
                        $gnirts_atad_eganam = strrev( "{$e}{$g}{$a}{$n}{$a}{$m}" );

                        $atad_sesnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sesnecil' );
                        extract( $atad_sesnecil );
                        $gnirts_atad_sesnecil = strrev( "{$s}{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );
                    ?>
                    <p><?php echo $gnirts_atad_fi . ' ' . $gnirts_atad_uoy . ' ' . $gnirts_atad_evah . ' ' . $gnirts_atad_na . ' ' . $gnirts_atad_gnitsixe . ' ' . $gnirts_atad_esnecil . ' ' . $gnirts_atad_yek . ' ' . $gnirts_atad_etacol . ' ' . $gnirts_atad_ti . ' ' . $gnirts_atad_no . ' ' . $gnirts_atad_ruoy . ' ' . sprintf( '%1$s', "<a href='{$gnirts_atad_sptth}://{$gnirts_atad_www}.{$gnirts_atad_sserpgnikoob}{$gnirts_atad_nigulp}.{$gnirts_atad_moc}/{$gnirts_atad_eganam}-{$gnirts_atad_sesnecil}' target='_blank'>". $gnirts_atad_tnuocca. ' ' . $gnirts_atad_egap . '</a>' ) .' ' . $gnirts_atad_dna . ' ' . $gnirts_atad_retne . ' ' . $gnirts_atad_woleb; ?></p>
                    <div class="bpa-rpt-license-input-wrapper">
                        <bp-ui-form ref="rprt_license_form" class="bpa-rprt-license-form">
                            <bp-ui-form-item prop="rprt_license_key">
                                <?php
                                    $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esneciL' );
                                    extract( $atad_esnecil );
                                    $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$L}" );

                                    $atad_yek = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yeK' );
                                    extract( $atad_yek );
                                    $gnirts_atad_yek = strrev( "{$y}{$e}{$K}" );

                                    $atad_yfirev = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yfireV' );
                                    extract( $atad_yfirev );
                                    $gnirts_atad_yfirev = strrev( "{$y}{$f}{$i}{$r}{$e}{$V}" );
                                ?>
                                <bp-ui-input placeholder="<?php echo $gnirts_atad_esnecil . ' ' . $gnirts_atad_yek; ?>" v-model="rprt_license_key" class="bpa-rprt-license-input"></bp-ui-input>&nbsp;
                                <bp-ui-button class="bpa-btn bpa-btn--primary bpa-rprt-license-verify-btn" :class="{'bpa-btn--is-loader': rprt_verify_btn_loading}" @click="bookingpress_verify_license_key_func('rprt_license_form')">
                                    <span class="bpa-btn__label"><?php echo $gnirts_atad_yfirev . ' ' . $gnirts_atad_yek; ?></span>
                                    <div class="bpa-btn--loader__circles">				    
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>
                                </bp-ui-button>
                            </bp-ui-form-item>
                        </bp-ui-form>
                    </div>
                </div>
                <?php
                    $atad_ecno = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ecnO' );
                    extract( $atad_ecno );
                    $gnirts_atad_ecno = strrev( "{$e}{$c}{$n}{$O}" );

                    $atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
                    extract( $atad_ruoy );
                    $gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

                    $atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
                    extract( $atad_esnecil );
                    $gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

                    $atad_si = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'si' );
                    extract( $atad_si );
                    $gnirts_atad_si = strrev( "{$s}{$i}" );

                    $atad_deifirev = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'deifirev' );
                    extract( $atad_deifirev );
                    $gnirts_atad_deifirev = strrev( "{$d}{$e}{$i}{$f}{$i}{$r}{$e}{$v}" );

                    $atad_uoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'uoy' );
                    extract( $atad_uoy );
                    $gnirts_atad_uoy = strrev( "{$u}{$o}{$y}" );

                    $atad_tsum = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tsum' );
                    extract( $atad_tsum );
                    $gnirts_atad_tsum = strrev( "{$t}{$s}{$u}{$m}" );

                    $atad_etadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etadpu' );
                    extract( $atad_etadpu );
                    $gnirts_atad_etadpu = strrev( "{$e}{$t}{$a}{$d}{$p}{$u}" );

                    $atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
                    extract( $atad_sserpgnikoob );
                    $gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

                    $atad_htiw = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'htiw' );
                    extract( $atad_htiw );
                    $gnirts_atad_htiw = strrev( "{$h}{$t}{$i}{$w}" );

                    $atad_eht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eht' );
                    extract( $atad_eht );
                    $gnirts_atad_eht = strrev( "{$e}{$h}{$t}" );

                    $atad_laiciffo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laiciffo' );
                    extract( $atad_laiciffo );
                    $gnirts_atad_laiciffo = strrev( "{$l}{$a}{$i}{$c}{$i}{$f}{$f}{$o}" );

                    $atad_desnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'desnecil' );
                    extract( $atad_desnecil );
                    $gnirts_atad_desnecil = strrev( "{$d}{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

                    $atad_egakcap = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egakcap' );
                    extract( $atad_egakcap );
                    $gnirts_atad_egakcap = strrev( "{$e}{$g}{$a}{$k}{$c}{$a}{$p}" );

                    $atad_ot = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ot' );
                    extract( $atad_ot );
                    $gnirts_atad_ot = strrev( "{$o}{$t}" );

                    $atad_diova = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'diova' );
                    extract( $atad_diova );
                    $gnirts_atad_diova = strrev( "{$d}{$i}{$o}{$v}{$a}" );

                    $atad_erutuf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'erutuf' );
                    extract( $atad_erutuf );
                    $gnirts_atad_erutuf = strrev( "{$e}{$r}{$u}{$t}{$u}{$f}" );

                    $atad_snoitcirtser = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitcirtser' );
                    extract( $atad_snoitcirtser );
                    $gnirts_atad_snoitcirtser = strrev( "{$s}{$n}{$o}{$i}{$t}{$c}{$i}{$r}{$t}{$s}{$e}{$r}" );

                    $atad_ro = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ro' );
                    extract( $atad_ro );
                    $gnirts_atad_ro = strrev( "{$r}{$o}" );

                    $atad_elbissop = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'elbissop' );
                    extract( $atad_elbissop );
                    $gnirts_atad_elbissop = strrev( "{$e}{$l}{$b}{$i}{$s}{$s}{$o}{$p}" );

                    $atad_gnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnikoob' );
                    extract( $atad_gnikoob );
                    $gnirts_atad_gnikoob = strrev( "{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

                    $atad_atad = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'atad' );
                    extract( $atad_atad );
                    $gnirts_atad_atad = strrev( "{$a}{$t}{$a}{$d}" );

                    $atad_seussi = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'seussi' );
                    extract( $atad_seussi );
                    $gnirts_atad_seussi = strrev( "{$s}{$e}{$u}{$s}{$s}{$i}" );

                    $atad_wollof = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'wolloF' );
                    extract( $atad_wollof );
                    $gnirts_atad_wollof = strrev( "{$w}{$o}{$l}{$l}{$o}{$F}" );

                    $atad_siht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'siht' );
                    extract( $atad_siht );
                    $gnirts_atad_siht = strrev( "{$s}{$i}{$h}{$t}" );

                    $atad_ediug = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ediug' );
                    extract( $atad_ediug );
                    $gnirts_atad_ediug = strrev( "{$e}{$d}{$i}{$u}{$g}" );

                    $atad_yllaunam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yllaunam' );
                    extract( $atad_yllaunam );
                    $gnirts_atad_yllaunam = strrev( "{$y}{$l}{$l}{$a}{$u}{$n}{$a}{$m}" );

                    $atad_nigulp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'nigulp' );
                    extract( $atad_nigulp );
                    $gnirts_atad_nigulp = strrev( "{$n}{$i}{$g}{$u}{$l}{$p}" );

                    $atad_stnemucod = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'stnemucod' );
                    extract( $atad_stnemucod );
                    $gnirts_atad_stnemucod = strrev( "{$s}{$t}{$n}{$e}{$m}{$u}{$c}{$o}{$d}" );

                    $atad_gnillatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnillatsni' );
                    extract( $atad_gnillatsni );
                    $gnirts_atad_gnillatsni = strrev( "{$g}{$n}{$i}{$l}{$l}{$a}{$t}{$s}{$n}{$i}" );

                    $atad_gnitadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitadpu' );
                    extract( $atad_gnitadpu );
                    $gnirts_atad_gnitadpu = strrev( "{$g}{$n}{$i}{$t}{$a}{$d}{$p}{$u}" );

                    $atad_launam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'launam' );
                    extract( $atad_launam );
                    $gnirts_atad_launam = strrev( "{$l}{$a}{$u}{$n}{$a}{$m}" );

                    $atad_etadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etadpu' );
                    extract( $atad_etadpu );
                    $gnirts_atad_etadpu = strrev( "{$e}{$t}{$a}{$d}{$p}{$u}" );

                    $v2_atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserpgnikoob' );
                    extract( $v2_atad_sserpgnikoob );
                    $v2_gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$p}{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );
                ?>
                <div class="bpa-dialog-rprt-model-footer">
                    <p>
                        <?php
                            echo $gnirts_atad_ecno. ' ' . $gnirts_atad_ruoy. ' ' . $gnirts_atad_esnecil. ' ' . $gnirts_atad_si. ' ' . $gnirts_atad_deifirev. ' ' . $gnirts_atad_uoy. ' ' . $gnirts_atad_tsum. ' ' . $gnirts_atad_etadpu. ' ' . $gnirts_atad_sserpgnikoob. ' ' . $gnirts_atad_htiw. ' ' . $gnirts_atad_eht. ' ' . $gnirts_atad_laiciffo. ' ' . $gnirts_atad_desnecil. ' ' . $gnirts_atad_egakcap. ' ' . $gnirts_atad_ot. ' ' . $gnirts_atad_diova. ' ' . $gnirts_atad_erutuf. ' ' . $gnirts_atad_snoitcirtser. ' ' . $gnirts_atad_ro. ' ' . $gnirts_atad_elbissop. ' ' . $gnirts_atad_gnikoob. ' ' . $gnirts_atad_atad. ' ' . $gnirts_atad_seussi. '. ' . $gnirts_atad_wollof. ' ' . sprintf( '%1$s', "<a href='{$gnirts_atad_sptth}://{$gnirts_atad_www}.{$v2_gnirts_atad_sserpgnikoob}{$gnirts_atad_nigulp}.{$gnirts_atad_moc}/{$gnirts_atad_stnemucod}/{$gnirts_atad_gnillatsni}-{$gnirts_atad_gnitadpu}-{$v2_gnirts_atad_sserpgnikoob}/#{$v2_gnirts_atad_sserpgnikoob}-{$gnirts_atad_launam}-{$gnirts_atad_etadpu}' target='_blank'>". $gnirts_atad_siht. ' ' . $gnirts_atad_ediug. '</a>' ) . ' ' . $gnirts_atad_ot. ' ' . $gnirts_atad_yllaunam . ' ' . $gnirts_atad_etadpu .' ' . $gnirts_atad_eht . ' '. $gnirts_atad_nigulp.'.';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </bp-ui-dialog>
</div>
<style>
.bpa-aaf--extra-popover .bp-select__selected-item.is-hidden,
.bpa-dialog-body .bp-select__selected-item.is-hidden {
    position: static !important;
    opacity: 1 !important;
    z-index: 1 !important;
    display: block !important;
    visibility: visible !important;
}
</style>