<?php
	global $bookingpress_ajaxurl, $BookingPressPro, $bookingpress_common_date_format;
	$bookingpress_edit_staff_service = $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_staff_service' );	
?>
<el-main class="bpa-main-listing-card-container bpa-default-card bpa-myservices-contanier bpa--is-page-non-scrollable-mob" :class="(bookingpress_staff_customize_view == 1 ) ? 'bpa-main-list-card__is-staff-custom-view':''" id="all-page-main-container">
	<el-row type="flex" class="bpa-mlc-head-wrap">
		<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" class="bpa-mlc-left-heading">
			<h1 class="bpa-page-heading"><?php esc_html_e( 'My Services', 'bookingpress-appointment-booking' ); ?></h1>
			<span class="bpa-ms__count-pill">{{ assigned_services_details.length }} <?php esc_html_e( 'Assigned Services', 'bookingpress-appointment-booking' ); ?></span>
		</el-col>
	</el-row>
	<div class="bpa-back-loader-container" id="bpa-page-loading-loader">
		<div class="bpa-back-loader"></div>
	</div>
	<div id="bpa-main-container">
		<div class="bpa-myservices--items-wrapper">
			<div class="bpa-myservice__items-row">
				<el-row type="flex" :gutter="28">
					<el-col :xs="24" :sm="24" :md="12" :lg="12" :xl="08" v-for="(service_data, index) in assigned_services_details">
						<div class="bpa-ms__item">
							<div class="bpa-ms-item__head">
								<div class="bpa-ih__left">
									<div class="bpa-ih--avatar">
										<el-image class="bpa-assigned-service-avatar" :src="service_data.bookingpress_service_img_url"></el-image>
									</div>
									<div class="bpa-ih--service-brief">
										<h4>{{ service_data.bookingpress_service_name }}</h4>
										<div class="bpa-sb__row">
											<div class="bpa-sb__item">
												<p><span class="material-icons-round">watch_later</span>{{ service_data.bookingpress_service_duration }}</p>
												<p><span class="material-icons-round">group</span><?php esc_html_e( 'Max', 'bookingpress-appointment-booking' ); ?>: {{ service_data.bookingpress_max_capacity }}</p>
											</div>
										</div>
									</div>
								</div>
								<div class="bpa-ih__right">
									<h4 class="bpa-ih--service-price">{{ service_data.bookingpress_service_formatted_price }}</h4>
									<?php if ( $bookingpress_edit_staff_service==1 ) { ?>
										<el-button class="bpa-btn bpa-btn--icon-without-box" @click="edit_staff_member_price(event, service_data, index )">
											<span class="material-icons-round">mode_edit</span>
										</el-button>
										<?php } ?>
									<span class="bpa-deposit-amount" v-if="service_data.bookingpress_deposit_amount != '0'"><?php esc_html_e( 'Deposit', 'bookingpress-appointment-booking' ); ?>: {{ service_data.bookingpress_deposit_amount }}</span>
								</div>
							</div>
							<div class="bpa-ms-item__category">
								<div class="bpa-category--item">
									<p><span class="material-icons-round">dns</span>{{ service_data.bookingpress_category }}</p>
								</div>
							</div>
							<div class="bpa-ms-item__desc">
								<p>{{ service_data.bookingpress_service_description }}</p>
							</div>
							<div class="bpa-ms-item__extras" v-if="service_data.bookingpress_extra_services.length > 0 && service_extra_activated == '1'" >
								<h5><?php esc_html_e( 'Extra\'s', 'bookingpress-appointment-booking' ); ?></h5>
								<div class="bpa-extra--item-row">
									<div class="bpa-extra__item" v-for="extra_service_data in service_data.bookingpress_extra_services">
										<h4><span class="material-icons-round">check</span>{{ extra_service_data.service_name }} - <strong> {{ extra_service_data.service_formatted_price }}</strong></h4>										
									</div>
								</div>
							</div>
							<?php do_action( 'bookingpress_myservices_staff_panel_external_content' ); ?>
							
						</div>
					</el-col>
				</el-row>
			</div>
		</div>
	</div>
</el-main>
<el-dialog id="service_price_edit_model" :visible.sync="open_edit_service_price_model" title="" custom-class="bpa-dialog bpa-dailog__small bpa-dialog--edit-service-price bpa-dialog--add-assign-staff" :modal="is_mask_display" top="10px" :close-on-press-escape="close_modal_on_esc" :modal="is_mask_display" @open="bookingpress_enable_modal" @close="bookingpress_disable_modal">
	<div class="bpa-dialog-heading">
		<el-row type="flex">
			<el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
				<h1 class="bpa-page-heading"><?php esc_html_e( 'Edit Price', 'bookingpress-appointment-booking' ); ?></h1>
			</el-col>	
		</el-row>
	</div>
	<div class="bpa-dialog-body">
		<el-container class="bpa-grid-list-container bpa-add-categpry-container">
			<div class="bpa-form-row">
				<el-form class="bpa-add-appointment-form" ref="appointment_formdata" label-position="top" @submit.native.prevent>
					<el-form-item v-if="typeof edit_service_details.enable_custom_service_duration == 'undefined' || edit_service_details.enable_custom_service_duration == false || edit_service_details.enable_custom_service_duration == 'false'">
						<template #label> 
							<span class="bpa-form-label"><?php esc_html_e( 'Price', 'bookingpress-appointment-booking' ); ?>($)</span>
						</template> 
							<el-input class="bpa-form-control" placeholder="0.00" v-model="edit_service_details.bookingpress_service_price" @input="staffmember_service_price_validate($event)"/>
					</el-form-item> 
					<?php
						do_action('bookingpress_add_staff_custom_service_duration_price_field');
					?>	
				</el-form>
			</div>
		</el-container>
	</div>
	<div class="bpa-dialog-footer">
		<div class="bpa-hw-right-btn-group">
			<el-button class="bpa-btn bpa-btn__small" @click="bookingpress_close_edit_staffmember_price_modal()"><?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?></el-button>
			<el-button class="bpa-btn bpa-btn__small bpa-btn--primary" @click="bookingpress_save_edit_staffmember_price_data(edit_service_details)"><?php esc_html_e( 'Save', 'bookingpress-appointment-booking' ); ?></el-button>
		</div>
	</div>
</el-dialog>