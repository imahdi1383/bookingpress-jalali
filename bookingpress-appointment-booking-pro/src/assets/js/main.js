"use strict";

import { createApp, ref } from 'vue';
const BookingPressConfig = window.BookingPressConfig;
const rest_url = BookingPressConfig.rest_url;

function getModuleData(moduleId) {
    const el = document.getElementById(`wp-script-module-data-${moduleId}`);

    if (!el) {
        return {};
    }

    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('Failed to parse module data:', error);
        return {};
    }
}

wp.hooks.addFilter('bookingpress_modify_appointment_model_data', 'bookingpress-appointment-booking-pro', function (ModelConfigData, vm) {

    const AppointmentModelData = getModuleData('bookingpress-appointment-model');

    ModelConfigData.is_compitible_with_pro = 1;

    ModelConfigData.bpa_allow_custom_duration = true;
    ModelConfigData.is_timeslot_display = '1';
    ModelConfigData.appointment_formdata.appointment_custom_timing = false;

    ModelConfigData.appointment_formdata.default_appointment_timing = AppointmentModelData.default_appointment_timing;

    ModelConfigData.appointment_formdata.subtotal_temp = 0;
    ModelConfigData.appointment_formdata.subtotal_with_currency = vm.bookingpress_price_with_currency_symbol(0);

    ModelConfigData.appointment_formdata.mark_as_paid = false;
    ModelConfigData.appointment_formdata.complete_payment_url_selection = 'do_nothing';
    ModelConfigData.appointment_formdata.complete_payment_url_selected_method = [];

    /** Deposit */
    ModelConfigData.bookingpress_applied_deposit = 0;
    /** Deposit */

    /** Tax */
    ModelConfigData.tax = 0;
    ModelConfigData.tax_price_display_options = 'exclude_tax';
    /** Tax */

    /** Service Extras */
    ModelConfigData.appointment_formdata.selected_extra_services = AppointmentModelData.selected_extra_services;
    ModelConfigData.bookingpress_extras_popover_modal = AppointmentModelData.bookingpress_extras_popover_modal;
    ModelConfigData.is_extras_enable = AppointmentModelData.is_extras_enable || 0;
    ModelConfigData.bookingpress_loaded_extras = AppointmentModelData.bookingpress_loaded_extras || [];
    ModelConfigData.appointment_formdata.selected_extra_services_ids = [];
    /** Service Extras */

    /** Number of Person */
    ModelConfigData.appointment_formdata.selected_bring_members = 1;
    /** Number of Person */

    ModelConfigData.rprt_model = AppointmentModelData.rprt_model || false;
    ModelConfigData.rprt_license_key = AppointmentModelData.rprt_license_key || '';
    ModelConfigData.rprt_verify_btn_loading = AppointmentModelData.rprt_verify_btn_loading || false;

    return ModelConfigData;
}, 10, 2);

wp.hooks.addFilter('bookingpress_appointment_reschedule_computed_data', 'bookingpress-appointment-booking-pro', function (ExternalComputedMethods) {
    ExternalComputedMethods.filteredAppointmentTiming = function () {
        return this.reschedule_formdata.default_appointment_timing.filter(
            item => item.start_time_val < '24:00:00'
        );
    }

    ExternalComputedMethods.filteredAppointmentEndTiming = function () {
        return this.reschedule_formdata.default_appointment_timing.filter(
            item => item.end_time_val > this.reschedule_formdata.reschedule_time && true == item.is_visible
        );
    }

    return ExternalComputedMethods;
});

wp.hooks.addFilter('bookingpress_appointment_external_computed_methods', 'bookingpress-appointment-booking-pro', function (ExternalComputedMethods) {
    ExternalComputedMethods.filteredAppointmentTiming = function () {
        return this.appointment_formdata.default_appointment_timing.filter(
            item => item.start_time_val < '24:00:00'
        );
    }

    ExternalComputedMethods.filteredAppointmentEndTiming = function () {
        return this.appointment_formdata.default_appointment_timing.filter(
            item => item.end_time_val > this.appointment_formdata.appointment_booked_time && true == item.is_visible
        );
    }

    ExternalComputedMethods.filterSelectedServiceExtras = function () {
        let selected_service = this.appointment_formdata.appointment_selected_service;
        if ('' == selected_service) {
            return [];
        }
        return this.bookingpress_loaded_extras[selected_service].filter(
            item => item.bookingpress_is_selected === true
        );
    }

    ExternalComputedMethods.filterLoadedStaffMembers = function () {
        let serviceId = this.appointment_formdata.appointment_selected_service;
        //let staffMembers = this.bookingpress_loaded_staff[serviceId] || [];

        if ('' == serviceId) {
            return [];
        }

        let bpa_chk_staff_role = this.bpa_chk_staff_role;
        let bpa_get_current_staff_id = this.bpa_get_current_staff_id;
        return this.bookingpress_loaded_staff[serviceId].filter(
            staff_member_details => (((bpa_chk_staff_role != 1 && typeof staff_member_details.profile_details != 'undefined') || (bpa_chk_staff_role == 1 && typeof staff_member_details.profile_details != 'undefined' && staff_member_details.profile_details.bookingpress_wpuser_id == bpa_get_current_staff_id)))
        );
    }

    return ExternalComputedMethods;
}, 10, 1);

wp.hooks.addFilter('bookingpress_appointment_external_watch_methods', 'bookingpress-appointment-booking-pro', function (ExternalWatchMethods) {

    const AppointmentModelData = getModuleData('bookingpress-appointment-model');

    ExternalWatchMethods['appointment_formdata.appointment_selected_service'] = {
        handle() {

            const serviceId = this.appointment_formdata.appointment_selected_service;
            const extras = this.bookingpress_loaded_extras?.[serviceId] || [];

            extras.forEach((extra) => {
                extra.bookingpress_selected_qty = Number(extra.bookingpress_selected_qty || 1);
            });

        },
        immediate: true
    };

    return ExternalWatchMethods;
}, 10, 1)

