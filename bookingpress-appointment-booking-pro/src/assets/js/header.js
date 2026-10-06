"use strict";

import { createApp } from 'vue';

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

wp.hooks.addFilter('bookingpress_external_header_data', 'bookingpress', function(externalHeaderData) {

    const moduleData = getModuleData('bookingpress-sidemenu-drawer');

    externalHeaderData.staffmember_module = (moduleData.staffmember_module == 'true' ? '1' : '') || '';
    externalHeaderData.coupon_module = (moduleData.coupon_module == 'true' ? '1' : '') || '';
    

    return externalHeaderData;
});

/** Staff Panel Header */

document.addEventListener('DOMContentLoaded', () => {
    initStaffPanelHeader();
});

const initStaffPanelHeader = function() {
    
    const StaffHeaderConfig = {
        data(){
            return {
                staffmember_customize_notification_model: false,
                is_mask_display: false,
                close_modal_on_esc: true,
                bpa_toggle_active: false
            }
        },
        methods:{
            bookingpress_open_upcomming_appointment_model(currentElement){
                const vm = this;
                const vm2 = window.BookingPressAppointmentDialog;
				vm.staffmember_customize_notification_model = true;
				if( typeof vm2.bpa_adjust_popup_position != 'undefined' ){
					vm2.bpa_adjust_popup_position( currentElement, '.bpa-dialog.bpa-dialog--staff-upcoming-appointment');
				}
            },
            bpa_staffmemeber_toogle_menu(){
                this.bpa_toggle_active = !this.bpa_toggle_active;
            },
            bookingpress_enable_modal(){
                document.body.style.overflow = 'hidden';
            },
            bookingpress_staffmember_logout(url) {
				window.location.href = url;
			},
            bpa_staffmember_view_site( home_url ){
				window.location.href = home_url;
			},
            bpa_staffmember_open_admin_view(){
                var url = new URL(window.location.href);
                url.searchParams.set('staffmember_view', 'admin_view');
                window.location.href = url;
            },
        }
    };

    const StaffHeader = createApp( StaffHeaderConfig );
    StaffHeader.use( BookingPressUI );
    window.staffHeader = StaffHeader.mount( "#bpa-staff-panel-header" );
}

document.addEventListener('DOMContentLoaded', () => {
    initStaffMobHeaderWrapper();
});

const initStaffMobHeaderWrapper = function() {
    
    const StaffMobHeaderConfig = {
        data(){
            return {
                bpa_toggle_active: false,
            }
        },
        methods:{
            bpa_staffmemeber_toogle_menu(){
                this.bpa_toggle_active = !this.bpa_toggle_active;
            },
            bpa_staffmember_open_admin_view(){
                var url = new URL(window.location.href);
                url.searchParams.set('staffmember_view', 'admin_view');
                window.location.href = url;
            },
        }
    };

    const StaffMobHeader = createApp( StaffMobHeaderConfig );
    StaffMobHeader.use( BookingPressUI );
    window.staffMobHeader = StaffMobHeader.mount( "#bpa-staff-panel-mob-header" );
};
/** Staff Panel Header */

wp.hooks.addFilter('bookingpress_external_notice_data', 'bookingpress', function(externalNoticeData) {
    
    externalNoticeData.is_licence_activated = '1';

    return externalNoticeData;
});

wp.hooks.addFilter('bookingpress_notice_wrapper_methods', 'bookingpress', function(externalNoticeMethods) {

    externalNoticeMethods.bookingpress_close_licence_notice = function(){
        const vm = this;

        fetch( rest_url + '/dismiss-license-notice', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                vm.is_licence_activated = '';
            }
        }).catch((error) => {
            console.error('Error:', error);
        });

    }

    return externalNoticeMethods;
});
