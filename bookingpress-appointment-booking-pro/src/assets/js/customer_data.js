"use strict";

wp.hooks.addAction( 'bookingpress_customer_edit_details', 'bookingpress-pro-customer', function( BookingPressCustomerDialog, rest_response ){
    let edit_customer_details_data = rest_response.edit_data;    
    if (typeof edit_customer_details_data.customer_metadata !== 'undefined') {
        for (let meta_key in edit_customer_details_data.customer_metadata) {
            let meta_value = edit_customer_details_data.customer_metadata[meta_key];
            if (Array.isArray(meta_value)) {
                window.BookingPressCustomerDialog.customer.bpa_customer_field = {
                    ...window.BookingPressCustomerDialog.customer.bpa_customer_field,
                    [meta_key]: [...meta_value]
                };
            } else {
                window.BookingPressCustomerDialog.customer.bpa_customer_field = {
                    ...window.BookingPressCustomerDialog.customer.bpa_customer_field,
                    [meta_key]: meta_value
                };
            }
        }
    }
});
wp.hooks.addAction('bookingpress_reset_customer_fields_data', 'bookingpress-pro-customer',() => {
    const fields = window.BookingPressCustomerDialog?.customer?.bpa_customer_field;
    if(!fields) return;
    Object.keys(fields).forEach((meta_key) => {
    window.BookingPressCustomerDialog.customer.bpa_customer_field = {
        ...window.BookingPressCustomerDialog.customer.bpa_customer_field,
            [meta_key]: Array.isArray(fields[meta_key]) ? [] : ''
        };
    });
});