wp.hooks.addFilter('bookingpress_appointment_external_methods', 'bookingpress-appointment-booking-pro', function (ExternalMethods) {

    ExternalMethods.bookingpress_admin_get_final_step_amount = function () {
        const vm = this;

        if ("undefined" != typeof vm.appointment_formdata.is_clubbed_service && (true === vm.appointment_formdata.is_clubbed_service || 'true' == vm.appointment_formdata.is_clubbed_service)) {
            return;
        }

        let amountConfig = {};

        var total_amount = vm.appointment_formdata.service_price_without_currency;

        if (typeof vm.appointment_services_list != "undefined") {
            let selected_service = vm.appointment_formdata.appointment_selected_service;
            let services_lists = vm.appointment_services_list;
            let max_capacity = 0;
            services_lists.forEach(function (categories) {
                let category_service_list = categories.category_services;
                for (let index in category_service_list) {
                    let services = category_service_list[index];
                    let service_id = services.service_id;
                    if (service_id == selected_service) {
                        total_amount = parseFloat(services.service_price_without_currency);
                        vm.appointment_formdata.service_price_without_currency = total_amount;
                    }
                }
            });
        }

        if (vm.is_staff_enable) {
            if (typeof vm.appointment_formdata.selected_staffmember != "undefined") {
                var selected_staffmember = vm.appointment_formdata.selected_staffmember;
                if (vm.appointment_formdata.appointment_selected_service) {
                    var staff_member_details = vm.bookingpress_loaded_staff[vm.appointment_formdata.appointment_selected_service];
                    Object.entries(staff_member_details).forEach(entry => {
                        const [key, value] = entry;
                        if (staff_member_details[key].bookingpress_staffmember_id == vm.appointment_formdata.selected_staffmember) {
                            total_amount = parseFloat(staff_member_details[key].bookingpress_service_price);
                            vm.appointment_formdata.service_price_without_currency = total_amount;
                        }
                    });
                }
            }
            amountConfig.total = total_amount;
            //<? php do_action('bookingpress_admin_calculate_total_after_staff_price'); ?>	
            amountConfig = wp.hooks.applyFilters('bookingpress_admin_calculate_total_after_staff_price', amountConfig, vm);
        }

        if (vm.is_custom_service_duration) {
            if (typeof vm.appointment_formdata.enable_custom_service_duration !== 'undefined' && vm.appointment_formdata.enable_custom_service_duration == true) {
                if (typeof vm.appointment_formdata.custom_service_duration_value !== 'undefined' && vm.appointment_formdata.custom_service_duration_value != '') {
                    if (typeof vm.bookingpress_custom_service_durations_slot !== 'undefined') {
                        vm.bookingpress_custom_service_durations_slot.forEach(function (item, index, arr) {
                            if (item.value == vm.appointment_formdata.custom_service_duration_value) {
                                if (item.service_duration_unit == 'd') {
                                    total_amount = item.real_price;
                                    vm.appointment_formdata.service_price_without_currency = total_amount;
                                } else {
                                    /*total_amount = item.service_price_without_currency;*/
                                    total_amount = item.real_price;
                                    vm.appointment_formdata.service_price_without_currency = total_amount;
                                }
                            }
                        });
                    }
                }
            }
        }

        //wp.hooks.doAction('bookingpress_admin_calculate_total_after_custom_duration_price', vm);

        total_amount = wp.hooks.applyFilters('bookingpress_admin_calculate_total_after_custom_duration_price', total_amount, vm);

        var subtotal_price = total_amount;

        var selected_bring_members = parseInt(vm.appointment_formdata.selected_bring_members);

        //total_amount = total_amount * selected_bring_members;
        if(typeof vm.appointment_formdata.enable_service_slot_capacity != "undefined" && vm.appointment_formdata.enable_service_slot_capacity ==1){
            total_amount = total_amount;
        } else {
            total_amount = total_amount * selected_bring_members;
        }

        /* Added Service Extras Calculations */
        if (vm.is_extras_enable) {
            var bookingpress_extras_price_total = 0;
            if (typeof vm.appointment_formdata.appointment_selected_service != "undefined") {
                if (typeof vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service] != "undefined") {
                    if (vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service] != "") {
                        let appointment_extra_details = vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service];
                        Object.entries(appointment_extra_details).forEach(entry => {
                            const [key, value] = entry;
                            if (value.bookingpress_is_selected) {
                                bookingpress_extras_price_total = bookingpress_extras_price_total + (parseFloat(value.bookingpress_extra_service_price) * parseInt(value.bookingpress_selected_qty));
                            }
                        });
                    }
                }
            }
            vm.appointment_formdata.extras_total = bookingpress_extras_price_total;
            vm.appointment_formdata.extras_total_with_currency = vm.bookingpress_price_with_currency_symbol(bookingpress_extras_price_total);
            total_amount = total_amount + bookingpress_extras_price_total;
        }

        amountConfig.total = total_amount;
        amountConfig.subtotal = subtotal_price;

        amountConfig = wp.hooks.applyFilters('bookingpress_admin_calculate_subtotal_price', amountConfig, vm);
        total_amount = amountConfig.total;
        subtotal_price = amountConfig.subtotal;

        /* New Deposit Calculation Start */
        var bookingpress_deposit_amt = 0;
        var bookingpress_deposit_due_amt = 0;
        var has_package_applied = false;
        var total_amount_without_tax_amount = 0;
        if (typeof vm.appointment_formdata.bookingpress_package_applied_data != "undefined" && vm.appointment_formdata.bookingpress_package_applied_data != "") {
            has_package_applied = true;
        }
        if (vm.deposit_payment_module && !has_package_applied) {
            if (vm.appointment_formdata.bookingpress_applied_deposit == '1' && vm.appointment_formdata.bookingpress_deposit_payment_method != 'allow_customer_to_pay_full_amount') {
                var deposit_type = vm.appointment_formdata.deposit_type;
                var deposit_value = vm.appointment_formdata.deposit_amount;
                if (deposit_type == "percentage") {
                    bookingpress_deposit_amt = total_amount * (parseFloat(deposit_value) / 100);
                    bookingpress_deposit_amt = bookingpress_deposit_amt;
                } else {
                    bookingpress_deposit_amt = deposit_value;
                }
            }
        }
        /* New Deposit Calculation Over */


        /* New tax calculation Start Here */
        if (vm.appointment_formdata.applied_coupon_code == '' || vm.coupon_applied_status == 'error') {
            var tax_amount = 0;
            if (vm.is_tax_enable) {
                if (vm.appointment_formdata.tax_percentage != '') {
                    var tax_percentage = parseFloat(vm.appointment_formdata.tax_percentage);
                    if (vm.appointment_formdata.tax_price_display_options == "include_taxes") {
                        //tax_amount = (total_amount * tax_percentage) / (100+tax_percentage);
                        tax_amount = parseFloat(((total_amount * tax_percentage) / (100 + tax_percentage)).toFixed(vm.bookingpress_decimal_points));
                    } else {
                        tax_amount = parseFloat((total_amount * (tax_percentage / 100)).toFixed(vm.bookingpress_decimal_points));
                        total_amount = parseFloat((total_amount + tax_amount).toFixed(vm.bookingpress_decimal_points));
                    }
                }
            }
            vm.appointment_formdata.tax = tax_amount;
            vm.appointment_formdata.tax_with_currency = vm.bookingpress_price_with_currency_symbol(tax_amount);
        }
        /* New tax calculation Over Here */

        vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(total_amount);
        vm.appointment_formdata.total_amount = total_amount;
        if (vm.is_coupon_enable == 1) {
            if (vm.bpa_coupon_apply_disabled == 1) {
                if (typeof vm.appointment_formdata.coupon_discounted_amount != "undefined") {

                    total_amount_without_tax_amount = total_amount;

                    if (vm.appointment_formdata.tax_percentage != "" && vm.is_tax_enable) {
                        var tax_percentage = parseFloat(vm.appointment_formdata.tax_percentage);
                        if (typeof vm.appointment_formdata.tax_price_display_options != "undefined" && vm.appointment_formdata.tax_price_display_options == "include_taxes") {
                            tax_amount = (total_amount * tax_percentage) / (100 + tax_percentage);
                            total_amount = total_amount - tax_amount;
                        }
                    }

                    total_amount = total_amount - vm.appointment_formdata.coupon_discounted_amount;
                    vm.appointment_formdata.total_amount = total_amount;
                    vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(total_amount);

                    let total_amount_after_tax = total_amount;
                    if (typeof vm.appointment_formdata.tax != "undefined" && vm.is_tax_enable) {

                        total_amount_after_tax = vm.appointment_formdata.total_amount + vm.appointment_formdata.tax;
                        vm.appointment_formdata.total_amount = total_amount_after_tax;
                        vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(total_amount_after_tax);
                    } else {

                        total_amount_after_tax = vm.appointment_formdata.total_amount
                    }


                    /** desposit related changes start */

                    if ("undefined" != vm.appointment_formdata.bookingpress_deposit_amt_without_currency && 1 == vm.deposit_payment_module) {

                        if (vm.appointment_formdata.bookingpress_deposit_payment_method == "deposit_or_full_price" || vm.appointment_formdata.bookingpress_deposit_payment_method == "allow_customer_to_pay_full_amount") {

                            if (vm.appointment_formdata.deposit_type == "fixed") {

                                if (vm.appointment_formdata.deposit_amount < total_amount_after_tax) {
                                    vm.appointment_formdata.bookingpress_remove_deposit = 0;

                                } else {

                                    vm.appointment_formdata.bookingpress_remove_deposit = 1;
                                }
                            }

                            if (vm.appointment_formdata.deposit_type == "percentage") {

                                let bookingpress_deposit_amt = total_amount_without_tax_amount * (parseFloat(vm.appointment_formdata.deposit_amount) / 100);

                                if (bookingpress_deposit_amt < total_amount_after_tax) {
                                    vm.appointment_formdata.bookingpress_remove_deposit = 0;
                                } else {
                                    vm.appointment_formdata.bookingpress_remove_deposit = 1;
                                }

                            }
                        }
                    }

                    /** desposit related changes end */
                }
            } else {
                vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(total_amount);
                vm.appointment_formdata.total_amount = total_amount;
                vm.appointment_formdata.bookingpress_remove_deposit = 0;
            }
        }
        vm.appointment_formdata.subtotal_with_currency = vm.bookingpress_price_with_currency_symbol(subtotal_price);
        vm.appointment_formdata.subtotal = subtotal_price;

        if (typeof vm.appointment_formdata.appointment_update_id != "undefined") {
            if (vm.appointment_formdata.appointment_update_id != 0) {
                if (total_amount != vm.appointment_formdata.total_amount) {
                    total_amount = vm.appointment_formdata.total_amount;
                }
            }
        }

        if (typeof vm.appointment_formdata.tip_amount !== 'undefined') {
            if (vm.appointment_formdata.tip_amount) {
                var tip_amount = parseFloat(vm.appointment_formdata.tip_amount);
                total_amount = total_amount + tip_amount;
                vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(total_amount);
                vm.appointment_formdata.total_amount = total_amount;
            }
        }

        wp.hooks.doAction('bookingpress_admin_modified_final_total_amount_data', vm, total_amount);

        if (vm.deposit_payment_module && vm.appointment_formdata.bookingpress_remove_deposit != 1) {

            vm.appointment_formdata.bookingpress_deposit_amt_without_currency = 0;
            vm.appointment_formdata.bookingpress_deposit_amt_with_currency = 0;
            vm.appointment_formdata.bookingpress_deposit_due_amt_without_currency = 0;

            if (vm.appointment_formdata.bookingpress_applied_deposit == '1' && bookingpress_deposit_amt != 0) {

                bookingpress_deposit_due_amt = parseFloat(vm.appointment_formdata.total_amount) - bookingpress_deposit_amt;
                vm.appointment_formdata.bookingpress_deposit_amt_without_currency = bookingpress_deposit_amt;
                vm.appointment_formdata.bookingpress_deposit_amt_with_currency = vm.bookingpress_price_with_currency_symbol(bookingpress_deposit_amt);
                vm.appointment_formdata.bookingpress_deposit_due_amt_without_currency = bookingpress_deposit_due_amt;
                vm.appointment_formdata.bookingpress_deposit_due_amt_with_currency = vm.bookingpress_price_with_currency_symbol(bookingpress_deposit_due_amt);

            }
        }

        /** remove deposit if the set as mark_as_paid from backend appointment */
        if (vm.deposit_payment_module && vm.appointment_formdata.bookingpress_remove_deposit != 1) {

            if (vm.appointment_formdata.complete_payment_url_selection != "" && vm.appointment_formdata.complete_payment_url_selection == 'mark_as_paid') {
                vm.appointment_formdata.bookingpress_remove_deposit = 1;
            }

        }
    }

    ExternalMethods.bookingpressDisabledDate = function(time){
        const vm = this;
        
        if( true == vm.appointment_formdata.appointment_custom_timing ){
            return false;
        }
        const dd = String(time.getDate()).padStart(2, '0');
        const mm = String(time.getMonth() + 1).padStart(2, '0');
        const yyyy = time.getFullYear();
        const selectedDate = `${yyyy}-${mm}-${dd}`;

        let normalizedDisabledDates = [];

        if (Array.isArray(vm.disabledDates)) {
            normalizedDisabledDates = vm.disabledDates;
        } else if (vm.disabledDates && typeof vm.disabledDates === 'object') {
            normalizedDisabledDates = Object.values(vm.disabledDates);
        }

        const disableDate = normalizedDisabledDates.includes(selectedDate);

        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);

        const disablePastDate = time.getTime() < yesterday.getTime();

        return disableDate || disablePastDate;
    }

    ExternalMethods.purchase_bookingpress = function(){
        window.open('https://www.bookingpressplugin.com/pricing/', '_blank')
    }

    ExternalMethods.bookingpress_verify_license_key_func = function(rprt_license_form){
        const vm = this;
        let license_key = vm.rprt_license_key;

        if( '' == license_key ){
            vm.$refs[rprt_license_form].validateField('rprt_license_key');
            return false;
        }

        vm.rprt_verify_btn_loading = true;

        fetch( rest_url + '/verify-license-key', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                license_key: license_key
            }),
        })
        .then( response => response.json() )
        .then( response => {
            vm.rprt_verify_btn_loading = false;
			if( 'undefined' != typeof response.data.variant && response.data.variant == 'success' ){
                vm.$notify({
                    title: response.data.title,
                    message: response.data.msg,
                    type: 'success',
                    customClass: 'success_notification',
                    duration:BookingPressConfig.notification_timeout,
                });
            }else{
                vm.$notify({
                    title: response.data.title,
                    message: response.data.msg ?? 'Something went wrong..',
                    type: 'error',
                    customClass: 'error_notification',
                    duration:BookingPressConfig.notification_timeout,
                });
            }
        })
        .catch( error => {
            vm.rprt_verify_btn_loading = false;
        });
    }

    ExternalMethods.handleCustomTimingChange = function (event) {
        const vm = this;

        if (true == event) {
            ExternalMethods.bookingpressDisabledDate = (time) => {
                return false;
            };
        }

        if (vm.appointment_formdata.appointment_custom_timing == false) {
            vm.appointment_formdata.appointment_booked_time = '';
            vm.appointment_formdata.appointment_booked_end_time = '';
        }

        wp.hooks.doAction('bookingpress_after_select_custom_timing_backend', vm);

        if (vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit == 'd') {
            vm.filter_pickerOptions.disabledDate = function (Time) {
                return false;
            };
        }
        if (vm.is_timeslot_display != '0' && vm.appointment_formdata.appointment_custom_timing == true) {
            vm.appointment_formdata.appointment_booked_time = '';
            vm.appointment_formdata.appointment_booked_end_time = '';
        }
        if (vm.is_timeslot_display == '0' && vm.appointment_formdata.appointment_custom_timing == true) {
            vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.appointment_booked_date;
        } else {
            vm.appointment_formdata.appointment_booked_end_date = '';
        }


    }

    ExternalMethods.change_custom_start_time = function (worktime) {
        const vm = this;
        if (vm.appointment_formdata.appointment_custom_timing == true) {
            vm.appointment_formdata.appointment_booked_end_time = '';
        }

        vm.appointment_formdata.default_appointment_timing.forEach((element, index) => {
            vm.appointment_formdata.default_appointment_timing[index].is_visible = false;
        });

        if (typeof vm.appointment_formdata.appointment_selected_service != "undefined" && vm.appointment_formdata.appointment_selected_service != "") {
            if (typeof vm.appointment_formdata.selected_service_duration != "undefined" && vm.appointment_formdata.selected_service_duration != "") {
                let selected_service_duration = vm.appointment_formdata.selected_service_duration;
                let selected_service_duration_unit = vm.appointment_formdata.selected_service_duration_unit;
                let serviceDurationConverted = 0;

                if (selected_service_duration_unit === "h") {
                    serviceDurationConverted = selected_service_duration * 3600; // Convert hours to seconds
                } else {
                    serviceDurationConverted = selected_service_duration * 60; // Convert minutes to seconds
                }

                if (worktime && typeof worktime === "string" && worktime.includes(":") && serviceDurationConverted != 0) {
                    let selectedStartTimestamp = vm.default_timeConvertToTimestamp(worktime);

                    let expectedEndTimestamp = selectedStartTimestamp + serviceDurationConverted;

                    let matchingEndTime = vm.appointment_formdata.default_appointment_timing.find(time =>
                        vm.default_timeConvertToTimestamp(time.end_time_val) === expectedEndTimestamp
                    );

                    if (typeof matchingEndTime != "undefined" && matchingEndTime != "" && typeof matchingEndTime.end_time_val != "undefined") {
                        vm.appointment_formdata.appointment_booked_end_time = matchingEndTime.end_time_val;
                        vm.change_custom_end_time(matchingEndTime.end_time_val);
                    }
                }
            }
        }

        vm.appointment_formdata.default_appointment_timing.forEach((element, index) => {
            if (element.start_time_val == worktime) {
                for (let i = 0; i <= 287; i++) {
                    vm.appointment_formdata.default_appointment_timing[index + i].is_visible = true;
                }
            }
        });
    }
    ExternalMethods.change_custom_end_time = function (worktime) {
        const vm = this;

        let start_time = vm.appointment_formdata.appointment_booked_time;
        let end_time = worktime;

        vm.appointment_formdata.is_next_day = false;
        vm.appointment_formdata.is_both_next_day = false;
        vm.appointment_formdata.appointment_temp_booked_end_time;
        vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.appointment_booked_date;

        if (start_time >= '24:00:00') {
            vm.appointment_formdata.is_next_day = true;
            vm.appointment_formdata.is_both_next_day = true;
        }

        if (end_time >= '24:00:00') {
            vm.appointment_formdata.is_next_day = true;
        }

        if (true == vm.appointment_formdata.is_next_day) {
            let booked_date = new Date(vm.appointment_formdata.appointment_booked_date);
            booked_date.setDate(booked_date.getDate() + 1);
            vm.appointment_formdata.appointment_booked_end_date = booked_date.toISOString().split("T")[0];
        }
    }

    ExternalMethods.bookingpress_appointment_change_service = function () {
        const vm = this;

        vm.appointment_time_slot = [];
        vm.appointment_formdata.appointment_booked_time = '';
        vm.appointment_formdata.appointment_booked_end_time = '';

        let selected_service = vm.appointment_formdata.appointment_selected_service;
        if(selected_service == ""){
            return;
        }
        let services_lists = vm.appointment_services_list;
        let selected_service_duration_unit = '';
        let selected_service_duration = '';

        let max_capacity = 0;
        let min_capacity = 0;
        let service_quantity_steps =1;
        let enable_service_slot_capacity = 0;
        let slot_capacity = 1;

        for (let categories of services_lists) {
            let category_service_list = categories.category_services;
            for (let services of category_service_list) {
                let service_id = services.service_id;
                if (service_id == selected_service) {

                    service_quantity_steps = ( "undefined" != typeof services.service_quantity_steps ) ? services.service_quantity_steps : 1;

                    if (services.enable_service_slot_capacity !== undefined && (services.enable_service_slot_capacity == 1 || services.enable_service_slot_capacity === true)) {
                        slot_capacity = services.slot_capacity || 1;
                    } else {
                        slot_capacity = 1;
                    }

                    enable_service_slot_capacity = ( "undefined" != typeof services.enable_service_slot_capacity ) ? services.enable_service_slot_capacity : 0;
                    max_capacity = ("undefined" != typeof services.service_max_capacity) ? services.service_max_capacity : 1;
                    min_capacity = ("undefined" != typeof services.service_min_capacity) ? services.service_min_capacity : 1;
                    selected_service_duration_unit = ("undefined" != typeof services.service_duration_unit) ? services.service_duration_unit : '';
                    selected_service_duration = ("undefined" != typeof services.service_duration) ? services.service_duration : '';
                    if (vm.is_compitible_with_pro == 0) {
                        max_capacity--;
                    }

                    break;
                }
            }
        }

        wp.hooks.doAction('bookingpress_before_change_backend_service', vm);

        vm.appointment_formdata.selected_bring_members = 0;
        if (vm.is_compitible_with_pro == 1) {
            vm.appointment_formdata.selected_bring_members = 1;
            if (min_capacity > 1) {
                vm.appointment_formdata.selected_bring_members = min_capacity;
            }
        }

        vm.appointment_formdata.enable_service_slot_capacity = enable_service_slot_capacity;                                
        vm.appointment_formdata.slot_capacity = parseInt(slot_capacity);  
        vm.appointment_formdata.bookingpress_bring_anyone_step = parseInt(service_quantity_steps);  
        vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(max_capacity);
        vm.appointment_formdata.bookingpress_bring_anyone_min_capacity = parseInt(min_capacity);

        vm.appointment_formdata.selected_service_duration_unit = selected_service_duration_unit;
        vm.appointment_formdata.selected_service_duration = selected_service_duration;


        //let selected_date = vm.appointment_formdata.appointment_booked_date;
        vm.bookingpress_get_disable_dates();

        //wp.hooks.doAction('bookingpress_appointment_change_service_action', vm);
        wp.hooks.doAction('bookingpress_change_backend_service', vm);
    }

    ExternalMethods.handleMarkAsPaid = function (event) {
        const vm = this;
        if (event == "mark_as_paid") {
            vm.appointment_formdata.bookingpress_remove_deposit = 1;
        } else {
            vm.appointment_formdata.bookingpress_remove_deposit = 0;
        }
        wp.hooks.doAction('bookingpress_cal_mark_as_paid_action_external', vm);
    }

    /** Staff Member methods */
    ExternalMethods.bookingpress_change_staff = function () {
        const vm = this
        vm.bookingpress_set_bring_anyone_capacity();
        //vm.bookingpress_appointment_get_disable_dates();
        let selected_date = vm.appointment_formdata.appointment_booked_date;
        vm.bookingpress_get_disable_dates();
        vm.select_appointment_booking_date(selected_date);
        if (typeof vm.appointment_formdata.selected_staffmember != "undefined") {
            var selected_staffmember = vm.appointment_formdata.selected_staffmember;
            if (vm.appointment_formdata.appointment_selected_service) {
                //<?php do_action( 'bookingpress_after_select_staff_backend') ?>
                wp.hooks.doAction('bookingpress_after_select_staff_backend', vm);
                var staff_member_details = vm.bookingpress_loaded_staff[vm.appointment_formdata.appointment_selected_service];
                Object.entries(staff_member_details).forEach(entry => {
                    const [key, value] = entry;
                    if (staff_member_details[key].bookingpress_staffmember_id == vm.appointment_formdata.selected_staffmember) {

                        /* if (vm.is_bring_anyone_with_you_enable == 1) {

                            vm.appointment_formdata.selected_bring_members = staff_member_details[key].bookingpress_service_min_capacity;
                        } */
                        var total_amount = parseFloat(staff_member_details[key].bookingpress_service_price);
                        //vm.appointment_formdata.tax = staff_member_details[key].tax_amount_without_currency;
                        //vm.appointment_formdata.tax_with_currency = staff_member_details[key].tax_amount;									
                        vm.appointment_formdata.service_price_without_currency = total_amount;

                    }
                });
            }
        }

        if (typeof vm.appointment_formdata.custom_service_duration_value !== 'undefined') {
            vm.appointment_formdata.custom_service_duration_value = '';
        }
        vm.bookingpress_admin_get_final_step_amount();

        if (vm.appointment_formdata.applied_coupon_code != '') {
            vm.bookingpress_apply_coupon_code();
        }
    }

    ExternalMethods.bookingpress_set_bring_anyone_capacity = function () {
        const vm = this;
        if (1 == vm.is_bring_anyone_with_you_enable) {
            vm.appointment_formdata.selected_bring_members = vm.bookingpress_snap_bring_anyone_value(vm.appointment_formdata.bookingpress_bring_anyone_min_capacity);
            let selected_staffmember = vm.appointment_formdata.selected_staffmember;
            if ("" != selected_staffmember) {
                let selected_service = vm.appointment_formdata.appointment_selected_service;
                let selected_service_staffmember = vm.bookingpress_loaded_staff[selected_service];
                let selected_staff_capacity = 1;
                let selected_staff_min_capacity = 1;
                selected_service_staffmember.forEach(function (elm) {
                    if (selected_staffmember == elm.bookingpress_staffmember_id) {
                        selected_staff_capacity = elm.bookingpress_service_capacity;
                        selected_staff_min_capacity = elm.bookingpress_service_min_capacity;
                        return false;
                    }
                });
                vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(selected_staff_capacity);
                vm.appointment_formdata.bookingpress_bring_anyone_min_capacity = parseInt(selected_staff_min_capacity);

                if( vm.is_bring_anyone_with_you_enable == 1 ){										
                    for( let index in vm.appointment_services_list ){
                        let currentValue = vm.appointment_services_list[ index ];
                        if(currentValue.category_services.length > 0){
                            for( let index2 in currentValue.category_services ){
                                let currentValue2 = currentValue.category_services[ index2 ];
                                if( currentValue2.service_id == vm.appointment_formdata.appointment_selected_service){
                                    let service_quantity_steps = ( "undefined" != typeof currentValue2.service_quantity_steps ) ? currentValue2.service_quantity_steps : 1;                                            
                                    let slot_capacity = 1;
                                    if (currentValue2.enable_service_slot_capacity !== undefined && (currentValue2.enable_service_slot_capacity == 1 || currentValue2.enable_service_slot_capacity === true)) {
                                        slot_capacity = currentValue2.slot_capacity || 1;
                                    }
                                    let enable_service_slot_capacity = ( "undefined" != typeof currentValue2.enable_service_slot_capacity ) ? currentValue2.enable_service_slot_capacity : 0;
                                    vm.appointment_formdata.slot_capacity = slot_capacity;
                                    vm.appointment_formdata.enable_service_slot_capacity = enable_service_slot_capacity;
                                    vm.appointment_formdata.bookingpress_bring_anyone_step = service_quantity_steps;
                                }
                            }
                        }

                        vm.appointment_formdata.selected_bring_members = vm.bookingpress_snap_bring_anyone_value(vm.appointment_formdata.bookingpress_bring_anyone_min_capacity);	
                    }
                    
                    if( vm.appointment_formdata.enable_service_slot_capacity == 1 && parseInt(vm.appointment_formdata.slot_capacity) > 0 ){
                        vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(vm.appointment_formdata.slot_capacity);
                    }							
                }
            }
        }
    }
    /** Staff Member methods */

    /** Service Extra Methods */

    ExternalMethods.getExtraQtyOptions = function (maxQty) {
        maxQty = parseInt(maxQty, 10) || 0;

        return Array.from({ length: maxQty }, (_, i) => ({
            label: String(i + 1),
            value: i + 1,
        }));
    }

    ExternalMethods.bookingpress_toggle_extra_address = function (extra_service_id, new_status) {
        const vm = this;
        vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function (currentValue, index, arr) {
            if (currentValue.bookingpress_extra_services_id == extra_service_id) {
                vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service][index]['bookingpress_is_display_description'] = parseInt(new_status);
            }
        });
    }

    ExternalMethods.bookingpress_add_extras = function () {
        const vm = this;
        if (vm.appointment_formdata.selected_extra_services_ids == "") {
            vm.appointment_formdata.selected_extra_services_ids = [];
        }
        vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function (currentValue, index, arr) {
            if (currentValue.bookingpress_is_selected == true) {
                if (!vm.appointment_formdata.selected_extra_services_ids.includes(currentValue.bookingpress_extra_services_id)) {
                    vm.appointment_formdata.selected_extra_services_ids.push(currentValue.bookingpress_extra_services_id);
                }
            } else {
                vm.appointment_formdata.selected_extra_services_ids.forEach(function (currentValue2, index2, arr2) {
                    if (currentValue.bookingpress_extra_services_id == currentValue2) {
                        vm.appointment_formdata.selected_extra_services_ids.splice(index2, 1);
                    }
                });
            }
        });

        let selectedDate = vm.appointment_formdata.appointment_booked_date;
        vm.select_appointment_booking_date(selectedDate);

        vm.bookingpress_admin_get_final_step_amount();

        if (vm.appointment_formdata.applied_coupon_code != '') {
            vm.bookingpress_apply_coupon_code();
        }

        wp.hooks.doAction('bookingpress_backend_after_add_service_extras');

        vm.bookingpress_close_extras_modal();

    }

    ExternalMethods.bookingpress_close_extras_modal = function () {
        this.bookingpress_admin_get_final_step_amount();
        this.bookingpress_extras_popover_modal = false;
    };

    ExternalMethods.bookingpress_pro_service_extra_quantity_change = function () {
        const vm = this;
        vm.bookingpress_extras_popover_modal = true;
    }

    ExternalMethods.bookingpress_remove_extras = function (remove_extra_service_id) {
        const vm = this;
        vm.appointment_formdata.selected_extra_services_ids.forEach(function (currentValue, index, arr) {
            if (remove_extra_service_id == currentValue) {
                vm.appointment_formdata.selected_extra_services_ids.splice(index, 1);
                vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function (currentValue2, index2, arr2) {
                    if (currentValue2.bookingpress_extra_services_id == remove_extra_service_id) {
                        vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service][index2].bookingpress_is_selected = false;
                    }
                });
            }
        });
        let selectedDate = vm.appointment_formdata.appointment_booked_date;
        vm.select_appointment_booking_date(selectedDate);

        vm.bookingpress_admin_get_final_step_amount();
        if (vm.appointment_formdata.applied_coupon_code != '') {
            vm.bookingpress_apply_coupon_code();
        }

        wp.hooks.doAction('bookingress_backend_after_remove_service_extra');
    }

    /** Service Extras Methods */

    /** Coupon related methods */
    ExternalMethods.bookingpress_apply_coupon_code = function () {
        const vm = this;
        if (typeof vm.appointment_formdata.tip_amount !== 'undefined') {
            var total_amount = vm.appointment_formdata.total_amount - vm.appointment_formdata.tip_amount;
            vm.appointment_formdata.total_amount = total_amount;
        }

        if (vm.bpa_coupon_apply_disabled == 0) {
            vm.coupon_apply_loader = "1"
            var postData = {
                "bookingpress_apply_coupon_data": {
                    "coupon_code": vm.appointment_formdata.applied_coupon_code,
                    "selected_service": vm.appointment_formdata.appointment_selected_service,
                    "payable_amount": vm.appointment_formdata.total_amount,
                    "appointment_formdata": vm.appointment_formdata
                }
            };

            fetch(rest_url + '/coupon/apply', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': BookingPressConfig.rest_nonce,
                },
                body: JSON.stringify(postData),
            })
                .then(response => response.json())
                .then(rest_response => {
                    vm.coupon_apply_loader = '0';
                    vm.coupon_applied_status = rest_response.data.variant;

                    if (rest_response.data.variant == "error") {
                        vm.coupon_code_msg = rest_response.data.msg
                    } else {
                        vm.appointment_formdata.subtotal_temp = rest_response.data.subtotal_temp;
                        vm.appointment_formdata.subtotal_temp_with_currency = rest_response.data.subtotal_temp_with_currency;
                        vm.coupon_code_msg = rest_response.data.msg
                        vm.appointment_formdata.coupon_discounted_amount = rest_response.data.discounted_amount;
                        vm.appointment_formdata.coupon_discounted_amount_with_currency = rest_response.data.discounted_amount_with_currency;
                        vm.appointment_formdata.applied_coupon_details = rest_response.data.coupon_data;
                        if (typeof rest_response.data.tax != "undefined" && vm.is_tax_enable) {
                            vm.appointment_formdata.tax_before_coupon = vm.appointment_formdata.tax;
                            vm.appointment_formdata.tax_before_coupon_with_currency = vm.appointment_formdata.tax_with_currency;
                            vm.appointment_formdata.tax = rest_response.data.tax;
                            vm.appointment_formdata.tax_with_currency = rest_response.data.tax_with_currency;
                        }
                        vm.bpa_coupon_apply_disabled = 1;
                    }
                    if ('undefined' != typeof vm.appointment_formdata.is_clubbed_service && vm.appointment_formdata.is_clubbed_service) {
                        vm.appointment_formdata.applied_coupon_details = { "coupon_data": rest_response.data.coupon_data };
                        vm.bookingpress_calculate_multiservice_amount();
                    } else {
                        vm.bookingpress_admin_get_final_step_amount();
                    }
                })
                .catch(error => {
                    console.log(error);
                    vm.coupon_apply_loader = '0';
                    this.$notify({
                        title: 'Error',
                        message: 'Something went wrong while applying coupone code',
                        type: 'error',
                        customClass: 'error_notification',
                        duration: BookingPressConfig.notification_timeout
                    });
                });
        }
    }

    ExternalMethods.bookingpress_remove_coupon_code = function () {
        const vm = this

        if (typeof vm.appointment_formdata.bookingpress_coupon_db_details != "undefined" && vm.appointment_formdata.bookingpress_coupon_db_details != "") {
            var bookingpress_applied_coupon_details = JSON.parse(vm.appointment_formdata.bookingpress_coupon_db_details);
            if (typeof vm.appointment_formdata.is_allow_edit_past_appointment != 'undefined' && vm.appointment_formdata.is_allow_edit_past_appointment === 1 && bookingpress_applied_coupon_details != '' && null != bookingpress_applied_coupon_details) {
                return;
            }
        }

        fetch(rest_url + '/coupon/remove', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({ coupon_code: vm.appointment_formdata.applied_coupon_code })
        })
            .then(response => response.json())
            .then(rest_response => { })
            .catch(error => {
                console.log(error);
                vm.coupon_apply_loader = '0';
                this.$notify({
                    title: 'Error',
                    message: 'Something went wrong while removing coupone code',
                    type: 'error',
                    customClass: 'error_notification',
                    duration: BookingPressConfig.notification_timeout
                });
            });

        vm.appointment_formdata.subtotal_temp = 0;
        vm.appointment_formdata.subtotal_temp_with_currency = vm.bookingpress_price_with_currency_symbol(0);
        vm.appointment_formdata.applied_coupon_code = "";
        vm.coupon_code_msg = ""
        vm.bpa_coupon_apply_disabled = 0;
        vm.coupon_applied_status = "error";
        vm.coupon_discounted_amount = "";
        vm.appointment_formdata.coupon_discounted_amount = '';
        vm.appointment_formdata.coupon_discounted_amount_with_currency = '';
        vm.appointment_formdata.applied_coupon_details = [];
        if (vm.is_tax_enable) {
            vm.appointment_formdata.tax = vm.appointment_formdata.tax_before_coupon;
            vm.appointment_formdata.tax_with_currency = vm.appointment_formdata.tax_before_coupon_with_currency;
        }
        if ('undefined' != typeof vm.appointment_formdata.is_clubbed_service && vm.appointment_formdata.is_clubbed_service) {
            vm.bookingpress_calculate_multiservice_amount();
        } else {
            vm.bookingpress_admin_get_final_step_amount();
        }
    }
    /** Coupon related methods */

    /** Number of person */
    ExternalMethods.bookingpress_change_bring_anyone = function () {
        const vm = this
        //vm.bookingpress_appointment_get_disable_dates();
        vm.bookingpress_admin_get_final_step_amount();
        if (vm.appointment_formdata.applied_coupon_code != '') {
            vm.bookingpress_apply_coupon_code();
        }
    }

    ExternalMethods.bookingpress_get_bring_anyone_options = function (min, max, step) {
        min  = parseInt(min)  || 1;
        max  = parseInt(max)  || 10;
        step = parseInt(step) || 1;
        const options = [];
        for (let i = min; i <= max; i += step) {
            options.push(i);
        }
        return options;
    }

    ExternalMethods.bookingpress_snap_bring_anyone_value = function (raw_val) {
        const vm = this;
        if( vm.is_bring_anyone_with_you_enable == 1 ){
            const valid_options = vm.bookingpress_get_bring_anyone_options(
                vm.appointment_formdata.bookingpress_bring_anyone_min_capacity,
                vm.appointment_formdata.bookingpress_bring_anyone_max_capacity,
                vm.appointment_formdata.bookingpress_bring_anyone_step
            );

            const min = valid_options.length > 0 ? valid_options[0] : 1;
            const val = parseInt(raw_val) || min;

            if (valid_options.includes(val)) {
                return val;
            }

            const snapped_val = valid_options.reduce((prev, curr) =>
                Math.abs(curr - val) < Math.abs(prev - val) ? curr : prev
            );
        }
        return snapped_val;
    }

    /** Tax Related */
    ExternalMethods.bookingpress_handle_tax_calculation = function (field_id, event, form_fields) {
        const vm = this;

        if (typeof vm.appointment_formdata.bookingpress_selected_country_field != "undefined" && vm.appointment_formdata.bookingpress_selected_country_field == field_id && typeof form_fields !== "undefined" && typeof form_fields.is_repeater_field_inner_field !== "undefined" && form_fields.is_repeater_field_inner_field != true) {
            if (typeof vm.appointment_formdata.enable_country_wise_tax != "undefined" && vm.appointment_formdata.enable_country_wise_tax == "true") {
                const form_field_value = event;
                const taxPercentage = vm.appointment_formdata.country_wise_tax_details.find(item => item.selectedOption === form_field_value)?.bookingpress_country_wise_tax_per;

                if (typeof vm.appointment_formdata.tax_percentage_temp == "undefined") {
                    vm.appointment_formdata.tax_percentage_temp = vm.appointment_formdata.tax_percentage;
                }
                else {
                    vm.appointment_formdata.tax_percentage = vm.appointment_formdata.tax_percentage_temp;
                }

                if (taxPercentage != "" && typeof taxPercentage != "undefined") {
                    vm.appointment_formdata.tax_percentage = parseFloat(taxPercentage);
                    //tax_percentage = parseFloat(taxPercentage); 
                }
            }
            if (vm.appointment_formdata.applied_coupon_code != '') {
                vm.bookingpress_remove_coupon_code();
            }
            else {
                vm.bookingpress_admin_get_final_step_amount();
            }
        }
    }
    /** Tax Related */

    ExternalMethods.saveProAppointmentBooking = function (bookingAppointment) {
        const vm = this;
        let is_timeslot_display = vm.is_timeslot_display;
        console.log(is_timeslot_display);
        if ('0' == is_timeslot_display) {
            vm[bookingAppointment].appointment_booked_time = "00:00:00";
        }

        console.log(vm.appointment_formdata.appointment_custom_timing);

        if (vm.appointment_formdata.appointment_custom_timing == false) {
            vm.saveProAppointmentBooking_final(bookingAppointment);
        } else {
            this.$refs[bookingAppointment].validate((valid) => {
                if (valid) {
                    let bookingpress_confirm_validate = 1;

                    if (vm.appointment_formdata.appointment_booked_time > vm.appointment_formdata.appointment_booked_end_time && vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit != 'd') {
                        bookingpress_confirm_validate = 0;
                        vm.is_disabled = false;
                        vm.is_display_save_loader = '0';
                        v2.$notify({
                            title: 'Error',
                            message: 'Start time is not greater than End time',
                            type: 'error',
                            customClass: 'error_notification',
                            duration: 5000,
                        });
                    } else if (vm.appointment_formdata.appointment_booked_time == vm.appointment_formdata.appointment_booked_end_time && vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit != 'd') {
                        bookingpress_confirm_validate = 0;
                        vm.is_disabled = false;
                        vm.is_display_save_loader = '0';
                        v2.$notify({
                            title: 'Error',
                            message: 'Start time and End time are not same',
                            type: 'error',
                            customClass: 'error_notification',
                            duration: 5000,
                        });
                    } else if (vm.appointment_formdata.appointment_custom_timing == true) {
                        let selected_date_time = new Date(`${vm.appointment_formdata.appointment_booked_date} ${vm.appointment_formdata.appointment_booked_time}`);
                        let is_past_date = selected_date_time < new Date();
                        if (is_past_date) {
                            bookingpress_confirm_validate = 0;
                            vm.$confirm('You have selected past time for the appointment, Do you still want to continue?', 'Warning', {
                                confirmButtonText: 'Ok',
                                cancelButtonText: 'Cancel',
                                type: 'warning',
                                center: true,
                                customClass: 'bpa_custom_timing_warning_notification',
                            }).then(() => {
                                vm.is_disabled = true;
                                vm.is_display_save_loader = '1';
                                vm.validateAppointmentBeforeSave(bookingAppointment);
                            }).catch(() => {
                                vm.is_disabled = false;
                                vm.is_display_save_loader = '0';
                            });
                        }
                    }
                    if (vm.appointment_formdata.appointment_custom_timing == true && bookingpress_confirm_validate == 1) {
                        vm.validateAppointmentBeforeSave(bookingAppointment);
                    }
                }
            });
        }
    }
    
    ExternalMethods.validateAppointmentBeforeSave = function (bookingAppointment) {

        const vm2 = this;
        fetch(rest_url + '/appointment/validatebeforesave', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify(vm2.appointment_formdata)
        })
        .then(response => response.json())
        .then(rest_response => {
            vm2.is_disabled = false;
            vm2.is_display_save_loader = '0';
            if (rest_response.data.variant == 'warning') {
                vm2.$confirm(rest_response.data.msg, 'Warning', {
                    confirmButtonText: 'Ok',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                    center: true,
                    customClass: 'bpa_custom_timing_warning_notification',
                }).then(() => {
                    vm2.is_disabled = true;
                    vm2.is_display_save_loader = '1';
                    vm2.saveProAppointmentBooking_final(bookingAppointment);
                }).catch(() => {
                    vm2.is_disabled = false;
                    vm2.is_display_save_loader = '0';
                });
            } else if (rest_response.data.variant == 'error') {
                vm2.$notify({
                    title: 'Error',
                    message: rest_response.data.msg,
                    type: 'error',
                    customClass: 'error_notification',
                    duration: 5000,
                });
            } else if (rest_response.data.variant == 'success') {
                vm2.is_disabled = true;
                vm2.is_display_save_loader = '1';
                vm2.saveProAppointmentBooking_final(bookingAppointment);
            }
        })
        .catch(error => {
            console.log(error);
            vm2.is_disabled = false;
            vm2.is_display_save_loader = '0';
            vm2.$notify({
                title: 'Error',
                message: 'Something went wrong..',
                type: 'error',
                customClass: 'error_notification',
                duration: 5000,
            });
        });
    }    

    ExternalMethods.saveProAppointmentBooking_final = function (bookingAppointment) {
        const vm = this;

        if (vm.bookingpress_payment_gateway == "on-site" || vm.bookingpress_payment_gateway == "manual") {
            vm.saveAppointmentBooking(bookingAppointment);
        } else {
            let actual_paid_amount = parseFloat(vm.appointment_formdata.bookingpress_paid_amount);
            let total_after = parseFloat(vm.appointment_formdata.total_amount);
            let is_partial_refund_supported = vm.appointment_formdata.is_partial_refund_supported;

            let show_refund_confirmation = true;
            if (-1 < [3, 5, '3', '5'].indexOf(vm.appointment_formdata.bookingpress_payment_status)) {
                show_refund_confirmation = false;
            }

            if (vm.appointment_formdata.applied_coupon_code != "" && true == show_refund_confirmation && actual_paid_amount != total_after && (typeof vm.appointment_formdata.bookingpress_is_cart === 'undefined' || vm.appointment_formdata.bookingpress_is_cart == 0)) {

                if (actual_paid_amount > total_after) {
                    let confirmMessage = is_partial_refund_supported
                        ? 'You want to refund remaining amount?'
                        : 'You have applied coupon code, Do you want to proceed further?';

                    let applied_coupon_code = vm.appointment_formdata.applied_coupon_code;

                    let confirmButton = is_partial_refund_supported ? 'Refund' : 'Ok';
                    vm.$confirm(confirmMessage, 'Warning', {
                        confirmButtonText: confirmButton,
                        cancelButtonText: 'Cancel',
                        type: 'warning',
                        center: true,
                        customClass: '',
                    }).then(() => {
                        vm.is_disabled = true;
                        vm.is_display_save_loader = '1';
                        if (is_partial_refund_supported == 1) {
                            vm.refundBeforeSave(bookingAppointment);
                        } else {
                            vm.saveAppointmentBooking(bookingAppointment);
                        }
                    }).catch(() => {
                        vm.is_disabled = false;
                        vm.is_display_save_loader = '0';
                    });
                } else {
                    vm.saveAppointmentBooking(bookingAppointment);
                }
            } else {
                vm.saveAppointmentBooking(bookingAppointment);
            }
        }
    }
    
    ExternalMethods.refundBeforeSave = function (postData) {
        const vm2 = this;
        fetch(rest_url + '/appointment/refund-before-save', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                appointment_data: vm2.appointment_formdata
            })
        })
        .then(response => response.json())
        .then(rest_response => {
            if (rest_response.success) {
                vm2.saveAppointmentBooking(postData);
            }
        }).catch(error => {
            console.log(error);
            vm2.$notify({
                title: 'Error',
                message: 'Something went wrong..',
                type: 'error',
                customClass: 'error_notification',
                duration: 3000,
            });

        });
    }

    ExternalMethods.is_field_visibility_on_staff = function (form_fields) {
        const vm = this;

        if (form_fields.bookingpress_field_options.visibility == "staff") {

            const selectedStaff = String(vm.appointment_formdata.selected_staffmember);
            const clubbedStaff = vm.appointment_formdata.clubbed_staff || [];
            const fieldStaff = form_fields.selected_staff || [];

            const fieldStaffStr = fieldStaff.map(String);
            const clubbedStaffStr = clubbedStaff.map(String);

            if (fieldStaffStr.includes(selectedStaff)) {
                return true;
            }

            console.log(clubbedStaffStr);
            if (clubbedStaffStr.length > 0) {
                return clubbedStaffStr.some(staffId => fieldStaffStr.includes(staffId));
            }
        }

        return false;

    }
    ExternalMethods.check_field_value_validation = function (field) {

        let vm = this;

        if (!field.bookingpress_field_options || field.bookingpress_field_options.visibility !== "on_field_value") {
            return true;
        }

        let conditions = field.bookingpress_field_options.conditions;

        if (!conditions || conditions.length === 0) {
            return true;
        }

        let condition_type = field.bookingpress_field_options.selected_condition_on_field || "all";

        let results = [];

        conditions.forEach(cond => {

            if (!cond.bp_condition_field) {
                results.push(true);
                return;
            }

            let target_field = vm.bookingpress_form_fields.find(
                f => f.bookingpress_form_field_id == cond.bp_condition_field
            );

            if (!target_field) {
                results.push(false);
                return;
            }


            let target_value = vm.appointment_formdata.bookingpress_appointment_meta_fields_value[
                target_field.bookingpress_field_meta_key
            ];

            let condition_value = cond.bp_condition_logic_value;

            let is_valid = false;

            switch (cond.bp_condition_operator) {
                case "equals":
                    is_valid = target_value == condition_value;
                    break;

                case "not_equals":
                    is_valid = target_value != condition_value;
                    break;

                case "greater_than":
                    is_valid = parseFloat(target_value) > parseFloat(condition_value);
                    break;

                case "less_than":
                    is_valid = parseFloat(target_value) < parseFloat(condition_value);
                    break;

                case "contains":
                    is_valid = (target_value || "").includes(condition_value);
                    break;
            }

            results.push(is_valid);
        });

        if (condition_type === "any") {
            return results.some(r => r);
        } else {
            return results.every(r => r);
        }

    }

    return ExternalMethods;
}, 10, 1);

