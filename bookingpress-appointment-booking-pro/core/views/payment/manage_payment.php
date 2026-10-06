<?php
global $bookingpress_ajaxurl, $bookingpress_common_date_format, $BookingPressPro, $bookingpress_global_options, $BookingPress;

$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
$bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
$bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

?>
<el-main class="bpa-main-listing-card-container bpa-default-card bpa--is-page-non-scrollable-mob" :class="(bookingpress_staff_customize_view == 1 ) ? 'bpa-main-list-card__is-staff-custom-view':''" id="all-page-main-container">
	<el-row type="flex" class="bpa-mlc-head-wrap">
		<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="bpa-mlc-left-heading">
			<h1 class="bpa-page-heading"><?php esc_html_e('Manage Payments', 'bookingpress-appointment-booking'); ?></h1>
		</el-col>
	</el-row>
	<div class="bpa-back-loader-container" id="bpa-page-loading-loader">
		<div class="bpa-back-loader"></div>
	</div>
	<div id="bpa-main-container">
		<div class="bpa-table-filter">
			<el-row type="flex" :gutter="32">
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
					<span class="bpa-form-label"><?php esc_html_e('Transaction Date', 'bookingpress-appointment-booking'); ?></span>
					<el-date-picker @focus="bookingpress_remove_date_range_picker_focus" class="bpa-form-control bpa-form-control--date-range-picker" :format="bpa_date_common_date_format" v-model="search_data.search_range" type="daterange"
						start-placeholder="<?php esc_html_e('Start date', 'bookingpress-appointment-booking'); ?>" end-placeholder="<?php esc_html_e('End Date', 'bookingpress-appointment-booking'); ?>" value-format="yyyy-MM-dd" :popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar bpa-date-range-picker-widget-wrapper" range-separator=" - " :picker-options="filter_pickerOptions"></el-date-picker>
				</el-col>
				<?php if (! $BookingPressPro->bookingpress_check_user_role('bookingpress-staffmember')) { ?>
					<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6" v-if="is_staffmember_activated == 1">
						<span class="bpa-form-label"><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " " . esc_html($bookingpress_plural_staffmember_name); ?></span>
						<el-select class="bpa-form-control bpa-from-select-tab" v-model="search_data.search_staff_member" multiple filterable collapse-tags
							placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " " . esc_html($bookingpress_plural_staffmember_name); ?>"
							:popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
							<el-option v-for="item in search_staffmember_data" :key="item.value" :label="item.text" :value="item.value"></el-option>
						</el-select>
					</el-col>
				<?php } ?>
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
					<span class="bpa-form-label"><?php esc_html_e('Customer Name', 'bookingpress-appointment-booking'); ?></span>
					<el-select class="bpa-form-control bpa-from-select-tab" v-model="search_data.search_customer" multiple filterable collapse-tags placeholder="<?php esc_html_e('Start typing to fetch Customer', 'bookingpress-appointment-booking'); ?>" remote reserve-keyword :remote-method="bookingpress_get_search_customer_list" :loading="boookingpress_loading" :popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
						<el-option v-for="item in search_customer_data" :key="item.value" :label="item.text" :value="item.value"></el-option>
					</el-select>
				</el-col>
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
					<span class="bpa-form-label"><?php esc_html_e('Service', 'bookingpress-appointment-booking'); ?></span>
					<el-select class="bpa-form-control bpa-from-select-tab" v-model="search_data.search_service" multiple filterable collapse-tags
						placeholder="<?php esc_html_e('Select Service', 'bookingpress-appointment-booking'); ?>"
						:popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
						<el-option-group v-for="item in search_services_data" :key="item.category_name" :label="item.category_name">
							<el-option v-for="cat_services in item.category_services" :key="cat_services.service_id" :label="cat_services.service_name" :value="cat_services.service_id"></el-option>
						</el-option-group>
					</el-select>
				</el-col>
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
					<span class="bpa-form-label"><?php esc_html_e('Payment Status', 'bookingpress-appointment-booking'); ?></span>
					<el-select class="bpa-form-control bpa-from-select-tab" v-model="search_data.search_status" filterable
						placeholder="<?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?>"
						:popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
						<el-option label="<?php esc_html_e('All', 'bookingpress-appointment-booking'); ?>" value="all"></el-option>
						<el-option v-for="item_data in payment_status_data" :key="item_data.text" :label="item_data.text" :value="item_data.value"></el-option>
					</el-select>
				</el-col>
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="6">
					<div class="bpa-tf-btn-group">
						<el-button class="bpa-btn bpa-btn__medium bpa-btn--full-width bpa_reset_payment_btn" @click="resetFilter">
							<?php esc_html_e('Reset', 'bookingpress-appointment-booking'); ?>
						</el-button>
						<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary bpa-btn--full-width" @click="loadPayments(true)">
							<?php esc_html_e('Apply', 'bookingpress-appointment-booking'); ?>
						</el-button>
						<el-button class="bpa-btn bpa-btn--secondary bpa-btn__medium bpa-btn--full-width" @click="Bookingpress_export_payment_data" v-if="bookingpress_export_payment == 1">
							<span class="material-icons-round">open_in_new</span><?php esc_html_e('Export', 'bookingpress-appointment-booking'); ?>
						</el-button>
					</div>
				</el-col>
			</el-row>
		</div>
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

		<el-row v-if="items.length > 0">
			<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
				<el-container class="bpa-table-container --bpa-is-payments-screen">
					<div class="bpa-back-loader-container bpa-back-loader-inner-container" v-if="is_display_loader == '1'">
						<div class="bpa-back-loader"></div>
					</div>
					<div class="bpa-tc__wrapper" v-if="current_screen_size == 'desktop'">
						<el-table ref="multipleTable" :data="items" class="bpa-manage-payment-items" @sort-change="handel_payment_appointment_data" @selection-change="handleSelectionChange" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
							<el-table-column type="expand">
								<template slot-scope="scope">
									<div class="bpa-view-payment-card">
										<div class="bpa-vpc--head">
											<div class="bpa-vpc--head__left">
												<h2><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h2>
											</div>
											<div class="bpa-hw-right-btn-group bpa-vpc--head__right" v-if="( ( bpa_chk_staff_role != 1 && scope.row.payment_refund_status == 1) || ( bpa_chk_staff_role == 1 && bpa_staff_refund_cap == 1 && scope.row.payment_refund_status == 1 ) )">
												<el-button class="bpa-btn bpa-btn__refund" @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_log_id,scope.row.appointment_currency_symbol,scope.row.payment_partial_refund)">
													<svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
														<path d="M12.1471 5.67946H15.6684V1.96777" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M16.56 9.18573C16.56 13.2612 13.2555 16.5639 9.18001 16.5639C5.1045 16.5639 1.79999 13.2594 1.79999 9.18384C1.79999 5.10834 5.1045 1.80005 9.18001 1.80005C11.9869 1.80005 14.4299 3.36654 15.6759 5.67009" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M7.54754 10.7106C7.54754 11.6117 8.27894 12.3431 9.18 12.3431C10.0811 12.3431 10.8125 11.6117 10.8125 10.7106C10.8125 9.80957 10.0811 9.07816 9.17812 9.07816C8.27517 9.07816 7.54565 8.34676 7.54565 7.4457C7.54565 6.54464 8.27706 5.81323 9.18 5.81323C10.0811 5.81323 10.8144 6.54464 10.8144 7.4457" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 13.6685V12.3452" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 5.81135V4.48804" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
													</svg>
													<?php esc_html_e('Refund', 'bookingpress-appointment-booking'); ?>
												</el-button>
												<?php
												do_action('bookingpress_add_dynamic_buttons_for_view_payments');
												?>
											</div>
										</div>
										<div class="bpa-vpc--body">
											<div class="bpa-vpc__customer-summary-wrapper">
												<div class="bpa-vpc-csw__item">
													<div class="bpa-csw--customer-info">
														<div class="bpa-ci--avatar">
															<img :src="scope.row.customer_avatar">
														</div>
														<div class="bpa-ci--body">
															<h4 v-if="scope.row.customer_firstname != '' && scope.row.customer_lastname != ''">{{ scope.row.customer_firstname }} {{ scope.row.customer_lastname }}</h4>
															<p>{{ scope.row.customer_email }}</p>
														</div>
													</div>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Mode', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.payment_gateway_label }}</p>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Transaction ID', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.transaction_id }}</p>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?></h4>
													<p :class="[(scope.row.payment_status == '1') ? 'bpa-cl-pt-main-green' : '', (scope.row.payment_status == '2') ? 'bpa-cl-sc-warning' : '', (scope.row.payment_status == '4') ? 'bpa-cl-pt-blue' : '', (scope.row.payment_status == '3') ? 'bpa-cl-danger' : '']">{{ scope.row.payment_status_label }}</p>
												</div>
												<?php
												do_action('bookingpress_add_dynamic_buttons_for_payment_details');
												?>
											</div>
											<div v-if="((scope.row.is_package_purchase == 'undefined') || (scope.row.is_package_purchase != 'undefined' && scope.row.is_package_purchase != '1')) && ((scope.row.is_gift_card_purchase == 'undefined') || (scope.row.is_gift_card_purchase != 'undefined' && scope.row.is_gift_card_purchase != '1'))" class="bpa-vpc__appointment-details">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Appointment Details', 'bookingpress-appointment-booking'); ?></h4>
												<template>
													<div class="bpa-vac-ap--items bpa-vac-ap--grid-items" :class="[(('1' == is_staffmember_activated && '1' != scope.row.is_deposit_enable) ? 'bpa-vac-ap--grid-items-with-staff' : ''), (('1' == scope.row.is_deposit_enable && '1' != is_staffmember_activated) ? 'bpa-vac-ap--grid-items-with-deposit' : '' ), ( ('1' == scope.row.is_deposit_enable && 1 == is_staffmember_activated ) ? 'bpa-vac-ap--grid-items-with-staff-deposit' : '' )]">
														<div class="bpa-ap-item__head">
															<div class="bpa-ih--item">
																<h4><?php esc_html_e('Sr.', 'bookingpress-appointment-booking'); ?></h4>
															</div>
															<div class="bpa-ih--item bpa-ih--item-service-name-cell">
																<h4><?php esc_html_e('Service', 'bookingpress-appointment-booking'); ?></h4>
															</div>
															<div class="bpa-ih--item">
																<h4><?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?></h4>
															</div>
															<div class="bpa-ih--item">
																<h4><?php esc_html_e('Time', 'bookingpress-appointment-booking'); ?></h4>
															</div>
															<div class="bpa-ih--item" v-show="is_staffmember_activated == '1'">
																<h4><?php echo esc_html($bookingpress_singular_staffmember_name); ?></h4>
															</div>
															<div class="bpa-ih--item bpa-ib--item-service-deposit-cell" v-show="scope.row.is_deposit_enable == '1'">
																<h4><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></h4>
															</div>
															<div class="bpa-ih--item bpa-ib--item-service-price-cell">
																<h4><?php esc_html_e('Price', 'bookingpress-appointment-booking'); ?></h4>
															</div>
														</div>
													</div>
													<div class="bpa-vac-ap--grid-items" :class="[(('1' == is_staffmember_activated && '1' != scope.row.is_deposit_enable) ? 'bpa-vac-ap--grid-items-with-staff' : ''), (('1' == scope.row.is_deposit_enable && '1' != is_staffmember_activated) ? 'bpa-vac-ap--grid-items-with-deposit' : '' ), ( ('1' == scope.row.is_deposit_enable && 1 == is_staffmember_activated ) ? 'bpa-vac-ap--grid-items-with-staff-deposit' : '' )]">
														<div class="bpa-ap-item__body">
															<div class="bpa-ib--item-card" :class="(true == appointment_details.is_service_extra_row || true == appointment_details.is_service_package_row ) ? 'bpa-ib--item-card-extras' : ''" v-for="appointment_details in scope.row.appointment_details_arr2">
																<template v-if="false == appointment_details.is_service_extra_row">
																	<div class="bpa-ib--item">
																		<p>{{appointment_details.appointment_index}}</p>
																	</div>
																	<div class="bpa-ib--item bpa-ih--item-service-name-cell">
																		<p>{{ appointment_details.bookingpress_service_name }}</p>
																		<!-- <div class="bpa-ap__service-extras" v-if="appointment_details.extra_service_details.length > 0">
																			<p v-for="appointment_extra_details in appointment_details.extra_service_details">
																				{{ appointment_extra_details.selected_qty }} x {{ appointment_extra_details.extra_name }}
																			</p>
																		</div>
																		<div class="bpa-ap__service-extras bpa-ap__pack-title" v-if="appointment_details.bookingpress_purchase_type == 3 && appointment_details.bookingpress_package_discount_amount_with_currency != 'undefined' && appointment_details.bookingpress_package_discount_amount_with_currency != 0 && typeof appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name != 'undefined'">
																			<p>{{ appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name }}</p> 
																		</div> -->
																	</div>
																	<div class="bpa-ib--item">
																		<p>{{ appointment_details.bookingpress_appointment_date }}</p>
																	</div>
																	<div class="bpa-ib--item">
																		<p v-if="scope.row.appointment_start_time != ''">{{ appointment_details.bookingpress_appointment_time }} <?php esc_html_e('to', 'bookingpress-appointment-booking'); ?> {{ appointment_details.bookingpress_appointment_end_time }}</p>
																	</div>

																	<!-- <div class="bpa-ib--item">
																		<div class="bpa-ib__amount-row">
																			<div class="bpa-ar__body">
																				<p>{{ appointment_details.bookingpress_service_price_with_currency }}</p>
																				<div class="bpa-ap__service-extras-price" v-if="appointment_details.extra_service_details.length > 0">
																					<p v-for="appointment_extra_details in appointment_details.extra_service_details">
																						{{ appointment_extra_details.extra_service_price_with_currency }}
																					</p>
																				</div>
																				<div class="bpa-ap__service-extras-price bpa-ap__pack-price" v-if="appointment_details.bookingpress_purchase_type == 3 && appointment_details.bookingpress_package_discount_amount_with_currency != 'undefined' && appointment_details.bookingpress_package_discount_amount_with_currency != 0">
																					<p> - {{ appointment_details.bookingpress_package_discount_amount_with_currency }}</p>
																				</div>	
																			</div>
																			<div class="bpa-ar__icons">
																				<el-tooltip content="< ?php esc_html_e('Total Persons', 'bookingpress-appointment-booking'); ?>" placement="top">
																					<p v-if="appointment_details.bookingpress_selected_extra_members > 1">
																						<span class="material-icons-round">account_circle</span>
																						{{ appointment_details.bookingpress_selected_extra_members }} 
																					</p>
																				</el-tooltip>
																			</div>
																		</div>
																	</div> -->
																	<div class="bpa-ib--item" v-show="is_staffmember_activated == '1'">
																		<div class="bpa-ib-item__staff-info" v-show="(appointment_details.bookingpress_staff_member_id != '0' || scope.row.staff_member_name != '')">
																			<img v-if="appointment_details.bookingpress_staff_member_id != '0'" :src="appointment_details.staff_avatar_url">
																			<p v-if="appointment_details.bookingpress_staff_member_id != '0'">{{ appointment_details.bookingpress_staff_first_name }} {{ appointment_details.bookingpress_staff_last_name }}</p>
																			<p v-else>{{ scope.row.staff_member_name }} </p>
																		</div>
																	</div>
																	<div class="bpa-ib--item bpa-ib--item-service-deposit-cell" v-if="appointment_details.is_deposit_applied == '1' || '1' == scope.row.is_deposit_enable">
																		<p>{{ appointment_details.bookingpress_deposit_amount_with_currency_symbol }}</p>
																	</div>
																	<div class="bpa-ib--item bpa-ib--item-service-price-cell">
																		<p>{{appointment_details.bookingpress_service_price_with_currency}}</p>
																	</div>
																</template>
																<template v-if="true == appointment_details.is_service_extra_row">
																	<div class="bpa-ib--item bpa-service-extra-name bpa-ib--item-service-name-cell">
																		<p>{{appointment_details.selected_qty}} x {{appointment_details.extra_name}}</p>
																	</div>
																	<div class="bpa-ib--item bpa-service-extra-price bpa-ib--item-service-price-cell">
																		<p>{{appointment_details.extra_service_price_with_currency}}
																	</div>
																</template>
																<template v-if="true == appointment_details.is_service_package_row">
																	<div class="bpa-ib--item bpa-ib--item-service-name-cell bpa-service-package-name">
																		<p>{{appointment_details.package_name}}</p>
																	</div>
																	<div class="bpa-ib--item bpa-service-package-price bpa-ib--item-service-price-cell">
																		<p> - {{appointment_details.package_discount}}
																	</div>
																</template>
															</div>
														</div>
													</div>
												</template>
											</div>
											<div class="bpa-vpc__payment-summary-wapper" :class="( ('undefined' != typeof scope.row.is_multi_service_booking && scope.row.is_multi_service_booking == 1) ? 'bpa-vac-body--multiservice-summary-details' : '') ">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Payment Summary', 'bookingpress-appointment-booking'); ?></h4>
												<div class="bpa-psw--body">
													<div class="bpa-psw--body-row">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Subtotal', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount" v-if="typeof scope.row.subtotal_amount_discounted != 'undefined' && scope.row.subtotal_amount_discounted != 0">
																<p>{{scope.row.subtotal_amount_with_discounted_currency }}</p>
															</div>
															<div class="bpa-psw__item-amount" v-else>
																<p>{{scope.row.subtotal_amount_with_currency }}</p>
															</div>
														</div>
														<?php do_action('bookingpress_payment_page_after_subtotal'); ?>
														<div class="bpa-psw__item" v-if="scope.row.is_deposit_enable == '1' || (scope.row.bookingpress_payment_adjustment_amount != '' && scope.row.payment_status == '4')">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.payment_amount }}</p>
															</div>
														</div>

														<div class="bpa-psw__item" v-if="scope.row.bookingpress_purchase_type == 1">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.bookingpress_payment_adjustment_amount_with_currency }}</p>
															</div>
														</div>

														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Due Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount bookingpress_due_amount">
																<p>{{ scope.row.due_amount_with_currency }}</p>
															</div>
														</div>

														<div class="bpa-psw__item" v-if="(scope.row.payment_status == '5' || scope.row.payment_status == '3')">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Refunded Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.refund_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-psw--body-row__tax-item" :class="( (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ) ) && scope.row.applied_coupon_code == '') ? 'bpa-psw--body-row__tax-item-excluded' : ''" v-if="scope.row.tax_amount != ''">
														<div class="bpa-psw__item" v-if="(scope.row.tax_amount != '') && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ))">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>+{{ scope.row.tax_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row" v-if="scope.row.applied_coupon_code != ''">
														<div class="bpa-psw__item --bpa-is-coupon-item" v-if="scope.row.applied_coupon_code != ''">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>-{{ scope.row.coupon_discount_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<?php do_action('bookingpress_modify_payment_managepayment_section') ?>
													<div class="bpa-psw--body-row --bpa-is-total-row -bpa-commission-enabled-total-row" class="bpa-vpc__payment-summary-wapper">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p>
																	<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?>
																	<span class="bpa-psw-ba__included-tax-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</span>
																</p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.total_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-row" v-if="scope.row.staff_commission_details && scope.row.staff_commission_details.length">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<el-popover placement="bottom-start" width="280" trigger="hover" popper-class="bpa-commission-popover" v-if="scope.row.staff_commission_details && 1<scope.row.staff_commission_details.length">
																	<div class="bpa-commission-wrapper">
																		<div class="bpa-commission-title"><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></div>
																		<div
																			class="bpa-commission-row"
																			v-for="(staff, index) in scope.row.staff_commission_details"
																			:key="index">
																			<span>{{ staff.staff_name }}</span>
																			<span>{{ staff.commission_amount }}</span>
																		</div>
																		<div class="bpa-commission-total">
																			<span>Total Commission</span>
																			<span class="bpa-total-amount">
																				{{ scope.row.staff_commission_amount_with_currency }}
																			</span>
																		</div>
																	</div>
																	<p slot="reference" style="cursor:pointer;">
																		-{{ scope.row.staff_commission_amount_with_currency }}
																	</p>
																</el-popover>
																<p v-else>-{{ scope.row.staff_commission_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-net-revenue" v-if="0<scope.row.staff_commission_amount">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Balance Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.staff_commission_net_revenue_with_currency }}</p>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</template>
							</el-table-column>
							<el-table-column type="selection"></el-table-column>
							<el-table-column prop="payment_date" min-width="80" label="<?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?>" sortable="false" sort-by="sort_payment_date"></el-table-column>
							<el-table-column prop="payment_customer" min-width="100" label="<?php esc_html_e('Customer', 'bookingpress-appointment-booking'); ?>" sortable="false"></el-table-column>
							<?php if (! $BookingPressPro->bookingpress_check_user_role('bookingpress-staffmember')) { ?>
								<el-table-column prop="staff_member_name" min-width="100" label="<?php echo esc_html($bookingpress_singular_staffmember_name); ?>" sortable="false" v-if="(is_staffmember_activated == 1 && typeof is_multi_staffmember_activated == 'undefined')"></el-table-column>
								<?php do_action('bookingpress_after_staff_members_column', $bookingpress_singular_staffmember_name); ?>
							<?php } ?>
							<el-table-column prop="payment_service" min-width="100" label="<?php
																							$bookingpress_payment_transaction_item_label = esc_html__('Service', 'bookingpress-appointment-booking');
																							$bookingpress_payment_transaction_item_label = apply_filters('bookingpress_payment_transaction_item_label', $bookingpress_payment_transaction_item_label);
																							echo esc_html($bookingpress_payment_transaction_item_label);
																							?>" sortable="false"><?php do_action('bookingpress_multiple_service_name_payment_section_fetch'); ?></el-table-column>
							<el-table-column prop="payment_gateway" min-width="70" label="<?php esc_html_e('Method', 'bookingpress-appointment-booking'); ?>">
								<template slot-scope="scope">
									<div class="bpa-mpg__body">
										<p> {{scope.row.payment_gateway_label}}</p>
										<span v-if="scope.row.payment_gateway == 'manual'">(<?php esc_html_e('booked by admin', 'bookingpress-appointment-booking'); ?>)</span>
									</div>
								</template>
							</el-table-column>
							<el-table-column prop="payment_status" min-width="80" label="<?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?>">
								<template slot-scope="scope">
									<div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''" v-if="bookingpress_edit_payment == 1">
										<div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
											<div class="bpa-btn--loader__circles">
												<div></div>
												<div></div>
												<div></div>
											</div>
										</div>
										<el-select class="bpa-form-control" :class="((scope.row.payment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.payment_status == '3' ? 'bpa-appointment-status--rejected' : '') || (scope.row.payment_status == '4' ? 'bpa-appointment-status--approved' : '') || (scope.row.payment_status == '1' ? 'bpa-appointment-status--completed' : '') || (scope.row.payment_status == '5' ? 'bpa-appointment-status--refund-partial' : '')" v-model="scope.row.payment_status" placeholder="<?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?>" popper-class="bpa-payment-status-dropdown-popper" @change="bookingpress_change_status(scope.row.payment_log_id, $event)">
											<el-option-group label="<?php esc_html_e('Change status', 'bookingpress-appointment-booking'); ?>">
												<el-option v-for="item in payment_status_data" :key="item.value" :label="item.text" :value="item.value"></el-option>
											</el-option-group>
										</el-select>
									</div>
									<el-tag class="bpa-front-pill " :class="((scope.row.payment_status == '2') ? '--warning' : '') || (scope.row.payment_status == '3' ? '--rejected' : '') || (scope.row.payment_status == '4' ? '--approved' : '') || (scope.row.payment_status == '1' ? '--completed' : '')" v-else>{{ scope.row.payment_status_label }}</el-tag>
								</template>
							</el-table-column>
							<el-table-column prop="payment_amount" min-width="100" label="<?php esc_html_e('Amount', 'bookingpress-appointment-booking'); ?>" sort-by="payment_numberic_amount">
								<template slot-scope="scope">
									<div class="bpa-mpi__amount-row">
										<div class="bpa-mpi__ar-body">
											<!-- <span class="bpa-mpi__amount" v-if="scope.row.is_deposit_enable == '1' && scope.row.payment_status == '4'">{{ scope.row.deposit_amount_with_currency }}</span> -->
											<span v-if="scope.row.is_deposit_enable == 1 && scope.row.payment_status == '4'" class="bpa-mpi__amount">{{ scope.row.payment_amount }}</span>
											<span v-else class="bpa-mpi__amount">{{scope.row.total_amount_with_currency}}</span>
											<span v-if="scope.row.is_deposit_enable == 1 && scope.row.payment_status == '4'" class="bpa-is-deposit-payment-val"><?php esc_html_e('of', 'bookingpress-appointment-booking'); ?> {{ scope.row.total_amount_with_currency }}</span>
										</div>
										<div class="bpa-mpi__ar-icons">
											<?php do_action('bookingpress_backend_payment_list_type_icons'); ?>
											<el-tooltip content="<?php esc_html_e('Past Modified Appointment', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_past_appointment_edited == 1">
												<span class="bpa-apc__past-edited-icon">
													<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M16.436 2.02543C16.1558 2.09391 15.8632 2.29311 15.5395 2.62305L15.2656 2.90319L16.2554 3.89923L17.2515 4.89526L17.5814 4.56533C17.9923 4.16069 18.1541 3.83697 18.1541 3.41366C18.1541 2.85961 17.8802 2.39894 17.4071 2.15616C17.1394 2.0192 16.6974 1.96318 16.436 2.02543Z" fill="#535D71" />
														<path d="M7.88861 3.12088C6.27005 3.44459 4.69506 4.39705 3.58075 5.7168C2.72789 6.73152 2.11782 8.07617 1.92483 9.37724C1.86881 9.75075 1.86881 11.4067 1.92483 11.693C2.09291 12.5148 2.21742 12.9505 2.4851 13.5668C3.90446 16.8787 7.38436 18.5781 11.1569 17.8124C11.7918 17.6817 12.8812 17.2148 13.5224 16.7977C14.3193 16.281 15.1597 15.4406 15.6826 14.6313C16.2429 13.766 16.7409 12.384 16.8343 11.4627C16.8716 11.0269 16.8841 11.0083 17.0584 11.0394C17.2887 11.083 17.4755 10.9585 17.4755 10.7655C17.4755 10.6347 17.3074 10.4293 16.7035 9.83168C16.2616 9.38969 15.8818 9.06598 15.8133 9.06598C15.7449 9.06598 15.3651 9.40214 14.9044 9.86903C14.2757 10.4978 14.1201 10.6908 14.1512 10.7841C14.2321 11.0083 14.3193 11.0705 14.4998 11.0332C14.7052 10.9896 14.7115 11.0269 14.5807 11.6432C14.064 14.096 12.0222 15.7768 9.5383 15.8079C8.51113 15.8204 7.84503 15.6772 6.9735 15.2601C5.41719 14.5068 4.48963 13.2369 4.09744 11.3382C3.97916 10.753 4.03519 9.91261 4.24685 9.00373C4.49586 7.95789 5.42964 6.70661 6.4568 6.04674C7.16648 5.5923 7.68318 5.39309 8.52981 5.23746C8.79749 5.18766 9.0963 5.08805 9.20213 5.01958C9.62545 4.71454 9.77486 3.94261 9.48849 3.51929C9.18346 3.05863 8.73524 2.9528 7.88861 3.12088Z" fill="#535D71" />
														<path d="M11.7973 6.37663L8.76562 9.40832L9.76166 10.4044L10.7577 11.4004L13.8081 8.35003L16.8584 5.29966L15.8811 4.3223C15.3395 3.7807 14.885 3.33871 14.8664 3.33871C14.8477 3.33871 13.4719 4.70204 11.7973 6.37663Z" fill="#535D71" />
														<path d="M7.87285 11.1515C7.23788 12.6207 7.22543 12.6456 7.33126 12.7701C7.48066 12.9444 7.67987 12.8822 9.41671 12.1227C9.55367 12.0604 9.79645 11.9546 9.95831 11.8923C10.1264 11.8301 10.2509 11.7554 10.2384 11.7305C10.226 11.6931 8.44558 9.86914 8.4269 9.87536C8.42067 9.87536 8.17166 10.4543 7.87285 11.1515Z" fill="#535D71" />
													</svg>
												</span>
											</el-tooltip>
											<el-tooltip content="<?php esc_html_e('Cart Transaction', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_cart == '1'">
												<span class="material-icons-round">shopping_cart</span>
											</el-tooltip>
											<el-tooltip content="<?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_deposit_enable == '1'">
												<span class="bpa-apc__deposit-icon">
													<svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M16.9596 12.2237C16.8902 12.0273 16.746 11.8662 16.5583 11.7756C16.3706 11.685 16.1548 11.6723 15.9578 11.7402L13.7872 12.4116C13.2376 12.9125 13.0288 12.7838 9.00068 12.7838C8.90842 12.7838 8.81994 12.7471 8.75471 12.6819C8.68947 12.6167 8.65282 12.5282 8.65282 12.4359C8.65282 12.3437 8.68947 12.2552 8.75471 12.1899C8.81994 12.1247 8.90842 12.0881 9.00068 12.0881C13.1749 12.0881 13.0323 12.1681 13.3384 11.862C13.4551 11.7331 13.5206 11.5661 13.5228 11.3923C13.5228 11.2078 13.4495 11.0309 13.319 10.9004C13.1886 10.7699 13.0116 10.6966 12.8271 10.6966H9.10504C8.62152 10.6966 8.21801 10.0009 6.80919 10.0009H4.47856V13.6256L4.92729 13.8795C6.20362 14.6153 7.63128 15.0496 9.10115 15.149C10.571 15.2485 12.0442 15.0106 13.408 14.4535L16.5387 13.1908C16.7162 13.1104 16.8576 12.967 16.9354 12.7883C17.0132 12.6096 17.0218 12.4084 16.9596 12.2237ZM1 14.523H3.78285V9.30521H1V14.523ZM2.0714 12.9994C2.09103 12.9518 2.12099 12.9092 2.1591 12.8746C2.19722 12.84 2.24255 12.8142 2.29181 12.7993C2.34107 12.7843 2.39304 12.7805 2.44398 12.788C2.49491 12.7956 2.54353 12.8143 2.58633 12.8429C2.62913 12.8716 2.66504 12.9093 2.69147 12.9535C2.71791 12.9977 2.7342 13.0472 2.73919 13.0984C2.74417 13.1497 2.73771 13.2014 2.72028 13.2499C2.70285 13.2983 2.67489 13.3423 2.6384 13.3786C2.58145 13.4353 2.50661 13.4705 2.42662 13.4783C2.34663 13.4861 2.26641 13.4659 2.1996 13.4213C2.13279 13.3766 2.08351 13.3102 2.06014 13.2333C2.03677 13.1564 2.04074 13.0737 2.0714 12.9994ZM11.4357 8.95736C12.1237 8.95736 12.7962 8.75334 13.3683 8.37112C13.9403 7.98889 14.3862 7.44561 14.6494 6.80999C14.9127 6.17437 14.9816 5.47494 14.8474 4.80017C14.7132 4.1254 14.3819 3.50558 13.8954 3.01909C13.4089 2.53261 12.7891 2.20131 12.1143 2.06709C11.4395 1.93286 10.7401 2.00175 10.1045 2.26504C9.46886 2.52832 8.92558 2.97417 8.54336 3.54622C8.16113 4.11827 7.95711 4.79081 7.95711 5.4788C7.95711 6.40137 8.3236 7.28616 8.97596 7.93851C9.62831 8.59087 10.5131 8.95736 11.4357 8.95736ZM11.7835 5.82666H11.0878C10.811 5.82666 10.5456 5.71671 10.3499 5.521C10.1542 5.3253 10.0442 5.05986 10.0442 4.78309C10.0442 4.50632 10.1542 4.24088 10.3499 4.04518C10.5456 3.84947 10.811 3.73952 11.0878 3.73952V3.39167C11.0878 3.29941 11.1245 3.21093 11.1897 3.1457C11.2549 3.08046 11.3434 3.04381 11.4357 3.04381C11.5279 3.04381 11.6164 3.08046 11.6816 3.1457C11.7469 3.21093 11.7835 3.29941 11.7835 3.39167V3.73952H12.4792C12.5715 3.73952 12.66 3.77617 12.7252 3.84141C12.7904 3.90664 12.8271 3.99512 12.8271 4.08738C12.8271 4.17964 12.7904 4.26812 12.7252 4.33335C12.66 4.39859 12.5715 4.43524 12.4792 4.43524H11.0878C10.9956 4.43524 10.9071 4.47188 10.8418 4.53712C10.7766 4.60236 10.74 4.69083 10.74 4.78309C10.74 4.87535 10.7766 4.96383 10.8418 5.02906C10.9071 5.0943 10.9956 5.13095 11.0878 5.13095H11.7835C12.0603 5.13095 12.3257 5.24089 12.5214 5.4366C12.7171 5.63231 12.8271 5.89774 12.8271 6.17451C12.8271 6.45128 12.7171 6.71672 12.5214 6.91243C12.3257 7.10813 12.0603 7.21808 11.7835 7.21808V7.56594C11.7835 7.65819 11.7469 7.74667 11.6816 7.81191C11.6164 7.87714 11.5279 7.91379 11.4357 7.91379C11.3434 7.91379 11.2549 7.87714 11.1897 7.81191C11.1245 7.74667 11.0878 7.65819 11.0878 7.56594V7.21808H10.3921C10.2998 7.21808 10.2114 7.18143 10.1461 7.1162C10.0809 7.05096 10.0442 6.96248 10.0442 6.87022C10.0442 6.77797 10.0809 6.68949 10.1461 6.62425C10.2114 6.55902 10.2998 6.52237 10.3921 6.52237H11.7835C11.8758 6.52237 11.9643 6.48572 12.0295 6.42049C12.0947 6.35525 12.1314 6.26677 12.1314 6.17451C12.1314 6.08226 12.0947 5.99378 12.0295 5.92854C11.9643 5.86331 11.8758 5.82666 11.7835 5.82666Z" />
													</svg>
												</span>
											</el-tooltip>
										</div>
									</div>
								</template>
							</el-table-column>
							<el-table-column prop="appointment_date" label="<?php esc_html_e('Appointment On', 'bookingpress-appointment-booking'); ?>" sortable="false">
								<template slot-scope="scope">
									<label>{{ scope.row.appointment_date }}</label>
									<div class="bpa-table-actions-wrap" v-if="bookingpress_delete_payment == 1 || ( bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2'))">
										<div class="bpa-table-actions">

											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="( ( bpa_chk_staff_role != 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2) || ( bpa_chk_staff_role == 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2 && bookingpress_edit_payment == 1 ) )">
												<div slot="content">
													<span><?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-button @click="bookingpress_open_additional_amount_model(event,scope.row.appointment_id,scope.row.payment_log_id, scope.$index)" class="bpa-btn bpa-btn--icon-without-box __secondary bpa-btn-addition-amount">
													<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 19C17.9853 19 20 16.9853 20 14.5C20 12.0147 17.9853 10 15.5 10C13.0147 10 11 12.0147 11 14.5C11 16.9853 13.0147 19 15.5 19ZM17.9134 15.0479L17.4415 14.6885C17.4475 14.6331 17.4526 14.5683 17.4526 14.4998C17.4526 14.4312 17.4477 14.3665 17.4415 14.311L17.9139 13.9515C18.0016 13.884 18.0256 13.7642 17.9697 13.6644L17.4789 12.8375C17.4265 12.7442 17.3116 12.6962 17.195 12.7381L16.6381 12.9558C16.5314 12.8808 16.4195 12.8175 16.3038 12.7669L16.219 12.1917C16.2051 12.0825 16.1076 12 15.9923 12H15.0081C14.8928 12 14.7955 12.0825 14.7818 12.19L14.6968 12.7673C14.5847 12.8165 14.4746 12.879 14.3631 12.9563L13.8047 12.7379C13.7001 12.6985 13.5749 12.7427 13.5229 12.8356L13.0315 13.6635C12.9736 13.759 12.9975 13.8829 13.0872 13.9521L13.5591 14.3115C13.5516 14.3817 13.548 14.4427 13.548 14.5C13.548 14.5573 13.5516 14.6183 13.5591 14.6888L13.0867 15.0483C12.999 15.116 12.9753 15.2358 13.0311 15.3354L13.5219 16.1623C13.5743 16.2554 13.6881 16.3037 13.8058 16.2617L14.3627 16.044C14.4692 16.1188 14.5811 16.1821 14.6968 16.2329L14.7816 16.8079C14.7955 16.9175 14.8928 17 15.0083 17H15.9925C16.1078 17 16.2053 16.9175 16.219 16.81L16.304 16.2329C16.4161 16.1835 16.526 16.1213 16.6377 16.0438L17.1961 16.2621C17.2231 16.2723 17.2511 16.2775 17.28 16.2775C17.363 16.2775 17.4393 16.2333 17.4779 16.1646L17.9708 15.3333C18.0256 15.2358 18.0016 15.116 17.9134 15.0479ZM14.6444 14.5C14.6444 14.9596 15.0282 15.3333 15.5002 15.3333C15.9721 15.3333 16.356 14.9596 16.356 14.5C16.356 14.0404 15.9721 13.6667 15.5002 13.6667C15.0282 13.6667 14.6444 14.0404 14.6444 14.5Z" fill="#727E95" />
														<path fill-rule="evenodd" clip-rule="evenodd" d="M2.85002 2.0246H14.15C14.5337 1.97081 14.9249 2.00552 15.2926 2.12596C15.6603 2.24641 15.9945 2.44929 16.2686 2.71854C16.5427 2.9878 16.7492 3.31603 16.8718 3.67726C16.9944 4.03849 17.0297 4.4228 16.975 4.79977V8.92553C16.9745 8.9697 16.9634 9.01314 16.9424 9.05222C16.9215 9.09131 16.8913 9.12492 16.8545 9.15025C16.8176 9.17559 16.7752 9.19191 16.7306 9.19788C16.6861 9.20384 16.6407 9.19927 16.5983 9.18454C16.1108 9.03452 15.6025 8.95963 15.0916 8.96253C13.7188 8.96497 12.4029 9.5018 11.4322 10.4554C10.4614 11.409 9.91498 12.7017 9.91249 14.0503C9.91096 14.2637 9.92354 14.4769 9.95016 14.6886C9.94967 14.7645 9.91875 14.8372 9.86411 14.8909C9.80948 14.9445 9.73551 14.9749 9.65824 14.9754H2.85002C2.46629 15.0292 2.07508 14.9945 1.70737 14.874C1.33965 14.7536 1.00553 14.5507 0.731439 14.2815C0.457352 14.0122 0.25083 13.684 0.128222 13.3227C0.00561449 12.9615 -0.0297127 12.5772 0.0250373 12.2002V4.79977C-0.0297127 4.4228 0.00561449 4.03849 0.128222 3.67726C0.25083 3.31603 0.457352 2.9878 0.731439 2.71854C1.00553 2.44929 1.33965 2.24641 1.70737 2.12596C2.07508 2.00552 2.46629 1.97081 2.85002 2.0246ZM2.32687 9.26916C2.48172 9.3708 2.66378 9.42506 2.85002 9.42506C3.09977 9.42506 3.33928 9.3276 3.51588 9.15411C3.69248 8.98063 3.79169 8.74534 3.79169 8.5C3.79169 8.31704 3.73646 8.13819 3.63299 7.98606C3.52952 7.83394 3.38245 7.71537 3.21038 7.64536C3.03832 7.57534 2.84898 7.55702 2.66632 7.59272C2.48365 7.62841 2.31586 7.71651 2.18417 7.84589C2.05248 7.97526 1.96279 8.14009 1.92646 8.31953C1.89012 8.49897 1.90877 8.68497 1.98004 8.854C2.05131 9.02304 2.17201 9.16751 2.32687 9.26916ZM6.93052 10.8075C7.39509 11.1124 7.94127 11.2752 8.5 11.2752C9.24923 11.2752 9.96778 10.9828 10.4976 10.4623C11.0274 9.9419 11.325 9.23602 11.325 8.5C11.325 7.95112 11.1593 7.41457 10.8489 6.9582C10.5385 6.50182 10.0973 6.14612 9.58108 5.93607C9.06488 5.72603 8.49687 5.67107 7.94887 5.77815C7.40088 5.88523 6.89751 6.14954 6.50243 6.53766C6.10735 6.92577 5.8383 7.42026 5.72929 7.95859C5.62029 8.49692 5.67624 9.05492 5.89005 9.56201C6.10387 10.0691 6.46595 10.5025 6.93052 10.8075Z" fill="#727E95" />
													</svg>
												</el-button>
											</el-tooltip>

											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2')">
												<div slot="content">
													<span><?php esc_html_e('Send Payment Link', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popover placement="bottom" trigger="click" popper-class="bpa-dialog bpa-dailog__small bpa-dialog--add-category bpa-complete-payment-dialog">
													<div class="bpa-dialog-heading">
														<el-row type="flex">
															<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																<h1 class="bpa-page-heading"><?php esc_html_e('Share Payment Link', 'bookingpress-appointment-booking'); ?></h1>
															</el-col>
														</el-row>
													</div>
													<div class="bpa-dialog-body">
														<el-container class="bpa-grid-list-container bpa-add-categpry-container">
															<div class="bpa-form-row">
																<el-row>
																	<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																		<el-form label-position="top">
																			<div class="bpa-form-body-row bpa-dsu__checkbox-row">
																				<el-row>
																					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																						<el-form-item>
																							<template #label>
																								<span class="bpa-form-label"><?php echo esc_html__('Share With', 'bookingpress-appointment-booking'); ?></span>
																							</template>
																							<el-checkbox-group v-model="bookingpress_complete_payment_link_send_options">
																								<el-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" label="email"><?php esc_html_e('Through Email', 'bookingpress-appointment-booking'); ?></el-checkbox>
																								<?php
																								do_action('bookingpress_add_more_complete_payment_link_option');
																								?>
																							</el-checkbox-group>
																						</el-form-item>
																					</el-col>
																				</el-row>
																			</div>
																		</el-form>
																	</el-col>
																</el-row>
															</div>
														</el-container>
													</div>
													<div class="bpa-dialog-footer">
														<div class="bpa-hw-right-btn-group">
															<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary" @click="bpa_send_complete_payment_link(scope.row.payment_log_id)" :class="(is_send_button_loader == '1') ? 'bpa-btn--is-loader' : ''" :disabled="is_send_button_disabled">
																<span class="bpa-btn__label"><?php esc_html_e('Send Link', 'bookingpress-appointment-booking'); ?></span>
																<div class="bpa-btn--loader__circles">
																	<div></div>
																	<div></div>
																	<div></div>
																</div>
															</el-button>
														</div>
													</div>
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __secondary">
														<span class="material-icons-round">link</span>
													</el-button>
												</el-popover>
											</el-tooltip>

											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="bookingpress_edit_payment == 1">
												<div slot="content">
													<span><?php esc_html_e('Approve', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Approve', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to approve the Payment (Appointment will automatically be approved)?', 'bookingpress-appointment-booking'); ?>"
													@confirm="bpa_approve_appointment(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--primary"
													cancel-button-type="bpa-btn bpa-btn__small"
													v-if="scope.row.appointment_status == '2'">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __secondary">
														<span class="material-icons-round">done</span>
													</el-button>
												</el-popconfirm>
											</el-tooltip>
											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="bookingpress_delete_payment == 1">
												<div slot="content">
													<span><?php esc_html_e('Delete', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Delete', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to delete this payment transaction?', 'bookingpress-appointment-booking'); ?>"
													@confirm="deletePaymentLog(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger"
													cancel-button-type="bpa-btn bpa-btn__small">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __danger">
														<span class="material-icons-round">delete</span>
													</el-button>
												</el-popconfirm>
											</el-tooltip>
											<?php
											$bookingpress_add_dynamic_action_btn_content = "";
											$bookingpress_add_dynamic_action_btn_content = apply_filters('bookingpress_payment_list_add_action_button', $bookingpress_add_dynamic_action_btn_content);
											echo $bookingpress_add_dynamic_action_btn_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
									</div>
								</template>
							</el-table-column>
						</el-table>
					</div>
					<div class="bpa-tc__wrapper" v-if="current_screen_size == 'tablet'">
						<el-table ref="multipleTable" :data="items" class="bpa-manage-payment-items" @sort-change="handel_payment_appointment_data" @selection-change="handleSelectionChange" @row-click="bookingpress_full_row_clickable" @expand-change="bookingpress_row_expand">
							<el-table-column type="expand">
								<template slot-scope="scope">
									<div class="bpa-view-payment-card">
										<div class="bpa-vpc--head">
											<div class="bpa-vpc--head__left">
												<h2><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h2>
											</div>
											<div class="bpa-hw-right-btn-group bpa-vpc--head__right" v-if="scope.row.payment_refund_status == 1">
												<el-button class="bpa-btn bpa-btn__refund" @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_log_id,scope.row.appointment_currency_symbol,scope.row.payment_partial_refund)">
													<svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
														<path d="M12.1471 5.67946H15.6684V1.96777" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M16.56 9.18573C16.56 13.2612 13.2555 16.5639 9.18001 16.5639C5.1045 16.5639 1.79999 13.2594 1.79999 9.18384C1.79999 5.10834 5.1045 1.80005 9.18001 1.80005C11.9869 1.80005 14.4299 3.36654 15.6759 5.67009" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M7.54754 10.7106C7.54754 11.6117 8.27894 12.3431 9.18 12.3431C10.0811 12.3431 10.8125 11.6117 10.8125 10.7106C10.8125 9.80957 10.0811 9.07816 9.17812 9.07816C8.27517 9.07816 7.54565 8.34676 7.54565 7.4457C7.54565 6.54464 8.27706 5.81323 9.18 5.81323C10.0811 5.81323 10.8144 6.54464 10.8144 7.4457" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 13.6685V12.3452" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 5.81135V4.48804" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
													</svg>
													<?php esc_html_e('Refund', 'bookingpress-appointment-booking'); ?>
												</el-button>
												<?php
												do_action('bookingpress_add_dynamic_buttons_for_view_payments');
												?>
											</div>
										</div>
										<div class="bpa-vpc--body">
											<div class="bpa-vpc__customer-summary-wrapper">
												<div class="bpa-vpc-csw__item">
													<div class="bpa-csw--customer-info">
														<div class="bpa-ci--avatar">
															<img :src="scope.row.customer_avatar">
														</div>
														<div class="bpa-ci--body">
															<h4 v-if="scope.row.customer_firstname != '' && scope.row.customer_lastname != ''">{{ scope.row.customer_firstname }} {{ scope.row.customer_lastname }}</h4>
															<p>{{ scope.row.customer_email }}</p>
														</div>
													</div>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Mode', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.payment_gateway_label }}</p>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Transaction ID', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.transaction_id }}</p>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?></h4>
													<p :class="[(scope.row.payment_status == '1') ? 'bpa-cl-pt-main-green' : '', (scope.row.payment_status == '2') ? 'bpa-cl-sc-warning' : '', (scope.row.payment_status == '4') ? 'bpa-cl-pt-blue' : '', (scope.row.payment_status == '3') ? 'bpa-cl-danger' : '']">{{ scope.row.payment_status_label }}</p>
												</div>
											</div>
											<div v-if="(scope.row.is_package_purchase == 'undefined') || (scope.row.is_package_purchase != 'undefined' && scope.row.is_package_purchase != '1')" class="bpa-vpc__appointment-details">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Appointment Details', 'bookingpress-appointment-booking'); ?></h4>
												<div class="bpa-vac-ap--items">
													<div class="bpa-ap-item__head">
														<div class="bpa-ih--item">
															<h4><?php esc_html_e('Service', 'bookingpress-appointment-booking'); ?></h4>
														</div>
														<div class="bpa-ih--item">
															<h4><?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?></h4>
														</div>
														<div class="bpa-ih--item">
															<h4><?php esc_html_e('Time', 'bookingpress-appointment-booking'); ?></h4>
														</div>
														<div class="bpa-ih--item">
															<h4><?php esc_html_e('Price', 'bookingpress-appointment-booking'); ?></h4>
														</div>
														<div class="bpa-ih--item" v-show="scope.row.is_deposit_enable == '1'">
															<h4><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></h4>
														</div>
														<div class="bpa-ih--item" v-show="is_staffmember_activated == '1'">
															<h4><?php echo esc_html($bookingpress_singular_staffmember_name); ?></h4>
														</div>
													</div>
													<div class="bpa-ap-item__body">
														<div class="bpa-ib--item-card" v-for="appointment_details in scope.row.appointment_details">
															<div class="bpa-ib--item">
																<p>{{ appointment_details.bookingpress_service_name }}</p>
																<div class="bpa-ap__service-extras" v-if="appointment_details.extra_service_details.length > 0">
																	<p v-for="appointment_extra_details in appointment_details.extra_service_details">
																		x {{ appointment_extra_details.selected_qty }} {{ appointment_extra_details.extra_name }}
																	</p>
																</div>
																<div class="bpa-ap__service-extras bpa-ap__pack-title" v-if="appointment_details.bookingpress_purchase_type == 3 && appointment_details.bookingpress_package_discount_amount_with_currency != 'undefined' && appointment_details.bookingpress_package_discount_amount_with_currency != 0 && typeof appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name != 'undefined'">
																	<p>{{ appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name }}</p>
																</div>
															</div>
															<div class="bpa-ib--item">
																<p>{{ appointment_details.bookingpress_appointment_date }}</p>
															</div>
															<div class="bpa-ib--item">
																<p v-if="scope.row.appointment_start_time != ''">{{ appointment_details.bookingpress_appointment_time }} <?php esc_html_e('to', 'bookingpress-appointment-booking'); ?> {{ appointment_details.bookingpress_appointment_end_time }}</p>
															</div>
															<div class="bpa-ib--item">
																<div class="bpa-ib__amount-row">
																	<div class="bpa-ar__body">
																		<p>{{ appointment_details.bookingpress_service_price_with_currency }}</p>
																		<div class="bpa-ap__service-extras-price" v-if="appointment_details.extra_service_details.length > 0">
																			<p v-for="appointment_extra_details in appointment_details.extra_service_details">
																				{{ appointment_extra_details.extra_service_price_with_currency }}
																			</p>
																		</div>
																		<div class="bpa-ap__service-extras-price bpa-ap__pack-price" v-if="appointment_details.bookingpress_purchase_type == 3 && appointment_details.bookingpress_package_discount_amount_with_currency != 'undefined' && appointment_details.bookingpress_package_discount_amount_with_currency != 0">
																			<p> - {{ appointment_details.bookingpress_package_discount_amount_with_currency }}</p>
																		</div>
																	</div>
																	<div class="bpa-ar__icons">
																		<el-tooltip content="<?php esc_html_e('Total Persons', 'bookingpress-appointment-booking'); ?>" placement="top">
																			<p v-if="appointment_details.bookingpress_selected_extra_members > 1">
																				<span class="material-icons-round">account_circle</span>
																				{{ appointment_details.bookingpress_selected_extra_members }}
																			</p>
																		</el-tooltip>
																	</div>
																</div>
															</div>
															<div class="bpa-ib--item" v-if="appointment_details.is_deposit_applied == '1'">
																<p>{{ appointment_details.bookingpress_deposit_amount_with_currency }}</p>
															</div>
															<div class="bpa-ib--item" v-show="is_staffmember_activated == '1'">
																<div class="bpa-ib-item__staff-info" v-show="(appointment_details.bookingpress_staff_member_id != '0' || scope.row.staff_member_name != '')">
																	<img v-if="appointment_details.bookingpress_staff_member_id != '0'" :src="appointment_details.staff_avatar_url">
																	<p v-if="scope.row.staff_member_name != ''">{{ scope.row.staff_member_name }} </p>
																	<p v-else>{{ appointment_details.bookingpress_staff_first_name }} {{ appointment_details.bookingpress_staff_last_name }}</p>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="bpa-vpc__payment-summary-wapper">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Payment Summary', 'bookingpress-appointment-booking'); ?></h4>
												<div class="bpa-psw--body">
													<div class="bpa-psw--body-row">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Subtotal', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount" v-if="typeof scope.row.subtotal_amount_discounted != 'undefined' && scope.row.subtotal_amount_discounted != 0">
																<p>{{scope.row.subtotal_amount_with_discounted_currency }}</p>
															</div>
															<div class="bpa-psw__item-amount" v-else>
																<p>{{scope.row.subtotal_amount_with_currency }}</p>
															</div>
														</div>
														<?php do_action('bookingpress_payment_page_after_subtotal'); ?>
														<div class="bpa-psw__item" v-if="scope.row.is_deposit_enable == '1' || (scope.row.bookingpress_payment_adjustment_amount != '' && scope.row.payment_status == '4')">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.payment_amount }}</p>
															</div>
														</div>
														<div class="bpa-psw__item" v-if="scope.row.bookingpress_purchase_type == 1">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.bookingpress_payment_adjustment_amount_with_currency }}</p>
															</div>
														</div>
														<div class="bpa-psw__item" v-if="scope.row.is_deposit_enable == '1' && scope.row.payment_status == '4'">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Due Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount bookingpress_due_amount">
																<p>{{ scope.row.due_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-psw--body-row__tax-item" :class="( (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ) ) && scope.row.applied_coupon_code == '') ? 'bpa-psw--body-row__tax-item-excluded' : ''" v-if="scope.row.tax_amount != ''">
														<div class="bpa-psw__item" v-if="(scope.row.tax_amount != '') && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ))">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>+{{ scope.row.tax_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row" v-if="scope.row.applied_coupon_code != ''">
														<div class="bpa-psw__item --bpa-is-coupon-item" v-if="scope.row.applied_coupon_code != ''">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>-{{ scope.row.coupon_discount_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<?php do_action('bookingpress_modify_payment_managepayment_section') ?>
													<div class="bpa-psw--body-row --bpa-is-total-row">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p>
																	<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?>
																	<span class="bpa-psw-ba__included-tax-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</span>
																</p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.total_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-row" v-if="scope.row.staff_commission_details && scope.row.staff_commission_details.length">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<el-popover placement="bottom-start" width="280" trigger="hover" popper-class="bpa-commission-popover" v-if="scope.row.staff_commission_details && 1<scope.row.staff_commission_details.length">
																	<div class="bpa-commission-wrapper">
																		<div class="bpa-commission-title"><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></div>
																		<div
																			class="bpa-commission-row"
																			v-for="(staff, index) in scope.row.staff_commission_details"
																			:key="index">
																			<span>{{ staff.staff_name }}</span>
																			<span>{{ staff.commission_amount }}</span>
																		</div>
																		<div class="bpa-commission-total">
																			<span>Total Commission</span>
																			<span class="bpa-total-amount">
																				{{ scope.row.staff_commission_amount_with_currency }}
																			</span>
																		</div>
																	</div>
																	<p slot="reference" style="cursor:pointer;">
																		-{{ scope.row.staff_commission_amount_with_currency }}
																	</p>
																</el-popover>
																<p v-else>-{{ scope.row.staff_commission_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-net-revenue" v-if="0<scope.row.staff_commission_amount">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Balance Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>	
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.staff_commission_net_revenue_with_currency }}</p>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</template>
							</el-table-column>
							<el-table-column type="selection"></el-table-column>
							<el-table-column prop="payment_date" min-width="70" label="<?php esc_html_e('Date', 'bookingpress-appointment-booking'); ?>" sortable sort-by="sort_payment_date"></el-table-column>
							<el-table-column prop="payment_service" min-width="100" label="<?php
																							$bookingpress_payment_transaction_item_label = esc_html__('Service', 'bookingpress-appointment-booking');
																							$bookingpress_payment_transaction_item_label = apply_filters('bookingpress_payment_transaction_item_label', $bookingpress_payment_transaction_item_label);
																							echo esc_html($bookingpress_payment_transaction_item_label);
																							?>" sortable><?php do_action('bookingpress_multiple_service_name_payment_section_fetch'); ?></el-table-column>
							<el-table-column prop="payment_status" min-width="90" label="<?php esc_html_e('Status', 'bookingpress-appointment-booking'); ?>">
								<template slot-scope="scope">
									<div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''" v-if="bookingpress_edit_payment == 1">
										<div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
											<div class="bpa-btn--loader__circles">
												<div></div>
												<div></div>
												<div></div>
											</div>
										</div>
										<el-select class="bpa-form-control" :class="((scope.row.payment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.payment_status == '3' ? 'bpa-appointment-status--rejected' : '') || (scope.row.payment_status == '4' ? 'bpa-appointment-status--approved' : '') || (scope.row.payment_status == '1' ? 'bpa-appointment-status--completed' : '') || (scope.row.payment_status == '5' ? 'bpa-appointment-status--refund-partial' : '')" v-model="scope.row.payment_status" placeholder="<?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?>" popper-class="bpa-payment-status-dropdown-popper" @change="bookingpress_change_status(scope.row.payment_log_id, $event)">
											<el-option-group label="<?php esc_html_e('Change status', 'bookingpress-appointment-booking'); ?>">
												<el-option v-for="item in payment_status_data" :key="item.value" :label="item.text" :value="item.value"></el-option>
											</el-option-group>
										</el-select>
									</div>
									<el-tag class="bpa-front-pill " :class="((scope.row.payment_status == '2') ? '--warning' : '') || (scope.row.payment_status == '3' ? '--rejected' : '') || (scope.row.payment_status == '4' ? '--approved' : '') || (scope.row.payment_status == '1' ? '--completed' : '')" v-else>{{ scope.row.payment_status_label }}</el-tag>
								</template>
							</el-table-column>
							<el-table-column prop="payment_amount" min-width="70" label="<?php esc_html_e('Amount', 'bookingpress-appointment-booking'); ?>" sortable sort-by="payment_numberic_amount">
								<template slot-scope="scope">
									<div class="bpa-mpi__amount-row">
										<div class="bpa-mpi__ar-body">
											<!-- <span class="bpa-mpi__amount" v-if="scope.row.is_deposit_enable == '1' && scope.row.payment_status == '4'">{{ scope.row.deposit_amount_with_currency }}</span> -->
											<span v-if="scope.row.is_deposit_enable == 1 && scope.row.payment_status == '4'" class="bpa-mpi__amount">{{ scope.row.payment_amount }}</span>
											<span v-else class="bpa-mpi__amount">{{scope.row.total_amount_with_currency}}</span>
											<span v-if="scope.row.is_deposit_enable == 1 && scope.row.payment_status == '4'" class="bpa-is-deposit-payment-val"><?php esc_html_e('of', 'bookingpress-appointment-booking'); ?> {{ scope.row.total_amount_with_currency }}</span>
										</div>
										<div class="bpa-mpi__ar-icons">
											<?php do_action('bookingpress_backend_payment_list_type_icons'); ?>
											<el-tooltip content="<?php esc_html_e('Past Modified Appointment', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_past_appointment_edited == 1">
												<span class="bpa-apc__past-edited-icon">
													<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M16.436 2.02543C16.1558 2.09391 15.8632 2.29311 15.5395 2.62305L15.2656 2.90319L16.2554 3.89923L17.2515 4.89526L17.5814 4.56533C17.9923 4.16069 18.1541 3.83697 18.1541 3.41366C18.1541 2.85961 17.8802 2.39894 17.4071 2.15616C17.1394 2.0192 16.6974 1.96318 16.436 2.02543Z" fill="#535D71" />
														<path d="M7.88861 3.12088C6.27005 3.44459 4.69506 4.39705 3.58075 5.7168C2.72789 6.73152 2.11782 8.07617 1.92483 9.37724C1.86881 9.75075 1.86881 11.4067 1.92483 11.693C2.09291 12.5148 2.21742 12.9505 2.4851 13.5668C3.90446 16.8787 7.38436 18.5781 11.1569 17.8124C11.7918 17.6817 12.8812 17.2148 13.5224 16.7977C14.3193 16.281 15.1597 15.4406 15.6826 14.6313C16.2429 13.766 16.7409 12.384 16.8343 11.4627C16.8716 11.0269 16.8841 11.0083 17.0584 11.0394C17.2887 11.083 17.4755 10.9585 17.4755 10.7655C17.4755 10.6347 17.3074 10.4293 16.7035 9.83168C16.2616 9.38969 15.8818 9.06598 15.8133 9.06598C15.7449 9.06598 15.3651 9.40214 14.9044 9.86903C14.2757 10.4978 14.1201 10.6908 14.1512 10.7841C14.2321 11.0083 14.3193 11.0705 14.4998 11.0332C14.7052 10.9896 14.7115 11.0269 14.5807 11.6432C14.064 14.096 12.0222 15.7768 9.5383 15.8079C8.51113 15.8204 7.84503 15.6772 6.9735 15.2601C5.41719 14.5068 4.48963 13.2369 4.09744 11.3382C3.97916 10.753 4.03519 9.91261 4.24685 9.00373C4.49586 7.95789 5.42964 6.70661 6.4568 6.04674C7.16648 5.5923 7.68318 5.39309 8.52981 5.23746C8.79749 5.18766 9.0963 5.08805 9.20213 5.01958C9.62545 4.71454 9.77486 3.94261 9.48849 3.51929C9.18346 3.05863 8.73524 2.9528 7.88861 3.12088Z" fill="#535D71" />
														<path d="M11.7973 6.37663L8.76562 9.40832L9.76166 10.4044L10.7577 11.4004L13.8081 8.35003L16.8584 5.29966L15.8811 4.3223C15.3395 3.7807 14.885 3.33871 14.8664 3.33871C14.8477 3.33871 13.4719 4.70204 11.7973 6.37663Z" fill="#535D71" />
														<path d="M7.87285 11.1515C7.23788 12.6207 7.22543 12.6456 7.33126 12.7701C7.48066 12.9444 7.67987 12.8822 9.41671 12.1227C9.55367 12.0604 9.79645 11.9546 9.95831 11.8923C10.1264 11.8301 10.2509 11.7554 10.2384 11.7305C10.226 11.6931 8.44558 9.86914 8.4269 9.87536C8.42067 9.87536 8.17166 10.4543 7.87285 11.1515Z" fill="#535D71" />
													</svg>
												</span>
											</el-tooltip>
											<el-tooltip content="<?php esc_html_e('Cart Transaction', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_cart == '1'">
												<span class="material-icons-round">shopping_cart</span>
											</el-tooltip>
											<el-tooltip content="<?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_deposit_enable == '1'">
												<span class="bpa-apc__deposit-icon">
													<svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M16.9596 12.2237C16.8902 12.0273 16.746 11.8662 16.5583 11.7756C16.3706 11.685 16.1548 11.6723 15.9578 11.7402L13.7872 12.4116C13.2376 12.9125 13.0288 12.7838 9.00068 12.7838C8.90842 12.7838 8.81994 12.7471 8.75471 12.6819C8.68947 12.6167 8.65282 12.5282 8.65282 12.4359C8.65282 12.3437 8.68947 12.2552 8.75471 12.1899C8.81994 12.1247 8.90842 12.0881 9.00068 12.0881C13.1749 12.0881 13.0323 12.1681 13.3384 11.862C13.4551 11.7331 13.5206 11.5661 13.5228 11.3923C13.5228 11.2078 13.4495 11.0309 13.319 10.9004C13.1886 10.7699 13.0116 10.6966 12.8271 10.6966H9.10504C8.62152 10.6966 8.21801 10.0009 6.80919 10.0009H4.47856V13.6256L4.92729 13.8795C6.20362 14.6153 7.63128 15.0496 9.10115 15.149C10.571 15.2485 12.0442 15.0106 13.408 14.4535L16.5387 13.1908C16.7162 13.1104 16.8576 12.967 16.9354 12.7883C17.0132 12.6096 17.0218 12.4084 16.9596 12.2237ZM1 14.523H3.78285V9.30521H1V14.523ZM2.0714 12.9994C2.09103 12.9518 2.12099 12.9092 2.1591 12.8746C2.19722 12.84 2.24255 12.8142 2.29181 12.7993C2.34107 12.7843 2.39304 12.7805 2.44398 12.788C2.49491 12.7956 2.54353 12.8143 2.58633 12.8429C2.62913 12.8716 2.66504 12.9093 2.69147 12.9535C2.71791 12.9977 2.7342 13.0472 2.73919 13.0984C2.74417 13.1497 2.73771 13.2014 2.72028 13.2499C2.70285 13.2983 2.67489 13.3423 2.6384 13.3786C2.58145 13.4353 2.50661 13.4705 2.42662 13.4783C2.34663 13.4861 2.26641 13.4659 2.1996 13.4213C2.13279 13.3766 2.08351 13.3102 2.06014 13.2333C2.03677 13.1564 2.04074 13.0737 2.0714 12.9994ZM11.4357 8.95736C12.1237 8.95736 12.7962 8.75334 13.3683 8.37112C13.9403 7.98889 14.3862 7.44561 14.6494 6.80999C14.9127 6.17437 14.9816 5.47494 14.8474 4.80017C14.7132 4.1254 14.3819 3.50558 13.8954 3.01909C13.4089 2.53261 12.7891 2.20131 12.1143 2.06709C11.4395 1.93286 10.7401 2.00175 10.1045 2.26504C9.46886 2.52832 8.92558 2.97417 8.54336 3.54622C8.16113 4.11827 7.95711 4.79081 7.95711 5.4788C7.95711 6.40137 8.3236 7.28616 8.97596 7.93851C9.62831 8.59087 10.5131 8.95736 11.4357 8.95736ZM11.7835 5.82666H11.0878C10.811 5.82666 10.5456 5.71671 10.3499 5.521C10.1542 5.3253 10.0442 5.05986 10.0442 4.78309C10.0442 4.50632 10.1542 4.24088 10.3499 4.04518C10.5456 3.84947 10.811 3.73952 11.0878 3.73952V3.39167C11.0878 3.29941 11.1245 3.21093 11.1897 3.1457C11.2549 3.08046 11.3434 3.04381 11.4357 3.04381C11.5279 3.04381 11.6164 3.08046 11.6816 3.1457C11.7469 3.21093 11.7835 3.29941 11.7835 3.39167V3.73952H12.4792C12.5715 3.73952 12.66 3.77617 12.7252 3.84141C12.7904 3.90664 12.8271 3.99512 12.8271 4.08738C12.8271 4.17964 12.7904 4.26812 12.7252 4.33335C12.66 4.39859 12.5715 4.43524 12.4792 4.43524H11.0878C10.9956 4.43524 10.9071 4.47188 10.8418 4.53712C10.7766 4.60236 10.74 4.69083 10.74 4.78309C10.74 4.87535 10.7766 4.96383 10.8418 5.02906C10.9071 5.0943 10.9956 5.13095 11.0878 5.13095H11.7835C12.0603 5.13095 12.3257 5.24089 12.5214 5.4366C12.7171 5.63231 12.8271 5.89774 12.8271 6.17451C12.8271 6.45128 12.7171 6.71672 12.5214 6.91243C12.3257 7.10813 12.0603 7.21808 11.7835 7.21808V7.56594C11.7835 7.65819 11.7469 7.74667 11.6816 7.81191C11.6164 7.87714 11.5279 7.91379 11.4357 7.91379C11.3434 7.91379 11.2549 7.87714 11.1897 7.81191C11.1245 7.74667 11.0878 7.65819 11.0878 7.56594V7.21808H10.3921C10.2998 7.21808 10.2114 7.18143 10.1461 7.1162C10.0809 7.05096 10.0442 6.96248 10.0442 6.87022C10.0442 6.77797 10.0809 6.68949 10.1461 6.62425C10.2114 6.55902 10.2998 6.52237 10.3921 6.52237H11.7835C11.8758 6.52237 11.9643 6.48572 12.0295 6.42049C12.0947 6.35525 12.1314 6.26677 12.1314 6.17451C12.1314 6.08226 12.0947 5.99378 12.0295 5.92854C11.9643 5.86331 11.8758 5.82666 11.7835 5.82666Z" />
													</svg>
												</span>
											</el-tooltip>
										</div>
									</div>
									<div class="bpa-table-actions-wrap" v-if="bookingpress_delete_payment == 1 || ( bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2'))">
										<div class="bpa-table-actions">
											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="( ( bpa_chk_staff_role != 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2) || ( bpa_chk_staff_role == 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2 && bookingpress_edit_payment == 1 ) )">
												<div slot="content">
													<span><?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-button @click="bookingpress_open_additional_amount_model(event,scope.row.appointment_id,scope.row.payment_log_id, scope.$index)" class="bpa-btn bpa-btn--icon-without-box __secondary bpa-btn-addition-amount">
													<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 19C17.9853 19 20 16.9853 20 14.5C20 12.0147 17.9853 10 15.5 10C13.0147 10 11 12.0147 11 14.5C11 16.9853 13.0147 19 15.5 19ZM17.9134 15.0479L17.4415 14.6885C17.4475 14.6331 17.4526 14.5683 17.4526 14.4998C17.4526 14.4312 17.4477 14.3665 17.4415 14.311L17.9139 13.9515C18.0016 13.884 18.0256 13.7642 17.9697 13.6644L17.4789 12.8375C17.4265 12.7442 17.3116 12.6962 17.195 12.7381L16.6381 12.9558C16.5314 12.8808 16.4195 12.8175 16.3038 12.7669L16.219 12.1917C16.2051 12.0825 16.1076 12 15.9923 12H15.0081C14.8928 12 14.7955 12.0825 14.7818 12.19L14.6968 12.7673C14.5847 12.8165 14.4746 12.879 14.3631 12.9563L13.8047 12.7379C13.7001 12.6985 13.5749 12.7427 13.5229 12.8356L13.0315 13.6635C12.9736 13.759 12.9975 13.8829 13.0872 13.9521L13.5591 14.3115C13.5516 14.3817 13.548 14.4427 13.548 14.5C13.548 14.5573 13.5516 14.6183 13.5591 14.6888L13.0867 15.0483C12.999 15.116 12.9753 15.2358 13.0311 15.3354L13.5219 16.1623C13.5743 16.2554 13.6881 16.3037 13.8058 16.2617L14.3627 16.044C14.4692 16.1188 14.5811 16.1821 14.6968 16.2329L14.7816 16.8079C14.7955 16.9175 14.8928 17 15.0083 17H15.9925C16.1078 17 16.2053 16.9175 16.219 16.81L16.304 16.2329C16.4161 16.1835 16.526 16.1213 16.6377 16.0438L17.1961 16.2621C17.2231 16.2723 17.2511 16.2775 17.28 16.2775C17.363 16.2775 17.4393 16.2333 17.4779 16.1646L17.9708 15.3333C18.0256 15.2358 18.0016 15.116 17.9134 15.0479ZM14.6444 14.5C14.6444 14.9596 15.0282 15.3333 15.5002 15.3333C15.9721 15.3333 16.356 14.9596 16.356 14.5C16.356 14.0404 15.9721 13.6667 15.5002 13.6667C15.0282 13.6667 14.6444 14.0404 14.6444 14.5Z" fill="#727E95" />
														<path fill-rule="evenodd" clip-rule="evenodd" d="M2.85002 2.0246H14.15C14.5337 1.97081 14.9249 2.00552 15.2926 2.12596C15.6603 2.24641 15.9945 2.44929 16.2686 2.71854C16.5427 2.9878 16.7492 3.31603 16.8718 3.67726C16.9944 4.03849 17.0297 4.4228 16.975 4.79977V8.92553C16.9745 8.9697 16.9634 9.01314 16.9424 9.05222C16.9215 9.09131 16.8913 9.12492 16.8545 9.15025C16.8176 9.17559 16.7752 9.19191 16.7306 9.19788C16.6861 9.20384 16.6407 9.19927 16.5983 9.18454C16.1108 9.03452 15.6025 8.95963 15.0916 8.96253C13.7188 8.96497 12.4029 9.5018 11.4322 10.4554C10.4614 11.409 9.91498 12.7017 9.91249 14.0503C9.91096 14.2637 9.92354 14.4769 9.95016 14.6886C9.94967 14.7645 9.91875 14.8372 9.86411 14.8909C9.80948 14.9445 9.73551 14.9749 9.65824 14.9754H2.85002C2.46629 15.0292 2.07508 14.9945 1.70737 14.874C1.33965 14.7536 1.00553 14.5507 0.731439 14.2815C0.457352 14.0122 0.25083 13.684 0.128222 13.3227C0.00561449 12.9615 -0.0297127 12.5772 0.0250373 12.2002V4.79977C-0.0297127 4.4228 0.00561449 4.03849 0.128222 3.67726C0.25083 3.31603 0.457352 2.9878 0.731439 2.71854C1.00553 2.44929 1.33965 2.24641 1.70737 2.12596C2.07508 2.00552 2.46629 1.97081 2.85002 2.0246ZM2.32687 9.26916C2.48172 9.3708 2.66378 9.42506 2.85002 9.42506C3.09977 9.42506 3.33928 9.3276 3.51588 9.15411C3.69248 8.98063 3.79169 8.74534 3.79169 8.5C3.79169 8.31704 3.73646 8.13819 3.63299 7.98606C3.52952 7.83394 3.38245 7.71537 3.21038 7.64536C3.03832 7.57534 2.84898 7.55702 2.66632 7.59272C2.48365 7.62841 2.31586 7.71651 2.18417 7.84589C2.05248 7.97526 1.96279 8.14009 1.92646 8.31953C1.89012 8.49897 1.90877 8.68497 1.98004 8.854C2.05131 9.02304 2.17201 9.16751 2.32687 9.26916ZM6.93052 10.8075C7.39509 11.1124 7.94127 11.2752 8.5 11.2752C9.24923 11.2752 9.96778 10.9828 10.4976 10.4623C11.0274 9.9419 11.325 9.23602 11.325 8.5C11.325 7.95112 11.1593 7.41457 10.8489 6.9582C10.5385 6.50182 10.0973 6.14612 9.58108 5.93607C9.06488 5.72603 8.49687 5.67107 7.94887 5.77815C7.40088 5.88523 6.89751 6.14954 6.50243 6.53766C6.10735 6.92577 5.8383 7.42026 5.72929 7.95859C5.62029 8.49692 5.67624 9.05492 5.89005 9.56201C6.10387 10.0691 6.46595 10.5025 6.93052 10.8075Z" fill="#727E95" />
													</svg>
												</el-button>
											</el-tooltip>

											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2')">
												<div slot="content">
													<span><?php esc_html_e('Send Payment Link', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popover placement="bottom" trigger="click" popper-class="bpa-dialog bpa-dailog__small bpa-dialog--add-category bpa-complete-payment-dialog">
													<div class="bpa-dialog-heading">
														<el-row type="flex">
															<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																<h1 class="bpa-page-heading"><?php esc_html_e('Share Payment Link', 'bookingpress-appointment-booking'); ?></h1>
															</el-col>
														</el-row>
													</div>
													<div class="bpa-dialog-body">
														<el-container class="bpa-grid-list-container bpa-add-categpry-container">
															<div class="bpa-form-row">
																<el-row>
																	<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																		<el-form label-position="top">
																			<div class="bpa-form-body-row bpa-dsu__checkbox-row">
																				<el-row>
																					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																						<el-form-item>
																							<template #label>
																								<span class="bpa-form-label"><?php echo esc_html__('Share With', 'bookingpress-appointment-booking'); ?></span>
																							</template>
																							<el-checkbox-group v-model="bookingpress_complete_payment_link_send_options">
																								<el-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" label="email"><?php esc_html_e('Through Email', 'bookingpress-appointment-booking'); ?></el-checkbox>
																								<?php
																								do_action('bookingpress_add_more_complete_payment_link_option');
																								?>
																							</el-checkbox-group>
																						</el-form-item>
																					</el-col>
																				</el-row>
																			</div>
																		</el-form>
																	</el-col>
																</el-row>
															</div>
														</el-container>
													</div>
													<div class="bpa-dialog-footer">
														<div class="bpa-hw-right-btn-group">
															<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary" @click="bpa_send_complete_payment_link(scope.row.payment_log_id)" :class="(is_send_button_loader == '1') ? 'bpa-btn--is-loader' : ''" :disabled="is_send_button_disabled">
																<span class="bpa-btn__label"><?php esc_html_e('Send Link', 'bookingpress-appointment-booking'); ?></span>
																<div class="bpa-btn--loader__circles">
																	<div></div>
																	<div></div>
																	<div></div>
																</div>
															</el-button>
														</div>
													</div>
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __secondary">
														<span class="material-icons-round">link</span>
													</el-button>
												</el-popover>
											</el-tooltip>

											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300">
												<div slot="content">
													<span><?php esc_html_e('Approve', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Approve', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to approve the Payment (Appointment will automatically be approved)?', 'bookingpress-appointment-booking'); ?>"
													@confirm="bpa_approve_appointment(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--primary"
													cancel-button-type="bpa-btn bpa-btn__small"
													v-if="scope.row.appointment_status == '2'">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __secondary">
														<span class="material-icons-round">done</span>
													</el-button>
												</el-popconfirm>
											</el-tooltip>
											<el-tooltip tabindex="-1" effect="dark" content="" placement="top" open-delay="300" v-if="bookingpress_delete_payment == 1">
												<div slot="content">
													<span><?php esc_html_e('Delete', 'bookingpress-appointment-booking'); ?></span>
												</div>
												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Delete', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to delete this payment transaction?', 'bookingpress-appointment-booking'); ?>"
													@confirm="deletePaymentLog(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger"
													cancel-button-type="bpa-btn bpa-btn__small">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn--icon-without-box __danger">
														<span class="material-icons-round">delete</span>
													</el-button>
												</el-popconfirm>
											</el-tooltip>
											<?php
											$bookingpress_add_dynamic_action_btn_content = "";
											$bookingpress_add_dynamic_action_btn_content = apply_filters('bookingpress_payment_list_add_action_button', $bookingpress_add_dynamic_action_btn_content);
											echo $bookingpress_add_dynamic_action_btn_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
									</div>
								</template>
							</el-table-column>
						</el-table>
					</div>
					<div class="bpa-tc__wrapper bpa-manage-payments-container--sm" v-if="current_screen_size == 'mobile'">
						<el-table ref="multipleTable" :data="items" class="bpa-manage-payment-items" @selection-change="handleSelectionChange" @row-click="bookingpress_full_row_clickable" :show-header="false" @expand-change="bookingpress_row_expand">
							<el-table-column type="expand">
								<template slot-scope="scope">
									<div class="bpa-view-payment-card">
										<div class="bpa-vpc--head">
											<div class="bpa-vpc--head__left">
												<h2><?php esc_html_e('Payment Details', 'bookingpress-appointment-booking'); ?></h2>
												<p :class="[(scope.row.payment_status == '1') ? 'bpa-cl-pt-main-green' : '', (scope.row.payment_status == '2') ? 'bpa-cl-sc-warning' : '', (scope.row.payment_status == '4') ? 'bpa-cl-pt-blue' : '', (scope.row.payment_status == '3') ? 'bpa-cl-danger' : '']">{{ scope.row.payment_status_label }}</p>
											</div>
											<div class="bpa-hw-right-btn-group bpa-vpc--head__right" v-if="scope.row.payment_refund_status == 1">
												<el-button class="bpa-btn bpa-btn__refund" @click="bookingpress_open_refund_model(event,scope.row.appointment_id,scope.row.payment_log_id,scope.row.appointment_currency_symbol,scope.row.payment_partial_refund)">
													<svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
														<path d="M12.1471 5.67946H15.6684V1.96777" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M16.56 9.18573C16.56 13.2612 13.2555 16.5639 9.18001 16.5639C5.1045 16.5639 1.79999 13.2594 1.79999 9.18384C1.79999 5.10834 5.1045 1.80005 9.18001 1.80005C11.9869 1.80005 14.4299 3.36654 15.6759 5.67009" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M7.54754 10.7106C7.54754 11.6117 8.27894 12.3431 9.18 12.3431C10.0811 12.3431 10.8125 11.6117 10.8125 10.7106C10.8125 9.80957 10.0811 9.07816 9.17812 9.07816C8.27517 9.07816 7.54565 8.34676 7.54565 7.4457C7.54565 6.54464 8.27706 5.81323 9.18 5.81323C10.0811 5.81323 10.8144 6.54464 10.8144 7.4457" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 13.6685V12.3452" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
														<path d="M9.17999 5.81135V4.48804" stroke="#727E95" stroke-width="1.50805" stroke-miterlimit="10" />
													</svg>
													<?php esc_html_e('Refund', 'bookingpress-appointment-booking'); ?>
												</el-button>
												<?php
												do_action('bookingpress_add_dynamic_buttons_for_view_payments');
												?>
											</div>
										</div>
										<div class="bpa-vpc--body">
											<div class="bpa-csw--customer-info">
												<div class="bpa-ci--avatar">
													<img :src="scope.row.customer_avatar">
												</div>
												<div class="bpa-ci--body">
													<h4 v-if="scope.row.customer_firstname != '' && scope.row.customer_lastname != ''">{{ scope.row.customer_firstname }} {{ scope.row.customer_lastname }}</h4>
													<p>{{ scope.row.customer_email }}</p>
												</div>
											</div>
											<div class="bpa-vpc__customer-summary-wrapper">
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Mode', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.payment_gateway_label }}</p>
												</div>
												<div class="bpa-vpc-csw__item">
													<h4><?php esc_html_e('Transaction ID', 'bookingpress-appointment-booking'); ?></h4>
													<p>{{ scope.row.transaction_id }}</p>
												</div>
											</div>
											<div v-if="(scope.row.is_package_purchase == 'undefined') || (scope.row.is_package_purchase != 'undefined' && scope.row.is_package_purchase != '1')" class="bpa-vpc__appointment-details">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Appointment Details', 'bookingpress-appointment-booking'); ?></h4>
												<div class="bpa-vac-ap--items__sm">
													<div class="bpa-ap-item__body">
														<div class="bpa-ib--item-card" v-for="appointment_details in scope.row.appointment_details">
															<div class="bpa-ib--item-head__sm">
																<div class="bpa-ih-left__sm">
																	<p>{{ appointment_details.bookingpress_service_name }}</p>
																</div>
																<div class="bpa-ih-right__sm">
																	<p>{{ appointment_details.bookingpress_service_price_with_currency }}</p>
																	<p class="bpa-ih-guest-counter--sm" v-if="appointment_details.bookingpress_selected_extra_members > 1">
																		<span class="material-icons-round">account_circle</span>
																		{{ appointment_details.bookingpress_selected_extra_members }}
																	</p>
																</div>
															</div>

															<div class="bpa-ap__service-extras--sm" v-if="appointment_details.extra_service_details.length > 0">
																<div class="bpa-se__item--sm" v-for="appointment_extra_details in appointment_details.extra_service_details">
																	<p>x {{ appointment_extra_details.selected_qty }} {{ appointment_extra_details.extra_name }}</p>
																	<p>{{ appointment_extra_details.extra_service_price_with_currency }}</p>
																</div>
															</div>
															<div class="bpa-ap__service-extras--sm" v-if="appointment_details.bookingpress_purchase_type == 3 && appointment_details.bookingpress_package_discount_amount_with_currency != 'undefined' && appointment_details.bookingpress_package_discount_amount_with_currency != 0 && typeof appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name != 'undefined'">
																<div class="bpa-se__item--sm">
																	<p>{{ appointment_details.bookingpress_applied_package_data_arr.bookingpress_package_name }}</p>
																	<p> - {{ appointment_details.bookingpress_package_discount_amount_with_currency }}</p>
																</div>
															</div>
															<div class="bpa-ib--datetime__sm">
																<div class="bpa-dt__item">
																	<span class="material-icons-round">calendar_today</span>
																	<p>{{ appointment_details.bookingpress_appointment_date }}</p>
																</div>
																<div class="bpa-dt__item">
																	<span class="material-icons-round">access_time</span>
																	<p v-if="scope.row.appointment_start_time != ''">{{ appointment_details.bookingpress_appointment_time }} <?php esc_html_e('to', 'bookingpress-appointment-booking'); ?> {{ appointment_details.bookingpress_appointment_end_time }}</p>
																</div>
															</div>

															<!-- <div class="bpa-ib--item" v-if="appointment_details.is_deposit_applied == '1'">
																<p>{{ appointment_details.bookingpress_deposit_amount_with_currency }}</p>
															</div> -->

															<div class="bpa-ib-item__staff-info" v-show="(appointment_details.bookingpress_staff_member_id != '0' || scope.row.staff_member_name != '')">
																<img v-if="appointment_details.bookingpress_staff_member_id != '0'" :src="appointment_details.staff_avatar_url">
																<p v-if="scope.row.staff_member_name != ''">{{ scope.row.staff_member_name }} </p>
																<p v-else>{{ appointment_details.bookingpress_staff_first_name }} {{ appointment_details.bookingpress_staff_last_name }}</p>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="bpa-vpc__payment-summary-wapper">
												<h4 class="bpa-vac__sec-heading"><?php esc_html_e('Payment Summary', 'bookingpress-appointment-booking'); ?></h4>
												<div class="bpa-psw--body">
													<div class="bpa-psw--body-row">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Subtotal', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount" v-if="typeof scope.row.subtotal_amount_discounted != 'undefined' && scope.row.subtotal_amount_discounted != 0">
																<p>{{scope.row.subtotal_amount_with_discounted_currency }}</p>
															</div>
															<div class="bpa-psw__item-amount" v-else>
																<p>{{scope.row.subtotal_amount_with_currency }}</p>
															</div>
														</div>
														<?php do_action('bookingpress_payment_page_after_subtotal'); ?>
														<div class="bpa-psw__item" v-if="scope.row.is_deposit_enable == '1'">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.payment_amount }}</p>
															</div>
														</div>
														<div class="bpa-psw__item" v-if="scope.row.bookingpress_purchase_type == 1">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.bookingpress_payment_adjustment_amount_with_currency }}</p>
															</div>
														</div>
														<div class="bpa-psw__item" v-if="scope.row.is_deposit_enable == '1' && scope.row.payment_status == '4'">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Due Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount bookingpress_due_amount">
																<p>{{ scope.row.due_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-psw--body-row__tax-item" :class="( (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ) ) && scope.row.applied_coupon_code == '') ? 'bpa-psw--body-row__tax-item-excluded' : ''" v-if="scope.row.tax_amount != ''">
														<div class="bpa-psw__item" v-if="(scope.row.tax_amount != '') && (scope.row.price_display_setting != 'include_taxes' || (scope.row.price_display_setting == 'include_taxes' && scope.row.display_tax_order_summary == 'true' ))">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Tax', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>+{{ scope.row.tax_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row" v-if="scope.row.applied_coupon_code != ''">
														<div class="bpa-psw__item --bpa-is-coupon-item" v-if="scope.row.applied_coupon_code != ''">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Coupon', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>-{{ scope.row.coupon_discount_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<?php do_action('bookingpress_modify_payment_managepayment_section') ?>
													<div class="bpa-psw--body-row --bpa-is-total-row">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p>
																	<?php esc_html_e('Total Amount', 'bookingpress-appointment-booking'); ?>
																	<span class="bpa-psw-ba__included-tax-label" v-if="scope.row.price_display_setting == 'include_taxes'">{{ scope.row.included_tax_label }}</span>
																</p>
															</div>
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.total_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-row" v-if="scope.row.staff_commission_details && scope.row.staff_commission_details.length">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></p>
															</div>
															<div class="bpa-psw__item-amount">
																<el-popover placement="bottom-start" width="280" trigger="hover" popper-class="bpa-commission-popover" v-if="scope.row.staff_commission_details && 1<scope.row.staff_commission_details.length">
																	<div class="bpa-commission-wrapper">
																		<div class="bpa-commission-title"><?php esc_html_e('Staff Commission', 'bookingpress-appointment-booking'); ?></div>
																		<div
																			class="bpa-commission-row"
																			v-for="(staff, index) in scope.row.staff_commission_details"
																			:key="index">
																			<span>{{ staff.staff_name }}</span>
																			<span>{{ staff.commission_amount }}</span>
																		</div>
																		<div class="bpa-commission-total">
																			<span>Total Commission</span>
																			<span class="bpa-total-amount">
																				{{ scope.row.staff_commission_amount_with_currency }}
																			</span>
																		</div>
																	</div>
																	<p slot="reference" style="cursor:pointer;">
																		-{{ scope.row.staff_commission_amount_with_currency }}
																	</p>
																</el-popover>
																<p v-else>-{{ scope.row.staff_commission_amount_with_currency }}</p>
															</div>
														</div>
													</div>
													<div class="bpa-psw--body-row bpa-payment-commission-net-revenue" v-if="0<scope.row.staff_commission_amount">
														<div class="bpa-psw__item">
															<div class="bpa-psw__item-title">
																<p><?php esc_html_e('Balance Amount', 'bookingpress-appointment-booking'); ?></p>
															</div>	
															<div class="bpa-psw__item-amount">
																<p>{{ scope.row.staff_commission_net_revenue_with_currency }}</p>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</template>
							</el-table-column>
							<el-table-column type="selection"></el-table-column>
							<el-table-column>
								<template slot-scope="scope">
									<div class="bpa-mpay-item__mob">
										<div class="bpa-mpay-item--head">
											<div class="bpa-mpay-head__left">
												<h4>{{ scope.row.payment_service }}</h4>
												<span>{{ scope.row.payment_date }}</span>
											</div>
											<div class="bpa-mpay-head__right">
												<div class="bpa-mpi__amount-row">
													<div class="bpa-mpi__ar-body">
														<!-- <span class="bpa-mpi__amount" v-if="scope.row.is_deposit_enable == '1' && scope.row.payment_status == '4'">{{ scope.row.deposit_amount_with_currency }}</span> -->
														<span class="bpa-mpi__amount">{{ scope.row.payment_amount }}</span>
														<span v-if="scope.row.is_deposit_enable == 1 && scope.row.payment_status == '4'" class="bpa-is-deposit-payment-val"><?php esc_html_e('of', 'bookingpress-appointment-booking'); ?> {{ scope.row.total_amount_with_currency }}</span>
													</div>
													<div class="bpa-mpi__ar-icons">
														<?php do_action('bookingpress_backend_payment_list_type_icons'); ?>
														<el-tooltip content="<?php esc_html_e('Past Modified Appointment', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.bookingpress_is_past_appointment_edited == 1">
															<span class="bpa-apc__past-edited-icon">
																<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																	<path d="M16.436 2.02543C16.1558 2.09391 15.8632 2.29311 15.5395 2.62305L15.2656 2.90319L16.2554 3.89923L17.2515 4.89526L17.5814 4.56533C17.9923 4.16069 18.1541 3.83697 18.1541 3.41366C18.1541 2.85961 17.8802 2.39894 17.4071 2.15616C17.1394 2.0192 16.6974 1.96318 16.436 2.02543Z" fill="#535D71" />
																	<path d="M7.88861 3.12088C6.27005 3.44459 4.69506 4.39705 3.58075 5.7168C2.72789 6.73152 2.11782 8.07617 1.92483 9.37724C1.86881 9.75075 1.86881 11.4067 1.92483 11.693C2.09291 12.5148 2.21742 12.9505 2.4851 13.5668C3.90446 16.8787 7.38436 18.5781 11.1569 17.8124C11.7918 17.6817 12.8812 17.2148 13.5224 16.7977C14.3193 16.281 15.1597 15.4406 15.6826 14.6313C16.2429 13.766 16.7409 12.384 16.8343 11.4627C16.8716 11.0269 16.8841 11.0083 17.0584 11.0394C17.2887 11.083 17.4755 10.9585 17.4755 10.7655C17.4755 10.6347 17.3074 10.4293 16.7035 9.83168C16.2616 9.38969 15.8818 9.06598 15.8133 9.06598C15.7449 9.06598 15.3651 9.40214 14.9044 9.86903C14.2757 10.4978 14.1201 10.6908 14.1512 10.7841C14.2321 11.0083 14.3193 11.0705 14.4998 11.0332C14.7052 10.9896 14.7115 11.0269 14.5807 11.6432C14.064 14.096 12.0222 15.7768 9.5383 15.8079C8.51113 15.8204 7.84503 15.6772 6.9735 15.2601C5.41719 14.5068 4.48963 13.2369 4.09744 11.3382C3.97916 10.753 4.03519 9.91261 4.24685 9.00373C4.49586 7.95789 5.42964 6.70661 6.4568 6.04674C7.16648 5.5923 7.68318 5.39309 8.52981 5.23746C8.79749 5.18766 9.0963 5.08805 9.20213 5.01958C9.62545 4.71454 9.77486 3.94261 9.48849 3.51929C9.18346 3.05863 8.73524 2.9528 7.88861 3.12088Z" fill="#535D71" />
																	<path d="M11.7973 6.37663L8.76562 9.40832L9.76166 10.4044L10.7577 11.4004L13.8081 8.35003L16.8584 5.29966L15.8811 4.3223C15.3395 3.7807 14.885 3.33871 14.8664 3.33871C14.8477 3.33871 13.4719 4.70204 11.7973 6.37663Z" fill="#535D71" />
																	<path d="M7.87285 11.1515C7.23788 12.6207 7.22543 12.6456 7.33126 12.7701C7.48066 12.9444 7.67987 12.8822 9.41671 12.1227C9.55367 12.0604 9.79645 11.9546 9.95831 11.8923C10.1264 11.8301 10.2509 11.7554 10.2384 11.7305C10.226 11.6931 8.44558 9.86914 8.4269 9.87536C8.42067 9.87536 8.17166 10.4543 7.87285 11.1515Z" fill="#535D71" />
																</svg>
															</span>
														</el-tooltip>
														<el-tooltip content="<?php esc_html_e('Cart Transaction', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_cart == '1'">
															<span class="material-icons-round">shopping_cart</span>
														</el-tooltip>
														<el-tooltip content="<?php esc_html_e('Deposit', 'bookingpress-appointment-booking'); ?>" placement="top" v-if="scope.row.is_deposit_enable == '1'">
															<span class="bpa-apc__deposit-icon">
																<svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
																	<path d="M16.9596 12.2237C16.8902 12.0273 16.746 11.8662 16.5583 11.7756C16.3706 11.685 16.1548 11.6723 15.9578 11.7402L13.7872 12.4116C13.2376 12.9125 13.0288 12.7838 9.00068 12.7838C8.90842 12.7838 8.81994 12.7471 8.75471 12.6819C8.68947 12.6167 8.65282 12.5282 8.65282 12.4359C8.65282 12.3437 8.68947 12.2552 8.75471 12.1899C8.81994 12.1247 8.90842 12.0881 9.00068 12.0881C13.1749 12.0881 13.0323 12.1681 13.3384 11.862C13.4551 11.7331 13.5206 11.5661 13.5228 11.3923C13.5228 11.2078 13.4495 11.0309 13.319 10.9004C13.1886 10.7699 13.0116 10.6966 12.8271 10.6966H9.10504C8.62152 10.6966 8.21801 10.0009 6.80919 10.0009H4.47856V13.6256L4.92729 13.8795C6.20362 14.6153 7.63128 15.0496 9.10115 15.149C10.571 15.2485 12.0442 15.0106 13.408 14.4535L16.5387 13.1908C16.7162 13.1104 16.8576 12.967 16.9354 12.7883C17.0132 12.6096 17.0218 12.4084 16.9596 12.2237ZM1 14.523H3.78285V9.30521H1V14.523ZM2.0714 12.9994C2.09103 12.9518 2.12099 12.9092 2.1591 12.8746C2.19722 12.84 2.24255 12.8142 2.29181 12.7993C2.34107 12.7843 2.39304 12.7805 2.44398 12.788C2.49491 12.7956 2.54353 12.8143 2.58633 12.8429C2.62913 12.8716 2.66504 12.9093 2.69147 12.9535C2.71791 12.9977 2.7342 13.0472 2.73919 13.0984C2.74417 13.1497 2.73771 13.2014 2.72028 13.2499C2.70285 13.2983 2.67489 13.3423 2.6384 13.3786C2.58145 13.4353 2.50661 13.4705 2.42662 13.4783C2.34663 13.4861 2.26641 13.4659 2.1996 13.4213C2.13279 13.3766 2.08351 13.3102 2.06014 13.2333C2.03677 13.1564 2.04074 13.0737 2.0714 12.9994ZM11.4357 8.95736C12.1237 8.95736 12.7962 8.75334 13.3683 8.37112C13.9403 7.98889 14.3862 7.44561 14.6494 6.80999C14.9127 6.17437 14.9816 5.47494 14.8474 4.80017C14.7132 4.1254 14.3819 3.50558 13.8954 3.01909C13.4089 2.53261 12.7891 2.20131 12.1143 2.06709C11.4395 1.93286 10.7401 2.00175 10.1045 2.26504C9.46886 2.52832 8.92558 2.97417 8.54336 3.54622C8.16113 4.11827 7.95711 4.79081 7.95711 5.4788C7.95711 6.40137 8.3236 7.28616 8.97596 7.93851C9.62831 8.59087 10.5131 8.95736 11.4357 8.95736ZM11.7835 5.82666H11.0878C10.811 5.82666 10.5456 5.71671 10.3499 5.521C10.1542 5.3253 10.0442 5.05986 10.0442 4.78309C10.0442 4.50632 10.1542 4.24088 10.3499 4.04518C10.5456 3.84947 10.811 3.73952 11.0878 3.73952V3.39167C11.0878 3.29941 11.1245 3.21093 11.1897 3.1457C11.2549 3.08046 11.3434 3.04381 11.4357 3.04381C11.5279 3.04381 11.6164 3.08046 11.6816 3.1457C11.7469 3.21093 11.7835 3.29941 11.7835 3.39167V3.73952H12.4792C12.5715 3.73952 12.66 3.77617 12.7252 3.84141C12.7904 3.90664 12.8271 3.99512 12.8271 4.08738C12.8271 4.17964 12.7904 4.26812 12.7252 4.33335C12.66 4.39859 12.5715 4.43524 12.4792 4.43524H11.0878C10.9956 4.43524 10.9071 4.47188 10.8418 4.53712C10.7766 4.60236 10.74 4.69083 10.74 4.78309C10.74 4.87535 10.7766 4.96383 10.8418 5.02906C10.9071 5.0943 10.9956 5.13095 11.0878 5.13095H11.7835C12.0603 5.13095 12.3257 5.24089 12.5214 5.4366C12.7171 5.63231 12.8271 5.89774 12.8271 6.17451C12.8271 6.45128 12.7171 6.71672 12.5214 6.91243C12.3257 7.10813 12.0603 7.21808 11.7835 7.21808V7.56594C11.7835 7.65819 11.7469 7.74667 11.6816 7.81191C11.6164 7.87714 11.5279 7.91379 11.4357 7.91379C11.3434 7.91379 11.2549 7.87714 11.1897 7.81191C11.1245 7.74667 11.0878 7.65819 11.0878 7.56594V7.21808H10.3921C10.2998 7.21808 10.2114 7.18143 10.1461 7.1162C10.0809 7.05096 10.0442 6.96248 10.0442 6.87022C10.0442 6.77797 10.0809 6.68949 10.1461 6.62425C10.2114 6.55902 10.2998 6.52237 10.3921 6.52237H11.7835C11.8758 6.52237 11.9643 6.48572 12.0295 6.42049C12.0947 6.35525 12.1314 6.26677 12.1314 6.17451C12.1314 6.08226 12.0947 5.99378 12.0295 5.92854C11.9643 5.86331 11.8758 5.82666 11.7835 5.82666Z" />
																</svg>
															</span>
														</el-tooltip>
													</div>
												</div>
											</div>
										</div>
										<div class="bpa-mpay-item--foot">
											<div class="bpa-table-status-dropdown-wrapper" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-loader-active' : ''" v-if="bookingpress_edit_payment == 1">
												<div class="bpa-tsd--loader" v-if="scope.row.change_status_loader == 1" :class="(scope.row.change_status_loader == 1) ? '__bpa-is-active' : ''">
													<div class="bpa-btn--loader__circles">
														<div></div>
														<div></div>
														<div></div>
													</div>
												</div>
												<el-select class="bpa-form-control" :class="((scope.row.payment_status == '2') ? 'bpa-appointment-status--warning' : '') || (scope.row.payment_status == '3' ? 'bpa-appointment-status--rejected' : '') || (scope.row.payment_status == '4' ? 'bpa-appointment-status--approved' : '') || (scope.row.payment_status == '1' ? 'bpa-appointment-status--completed' : '') || (scope.row.payment_status == '5' ? 'bpa-appointment-status--refund-partial' : '')" v-model="scope.row.payment_status" placeholder="<?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?>" popper-class="bpa-payment-status-dropdown-popper" @change="bookingpress_change_status(scope.row.payment_log_id, $event)">
													<el-option-group label="<?php esc_html_e('Change status', 'bookingpress-appointment-booking'); ?>">
														<el-option v-for="item in payment_status_data" :key="item.value" :label="item.text" :value="item.value"></el-option>
													</el-option-group>
												</el-select>
											</div>
											<el-tag class="bpa-front-pill " :class="((scope.row.payment_status == '2') ? '--warning' : '') || (scope.row.payment_status == '3' ? '--rejected' : '') || (scope.row.payment_status == '4' ? '--approved' : '') || (scope.row.payment_status == '1' ? '--completed' : '')" v-else>{{ scope.row.payment_status_label }}</el-tag>

											<div class="bpa-mpay-fi__actions" v-if="bookingpress_delete_payment == 1 || ( bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2'))">
												<el-popover placement="bottom" trigger="click" popper-class="bpa-dialog bpa-dailog__small bpa-dialog--add-category bpa-complete-payment-dialog" v-if="bookingpress_edit_payment == 1 && (scope.row.payment_status == '4') || (scope.row.appointment_status == '2' && scope.row.payment_status == '2') || (scope.row.is_cart == '1' && scope.row.payment_status == '2')">
													<div class="bpa-dialog-heading">
														<el-row type="flex">
															<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																<h1 class="bpa-page-heading"><?php esc_html_e('Share Payment Link', 'bookingpress-appointment-booking'); ?></h1>
															</el-col>
														</el-row>
													</div>
													<div class="bpa-dialog-body">
														<el-container class="bpa-grid-list-container bpa-add-categpry-container">
															<div class="bpa-form-row">
																<el-row>
																	<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																		<el-form label-position="top">
																			<div class="bpa-form-body-row bpa-dsu__checkbox-row">
																				<el-row>
																					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
																						<el-form-item>
																							<template #label>
																								<span class="bpa-form-label"><?php echo esc_html__('Share With', 'bookingpress-appointment-booking'); ?></span>
																							</template>
																							<el-checkbox-group v-model="bookingpress_complete_payment_link_send_options">
																								<el-checkbox class="bpa-front-label bpa-custom-checkbox--is-label" label="email"><?php esc_html_e('Through Email', 'bookingpress-appointment-booking'); ?></el-checkbox>
																								<?php
																								do_action('bookingpress_add_more_complete_payment_link_option');
																								?>
																							</el-checkbox-group>
																						</el-form-item>
																					</el-col>
																				</el-row>
																			</div>
																		</el-form>
																	</el-col>
																</el-row>
															</div>
														</el-container>
													</div>
													<div class="bpa-dialog-footer">
														<div class="bpa-hw-right-btn-group">
															<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary" @click="bpa_send_complete_payment_link(scope.row.payment_log_id)" :class="(is_send_button_loader == '1') ? 'bpa-btn--is-loader' : ''" :disabled="is_send_button_disabled">
																<span class="bpa-btn__label"><?php esc_html_e('Send Link', 'bookingpress-appointment-booking'); ?></span>
																<div class="bpa-btn--loader__circles">
																	<div></div>
																	<div></div>
																	<div></div>
																</div>
															</el-button>
														</div>
													</div>
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn__filled-light __secondary">
														<span class="material-icons-round">link</span>
													</el-button>
												</el-popover>
												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Approve', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to approve the Payment (Appointment will automatically be approved)?', 'bookingpress-appointment-booking'); ?>"
													@confirm="bpa_approve_appointment(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--primary"
													cancel-button-type="bpa-btn bpa-btn__small"
													v-if="scope.row.appointment_status == '2'">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn__filled-light __secondary">
														<span class="material-icons-round">done</span>
													</el-button>
												</el-popconfirm>

												<span v-if="( ( bpa_chk_staff_role != 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2) || ( bpa_chk_staff_role == 1 && scope.row.bookingpress_purchase_type == 1 && scope.row.bookingpress_payment_adjustment_status != 2 && bookingpress_edit_payment == 1 ) )">
													<el-button @click="bookingpress_open_additional_amount_model(event,scope.row.appointment_id,scope.row.payment_log_id, scope.$index)" class="bpa-btn bpa-btn__filled-light __danger bpa-btn-addition-amount">
														<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
															<path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 19C17.9853 19 20 16.9853 20 14.5C20 12.0147 17.9853 10 15.5 10C13.0147 10 11 12.0147 11 14.5C11 16.9853 13.0147 19 15.5 19ZM17.9134 15.0479L17.4415 14.6885C17.4475 14.6331 17.4526 14.5683 17.4526 14.4998C17.4526 14.4312 17.4477 14.3665 17.4415 14.311L17.9139 13.9515C18.0016 13.884 18.0256 13.7642 17.9697 13.6644L17.4789 12.8375C17.4265 12.7442 17.3116 12.6962 17.195 12.7381L16.6381 12.9558C16.5314 12.8808 16.4195 12.8175 16.3038 12.7669L16.219 12.1917C16.2051 12.0825 16.1076 12 15.9923 12H15.0081C14.8928 12 14.7955 12.0825 14.7818 12.19L14.6968 12.7673C14.5847 12.8165 14.4746 12.879 14.3631 12.9563L13.8047 12.7379C13.7001 12.6985 13.5749 12.7427 13.5229 12.8356L13.0315 13.6635C12.9736 13.759 12.9975 13.8829 13.0872 13.9521L13.5591 14.3115C13.5516 14.3817 13.548 14.4427 13.548 14.5C13.548 14.5573 13.5516 14.6183 13.5591 14.6888L13.0867 15.0483C12.999 15.116 12.9753 15.2358 13.0311 15.3354L13.5219 16.1623C13.5743 16.2554 13.6881 16.3037 13.8058 16.2617L14.3627 16.044C14.4692 16.1188 14.5811 16.1821 14.6968 16.2329L14.7816 16.8079C14.7955 16.9175 14.8928 17 15.0083 17H15.9925C16.1078 17 16.2053 16.9175 16.219 16.81L16.304 16.2329C16.4161 16.1835 16.526 16.1213 16.6377 16.0438L17.1961 16.2621C17.2231 16.2723 17.2511 16.2775 17.28 16.2775C17.363 16.2775 17.4393 16.2333 17.4779 16.1646L17.9708 15.3333C18.0256 15.2358 18.0016 15.116 17.9134 15.0479ZM14.6444 14.5C14.6444 14.9596 15.0282 15.3333 15.5002 15.3333C15.9721 15.3333 16.356 14.9596 16.356 14.5C16.356 14.0404 15.9721 13.6667 15.5002 13.6667C15.0282 13.6667 14.6444 14.0404 14.6444 14.5Z" fill="#727E95" />
															<path fill-rule="evenodd" clip-rule="evenodd" d="M2.85002 2.0246H14.15C14.5337 1.97081 14.9249 2.00552 15.2926 2.12596C15.6603 2.24641 15.9945 2.44929 16.2686 2.71854C16.5427 2.9878 16.7492 3.31603 16.8718 3.67726C16.9944 4.03849 17.0297 4.4228 16.975 4.79977V8.92553C16.9745 8.9697 16.9634 9.01314 16.9424 9.05222C16.9215 9.09131 16.8913 9.12492 16.8545 9.15025C16.8176 9.17559 16.7752 9.19191 16.7306 9.19788C16.6861 9.20384 16.6407 9.19927 16.5983 9.18454C16.1108 9.03452 15.6025 8.95963 15.0916 8.96253C13.7188 8.96497 12.4029 9.5018 11.4322 10.4554C10.4614 11.409 9.91498 12.7017 9.91249 14.0503C9.91096 14.2637 9.92354 14.4769 9.95016 14.6886C9.94967 14.7645 9.91875 14.8372 9.86411 14.8909C9.80948 14.9445 9.73551 14.9749 9.65824 14.9754H2.85002C2.46629 15.0292 2.07508 14.9945 1.70737 14.874C1.33965 14.7536 1.00553 14.5507 0.731439 14.2815C0.457352 14.0122 0.25083 13.684 0.128222 13.3227C0.00561449 12.9615 -0.0297127 12.5772 0.0250373 12.2002V4.79977C-0.0297127 4.4228 0.00561449 4.03849 0.128222 3.67726C0.25083 3.31603 0.457352 2.9878 0.731439 2.71854C1.00553 2.44929 1.33965 2.24641 1.70737 2.12596C2.07508 2.00552 2.46629 1.97081 2.85002 2.0246ZM2.32687 9.26916C2.48172 9.3708 2.66378 9.42506 2.85002 9.42506C3.09977 9.42506 3.33928 9.3276 3.51588 9.15411C3.69248 8.98063 3.79169 8.74534 3.79169 8.5C3.79169 8.31704 3.73646 8.13819 3.63299 7.98606C3.52952 7.83394 3.38245 7.71537 3.21038 7.64536C3.03832 7.57534 2.84898 7.55702 2.66632 7.59272C2.48365 7.62841 2.31586 7.71651 2.18417 7.84589C2.05248 7.97526 1.96279 8.14009 1.92646 8.31953C1.89012 8.49897 1.90877 8.68497 1.98004 8.854C2.05131 9.02304 2.17201 9.16751 2.32687 9.26916ZM6.93052 10.8075C7.39509 11.1124 7.94127 11.2752 8.5 11.2752C9.24923 11.2752 9.96778 10.9828 10.4976 10.4623C11.0274 9.9419 11.325 9.23602 11.325 8.5C11.325 7.95112 11.1593 7.41457 10.8489 6.9582C10.5385 6.50182 10.0973 6.14612 9.58108 5.93607C9.06488 5.72603 8.49687 5.67107 7.94887 5.77815C7.40088 5.88523 6.89751 6.14954 6.50243 6.53766C6.10735 6.92577 5.8383 7.42026 5.72929 7.95859C5.62029 8.49692 5.67624 9.05492 5.89005 9.56201C6.10387 10.0691 6.46595 10.5025 6.93052 10.8075Z" fill="#727E95" />
														</svg>
													</el-button>
												</span>

												<el-popconfirm
													confirm-button-text='<?php esc_html_e('Delete', 'bookingpress-appointment-booking'); ?>'
													cancel-button-text='<?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?>'
													icon="false"
													title="<?php esc_html_e('Are you sure you want to delete this payment transaction?', 'bookingpress-appointment-booking'); ?>"
													@confirm="deletePaymentLog(scope.row.payment_log_id)"
													confirm-button-type="bpa-btn bpa-btn__small bpa-btn--danger"
													cancel-button-type="bpa-btn bpa-btn__small" v-if="bookingpress_delete_payment == 1">
													<el-button type="text" slot="reference" class="bpa-btn bpa-btn__filled-light __danger">
														<span class="material-icons-round">delete</span>
													</el-button>
												</el-popconfirm>
												<?php
												$bookingpress_add_dynamic_action_btn_content = "";
												$bookingpress_add_dynamic_action_btn_content = apply_filters('bookingpress_payment_list_add_action_button', $bookingpress_add_dynamic_action_btn_content);
												echo $bookingpress_add_dynamic_action_btn_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
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
		<el-row class="bpa-pagination" type="flex" v-if="items.length > 0"> <!-- Pagination -->
			<el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
				<div class="bpa-pagination-left">
					<p><?php esc_html_e('Showing', 'bookingpress-appointment-booking'); ?>&nbsp;<strong><u>{{ items.length }}</u></strong> <?php esc_html_e('out of', 'bookingpress-appointment-booking'); ?>&nbsp;<strong>{{ totalItems }}</strong></p>
					<div class="bpa-pagination-per-page">
						<p><?php esc_html_e('Per Page', 'bookingpress-appointment-booking'); ?></p>
						<el-select v-model="pagination_length_val" placeholder="Select" @change="changePaginationSize($event)" class="bpa-form-control" popper-class="bpa-pagination-dropdown">
							<el-option v-for="item in pagination_val" :key="item.text" :label="item.text" :value="item.value"></el-option>
						</el-select>
					</div>
				</div>
			</el-col>
			<el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="bpa-pagination-nav">
				<el-pagination ref="bpa_pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="currentPage" layout="prev, pager, next" :total="totalItems" :page-sizes="pagination_length" :page-size="perPage"></el-pagination>
			</el-col>
			<el-container v-if="(bookingpress_edit_payment == 1|| bookingpress_delete_payment == 1) && multipleSelection.length > 0" class="bpa-default-card bpa-bulk-actions-card">
				<el-button class="bpa-btn bpa-btn--icon-without-box bpa-bac__close-icon" @click="closeBulkAction">
					<span class="material-icons-round">close</span>
				</el-button>
				<el-row type="flex" class="bpa-bac__wrapper">
					<el-col class="bpa-bac__left-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
						<span class="material-icons-round">check_circle</span>
						<p>{{ multipleSelection.length }}<?php esc_html_e(' Items Selected', 'bookingpress-appointment-booking'); ?></p>
					</el-col>
					<el-col class="bpa-bac__right-area" :xs="24" :sm="12" :md="12" :lg="12" :xl="12">
						<el-select class="bpa-form-control" v-model="bulk_action" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?>" v-if="bookingpress_edit_payment == 1 && bookingpress_delete_payment == 0">
							<el-option-group v-for="bulk_option_data in bulk_options" :key="bulk_option_data.label" :label="bulk_option_data.label" :value="bulk_option_data.label">
								<el-option v-for="bulk_action_data in bulk_option_data.bulk_actions" :label="bulk_action_data.text" :value="bulk_action_data.value" v-if="bulk_action_data.value != 'delete'"></el-option>
							</el-option-group>
						</el-select>
						<el-select class="bpa-form-control" v-model="bulk_action" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?>" v-if="bookingpress_edit_payment == 0 && bookingpress_delete_payment == 1">
							<el-option-group v-for="bulk_option_data in bulk_options" :key="bulk_option_data.label" :label="bulk_option_data.label" :value="bulk_option_data.label" v-if="bulk_option_data.value != 'change_status'">
								<el-option v-for="bulk_action_data in bulk_option_data.bulk_actions" :label="bulk_action_data.text" :value="bulk_action_data.value" v-if="bulk_action_data.value == 'delete' || bulk_action_data.value == 'bulk_action'"></el-option>
							</el-option-group>
						</el-select>
						<el-select class="bpa-form-control" v-model="bulk_action" placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?>" v-else>
							<el-option-group v-for="bulk_option_data in bulk_options" :key="bulk_option_data.label" :label="bulk_option_data.label" :value="bulk_option_data.label">
								<el-option v-for="bulk_action_data in bulk_option_data.bulk_actions" :label="bulk_action_data.text" :value="bulk_action_data.value"></el-option>
							</el-option-group>
						</el-select>
						<el-button @click="bulk_actions" class="bpa-btn bpa-btn--primary bpa-btn__medium">
							<?php esc_html_e('Go', 'bookingpress-appointment-booking'); ?>
						</el-button>
					</el-col>
				</el-row>
			</el-container>
		</el-row>
	</div>
</el-main>

<!-- View Payment Logs Modal -->

<el-dialog custom-class="bpa-dialog bpa-dialog--default bpa-dialog--manage-categories bpa-dialog--view-payment-info" title="" :visible.sync="view_payment_details_modal" :close-on-press-escape="close_modal_on_esc" @open="bookingpress_enable_modal" @close="bookingpress_disable_modal">
	<div class="bpa-dialog-heading">
		<el-row type="flex" :gutter="24">
			<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
				<h1 class="bpa-page-heading"><?php esc_html_e('View Details', 'bookingpress-appointment-booking'); ?></h1>
				<el-button class="bpa-btn bpa-btn--icon-without-box bpa-bac__close-icon" @click="ClosePaymentModal()">
					<span class="material-icons-round">close</span>
				</el-button>
			</el-col>
		</el-row>
	</div>
	<div class="bpa-back-loader-container" v-if="is_display_loader_view == '1'">
		<div class="bpa-back-loader"></div>
	</div>
	<div class="bpa-dialog-body">
		<div class="bpa-card bpa-card__body-row">
			<div class="bpa-dialog--vpi__body">
				<div class="bpa-dialog--vpi__body--head">
					<ul>
						<li :xs="12" :sm="12" :md="12" :lg="8" :xl="8">
							<span><?php esc_html_e('Customer', 'bookingpress-appointment-booking'); ?></span>
							<p v-text="view_payment_data.customer_name"></p>
						</li>
						<li :xs="12" :sm="12" :md="12" :lg="8" :xl="8">
							<span><?php esc_html_e('Appointment Date', 'bookingpress-appointment-booking'); ?></span>
							<p v-text="view_payment_data.bookingpress_appointment_date"></p>
						</li>
						<li :xs="12" :sm="12" :md="12" :lg="8" :xl="8">
							<span><?php esc_html_e('Payment Status', 'bookingpress-appointment-booking'); ?></span>
							<p v-text="view_payment_data.bookingpress_payment_status"></p>
						</li>
						</el-row>
				</div>
				<div class="bpa-dialog--vpi__body--extra-fields">
					<div class="bpa-dialog--vpi__body--ef-row">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Service', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_service_name"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Payment Gateway', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_payment_gateway"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Paid Amount', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_payment_amount"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Transaction ID', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_transaction_id"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row" v-if="view_payment_data.bookingpress_applied_coupon_code != ''">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Coupon Text', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_applied_coupon_code"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row" v-if="view_payment_data.bookingpress_coupon_discount_amount != ''">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Coupon Amount', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_coupon_discount_amount"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row" v-if="view_payment_data.bookingpress_tax_percentage != ''">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Tax Percentage', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_tax_precentage"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row" v-if="view_payment_data.bookingpress_tax_amount != ''">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Tax Amount', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_tax_amount"></p>
							</el-col>
						</el-row>
					</div>
					<div class="bpa-dialog--vpi__body--ef-row">
						<el-row type="flex">
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<span><?php esc_html_e('Payer Email', 'bookingpress-appointment-booking'); ?></span>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
								<p v-text="view_payment_data.bookingpress_payer_email"></p>
							</el-col>
						</el-row>
					</div>
				</div>
			</div>
		</div>
	</div>
</el-dialog>
<el-dialog custom-class="bpa-dialog bpa-dailog__small bpa-dialog--export-payments" id="payment_export_model" title="" :visible.sync="ExportPayment" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display" @open="bookingpress_enable_modal" @close="bookingpress_disable_modal">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
				<h1 class="bpa-page-heading"><?php esc_html_e('Export Data', 'bookingpress-appointment-booking'); ?></h1>
			</el-col>
		</el-row>
	</div>
	<div class="bpa-dialog-body">
		<el-container class="bpa-grid-list-container bpa-add-categpry-container">
			<div class="bpa-form-row">
				<el-row>
					<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
						<el-form label-position="top" @submit.native.prevent>
							<div class="bpa-form-body-row">
								<el-row>
									<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
										<el-form-item>
											<el-checkbox-group v-model="export_checked_field">
												<el-checkbox class="bpa-form-label bpa-custom-checkbox--is-label" v-for="item in payment_export_field_list" :label="item.name">{{item.text}}</el-checkbox>
											</el-checkbox-group>
										</el-form-item>
									</el-col>
								</el-row>
							</div>
						</el-form>
					</el-col>
				</el-row>
			</div>
		</el-container>
	</div>
	<div class="bpa-dialog-footer">
		<div class="bpa-hw-right-btn-group">
			<el-button class="bpa-btn bpa-btn__medium" @click="close_export_payment_model"><?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?></el-button>
			<el-button class="bpa-btn bpa-btn__medium bpa-btn--primary" :class="(is_export_button_loader == '1') ?'bpa-btn--is-loader' : ''" @click="bookingpress_export_payments" :disabled="is_export_button_disabled">
				<span class="bpa-btn__label"><?php esc_html_e('Export', 'bookingpress-appointment-booking'); ?></span>
				<div class="bpa-btn--loader__circles">
					<div></div>
					<div></div>
					<div></div>
				</div>
			</el-button>
		</div>
	</div>
</el-dialog>

<el-dialog id="refund_confirm_process" custom-class="bpa-dialog bpa-dailog__small bpa-dialog--refund-process" title="" :visible.sync="refund_confirm_modal" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="12" :sm="12" :md="16" :lg="16" :xl="16">
				<h1 class="bpa-page-heading"><?php esc_html_e('Refund', 'bookingpress-appointment-booking'); ?></h1>
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
									<el-form-item prop="refund_type">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e('Refund Type', 'bookingpress-appointment-booking'); ?></span>
										</template>
										<el-radio v-model="refund_confirm_form.refund_type" label="full"><?php esc_html_e('Full refund', 'bookingpress-appointment-booking'); ?></el-radio>
										<el-radio v-model="refund_confirm_form.refund_type" label="partial" v-if="refund_confirm_form.allow_partial_refund == 1"><?php esc_html_e('Partial refund', 'bookingpress-appointment-booking'); ?></el-radio>
										<el-radio v-model="refund_confirm_form.refund_type" label="partial" disabled v-else><?php esc_html_e('Partial refund', 'bookingpress-appointment-booking'); ?></el-radio>
									</el-form-item>
								</el-col>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
									<el-form-item prop="refund_appointment_status">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e('Appointment status', 'bookingpress-appointment-booking'); ?></span>
										</template>
										<el-select v-model="refund_confirm_form.refund_appointment_status" class="bpa-form-control" placeholder="<?php esc_html_e('Select Status', 'bookingpress-appointment-booking'); ?>">
											<el-option v-for="appointment_status in bookingpress_payment_appointment_status" :key="appointment_status.text" :label="appointment_status.text" :value="appointment_status.value"></el-option>
										</el-select>
									</el-form-item>
								</el-col>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="refund_confirm_form.refund_type == 'partial'">
									<el-form-item prop="refund_amount">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e('Refund Amount', 'bookingpress-appointment-booking'); ?> ({{refund_confirm_form.refund_currency}})</span>
										</template>
										<el-input @input="isValidateZeroDecimal" class="bpa-form-control" v-model="refund_confirm_form.refund_amount"></el-input>
									</el-form-item>
								</el-col>
								<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
									<el-form-item prop="refund_reason">
										<template #label>
											<span class="bpa-form-label"><?php esc_html_e('Refund Reason', 'bookingpress-appointment-booking'); ?></span>
										</template>
										<el-input class="bpa-form-control" v-model="refund_confirm_form.refund_reason" placeholder="<?php esc_html_e('Refund Reason', 'bookingpress-appointment-booking') ?>" type="textarea" :rows="3"></el-input>
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
			<el-button class="bpa-btn bpa-btn__small" @click="close_refund_confirm_model"><?php esc_html_e('Cancel', 'bookingpress-appointment-booking'); ?></el-button>
			<el-button class="bpa-btn bpa-btn__small bpa-btn--primary" :class="(is_display_refund_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="bookingpress_apply_for_refund(refund_confirm_form.payment_id,refund_confirm_form.appointment_id)" :disabled="is_refund_btn_disabled">
				<span class="bpa-btn__label"><?php esc_html_e('Apply', 'bookingpress-appointment-booking'); ?></span>
				<div class="bpa-btn--loader__circles">
					<div></div>
					<div></div>
					<div></div>
				</div>
			</el-button>
		</div>
	</div>
</el-dialog>

<el-dialog title="<?php esc_html_e('Additional Amount', 'bookingpress-appointment-booking') ?>" :visible.sync="adjustAmountDialogVisible" :width="current_screen_size === 'mobile' ? '100%' : '30%'" class="bpa-additiona-amount-dialog-container">
	<el-form :model="adjustAmountForm">
		<el-form-item label="<?php esc_html_e('Details', 'bookingpress-appointment-booking') ?>">
			<el-input class="bpa-form-control" :rows="3" type="textarea" v-model="adjustAmountForm.details"></el-input>
		</el-form-item>
		<el-form-item :label="'<?php esc_html_e('Amount', 'bookingpress-appointment-booking') ?> (' + adjustAmountForm.currency_symbol + ')'">
			<el-input class="bpa-form-control" @input="isNumberValidate($event)" v-model="adjustAmountForm.amount" v-if="bookingpress_decimal_points != '0'"></el-input>
			<el-input class="bpa-form-control" @input="isValidateZeroDecimal($event)" v-model="adjustAmountForm.amount" placeholder="<?php esc_html_e('Amount', 'bookingpress-appointment-booking') ?>" v-else></el-input>
		</el-form-item>
	</el-form>

	<span slot="footer" class="dialog-footer">
		<el-button class="bpa-btn bpa-add-amount-save-btn" @click="bpa_apply_payment_adjustment" :class="(is_display_adjustment_loader == '1') ? 'bpa-btn--is-loader' : ''" :disabled="is_adjustment_btn_disabled">
			<span class="bpa-btn__label bpa-save-text-plus">+</span>
			<span class="bpa-btn__label bpa-save-text-credit-amt">Credit Amount</span>
			<div class="bpa-btn--loader__circles">
				<div></div>
				<div></div>
				<div></div>
			</div>
		</el-button>
	</span>
</el-dialog>


<?php do_action('bookingpress_payment_page_dialog_outside'); ?>