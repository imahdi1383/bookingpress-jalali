<?php
	global $BookingPress, $bookingpress_common_date_format, $BookingPressPro, $bookingpress_global_options, $bookingpress_pro_staff_members;	
	$bookingpress_common_datetime_format = $bookingpress_common_date_format . ' HH:mm';
	$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
	$bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
	$bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

	$staff_commission_module_active = $bookingpress_pro_staff_members->bookingpress_check_staffmember_commission_module_activation();
	  
?>

<el-main class="bpa-main-listing-card-container bpa-dashboard-container bpa--is-page-non-scrollable-mob" :class="(bookingpress_staff_customize_view == 1 ) ? 'bpa-main-list-card__is-staff-custom-view':''" id="all-page-main-container">    	
    <div class="bpa-default-card bpa-dashboard--summary">
        <div class="bpa-back-loader-container" id="bpa-page-loading-loader">
            <div class="bpa-back-loader"></div>
        </div>
        <el-row type="flex" class="bpa-mlc-head-wrap">
            <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="bpa-mlc-left-heading">
                <h1 class="bpa-page-heading"><?php esc_html_e('Dashboard', 'bookingpress-appointment-booking'); ?></h1>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
                <div class="bpa-hw-right-btn-group">
                    <el-date-picker ref="bookingpress_custom_filter_rangepicker" @focus="bookingpress_remove_date_range_picker_focus" v-model="custom_filter_val" class="bpa-form-control bpa-form-control--date-range-picker" :format="bpa_date_common_date_format" type="daterange" start-placeholder="<?php esc_html_e('Start date', 'bookingpress-appointment-booking'); ?>" end-placeholder="<?php esc_html_e( 'End Date', 'bookingpress-appointment-booking'); ?>" :popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar bpa-date-range-picker__is-filter-enabled bpa-date-range-picker-widget-wrapper" range-separator="-" :picker-options="bookingpress_picker_options" @change="select_dashboard_custom_date_filter($event)" value-format="yyyy-MM-dd" :clearable="false" ></el-date-picker>
                </div>
            </el-col>
        </el-row>
        <div id="bpa-main-container">
            <div class="bpa-dashboard--summary-body" :class="is_staffmember_activated == 1 ? 'bpa-dashboard--sb__staff-module-enabled' :''">
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
            </div>
            <div class="bpa-dashboard--technical-analysis">
                <el-row type="flex" class="bpa-mlc-head-wrap">
                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="bpa-mlc-left-heading bpa-mlc-left-heading--is-visible-help">
                        <h1 class="bpa-page-heading"><?php esc_html_e('Technical Analysis', 'bookingpress-appointment-booking'); ?></h1>
                    </el-col>
                </el-row>
                <div class="bpa-dashboard--technical-analysis-body">
                    <el-row :gutter="24">
                        <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                            <canvas id="appointments_charts"></canvas>
                        </el-col>
                        <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                            <canvas id="revenue_charts"></canvas>
                        </el-col>
                        <el-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                            <canvas id="customer_charts"></canvas>
                        </el-col>
                    </el-row>
                </div>
            </div>
            <el-row class="bpa-dashboard--upcoming-appointments">
                <el-row type="flex" class="bpa-mlc-head-wrap">
                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                        <h1 class="bpa-page-heading"><?php esc_html_e('Upcoming Appointments', 'bookingpress-appointment-booking'); ?></h1>
                    </el-col>
                </el-row>            
                <el-row type="flex" v-if="items.length == 0">
                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                        <div class="bpa-data-empty-view">
                            <div class="bpa-ev-left-vector">
                                <picture>
                                    <source srcset="<?php echo esc_url(BOOKINGPRESS_IMAGES_URL . '/data-grid-empty-view-vector.webp'); ?>" type="image/webp">
                                    <img src="<?php echo esc_url(BOOKINGPRESS_IMAGES_URL . '/data-grid-empty-view-vector.png'); ?>">
                                </picture>
                            </div>
                            <div class="bpa-ev-right-content">                    
                                <h4><?php esc_html_e('No Record Found!', 'bookingpress-appointment-booking'); ?></h4>
                            </div>
                        </div>
                    </el-col>
                </el-row>
                <el-row v-else>
                    <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                        <el-container class="bpa-table-container">
							<div class="bpa-tc__wrapper" v-if="current_screen_size == 'desktop'">
								<el-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
									<el-table-column type="expand">
										<template slot-scope="scope">
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
															do_action('bookingpress_backend_appointment_list_after_appointment_price','desktop');
														?>														
													</div>
													<div class="bpa-hw-right-btn-group bpa-vac--head__right">
														<?php 
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
														?>	
															<el-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="(( bpa_chk_staff_role != 1 && scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3') || ( bpa_chk_staff_role == 1 && bpa_staff_refund_cap == 1 && scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3' ) )">
																<span class="material-icons-round">close</span>
																<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
															</el-button>
															<el-popconfirm 
																cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
																confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
																icon="false" 
																title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
																@confirm="bookingpress_change_status(scope.row.appointment_id, '3')" 
																confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
																cancel-button-type="bpa-btn bpa-btn__small"
																v-else-if="scope.row.appointment_status != '3'">
																<el-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
																	<span class="material-icons-round">close</span>
																	<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
																</el-button>
															</el-popconfirm>&nbsp;
														<?php } ?>
														<?php
															do_action('bookingpress_add_dynamic_buttons_for_view_appointments');
														?>
													</div>
												</div>
												<div class="bpa-vac--body">
													<el-row :gutter="56">
														<el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
															<div class="bpa-vac-body--appointment-details">
																<el-row :gutter="40">
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																			<?php do_action('add_bookingpress_appointment_details_outside'); ?>
																		</div>
																	</el-col>
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																	</el-col>
																</el-row>
															</div>
															<div class="bpa-vac-body--service-extras" v-if="scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
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
															<div class="bpa-vac-body--custom-fields" v-if="scope.row.custom_fields_values.length > 0">
																<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
																<div class="bpa-cf__body">
																	<el-row>
																		<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
																			<div class="bpa-bd__item">
																				<div class="bpa-bd__item-head">
																					<span v-html="custom_fields.label"></span>
																				</div>
																				<div class="bpa-bd__item-body">
																					<h4 v-html="custom_fields.value"></h4>
																				</div>
																			</div>																
																		</el-col>
																	</el-row>
																</div>
															</div>
															<?php do_action('bookingpress_backend_display_guest_data'); ?>
														</el-col>
														<?php
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
														?>
														<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
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
																	<?php do_action('bookingpress_modify_payment_appointment_section') ?>
																	<div class="bpa-pd__item bpa-pd-total__item">
																		<span>
																			<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
																			<div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
																		</span>
																		<p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
																	</div>
																</div>									
															</div>
														</el-col>
														<?php } ?>
													</el-row>										
												</div>
												<?php do_action("bookingpress_manage_appointment_expnd_additional_content_add"); ?>
											</div>
										</template>
									</el-table-column>
									<el-table-column prop="booking_id" min-width="30" label="<?php esc_html_e( 'ID', 'bookingpress-appointment-booking' ); ?>">
										<template slot-scope="scope">
											<span>#{{ scope.row.booking_id }}</span>
										</template>
									</el-table-column>
									<?php do_action('bookingpress_add_column_outsite'); ?>
									<el-table-column prop="appointment_date" min-width="120" label="<?php esc_html_e( 'Date', 'bookingpress-appointment-booking' ); ?>" sortable>
										<template slot-scope="scope">
											<label class="bpa-item__date-col">{{ scope.row.appointment_date }}</label>
											<el-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
												<span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
											</el-tooltip>
										</template>
									</el-table-column>
									<el-table-column prop="customer_name" min-width="90" label="<?php esc_html_e( 'Customer', 'bookingpress-appointment-booking' ); ?>" sortable>
										<template slot-scope="scope">
											<span v-if="scope.row.customer_name != ''">{{ scope.row.customer_name }}</span>
											<span v-else>{{ scope.row.customer_first_name }} {{ scope.row.customer_last_name }}</span>
										</template>
									</el-table-column>
									<?php if ( ! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
										<el-table-column prop="staff_member_name" min-width="90" label="<?php echo esc_html($bookingpress_singular_staffmember_name); ?>" sortable v-if="(is_staffmember_activated == 1 && typeof is_multi_staffmember_activated == 'undefined')"></el-table-column>
										<?php do_action('bookingpress_after_staff_members_column', $bookingpress_singular_staffmember_name); ?>
									<?php } ?>
									<el-table-column prop="service_name" min-width="110" label="<?php esc_html_e( 'Service', 'bookingpress-appointment-booking' ); ?>" sortable>
										<?php do_action('bookingpress_multiple_service_name_dashboard_section_fetch'); ?>
									</el-table-column>
									<el-table-column prop="appointment_duration" min-width="70" label="<?php esc_html_e( 'Duration', 'bookingpress-appointment-booking' ); ?>" sortable sort-by="bookingpress_service_duration_sortable"></el-table-column>
									<el-table-column prop="appointment_status" min-width="80" label="<?php esc_html_e( 'Status', 'bookingpress-appointment-booking' ); ?>">
										<template slot-scope="scope">
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
													<el-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event)" popper-class="bpa-appointment-status-dropdown-popper">
														<el-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
															<el-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
														</el-option-group>
													</el-select>
												</div>
												
												<?php
											} else {
												?>
											<el-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</el-tag>
												<?php
											}?>
										</template>
									</el-table-column>
									<el-table-column prop="appointment_payment" min-width="100" label="<?php esc_html_e( 'Payment', 'bookingpress-appointment-booking' ); ?>" sortable sort-by="payment_numberic_amount">
										<template slot-scope="scope">
											<div class="bpa-apc__amount-row">
												<div class="bpa-apc__ar-body">
													<span class="bpa-apc__amount" v-if="scope.row.bookingpress_is_deposit_enable == 1 && scope.row.bookingpress_payment_status == '4'">{{scope.row.appointment_payment}}</span>											
													<span v-else class="bpa-apc__amount">{{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
													<span v-if="scope.row.bookingpress_is_deposit_enable == 1 && scope.row.bookingpress_payment_status == '4'" class="bpa-is-deposit-payment-val"><?php esc_html_e('of', 'bookingpress-appointment-booking'); ?> {{ scope.row.bookingpress_final_total_amt_with_currency }}</span>
												</div>
												<div class="bpa-apc__ar-icons">
													<?php do_action('bookingpress_backend_appointment_list_type_icons'); ?>
													<el-tooltip content="<?php esc_html_e('Cart Transaction', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_cart == 1">
														<span class="material-icons-round bpa-appointment-cart-icon" v-if="scope.row.bookingpress_is_cart == 1">shopping_cart</span>
													</el-tooltip>
													<el-tooltip content="<?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_deposit_enable == 1">
														<span class="bpa-apc__deposit-icon" v-if="scope.row.bookingpress_is_deposit_enable == 1">
															<svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M16.9596 12.2237C16.8902 12.0273 16.746 11.8662 16.5583 11.7756C16.3706 11.685 16.1548 11.6723 15.9578 11.7402L13.7872 12.4116C13.2376 12.9125 13.0288 12.7838 9.00068 12.7838C8.90842 12.7838 8.81994 12.7471 8.75471 12.6819C8.68947 12.6167 8.65282 12.5282 8.65282 12.4359C8.65282 12.3437 8.68947 12.2552 8.75471 12.1899C8.81994 12.1247 8.90842 12.0881 9.00068 12.0881C13.1749 12.0881 13.0323 12.1681 13.3384 11.862C13.4551 11.7331 13.5206 11.5661 13.5228 11.3923C13.5228 11.2078 13.4495 11.0309 13.319 10.9004C13.1886 10.7699 13.0116 10.6966 12.8271 10.6966H9.10504C8.62152 10.6966 8.21801 10.0009 6.80919 10.0009H4.47856V13.6256L4.92729 13.8795C6.20362 14.6153 7.63128 15.0496 9.10115 15.149C10.571 15.2485 12.0442 15.0106 13.408 14.4535L16.5387 13.1908C16.7162 13.1104 16.8576 12.967 16.9354 12.7883C17.0132 12.6096 17.0218 12.4084 16.9596 12.2237ZM1 14.523H3.78285V9.30521H1V14.523ZM2.0714 12.9994C2.09103 12.9518 2.12099 12.9092 2.1591 12.8746C2.19722 12.84 2.24255 12.8142 2.29181 12.7993C2.34107 12.7843 2.39304 12.7805 2.44398 12.788C2.49491 12.7956 2.54353 12.8143 2.58633 12.8429C2.62913 12.8716 2.66504 12.9093 2.69147 12.9535C2.71791 12.9977 2.7342 13.0472 2.73919 13.0984C2.74417 13.1497 2.73771 13.2014 2.72028 13.2499C2.70285 13.2983 2.67489 13.3423 2.6384 13.3786C2.58145 13.4353 2.50661 13.4705 2.42662 13.4783C2.34663 13.4861 2.26641 13.4659 2.1996 13.4213C2.13279 13.3766 2.08351 13.3102 2.06014 13.2333C2.03677 13.1564 2.04074 13.0737 2.0714 12.9994ZM11.4357 8.95736C12.1237 8.95736 12.7962 8.75334 13.3683 8.37112C13.9403 7.98889 14.3862 7.44561 14.6494 6.80999C14.9127 6.17437 14.9816 5.47494 14.8474 4.80017C14.7132 4.1254 14.3819 3.50558 13.8954 3.01909C13.4089 2.53261 12.7891 2.20131 12.1143 2.06709C11.4395 1.93286 10.7401 2.00175 10.1045 2.26504C9.46886 2.52832 8.92558 2.97417 8.54336 3.54622C8.16113 4.11827 7.95711 4.79081 7.95711 5.4788C7.95711 6.40137 8.3236 7.28616 8.97596 7.93851C9.62831 8.59087 10.5131 8.95736 11.4357 8.95736ZM11.7835 5.82666H11.0878C10.811 5.82666 10.5456 5.71671 10.3499 5.521C10.1542 5.3253 10.0442 5.05986 10.0442 4.78309C10.0442 4.50632 10.1542 4.24088 10.3499 4.04518C10.5456 3.84947 10.811 3.73952 11.0878 3.73952V3.39167C11.0878 3.29941 11.1245 3.21093 11.1897 3.1457C11.2549 3.08046 11.3434 3.04381 11.4357 3.04381C11.5279 3.04381 11.6164 3.08046 11.6816 3.1457C11.7469 3.21093 11.7835 3.29941 11.7835 3.39167V3.73952H12.4792C12.5715 3.73952 12.66 3.77617 12.7252 3.84141C12.7904 3.90664 12.8271 3.99512 12.8271 4.08738C12.8271 4.17964 12.7904 4.26812 12.7252 4.33335C12.66 4.39859 12.5715 4.43524 12.4792 4.43524H11.0878C10.9956 4.43524 10.9071 4.47188 10.8418 4.53712C10.7766 4.60236 10.74 4.69083 10.74 4.78309C10.74 4.87535 10.7766 4.96383 10.8418 5.02906C10.9071 5.0943 10.9956 5.13095 11.0878 5.13095H11.7835C12.0603 5.13095 12.3257 5.24089 12.5214 5.4366C12.7171 5.63231 12.8271 5.89774 12.8271 6.17451C12.8271 6.45128 12.7171 6.71672 12.5214 6.91243C12.3257 7.10813 12.0603 7.21808 11.7835 7.21808V7.56594C11.7835 7.65819 11.7469 7.74667 11.6816 7.81191C11.6164 7.87714 11.5279 7.91379 11.4357 7.91379C11.3434 7.91379 11.2549 7.87714 11.1897 7.81191C11.1245 7.74667 11.0878 7.65819 11.0878 7.56594V7.21808H10.3921C10.2998 7.21808 10.2114 7.18143 10.1461 7.1162C10.0809 7.05096 10.0442 6.96248 10.0442 6.87022C10.0442 6.77797 10.0809 6.68949 10.1461 6.62425C10.2114 6.55902 10.2998 6.52237 10.3921 6.52237H11.7835C11.8758 6.52237 11.9643 6.48572 12.0295 6.42049C12.0947 6.35525 12.1314 6.26677 12.1314 6.17451C12.1314 6.08226 12.0947 5.99378 12.0295 5.92854C11.9643 5.86331 11.8758 5.82666 11.7835 5.82666Z" />
															</svg>
														</span>
													</el-tooltip>
												</div>
											</div>
										</template>
									</el-table-column>
									<el-table-column prop="created_date" label="<?php esc_html_e( 'Created Date', 'bookingpress-appointment-booking' ); ?>" sortable>
										<template slot-scope="scope">
											<label>{{ scope.row.created_date }}</label>
											<?php
												if ( ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) || $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
												?>
												<div class="bpa-table-actions-wrap">
													<div class="bpa-table-actions">
														<?php
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
															?>
														<el-tooltip effect="dark" content="" placement="top" open-delay="300">
															<div slot="content">
																<span><?php esc_html_e( 'Edit', 'bookingpress-appointment-booking' ); ?></span>
															</div>
															<el-button class="bpa-btn bpa-btn--icon-without-box" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
																<span class="material-icons-round">mode_edit</span>
															</el-button>
														</el-tooltip>

														<el-tooltip effect="dark" content="" placement="top" open-delay="300">
															<div slot="content">
																<span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
															</div>
															<el-button class="bpa-btn bpa-btn--icon-without-box bpa--admin_note" @click="addadminnote(event,scope.row.appointment_id,scope.row.payment_id)">
																<span>
																<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																	<path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
																	</svg>
																</span>
															</el-button>
														</el-tooltip>
															<?php
														}
															do_action('bookingpress_appointment_list_add_action_button');
														?>
													</div>
												</div>
											<?php } ?>
										</template>
									</el-table-column>
								</el-table>
							</div>
							<div class="bpa-tc__wrapper" v-if="current_screen_size == 'tablet'">
								<el-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
									<el-table-column type="expand">
										<template slot-scope="scope">
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
															do_action('bookingpress_backend_appointment_list_after_appointment_price','tablet');
														?>														
													</div>
													<div class="bpa-hw-right-btn-group bpa-vac--head__right">
														<?php 
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
														?>	
															<el-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3'">
																<span class="material-icons-round">close</span>
																<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
															</el-button>
															<el-popconfirm 
																cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
																confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
																icon="false" 
																title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
																@confirm="bookingpress_change_status(scope.row.appointment_id, '3')" 
																confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
																cancel-button-type="bpa-btn bpa-btn__small"
																v-else-if="scope.row.appointment_status != '3'">
																<el-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
																	<span class="material-icons-round">close</span>
																	<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
																</el-button>
															</el-popconfirm>&nbsp;
														<?php } ?>
														<?php
															do_action('bookingpress_add_dynamic_buttons_for_view_appointments');
														?>
													</div>
												</div>
												<div class="bpa-vac--body">
													<el-row :gutter="56">
														<el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
															<div class="bpa-vac-body--appointment-details">
																<el-row :gutter="40">
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																			<?php do_action('add_bookingpress_appointment_details_outside'); ?>
																		</div>
																	</el-col>
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																	</el-col>
																</el-row>
															</div>
															<div class="bpa-vac-body--service-extras" v-if="scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
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
															<div class="bpa-vac-body--custom-fields" v-if="scope.row.custom_fields_values.length > 0">
																<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
																<div class="bpa-cf__body">
																	<el-row>
																		<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
																			<div class="bpa-bd__item">
																				<div class="bpa-bd__item-head">
																					<span v-html="custom_fields.label"></span>
																				</div>
																				<div class="bpa-bd__item-body">
																					<h4 v-html="custom_fields.value"></h4>
																				</div>
																			</div>																
																		</el-col>
																	</el-row>
																</div>
															</div>
															<?php do_action('bookingpress_backend_display_guest_data'); ?>
														</el-col>
														<?php
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
														?>
														<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
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
																	<?php do_action('bookingpress_modify_payment_appointment_section') ?>
																	<div class="bpa-pd__item bpa-pd-total__item">
																		<span>
																			<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
																			<div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
																		</span>
																		<p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
																	</div>
																</div>									
															</div>
														</el-col>
														<?php } ?>
													</el-row>										
												</div>
												<?php do_action("bookingpress_manage_appointment_expnd_additional_content_add"); ?>
											</div>
										</template>
									</el-table-column>
									<el-table-column prop="booking_id" min-width="30" label="<?php esc_html_e( 'ID', 'bookingpress-appointment-booking' ); ?>">
										<template slot-scope="scope">
											<span>#{{ scope.row.booking_id }}</span>
										</template>
									</el-table-column>
									<?php do_action('bookingpress_add_column_outsite'); ?>
									<el-table-column prop="appointment_date" min-width="100" label="<?php esc_html_e( 'Date', 'bookingpress-appointment-booking' ); ?>" sortable>
										<template slot-scope="scope">
											<label class="bpa-item__date-col">{{ scope.row.appointment_date }}</label>
											<label class="bpa-item__date-col bpa-item__dt-col-duration-md">
                                                <span class="material-icons-round">schedule</span>
                                                {{ scope.row.appointment_duration }}
                                            </label>
											<el-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
												<span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
											</el-tooltip>
										</template>
									</el-table-column>									
									<el-table-column prop="service_name" min-width="100" label="<?php esc_html_e( 'Service', 'bookingpress-appointment-booking' ); ?>" sortable>
										<?php do_action('bookingpress_multiple_service_name_dashboard_section_fetch'); ?>
									</el-table-column>									
									<el-table-column prop="appointment_status" min-width="90" label="<?php esc_html_e( 'Status', 'bookingpress-appointment-booking' ); ?>">
										<template slot-scope="scope">
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
													<el-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event)" popper-class="bpa-appointment-status-dropdown-popper">
														<el-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
															<el-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
														</el-option-group>
													</el-select>
												</div>
												
												<?php
											} else {
												?>
											<el-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</el-tag>
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
														<el-tooltip effect="dark" content="" placement="top" open-delay="300">
															<div slot="content">
																<span><?php esc_html_e( 'Edit', 'bookingpress-appointment-booking' ); ?></span>
															</div>
															<el-button class="bpa-btn bpa-btn--icon-without-box" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
																<span class="material-icons-round">mode_edit</span>
															</el-button>
														</el-tooltip>

														<el-tooltip effect="dark" content="" placement="top" open-delay="300">
															<div slot="content">
																<span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
															</div>
															<el-button class="bpa-btn bpa-btn--icon-without-box bpa--admin_note" @click="addadminnote(event,scope.row.appointment_id,scope.row.payment_id)">
																<span>
																<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																	<path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
																	</svg>
																</span>
															</el-button>
														</el-tooltip>
															<?php
														}
															do_action('bookingpress_appointment_list_add_action_button');
														?>
													</div>
												</div>
											<?php } ?>
										</template>
									</el-table-column>
								</el-table>
							</div>
							<div class="bpa-tc__wrapper bpa-manage-appointment-container--sm" v-if="current_screen_size == 'mobile'">
								<el-table ref="multipleTable" class="bpa-manage-appointment-items" :data="items" fit="false" @row-click="bookingpress_full_row_clickable" :show-header="false" @expand-change="bookingpress_row_expand">
									<el-table-column type="expand">
										<template slot-scope="scope">
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
															<el-button @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_id,scope.row.appointment_currency_symbol,scope.row.appointment_partial_refund)" class="bpa-btn" v-if="scope.row.appointment_refund_status == 1 && scope.row.appointment_status != '3'">
																<span class="material-icons-round">close</span>
																<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
															</el-button>
															<el-popconfirm 
																cancel-button-text='<?php esc_html_e( 'Close', 'bookingpress-appointment-booking' ); ?>' 
																confirm-button-text='<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>' 
																icon="false" 
																title="<?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'bookingpress-appointment-booking' ); ?>" 
																@confirm="bookingpress_change_status(scope.row.appointment_id, '3')" 
																confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger" 
																cancel-button-type="bpa-btn bpa-btn__small"
																v-else-if="scope.row.appointment_status != '3'">
																<el-button type="text" slot="reference" class="bpa-btn" v-if="scope.row.appointment_status != '3'">
																	<span class="material-icons-round">close</span>
																	<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>
																</el-button>
															</el-popconfirm>&nbsp;
														<?php } ?>
														<?php
															do_action('bookingpress_add_dynamic_buttons_for_view_appointments');
														?>
													</div>
												</div>
												<?php  
													do_action('bookingpress_backend_appointment_list_after_appointment_price','mobile');
												?>												
												<div class="bpa-vac--body">
													<el-row :gutter="56">
														<el-col :xs="24" :sm="24" :md="24" :lg="16" :xl="18">
															<div class="bpa-vac-body--appointment-details">
																<el-row :gutter="40">
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																			<?php do_action('add_bookingpress_appointment_details_outside'); ?>
																		</div>
																	</el-col>
																	<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
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
																	</el-col>
																</el-row>
															</div>
															<div class="bpa-vac-body--service-extras" v-if="scope.row.bookingpress_extra_service_data.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
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
															<div class="bpa-vac-body--custom-fields" v-if="scope.row.custom_fields_values.length > 0">
																<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Custom Fields', 'bookingpress-appointment-booking'); ?></h4>
																<div class="bpa-cf__body">
																	<el-row>
																		<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12" v-for="custom_fields in scope.row.custom_fields_values">
																			<div class="bpa-bd__item">
																				<div class="bpa-bd__item-head">
																					<span v-html="custom_fields.label"></span>
																				</div>
																				<div class="bpa-bd__item-body">
																					<h4 v-html="custom_fields.value"></h4>
																				</div>
																			</div>																
																		</el-col>
																	</el-row>
																</div>
															</div>
															<?php do_action('bookingpress_backend_display_guest_data'); ?>
														</el-col>
														<?php
														if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
														?>
														<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
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
																	<?php do_action('bookingpress_modify_payment_appointment_section') ?>
																	<div class="bpa-pd__item bpa-pd-total__item">
																		<span>
																			<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?> 
																			<div class="bpa-vac-pd-total__tax-include-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</div>
																		</span>
																		<p class="bpa-cl-pt-main-green">{{ scope.row.bookingpress_final_total_amt_with_currency }}</p>
																	</div>
																</div>									
															</div>
														</el-col>
														<?php } ?>
													</el-row>										
												</div>
												<?php do_action("bookingpress_manage_appointment_expnd_additional_content_add"); ?>
											</div>
										</template>
									</el-table-column>
									<el-table-column>
										<template slot-scope="scope">
											<div class="bpa-ap-item__mob">
												<div class="bpa-api--head">
													<h4>{{ scope.row.service_name }}</h4>
													<div class="bpa-api--head-apointment-details">
                                                        <p>
															<span class="material-icons-round">today</span>{{ scope.row.appointment_date }} 
															<el-tooltip content="<?php esc_html_e('Rescheduled', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_rescheduled == 1">
																<span class="material-icons-round bpa-rescheduled-appointment-icon" v-if="scope.row.is_rescheduled == 1">update</span>
															</el-tooltip>
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
																<el-select class="bpa-form-control" :class="((scope.row.appointment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.appointment_status == '3' ? 'bpa-appointment-status--cancelled' : '') || (scope.row.appointment_status == '1' ? 'bpa-appointment-status--approved' : '') || (scope.row.appointment_status == '4' ? 'bpa-appointment-status--rejected' : '') || (scope.row.appointment_status == '5' ? 'bpa-appointment-status--no-show' : '') || (scope.row.appointment_status == '6' ? 'bpa-appointment-status--completed' : '')" v-model="scope.row.appointment_status" placeholder="<?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?>" @change="bookingpress_change_status(scope.row.appointment_id, $event)" popper-class="bpa-appointment-status-dropdown-popper">
																	<el-option-group label="<?php esc_html_e( 'Change status', 'bookingpress-appointment-booking' ); ?>">
																		<el-option v-for="item in appointment_status" :key="item.value" :label="item.text" :value="item.value"></el-option>
																	</el-option-group>
																</el-select>
															</div>
															<?php
														} else {
															?>
														<el-tag class="bpa-front-pill " :class="((scope.row.appointment_status == '2') ? '--warning' : '') || (scope.row.appointment_status == '3' ? '--info' : '') || (scope.row.appointment_status == '1' ? '--approved' : '') || (scope.row.appointment_status == '4' ? '--rejected' : '') || (scope.row.appointment_status == '5' ? '--no-show' : '') || (scope.row.appointment_status == '6' ? '--completed' : '') " >{{ scope.row.appointment_status_label }}</el-tag>
														<?php
														}?>
													<div class="bpa-mpay-fi__actions bpa-mac-fi__actions">
														<?php
															if ( ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) || $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
														?>
														<?php
															if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) ) {
														?>
														<el-button class="bpa-btn bpa-btn__small bpa-btn__filled-light" @click.native.prevent="editAppointmentData(scope.$index, scope.row)">
															<span class="material-icons-round">mode_edit</span>
														</el-button>

														<el-tooltip effect="dark" content="" placement="top" open-delay="300">
															<div slot="content">
																<span><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
															</div>
															<el-button class="bpa-btn bpa-btn__small bpa-btn__filled-light bpa--admin_note" @click="addadminnote(event,scope.row.appointment_id,scope.row.payment_id)">
																<span>
																<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																	<path d="M11.3999 4.66566C11.2884 4.07519 11.4145 3.47675 11.755 2.98073C12.0955 2.48463 12.6095 2.15026 13.2025 2.03929C13.3413 2.01326 13.4828 2 13.6228 2C14.7068 2 15.6391 2.77149 15.8397 3.83438C16.0698 5.0534 15.2613 6.23158 14.0373 6.46083C13.8983 6.48687 13.7568 6.50005 13.617 6.50005C12.533 6.49996 11.6006 5.72856 11.3999 4.66566ZM16.977 15.1548C16.9309 15.2856 16.8212 15.3841 16.6857 15.4161L5.78649 17.989C5.75504 17.9964 5.72351 18 5.69222 18C5.55205 18 5.41873 17.9278 5.34341 17.8048C5.31253 17.7543 4.5779 16.5289 3.86353 13.2456C3.15561 9.9917 3.00621 4.88556 3.00016 4.66981C2.99412 4.45325 3.15954 4.26995 3.37642 4.25295L10.5954 3.68819C10.5267 4.05607 10.5254 4.43714 10.597 4.81592C10.6643 5.17258 10.7925 5.505 10.969 5.80365L8.52365 7.74363C8.3472 7.88364 8.31804 8.1395 8.45863 8.31523C8.53925 8.41603 8.65827 8.46858 8.77836 8.46858C8.86756 8.46858 8.95734 8.43962 9.03257 8.37999L11.4734 6.44375C12.0316 6.98549 12.7919 7.31359 13.6169 7.31359C13.8074 7.31359 13.9996 7.29569 14.1881 7.26039C14.3081 7.23793 14.4249 7.20799 14.5387 7.1726C14.5944 7.84337 14.6664 8.58793 14.7605 9.38488C15.124 12.4634 16.8954 14.747 16.9132 14.7697C16.999 14.8788 17.0231 15.024 16.977 15.1548ZM11.3492 13.7015C11.2962 13.4831 11.0755 13.3488 10.8562 13.4017L6.53575 14.4419C6.31641 14.4947 6.18171 14.7145 6.23464 14.9329C6.2799 15.1192 6.44711 15.2441 6.6314 15.2441C6.66318 15.2441 6.69545 15.2405 6.72772 15.2326L11.0482 14.1924C11.2674 14.1396 11.4021 13.9199 11.3492 13.7015ZM12.6681 10.3513C12.6222 10.1314 12.406 9.98967 12.1853 10.0357L5.691 11.3781C5.47011 11.4237 5.32822 11.639 5.37405 11.859C5.41407 12.0511 5.58399 12.1832 5.77359 12.1832C5.80103 12.1832 5.82905 12.1805 5.85699 12.1747L12.3513 10.8323C12.5721 10.7866 12.7141 10.5713 12.6681 10.3513Z" fill="#727E95"/>
																	</svg>
																</span>
															</el-button>
														</el-tooltip>
														<?php
															}
															do_action('bookingpress_appointment_list_add_action_button');
														?>
														<?php } ?>
													</div>
												</div>
											</div>
										</template>
									</el-table-column>
								</el-table>
							</div>                            
                        </el-container>
                    </el-col>
                </el-row>
            </el-row>
        </div>
    </div>