wp.hooks.addFilter('bookingpress_get_front_timing_set_additional_appointment_reschedule_xhr_data', 'bookingpress-appointment-booking-pro', function (postData, vm) {
    if ("" != vm.reschedule_formdata.selected_staff_member_id) {
        postData.staffmember_id = vm.reschedule_formdata.selected_staff_member_id;
        postData.appointment_data_obj.bookingpress_selected_staff_member_details = {
            selected_staff_member_id: vm.reschedule_formdata.selected_staff_member_id
        }
    }

    return postData;
}, 10, 2);

wp.hooks.addFilter('bookingpress_get_front_timing_set_additional_appointment_xhr_data', 'bookingpress-appointment-booking-pro', function (postData, vm) {


    if ("undefined" != typeof vm.bookingpress_loaded_extras && "undefined" != typeof vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service]) {
        let bpa_selected_extras = {};
        vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function (element) {
            let is_selected = element.bookingpress_is_selected;
            if ("true" == is_selected || true == is_selected) {
                bpa_selected_extras[element.bookingpress_extra_services_id] = {
                    "bookingpress_is_selected": "true",
                    "bookingpress_selected_qty": element.bookingpress_selected_qty
                };
            }
        });
        postData.appointment_data_obj.bookingpress_selected_extra_details = bpa_selected_extras;
    }

    if ("" != vm.appointment_formdata.selected_staffmember) {
        postData.staffmember_id = vm.appointment_formdata.selected_staffmember;
        postData.appointment_data_obj.bookingpress_selected_staff_member_details = {
            selected_staff_member_id: vm.appointment_formdata.selected_staffmember
        }
    }

    return postData;
}, 10, 2);

