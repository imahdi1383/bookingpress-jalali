"use strict";

window.BookingPressExternalData = {
    is_display_activate_loader: '',
    is_display_deactivate_loader: '',
    is_disabled_activate: '',
    is_disabled_deactivate: '',
    bpa_internal_modules: []
}

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


document.addEventListener('DOMContentLoaded', () => {
    initProAddonsWrapper();
});

const initProAddonsWrapper = () => {

    const moduleData = getModuleData('bookingpress-addons');

    const addonModules = moduleData.internal_modules || {};

    let addon_lists = {
        'bookingpress_staffmember_module': {
            'name': addonModules.staffmember_module.name || 'Staff Member',
            'icon_slug': 'bpa_staff_module',
            'key': 'bookingpress_staffmember_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.staffmember_module.description || 'Enable staff option throughout your website',
            'configuration_url': addonModules.staffmember_module.config_url || '',
            'is_configurable': 1,
            'is_active': addonModules.staffmember_module.status || false,
            'documentation_url': addonModules.staffmember_module.documentation_url || '',
        },
        'bookingpress_service_extra_module': {
            'name': addonModules.service_extras_module.name || 'Service Extra',
            'icon_slug': 'bpa_service_extra',
            'key': 'bookingpress_service_extra_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.service_extras_module.description || 'Enable extras for your services',
            'configuration_url': addonModules.service_extras_module.config_url || '',
            'is_active': addonModules.service_extras_module.status || false,
            'documentation_url': addonModules.service_extras_module.documentation_url || ''
        },
        'bookingpress_coupon_module': {
            'name': addonModules.coupon_module.name || 'Coupon Management',
            'key': 'bookingpress_coupon_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.coupon_module.description || 'Give discount coupons while booking an appointment',
            'configuration_url': addonModules.coupon_module.config_url || '',
            'is_active': addonModules.coupon_module.status || false,
            'icon_slug': 'bpa_coupon',
            'documentation_url': addonModules.coupon_module.documentation_url || ''
        },
        'bookingpress_deposit_payment_module': {
            'name': addonModules.deposit_module.name || 'Deposit Payment',
            'key': 'bookingpress_deposit_payment_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.deposit_module.description || 'Allow partial payments to be charged to a customer while booking an appointment',
            'configuration_url': addonModules.deposit_module.config_url || '',
            'is_active': addonModules.deposit_module.status || false,
            'is_configurable': 1,
            'icon_slug': 'bpa_deposit_module',
            'documentation_url': addonModules.deposit_module.documentation_url || ''
        },
        'bookingpress_bring_anyone_with_you_module': {
            'name': addonModules.multiple_quantity_module.name || 'Multiple Quantity',
            'key': 'bookingpress_bring_anyone_with_you_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.multiple_quantity_module.description || 'Allow customer to book an appointment for more than one person',
            'configuration_url': addonModules.multiple_quantity_module.config_url || '',
            'is_active': addonModules.multiple_quantity_module.status || false,
            'icon_slug': 'bpa_guest_module',
            'documentation_url': addonModules.multiple_quantity_module.documentation_url || ''
        },
        'bookingpress_staffmember_commission_module': {
            'name': addonModules.staff_commission_module.name || 'Staff Commission',
            'key': 'bookingpress_staffmember_commission_module',
            'img_url': moduleData.addon_image_url,
            'description': addonModules.staff_commission_module.description || 'Set up staff commission and track appointment earnings',
            'configuration_url': addonModules.staff_commission_module.config_url || '',
            'is_active': addonModules.staff_commission_module.status || false,
            'icon_slug': 'bpa_staff_commission_module',
            'documentation_url': addonModules.staff_commission_module.documentation_url || ''
        }
    };

    window.BookingPressExternalData.bpa_internal_modules = addon_lists;
    Object.assign(window.BookingPressAddons, { bpa_internal_modules: addon_lists });
    window.BookingPressAddons.$forceUpdate();
   /*  window.BookingPressAddons.bpa_lite_addons_new.internal_modules = addon_lists; */
};


