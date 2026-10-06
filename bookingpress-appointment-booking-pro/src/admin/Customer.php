<?php

namespace BookingPressPro\admin;

if( !defined( 'ABSPATH' ) ){
    exit;
}

class Customer extends Base{

    public static function init(){
        parent::init();          

        add_filter( 'bookingpress_customer_data', [ __CLASS__, 'bookingpress_add_customer_script_module_data_func' ] );

        add_filter( 'script_module_data_bookingpress-appointment-model', [ __CLASS__, 'bookingpress_add_customer_app_script_module_data_func' ] );
    }

    public static function bookingpress_add_customer_app_script_module_data_func($customer_data){

        global $wpdb, $tbl_bookingpress_form_fields;

        $bpa_customer_form_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tbl_bookingpress_form_fields}` WHERE bookingpress_is_customer_field = %d ORDER BY bookingpress_field_position ASC", 1 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_form_fields is table name.
        $bpa_customer_fields = array();

        if( !empty( $bpa_customer_form_fields ) ){
			foreach( $bpa_customer_form_fields as $x => $cs_form_fields ){
                $bpa_customer_fields[ $x ] = $cs_form_fields;
                $bpa_customer_fields[ $x ]['bookingpress_field_label']  = stripslashes_deep($cs_form_fields['bookingpress_field_label']);
                $bpa_customer_fields[ $x ]['bookingpress_field_error_message']  = stripslashes_deep($cs_form_fields['bookingpress_field_error_message']);
                $bpa_customer_fields[ $x ]['bookingpress_field_placeholder']  = stripslashes_deep($cs_form_fields['bookingpress_field_placeholder']);
                $bpa_customer_fields[ $x ]['bookingpress_field_values'] = json_decode( $cs_form_fields['bookingpress_field_values'], true );
                $bpa_customer_fields[ $x ]['bookingpress_field_options'] = json_decode( $cs_form_fields['bookingpress_field_options'], true );
                $bpa_customer_fields[ $x ]['bookingpress_field_key'] = '';

                if( 'checkbox' == $cs_form_fields['bookingpress_field_type'] ){
                    $bpa_customer_fields[ $x ]['bookingpress_field_key'] = array();
                    foreach( $bpa_customer_fields[ $x ]['bookingpress_field_values'] as $chk_key => $chk_val ){
                        $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key'] . '_' . $chk_key ] = false;
                    }
                    $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key']] = [];
                } else {
                    $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key']] = $bpa_customer_fields[ $x ]['bookingpress_field_key'];
                }                          
            }
        }
        $customer_data['bookingpress_customer_fields'] = $bpa_customer_fields;   

        return $customer_data;
    }

    public static function bookingpress_add_customer_script_module_data_func($customer_data){

        global $wpdb, $tbl_bookingpress_form_fields;

        $bookingpress_import_field_data = (isset($customer_data['bookingpress_import_field_data']))?$customer_data['bookingpress_import_field_data']:array();

        if(!empty($bookingpress_import_field_data)){
            
            $bookingpress_import_fields = (isset($customer_data['bookingpress_import_fields']))?$customer_data['bookingpress_import_fields']:array();
			
            $customer_data['is_allow_customer_import'] = '1';

            $all_custom_fields = $wpdb->get_results( $wpdb->prepare( "SELECT bookingpress_field_label,bookingpress_field_type, bookingpress_field_values,bookingpress_field_meta_key FROM `{$tbl_bookingpress_form_fields}` WHERE bookingpress_field_is_default = %d AND bookingpress_is_customer_field = 1 AND bookingpress_field_type NOT IN ('terms_and_conditions','file','3_col','4_col','2_col')", 0 ), ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason $tbl_bookingpress_form_fields is a table name.

			$bookingpress_all_custom_import_fields = $all_custom_fields;

            if(!empty($all_custom_fields)){
                foreach($all_custom_fields as $custom_field){
                    $bookingpress_field_meta_key = (isset($custom_field['bookingpress_field_meta_key']) && !empty($custom_field['bookingpress_field_meta_key']))?$custom_field['bookingpress_field_meta_key']:'';
                    $bookingpress_field_label    = (isset($custom_field['bookingpress_field_label']) && !empty($custom_field['bookingpress_field_label']))?$custom_field['bookingpress_field_label']:'';
                    $bookingpress_import_field_data[] = array(
                        'field_key'    => $bookingpress_field_meta_key,
                        'field_label'  => $bookingpress_field_label,
                        'is_required'  => 0,
                        'is_userfield' => 0,							
                    );
                }

                foreach($bookingpress_import_field_data as $bookingpress_import_field){
                    $bookingpress_import_fields[$bookingpress_import_field['field_key']] = '';
                }
            }
            $customer_data['bookingpress_import_fields']         = $bookingpress_import_fields;
            $customer_data['bookingpress_import_fields_org']     = $bookingpress_import_fields;
            $customer_data['bookingpress_import_field_data']     = $bookingpress_import_field_data;
            $customer_data['bookingpress_import_field_data_org'] = $bookingpress_import_field_data;
        }

        
        $bpa_customer_form_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tbl_bookingpress_form_fields}` WHERE bookingpress_is_customer_field = %d ORDER BY bookingpress_field_position ASC", 1 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_form_fields is table name.
        $bpa_customer_fields = array();