wp.hooks.addAction('bookingpress_add_appointment_model_reset', 'bookingpress-appointment-booking-pro', function (vm) {

    vm.appointment_formdata.applied_coupon_code = ''; vm.appointment_formdata.appointment_booked_end_time = '';
    vm.appointment_formdata.appointment_custom_timing = false;

    let appointment_meta_fields = vm.appointment_formdata.bookingpress_appointment_meta_fields_value;
    for (let k in appointment_meta_fields) {
        let currentVal = appointment_meta_fields[k];
        if ("boolean" == typeof currentVal) {
            vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = false;
        } else if ("string" == typeof currentVal) {
            vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = "";
        } else if ("object" == typeof currentVal) {
            vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = [];
        }
    }

    vm.appointment_formdata.complete_payment_url_selection = 'do_nothing';
    vm.appointment_formdata.complete_payment_url_selected_method = [];

    let appointment_form_fields = vm.bookingpress_form_fields;
    for (let m in appointment_form_fields) {
        let currentval = appointment_form_fields[m];
        if (currentval.bookingpress_field_type == 'file') {
            vm.bookingpress_form_fields[m]['bpa_file_list'] = [];
        }
    }
    vm.appointment_formdata.bookingpress_currency_name = vm.appointment_formdata.bookingpress_currency_name_org;
    vm.appointment_formdata.total_amount = 0;
    vm.appointment_formdata.total_amount_with_currency = vm.bookingpress_price_with_currency_symbol(0);
    vm.appointment_formdata.subtotal = 0;
    vm.appointment_formdata.subtotal_with_currency = vm.bookingpress_price_with_currency_symbol(0);

    vm.appointment_formdata.bookingpress_payment_id = 0;
    vm.bpa_multi_appoitnment_coupon_apply_disabled = 0;

    vm.appointment_formdata.bookingpress_remove_deposit = 0;
    vm.appointment_formdata.bookingpress_applied_deposit = 0;
    vm.deposit_type = '';
    vm.deposit_amount = 0;
    vm.bookingpress_deposit_amt_without_currency = 0;
    vm.bookingpress_deposit_due_amt_without_currency = 0;
    vm.bookingpress_deposit_amt_with_currency = '';
    vm.bookingpress_deposit_due_amt_with_currency = '';

    vm.is_display_save_loader = '0';
    vm.is_disabled = false;
    Object.assign(vm.appointment_formdata, vm.default_appointment_formdata);

    /** Service Extras Reset */
    vm.appointment_formdata.selected_extra_services_ids = [];
    for (let m in vm.bookingpress_loaded_extras) {
        for (let i in vm.bookingpress_loaded_extras[m]) {
            vm.bookingpress_loaded_extras[m][i]['bookingpress_is_selected'] = false;
        }
    }
    if (typeof vm.appointment_formdata.extras_total !== 'undefined') {
        vm.appointment_formdata.extras_total = 0;
    }
    if (typeof vm.appointment_formdata.extras_total !== 'undefined') {
        vm.appointment_formdata.extras_total_with_currency = vm.bookingpress_price_with_currency_symbol(0);
    }
    /** Service Extras Reset */

    if (typeof vm.bpa_coupon_apply_disabled !== 'undefined') {
        vm.bpa_coupon_apply_disabled = 0;
    }
    if (typeof vm.appointment_formdata.applied_coupon_code !== 'undefined') {
        vm.appointment_formdata.applied_coupon_code = '';
    }
    if (typeof vm.appointment_formdata.applied_coupon_details !== 'undefined') {
        vm.appointment_formdata.applied_coupon_details = [];
    }
    if (typeof vm.appointment_formdata.coupon_discounted_amount_with_currency !== 'undefined') {
        vm.appointment_formdata.coupon_discounted_amount_with_currency = '';
    }
    if (typeof vm.coupon_applied_status !== 'undefined') {
        vm.coupon_applied_status = '';
    }

}, 10, 1);