</el-main>

<el-dialog custom-class="bpa-dialog bpa-dialog--fullscreen bpa--is-page-non-scrollable-mob" modal-append-to-body=false :visible.sync="open_appointment_modal" :before-close="closeAppointmentModal" fullscreen=true :close-on-press-escape="close_modal_on_esc">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
				<h1 class="bpa-page-heading" v-if="appointment_formdata.appointment_update_id == 0"><?php esc_html_e( 'Add Appointment', 'bookingpress-appointment-booking' ); ?></h1>
				<h1 class="bpa-page-heading" v-else><?php esc_html_e( 'Edit Appointment', 'bookingpress-appointment-booking' ); ?></h1>
			</el-col>
			<el-col :xs="12" :sm="12" :md="7" :lg="7" :xl="7" class="bpa-dh__btn-group-col">
				<el-button class="bpa-btn bpa-btn--primary" :class="(is_display_save_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="saveProAppointmentBooking('appointment_formdata')" :disabled="is_disabled" >					
					<span class="bpa-btn__label"><?php esc_html_e( 'Save', 'bookingpress-appointment-booking' ); ?></span>
					<div class="bpa-btn--loader__circles">				    
						<div></div>
						<div></div>
						<div></div>
					</div>
				</el-button>
				<el-button class="bpa-btn" @click="closeAppointmentModal()"><?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?></el-button>
			</el-col>
		</el-row>
	</div>
	<div class="bpa-dialog-body">
		<div class="bpa-back-loader-container" v-if="is_display_loader == '1'">
			<div class="bpa-back-loader"></div>
		</div>
		<div class="bpa-form-row">
			<el-row>
				<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
					<div class="bpa-db-sec-heading">
						<el-row type="flex" align="middle">
							<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
								<div class="db-sec-left">
									<h2 class="bpa-page-heading"><?php esc_html_e( 'Basic Details', 'bookingpress-appointment-booking' ); ?></h2>
								</div>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-default-card bpa-db-card">
						<el-form class="bpa-add-appointment-form" ref="appointment_formdata" :rules="rules" :model="appointment_formdata" label-position="top" @submit.native.prevent>
							<template>								
								<div class="bpa-form-body-row">
									<el-row :gutter="32">
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" :class="(is_extras_enable == 1) ? 'bpa-select-appointment-service' : ''">
											<el-form-item prop="appointment_selected_service">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'Select Service', 'bookingpress-appointment-booking' ); ?></span>
												</template>
												<div class="bpa-aaf__service-selection-col">
													<el-select class="bpa-form-control" @Change="bookingpress_appointment_change_service" v-model="appointment_formdata.appointment_selected_service" name="appointment_selected_service" filterable placeholder="<?php esc_html_e( 'Select Service', 'bookingpress-appointment-booking' ); ?>" popper-class="bpa-el-select--is-with-modal">
														<el-option-group v-for="service_cat_data in appointment_services_list" :key="service_cat_data.category_name" :label="service_cat_data.category_name">
															<template v-if="service_data.service_id == 0" v-for="service_data in service_cat_data.category_services">
																<el-option :key="service_data.service_id" :label="service_data.service_name" :value="''" ></el-option>
															</template>
															<template v-else>
																<el-option :class="(service_data.service_enabled != 'true')?'bpa-disable-serv-option':''" :key="service_data.service_id" :label="(service_data.service_name+' ('+service_data.service_price+' )'+ ' - ' +service_data.service_duration_formatted)" :value="service_data.service_id">
																	<span :class="(service_data.service_enabled != 'true')?'bpa-disable-serv-label':''">{{ (service_data.service_name+' ('+service_data.service_price+' )'+ ' - ' +service_data.service_duration_formatted)  }}</span>
																	<span class="bpa-disable-serv-txt" v-if="service_data.service_enabled != 'true'"><?php esc_html_e( 'Disabled', 'bookingpress-appointment-booking' ); ?></span>
																</el-option>
															</template>
														</el-option-group>
													</el-select>
													<el-popover width="400" trigger="click" popper-class="bpa-aaf--extra-popover" v-modal="bookingpress_extras_popover_modal" v-if="is_extras_enable == 1" :disabled="(appointment_formdata.appointment_selected_service == '' || bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length == 0) ? true : false">
														<div class="bpa-aaf--service-extras">
															<h4><?php esc_html_e('Select Extras', 'bookingpress-appointment-booking'); ?></h4>
															<div class="bpa-aaf__extras-body" v-if="appointment_formdata.appointment_selected_service != ''">
																<div class="bpa-aaf-extra__item" v-for="(extras_details, index) in bookingpress_loaded_extras[appointment_formdata.appointment_selected_service]" v-if="bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length > 0">
																	<div class="bpa-aaf-ei__header">
																		<div class="bpa-aaf-ei__left">
																			<div class="bpa-aaf-ei__left-checkbox">
																				<el-checkbox class="bpa-form-control--checkbox" v-model="bookingpress_loaded_extras[appointment_formdata.appointment_selected_service][index]['bookingpress_is_selected']"></el-checkbox>
																			</div>
																			<div class="bpa-aaf-ei__left-body">
																				<h5 class="bpa-aaf-ei__heading">{{ extras_details.bookingpress_extra_service_name }}</h5>
																				<div class="bpa-aaf-ei--options">
																					<p>{{ extras_details.bookingpress_extra_service_price_with_currency }}</p>
																					<p class="bpa-aaf-ei__duration"><span class="material-icons-round">schedule</span> {{ extras_details.bookingpress_extra_service_duration }}{{ extras_details.bookingpress_extra_service_duration_unit }}</p>
																				</div>
																				<div class="bpa-aaf-ei__description" v-if="extras_details.bookingpress_service_description != ''">																					
																					<el-link class="bpa-aaf-ei__btn" @click="bookingpress_toggle_extra_address(extras_details.bookingpress_extra_services_id, 1)" v-if="extras_details.bookingpress_is_display_description == '0'">
																						<?php esc_html_e('View more', 'bookingpress-appointment-booking'); ?> 
																						<span class="material-icons-round">add</span>
																					</el-link>
																					<el-link class="bpa-aaf-ei__btn" @click="bookingpress_toggle_extra_address(extras_details.bookingpress_extra_services_id, 0)" v-if="extras_details.bookingpress_is_display_description == '1'">
																						<?php esc_html_e('View less', 'bookingpress-appointment-booking'); ?> 
																						<span class="material-icons-round">remove</span>
																					</el-link>
																					<p v-if="extras_details.bookingpress_is_display_description == '1'">{{ extras_details.bookingpress_service_description }}</span>
																				</div>
																			</div>
																		</div>
																		<div class="bpa-aaf-ei__right">
																			<el-select class="bpa-form-control" popper-class="bpa-aaf-ei-quantity-dropdown bpa-sum-ei-quantity-dropdown" v-model="bookingpress_loaded_extras[appointment_formdata.appointment_selected_service][index]['bookingpress_selected_qty']">
																				<el-option v-for="n in parseInt(extras_details.bookingpress_extra_service_max_quantity)" :value="n">{{ n }}</el-option>
																			</el-select>
																		</div>
																	</div>
																</div>

															</div>
															<div class="bpa-aaf__extras-foot">
																<el-button class="bpa-btn bpa-btn__small" @click="bookingpress_close_extras_modal"><?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?></el-button>
																<el-button class="bpa-btn bpa-btn--primary bpa-btn__small" @click="bookingpress_add_extras"><?php esc_html_e('Add', 'bookingpress-appointment-booking'); ?></el-button>
															</div>
														</div>
														<el-button slot="reference" class="bpa-btn bpa-btn__medium" :class="(appointment_formdata.appointment_selected_service == '' || bookingpress_loaded_extras[appointment_formdata.appointment_selected_service].length == 0) ? '__bpa-is-disabled' : ''">
															<?php esc_html_e('Add Extra', 'bookingpress-appointment-booking'); ?>
															<span class="bpa-ep__counter" v-if="appointment_formdata.selected_extra_services_ids.length > 0">{{ appointment_formdata.selected_extra_services_ids.length }}</span>
														</el-button>
													</el-popover>
												</div>
												<div class="bpa-aaf__extras-preview" v-if="appointment_formdata.selected_extra_services_ids.length > 0 && ( scope.row.is_multi_service_booking == '' || 1 != scope.row.is_multi_service_booking )">
													<h4><?php esc_html_e('Extras', 'bookingpress-appointment-booking'); ?></h4>
													<div class="bpa-aaf-ep__items">
														<div class="bpa-aaf-ep__item" v-for="selected_extras in bookingpress_loaded_extras[appointment_formdata.appointment_selected_service]" v-if="selected_extras.bookingpress_is_selected === true">
															<p v-if="selected_extras.bookingpress_is_selected === true">{{ selected_extras.bookingpress_extra_service_name }}</p>
															<span class="material-icons-round" @click="bookingpress_remove_extras(selected_extras.bookingpress_extra_services_id)">close</span>
														</div>
													</div>
												</div>
											</el-form-item>
										</el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="is_staff_enable == 1 && (typeof allow_multi_staffmember != 'undefined' && allow_multi_staffmember == '0') || (typeof allow_multi_staffmember == 'undefined')">
											<el-form-item prop="selected_staffmember">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?></span>
												</template>
												<el-select class="bpa-form-control" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?>" filterable v-model="appointment_formdata.selected_staffmember" @change="bookingpress_change_staff" >
													<el-option value=""><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_singular_staffmember_name); ?></el-option>
													<el-option :label="staff_member_details.profile_details.bookingpress_staffmember_firstname != '' && staff_member_details.profile_details.bookingpress_staffmember_lastname != '' ? staff_member_details.profile_details.bookingpress_staffmember_firstname+' '+staff_member_details.profile_details.bookingpress_staffmember_lastname+' ( '+staff_member_details.staff_price_with_currency+' )' : staff_member_details.profile_details.bookingpress_staffmember_email+' ( '+staff_member_details.staff_price_with_currency+' )'" :value="staff_member_details.profile_details.bookingpress_staffmember_id" v-for="staff_member_details in bookingpress_loaded_staff[appointment_formdata.appointment_selected_service]" v-if="typeof staff_member_details.profile_details != 'undefined'"></el-option>
												</el-select>
											</el-form-item>
										</el-col>
										<?php do_action('bookingpress_add_appointment_after_staff_select_box', $bookingpress_singular_staffmember_name); ?>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="is_bring_anyone_with_you_enable == 1">
											<el-form-item>
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'No. of Person', 'bookingpress-appointment-booking' ); ?></span>
												</template>
												<!-- <el-input-number v-model="appointment_formdata.selected_bring_members" class="bpa-form-control bpa-form-control--number" :min="appointment_formdata.bookingpress_bring_anyone_min_capacity" :max="appointment_formdata.bookingpress_bring_anyone_max_capacity" @change="bookingpress_change_bring_anyone()" :step="appointment_formdata.bookingpress_bring_anyone_step"></el-input-number> -->

												<el-select class="bpa-form-control" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?>" v-model="appointment_formdata.selected_bring_members" @change="bookingpress_change_bring_anyone()">
													<el-option v-for="num in bookingpress_get_bring_anyone_options(appointment_formdata.bookingpress_bring_anyone_min_capacity,appointment_formdata.bookingpress_bring_anyone_max_capacity,appointment_formdata.bookingpress_bring_anyone_step)" :key="num" :label="num + ' ' + (num == 1 ? '<?php esc_html_e('Person', 'bookingpress-appointment-booking'); ?>' : '<?php esc_html_e('Persons', 'bookingpress-appointment-booking'); ?>')" :value="num"></el-option>
												</el-select>
											</el-form-item>
										</el-col>
										<?php do_action('bookingpress_add_appointment_custom_service_duration_field_section') ?>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">											
											<el-form-item prop="appointment_selected_customer">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'Select Customer', 'bookingpress-appointment-booking' ); ?></span>
												</template>						
												<el-select class="bpa-form-control" name="appointment_selected_customer" v-model="appointment_formdata.appointment_selected_customer" @change="bookingpress_select_customer($event)"  filterable placeholder="<?php esc_html_e( 'Start typing to fetch Customer', 'bookingpress-appointment-booking' ); ?>" remote reserve-keyword :remote-method="bookingpress_get_customer_list" :loading="bookingpress_loading"  popper-class="bpa-el-select--is-with-modal" v-cancel-read-only>
													<el-option v-for="customer_data in appointment_customers_list" :key="customer_data.value" :label="customer_data.text" :value="customer_data.value">
														<i class="el-icon-plus" v-if="customer_data.value == 'add_new'"></i>
														<span>{{ customer_data.text }}</span>
													</el-option>
												</el-select>  															
											</el-form-item>
										</el-col>										
									</el-row>
									<el-row :gutter="32">
										<el-col class="bpa-custom-time-row" :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                                            <el-form-item>
                                                <template #label>
                                                <span class="bpa-form-label"></span>
                                                </template>
                                                <label class="bpa-form-label bpa-custom-checkbox--is-label"> <el-checkbox v-model="appointment_formdata.appointment_custom_timing" @change="handleCustomTimingChange($event)"></el-checkbox> <?php esc_html_e('Allow Custom Duration', 'bookingpress-appointment-booking'); ?></label>
                                            </el-form-item>
                                        </el-col>
									</el-row>									
									<el-row :gutter="32">
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == true">
                                            <el-form-item prop="appointment_booked_date">
                                                <template #label>
													<span v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '0'" class="bpa-form-label"><?php esc_html_e('Appointment Start Date', 'bookingpress-appointment-booking'); ?></span>		
                                                    <span v-else class="bpa-form-label"><?php esc_html_e('Appointment Date', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
												<el-date-picker class="bpa-form-control bpa-form-control--date-picker" type="date" :format="bpa_date_common_date_format" :picker-options="filter_pickerOptions" v-model="appointment_formdata.appointment_booked_date" name="appointment_booked_date" popper-class="bpa-el-datepicker-widget-wrapper" type="date" :clearable="false" @change="select_appointment_booking_date($event)" value-format="yyyy-MM-dd"></el-date-picker>
                                            </el-form-item>
                                        </el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '0'">
											<el-form-item prop="appointment_booked_end_date">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Appointment End Date', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
												<el-date-picker class="bpa-form-control bpa-form-control--date-picker" type="date" :format="bpa_date_common_date_format"  v-model="appointment_formdata.appointment_booked_end_date" name="appointment_booked_end_date" popper-class="bpa-el-datepicker-widget-wrapper" type="date" :clearable="false"  value-format="yyyy-MM-dd"></el-date-picker> <!--@change="select_appointment_booking_end_date($event)" -->
                                            </el-form-item>
										</el-col>										
										<el-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '1'">
                                            <el-form-item prop="appointment_booked_time">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('Start Time', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <el-select v-model="appointment_formdata.appointment_booked_time" @change="change_custom_start_time($event)" class="bpa-form-control bpa-form-control__left-icon" filterable placeholder="<?php esc_html_e('Start Time', 'bookingpress-appointment-booking'); ?>">
                                                    <span slot="prefix" class="material-icons-round">access_time</span>
                                                    <el-option v-for="appointment_times in appointment_formdata.default_appointment_timing" :key="appointment_times.start_time_val" :label="appointment_times.start_time_formatted" v-if="appointment_times.start_time_val < '24:00:00'" :value="appointment_times.start_time_val" ></el-option>
                                                </el-select>
                                            </el-form-item>
                                        </el-col>		
										<el-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" v-if="appointment_formdata.appointment_custom_timing == true && is_timeslot_display == '1'">
                                            <el-form-item prop="appointment_booked_end_time">
                                                <template #label>
                                                    <span class="bpa-form-label"><?php esc_html_e('End Time', 'bookingpress-appointment-booking'); ?></span>
                                                </template>
                                                <el-select v-model="appointment_formdata.appointment_booked_end_time"  @change="change_custom_end_time($event)" class="bpa-form-control bpa-form-control__left-icon" filterable placeholder="<?php esc_html_e('End Time', 'bookingpress-appointment-booking'); ?>">
                                                    <span slot="prefix" class="material-icons-round">access_time</span>
													<el-option v-for="appointment_times in appointment_formdata.default_appointment_timing" v-if="( appointment_times.end_time_val > appointment_formdata.appointment_booked_time && true == appointment_times.is_visible)" :key="appointment_times.end_time_val" :label="appointment_times.end_time_formatted" :value="appointment_times.end_time_val"></el-option>
                                                </el-select>
                                            </el-form-item>
                                        </el-col>	
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="appointment_formdata.appointment_custom_timing == false">
											<el-form-item prop="appointment_booked_date">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'Appointment Date', 'bookingpress-appointment-booking' ); ?></span>
												</template>
												<el-date-picker class="bpa-form-control bpa-form-control--date-picker" :format="bpa_date_common_date_format" v-model="appointment_formdata.appointment_booked_date" name="appointment_booked_date" type="date" popper-class="bpa-el-select--is-with-modal bpa-el-datepicker-widget-wrapper" :clearable="false" :picker-options="pickerOptions" @change="select_appointment_booking_date($event)" value-format="yyyy-MM-dd"></el-date-picker>
											</el-form-item>
										</el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-show="appointment_formdata.appointment_custom_timing == false && is_timeslot_display == '1'">
											<el-form-item prop="appointment_booked_time">
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'Appointment Time', 'bookingpress-appointment-booking' ); ?></span>
												</template>
												<el-select class="bpa-form-control" Placeholder="<?php esc_html_e( 'Select Time', 'bookingpress-appointment-booking' ); ?>" v-model="appointment_formdata.appointment_booked_time" filterable popper-class="bpa-el-select--is-with-modal" @Change="bookingpress_set_time($event,appointment_time_slot)">
													<el-option-group v-for="appointment_time_slot_data in appointment_time_slot" :key="appointment_time_slot_data.timeslot_label" :label="appointment_time_slot_data.timeslot_label">
														<el-option v-for="appointment_time in appointment_time_slot_data.timeslots" :label="(appointment_time.formatted_start_time)+' to '+(appointment_time.formatted_end_time)" :value="appointment_time.store_start_time" :disabled="( appointment_time.is_disabled || appointment_time.max_capacity == 0 || appointment_time.is_booked == 1 )">
															<span>
																{{ appointment_time.formatted_start_time  }} to {{appointment_time.formatted_end_time}}
																<span v-if="appointment_time.is_next_day == 'true' || appointment_time.is_next_day == true">(<?php esc_html_e( 'Next Day', 'bookingpress-appointment-booking' ); ?>)</span>
															</span>
														</el-option>	
													</el-option-group>
												</el-select>
											</el-form-item>
										</el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
											<el-form-item>
											<template #label>
												<span class="bpa-form-label"><?php esc_html_e( 'Select Status', 'bookingpress-appointment-booking' ); ?></span>
											</template>
											<el-select class="bpa-form-control" v-model="appointment_formdata.appointment_status">
												<el-option v-for="status_data in appointment_status" :key="status_data.value" :label="status_data.text" :value="status_data.value">
													<span>{{ status_data.text }}</span>
												</el-option>
											</el-select>
											</el-form-item>
										</el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
											<el-form-item>
												<template #label>
													<span class="bpa-form-label"><?php esc_html_e( 'Internal note', 'bookingpress-appointment-booking' ); ?></span>
												</template>
												<el-input class="bpa-form-control" v-model="appointment_formdata.appointment_internal_note"></el-input>
											</el-form-item>
										</el-col>
									</el-row>
								</div>						
								<div class="bpa-form-body-row">
									<el-row :gutter="24">
										<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
											<el-form-item>
												<label class="bpa-form-label bpa-custom-checkbox--is-label"> <el-checkbox v-model="appointment_formdata.appointment_send_notification"></el-checkbox> <?php esc_html_e( 'Do Not Send Notifications', 'bookingpress-appointment-booking' ); ?></label>
											</el-form-item>
										</el-col> 
										<?php do_action('bookingpress_add_appointment_field_section') ?>										
									</el-row>
								</div>	
							</template>
						</el-form>
					</div>
				</el-col>				
			</el-row>			
		</div>
		<div class="bpa-form-row" v-if="bookingpress_form_fields.length > 0">
			<el-row>
				<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="bookingpress_form_fields.length > 0">
					<div class="bpa-db-sec-heading">
						<el-row type="flex" align="middle">
							<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
								<div class="db-sec-left">
									<h2 class="bpa-page-heading"><?php esc_html_e( 'Custom Fields', 'bookingpress-appointment-booking' ); ?></h2>
								</div>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-default-card bpa-db-card">
						<el-form ref="appointment_custom_formdata" :rules="custom_field_rules" :model="appointment_formdata.bookingpress_appointment_meta_fields_value" label-position="top" @submit.native.prevent>
							<template>
								<div class="bpa-form-body-row">
									<el-row :gutter="34">
										<el-col :xs="24" :sm="24" :md="24" :lg="08" :xl="08" v-for="form_fields in bookingpress_form_fields" :class="(form_fields.is_separator == true) ? '--bpa-is-field-separator' : ''">
											<div v-if="form_fields.is_separator == false">
												<div v-if="form_fields.selected_services.length > 0">
													<el-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone") && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></el-input>
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "textarea" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3"></el-input>
													</el-form-item>									
													<el-form-item v-if="form_fields.bookingpress_field_type == 'checkbox' && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)" :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
															<el-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></el-checkbox>
														</el-checkbox-group>
													</el-form-item>
													<el-form-item v-if="form_fields.bookingpress_field_type == 'radio' && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)" :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-radio class="bpa-form-label bpa-custom-radio--is-label" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" v-if="keys < 5" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.label" :key="chk_data.value" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, chk_data.value, form_fields)">{{chk_data.label}}</el-radio>
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "dropdown" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
															<el-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value" ></el-option>
														</el-select>
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "date" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" prefix-icon="" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder" :type="'true' == form_fields.bookingpress_field_options.enable_timepicker ? 'datetime' : 'date'" :value-format="form_fields.bookingpress_field_options.enable_timepicker == 'true' ? 'yyyy-MM-dd hh:mm' : 'yyyy-MM-dd'" :picker-options="filter_pickerOptions"></el-date-picker> <!-- @change="bookingpress_custom_field_date_change($event,form_fields.bookingpress_field_meta_key,form_fields.bookingpress_field_options.enable_timepicker)" -->
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "file" && form_fields.selected_services.includes(appointment_formdata.appointment_selected_service)' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-upload :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :on-exceed="BPACustomerhandleFileExceed" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
															<label for="bpa-file-upload-two" class="bpa-form-control--file-upload" >
																<span class="bpa-fu__placeholder"> {{form_fields.bookingpress_field_placeholder }} </span>
																<span class="bpa-fu__btn"> {{ form_fields.bookingpress_field_options.browse_button_label }}</span>
															</label> 
														</el-upload>
													</el-form-item>
												</div>
												<div v-else>
													<el-form-item v-if='(form_fields.bookingpress_field_type == "text" || form_fields.bookingpress_field_type == "email" || form_fields.bookingpress_field_type == "phone")' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-input class="bpa-form-control" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :placeholder="form_fields.bookingpress_field_placeholder"></el-input>
													</el-form-item>

													<el-form-item v-if='form_fields.bookingpress_field_type == "textarea"' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-input class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" type="textarea" :rows="3" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]"></el-input>
													</el-form-item>									
													<el-form-item v-if="form_fields.bookingpress_field_type == 'checkbox'" :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-checkbox-group v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields['bookingpress_field_meta_key']]">
															<el-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" v-for="(chk_data, keys) in JSON.parse( form_fields.bookingpress_field_values)" :label="chk_data.value" :key="chk_data.value" :name="form_fields['bookingpress_field_meta_key']"><p v-html="chk_data.label"></p></el-checkbox>
														</el-checkbox-group>
													</el-form-item>
													<el-form-item v-if="form_fields.bookingpress_field_type == 'radio'" :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-radio class="bpa-form-label bpa-custom-radio--is-label" v-if="keys < 5" v-for="(chk_data, keys) in JSON.parse(form_fields.bookingpress_field_values)" :label="chk_data.value" :key="chk_data.value" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, chk_data.value, form_fields)">{{chk_data.label}}</el-radio>
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "dropdown"' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-select class="bpa-form-control" :placeholder="form_fields.bookingpress_field_placeholder" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" @change="bookingpress_handle_tax_calculation(form_fields.bookingpress_form_field_id, $event, form_fields)">
															<el-option v-for="sel_data in JSON.parse(form_fields.bookingpress_field_values)" :key="sel_data.value" :label="sel_data.label" :value="sel_data.value"></el-option>
														</el-select>
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "date"' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-date-picker :format="( 'true' == form_fields.bookingpress_field_options.enable_timepicker ) ? bpa_date_time_common_date_format : bpa_date_common_date_format" class="bpa-form-control bpa-form-control--date-picker" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" prefix-icon="" :placeholder="form_fields.bookingpress_field_placeholder" :type="'true' == form_fields.bookingpress_field_options.enable_timepicker ? 'datetime' : 'date'" :value-format="form_fields.bookingpress_field_options.enable_timepicker == 'true' ? 'yyyy-MM-dd hh:mm' : 'yyyy-MM-dd'" :picker-options="filter_pickerOptions"></el-date-picker> <!-- @change="bookingpress_custom_field_date_change($event,form_fields.bookingpress_field_meta_key,form_fields.bookingpress_field_options.enable_timepicker)" -->
													</el-form-item>
													<el-form-item v-if='form_fields.bookingpress_field_type == "file"' :prop="form_fields.bookingpress_field_meta_key">
														<template #label>
															<span class="bpa-form-label">{{ form_fields.bookingpress_field_label }}</span>
														</template>
														<el-upload class="bpa-form-control" :action="form_fields.bpa_action_url" :ref="form_fields.bpa_ref_name" :data="form_fields.bpa_action_data" v-model="appointment_formdata.bookingpress_appointment_meta_fields_value[form_fields.bookingpress_field_meta_key]" :on-success="BPACustomerFileUpload" :on-remove="BPACustomerFileUploadRemove" :file-list="form_fields.bpa_file_list" :on-error="BPACustomerFileUploadError" :on-exceed="BPACustomerhandleFileExceed" :multiple="form_fields.bookingpress_field_options.multiple_file_upload" :limit="form_fields.bookingpress_field_options.multiple_file_upload ? form_fields.bookingpress_field_options.max_file_upload : 1" :name="form_fields.bookingpress_field_meta_key" >
															<label for="bpa-file-upload-two" class="bpa-form-control--file-upload" >
																<span class="bpa-fu__placeholder"> {{form_fields.bookingpress_field_placeholder }} </span>
																<span class="bpa-fu__btn"> {{ form_fields.bookingpress_field_options.browse_button_label }}</span>
															</label> 
														</el-upload>
													</el-form-item>
												</div>
											</div>
										</el-col>
									</el-row>
								</div>
							</template>
						</el-form>	
					</div>
				</el-col>
			</el-row>
		</div>
		<?php 
		if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_payments' ) ) {
		?>
		<div class="bpa-form-row">
			<el-row>
				<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
					<div class="bpa-db-sec-heading">
						<el-row type="flex" align="middle">
							<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="12">
								<div class="db-sec-left">
									<h2 class="bpa-page-heading"><?php esc_html_e( 'Payment Details', 'bookingpress-appointment-booking' ); ?></h2>
								</div>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-default-card bpa-db-card">
						<div class="bpa-aaf--payment-details">
							<div class="bpa-aaf-pd__base-price-row">
								<div class="bpa-bpr__item">
									<h4>
										<?php esc_html_e('Subtotal', 'bookingpress-appointment-booking'); ?> 
										<span v-if="appointment_formdata.selected_bring_members != 0">(<?php esc_html_e('Guests', 'bookingpress-appointment-booking'); ?> x {{ appointment_formdata.selected_bring_members }})</span>
									</h4>
									<h4 v-if="typeof appointment_formdata.subtotal_temp != 'undefined' && appointment_formdata.subtotal_temp != 0">{{ appointment_formdata.subtotal_temp_with_currency }}</h4>
									<h4 v-else>{{ appointment_formdata.subtotal_with_currency }}</h4>
								</div>
								<?php do_action('bookingpress_backend_add_appointment_after_sub_total'); ?>
								<div class="bpa-bpr__item">
									<h4><?php esc_html_e('Service Extras', 'bookingpress-appointment-booking'); ?></h4>
									<h4>{{ appointment_formdata.extras_total_with_currency }}</h4>
								</div>
								<div class="bpa-bpr__item" v-if="appointment_formdata.tax != '0' && (appointment_formdata.tax_price_display_options != 'include_taxes' || (appointment_formdata.tax_price_display_options == 'include_taxes' && (appointment_formdata.display_tax_order_summary == 'true' || appointment_formdata.display_tax_order_summary == '1')))">
									<h4><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></h4>
									<h4>+{{ appointment_formdata.tax_with_currency }}</h4>
								</div>								
							</div>
							<?php do_action('bookingpress_add_appointment_after_sub_total_backend'); ?>
							<div class="bpa-aaf-pd__coupon-module" v-if="is_coupon_enable == 1 && bookingpress_allow_coupon_code == 1">
								<div class="bpa-aaf--bs__coupon-module-textbox" v-if="is_coupon_enable == '1' && coupon_applied_status != 'success'">
									<el-row>
										<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
											<span class="bpa-form-label"><?php esc_html_e( 'Have a coupon code?', 'bookingpress-appointment-booking' ); ?></span>
											<el-input class="bpa-form-control" v-model="appointment_formdata.applied_coupon_code" placeholder="<?php esc_html_e( 'Enter your coupon code', 'bookingpress-appointment-booking' ); ?>" :disabled="bpa_coupon_apply_disabled || bpa_multi_appoitnment_coupon_apply_disabled ==1"></el-input>
											<div class="bpa-bs__coupon-validation --is-error" v-if="coupon_applied_status == 'error' && coupon_code_msg != ''">
												<span class="material-icons-round">error_outline</span>
												<p>{{ coupon_code_msg }}</p>
											</div>
											<div class="bpa-bs__coupon-validation --is-success" v-if="coupon_applied_status == 'success' && coupon_code_msg != ''">
												<span class="material-icons-round">check_circle</span>
												<p>{{ coupon_code_msg }}</p>
											</div>
											<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary" @click="bookingpress_apply_coupon_code" :disabled="bpa_coupon_apply_disabled || bpa_multi_appoitnment_coupon_apply_disabled ==1">
												<span class="bpa-btn__label" v-if="bpa_coupon_apply_disabled == 0"><?php esc_html_e( 'Apply', 'bookingpress-appointment-booking' ); ?></span>
												<span class="bpa-btn__label" v-else><?php esc_html_e( 'Applied', 'bookingpress-appointment-booking' ); ?></span>
												<div class="bpa-btn--loader__circles">
													<div></div>
													<div></div>
													<div></div>
												</div>
											</el-button>
										</el-col>
									</el-row>
								</div>
								<div class="bpa-fm--bs-amount-item bpa-is-coupon-applied bpa-is-hide-stroke" v-if="is_coupon_enable == '1' && coupon_applied_status == 'success'">
									<el-row>
										<el-col :xs="24" :sm="24" :md="24" :lg="22" :xl="22">
											<h4>
												<?php esc_html_e( 'Coupon Applied', 'bookingpress-appointment-booking' ); ?>
												<span>{{ appointment_formdata.applied_coupon_code }}<a class="material-icons-round" @click="bookingpress_remove_coupon_code">close</a></span>		
											</h4>
										</el-col>
										<el-col :xs="24" :sm="24" :md="24" :lg="2" :xl="2">
											<h4 class="is-price">-{{ appointment_formdata.coupon_discounted_amount_with_currency }}</h4>
										</el-col>
									</el-row>
								</div>
							</div>
							<!-- for tip addon add do_action for fornt-end add appointment -->
							<?php do_action('bookingpress_add_content_after_subtotal_data_backend'); ?>
							<div v-if="(appointment_formdata.bookingpress_applied_deposit == '0' || appointment_formdata.bookingpress_deposit_payment_method == 'allow_customer_to_pay_full_amount') || (typeof appointment_formdata.bookingpress_package_applied_data != 'undefined' && appointment_formdata.bookingpress_package_applied_data != '') || (typeof appointment_formdata.bookingpress_gift_card_details != 'undefined' && appointment_formdata.bookingpress_gift_card_details != '')" class="bpa-aaf-pd__base-price-row bpa-aaf-pd__total-row">
								<div class="bpa-bpr__item">
									<h4><?php esc_html_e('Total', 'bookingpress-appointment-booking'); ?> <span v-if="appointment_formdata.tax_price_display_options == 'include_taxes'">{{ appointment_formdata.included_tax_label }}</span></h4>
									<h4 class="bpa-text--primary-color">{{ appointment_formdata.total_amount_with_currency }}</h4>
								</div>								
							</div>
							<div class="bpa-aaf-pd__mark-paid-checkbox" v-if="appointment_formdata.appointment_update_id == ''">
								<label class="bpa-form-label bpa-custom-checkbox--is-label"> 
									<el-checkbox v-model="appointment_formdata.mark_as_paid"></el-checkbox>
									<?php esc_html_e( 'Mark as paid', 'bookingpress-appointment-booking' ); ?>
								</label>
							</div>
						</div>
					</div>
				</el-col>
			</el-row>
		</div>
		<?php } ?>
	</div>