        $customer_data['export_checked_field_lite'] = array( 'first_name', 'last_name', 'email', 'phone', 'note', 'last_appointment', 'total_appointments' );

        if( !empty( $bpa_customer_form_fields ) ){
			foreach( $bpa_customer_form_fields as $x => $cs_form_fields ){
                $bpa_customer_fields[ $x ] = $cs_form_fields;
                $bpa_customer_fields[ $x ]['bookingpress_field_label']  = stripslashes_deep($cs_form_fields['bookingpress_field_label']);
                $bpa_customer_fields[ $x ]['bookingpress_field_error_message']  = stripslashes_deep($cs_form_fields['bookingpress_field_error_message']);
                $bpa_customer_fields[ $x ]['bookingpress_field_placeholder']  = stripslashes_deep($cs_form_fields['bookingpress_field_placeholder']);
                $bpa_customer_fields[ $x ]['bookingpress_field_values'] = json_decode( $cs_form_fields['bookingpress_field_values'], true );
                $bpa_customer_fields[ $x ]['bookingpress_field_options'] = json_decode( $cs_form_fields['bookingpress_field_options'], true );
                $bpa_customer_fields[ $x ]['bookingpress_field_key'] = '';

                if( 'checkbox' == $cs_form_fields['bookingpress_field_type'] ){
                    $bpa_customer_fields[ $x ]['bookingpress_field_key'] = array();
                    foreach( $bpa_customer_fields[ $x ]['bookingpress_field_values'] as $chk_key => $chk_val ){
                        $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key'] . '_' . $chk_key ] = false;
                    }
                    $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key']] = [];
                } else {
                    $customer_data['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key']] = $bpa_customer_fields[ $x ]['bookingpress_field_key'];
                }

                $customer_data['customer_export_field_list_lite'][] = array(
                    'name' => 'bpa_custom_field_'.$cs_form_fields['bookingpress_field_meta_key'],
                    'text' => stripslashes_deep($cs_form_fields['bookingpress_field_label']),						
                );
                $customer_data['export_checked_field_lite'][] = 'bpa_custom_field_'.$cs_form_fields['bookingpress_field_meta_key'];                
            }
        }
        $customer_data['bookingpress_customer_fields'] = $bpa_customer_fields;        
        $customer_data['export_checked_field_lite_org'] = $customer_data['export_checked_field_lite']; 

        return $customer_data;
    }

    public static function getCustomerViewComponents(){
        require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/CustomerModel.php';        
        global $BookingPressPro, $bookingpress_roles;
        $custom_role = "";
        if(!empty($bookingpress_roles)){
            $custom_role = $bookingpress_roles->get_users_custom_role();	
        }
        if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/StaffSideMenuDrawer.php';
        } else {
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/SideMenuDrawer.php';
        }        
    }

    public static function render_customer_header(){
        global $BookingPressPro, $bookingpress_roles;
        $custom_role = "";
        if(!empty($bookingpress_roles)){
            $custom_role = $bookingpress_roles->get_users_custom_role();	
        }
        if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) || (!empty($custom_role) && $BookingPressPro->bookingpress_check_user_role( $custom_role )) ){
            require_once BOOKINGPRESS_DIR_PRO . '/src/views/components/StaffMenuBar.php';
        } else {
            require_once BOOKINGPRESS_DIR . '/src/views/components/Header.php';
        }
    }

    public static function enqueue_assets( $hook ) {
        // Child classes implement specific enqueuing here
        if ( empty( $_REQUEST['page'] ) || $_REQUEST['page'] !== 'bookingpress_customers' ) {
            return;
        }
        wp_register_script_module('bookingpress-customer_data', BOOKINGPRESS_PRO_URL . '/src/assets/js/customer_data.js', ['bookingpress-ui'], BOOKINGPRESS_PRO_VERSION);
        wp_enqueue_script_module( 'bookingpress-customer_data' );        
    }
}