wp.hooks.addAction('bookingpress_appointment_change_service_action', 'bookingpress-appointment-booking-pro', function (vm) {

    var is_timeslot_disp = 1;
    vm.is_timeslot_display = '1';
    vm.appointment_formdata.appointment_booked_time = '';

    vm.appointment_services_list.forEach(function (currentValue, index, arr) {
        if (currentValue.category_services.length > 0) {
            currentValue.category_services.forEach(function (currentValue2, index2, arr2) {
                if (currentValue2.service_id == vm.appointment_formdata.appointment_selected_service && currentValue2.service_duration_unit == 'd') {
                    is_timeslot_disp = 0;
                }
            });
        }
    });

    if (is_timeslot_disp == 0) {
        vm.is_timeslot_display = '0';
        vm.appointment_formdata.appointment_booked_time = '00:00:00';
    }

    /** Deposit Related Changes */
    let selected_service_new = vm.appointment_formdata.appointment_selected_service;
    let appointment_id = "";
    let services_lists = vm.appointment_services_list;

    for (let categories of services_lists) {
        let category_service_list = categories.category_services;
        for (let services of category_service_list) {
            let service_id = services.service_id;
            if (service_id == selected_service_new) {
                let bookingpress_applied_deposit = ("undefined" != typeof services.bookingpress_applied_deposit) ? services.bookingpress_applied_deposit : '0';
                let deposit_type = ("undefined" != typeof services.deposit_type) ? services.deposit_type : '';
                let deposit_amount = ("undefined" != typeof services.deposit_amount) ? services.deposit_amount : '';
                vm.appointment_formdata.bookingpress_applied_deposit = bookingpress_applied_deposit;
                vm.appointment_formdata.deposit_type = deposit_type;
                vm.appointment_formdata.deposit_amount = deposit_amount;
                break;
            }
        }
    }

    /** Deposit Related Changes */

    if( vm.is_bring_anyone_with_you_enable == 1 ){
        
        vm.appointment_formdata.selected_bring_members = vm.bookingpress_snap_bring_anyone_value(vm.appointment_formdata.bookingpress_bring_anyone_min_capacity);

        if( vm.appointment_formdata.enable_service_slot_capacity == 1 && parseInt(vm.appointment_formdata.slot_capacity) > 0 ){
            vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(vm.appointment_formdata.slot_capacity);
        }
        
    }	

}, 10, 1);

wp.hooks.addAction('bookingpress_additional_disable_dates', 'bookingpress-appointment-booking-pro', function (vm) {
    vm.bookingpress_admin_get_final_step_amount();
    if (vm.appointment_formdata.applied_coupon_code != '') {
        vm.bookingpress_apply_coupon_code();
    }
}, 10, 1);

wp.hooks.addAction('bookingpress_change_backend_service', 'bookingpress-appointment-booking-pro', function (vm) {

    var is_timeslot_disp = 1;
    vm.is_timeslot_display = '1';
    vm.appointment_formdata.appointment_booked_time = '';

    vm.appointment_services_list.forEach(function (currentValue, index, arr) {
        if (currentValue.category_services.length > 0) {
            currentValue.category_services.forEach(function (currentValue2, index2, arr2) {
                if (currentValue2.service_id == vm.appointment_formdata.appointment_selected_service && currentValue2.service_duration_unit == 'd') {
                    is_timeslot_disp = 0;
                }
            });
        }
    });

    if (is_timeslot_disp == 0) {
        vm.is_timeslot_display = '0';
        vm.appointment_formdata.appointment_booked_time = '00:00:00';
    }

    /** Deposit Related */
    let services_lists = vm.appointment_services_list;
    let selected_service_new = vm.appointment_formdata.appointment_selected_service;
    let appointment_id = "";
    for (let categories of services_lists) {
        let category_service_list = categories.category_services;
        for (let services of category_service_list) {
            let service_id = services.service_id;
            if (service_id == selected_service_new) {
                let bookingpress_applied_deposit = ("undefined" != typeof services.bookingpress_applied_deposit) ? services.bookingpress_applied_deposit : '0';
                let deposit_type = ("undefined" != typeof services.deposit_type) ? services.deposit_type : '';
                let deposit_amount = ("undefined" != typeof services.deposit_amount) ? services.deposit_amount : '';
                vm.appointment_formdata.bookingpress_applied_deposit = bookingpress_applied_deposit;
                vm.appointment_formdata.deposit_type = deposit_type;
                vm.appointment_formdata.deposit_amount = deposit_amount;
                break;
            }
        }
    }
    /** Deposit Related */

    /** Service Extras */
    vm.appointment_formdata.selected_extra_services_ids = '';
    for (let m in vm.bookingpress_loaded_extras) {
        for (let i in vm.bookingpress_loaded_extras[m]) {
            vm.bookingpress_loaded_extras[m][i]['bookingpress_is_selected'] = false;
        }
    }
    if (typeof vm.appointment_formdata.extras_total !== 'undefined') {
        vm.appointment_formdata.extras_total = 0;
    }
    if (typeof vm.appointment_formdata.extras_total !== 'undefined') {
        vm.appointment_formdata.extras_total_with_currency = vm.bookingpress_price_with_currency_symbol(0);
    }
    /** Service Extras */

    /** Staff Member */

    if (typeof vm.appointment_formdata.selected_staffmember != "undefined") {
        vm.appointment_formdata.selected_staffmember = '';
    }

    if( vm.is_bring_anyone_with_you_enable == 1 ){
        vm.appointment_formdata.selected_bring_members = vm.bookingpress_snap_bring_anyone_value(vm.appointment_formdata.bookingpress_bring_anyone_min_capacity);

        if( vm.appointment_formdata.enable_service_slot_capacity == 1 && parseInt(vm.appointment_formdata.slot_capacity) > 0 ){
            vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(vm.appointment_formdata.slot_capacity);
        }

    }	
    /** Staff Member */

});