</el-dialog>

<el-dialog id="refund_confirm_process" custom-class="bpa-dialog bpa-dailog__small bpa-dialog--refund-process" title="" :visible.sync="refund_confirm_modal" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
				<h1 class="bpa-page-heading" ><?php esc_html_e( 'Refund', 'bookingpress-appointment-booking' ); ?></h1>
			</el-col>
		</el-row>
	</div>
	<div class="bpa-dialog-body">
		<el-container class="bpa-grid-list-container bpa-add-categpry-container">
			<div class="bpa-form-row">
				<el-row>
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
						<el-form ref="refund_confirm_form" :rules="rules_refund_confirm_form" :model="refund_confirm_form" label-position="top">
							<el-row>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
									<el-form-item prop="allow_refund" class="bpa-appointment-rp__allow-refund-row">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e( 'Give refund upon cancellation?', 'bookingpress-appointment-booking' ); ?></span>
										</template>
										<el-switch class="bpa-swtich-control" v-model="refund_confirm_form.allow_refund"></el-switch>
									</el-form-item>
								</el-col>								
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="refund_confirm_form.allow_refund == true">
									<el-form-item prop="refund_type">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e( 'Refund Type', 'bookingpress-appointment-booking' ); ?></span>
										</template>
										<el-radio v-model="refund_confirm_form.refund_type" label="full"><?php esc_html_e( 'Full refund', 'bookingpress-appointment-booking' ); ?></el-radio>
										<el-radio v-model="refund_confirm_form.refund_type" label="partial" v-if="refund_confirm_form.allow_partial_refund == 1"><?php esc_html_e( 'Partial refund', 'bookingpress-appointment-booking' ); ?></el-radio>
										<el-radio v-model="refund_confirm_form.refund_type" label="partial" disabled v-else><?php esc_html_e( 'Partial refund', 'bookingpress-appointment-booking' ); ?></el-radio>
									</el-form-item>
								</el-col>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="refund_confirm_form.refund_type == 'partial' && refund_confirm_form.allow_refund == true">
									<el-form-item prop="refund_amount">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e( 'Refund Amount', 'bookingpress-appointment-booking' ); ?> ({{refund_confirm_form.refund_currency}})</span>
										</template>										
										<el-input @input="isValidateZeroDecimal" class="bpa-form-control" v-model="refund_confirm_form.refund_amount"></el-input>
									</el-form-item>
								</el-col>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="refund_confirm_form.allow_refund == true">
									<el-form-item prop="refund_reason">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e( 'Refund Reason', 'bookingpress-appointment-booking' ); ?></span>
										</template>
										<el-input class="bpa-form-control" v-model="refund_confirm_form.refund_reason" placeholder="<?php esc_html_e('Refund Reason','bookingpress-appointment-booking') ?>" type="textarea" :rows="3"></el-input>
									</el-form-item>
								</el-col>								
							</el-row>
						</el-form>
					</el-col>
				</el-row>
			</div>
		</el-container>
	</div>
	<div class="bpa-dialog-footer">
		<div class="bpa-hw-right-btn-group">
			<el-button class="bpa-btn bpa-btn__small" @click="close_refund_confirm_model"><?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?></el-button>
			<el-button class="bpa-btn bpa-btn__small bpa-btn--primary" :class="(is_display_refund_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="bookingpress_apply_for_refund(refund_confirm_form.payment_id,refund_confirm_form.appointment_id)" :disabled="is_refund_btn_disabled">
				<span class="bpa-btn__label"><?php esc_html_e( 'Apply', 'bookingpress-appointment-booking' ); ?></span>
				<div class="bpa-btn--loader__circles">				    
					<div></div>
					<div></div>
					<div></div>
				</div>
			</el-button>
		</div>
	</div>
