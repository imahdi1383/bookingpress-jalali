<?php
global $BookingPress;

$bookingpress_searchbox_enable = $BookingPress->bookingpress_get_customize_settings('bpa_enable_searchbox','booking_form');
$bookingpress_searchbox_data = $BookingPress->bookingpress_get_customize_settings('bookingpress_selected_searchbox','booking_form');
$bookingpress_searchbox_data = explode(",",$bookingpress_searchbox_data);

$show_only_name = false;
$bpa_staff_info = $BookingPress->bookingpress_get_customize_settings('bookingpress_staffmember_information', 'booking_form');
$bpa_show_staff_bio = $BookingPress->bookingpress_get_customize_settings('show_staffmember_bio', 'booking_form');
$no_staffmember_available = $BookingPress->bookingpress_get_settings('no_staffmember_available','message_setting');
$no_staffmember_available = stripslashes_deep( $no_staffmember_available );

if( $bpa_staff_info == 4 && 'false' == $bpa_show_staff_bio ){
    $show_only_name = true;
}

$show_only_name = apply_filters( 'bookingpress_show_only_staff_name', $show_only_name );

?>
<div class="bpa-front-tabs--panel-body" :class="[bookingpress_current_tab == 'staffmembers' ? ' __bpa-is-active' : '']" v-if="typeof bookingpress_sidebar_step_data['staffmembers'] != 'undefined'">
    <div class="bpa-front-default-card">
        <div class="bpa-front-toast-notification --bpa-error" v-if="is_display_error == '1'" :aria-label="is_error_msg">
            <div class="bpa-front-tn-body">
                <p>{{ is_error_msg }}</p>
            </div>
        </div> 
        <div class="bpa-front-dc--body">
            <el-row>
                <div class="bpa_search_service_data_cls" role="search">
                    <?php 
                        if( ($bookingpress_searchbox_enable === true || $bookingpress_searchbox_enable === 'true') && ( is_array($bookingpress_searchbox_data) && in_array("search_staffmember_step", $bookingpress_searchbox_data)) ){ ?>
                        <el-input v-model="bpa_search_staff_data" @input.native="bpa_search_staff(bpa_search_staff_data=$event.target.value)" :placeholder="appointment_step_form_data.staff_search_placeholder" class="bpa-front-form-control bpa_search_cls" tabindex="0" role="searchbox"> </el-input>
                    <?php } ?>
                </div>                
                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-if="570 < window.innerWidth">
                    <?php do_action('bookingpress_multi_staff_selection_front_booking_step'); ?>
                    <div v-if="typeof appointment_step_form_data.is_allow_club_staff_selection == 'undefined' || (typeof appointment_step_form_data.is_allow_club_staff_selection != 'undefined' && appointment_step_form_data.is_allow_club_staff_selection == '0')" class="bpa-front-module-container bpa-front-module--staff">
                        <div class="bpa-front-module-heading" :aria-label="staffmember_heading_title"> {{staffmember_heading_title}}</div>
                        <div class="bpa-front-module--staff-item-row" role="list" ref="staffmemberItemsGroup" tabindex="0" @focus="(typeof focusFirstStaff !== 'undefined')? focusFirstStaff() : ''">
                            <!-- ANY STAFF DIV START -->
                            <div class="bpa-front-sm--col --bpa-sm-any-staff-col --bpa-sm-is-any-staff-col <?php echo ( $show_only_name ) ? 'bpa-front-sm--col-only-name' : ''; ?>" @click="bookingpress_select_any_staffmember()" :class="(appointment_step_form_data.bookingpress_selected_staff_member_details.is_any_staff_option_selected == '1') ? '__bpa-is-selected' : ''" v-if="is_any_staff_option_enable == 1">
                                <div class="bpa-front-sm-card bpa_focusable" tabindex="-1" role="listitem" ref="staffmember"  v-on:keydown.enter="bookingpress_select_any_staffmember()" @keydown="(typeof handle_staff_key_events != 'undefined') ? handle_staff_key_events($event):''">
                                    <div class="bpa-front-sm-card__left">
                                        <svg width="74" height="74" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.9995 73.999C57.4338 73.999 73.999 57.4338 73.999 36.9995C73.999 16.5652 57.4338 0 36.9995 0C16.5652 0 0 16.5652 0 36.9995C0 57.4338 16.5652 73.999 36.9995 73.999Z" /><path d="M36.9996 33.1535C41.6922 33.1535 45.4963 29.3494 45.4963 24.6568C45.4963 19.9643 41.6922 16.1602 36.9996 16.1602C32.307 16.1602 28.5029 19.9643 28.5029 24.6568C28.5029 29.3494 32.307 33.1535 36.9996 33.1535Z" fill="white"/><path d="M49.8919 56.3804C53.9733 56.3804 56.6507 52.1381 54.9279 48.4362C51.7861 41.682 44.9408 36.998 36.9996 36.998C29.0585 36.998 22.2147 41.6805 19.0713 48.4362C17.3502 52.1381 20.026 56.3804 24.1074 56.3804H49.8919Z" fill="white"/></svg>
                                    </div>
                                    <div class="bpa-front-sm-card__body">
                                        <div class="bpa-front-sm-card__body--name">
                                            <span :aria-label="any_staff_title">{{ any_staff_title }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ANY STAFF DIV END -->
                            <div class="bpa-front-sm--col <?php echo ( $show_only_name ) ? 'bpa-front-sm--col-only-name' : ''; ?>" v-if="staffmember_details.is_display_staff == true && staffmember_details.is_display_staff_with_flag == true" v-for="(staffmember_details, index) in bookingpress_staffmembers_details" :class="[(((appointment_step_form_data.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)) ? '__bpa-is-selected' : ''), ( false == staffmember_details.show_bio ? 'bpa-front-sm--col-only-name bpa-front-sm--col-single-only-name' : '' )]" v-show="( '' == bpa_search_staff_data || ( '' != bpa_search_staff_data && staffmember_details.show_with_staff_search ) )" :data-id="staffmember_details.bookingpress_staffmember_id">
                                <div class="bpa-front-sm-card bpa_focusable" :class="((appointment_step_form_data.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)) ? '__bpa-is-active' : ''" @keydown="(typeof handle_staff_key_events != 'undefined') ? handle_staff_key_events($event) : ''" @click="(appointment_step_form_data.is_club_staff == '1')?bookingpress_select_multi_staffmember(staffmember_details.bookingpress_staffmember_id, 0):bookingpress_select_staffmember(staffmember_details.bookingpress_staffmember_id, 0, $event)" :data-id="staffmember_details.bookingpress_staffmember_id" tabindex="-1" role="listitem" ref="staffmember">
                                    <div class="bpa-front-sm-card__left" v-if="staffmember_details.staffmember_avatar_url != ''">
                                        <img class="bpa-front-sm__avatar" :src="staffmember_details.staffmember_avatar_url" :alt="staffmember_details.bookingpress_staffmember_firstname + ' ' + staffmember_details.bookingpress_staffmember_lastname">
                                    </div>
                                    <div class="bpa-front-sm-card__left" v-else>
                                        <div class="bpa-front-sm__default-avatar">
                                            <svg viewBox="0 0 252 210" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="252" height="210" rx="12" fill="#CFD6E5" fill-opacity="0.5"/><g clip-path="url(#clip0_269_1959)"><path d="M125.43 103.536C130.724 103.536 135.308 101.637 139.054 97.8911C142.8 94.1454 144.699 89.5623 144.699 84.2675C144.699 78.9746 142.8 74.3908 139.054 70.6439C135.307 66.8988 130.724 65 125.43 65C120.135 65 115.552 66.8988 111.806 70.6445C108.061 74.3902 106.161 78.9739 106.161 84.2675C106.161 89.5623 108.061 94.146 111.807 97.8917C115.553 101.637 120.137 103.536 125.43 103.536Z"/><path d="M159.145 126.516C159.037 124.957 158.819 123.257 158.497 121.461C158.172 119.652 157.754 117.942 157.254 116.379C156.737 114.763 156.034 113.168 155.164 111.639C154.262 110.052 153.203 108.67 152.014 107.533C150.771 106.343 149.248 105.387 147.488 104.689C145.734 103.995 143.79 103.644 141.71 103.644C140.894 103.644 140.104 103.979 138.579 104.972C137.64 105.584 136.542 106.292 135.316 107.075C134.268 107.743 132.849 108.368 131.095 108.935C129.384 109.488 127.647 109.769 125.933 109.769C124.218 109.769 122.482 109.488 120.769 108.935C119.018 108.369 117.598 107.743 116.551 107.076C115.337 106.3 114.239 105.592 113.286 104.971C111.762 103.978 110.972 103.643 110.155 103.643C108.075 103.643 106.132 103.995 104.378 104.69C102.619 105.386 101.096 106.343 99.8519 107.533C98.6636 108.671 97.6034 110.052 96.7025 111.639C95.834 113.168 95.1309 114.762 94.6133 116.379C94.1134 117.942 93.6953 119.652 93.3706 121.461C93.049 123.254 92.8304 124.955 92.7224 126.518C92.6162 128.048 92.5625 129.637 92.5625 131.242C92.5625 135.418 93.89 138.799 96.5078 141.292C99.0933 143.752 102.514 145 106.674 145H145.195C149.355 145 152.775 143.753 155.361 141.292C157.979 138.8 159.307 135.419 159.307 131.241C159.306 129.629 159.252 128.039 159.145 126.516Z"/></g><defs><clipPath id="clip0_269_1959"><rect width="80" height="80" transform="translate(86 65)"/></clipPath></defs></svg>
                                        </div>
                                    </div>
                                    <div class="bpa-front-sm-card__body">
                                        <div class="bpa-front-sm-card__body--name">
                                            <span>{{staffmember_details.bookingpress_staffmember_firstname}} {{staffmember_details.bookingpress_staffmember_lastname}}</span>
                                        </div>
                                        <?php do_action('bookingpress_front_staffmember_details_outside'); ?>
                                    </div>
                                    <?php do_action( 'bookingpress_front_staffmember_external_details_outside'); ?>
                                    <div class="bpa-front-sm-card__inner-body">
                                        <div class="bpa-front-sm-card__inner-body-wrapper">
                                            <div class="bpa-front-sm-card__inner-body--name">
                                                <span>{{ staffmember_details.bookingpress_staffmember_firstname}} {{staffmember_details.bookingpress_staffmember_lastname }}</span>
                                            </div>
                                            <?php do_action('bookingpress_front_staffmember_inner_details_outside') ?>
                                            <div class="bpa-front-sm-card__inner-body--bio" v-if="staffmember_details.show_staffmember_bio == 1"><span v-html="staffmember_details.staffmember_bio"></span></div>
                                            <div class="bpa-front-sm-card__inner-body-item" v-if="staffmember_details.staffmember_information_rule == '1' || staffmember_details.staffmember_information_rule == '2'">
                                                <span class="bpa-front-sm-card__inner-body-item-wrapper bpa-front-sm-card__inner-item-icon"><svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.95 0.600342C12.0163 0.600342 12.9968 0.865512 13.7136 1.52808C14.4387 2.19851 14.7996 3.18793 14.7996 4.4353V8.9646C14.7996 10.212 14.4387 11.2014 13.7136 11.8718C12.9968 12.5344 12.0163 12.7996 10.95 12.7996H4.44995C3.38362 12.7996 2.40305 12.5344 1.68628 11.8718C0.961155 11.2014 0.600342 10.212 0.600342 8.9646V4.4353C0.600342 3.18793 0.961155 2.19851 1.68628 1.52808C2.40305 0.865512 3.38362 0.600342 4.44995 0.600342H10.95Z" stroke="#535D71" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.7 4.69995L8.42251 7.43375C7.99692 7.78869 7.403 7.78869 6.97741 7.43375L3.69995 4.69995" stroke="#535D71" stroke-width="1.2" stroke-linecap="round"/></svg> {{staffmember_details.bookingpress_staffmember_email}}</span>
                                            </div>
                                            <div class="bpa-front-sm-card__inner-body-item" v-if="'' != staffmember_details.bookingpress_staffmember_phone && (staffmember_details.staffmember_information_rule == '1' || staffmember_details.staffmember_information_rule == '3')">
                                                <span class="bpa-front-sm-card__inner-body-item-wrapper bpa-front-sm-card__inner-item-icon"><svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.04663 0.600342C4.59941 0.60046 5.09163 0.93485 5.29077 1.44409L5.3269 1.54761L5.33081 1.56421L5.33472 1.57983L5.99194 4.31909L5.99292 4.32104C6.06493 4.62645 5.97683 4.96031 5.74097 5.19019L5.73999 5.18921L4.95581 5.95581C5.1141 6.28455 5.29706 6.60442 5.50073 6.90796H5.49976C5.76305 7.29688 6.06606 7.66222 6.39917 7.9978C6.73564 8.3323 7.10313 8.63664 7.4939 8.90112C7.79835 9.10537 8.11735 9.2871 8.44409 9.44214L9.35034 8.51929C9.58662 8.27773 9.93523 8.18586 10.2615 8.28101H10.2625L12.8855 9.04468L12.907 9.05151L12.9275 9.05835C13.4498 9.25263 13.7994 9.75462 13.7996 10.3132V12.8845C13.7993 13.39 13.3907 13.7993 12.8845 13.7996H11.6208C8.67858 13.7995 5.90881 12.652 3.82788 10.571C1.74725 8.49021 0.600425 5.72119 0.600342 2.77905V1.51538C0.600556 1.00954 1.00941 0.600561 1.51538 0.600342H4.04663Z" stroke="#535D71" stroke-width="1.2"/></svg> {{staffmember_details.bookingpress_staffmember_phone}}</span>
                                            </div>
                                        </div>
                                        <div class="bpa-front-sm-card__inner-footer">
                                            <el-button type="button" class="bpa-front-sm-card__inner-button bpa_focusable" :class="(((appointment_step_form_data.bookingpress_selected_staff_member_details.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id))) ? ' bpa-sm-card__active ' : ''">
                                                <span v-if="(((appointment_step_form_data.bookingpress_selected_staff_member_details.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)))">{{appointment_step_form_data.staff_member_selected_text}}</span>
                                                <span v-else>{{appointment_step_form_data.staff_member_select_text}}</span>
                                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.33333 0C3.73333 0 0 3.73333 0 8.33333C0 12.9333 3.73333 16.6667 8.33333 16.6667C12.9333 16.6667 16.6667 12.9333 16.6667 8.33333C16.6667 3.73333 12.9333 0 8.33333 0ZM6.075 11.9083L3.08333 8.91667C2.75833 8.59167 2.75833 8.06667 3.08333 7.74167C3.40833 7.41667 3.93333 7.41667 4.25833 7.74167L6.66667 10.1417L12.4 4.40833C12.725 4.08333 13.25 4.08333 13.575 4.40833C13.9 4.73333 13.9 5.25833 13.575 5.58333L7.25 11.9083C6.93333 12.2333 6.4 12.2333 6.075 11.9083Z"/></svg>
                                            </el-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bpa-empty-view-cls" v-if="'undefined' != typeof no_staffmember_available && no_staffmember_available != '' && no_staffmember_available == true">
                        <div class="bpa-front-module-container bpa-front-module--staff bpa-front__no-timeslots-body" v-if="'undefined' != typeof no_staffmember_available && no_staffmember_available != '' && no_staffmember_available == true">
                            <svg viewBox="0 0 120 121" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M108.486 103.08C101.042 112.139 86.7296 109.719 75.3575 112.572C64.3105 115.344 53.4388 123.192 42.6284 119.606C31.8342 116.026 27.8283 103.242 20.6455 94.4249C13.5882 85.7617 2.04617 79.3615 0.797986 68.2575C-0.448903 57.1649 8.61128 47.9531 14.3452 38.376C19.5416 29.6967 24.6347 21.093 32.6953 14.9808C41.3289 8.43396 51.0768 2.35675 61.9118 2.30667C72.8285 2.25621 82.086 9.1904 91.5052 14.709C101.484 20.5552 114.441 24.5839 118.451 35.4317C122.456 46.2671 113.129 57.2263 111.445 68.6549C109.732 80.2849 115.949 93.9976 108.486 103.08Z" class="bpa-front-dev__panel-bg"/>
                                <g filter="url(#filter0_d_4344_13430)">
                                    <rect x="16.3105" y="27.8936" width="95.3718" height="22.2173" rx="11.1086" class="bpa-front-dev__form-bg"/>
                                </g>
                                <circle cx="27.1474" cy="39.0009" r="5.41885" class="bpa-front-dev__primary-bg"/>
                                <rect x="37.9863" y="39.542" width="41.1833" height="2.16754" rx="1.08377" fill="#F4F7FB"/>
                                <rect x="37.9863" y="36.0215" width="13.5471" height="2.16754" rx="1.08377" fill="#F4F7FB"/>
                                <rect x="53.4297" y="36.0215" width="25.7395" height="2.16754" rx="1.08377" fill="#F4F7FB"/>
                                <rect x="84.5859" y="34.9375" width="21.6754" height="8.12828" rx="4" fill="#F4F7FB"/>
                                <g filter="url(#filter1_d_4344_13430)">
                                    <rect x="16.3105" y="54.1748" width="95.3718" height="22.2173" rx="11.1086" class="bpa-front-dev__form-bg"/>
                                </g>
                                <circle cx="27.1474" cy="65.2831" r="5.41885" fill="#E8ECF5"/>
                                <rect x="37.9863" y="65.8252" width="41.1833" height="2.16754" rx="1.08377" fill="#E8ECF5"/>
                                <rect x="37.9863" y="62.3037" width="13.5471" height="2.16754" rx="1.08377" fill="#DDE1ED"/>
                                <rect x="53.4297" y="62.3037" width="25.7395" height="2.16754" rx="1.08377" fill="#E8ECF5"/>
                                <rect x="84.5859" y="61.2197" width="21.6754" height="8.12828" rx="4" fill="#F4F7FB"/>
                                <g filter="url(#filter2_d_4344_13430)">
                                    <rect x="16.3105" y="80.4541" width="95.3718" height="22.2173" rx="11.1086" class="bpa-front-dev__form-bg"/>
                                </g>
                                <circle cx="27.1474" cy="91.5644" r="5.41885" fill="#E8ECF5"/>
                                <rect x="37.9863" y="92.1064" width="41.1833" height="2.16754" rx="1.08377" fill="#E8ECF5"/>
                                <rect x="37.9863" y="88.582" width="13.5471" height="2.16754" rx="1.08377" fill="#DDE1ED"/>
                                <rect x="53.4297" y="88.582" width="25.7395" height="2.16754" rx="1.08377" fill="#E8ECF5"/>
                                <rect x="84.5859" y="87.499" width="21.6754" height="8.12828" rx="4" class="bpa-front-dev__primary-bg"/>
                                <path d="M10.6699 62.6393C11.3924 62.6393 11.6694 61.9455 11.7176 61.5986C11.7176 62.3164 12.4642 62.6058 12.8375 62.6537C11.9704 62.6537 11.7296 63.3953 11.7176 63.7662C11.7176 62.9623 11.0191 62.6752 10.6699 62.6393Z" stroke="#F4B125" stroke-opacity="0.6" stroke-linejoin="round"/>
                                <line x1="11.4707" y1="60.4463" x2="11.4707" y2="60.3625" stroke="#F4B125" stroke-opacity="0.6" stroke-linecap="round"/>
                                <line x1="11.4707" y1="65.8652" x2="11.4707" y2="65.1312" stroke="#F4B125" stroke-opacity="0.6" stroke-linecap="round"/>
                                <path d="M13.4863 62.709H14.7869" stroke="#F4B125" stroke-opacity="0.6" stroke-linecap="round"/>
                                <path d="M8.7207 62.709H9.53353" stroke="#F4B125" stroke-opacity="0.6" stroke-linecap="round"/>
                                <path d="M10.3483 40.076L10.35 40.0813H10.3556L10.3511 40.0846L10.3528 40.0898L10.3483 40.0866L10.3438 40.0898L10.3455 40.0846L10.3411 40.0813H10.3466L10.3483 40.076Z" class="bpa-front-dev__primary-bg"/>
                                <path d="M117.915 48.4764L117.916 48.4817H117.922L117.917 48.485L117.919 48.4902L117.915 48.487L117.91 48.4902L117.912 48.485L117.907 48.4817H117.913L117.915 48.4764Z" class="bpa-front-dev__primary-bg"/>
                                <path d="M84.5866 111.606L84.5883 111.612H84.5938L84.5894 111.615L84.5911 111.62L84.5866 111.617L84.5821 111.62L84.5838 111.615L84.5793 111.612H84.5849L84.5866 111.606Z" stroke="#F5AE41"/>
                                <circle cx="56.1379" cy="1.88181" r="0.854713" stroke="#EE2445" stroke-opacity="0.7"/>
                                <circle cx="111.681" cy="79.0998" r="0.854713" stroke="#EE2445" stroke-opacity="0.6"/>
                                <circle cx="2.76292" cy="79.0993" r="0.854713" stroke="#EE2445" stroke-opacity="0.6"/>
                                <circle cx="69.9579" cy="15.9723" r="0.541885" fill="#2166F1"/>
                                <line x1="43.9062" y1="16.5115" x2="43.9062" y2="20.0337" stroke="#01CB62" stroke-opacity="0.3"/>
                                <line x1="45.3027" y1="18.6365" x2="41.7805" y2="18.6365" stroke="#01CB62" stroke-opacity="0.3"/>
                                <line x1="21.3262" y1="105.778" x2="61.9479" y2="105.778" stroke="#DCE4F5" stroke-width="3" stroke-linecap="round"/>
                                <line x1="69.0176" y1="105.778" x2="87.9639" y2="105.778" stroke="#DCE4F5" stroke-width="3" stroke-linecap="round"/>
                                <line x1="95.8379" y1="105.778" x2="114.784" y2="105.778" stroke="#DCE4F5" stroke-width="3" stroke-linecap="round"/>
                                <path d="M92.9902 15.9169C93.8934 15.9169 94.2396 15.0496 94.2998 14.616C94.2998 15.5131 95.233 15.875 95.6997 15.9348C94.6159 15.9348 94.3148 16.8619 94.2998 17.3254C94.2998 16.3206 93.4268 15.9617 92.9902 15.9169Z" stroke="#F4B125" stroke-linejoin="round"/>
                                <line x1="94.1113" y1="13.3025" x2="94.1113" y2="12.9478" stroke="#F4B125" stroke-linecap="round"/>
                                <line x1="94.1113" y1="20.0769" x2="94.1113" y2="18.9094" stroke="#F4B125" stroke-linecap="round"/>
                                <path d="M96.5098 16.0056H98.1354" stroke="#F4B125" stroke-linecap="round"/>
                                <path d="M90.5488 16.0056H91.5649" stroke="#F4B125" stroke-linecap="round"/>
                                <defs>
                                    <filter id="filter0_d_4344_13430" x="8.31055" y="21.8936" width="111.372" height="38.2173" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                        <feOffset dy="2"/>
                                        <feGaussianBlur stdDeviation="4"/>
                                        <feComposite in2="hardAlpha" operator="out"/>
                                        <feColorMatrix type="matrix" values="0 0 0 0 0.129412 0 0 0 0 0.403922 0 0 0 0 0.945098 0 0 0 0.1 0"/>
                                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4344_13430"/>
                                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4344_13430" result="shape"/>
                                    </filter>
                                    <filter id="filter1_d_4344_13430" x="8.31055" y="48.1748" width="111.372" height="38.2173" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                        <feOffset dy="2"/>
                                        <feGaussianBlur stdDeviation="4"/>
                                        <feComposite in2="hardAlpha" operator="out"/>
                                        <feColorMatrix type="matrix" values="0 0 0 0 0.129412 0 0 0 0 0.403922 0 0 0 0 0.945098 0 0 0 0.1 0"/>
                                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4344_13430"/>
                                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4344_13430" result="shape"/>
                                    </filter>
                                    <filter id="filter2_d_4344_13430" x="8.31055" y="74.4541" width="111.372" height="38.2173" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                        <feOffset dy="2"/>
                                        <feGaussianBlur stdDeviation="4"/>
                                        <feComposite in2="hardAlpha" operator="out"/>
                                        <feColorMatrix type="matrix" values="0 0 0 0 0.129412 0 0 0 0 0.403922 0 0 0 0 0.945098 0 0 0 0.1 0"/>
                                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4344_13430"/>
                                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4344_13430" result="shape"/>
                                    </filter>
                                </defs>
                            </svg>
                            <div class="bpa-front-ntb__val bpa-empty-view-dec"><?php echo esc_html($no_staffmember_available); ?></div>
                        </div>
                    </div>
                </el-col>
                <!-- STAFF SELECT MOBILE DEVICE SCREEN CHANGE START -->
                <el-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24" v-else>
                    <?php do_action( 'bookingpress_multi_staff_selection_front_booking_step_mobile_view'); ?>
                    <div v-if="typeof appointment_step_form_data.is_allow_club_staff_selection == 'undefined' || (typeof appointment_step_form_data.is_allow_club_staff_selection != 'undefined' && appointment_step_form_data.is_allow_club_staff_selection == '0')" class="bpa-front-module-container bpa-front-module--staff">
                        <div class="bpa-front-module-heading" :aria-label="staffmember_heading_title"> {{staffmember_heading_title}}</div>
                        <div class="bpa-front-module--staff-item-row" role="list" ref="staffmemberItemsGroup">
                            <!-- ANY STAFF DIV START -->
                            <div class="bpa-front-sm--col --bpa-sm-any-staff-col  <?php echo ( $show_only_name ) ? 'bpa-front-sm--col-only-name' : ''; ?>" @click="bookingpress_select_any_staffmember()" :class="(appointment_step_form_data.bookingpress_selected_staff_member_details.is_any_staff_option_selected == '1') ? '__bpa-is-selected' : ''" v-if="is_any_staff_option_enable == 1">
                                <div class="bpa-front-sm-card bpa_focusable" tabindex="-1" role="listitem" ref="staffmember"  v-on:keydown.enter="bookingpress_select_any_staffmember()" @keydown="(typeof handle_staff_key_events != 'undefined') ? handle_staff_key_events($event):''">
                                    <div class="bpa-front-sm-card__left">
                                        <svg width="74" height="74" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.9995 73.999C57.4338 73.999 73.999 57.4338 73.999 36.9995C73.999 16.5652 57.4338 0 36.9995 0C16.5652 0 0 16.5652 0 36.9995C0 57.4338 16.5652 73.999 36.9995 73.999Z" /><path d="M36.9996 33.1535C41.6922 33.1535 45.4963 29.3494 45.4963 24.6568C45.4963 19.9643 41.6922 16.1602 36.9996 16.1602C32.307 16.1602 28.5029 19.9643 28.5029 24.6568C28.5029 29.3494 32.307 33.1535 36.9996 33.1535Z" fill="white"/><path d="M49.8919 56.3804C53.9733 56.3804 56.6507 52.1381 54.9279 48.4362C51.7861 41.682 44.9408 36.998 36.9996 36.998C29.0585 36.998 22.2147 41.6805 19.0713 48.4362C17.3502 52.1381 20.026 56.3804 24.1074 56.3804H49.8919Z" fill="white"/></svg>
                                    </div>
                                    <div class="bpa-front-sm-card__body">
                                        <div class="bpa-front-sm-card__body--name">
                                            <span>{{ any_staff_title }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ANY STAFF DIV END -->
                            <div class="bpa-front-sm--col <?php echo ( $show_only_name ) ? 'bpa-front-sm--col-only-name' : ''; ?>" v-if="staffmember_details.is_display_staff == true && staffmember_details.is_display_staff_with_flag == true" v-for="(staffmember_details, index) in bookingpress_staffmembers_details" :class="((appointment_step_form_data.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)) ? '__bpa-is-selected' : ''" @click="(appointment_step_form_data.is_club_staff == '1')?bookingpress_select_multi_staffmember(staffmember_details.bookingpress_staffmember_id, 0):bookingpress_select_staffmember(staffmember_details.bookingpress_staffmember_id, 0, $event)" v-on:keydown.enter="(appointment_step_form_data.is_club_staff == '1')?bookingpress_select_multi_staffmember(staffmember_details.bookingpress_staffmember_id, 0):bookingpress_select_staffmember(staffmember_details.bookingpress_staffmember_id, 0, $event)" v-show="( '' == bpa_search_staff_data || ( '' != bpa_search_staff_data && staffmember_details.show_with_staff_search ) )" :data-id="staffmember_details.bookingpress_staffmember_id">
                                <div class="bpa-front-sm-card" :class="((appointment_step_form_data.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)) ? '__bpa-is-active' : ''">
                                    <div class="bpa-front-sm-card-sm__body">
                                        <div class="bpa-front-sm-card-sm__avatar">
                                            <div class="bpa-front-sm-card-sm__avatar_img" v-if="staffmember_details.staffmember_avatar_url != ''">
                                                <img class="bpa-front-sm__avatar" :src="staffmember_details.staffmember_avatar_url" :alt="staffmember_details.bookingpress_staffmember_firstname + ' ' + staffmember_details.bookingpress_staffmember_lastname">
                                            </div>
                                            <div class="bpa-front-sm-card-sm__avatar_img bpa-front-sm__default-avatar" v-else>
                                                <svg viewBox="0 0 252 200" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="252" height="200" rx="12" fill="#CFD6E5" fill-opacity="0.5"/><g clip-path="url(#clip0_269_1959)"><path d="M125.43 103.536C130.724 103.536 135.308 101.637 139.054 97.8911C142.8 94.1454 144.699 89.5623 144.699 84.2675C144.699 78.9746 142.8 74.3908 139.054 70.6439C135.307 66.8988 130.724 65 125.43 65C120.135 65 115.552 66.8988 111.806 70.6445C108.061 74.3902 106.161 78.9739 106.161 84.2675C106.161 89.5623 108.061 94.146 111.807 97.8917C115.553 101.637 120.137 103.536 125.43 103.536Z"/><path d="M159.145 126.516C159.037 124.957 158.819 123.257 158.497 121.461C158.172 119.652 157.754 117.942 157.254 116.379C156.737 114.763 156.034 113.168 155.164 111.639C154.262 110.052 153.203 108.67 152.014 107.533C150.771 106.343 149.248 105.387 147.488 104.689C145.734 103.995 143.79 103.644 141.71 103.644C140.894 103.644 140.104 103.979 138.579 104.972C137.64 105.584 136.542 106.292 135.316 107.075C134.268 107.743 132.849 108.368 131.095 108.935C129.384 109.488 127.647 109.769 125.933 109.769C124.218 109.769 122.482 109.488 120.769 108.935C119.018 108.369 117.598 107.743 116.551 107.076C115.337 106.3 114.239 105.592 113.286 104.971C111.762 103.978 110.972 103.643 110.155 103.643C108.075 103.643 106.132 103.995 104.378 104.69C102.619 105.386 101.096 106.343 99.8519 107.533C98.6636 108.671 97.6034 110.052 96.7025 111.639C95.834 113.168 95.1309 114.762 94.6133 116.379C94.1134 117.942 93.6953 119.652 93.3706 121.461C93.049 123.254 92.8304 124.955 92.7224 126.518C92.6162 128.048 92.5625 129.637 92.5625 131.242C92.5625 135.418 93.89 138.799 96.5078 141.292C99.0933 143.752 102.514 145 106.674 145H145.195C149.355 145 152.775 143.753 155.361 141.292C157.979 138.8 159.307 135.419 159.307 131.241C159.306 129.629 159.252 128.039 159.145 126.516Z"/></g><defs><clipPath id="clip0_269_1959"><rect width="80" height="80" transform="translate(86 65)"/></clipPath></defs></svg>
                                            </div>
                                        </div>
                                        <div class="bpa-front-sm-card-sm__body-wrapper" :class="staffmember_details.is_expanded_view ? '--is-expanded' : ''" :data-staff-index="index" ref="staffInnerBody">
                                            <div class="bpa-front-sm-card__body--name">
                                                <span>{{staffmember_details.bookingpress_staffmember_firstname}} {{staffmember_details.bookingpress_staffmember_lastname}}</span>
                                            </div>
                                            <?php do_action('bookingpress_front_staffmember_details_outside', true); ?>
                                            <div class="bpa-front-sm-card__inner-body--bio" v-if="staffmember_details.show_staffmember_bio == 1"><span v-html="staffmember_details.staffmember_bio"></span></div>
                                            <div class="bpa-front-sm-card__inner-body-item" v-if="staffmember_details.staffmember_information_rule == '1' || staffmember_details.staffmember_information_rule == '2'">
                                                <span class="bpa-front-sm-card__inner-body-item-wrapper bpa-front-sm-card__inner-item-icon"><svg width="16" height="14" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.95 0.600342C12.0163 0.600342 12.9968 0.865512 13.7136 1.52808C14.4387 2.19851 14.7996 3.18793 14.7996 4.4353V8.9646C14.7996 10.212 14.4387 11.2014 13.7136 11.8718C12.9968 12.5344 12.0163 12.7996 10.95 12.7996H4.44995C3.38362 12.7996 2.40305 12.5344 1.68628 11.8718C0.961155 11.2014 0.600342 10.212 0.600342 8.9646V4.4353C0.600342 3.18793 0.961155 2.19851 1.68628 1.52808C2.40305 0.865512 3.38362 0.600342 4.44995 0.600342H10.95Z" stroke="#535D71" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.7 4.69995L8.42251 7.43375C7.99692 7.78869 7.403 7.78869 6.97741 7.43375L3.69995 4.69995" stroke="#535D71" stroke-width="1.2" stroke-linecap="round"/></svg> {{staffmember_details.bookingpress_staffmember_email}}</span>
                                            </div>
                                            <div class="bpa-front-sm-card__inner-body-item" v-if="'' != staffmember_details.bookingpress_staffmember_phone && (staffmember_details.staffmember_information_rule == '1' || staffmember_details.staffmember_information_rule == '3')">
                                                <span class="bpa-front-sm-card__inner-body-item-wrapper bpa-front-sm-card__inner-item-icon"><svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.04663 0.600342C4.59941 0.60046 5.09163 0.93485 5.29077 1.44409L5.3269 1.54761L5.33081 1.56421L5.33472 1.57983L5.99194 4.31909L5.99292 4.32104C6.06493 4.62645 5.97683 4.96031 5.74097 5.19019L5.73999 5.18921L4.95581 5.95581C5.1141 6.28455 5.29706 6.60442 5.50073 6.90796H5.49976C5.76305 7.29688 6.06606 7.66222 6.39917 7.9978C6.73564 8.3323 7.10313 8.63664 7.4939 8.90112C7.79835 9.10537 8.11735 9.2871 8.44409 9.44214L9.35034 8.51929C9.58662 8.27773 9.93523 8.18586 10.2615 8.28101H10.2625L12.8855 9.04468L12.907 9.05151L12.9275 9.05835C13.4498 9.25263 13.7994 9.75462 13.7996 10.3132V12.8845C13.7993 13.39 13.3907 13.7993 12.8845 13.7996H11.6208C8.67858 13.7995 5.90881 12.652 3.82788 10.571C1.74725 8.49021 0.600425 5.72119 0.600342 2.77905V1.51538C0.600556 1.00954 1.00941 0.600561 1.51538 0.600342H4.04663Z" stroke="#535D71" stroke-width="1.2"/></svg> {{staffmember_details.bookingpress_staffmember_phone}}</span>
                                            </div>
                                        </div>
                                        <?php do_action( 'bookingpress_front_staffmember_external_details_outside'); ?>
                                        <div class="bpa-front-sm-card-sm__body-expand-collapse-wrapper" v-if="staffmember_details.enable_toggle">
                                            <span @click="bpaToggleStaffData(index,false)" v-if="staffmember_details.is_expanded_view"><?php esc_html_e( 'Show less', 'bookingpress-appointment-booking') ?></span>
                                            <span @click="bpaToggleStaffData(index,true)" v-else><?php esc_html_e( 'Show more', 'bookingpress-appointment-booking') ?></span>
                                        </div>
                                    </div>
                                    <div class="bpa-front-sm-card-sm__footer">
                                        <el-button type="button" class="bpa-front-sm-card__inner-button" tabindex="-1" :class="(((appointment_step_form_data.bookingpress_selected_staff_member_details.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id))) ? ' bpa-sm-card__active ' : ''" :data-id="staffmember_details.bookingpress_staffmember_id">
                                            <span v-if="(((appointment_step_form_data.bookingpress_selected_staff_member_details.selected_staff_member_id == staffmember_details.bookingpress_staffmember_id && (appointment_step_form_data.is_club_staff == '0')) || bpa_is_select_multistaff_member(staffmember_details.bookingpress_staffmember_id)))">{{appointment_step_form_data.staff_member_selected_text}}</span>
                                            <span v-else>{{appointment_step_form_data.staff_member_select_text}}</span>
                                            <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.33333 0C3.73333 0 0 3.73333 0 8.33333C0 12.9333 3.73333 16.6667 8.33333 16.6667C12.9333 16.6667 16.6667 12.9333 16.6667 8.33333C16.6667 3.73333 12.9333 0 8.33333 0ZM6.075 11.9083L3.08333 8.91667C2.75833 8.59167 2.75833 8.06667 3.08333 7.74167C3.40833 7.41667 3.93333 7.41667 4.25833 7.74167L6.66667 10.1417L12.4 4.40833C12.725 4.08333 13.25 4.08333 13.575 4.40833C13.9 4.73333 13.9 5.25833 13.575 5.58333L7.25 11.9083C6.93333 12.2333 6.4 12.2333 6.075 11.9083Z"/></svg>
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-col>
                <!-- STAFF SELECT MOBILE DEVICE SCREEN CHANGE START -->
            </el-row>
        </div>
        <div class="bpa-front-dc--footer" :class="bookingpress_footer_dynamic_class">
            <el-row>
                <el-col>
                    <div class="bpa-front-tabs--foot">
                        <el-button class="bpa-front-btn bpa-front-btn__medium bpa-front-btn--borderless bpa_focusable" @click="bookingpress_step_navigation(bookingpress_sidebar_step_data['staffmembers'].previous_tab_name, bookingpress_sidebar_step_data['staffmembers'].next_tab_name, bookingpress_sidebar_step_data['staffmembers'].previous_tab_name)" v-if="bookingpress_sidebar_step_data.staffmembers.is_first_step == 0 || bookingpress_sidebar_step_data.basic_details.is_first_step == 1" aria-label="<?php echo esc_html( $bookingpress_goback_btn_text ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24"><rect fill="none" height="24" width="24"/><path d="M9.7,18.3L9.7,18.3c0.39-0.39,0.39-1.02,0-1.41L5.83,13H21c0.55,0,1-0.45,1-1v0c0-0.55-0.45-1-1-1H5.83l3.88-3.88 c0.39-0.39,0.39-1.02,0-1.41l0,0c-0.39-0.39-1.02-0.39-1.41,0L2.7,11.3c-0.39,0.39-0.39,1.02,0,1.41l5.59,5.59 C8.68,18.68,9.32,18.68,9.7,18.3z"/></svg>
                            <?php echo esc_html( $bookingpress_goback_btn_text ); ?>
                        </el-button>
                        <?php do_action('bookingpress_before_front_booking_next_button', $bookingpress_next_btn_text); ?>
                        <el-button v-if="typeof appointment_step_form_data.is_allow_club_staff_selection == 'undefined' || (typeof appointment_step_form_data.is_allow_club_staff_selection != 'undefined' && appointment_step_form_data.is_allow_club_staff_selection == '0')" class="bpa-front-btn bpa-front-btn__medium bpa-front-btn--primary bpa_focusable" @click="bookingpress_step_navigation(bookingpress_sidebar_step_data['staffmembers'].next_tab_name, bookingpress_sidebar_step_data['staffmembers'].next_tab_name, bookingpress_sidebar_step_data['staffmembers'].previous_tab_name)" :aria-label="`<?php echo esc_html( $bookingpress_next_btn_text ); ?> ${bookingpress_sidebar_step_data[bookingpress_sidebar_step_data[bookingpress_current_tab].next_tab_name].tab_name}`">	
                            <?php echo esc_html( $bookingpress_next_btn_text ); ?>&nbsp;<strong class="">{{ bookingpress_sidebar_step_data[bookingpress_sidebar_step_data[bookingpress_current_tab].next_tab_name].tab_name }}</strong>
                            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24"><rect fill="none" height="24" width="24"/><path d="M14.29,5.71L14.29,5.71c-0.39,0.39-0.39,1.02,0,1.41L18.17,11H3c-0.55,0-1,0.45-1,1v0c0,0.55,0.45,1,1,1h15.18l-3.88,3.88 c-0.39,0.39-0.39,1.02,0,1.41l0,0c0.39,0.39,1.02,0.39,1.41,0l5.59-5.59c0.39-0.39,0.39-1.02,0-1.41L15.7,5.71 C15.32,5.32,14.68,5.32,14.29,5.71z"/></svg>
                        </el-button>
                    </div>
                </el-col>
            </el-row>
        </div>
    </div>
</div>