wp.hooks.addAction('bookingpress_additional_disable_dates', 'bookingpress-appointment-booking-pro', function (vm, response) {
    let bookingpress_appointment_date = vm.appointment_formdata.appointment_booked_date;
    let bookingpress_appointment_form_data = vm.appointment_formdata;
    let bookingpress_moment_formatted_date = moment(bookingpress_appointment_date);
    bookingpress_appointment_date = bookingpress_moment_formatted_date.format('YYYY-MM-DD');
    if (false == response.prevent_next_month_check) {
        let postDataAction = "bookingpress_get_whole_day_appointments";
        if (true == response.check_for_multiple_days_event) {
            postDataAction = "bookingpress_get_whole_day_appointments_multiple_days";
        }
    }
}, 10, 2);

wp.hooks.addFilter('bookingpress_modify_reschedule_config_data', 'bookingpress-appointment-booking-pro', function (ModelConfigData) {

    const AppointmentModelData = getModuleData('bookingpress-appointment-model');

    ModelConfigData.is_rescheduling_booking = false;
    ModelConfigData.is_display_full_reschedule_loader = false;
    ModelConfigData.rescheduling_data = {};

    ModelConfigData.is_custom_timing = false;

    ModelConfigData.reschedule_formdata.default_appointment_timing = AppointmentModelData.default_appointment_timing;

    ModelConfigData.rules.reschedule_end_date = [
        { required: true, message: 'Please select booking end date', trigger: 'change' }
    ];
    ModelConfigData.rules.reschedule_end_time = [
        { required: true, message: 'Please select booking end time', trigger: 'change' }
    ];

    return ModelConfigData;
});

wp.hooks.addFilter('bookingpress_appointment_reschedule_external_methods', 'bookingpress-appointment-booking-pro', function (ExternalMethods) {

    ExternalMethods.change_custom_start_time = function (worktime) {
        const vm = this;
        if (vm.reschedule_formdata.appointment_custom_timing == true) {
            vm.reschedule_formdata.appointment_booked_end_time = '';
        }

        vm.reschedule_formdata.default_appointment_timing.forEach((element, index) => {
            vm.reschedule_formdata.default_appointment_timing[index].is_visible = false;
        });

        vm.reschedule_formdata.default_appointment_timing.forEach((element, index) => {
            if (element.start_time_val == worktime) {
                for (let i = 0; i <= 287; i++) {
                    vm.reschedule_formdata.default_appointment_timing[index + i].is_visible = true;
                }
            }
        });
    }
    ExternalMethods.change_custom_end_time = function (worktime) {
        const vm = this;

        let start_time = vm.reschedule_formdata.reschedule_time;
        let end_time = worktime;

        vm.reschedule_formdata.is_next_day = false;
        vm.reschedule_formdata.is_both_next_day = false;
        vm.reschedule_formdata.appointment_temp_booked_end_time;
        vm.reschedule_formdata.appointment_booked_end_date = vm.reschedule_formdata.booking_date = vm.reschedule_formdata.reschedule_date;

        if (start_time >= '24:00:00') {
            vm.reschedule_formdata.is_next_day = true;
            vm.reschedule_formdata.is_both_next_day = true;
        }

        if (end_time >= '24:00:00') {
            vm.reschedule_formdata.is_next_day = true;
        }

        if (true == vm.reschedule_formdata.is_next_day) {
            let booked_date = new Date(vm.reschedule_formdata.booking_date);
            booked_date.setDate(booked_date.getDate() + 1);
            vm.reschedule_formdata.appointment_booked_end_date = booked_date.toISOString().split("T")[0];
        }

        vm.appointment_formdata.appointment_booked_end_date = vm.reschedule_formdata.appointment_booked_end_date;
    }
    ExternalMethods.onCloseRescheduleModalPopup = function () {
        const vm = this;
        if (true == vm.is_rescheduling_booking) {

            let previousBooking = vm.rescheduling_data;

            let bookingId = previousBooking.id;

            window.BookingPressCalendarApp.updateBooking(bookingId, previousBooking);

            
        }
        const vm2 = window.BookingPressAppointmentDialog;
        Object.assign(vm2.appointment_formdata, vm2.default_appointment_formdata);
    }
    ExternalMethods.bookingpress_get_disable_dates_reschedule = function (fetch_time = false) {

        const vm = this;
        let booking_id = vm.reschedule_formdata.booking_id;
        vm.reschedule_formdata.is_rescheduling = false;
        fetch(rest_url + '/appointment/fetch', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({ appointment_id: booking_id })
        })
            .then(response => response.json())
            .then(rest_response => {
                if (rest_response.success) {
                    const vm2 = window.BookingPressAppointmentDialog;
                    vm2.appointment_customers_list = rest_response.data.appointment_customer_list;
                    vm2.appointment_formdata.appointment_selected_customer = rest_response.data.bookingpress_customer_id;

                    vm2.customer_id = vm2.appointment_formdata.appointment_selected_customer;
                    vm2.bookingpress_get_customer_list({ customer_id: vm2.customer_id });

                    vm2.appointment_formdata.appointment_selected_service = rest_response.data.bookingpress_service_id;
                    vm2.appointment_formdata.appointment_booked_date = rest_response.data.bookingpress_appointment_date;
                    vm2.appointment_formdata.appointment_booked_time = rest_response.data.bookingpress_appointment_time;
                    vm2.appointment_formdata.appointment_booked_end_time = rest_response.data.bookingpress_appointment_end_time;
                    vm2.appointment_formdata.appointment_internal_note = rest_response.data.bookingpress_appointment_internal_note;
                    vm2.appointment_time_slot = rest_response.data.appointment_time_slot;
                    vm2.appointment_formdata.appointment_status = rest_response.data.bookingpress_appointment_status;

                    let selected_date = vm2.appointment_formdata.appointment_booked_date;

                    wp.hooks.doAction('bookingpress_edit_appointment_details', vm2, rest_response);

                    vm.reschedule_formdata.is_rescheduling = fetch_time;

                    vm2.bookingpress_get_disable_dates(false, true);

                    (function (pvm, cvm) {
                        setTimeout(function () {
                            if (pvm.appointment_formdata.appointment_custom_timing) {
                                cvm.appointment_formdata.appointment_custom_timing = pvm.appointment_formdata.appointment_custom_timing;

                                cvm.appointment_formdata.appointment_booked_date = pvm.reschedule_formdata.booking_date;
                                cvm.appointment_formdata.appointment_booked_end_date = pvm.reschedule_formdata.booking_end_date;
                                cvm.appointment_formdata.appointment_booked_time = pvm.reschedule_formdata.booking_time;
                                cvm.appointment_formdata.appointment_booked_end_time = pvm.reschedule_formdata.booking_end_time;
                            }
                            pvm.appointment_formdata = cvm.appointment_formdata;
                        }, 1000);

                    })(vm, vm2);

                }
            })
            .catch(error => {
                console.error('Something went wrong while fetching appointment data:', error);
            });
    }

    ExternalMethods.submitRescheduleForm = function () {
        const vm = this;
        /* let reschedule_data = {
            appointment_update_id: vm.reschedule_formdata.booking_id,
            appointment_booked_date: vm.reschedule_formdata.reschedule_date,
            appointment_booked_end_date: vm.reschedule_formdata.reschedule_end_date,
            appointment_booked_time: vm.reschedule_formdata.reschedule_time,
            appointment_booked_end_time: vm.reschedule_formdata.reschedule_end_time,
            appointment_selected_customer: vm.reschedule_formdata.booking_customer_id,
            appointment_selected_service: vm.reschedule_formdata.booking_service_id,
            appointment_custom_timing: vm.reschedule_formdata.appointment_custom_timing ?? false
        };

        reschedule_data = wp.hooks.applyFilters( 'bookingpress_modify_reschedule_data', reschedule_data, vm); */

        vm.appointment_formdata.appointment_booked_date = vm.reschedule_formdata.reschedule_date;
        //vm.appointment_formdata.
        vm.appointment_formdata.appointment_update_id = parseInt(vm.reschedule_formdata.booking_id);
        vm.appointment_formdata.appointment_booked_time = ('d' == vm.appointment_formdata.selected_service_duration_unit) ? '00:00:00' : vm.reschedule_formdata.reschedule_time;
        vm.appointment_formdata.appointment_booked_end_time = ('d' == vm.appointment_formdata.selected_service_duration_unit) ? '00:00:00' : vm.reschedule_formdata.reschedule_end_time;

        if ('d' == vm.appointment_formdata.selected_service_duration_unit) {
            vm.appointment_formdata.appointment_booked_end_date = '';
            if (vm.appointment_formdata.appointment_custom_timing) {
                vm.appointment_formdata.appointment_booked_end_date = vm.reschedule_formdata.reschedule_end_date;
            }

        }

        let reschedule_data = { appointment_data: vm.appointment_formdata, action:"bookingpress_save_appointment_booking" };

        vm.is_display_reschedule_loader = true;
        vm.is_disabled = true;

        if (false == vm.appointment_formdata.appointment_custom_timing) {
            vm.saveProRescheduleAppointmentBooking_final(reschedule_data);
        } else {
            vm.$refs.reschedule_formdata.validate((valid) => {
                if (valid) {
                    let bookingpress_confirm_validate = 1;

                    if (vm.appointment_formdata.appointment_booked_time > vm.appointment_formdata.appointment_booked_end_time && vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit != 'd') {
                        bookingpress_confirm_validate = 0;
                        vm.is_disabled = false;
                        vm.is_display_reschedule_loader = false;
                        vm.$notify({
                            title: 'Error',
                            message: 'Start time is not greater than End time',
                            type: 'error',
                            customClass: 'error_notification',
                            duration: 5000,
                        });
                    } else if (vm.appointment_formdata.appointment_booked_time == vm.appointment_formdata.appointment_booked_end_time && vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit != 'd') {
                        bookingpress_confirm_validate = 0;
                        vm.is_disabled = false;
                        vm.is_display_reschedule_loader = false;
                        vm.$notify({
                            title: 'Error',
                            message: 'Start time and End time are not same',
                            type: 'error',
                            customClass: 'error_notification',
                            duration: 5000,
                        });
                    } else if (vm.appointment_formdata.appointment_custom_timing == true) {
                        let selected_date_time = new Date(`${vm.appointment_formdata.appointment_booked_date} ${vm.appointment_formdata.appointment_booked_time}`);
                        let is_past_date = selected_date_time < new Date();
                        if (is_past_date) {
                            bookingpress_confirm_validate = 0;
                            vm.$confirm('You have selected past time for the appointment, Do you still want to continue?', 'Warning', {
                                confirmButtonText: 'Ok',
                                cancelButtonText: 'Cancel',
                                type: 'warning',
                                center: true,
                                customClass: 'bpa_custom_timing_warning_notification',
                            }).then(() => {
                                vm.is_disabled = true;
                                vm.is_display_reschedule_loader = false;
                                vm.validateRescheduleAppointmentBeforeSave(reschedule_data);
                            }).catch(() => {
                                vm.is_disabled = false;
                                vm.is_display_reschedule_loader = false;
                            });
                        }
                    }

                    if (vm.appointment_formdata.appointment_custom_timing == true && bookingpress_confirm_validate == 1) {
                        vm.validateRescheduleAppointmentBeforeSave(reschedule_data);
                    }

                }
            });
        }


        /*   */
    }

    ExternalMethods.validateRescheduleAppointmentBeforeSave = function (reschedule_data) {

        const vm2 = this;
        fetch(rest_url + '/appointment/validatebeforesave', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify(vm2.appointment_formdata)
        })
        .then(response => response.json())
        .then(rest_response => {
            vm2.is_disabled = false;
            vm2.is_display_reschedule_loader = '0';

            if (rest_response.data.variant == 'warning') {
                vm2.$confirm(rest_response.data.msg, 'Warning', {
                    confirmButtonText: 'Ok',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                    center: true,
                    customClass: 'bpa_custom_timing_warning_notification',
                }).then(() => {
                    vm2.is_disabled = true;
                    vm2.is_display_reschedule_loader = '1';
                    vm2.saveProRescheduleAppointmentBooking_final(reschedule_data);
                }).catch(() => {
                    vm2.is_disabled = false;
                    vm2.is_display_reschedule_loader = '0';
                });
            } else if (rest_response.data.variant == 'error') {
                vm2.$notify({
                    title: 'Error',
                    message: rest_response.data.msg,
                    type: 'error',
                    customClass: 'error_notification',
                    duration: 5000,
                });
            } else if (rest_response.data.variant == 'success') {
                vm2.is_disabled = true;
                vm2.is_display_reschedule_loader = '1';
                vm2.saveProRescheduleAppointmentBooking_final(reschedule_data);
            }
        })
        .catch(error => {
            console.log(error);
            vm2.is_disabled = false;
            vm2.is_display_reschedule_loader = '0';
            vm2.$notify({
                title: 'Error',
                message: 'Something went wrong..',
                type: 'error',
                customClass: 'error_notification',
                duration: 5000,
            });
        });
    }

    ExternalMethods.saveProRescheduleAppointmentBooking_final = function (reschedule_data) {
        const vm = this;

        const vm2 = window.BookingPressAppointmentDialog;
        fetch(rest_url + '/appointment/create', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify(reschedule_data)
        })
            .then(response => response.json())
            .then(rest_response => {
                if (rest_response.success) {
                    vm.$notify({
                        title: 'Success',
                        message: rest_response.data.msg,
                        type: 'success',
                        customClass: 'success_notification',
                        duration: 5000,
                    });
                    vm.is_display_reschedule_loader = false;
                    vm.is_disabled = false;
                    let new_appointment_details = rest_response.data.appointment_details[0];
                    vm.is_rescheduling_booking = false;
                    window.BookingPressCalendarApp.updateBooking(vm.reschedule_formdata.booking_id, new_appointment_details);
                    vm.closeRescheduleModalPopup();
                    vm2.ResetAppointmentModel();
                } else {

                    vm.$notify({
                        title: 'Error',
                        message: rest_response.data.msg ?? rest_response.msg ?? 'Sorry Something went wrong while rescheduling appointment',
                        type: 'error',
                        customClass: 'error_notification',
                        duration: 5000,
                    });
                    vm.is_display_reschedule_loader = false;
                    vm.is_disabled = false;
                    vm.closeRescheduleModalPopup();
                    vm2.ResetAppointmentModel();

                }
            })
            .catch(error => {
                console.log(error);
                vm.$notify({
                    title: 'Error',
                    message: 'Something went wrong while rescheduling appointment',
                    type: 'error',
                    customClass: 'error_notification',
                    duration: 5000,
                });
                vm.is_display_reschedule_loader = false;
                vm.is_disabled = false;
                vm.closeRescheduleModalPopup();
                vm2.ResetAppointmentModel();
            });
    }

    return ExternalMethods;
});



