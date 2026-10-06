"use strict";

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

wp.hooks.addFilter( 'bookingpress_dashboard_config_data', 'bookingpress-pro-dashboard', function( externalData ){
    const DashboardModuleData = getModuleData('bookingpress-dashboard-loader');

    let dashboard_data = DashboardModuleData.bpa_pro_dashboard_data;

    delete dashboard_data.custom_filter_val;

    return {
        ...externalData,
        ...dashboard_data,
    };
});

wp.hooks.addFilter( 'bookingpress_dashboard_loader_methods', 'bookingpress-pro-dashboard', function( externalMethod ) {

    externalMethod.open_admin_note = function( currentElement,appointment_id,payment_id ){
        window.BookingPressAdminNote.addadminnote( currentElement, appointment_id, payment_id );
    }

    externalMethod.bookingpress_add_note = function( payment_id, appointment_id ){
        window.BookingPressAdminNote.bookingpress_add_note( payment_id, appointment_id );
    }

    return externalMethod;

});

wp.hooks.addFilter( 'bookingpress_dashboard_redirect_url', 'bookingpress-pro-dashboard', function( redirect_url, module ) {
    const DashboardModuleData = getModuleData('bookingpress-dashboard-loader');
    
    if( 'staff_members' == module ) {
        redirect_url = `${DashboardModuleData.redirect_urls.staffmember}`;
    } else if( 'commission' == module ) {
        redirect_url = `${DashboardModuleData.redirect_urls.commission}`;
    }

    return redirect_url;
});

wp.hooks.addAction( 'bookingpress_modify_dashboard_summary_response_data', 'bookingpress-pro-dashboard', function( response, vm ){

    vm.summary_data.total_staffmembers = response.data.total_staffmembers;

}, 10, 2);