wp.hooks.addFilter( 'bookingpress_addon_mounted_methods', 'bookingpress-addons-loader', function(ExternalMethods) {

    ExternalMethods.bookingpress_activate_addon = function(activate_addon_key){
        const vm = this;
        vm.is_display_activate_loader = activate_addon_key;
        vm.is_disabled_activate = activate_addon_key;

        fetch( rest_url + '/addons/activate', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                addon_key: activate_addon_key
            })
        })
        .then(res => res.json())
        .then((response) => {
            vm.is_display_activate_loader = '0';
            vm.is_disabled_activate = '';
            if (response.success) {
                vm.bpa_internal_modules[ activate_addon_key ].is_active = 'true';
                vm.$forceUpdate();
                if( 'bookingpress_staffmember_module' == activate_addon_key ){
                    window.BookingPressHeader.staffmember_module = 1;
                }
                if( 'bookingpress_coupon_module' == activate_addon_key ){
                    window.BookingPressHeader.coupon_module = 1;
                }
                vm.$notify({
                    title: response.data.title,
                    message: response.data.msg,
                    type: response.data.variant,
                    customClass: response.data.variant+'_notification',
                    duration:BookingPressConfig.notification_timeout,
                });	
            } else {
                //console.error("API ERROR:", response.data);
            }
        })
        .catch((err) => {
            vm.is_display_activate_loader = '0';
            vm.is_disabled_activate = '';
            console.error("API ERROR:", err);
        });
    };

    ExternalMethods.bookingpress_deactivate_addon = function( deactivate_addon_key ){
        const vm = this;
        vm.is_display_deactivate_loader = deactivate_addon_key;
        vm.is_disabled_deactivate = deactivate_addon_key;

        fetch( rest_url + '/addons/deactivate', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                addon_key: deactivate_addon_key
            })
        })
        .then(res => res.json())
        .then((response) => {
            vm.is_display_deactivate_loader = '0';
            vm.is_disabled_deactivate = '';
            if (response.success) {
                vm.bpa_internal_modules[ deactivate_addon_key ].is_active = '';
                if( 'bookingpress_staffmember_module' == deactivate_addon_key ){
                    window.BookingPressHeader.staffmember_module = '';
                }
                vm.$notify({
                    title: response.data.title,
                    message: response.data.msg,
                    type: response.data.variant,
                    customClass: response.data.variant+'_notification',
                    duration:BookingPressConfig.notification_timeout,
                });	
            } else {
                vm.$notify({
                    title: 'Error',
                    message: response.msg,
                    type: 'error',
                    customClass: 'error_notification',
                    duration:BookingPressConfig.notification_timeout,
                });
            }
        })
        .catch((err) => {
            vm.is_display_activate_loader = '0';
            vm.is_disabled_deactivate = '';
            console.error("API ERROR:", err);
        });
    }

    ExternalMethods.bookingpress_configure_redirection = function( addon_url ){
        if( '' != addon_url ){
            window.location.href = addon_url;
        }
    }

    ExternalMethods.bookingpress_open_addon_download_url = function( download_url ){
        window.open(download_url, '_blank');
    }

    ExternalMethods.bookingpress_activate_plugin = function( plugin_name, plugin_key, addon_name, addons, incompatible_addons = [], skip_confirmation = false ){
        const vm = this;
        vm.is_display_activate_loader = plugin_name;
        vm.is_disabled_activate = plugin_name;

        let show_confirmation = false;
        if( 0 < incompatible_addons.length ){
            show_confirmation = true;
        }

        let args = arguments;

        fetch( rest_url + '/addons/activate-plugin', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                plugin_name: plugin_name,
                addon_name: addon_name,
                incompatible_addons: incompatible_addons,
                show_confirmation: show_confirmation,
                skip_confirmation: skip_confirmation
            })
        })
        .then(res => res.json())
        .then((response) => {
            vm.is_display_activate_loader = '0';
            vm.is_disabled_activate = '';
            if (response.success) {
                if( response.data && 'confirmation' == response.data.variant && response.data.msg ){
                    vm.$confirm( response.data.msg, 'Warning', {
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Cancel',
                        type: 'warning',
                        customClass: 'bpa_addon_page_warning_notification bpa_custom_warning_notification'
                    } ).then( () => {
                        vm.is_display_activate_loader = '0'
                        vm.is_disabled_activate = false;
                        vm.bookingpress_activate_plugin( args[0], args[1], args[2], args[3], args[4], true );
                    }).catch( () => {
                        vm.is_display_activate_loader = '0'
                        vm.is_disabled_activate = false;
                    });
                } else {
                    
                    vm.is_display_activate_loader = '0'
                    vm.is_disabled_activate = false
                    if(response.data.variant == 'success') {
                        //addons.addon_isactive = '1';
                        vm.bookingpress_get_remote_addons_lite_list();
                    }
                    vm.$notify({
                        title: response.data.title,
                        message: response.data.msg,
                        type: response.data.variant,
                        customClass: response.data.variant+'_notification',
                        duration:BookingPressConfig.notification_timeout,
                    });
                }
                vm.$forceUpdate();
            } else if( 'undefined' == typeof response.data.msg ){
                vm.$notify({
                    title: "Error",
                    message: "Please activate license of BookingPress premium plugin to use BookingPress Add-on",
                    type: "error",
                    customClass: response.data.variant + "_notification",
                    duration: BookingPressConfig.notification_timeout
                });
                vm.is_display_activate_loader = '0'
                vm.is_disabled_activate = false
            } else {
                vm.$notify({
                    title: 'Error',
                    message: 'Something went wrong while Activating the plugin',
                    duration: BookingPressConfig.notification_timeout
                });
                //console.error("API ERROR:", response.data);
            }
        })
        .catch((err) => {
            vm.is_display_activate_loader = '0';
            vm.is_disabled_activate = '';
            console.error( err );
        });
    }

    ExternalMethods.bookingpress_deactivate_plugin = function( plugin_name, addons ){
        const vm = this;
        vm.is_display_deactivate_loader = plugin_name;
        vm.is_disabled_deactivate = plugin_name;

        fetch( rest_url + '/addons/deactivate-plugin', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': BookingPressConfig.rest_nonce,
            },
            body: JSON.stringify({
                plugin_name: plugin_name,
            })
        })
        .then(res => res.json())
        .then((response) => {
            
            vm.is_display_deactivate_loader = '0';
            vm.is_disabled_deactivate = '';
            if (response.success) {
                vm.is_display_deactivate_loader = '0'
                vm.is_disabled_deactivate = false
                if(response.data.variant == 'success') {
                    vm.bookingpress_get_remote_addons_lite_list();
                }
                vm.$notify({
                    title: response.data.title,
                    message: response.data.msg,
                    type: response.data.variant,
                    customClass: response.data.variant+'_notification',
                    duration:BookingPressConfig.notification_timeout,
                });
                vm.$forceUpdate();
            } else {
                vm.$notify({
                    title: 'Error',
                    message: 'Something went wrong while Deactivating the plugin',
                    duration: BookingPressConfig.notification_timeout
                });
                //console.error("API ERROR:", response.data);
            }
        })
        .catch((err) => {
            vm.is_display_deactivate_loader = '0';
            vm.is_disabled_deactivate = '';
            console.error( err );
        });
    }

    ExternalMethods.bookingpress_configure_redirection = function( addon_url ){
        console.log( addon_url );
        if( '' != addon_url ){
            window.location.href = addon_url;
        }
    }

    return ExternalMethods;
});