/** Drag & Drop + Resize event handling */

const validateRescheduleAppointmentData = function (event) {
    const { previousBooking, booking } = event.detail;


    if (window.BookingPressRescheduleDialog) {
        window.dispatchEvent(new CustomEvent('bookingpress:appointment-popover-close'));

        let isDayService = ('undefined' != typeof booking.metadata.isDayService) ? booking.metadata.isDayService : false

        window.BookingPressRescheduleDialog.reschedule_formdata.booking_id = booking.id;
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_date = booking.start_date;
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_time = (!isDayService) ? booking.start_time : '00:00:00';
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_end_time = (!isDayService) ? booking.end_time : '00:00:00';
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_service = booking.serviceName;
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_customer = booking.customerName;
        window.BookingPressRescheduleDialog.reschedule_formdata.formatted_booking_date = booking.metadata.formatted_booking_date;
        window.BookingPressRescheduleDialog.reschedule_formdata.formatted_booking_time = (!isDayService) ? booking.metadata.formatted_booking_time : '00:00:00';
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_service_id = booking.serviceId;
        window.BookingPressRescheduleDialog.reschedule_formdata.booking_customer_id = booking.metadata.customerId;

        window.BookingPressRescheduleDialog.reschedule_formdata.booking_end_date = booking.end_date;
        window.BookingPressRescheduleDialog.appointment_formdata.booking_end_date = booking.end_date;

        window.BookingPressRescheduleDialog.reschedule_formdata.reschedule_date = booking.start_date;   
        window.BookingPressRescheduleDialog.reschedule_formdata.reschedule_time = (!isDayService) ? booking.start_time_val : '00:00:00';


        window.BookingPressRescheduleDialog.change_custom_start_time(booking.start_time_val);
        window.BookingPressRescheduleDialog.reschedule_formdata.reschedule_end_time = (!isDayService) ? booking.end_time_val : '00:00:00';

        let custom_timing = (isDayService) ? false : true;
        custom_timing = wp.hooks.applyFilters('bookingpress_modify_custom_timing', custom_timing, booking);
        window.BookingPressRescheduleDialog.reschedule_formdata.appointment_custom_timing = custom_timing;

        if( isDayService ){
            window.BookingPressRescheduleDialog.reschedule_formdata.reschedule_end_date = booking.end_date;
        }

        window.BookingPressRescheduleDialog.is_rescheduling_booking = true;
        window.BookingPressRescheduleDialog.is_display_full_reschedule_loader = true;
        window.BookingPressRescheduleDialog.rescheduling_data = previousBooking;

        if( true == booking.metadata.isDayService ){
            window.BookingPressRescheduleDialog.is_display_time = false;
        }

        verifyReschedule(custom_timing);

        window.BookingPressRescheduleDialog.openRescheduleModalPopup();

    }
}

/** NOTE - DO NOT REMOVE THE SEMICOLON PLACED BEFORE THE ARRAY - IT IS INTENTIONAL PLACEMENT FOR JS STRICT MODE */
;['bookingpress:appointment-drag-stop', 'bookingpress:appointment-resize-stop'].forEach(event => {
    window.addEventListener(event, validateRescheduleAppointmentData)
});

const decodeHtml = (text) => {
    const el = document.createElement('textarea');
    el.innerHTML = text || '';
    return el.value;
}

const verifyReschedule = (custom_timing = true) => {

    let formData = window.BookingPressRescheduleDialog.reschedule_formdata;

    const vm = window.BookingPressRescheduleDialog;

    fetch(rest_url + '/appointment/can_reschedule', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': BookingPressConfig.rest_nonce,
            'X-Calendar-Nonce': BookingPressConfig.nonce
        },
        body: JSON.stringify(formData)
    })
        .then(response => response.json())
        .then(rest_response => {
            if (rest_response.variant == 'error') {
                let booking_id = vm.rescheduling_data.id;
                window.BookingPressCalendarApp.updateBooking(booking_id, vm.rescheduling_data);

                vm.rescheduling_data = {};
                vm.is_rescheduling_booking = false;
                vm.is_display_full_reschedule_loader = false;

                window.BookingPressRescheduleDialog.closeRescheduleModalPopup();

                let error_message = rest_response.msg;

                vm.$notify({
                    title: 'Error',
                    message: decodeHtml(error_message),
                    type: 'error',
                    customClass: 'error_notification',
                    duration: 5000,
                });
            } else {
                vm.is_custom_timing = custom_timing;

                window.BookingPressRescheduleDialog.appointment_custom_timing = custom_timing;
                window.BookingPressRescheduleDialog.appointment_formdata.appointment_custom_timing = custom_timing;

                window.BookingPressRescheduleDialog.bookingpress_get_disable_dates_reschedule(true);
                //
            }
        })
        .catch(error => {
            console.log(error);
            vm.$notify({
                title: 'Error',
                message: 'Something went wrong while verifying time slot',
                type: 'error',
                customClass: 'error_notification',
                duration: 5000,
            });
        });

}

/** Drag & Drop event handling */

wp.hooks.addAction('bookingpress_modify_reschedule_form_data', 'bookingpress-appointment-booking-pro', function (booking) {

    if ("undefined" != typeof booking.metadata.staffMemberId && booking.metadata.staffMemberId > 0) {
        window.BookingPressRescheduleDialog.reschedule_formdata.selected_staff_member_id = booking.metadata.staffMemberId;
    }

    window.BookingPressRescheduleDialog.reschedule_formdata.appointment_custom_timing = false;
    window.BookingPressRescheduleDialog.appointment_formdata.appointment_custom_timing = false;
    if ('1' == booking.metadata.is_custom_timing) {
        window.BookingPressRescheduleDialog.appointment_formdata.appointment_custom_timing = true;
        window.BookingPressRescheduleDialog.reschedule_formdata.appointment_custom_timing = true;
    }

    window.BookingPressRescheduleDialog.bookingpress_get_disable_dates_reschedule();

});

wp.hooks.addFilter('bookingpress_modify_reschedule_data', 'bookingpress-appointment-booking-pro', function (reschedule_data, vm) {

    if ("undefined" != typeof vm.reschedule_formdata.selected_staff_member_id && 0 < vm.reschedule_formdata.selected_staff_member_id) {
        reschedule_data.selected_staffmember = vm.reschedule_formdata.selected_staff_member_id;
    }

    return reschedule_data;
}, 10, 2);