</el-dialog>

<el-dialog id="note_confirm_modal" custom-class="bpa-dialog bpa-dailog__small bpa-dialog--admin-note" title="" :visible.sync="note_confirm_modal" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
				<h1 class="bpa-page-heading" ><?php esc_html_e( 'Note', 'bookingpress-appointment-booking' ); ?></h1>
			</el-col>
		</el-row>
	</div>
	<div class="bpa-dialog-body">
		<el-container class="bpa-grid-list-container bpa-add-categpry-container">
			<div class="bpa-form-row">
				<el-row>
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
						<el-form ref="admin_note_confirm_form" :model="admin_note_confirm_form" label-position="top">
							<el-row>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
									<el-form-item prop="bookingpress_add_admin_note">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e( 'Admin Note', 'bookingpress-appointment-booking' ); ?></span>
										</template>
										<el-input class="bpa-form-control" v-model="admin_note_confirm_form.bookingpress_add_admin_note" placeholder="<?php esc_html_e('Add Admin note','bookingpress-appointment-booking') ?>" type="textarea" :rows="6"></el-input>
									</el-form-item>
								</el-col>								
							</el-row>
						</el-form>
					</el-col>
				</el-row>
			</div>
		</el-container>
	</div>
	<div class="bpa-dialog-footer">
		<div class="bpa-hw-right-btn-group">
			<el-button class="bpa-btn bpa-btn__small bpa-btn--primary" :class="(is_display_admin_note_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="bookingpress_add_note(admin_note_confirm_form.payment_id,admin_note_confirm_form.appointment_id)" :disabled="is_admin_note_btn_disabled">
				<span class="bpa-btn__label"><?php esc_html_e( 'Save', 'bookingpress-appointment-booking' ); ?></span>
				<div class="bpa-btn--loader__circles">				    
					<div></div>
					<div></div>
					<div></div>
				</div>
			</el-button>
		</div>
	</div>
</el-dialog>
<?php do_action('bookingpress_manage_dashboard_view_bottom'); ?>