<?php
	global $wpdb, $bookingpress_ajaxurl, $BookingPress, $bookingpress_common_date_format, $tbl_bookingpress_appointment_bookings,$BookingPressPro, $bookingpress_global_options;	

	$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
	$bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) : esc_html_e('Staff Member', 'bookingpress-appointment-booking');
	$bookingpress_plural_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) ? stripslashes_deep($bookingpress_global_options_arr['bookingpress_staffmember_plural_name']) : esc_html_e('Staff Members', 'bookingpress-appointment-booking');

?>
<el-main class="bpa-main-listing-card-container bpa-default-card bpa--is-page-scrollable-tablet" id="all-page-main-container" :class="(bookingpress_staff_customize_view == 1 ) ? 'bpa-main-list-card__is-staff-custom-view':''">
    <el-row type="flex" class="bpa-mlc-head-wrap">
        <?php if ($BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
            <el-col :xs="20" :sm="20" :md="20" :lg="20" :xl="20" class="bpa-mlc-left-heading">
                <h1 class="bpa-page-heading"><?php esc_html_e('My Commission', 'bookingpress-appointment-booking'); ?></h1>
            </el-col> 
            <el-col :xs="4" :sm="4" :md="4" :lg="4" :xl="4" class="bpa-mlc-right-commission">
                <span class="bpa-page-commission-rate"><?php esc_html_e('Commission Rate', 'bookingpress-appointment-booking'); ?>:</span>
                <span class="bpa-staff-page-commission-rate">{{staffmember_commission_rate}}%</span>
            </el-col> 
        <?php } else {  ?>
        <el-col :xs="24" :sm="12" :md="12" :lg="12" :xl="12" class="bpa-mlc-left-heading">
            <h1 class="bpa-page-heading"><?php esc_html_e('Manage Commissions', 'bookingpress-appointment-booking'); ?></h1>
        </el-col>         
        <?php } ?>
        
    </el-row>
    <div class="bpa-back-loader-container" id="bpa-page-loading-loader">
        <div class="bpa-back-loader"></div>
    </div>
    <div id="bpa-main-container">
        <div class="bpa-table-filter"> 
            <el-row type="flex" :gutter="32"> 
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                    <span class="bpa-form-label"><?php esc_html_e('Appointment Date', 'bookingpress-appointment-booking'); ?></span>
                    <el-date-picker @focus="bookingpress_remove_date_range_picker_focus" class="bpa-form-control bpa-form-control--date-range-picker" :format="bpa_date_common_date_format" v-model="appointment_date_range" type="daterange" start-placeholder="<?php esc_html_e('Start date', 'bookingpress-appointment-booking'); ?>" end-placeholder="<?php esc_html_e('End date', 'bookingpress-appointment-booking'); ?>" :popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar bpa-date-range-picker-widget-wrapper" range-separator=" - " value-format="yyyy-MM-dd" :picker-options="filter_pickerOptions" :locale="site_locale"> </el-date-picker>
                </el-col> 
                <?php
				if ( ! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
					?>
				<el-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" v-if="is_staffmember_activated == 1" >
					<span class="bpa-form-label"><?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_plural_staffmember_name); ?></span>	
					<el-select class="bpa-form-control bpa-from-select-tab" v-model="search_staff_member_name" multiple filterable collapse-tags 
					placeholder="<?php esc_html_e('Select', 'bookingpress-appointment-booking'); ?><?php echo " ".esc_html($bookingpress_plural_staffmember_name); ?>"
					:popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
						<el-option v-for="item in search_staff_member_list" :key="item.value" :label="item.text" :value="item.value">	
						</el-option>
					</el-select>
				</el-col>
					<?php
				}
				?>
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                    <span class="bpa-form-label"><?php esc_html_e('Service', 'bookingpress-appointment-booking'); ?></span>
                    <el-select class="bpa-form-control bpa-from-select-tab" v-model="search_service_name" multiple filterable collapse-tags 
                        placeholder="<?php esc_html_e('Select service', 'bookingpress-appointment-booking'); ?>"
                        :popper-append-to-body="false" popper-class="bpa-el-select--is-with-navbar">
                       <el-option-group v-for="service_cat_data in appointment_services_data" :key="service_cat_data.category_name" :label="service_cat_data.category_name">
                            <el-option v-for="service_data in service_cat_data.category_services" :key="service_data.service_id" :label="service_data.service_name" :value="service_data.service_id"></el-option>
                        </el-option-group>
                    </el-select>
                </el-col>
                <?php
                if ($BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6" ></el-col>
                <?php } ?>
                <el-col :xs="24" :sm="24" :md="24" :lg="6" :xl="6">
                    <div class="bpa-tf-btn-group">
                        <el-button class="bpa-btn bpa-btn__medium bpa-btn--full-width" @click="resetFilter">
                            <?php esc_html_e('Reset', 'bookingpress-appointment-booking'); ?>
                        </el-button>
                        <el-button class="bpa-btn bpa-btn__medium bpa-btn--primary bpa-btn--full-width" @click="loadCommissions(true)">
                            <?php esc_html_e('Apply', 'bookingpress-appointment-booking'); ?>
                        </el-button>
                    </div>
                </el-col>
            </el-row><br>
        </div>
        <div id="bpa-loader-div"> <!-- No Records found -->
            <el-row type="flex" v-show="commission_items.length == 0">
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
        </div>
        <el-row v-if="commission_items.length > 0"> <!-- Content -->
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <el-container class="bpa-table-container">
					<div class="bpa-back-loader-container bpa-back-loader-inner-container" v-if="is_display_loader == '1'">
						<div class="bpa-back-loader"></div>
					</div>
                    <div class="bpa-tc__wrapper">
                        <el-table ref="multipleTable" class="bpa-manage-appointment-items bpa-manage-commission-items" :data="commission_items" fit="false">
                            <el-table-column type="expand"> 
                            </el-table-column>
                            <?php if ( ! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
                                <el-table-column type="selection"></el-table-column>
                            <?php } ?>
                            <el-table-column prop="appointment_id" min-width="50" label="<?php esc_html_e( 'Booking ID', 'bookingpress-appointment-booking' ); ?>">
								<template slot-scope="scope">
									<span>#{{ scope.row.appointment_id }}</span>
								</template>
							</el-table-column>
                            <el-table-column prop="appointment_date" min-width="110" label="<?php esc_html_e( 'Appointment Date', 'bookingpress-appointment-booking' ); ?>">
                            </el-table-column>
                            <?php if (!$BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) { ?>
                            <el-table-column prop="staffmember_name" min-width="120" label="<?php echo esc_html($bookingpress_singular_staffmember_name); ?>">
                            <?php } ?>
							</el-table-column>
                            <el-table-column prop="service_name" min-width="110" label="<?php esc_html_e( 'Service', 'bookingpress-appointment-booking' ); ?>">
                                <template #default="scope">
                                    <span>{{ scope.row.service_name }}</span>
                                    <el-popover v-if="scope.row.bookingpress_multiple_service_total > 0" placement="bottom-start" title="Other" width="280" trigger="hover" popper-class="bpa-commission-services-custom-popover">
                                        <div>{{ scope.row.bookingpress_multiple_service_extra_name }}</div>
                                        <span slot="reference">
                                            <el-link>+{{ scope.row.bookingpress_multiple_service_total }}</el-link>
                                        </span>
                                    </el-popover>
                                </template>
                            </el-table-column>
                            <el-table-column prop="commission_amount" min-width="80" label="<?php esc_html_e( 'Commission', 'bookingpress-appointment-booking' ); ?>" >
                                 <template slot-scope="scope">
                                        {{ scope.row.commission_amount_with_currency }}
                                </template>
							</el-table-column>                           
                        </el-table>
                    </div>
                </el-container>
            </el-col>
        </el-row>
        <el-row class="bpa-pagination" type="flex" v-if="commission_items.length > 0"> <!-- Pagination -->
            <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <div class="bpa-pagination-left">
                    <p><?php esc_html_e('Showing', 'bookingpress-appointment-booking'); ?> <strong><u>{{ commission_items.length }}</u></strong>&nbsp;<?php esc_html_e('out of', 'bookingpress-appointment-booking'); ?>&nbsp;<strong>{{ totalItems }}</strong></p>
                    <div class="bpa-pagination-per-page">
                        <p><?php esc_html_e('Per Page', 'bookingpress-appointment-booking'); ?></p>
                    <el-select v-model="pagination_length_val" placeholder="Select" @change="changePaginationSize($event)" class="bpa-form-control" popper-class="bpa-pagination-dropdown">
                            <el-option v-for="item in pagination_val" :key="item.text" :label="item.text" :value="item.value"></el-option>
                        </el-select>
                    </div>
                </div>
            </el-col>
            <el-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12" class="bpa-pagination-nav">
            <el-pagination @size-change="handleSizeChange" @current-change="handleCurrentChange" :current-page.sync="currentPage" layout="prev, pager, next" :total="totalItems" :page-sizes="pagination_length" :page-size="perPage"></el-pagination>
			</el-col>            
        </el-row>
    </div>
</el-main>