wp.hooks.addAction('bookingpress_edit_appointment_details', 'bookingpress-appointment-booking-pro', function (vm, response) {

    var bookingpress_appointment_booking_id = response.data.bookingpress_appointment_booking_id;

    var is_timeslot_disp = 1;
    vm.is_timeslot_display = '1';
    for (let index in vm.appointment_services_list) {
        let currentValue = vm.appointment_services_list[index];
        if (currentValue.category_services.length > 0) {
            for (let index2 in currentValue.category_services) {
                let currentValue2 = currentValue.category_services[index2];
                if (currentValue2.service_id == vm.appointment_formdata.appointment_selected_service && currentValue2.service_duration_unit == 'd') {
                    is_timeslot_disp = 0;
                }
            }
        }
    }
    if (is_timeslot_disp == 0) {
        vm.is_timeslot_display = '0';
        vm.appointment_formdata.appointment_booked_time = '00:00:00';
    }

    if (response.data.bookingpress_appointment_customize_timing == 1) {
        vm.appointment_formdata.appointment_custom_timing = true;
    }

    if (typeof response.data.bookingpress_appointment_end_date != "undefined") {
        vm.appointment_formdata.appointment_booked_end_date = response.data.bookingpress_appointment_end_date;
    }

    //Set edited extras value
    if (response.data.bookingpress_extra_service_details != "" && response.data.bookingpress_extra_service_details != null) {
        vm.appointment_formdata.selected_extra_services_ids = [];
        var bookingpress_extra_details = JSON.parse(response.data.bookingpress_extra_service_details);
        bookingpress_extra_details.forEach(function (currentValue, index, arr) {
            vm.appointment_formdata.selected_extra_services_ids.push(currentValue.bookingpress_extra_service_details.bookingpress_extra_services_id);
            vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function (currentValue2, index2, arr2) {
                if (currentValue2.bookingpress_extra_services_id == currentValue.bookingpress_extra_service_details.bookingpress_extra_services_id) {
                    vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service][index2].bookingpress_is_selected = true;
                    vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service][index2].bookingpress_selected_qty = parseInt(currentValue.bookingpress_selected_qty);
                }
            });
        });
    }

    //Set bring anyone with value
    var bring_anyone_max_cap = response.data.bring_anyone_max_capacity;

    if (typeof response.data.slot_capacity !== "undefined" && response.data.bookingpress_enable_service_slot_capacity ==1 && vm.is_bring_anyone_with_you_enable == 1) {
        bring_anyone_max_cap = parseInt(response.data.slot_capacity);
    }

    vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(bring_anyone_max_cap);
    if (vm.is_bring_anyone_with_you_enable == 1) {
        var bring_anyone_min_cap = response.data.bring_anyone_min_capacity;
        vm.appointment_formdata.bookingpress_bring_anyone_min_capacity = parseInt(bring_anyone_min_cap);
    }

    vm.appointment_formdata.selected_bring_members = parseInt(response.data.bookingpress_selected_extra_members);
    vm.appointment_formdata.enable_service_slot_capacity = response.data.bookingpress_enable_service_slot_capacity;

    if (typeof response.data.bookingpress_staff_member_id != 'undefined' && response.data.bookingpress_staff_member_id != 0 && response.data.bookingpress_staff_member_id != '') {
        let selected_staffmember = response.data.bookingpress_staff_member_id;
        if ("" != selected_staffmember) {
            let selected_service = response.data.bookingpress_service_id;
            let selected_service_staffmember = vm.bookingpress_loaded_staff[selected_service];
            let selected_staff_capacity = 1;
            let selected_staff_min_capacity = 1;
            selected_service_staffmember.forEach(function (elm) {
                if (selected_staffmember == elm.bookingpress_staffmember_id) {
                    selected_staff_capacity = elm.bookingpress_service_capacity;

                    if (vm.is_bring_anyone_with_you_enable == 1) {

                        selected_staff_min_capacity = elm.bookingpress_service_min_capacity;

                    }
                    return false;
                }
            });
            if (typeof response.data.slot_capacity !== "undefined" && response.data.bookingpress_enable_service_slot_capacity ==1 && vm.is_bring_anyone_with_you_enable == 1) {
                selected_staff_capacity = response.data.slot_capacity;
            }
            vm.appointment_formdata.bookingpress_bring_anyone_max_capacity = parseInt(selected_staff_capacity);
            if (vm.is_bring_anyone_with_you_enable == 1) {
                vm.appointment_formdata.bookingpress_bring_anyone_min_capacity = parseInt(selected_staff_min_capacity);
            }
        }
    }

    vm.appointment_formdata.bookingpress_bring_anyone_step = 1;
    if (typeof response.data.bookingpress_service_quantity_steps !== "undefined") {
        vm.appointment_formdata.bookingpress_bring_anyone_step = response.data.bookingpress_service_quantity_steps;
    }

    //Set Selected Staff Member
    if (response.data.bookingpress_staff_member_id == 0) {
        vm.appointment_formdata.selected_staffmember = '';
    } else {
        vm.appointment_formdata.selected_staffmember = response.data.bookingpress_staff_member_id;
    }

    if (typeof response.data.bookingpress_service_duration_unit != "undefined") {
        vm.appointment_formdata.selected_service_duration_unit = response.data.bookingpress_service_duration_unit;
    }
    if (typeof response.data.bookingpress_service_duration_val != "undefined") {
        vm.appointment_formdata.selected_service_duration = response.data.bookingpress_service_duration_val;
    }

    if (typeof response.data.is_allow_edit_past_appointment != "undefined") {
        vm.appointment_formdata.is_allow_edit_past_appointment = response.data.is_allow_edit_past_appointment;
    }

    if (typeof response.data.is_partial_refund_supported != "undefined") {
        vm.appointment_formdata.is_partial_refund_supported = response.data.is_partial_refund_supported;
    }

    //Set payment status
    vm.bookingpress_payment_status = response.data.bookingpress_payment_status
    vm.bookingpress_payment_gateway = response.data.bookingpress_payment_gateway
    vm.appointment_formdata.bookingpress_payment_gateway = response.data.bookingpress_payment_gateway
    vm.appointment_formdata.bookingpress_payment_id = response.data.bookingpress_payment_id;
    vm.appointment_formdata.bookingpress_payment_status = response.data.bookingpress_payment_status;

    vm.appointment_formdata.bookingpress_total_amount = response.data.bookingpress_total_amount;
    vm.appointment_formdata.bookingpress_paid_amount = response.data.bookingpress_paid_amount;

    var bookingpress_order_id = response.data.bookingpress_order_id;
    fetch( rest_url + '/appointment/fetch-meta-data', {
        method: 'POST',
        credentials: 'same-origin',
        headers:{
            'Content-Type': 'application/json',
            'X-WP-Nonce': BookingPressConfig.rest_nonce
        },
        body:JSON.stringify({
            bookingpress_appointment_id: bookingpress_appointment_booking_id,
            bookingpress_order_id: bookingpress_order_id
        })
    })
    .then( response => response.json() )
    .then( result => {
        if (result.data.custom_fields_values != "") {
            vm.appointment_formdata.bookingpress_appointment_meta_fields_value = [];
            vm.appointment_formdata.bookingpress_appointment_meta_fields_value = result.data.custom_fields_values;
            vm.bookingpress_form_fields.forEach((element, index) => {
                let appointment_file_field_list = [];
                if ("file" == element.bookingpress_field_type) {

                    let meta_key = element.bookingpress_field_meta_key;
                    let file_upload_urls = vm.appointment_formdata.bookingpress_appointment_meta_fields_value[meta_key];

                    if (Array.isArray(file_upload_urls)) {
                        file_upload_urls.forEach(file_upload_url => {
                            if (file_upload_url) {
                                let file_data = file_upload_url.split('/');
                                let file_name = file_data[file_data.length - 1];
                                let file_obj = {
                                    name: file_name,
                                    url: file_upload_url,
                                    response: { file_ref: meta_key }
                                };
                                appointment_file_field_list.push(file_obj);
                            }
                        });
                    } else {

                        let file_data = file_upload_urls.split('/');
                        let file_name = file_data[file_data.length - 1];
                        let file_obj = {
                            name: file_name,
                            url: file_upload_urls,
                            response: {
                                file_ref: meta_key
                            }
                        };
                        appointment_file_field_list.push(file_obj);
                    }

                    if (file_upload_urls != '') {
                        vm.bookingpress_form_fields[index].bpa_file_list = appointment_file_field_list;
                    } else {
                        vm.bookingpress_form_fields[index].bpa_file_list = [];
                    }
                }

            });
        }
    })
    .catch( error => {
        console.log( error );
    });

    if ("undefined" != typeof vm.appointment_formdata.appointment_custom_timing && true == vm.appointment_formdata.appointment_custom_timing) {

        let worktime = vm.appointment_formdata.appointment_booked_time;
        let endtime = vm.appointment_formdata.appointment_booked_end_time;

        vm.appointment_formdata.default_appointment_timing.forEach((element, index) => {
            vm.appointment_formdata.default_appointment_timing[index].is_visible = false;
            if (element.start_time_val == endtime) {

                let booked_date = response.data.bookingpress_appointment_date;
                //let booked_date = response.data.bookingpress_selected_appointment_date;

                let booked_end_date = response.data.bookingpress_appointment_end_date;

                if (booked_end_date > booked_date) {

                    let next_day_end_time = vm.appointment_formdata.default_appointment_timing[index + 287];
                    vm.appointment_formdata.appointment_booked_end_time = next_day_end_time.end_time_val;

                }
            }
        });

        vm.appointment_formdata.default_appointment_timing.forEach((element, index) => {
            if (element.start_time_val == worktime) {
                for (let i = 0; i <= 287; i++) {
                    vm.appointment_formdata.default_appointment_timing[index + i].is_visible = true;
                }
            }
        });
    }

    /* <?php do_action('bookingpress_reset_tax_for_admin_edit_appointment'); ?>				 */
    wp.hooks.doAction('bookingpress_reset_tax_for_admin_edit_appointment');

    vm.appointment_formdata.bookingpress_coupon_db_details = response.data.bookingpress_coupon_db_details;
    vm.appointment_formdata.bookingpress_currency_name = response.data.bookingpress_service_currency;

    vm.appointment_formdata.bookingpress_is_cart = 0;
    vm.appointment_formdata.bookingpress_is_recurring = 0;

    if (response.data.bookingpress_is_cart !== undefined) {
        vm.appointment_formdata.bookingpress_is_cart = response.data.bookingpress_is_cart;
        vm.bpa_multi_appoitnment_coupon_apply_disabled = response.data.bookingpress_is_cart;
    }
    if (response.data.bookingpress_is_recurring !== undefined) {
        vm.appointment_formdata.bookingpress_is_recurring = response.data.bookingpress_is_recurring;
        if (vm.bpa_multi_appoitnment_coupon_apply_disabled != 1) {
            vm.bpa_multi_appoitnment_coupon_apply_disabled = response.data.bookingpress_is_recurring;
        }
    }

    vm.coupon_applied_status = '';
    if (response.data.bookingpress_coupon_details != "") {
        var bookingpress_applied_coupon_details = JSON.parse(response.data.bookingpress_coupon_details);
        if (bookingpress_applied_coupon_details != '' && null != bookingpress_applied_coupon_details) {
            var bookingpress_coupon_code = bookingpress_applied_coupon_details.bookingpress_coupon_code;
            if (bookingpress_coupon_code == undefined && bookingpress_applied_coupon_details.coupon_data) {
                bookingpress_coupon_code = bookingpress_applied_coupon_details.coupon_data.bookingpress_coupon_code;
            }
            bookingpress_coupon_code = wp.hooks.applyFilters('bookingpress_modify_coupon_code_data', bookingpress_coupon_code, vm, response);

            (function (vm2) {
                setTimeout(function () {
                    vm2.appointment_formdata.applied_coupon_code = bookingpress_coupon_code;
                    vm2.bookingpress_apply_coupon_code();
                }, 2000);
            })(vm)
        }
    }
    vm.bookingpress_admin_get_final_step_amount();

}, 10, 2);

wp.hooks.addAction('bookingpress_edit_appointment_details', 'bookingpress-appointment-booking-pro', function (vm, response) {
    if (typeof response.data.bookingpress_applied_deposit != "undefined") {
        vm.appointment_formdata.bookingpress_applied_deposit = response.data.bookingpress_applied_deposit;
        if (vm.appointment_formdata.bookingpress_applied_deposit == "1") {

            vm.appointment_formdata.bookingpress_deposit_payment_method = "deposit_or_full_price";
            if (typeof response.data.deposit_type != "undefined") {
                vm.appointment_formdata.deposit_type = response.data.deposit_type;
            }
            if (typeof response.data.deposit_amount != "undefined") {
                vm.appointment_formdata.deposit_amount = response.data.deposit_amount;
            }

        }
    }
}, 20, 2);

wp.hooks.addAction('bookingpress_admin_add_appointment_after_select_timeslot', 'bookingpress-appointment-booking-pro', function (data_arr, vm) {
    vm.appointment_formdata.selected_end_date = data_arr.selected_end_date || vm.appointment_formdata.selected_date;
    vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.selected_end_date;

    vm.appointment_formdata.is_next_day = false;
    vm.appointment_formdata.is_both_next_day = false;
    if ("undefined" != typeof data_arr.is_next_day && true == data_arr.is_next_day) {
        vm.appointment_formdata.is_next_day = true;
        vm.appointment_formdata.next_day_selection_date = data_arr.selected_date;
    }

    if ("undefined" != typeof data_arr.is_both_next_day_time && true == data_arr.is_both_next_day_time) {
        vm.appointment_formdata.is_both_next_day = true;
    }
}, 10, 2);

wp.hooks.addAction('bookingpress_admin_reschedule_appointment_after_select_timeslot', 'bookingpress-appointment-booking-pro', function (data_arr, vm) {

    console.log(data_arr);
    vm.appointment_formdata.selected_end_date = data_arr.selected_end_date || vm.appointment_formdata.selected_date;
    vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.selected_end_date;

    vm.appointment_formdata.is_next_day = false;
    vm.appointment_formdata.is_both_next_day = false;
    if ("undefined" != typeof data_arr.is_next_day && true == data_arr.is_next_day) {
        vm.appointment_formdata.is_next_day = true;
        vm.appointment_formdata.next_day_selection_date = data_arr.selected_date;
    }

    if ("undefined" != typeof data_arr.is_both_next_day_time && true == data_arr.is_both_next_day_time) {
        vm.appointment_formdata.is_both_next_day = true;
    }
}, 10, 2);

wp.hooks.addAction('bookingpress_additional_disable_dates', 'bookingpress-appointment-booking-pro', function (vm) {

    const vm2 = window.BookingPressRescheduleDialog;

    if( 'undefined' == typeof vm2 ){
        return;
    }

    vm2.is_display_full_reschedule_loader = false;

    if ("undefined" != typeof vm2.appointment_custom_timing && true == vm2.appointment_custom_timing) {
        vm2.appointment_formdata.appointment_custom_timing = true;
    }

    if ("undefined" != typeof vm2.is_rescheduling_booking && true == vm2.is_rescheduling_booking) {
        let selected_value = vm2.reschedule_formdata.booking_date;
        vm2.select_appointment_booking_date(selected_value, true);
    }

});

wp.hooks.addAction('bookingpress_appointment_customer_selected', 'bookingpress-appointment-booking-pro', function (vm) {
    if (vm.coupon_module = 1 && typeof vm.appointment_formdata.applied_coupon_code != "undefined" && vm.appointment_formdata.applied_coupon_code != "") {
        vm.bookingpress_remove_coupon_code();
    }


});

/** Admin Note Related */
document.addEventListener('DOMContentLoaded', () => {
    initAdminNote();
});

const initAdminNote = () =>{

    let adminNoteConfigData = {
        data(){
            let configData = {
                note_confirm_modal: false,
                is_display_admin_note_loader:'',
                is_admin_note_btn_disabled: false,
                is_mask_display: false,
                admin_note_confirm_form:{
                    bookingpress_add_admin_note: ''
                }
            };

            return configData;
        },
        methods:{
            addadminnote:function( currentElement, appointment_id,payment_id ){
                const vm = this;
                vm.admin_note_confirm_form.appointment_id = appointment_id;
                vm.admin_note_confirm_form.payment_id = payment_id;

                const vm2 = window.BookingPressAppointmentDialog;
                
                fetch( rest_url + '/appointment/fetch-admin-note', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers:{
                        'Content-Type': 'application/json',
                        'X-WP_Nonce': BookingPressConfig.rest_nonce
                    },
                    body: JSON.stringify({
                        bookingpress_appointment_id: appointment_id,
                        bookingpress_payment_id: payment_id
                    })
                })
                .then( response => response.json() )
                .then( response => {
                    if( response.data.variant == 'success' ){
                        vm.admin_note_confirm_form.bookingpress_add_admin_note = response.data.bookingpress_admin_note;
                        vm.note_confirm_modal = true;
                        if( typeof vm2.bpa_adjust_popup_position != 'undefined' ){
                            vm2.bpa_adjust_popup_position( currentElement, 'div#bookingpress-admin-note-model .bp-dialog.bpa-dialog--admin-note');
                        }
                    } else {
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+'_notification',
                            duration:BookingPressConfig.notification_timeout
                        });
                    }
                } )
                .catch( error => {

                });
            },
            bookingpress_add_note:function( payment_id, appointment_id ){
                const vm = this;
                let bpa_admin_note = vm.admin_note_confirm_form.bookingpress_add_admin_note;
				vm.is_display_admin_note_loader = '1';
				vm.is_admin_note_btn_disabled = true;

                fetch( rest_url + '/appointment/add-admin-note', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers:{
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': BookingPressConfig.rest_nonce
                    },
                    body:JSON.stringify({
                        bookingpress_appointment_id:appointment_id,
                        bookingpress_payment_id :payment_id,
                        admin_note: bpa_admin_note
                    })
                })
                .then( response => response.json() )
                .then( response => {
                    if(response.data.variant == "success"){

						vm.admin_note_confirm_form.bookingpress_add_admin_note = bpa_admin_note;
						vm.is_display_admin_note_loader = '0';
						vm.is_admin_note_btn_disabled = false;
						vm.note_confirm_modal = false;

						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:BookingPressConfig.notification_timeout,
						});
						
					} else{
						vm.is_admin_note_btn_disabled = false;
						vm.is_display_admin_note_loader = '0';
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:BookingPressConfig.notification_timeout,
						});
					}
                })
                .catch( error => {

                });
            }
        }
    };

    const BookingPressAdminNote = createApp( adminNoteConfigData );
    BookingPressAdminNote.use( BookingPressUI );
    window.BookingPressAdminNote = BookingPressAdminNote.mount( "#bookingpress-admin-note-model" );

}
/** Admin Note Related */