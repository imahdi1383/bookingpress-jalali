<?php

if ( ! class_exists( 'bookingpress_coupons' ) && class_exists('BookingPress_Core')) {
	class bookingpress_coupons Extends BookingPress_Core {
		function __construct() {
			if ( $this->bookingpress_check_coupon_module_activation() ) {
				add_filter( 'bookingpress_coupons_dynamic_view_load', array( $this, 'bookingpress_load_coupons_view_func' ), 10 );
				add_action( 'bookingpress_coupons_dynamic_vue_methods', array( $this, 'bookingpress_coupons_vue_methods_func' ) );
				add_action( 'bookingpress_coupons_dynamic_on_load_methods', array( $this, 'bookingpress_coupons_on_load_methods_func' ) );
				add_action( 'bookingpress_coupons_dynamic_data_fields', array( $this, 'bookingpress_coupons_dynamic_data_fields_func' ) );
				add_action( 'bookingpress_coupons_dynamic_helper_vars', array( $this, 'bookingpress_coupons_dynamic_helper_vars_func' ) );

				add_action( 'wp_ajax_bookingpress_save_coupon_details', array( $this, 'bookingpress_save_coupon_details' ) );
				add_action( 'wp_ajax_bookingpress_load_coupon_details', array( $this, 'bookingpress_load_coupon_details' ) );
				add_action( 'wp_ajax_bookingpress_edit_coupon_data', array( $this, 'bookingpress_edit_coupon_details' ) );
				add_action( 'wp_ajax_bookingpress_delete_coupon_details', array( $this, 'bookingpress_delete_coupon_details' ) );
				add_action( 'wp_ajax_bookingpress_coupon_bulk_actions', array( $this, 'bookingpress_bulk_actions' ) );
				add_action( 'wp_ajax_bookingpress_change_coupon_status', array( $this, 'bookingpress_change_coupon_status' ) );

				add_action( 'wp_ajax_bookingpress_apply_coupon_code', array( $this, 'bookingpress_apply_coupon_code_func' ) );
				add_action( 'wp_ajax_nopriv_bookingpress_apply_coupon_code', array( $this, 'bookingpress_apply_coupon_code_func' ) );

				add_action( 'wp_ajax_bookingpress_remove_coupon_code', array( $this, 'bookingpress_remove_coupon_code_func' ));
				add_action( 'wp_ajax_nopriv_bookingpress_remove_coupon_code', array( $this, 'bookingpress_remove_coupon_code_func' ));

				add_action( 'bookingpress_after_book_appointment', array( $this, 'bookingpress_remove_locked_coupon_usage'), 10, 3 );
				add_action( 'bookingpress_after_change_appointment_status', array( $this, 'bookingpress_reset_coupon_usage'), 10, 2 );

				add_action('wp_ajax_bookingpress_apply_coupon_code_backend', array($this, 'bookingpress_apply_coupon_code_backend_func'));

				add_filter('bookingpress_customize_add_dynamic_data_fields',array($this,'bookingpress_customize_add_dynamic_data_fields_func'),10);
                add_filter('bookingpress_get_booking_form_customize_data_filter',array($this, 'bookingpress_get_booking_form_customize_data_filter_func'),10,1);

				add_filter('bookingpress_frontend_apointment_form_add_dynamic_data',array($this, 'bookingpress_frontend_apointment_form_add_dynamic_data_func'),10,1);

				add_filter('bookingpress_add_setting_dynamic_data_fields',array($this,'bookingpress_add_setting_dynamic_data_fields_func'));

				add_filter( 'bookingpress_before_selecting_booking_service', array( $this, 'bookingpress_reset_coupon_discount_on_service_selection') );
				
				add_action('bookingpress_add_appointment_model_reset',array($this,'bookingpress_add_appointment_model_reset_func'),11);

				add_action( 'bookingpress_dashboard_add_appointment_model_reset', array($this, 'bookingpress_add_appointment_model_reset_func'),11);

				add_action('bookingpress_calendar_add_appointment_model_reset', array( $this, 'bookingpress_add_appointment_model_reset_func' ),11);
				
				add_filter( 'bookingpress_front_booking_dynamic_on_load_methods', array( $this, 'bookingpress_reset_coupon_load_method') );
				add_filter( 'bookingpress_add_complete_payment_onload_method', array( $this, 'bookingpress_reset_coupon_load_method') , 12);

				add_action( 'bookingress_backend_after_select_customer', array( $this, 'bookingress_backend_after_select_customer_coupon'));

				if(is_plugin_active('bookingpress-multilanguage/bookingpress-multilanguage.php')) {
					add_filter('bookingpress_modified_language_translate_fields',array($this,'bookingpress_modified_language_translate_fields_func'),10);
                	add_filter('bookingpress_modified_customize_form_language_translate_fields',array($this,'bookingpress_modified_customize_form_language_translate_fields_func'),10);
				}
			}
		}


		function bookingress_backend_after_select_customer_coupon() {
			?>
			if(vm.coupon_module = 1 && typeof vm.appointment_formdata.applied_coupon_code != "undefined" && vm.appointment_formdata.applied_coupon_code != ""){
				vm.bookingpress_remove_coupon_code();
			}
			<?php	
        }


		function bookingpress_reset_coupon_load_method( $bookingpress_dynamic_on_load_methods_data ){

			$bookingpress_create_nonce      = wp_create_nonce( 'bpa_wp_nonce' );

			$bookingpress_dynamic_on_load_methods_data .= '
				window.onbeforeunload = function(){
					let vm = vm_onload;
					if( "undefined" != typeof vm.is_coupon_activated && 1 == vm.is_coupon_activated ){
						let applied_coupon = vm.appointment_step_form_data.coupon_code;
						if( "" != applied_coupon ){
							localStorage.setItem( "bpa_reset_coupon", true );
							localStorage.setItem( "bpa_reset_coupon_code", applied_coupon );
						}
					}
				}
					
				if( null != localStorage.getItem( "bpa_reset_coupon" ) && "true" == localStorage.getItem( "bpa_reset_coupon" ) ){
					let vm = vm_onload;
					let applied_coupon = localStorage.getItem( "bpa_reset_coupon_code" );

					let bookingpress_remove_coupon_data = {
						action: "bookingpress_remove_coupon_code",
						coupon_code: applied_coupon,
					};
					let bkp_wpnonce_pre = "' . $bookingpress_create_nonce . '";
					let bkp_wpnonce_pre_fetch = document.getElementById("_wpnonce");
					if(typeof bkp_wpnonce_pre_fetch=="undefined" || bkp_wpnonce_pre_fetch==null)
					{
						bkp_wpnonce_pre_fetch = bkp_wpnonce_pre;
					}
					else {
						bkp_wpnonce_pre_fetch = bkp_wpnonce_pre_fetch.value;
					}

					bookingpress_remove_coupon_data._wpnonce = bkp_wpnonce_pre_fetch;
					axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( bookingpress_remove_coupon_data ) )
					.then( function (response) {
						localStorage.removeItem( "bpa_reset_coupon" );
						localStorage.removeItem( "bpa_reset_coupon_code" );
					}.bind(this) )
					.catch( function (error) {
						vm.bookingpress_set_error_msg(error);
					});

				}
			';

			return $bookingpress_dynamic_on_load_methods_data;
		}

		
		/**
		 * bpa function for coupon applied
		 *
		 * @param  mixed $user_detail
		 * @return void
		*/
		function bookingpress_bpa_apply_coupon_code_func($user_detail=array()){
			global $BookingPress,$wpdb,$BookingPressPro;		
			$result = array();
			$response = array('status' => 0, 'message' => '', 'response' => array('result' => $result));
			if(class_exists('BookingPressPro') && method_exists( $BookingPressPro, 'bookingpress_bpa_check_valid_connection_callback_func') && $BookingPressPro->bookingpress_bpa_check_valid_connection_callback_func()){
				if(!empty($bookingpress_nonce)){
					$_REQUEST['_wpnonce'] = $bookingpress_nonce;
				}else{
					$bookingpress_nonce = wp_create_nonce('bpa_wp_nonce');
					$_REQUEST['_wpnonce'] = $bookingpress_nonce;					
				}				
				$user_detail = !empty($user_detail) ? array_map(array( $BookingPress, 'appointment_sanatize_field' ), $user_detail) : array();
				$appointment_details = isset($user_detail['appointment_details']) ? $user_detail['appointment_details'] : '';
				if(!empty($appointment_details)){
					$_REQUEST['appointment_details'] = $appointment_details;
					$_POST = $_REQUEST;
				}												
				$bookingpress_response = $this->bookingpress_apply_coupon_code_func(true);

				$bookingpress_check_response = (isset($bookingpress_response['variant']))?$bookingpress_response['variant']:'';
				if($bookingpress_check_response == 'error'){					
					$message = (isset($bookingpress_response['msg']))?$bookingpress_response['msg']:'';
					$response = array('status' => 0, 'message' => $message, 'response' => array('result' => $result));					
				}else{
					$result = $bookingpress_response;
					$response = array('status' => 1, 'message' => '', 'response' => array('result' => $result));					
				}

			}
			return $response;
		}

		function bookingpress_modified_customize_form_language_translate_fields_func($bookingpress_all_language_translation_fields){

			$bookingpress_coupon_language_translation_fields = array(                
				'coupon_code_title' => array('field_type'=>'text','field_label'=>__('Coupon heading title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),               
				'coupon_code_field_title' => array('field_type'=>'text','field_label'=>__('Enter coupon placeholder', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),    
				'coupon_apply_button_label' => array('field_type'=>'text','field_label'=>__('Coupon apply button label', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),      
				'couon_applied_title' => array('field_type'=>'text','field_label'=>__('Coupon applied title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),    
			);  
			$bookingpress_all_language_translation_fields['customized_form_summary_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_summary_step_labels'], $bookingpress_coupon_language_translation_fields);
			

			return $bookingpress_all_language_translation_fields;
		}		

		function bookingpress_modified_language_translate_fields_func($bookingpress_all_language_translation_fields){

			$bookingpress_coupon_language_translation_fields = array(                
				'coupon_code_title' => array('field_type'=>'text','field_label'=>__('Coupon heading title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),               
				'coupon_code_field_title' => array('field_type'=>'text','field_label'=>__('Enter coupon placeholder', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),    
				'coupon_apply_button_label' => array('field_type'=>'text','field_label'=>__('Coupon apply button label', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),      
				'couon_applied_title' => array('field_type'=>'text','field_label'=>__('Coupon applied title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),    
			);  
			$bookingpress_all_language_translation_fields['customized_form_summary_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_summary_step_labels'], $bookingpress_coupon_language_translation_fields);						
			$bookingpress_coupon_message_translation_fields = array(                
				'coupon_code_not_valid' => array('field_type'=>'text','field_label'=>__('Coupon code is not valid', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_not_allowed' => array('field_type'=>'text','field_label'=>__('Coupon code not allowed', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_expired' => array('field_type'=>'text','field_label'=>__('Coupon code expired', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_not_valid_for_customer' => array('field_type'=>'text','field_label'=>__('Coupon code is not allowed for this customer', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_not_valid_for_service' => array('field_type'=>'text','field_label'=>__('Coupon code is not valid for selected service', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_no_longer_available' => array('field_type'=>'text','field_label'=>__('Coupon code no longer available', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
				'coupon_code_does_not_exist' => array('field_type'=>'text','field_label'=>__('Coupon code does not exist', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),
			);						
			$bookingpress_all_language_translation_fields['message_setting'] = array_merge($bookingpress_all_language_translation_fields['message_setting'], $bookingpress_coupon_message_translation_fields);
			

			return $bookingpress_all_language_translation_fields;
		}

		function bookingpress_add_appointment_model_reset_func() {
            ?>
				if(typeof vm2.bpa_coupon_apply_disabled !== 'undefined'){
					vm2.bpa_coupon_apply_disabled = 0;
				}
				if(typeof vm2.appointment_formdata.applied_coupon_code !== 'undefined'){
					vm2.appointment_formdata.applied_coupon_code = '';    
				}
				if(typeof vm2.appointment_formdata.applied_coupon_details !== 'undefined'){
					vm2.appointment_formdata.applied_coupon_details = [];    
				}
				if(typeof vm2.appointment_formdata.coupon_discounted_amount_with_currency !== 'undefined'){
					vm2.appointment_formdata.coupon_discounted_amount_with_currency = '';    
				}
				if(typeof vm2.coupon_applied_status !== 'undefined'){					
					vm2.coupon_applied_status = '';    
				}
            <?php
        }

		/**
		 * Function to reset coupon discount amount when selecting service
		 *
		 * @param  mixed $bookingpress_before_selecting_booking_service_data
		 * @return void
		 */
		function bookingpress_reset_coupon_discount_on_service_selection( $bookingpress_before_selecting_booking_service_data ){

			$bookingpress_before_selecting_booking_service_data .= 'vm.appointment_step_form_data.coupon_discount_amount = 0;';

			return $bookingpress_before_selecting_booking_service_data;
		}
		
		/**
		 * Function for modify setting module data variables
		 *
		 * @param  mixed $bookingpress_dynamic_setting_data_fields
		 * @return void
		 */
		function bookingpress_add_setting_dynamic_data_fields_func( $bookingpress_dynamic_setting_data_fields ) {
			
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_not_valid'] = '';
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_not_allowed'] = '';
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_expired'] = '';
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_not_valid_for_service'] = '';
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_no_longer_available'] = '';
			$bookingpress_dynamic_setting_data_fields['message_setting_form']['coupon_code_does_not_exist'] = '';

			return $bookingpress_dynamic_setting_data_fields;

		}
		
		/**
		 * Function for add data variables at frontend booking form
		 *
		 * @param  mixed $bookingpress_front_vue_data_fields
		 * @return void
		 */
		function bookingpress_frontend_apointment_form_add_dynamic_data_func($bookingpress_front_vue_data_fields){
			global $BookingPress;
			$coupon_code_title = $BookingPress->bookingpress_get_customize_settings('coupon_code_title', 'booking_form');
			$coupon_code_field_title = $BookingPress->bookingpress_get_customize_settings('coupon_code_field_title', 'booking_form');
			$coupon_apply_button_label = $BookingPress->bookingpress_get_customize_settings('coupon_apply_button_label', 'booking_form');
			$couon_applied_title = $BookingPress->bookingpress_get_customize_settings('couon_applied_title', 'booking_form');
			$bookingpress_front_vue_data_fields['coupon_code_title'] = !empty($coupon_code_title) ? stripslashes_deep($coupon_code_title) : '';			
			$bookingpress_front_vue_data_fields['coupon_code_field_title'] = !empty($coupon_code_field_title) ? stripslashes_deep($coupon_code_field_title) : '';
			$bookingpress_front_vue_data_fields['coupon_apply_button_label'] = !empty($coupon_apply_button_label) ? stripslashes_deep($coupon_apply_button_label) : '';
			$bookingpress_front_vue_data_fields['couon_applied_title'] = !empty($couon_applied_title) ? stripslashes_deep($couon_applied_title) : '';
			return $bookingpress_front_vue_data_fields;
		}
		
		/**
		 * Function for add customize data variables
		 *
		 * @param  mixed $bookingpress_customize_vue_data_fields
		 * @return void
		 */
		function bookingpress_customize_add_dynamic_data_fields_func($bookingpress_customize_vue_data_fields) {
            $bookingpress_customize_vue_data_fields['tab_container_data']['cart_title'] = '';
            $bookingpress_customize_vue_data_fields['summary_container_data']['coupon_code_title'] = '';
            $bookingpress_customize_vue_data_fields['summary_container_data']['coupon_code_field_title'] = '';
            $bookingpress_customize_vue_data_fields['summary_container_data']['coupon_apply_button_label'] = '';
            $bookingpress_customize_vue_data_fields['summary_container_data']['couon_applied_title'] = '';
			return $bookingpress_customize_vue_data_fields;
		}
		
		/**
		 * Function for get booking form customize data variables
		 *
		 * @param  mixed $booking_form_settings
		 * @return void
		 */
		function bookingpress_get_booking_form_customize_data_filter_func($booking_form_settings){
            $booking_form_settings['summary_container_data']['coupon_code_title'] = __('Have a coupon code ?','bookingpress-appointment-booking');
            $booking_form_settings['summary_container_data']['coupon_code_field_title'] = __('Enter your coupon code', 'bookingpress-appointment-booking');
            $booking_form_settings['summary_container_data']['coupon_apply_button_label'] = __('Apply', 'bookingpress-appointment-booking');
            $booking_form_settings['summary_container_data']['couon_applied_title'] = __('Coupon Applied', 'bookingpress-appointment-booking');
			return $booking_form_settings;
		}
		
		/**
		 * Function for apply coupon code at backend
		 *
		 * @return void
		 */
		function bookingpress_apply_coupon_code_backend_func( $return = false ){
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress, $bookingpress_deposit_payment;
			$response = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'apply_coupon_code_backend', true, 'bpa_wp_nonce' );

			if(isset($_REQUEST['appointment_formdata']) && !empty($_REQUEST['appointment_formdata']) && !is_array($_REQUEST['appointment_formdata'])){
				$_REQUEST['appointment_formdata'] = json_decode(stripslashes_deep($_REQUEST['appointment_formdata']),true);  // phpcs:ignore
				$_POST['appointment_formdata'] = $_REQUEST['appointment_formdata']; // phpcs:ignore
			}


            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;
				if( true === $return ){
					return $response;
				}
                wp_send_json( $response );
                die;
            }

			$response['final_payable_amount'] = 0;
			$response['discounted_amount']    = 0;
			$bookingpress_coupon_code      = ! empty( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : ''; // phpcs:ignore
			$bookingpress_selected_service = ! empty( $_POST['selected_service'] ) ? intval( $_POST['selected_service'] ) : 0; // phpcs:ignore
			$bookingpress_payable_amount = !empty($_POST['payable_amount']) ? floatval($_POST['payable_amount']) : 0; // phpcs:ignore
			$bookingpress_payable_amount = !empty($_POST['payable_amount']) ? floatval($_POST['payable_amount']) : 0; // phpcs:ignore
			$is_tax_enable = (is_plugin_active('bookingpress-tax/bookingpress-tax.php'))?1:0;
			
			$bookingpress_appointment_formdata = !empty( $_POST['appointment_formdata'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['appointment_formdata'] ) : array(); 	// phpcs:ignore 
			

			if(!empty($_POST['appointment_formdata']['clubbed_services'])){
				$bookingpress_selected_service = $_POST['appointment_formdata']['clubbed_services']; // array of selected services
			}

			$coupon_apply_single_app_check = apply_filters( 'bookingpress_coupon_apply_on_single_appointment_check', false, $bookingpress_appointment_formdata );

			
			if( false == $coupon_apply_single_app_check){
				
				$bookingpress_applied_coupon_response = $this->bookingpress_apply_coupon_code( $bookingpress_coupon_code, $bookingpress_selected_service );			
				
				$response['variant']     = $bookingpress_applied_coupon_response['coupon_status'];
				$response['title']       = ( $bookingpress_applied_coupon_response['coupon_status'] == 'error' ) ? __( 'Error', 'bookingpress-appointment-booking' ) : __( 'Success', 'bookingpress-appointment-booking' );
				$response['msg']         = $bookingpress_applied_coupon_response['msg'];
				$response['coupon_data'] = $bookingpress_applied_coupon_response['coupon_data'];

				if ( is_array( $bookingpress_applied_coupon_response ) && ! empty( $bookingpress_applied_coupon_response )  && $bookingpress_applied_coupon_response['coupon_status'] == 'success') {

					$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $bookingpress_coupon_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm

					$coupon_discount_type   = ! empty( $coupon_data['bookingpress_coupon_discount_type'] ) ? $coupon_data['bookingpress_coupon_discount_type'] : '';
					$db_coupon_discount  = ! empty( $coupon_data['bookingpress_coupon_discount'] ) ? $coupon_data['bookingpress_coupon_discount'] : 0;

					$bookingpress_max_coupon_discount = 0;
					if($coupon_discount_type == "Fixed"){
						$bookingpress_max_coupon_discount = ! empty( $coupon_data['bookingpress_coupon_discount'] ) ? floatval( $coupon_data['bookingpress_coupon_discount'] ) : 0;					
					}

					$bookingpress_service_base_price = 0;
					if($is_tax_enable) {
						$bookingpress_price_display_setting = !empty($bookingpress_appointment_formdata['tax_price_display_options']) ? $bookingpress_appointment_formdata['tax_price_display_options'] : 'exclude_taxes';
						$tax_percentage = !empty($bookingpress_appointment_formdata['tax_percentage']) ? $bookingpress_appointment_formdata['tax_percentage'] : 0;
						if($tax_percentage != "") {
							$bookingpress_tax_amount = !empty($bookingpress_appointment_formdata['tax']) ? $bookingpress_appointment_formdata['tax']: 0;
							$bookingpress_service_base_price = $bookingpress_payable_amount = $bookingpress_payable_amount - $bookingpress_tax_amount;	
							
							if($coupon_discount_type == "Percentage" && $db_coupon_discount == 100){
								$bookingpress_max_coupon_discount = $bookingpress_max_coupon_discount + $bookingpress_service_base_price;
							}
						}
					}
					$bookingpress_after_discount_amounts = $this->bookingpress_calculate_bookingpress_coupon_amount( $bookingpress_coupon_code, $bookingpress_payable_amount );
					if( $bookingpress_payable_amount >= $bookingpress_after_discount_amounts['discounted_amount']) {						
						$final_payable_amount = $response['final_payable_amount'] = ! empty( $bookingpress_after_discount_amounts['final_payable_amount'] ) ? floatval( $bookingpress_after_discount_amounts['final_payable_amount'] ) : 0;
						$bookingpress_discounted_amount = floatval( $bookingpress_after_discount_amounts['discounted_amount']);
						$response['discounted_amount'] = floatval($bookingpress_discounted_amount);

						$response['coupon_data']['bookingpress_coupon_discount_amount'] =$response['discounted_amount']; 
						$response['coupon_data']['coupon_discount_amount'] =$response['discounted_amount'];

						$response['discounted_amount_with_currency'] = !empty($bookingpress_after_discount_amounts['discounted_amount']) ? $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_discounted_amount) : 0;

						$coupon = 0;
						if($is_tax_enable) {
							if($bookingpress_price_display_setting == 'include_taxes' && ($coupon_discount_type == "Fixed" || ($coupon_discount_type == "Percentage" && $db_coupon_discount == 100))) {
								$coupon = ( $bookingpress_max_coupon_discount > $bookingpress_service_base_price ) ? $bookingpress_service_base_price : $bookingpress_max_coupon_discount;
								$discounted = $bookingpress_service_base_price - $coupon;
							}
						}

						$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
						$bookingpress_decimal_points = intval($bookingpress_decimal_points);

						if( isset($discounted) && $discounted == 0 ){
							$temp_subtotal = number_format(floatval($bookingpress_service_base_price),$bookingpress_decimal_points );
						} else {
							$temp_subtotal = 0;
						}					 
						$response['subtotal_temp'] = $temp_subtotal;
						$response['subtotal_temp_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $temp_subtotal );

						if ( ! empty( $tax_percentage ) && $is_tax_enable ) {
							if($bookingpress_price_display_setting == "include_taxes"){
								$bookingpress_tax_amount    = $final_payable_amount * ( $tax_percentage / 100 );
								$final_payable_amount = $final_payable_amount + $bookingpress_tax_amount;
								$response['tax_included_amount'] = number_format($final_payable_amount,2);
							}else{
								$bookingpress_tax_amount    = $final_payable_amount * ( $tax_percentage / 100 );
								$final_payable_amount = $final_payable_amount + $bookingpress_tax_amount;
								$response['tax_excluded_amount'] = number_format($final_payable_amount,2);
							}
							$response['tax']        = $bookingpress_tax_amount;
							$bookingpress_tax_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_tax_amount );
							$response['tax_with_currency'] = is_float( $bookingpress_tax_amount ) ? number_format($bookingpress_tax_amount,2) : $bookingpress_tax_amount;
							$response['is_tax_calculated'] = 1;
							$response['tax_included_amount'] = number_format($final_payable_amount,2);
							$response['final_payable_amount'] = number_format($final_payable_amount,2);
						}
					} else {
						$response['variant'] = 'error';
						$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
						$response['msg'] = esc_html__( 'Coupon code not applied on this service', 'bookingpress-appointment-booking' );
						if($bookingpress_after_discount_amounts['discounted_amount'] >= $bookingpress_payable_amount ) {
							$this->bookingpress_decrease_coupon_usage_manual($bookingpress_coupon_code);									
						}
					}
				}
			} else {
				$response = apply_filters('bookingpress_coupon_apply_on_appointment_outside', $response, $_POST);
			}

			if( true === $return ){
				return $response;
			}
			echo wp_json_encode( $response );
			exit();
		}

		
		/**
		 * bookingpress_decrease_coupon_usage_manual
		 *
		 * @return void
		 */
		function bookingpress_decrease_coupon_usage_manual($bookingpress_coupon_code){
			global $wpdb, $tbl_bookingpress_settings, $BookingPress;
			if(!empty($bookingpress_coupon_code)){
				$bookingpress_fetch_settings_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tbl_bookingpress_settings}` WHERE setting_type = %s AND setting_name = %s", 'coupon_usage_settings', 'bpa_coupon_usage_'.$bookingpress_coupon_code  ), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_settings is table name defined globally. False Positive alarm
				$coupon_usage_value = 0;
				if(!empty($bookingpress_fetch_settings_details)) {
					foreach( $bookingpress_fetch_settings_details as $setting_key => $setting_details ){
						$coupon_usage_value = isset($setting_details['setting_value']) ? $setting_details['setting_value'] : 0;
					}
				}
				if($coupon_usage_value > 0){
					$setting_name = 'bpa_coupon_usage_' . $bookingpress_coupon_code; 
					$BookingPress->bookingpress_update_settings( $setting_name, 'coupon_usage_settings', ( $coupon_usage_value - 1 ) );
				}	
			}				
		}
		
		
		/**
		 * Common function for apply coupon code at frontend
		 *
		 * @return void
		 */
		function bookingpress_apply_coupon_code_func($return_data = false) {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress, $bookingpress_deposit_payment;
			$wpnonce               = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$bpa_verify_nonce_flag = wp_verify_nonce( $wpnonce, 'bpa_wp_nonce' );
			$response              = array();
			if ( ! $bpa_verify_nonce_flag ) {
				$response                = array();
				$response['variant']     = 'error';
				$response['title']       = esc_html__( 'Error', 'bookingpress-appointment-booking' );
				$response['msg']         = esc_html__( 'Sorry, Your request can not be processed due to security reason.', 'bookingpress-appointment-booking' );
				$response['coupon_data'] = array();
				if($return_data){
					return $response;
				}
				echo wp_json_encode( $response );
				die();
			}
			$response                         = array();
			$response['variant']              = 'error';
			$response['title']                = __( 'Error', 'bookingpress-appointment-booking' );
			$response['msg']                  = __( 'Something went wrong..', 'bookingpress-appointment-booking' );
			$response['coupon_data']          = array();
			$response['final_payable_amount'] = 0;
			$response['discounted_amount']    = 0;
			if( !empty( $_POST['appointment_details'] ) && !is_array( $_POST['appointment_details'] ) ){
				$_POST['appointment_details'] = json_decode( stripslashes_deep( $_POST['appointment_details'] ), true ); //phpcs:ignore
				$_POST['appointment_details'] =  !empty($_POST['appointment_details']) ? array_map(array($this,'bookingpress_boolean_type_cast'), $_POST['appointment_details'] ) : array(); // phpcs:ignore       
			}
			$bookingpress_coupon_code      = ! empty( $_POST['appointment_details']['coupon_code'] ) ? sanitize_text_field( $_POST['appointment_details']['coupon_code'] ) : '';
			$bookingpress_selected_service = ! empty( $_POST['appointment_details']['selected_service'] ) ? intval( $_POST['appointment_details']['selected_service'] ) : 0;
			$bookingpress_payable_amount   = ! empty( $_POST['appointment_details']['service_price_without_currency'] ) ? floatval( $_POST['appointment_details']['service_price_without_currency'] ) : 0; 

			//26 April 2023 changes
			$bookingpress_tax_amount_without_currency   = ! empty( $_POST['appointment_details']['tax_amount_without_currency'] ) ? floatval( $_POST['appointment_details']['tax_amount_without_currency'] ) : 0; 	
			//26 April 2023 changes
			
			$bookingpress_appointment_details = !empty( $_POST['appointment_details'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['appointment_details'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason: $_POST['appointment_details'] has already been sanitized.

			$skip_checking_single_appointments = apply_filters( 'bookingpress_skip_coupon_apply_on_single_appointment', false, $bookingpress_appointment_details );

			if( false == $skip_checking_single_appointments && empty($bookingpress_appointment_details['cart_items'])){
				/*if($bookingpress_deposit_payment->bookingpress_check_deposit_payment_module_activation() && !empty($bookingpress_appointment_details['bookingpress_deposit_amt_without_currency'])){
					$bookingpress_payable_amount = $bookingpress_payable_amount + $bookingpress_appointment_details['bookingpress_deposit_amt_without_currency'];
				}*/

				$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $bookingpress_coupon_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm

				$coupon_discount_type   = ! empty( $coupon_data['bookingpress_coupon_discount_type'] ) ? $coupon_data['bookingpress_coupon_discount_type'] : '';
				$db_coupon_discount  = ! empty( $coupon_data['bookingpress_coupon_discount'] ) ? $coupon_data['bookingpress_coupon_discount'] : 0;

				$bookingpress_max_coupon_discount = 0;
				if($coupon_discount_type == "Fixed"){
					$bookingpress_max_coupon_discount = ! empty( $coupon_data['bookingpress_coupon_discount'] ) ? floatval( $coupon_data['bookingpress_coupon_discount'] ) : 0;					
				}
				
				$bookingpress_payable_amount = apply_filters( 'bookingpress_modified_coupon_total_payable_amount',$bookingpress_payable_amount, $bookingpress_appointment_details);
				
				$bookingpress_applied_coupon_response = $this->bookingpress_apply_coupon_code( $bookingpress_coupon_code, $bookingpress_selected_service );
				
				$response['variant']     = $bookingpress_applied_coupon_response['coupon_status'];
				$response['title']       = ( $bookingpress_applied_coupon_response['coupon_status'] == 'error' ) ? __( 'Error', 'bookingpress-appointment-booking' ) : __( 'Success', 'bookingpress-appointment-booking' );
				$response['msg']         = $bookingpress_applied_coupon_response['msg'];
				$bookingpress_tax_amount = 0;
				if ( is_array( $bookingpress_applied_coupon_response ) && ! empty( $bookingpress_applied_coupon_response ) && $bookingpress_applied_coupon_response['coupon_status'] == 'success') {

					$bookingpress_price_display_setting = !empty($bookingpress_appointment_details['tax_price_display_options']) ? $bookingpress_appointment_details['tax_price_display_options'] : 'exclude_taxes';
					$tax_percentage = !empty($bookingpress_appointment_details['tax_percentage']) ? $bookingpress_appointment_details['tax_percentage'] : 0;

					$bookingpress_service_base_price = $bookingpress_payable_amount;
					if($bookingpress_price_display_setting == 'include_taxes') {
						$bookingpress_tax_amount = ($bookingpress_payable_amount * $tax_percentage) / (100+$tax_percentage);
						$bookingpress_service_base_price = $bookingpress_payable_amount = $bookingpress_payable_amount - $bookingpress_tax_amount;

						if($coupon_discount_type == "Percentage" && $db_coupon_discount == 100){
							$bookingpress_max_coupon_discount = $bookingpress_max_coupon_discount + $bookingpress_service_base_price;
						}
					}
					$bookingpress_after_discount_amounts = $this->bookingpress_calculate_bookingpress_coupon_amount( $bookingpress_coupon_code, $bookingpress_payable_amount );

					$coupon = 0;
					if($bookingpress_price_display_setting == 'include_taxes' && ($coupon_discount_type == "Fixed" || ($coupon_discount_type == "Percentage" && $db_coupon_discount == 100))) {
						$coupon = ( $bookingpress_max_coupon_discount > $bookingpress_service_base_price ) ? $bookingpress_service_base_price : $bookingpress_max_coupon_discount;
						$discounted = $bookingpress_service_base_price - $coupon;
					}

					$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
                    $bookingpress_decimal_points = intval($bookingpress_decimal_points);					
					if( isset($discounted) && $discounted == 0 ){
						$temp_subtotal = number_format(floatval($bookingpress_service_base_price),$bookingpress_decimal_points );
					} else {
						$temp_subtotal = 0;
					}					 
					$response['bookingpress_temp_subtotal'] = $temp_subtotal;
					$response['bookingpress_temp_subtotal_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $temp_subtotal );

					if($bookingpress_after_discount_amounts['discounted_amount'] > $bookingpress_payable_amount ) {
						$response['variant']              = 'error';
						$response['title']                = esc_html__( 'Error', 'bookingpress-appointment-booking' );
						$response['msg']                  = esc_html__( 'Coupon code not applied on this service', 'bookingpress-appointment-booking' );
						$this->bookingpress_decrease_coupon_usage_manual($bookingpress_coupon_code);
					} else {
						$final_payable_amount = $response['final_payable_amount'] = ! empty( $bookingpress_after_discount_amounts['final_payable_amount'] ) ? floatval( $bookingpress_after_discount_amounts['final_payable_amount'] ) : 0;

						$bookingpress_payable_amount = number_format($bookingpress_payable_amount,2, '.', '');

						$discounted_amount = $response['discounted_amount']    = ! empty( $bookingpress_after_discount_amounts['discounted_amount'] ) ?
						$BookingPress->bookingpress_price_formatter_with_currency_symbol( floatval( $bookingpress_after_discount_amounts['discounted_amount'] ) ) : 0;
						/*Recalculation of the Tax */
						if ( ! empty( $tax_percentage ) ) {
							if($bookingpress_price_display_setting == "include_taxes"){
								$bookingpress_tax_amount    = $final_payable_amount * ( $tax_percentage / 100 );
								$final_payable_amount = $final_payable_amount + $bookingpress_tax_amount;
								$response['tax_included_amount'] = number_format($final_payable_amount,2, '.', '');
							}else{
								$bookingpress_tax_amount    = $final_payable_amount * ( $tax_percentage / 100 );
								$final_payable_amount = $final_payable_amount + $bookingpress_tax_amount;
								$response['tax_excluded_amount'] = number_format($final_payable_amount,2, '.', '');
							}

							$bookingpress_tax_amount = number_format($bookingpress_tax_amount,2, '.', '');
							$bookingpress_tex_without_currency  = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_tax_amount, '', 0, true );
							$response['tax_amount_without_currency'] = floatval( $bookingpress_tex_without_currency );
							$bookingpress_tax_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_tax_amount );
							$response['tax_amount']        = $bookingpress_tax_amount;
							$response['is_tax_calculated'] = 1;
							$response['tax_included_amount'] = number_format($final_payable_amount,2,'.', '');
							$response['final_payable_amount'] = number_format($final_payable_amount,2, '.', '');
						}
						/*Recalculation of the Tax */
						$response['coupon_discount_amount'] = $bookingpress_after_discount_amounts['discounted_amount'];
						$response['coupon_discount_amount_with_currecny'] = $response['discounted_amount'];						
						$response['total_payable_amount_with_currency'] = ! empty( $response['final_payable_amount'] ) ?
						$BookingPress->bookingpress_price_formatter_with_currency_symbol( floatval( $response['final_payable_amount'] ) ) : $BookingPress->bookingpress_price_formatter_with_currency_symbol( 0 );
						$response['total_payable_amount'] = $response['final_payable_amount'];
						$response['coupon_data'] = $bookingpress_applied_coupon_response['coupon_data'];

					}				
				}
			}else{
				$response = apply_filters('bookingpress_check_coupon_validity_from_outside', $response, $bookingpress_appointment_details);
			}
			if($return_data){
				return $response;
			}
			echo wp_json_encode( $response );
			exit();
		}
		
		/**
		 * Function for update coupon usage counter
		 *
		 * @param  mixed $coupon_id
		 * @return void
		 */
		function bookingpress_update_coupon_usage_counter( $coupon_id ) {
			global $wpdb, $tbl_bookingpress_coupons;
			$coupon_details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_id = %d", $coupon_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
			if ( ! empty( $coupon_details ) ) {
				$bookingpress_coupon_usage_counter = ! empty( $coupon_details['bookingpress_coupon_used'] ) ? intval( $coupon_details['bookingpress_coupon_used'] ) : 0;
				$bookingpress_coupon_usage_counter = $bookingpress_coupon_usage_counter + 1;
				$wpdb->update( $tbl_bookingpress_coupons, array( 'bookingpress_coupon_used' => $bookingpress_coupon_usage_counter ), array( 'bookingpress_coupon_id' => $coupon_id ) );
			}
		}
		
		/**
		 * Common function for calculate coupon amount as per passed coupon code and payable amount
		 *
		 * @param  mixed $coupon_code
		 * @param  mixed $final_payable_amount
		 * @return void
		 */
		function bookingpress_calculate_bookingpress_coupon_amount( $coupon_code, $final_payable_amount ) {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress;
			$coupon_code       = trim( $coupon_code );
			$return_data       = array();
			$discounted_amount = 0;
			if ( ! empty( $coupon_code ) && ! empty( $final_payable_amount ) && empty( $coupon_amount ) && empty( $coupon_type ) ) {
				$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $coupon_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
				if ( ! empty( $coupon_data ) ) {
					$coupon_discount_amount = ! empty( $coupon_data['bookingpress_coupon_discount'] ) ? floatval( $coupon_data['bookingpress_coupon_discount'] ) : 0;
					$coupon_discount_type   = ! empty( $coupon_data['bookingpress_coupon_discount_type'] ) ? $coupon_data['bookingpress_coupon_discount_type'] : '';

					if ( ! empty( $coupon_discount_type ) ) {
						if ( $coupon_discount_type == 'Percentage' ) {
							$discounted_amount    = $final_payable_amount * ( $coupon_discount_amount / 100 );
						} else {							
							$discounted_amount    = $coupon_discount_amount;

							if($coupon_discount_amount > $final_payable_amount){
								$discounted_amount    = $final_payable_amount;
							}

						}
						if($discounted_amount <= $final_payable_amount) {
							$final_payable_amount = $final_payable_amount - $discounted_amount;
						}
					}
				}
			}
			$return_data['discounted_amount']    = $discounted_amount;
			$return_data['final_payable_amount'] = $final_payable_amount;
			return $return_data;
		}

		function bookingpress_remove_coupon_code_func( $return_data = false ){

			global $wpdb, $tbl_bookingpress_coupons, $BookingPress;

			$wpnonce               = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$bpa_verify_nonce_flag = wp_verify_nonce( $wpnonce, 'bpa_wp_nonce' );
			$response              = array();
			if ( ! $bpa_verify_nonce_flag ) {
				$response                = array();
				$response['variant']     = 'error';
				$response['title']       = esc_html__( 'Error', 'bookingpress-appointment-booking' );
				$response['msg']         = esc_html__( 'Sorry, Your request can not be processed due to security reason.', 'bookingpress-appointment-booking' );
				$response['coupon_data'] = array();
				if($return_data){
					return $response;
				}
				echo wp_json_encode( $response );
				die();
			}

			$bookingpress_coupon_code      = ! empty( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
			
			$coupon_exist = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(bookingpress_coupon_id) as total FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $bookingpress_coupon_code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
			
			if ( $coupon_exist > 0 ) {
				$this->bookingpress_decrease_coupon_usage( $bookingpress_coupon_code );
			}

			if( true == $return_data ){
				return ['variant' => 'success'];
			}

			die;

		}
		
		/**
		 * Function for apply coupon code at frontend
		 *
		 * @param  mixed $coupon_code
		 * @param  mixed $selected_service
		 * @return void
		 */
		function bookingpress_apply_coupon_code( $coupon_code, $selected_service = 0 ) {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress, $tbl_bookingpress_customers;

			$coupon_code = trim( $coupon_code );
			$coupon_code_not_valid = $BookingPress->bookingpress_get_settings('coupon_code_not_valid','message_setting');
			$coupon_code_not_allowed = $BookingPress->bookingpress_get_settings('coupon_code_not_allowed','message_setting');
			$coupon_code_expired = $BookingPress->bookingpress_get_settings('coupon_code_expired','message_setting');
			$coupon_code_not_valid_for_service = $BookingPress->bookingpress_get_settings('coupon_code_not_valid_for_service','message_setting');
			$coupon_code_not_valid_for_customer = $BookingPress->bookingpress_get_settings('coupon_code_not_valid_for_customer','message_setting');
			$coupon_code_no_longer_available = $BookingPress->bookingpress_get_settings('coupon_code_no_longer_available','message_setting');
			$coupon_code_does_not_exist = $BookingPress->bookingpress_get_settings('coupon_code_does_not_exist','message_setting');

			$coupon_code_not_valid = !empty($coupon_code_not_valid) ? stripslashes_deep($coupon_code_not_valid) : __( 'Coupon code is not valid', 'bookingpress-appointment-booking' );
			$coupon_code_not_allowed = !empty($coupon_code_not_allowed) ? stripslashes_deep($coupon_code_not_allowed) : __( 'Coupon code not allowed', 'bookingpress-appointment-booking' );
			$coupon_code_expired = !empty($coupon_code_expired) ? stripslashes_deep($coupon_code_expired) : __( 'Coupon code expired', 'bookingpress-appointment-booking' );
			$coupon_code_not_valid_for_service = !empty($coupon_code_not_valid_for_service) ? stripslashes_deep($coupon_code_not_valid_for_service) : __( 'Coupon code is not valid for selected service', 'bookingpress-appointment-booking' );

			$coupon_code_not_valid_for_customer = !empty($coupon_code_not_valid_for_customer) ? stripslashes_deep($coupon_code_not_valid_for_customer) : __( 'Coupon code is not allowed for this customer', 'bookingpress-appointment-booking' );

			$coupon_code_no_longer_available = !empty($coupon_code_no_longer_available) ? stripslashes_deep($coupon_code_no_longer_available) : __( 'Coupon code no longer available', 'bookingpress-appointment-booking' );
			$coupon_code_does_not_exist = !empty($coupon_code_does_not_exist) ? stripslashes_deep($coupon_code_does_not_exist) : __( 'Coupon code does not exist', 'bookingpress-appointment-booking' );

			$response                  = array();
			$response['coupon_status'] = 'error';
			$response['msg']           = $coupon_code_not_valid;
			$response['coupon_data']   = array();

			if ( $this->bookingpress_check_coupon_module_activation() && ! empty( $coupon_code ) ) {
				$coupon_exist = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(bookingpress_coupon_id) as total FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $coupon_code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
				if ( $coupon_exist > 0 ) {
					$coupon_data                   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $coupon_code ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
					$bookingpress_applied_date     = date( 'Y-m-d', current_time( 'timestamp' ) );
					$bookingpress_selected_service = $selected_service;
					$coupon_start_date = ! empty( $coupon_data['bookingpress_coupon_start_date'] ) ? date( 'Y-m-d', strtotime( $coupon_data['bookingpress_coupon_start_date'] ) ) : date( 'Y-m-d', current_time( 'timestamp' ) );
					$coupon_end_date   = ! empty( $coupon_data['bookingpress_coupon_end_date'] ) ? date( 'Y-m-d', strtotime( $coupon_data['bookingpress_coupon_end_date'] ) ) : date( 'Y-m-d', current_time( 'timestamp' ) );
					$coupon_services   = ! empty( $coupon_data['bookingpress_coupon_services'] ) ? explode( ',', $coupon_data['bookingpress_coupon_services'] ) : array();
					$bookingpress_coupon_allowed_uses = ! empty( $coupon_data['bookingpress_coupon_allowed_uses'] ) ? intval( $coupon_data['bookingpress_coupon_allowed_uses'] ) : 0;
					$bookingpress_coupon_used         = ! empty( $coupon_data['bookingpress_coupon_used'] ) ? intval( $coupon_data['bookingpress_coupon_used'] ) : 0;
					$increase_coupon = false;

					$current_user_id = get_current_user_id();
					$get_customer_id = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_customer_id FROM {$tbl_bookingpress_customers} WHERE bookingpress_wpuser_id = %d AND bookingpress_user_status = %d AND bookingpress_user_type = %d ORDER BY bookingpress_customer_id DESC", $current_user_id, 1, 2 ), ARRAY_A); // phpcs:ignore
					$customer_id = 0;
					$coupon_customers = ! empty( $coupon_data['bookingpress_coupon_customers'] ) ? explode( ',', $coupon_data['bookingpress_coupon_customers'] ) : array();
					if( !empty( $get_customer_id ) ){
						$customer_id = isset($get_customer_id['bookingpress_customer_id']) ? $get_customer_id['bookingpress_customer_id'] : 0;				
					}
					
					if( !empty( $_REQUEST['action'] ) && ('bookingpress_apply_coupon_code' == $_REQUEST['action'] || 'bookingpress_apply_coupon_code_backend' == $_REQUEST['action'] ) ){
						$bookingpress_locked_coupon		  = $this->bookingpress_get_coupon_locked_usage( $coupon_code );
						$bookingpress_coupon_used		 += $bookingpress_locked_coupon;
						$increase_coupon = true;
					} 

					if(!empty( $_REQUEST['action'] ) && ('bookingpress_apply_coupon_code_backend' == $_REQUEST['action'])){
						$customer_id = isset($_POST['appointment_formdata']['appointment_selected_customer']) ? $_POST['appointment_formdata']['appointment_selected_customer'] : 0;
					}

					if ( empty( $coupon_data['bookingpress_coupon_status'] ) || ( $coupon_data['bookingpress_coupon_status'] == 0 ) ) {
						$response['msg'] = $coupon_code_not_allowed;
					} else if  ( $coupon_data['bookingpress_coupon_period_type'] == 'date_range' && ( $bookingpress_applied_date < $coupon_start_date || $bookingpress_applied_date > $coupon_end_date ) ) {
						$response['msg'] = $coupon_code_expired;						
					} else if ( is_array( $coupon_services ) && ! empty( $coupon_services ) && ! empty( $bookingpress_selected_service ) && ! in_array( $bookingpress_selected_service, $coupon_services ) ) {
						$response['msg'] = $coupon_code_not_valid_for_service;						
					} else if ( is_array( $coupon_customers ) && ! empty( $coupon_customers ) && ! in_array( $customer_id, $coupon_customers ) ) {
						$response['msg'] = $coupon_code_not_valid_for_customer;						
					} else if ( $bookingpress_coupon_allowed_uses > 0 && $bookingpress_coupon_used >= $bookingpress_coupon_allowed_uses ) {
						$response['msg'] = $coupon_code_no_longer_available;						
					} else {

						static $already_incremented_coupons = [];

						if( true == $increase_coupon && !isset($already_incremented_coupons[$coupon_code])){	
							$this->bookingpress_increase_coupon_usage( $coupon_code );
							$already_incremented_coupons[$coupon_code] = true;
						}

						$response['coupon_status'] = 'success';
						$response['msg']           = __( 'Coupon applied successfully', 'bookingpress-appointment-booking' );
						$response['coupon_data']   = $coupon_data;
					}

				} else {
					$response['msg'] = $coupon_code_does_not_exist;
				}
			}

			return $response;
		}

		function bookingpress_increase_coupon_usage( $coupon_code ){

			global $wpdb, $BookingPress;

			$coupon_usage = $this->bookingpress_get_coupon_locked_usage( $coupon_code );

			$setting_name = 'bpa_coupon_usage_' . $coupon_code; 
			$BookingPress->bookingpress_update_settings( $setting_name, 'coupon_usage_settings', ( $coupon_usage + 1 ) );

		}

		function bookingpress_decrease_coupon_usage( $coupon_code ){
			global $wpdb, $BookingPress;

			$coupon_usage = $this->bookingpress_get_coupon_locked_usage( $coupon_code );

			if( 1 > $coupon_usage ){
				return;
			}

			$setting_name = 'bpa_coupon_usage_' . $coupon_code; 
			$BookingPress->bookingpress_update_settings( $setting_name, 'coupon_usage_settings', ( $coupon_usage - 1 ) );

		}

		function bookingpress_get_coupon_locked_usage( $coupon_code ){

			global $wpdb, $tbl_bookingpress_settings, $BookingPress;

			$setting_name = 'bpa_coupon_usage_' . $coupon_code;

			//$coupon_usage = $BookingPress->bookingpress_get_settings( $setting_name, 'coupon_usage_settings' );

			$bpa_fetch_all_coupon_usgae = $wpdb->get_results( $wpdb->prepare( "SELECT setting_name,setting_value,setting_type FROM {$tbl_bookingpress_settings} WHERE setting_type = %s AND setting_name = %s", 'coupon_usage_settings', $setting_name ), ARRAY_A ); 

			$coupon_usage = 0;

			if(!empty($bpa_fetch_all_coupon_usgae)){
				foreach($bpa_fetch_all_coupon_usgae as $k => $v){
					$coupon_usage = $setting_value = $v['setting_value'];
				}
			}

			if( empty( $coupon_usage ) ){
				return 0;
			} else {
				return $coupon_usage;
			}

		}

		function bookingpress_remove_locked_coupon_usage( $appointment_id, $entry_id, $payment_gateway_data ){
			global $wpdb, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_settings;

			$get_coupon_details = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_coupon_details FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm

			if( !empty( $get_coupon_details ) ){
				$coupon_data = json_decode( $get_coupon_details->bookingpress_coupon_details, true );


				if( !empty( $coupon_data )){

					$coupon_code = !empty( $coupon_data['coupon_data']['bookingpress_coupon_code'] ) ? $coupon_data['coupon_data']['bookingpress_coupon_code'] : ( !empty( $coupon_data['bookingpress_coupon_code'] ) ? $coupon_data['bookingpress_coupon_code'] : '' );
	
					$this->bookingpress_decrease_coupon_usage( $coupon_code );
				}

				
			}
			
		}

		function bookingpress_reset_coupon_usage( $appointment_id, $new_appointment_status ){

			global $wpdb, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_appointment_meta, $tbl_bookingpress_coupons, $BookingPress;

			if( '3' == $new_appointment_status || '4' == $new_appointment_status ){
				$get_coupon_details = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_coupon_details,bookingpress_is_cart FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm

				if( !empty( $get_coupon_details ) ){
					$coupon_data = json_decode( $get_coupon_details->bookingpress_coupon_details, true );
					$is_cart_appointment = $get_coupon_details->bookingpress_is_cart;				

					if(!empty($coupon_data) && $is_cart_appointment ==0){
						$coupon_code = $coupon_data['coupon_data']['bookingpress_coupon_code'];

						$bookingpress_coupon_usage = $wpdb->get_var( $wpdb->prepare( "SELECT bookingpress_coupon_used FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_id = %d", $coupon_data['coupon_data']['bookingpress_coupon_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_coupons is table name defined globally. False Positive alarm
						
						if( 0 < $bookingpress_coupon_usage ){
							$wpdb->update(
								$tbl_bookingpress_coupons,
								array(
									'bookingpress_coupon_used' => ( $bookingpress_coupon_usage - 1 )
								),
								array(
									'bookingpress_coupon_id' => $coupon_data['coupon_data']['bookingpress_coupon_id']
								)
							);
						}				
					}

				}
			}

		}
		
		/**
		 * Function for change coupon status backend
		 *
		 * @return void
		 */
		function bookingpress_change_coupon_status() {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress;
			$response              = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'change_coupon_status', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			$coupon_id = ! empty( $_POST['coupon_id'] ) ? intval( $_POST['coupon_id'] ) : 0; // phpcs:ignore
			if ( ! empty( $coupon_id ) ) {
				$coupon_details                 = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_id = %d", $coupon_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
				$bookingpress_coupon_old_status = isset( $coupon_details['bookingpress_coupon_status'] ) ? intval( $coupon_details['bookingpress_coupon_status'] ) : 0;

				$bookingpress_coupon_new_status = 1;
				if ( $bookingpress_coupon_old_status == 0 ) {
					$bookingpress_coupon_new_status = 1;
				} else {
					$bookingpress_coupon_new_status = 0;
				}

				$coupon_update_data = array(
					'bookingpress_coupon_status' => $bookingpress_coupon_new_status,
				);

				$coupon_where_condition = array(
					'bookingpress_coupon_id' => $coupon_id,
				);

				$wpdb->update( $tbl_bookingpress_coupons, $coupon_update_data, $coupon_where_condition );

				$response['variant'] = 'success';
				$response['title']   = __( 'Success', 'bookingpress-appointment-booking' );
				$response['msg']     = __( 'Coupon status change successfully', 'bookingpress-appointment-booking' );
			}

			echo wp_json_encode( $response );
			exit();
		}
		
		/**
		 * Function for bulk actions of coupon module
		 *
		 * @return void
		 */
		function bookingpress_bulk_actions() {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress;
			$response              = array();
			
			$bpa_check_authorization = $this->bpa_check_authentication( 'bulk_coupon_actions', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			if ( ! empty( $_POST['bulk_action'] ) && sanitize_text_field( $_POST['bulk_action'] ) == 'delete' ) { // phpcs:ignore
				$delete_ids = ! empty( $_POST['delete_ids'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['delete_ids'] ) : array(); // phpcs:ignore
				if ( ! empty( $delete_ids ) ) {
					foreach ( $delete_ids as $delete_coupon_key => $delete_coupon_id ) {
						$delete_coupon_id = $delete_coupon_id['coupon_id'];
						$wpdb->delete( $tbl_bookingpress_coupons, array( 'bookingpress_coupon_id' => $delete_coupon_id ) );
					}

					$response['variant'] = 'success';
					$response['title']   = __( 'Success', 'bookingpress-appointment-booking' );
					$response['msg']     = __( 'Coupons deleted successfully', 'bookingpress-appointment-booking' );
				}
			}

			echo wp_json_encode( $response );
			exit();
		}
		
		/**
		 * Function for delete coupon
		 *
		 * @return void
		 */
		function bookingpress_delete_coupon_details($is_api = false) {
			global $wpdb, $BookingPress, $tbl_bookingpress_coupons;
			$response              = array();

			if($is_api == true){
				$is_error = $this->bookingpress_validate_token();
				if( is_wp_error( $is_error ) ){
					return $is_error;
				}
			}else{
				$bpa_check_authorization = $this->bpa_check_authentication( 'delete_coupons', true, 'bpa_wp_nonce' );           
				if( preg_match( '/error/', $bpa_check_authorization ) ){
					$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
					$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

					$response['variant'] = 'error';
					$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
					$response['msg'] = $bpa_error_msg;

					wp_send_json( $response );
					die;
				}
			}
			
			

			$delete_id = ! empty( $_POST['delete_id'] ) ? intval( $_POST['delete_id'] ) : 0; // phpcs:ignore
			if ( ! empty( $delete_id ) ) {
				$is_deleted = $wpdb->delete( $tbl_bookingpress_coupons, array( 'bookingpress_coupon_id' => $delete_id ) );

				if($is_deleted){
					$response['variant'] = 'success';
					$response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
					$response['msg']     = esc_html__( 'Coupon deleted successfully', 'bookingpress-appointment-booking' );
				}else{
					$response['variant'] = 'error';
					$response['title']   = esc_html__( 'Error', 'bookingpress-appointment-booking' );
					$response['msg']     = esc_html__( 'Either coupon deleted already or coupon not found', 'bookingpress-appointment-booking' );
				}
				
			}
			if($is_api){
				return $response;
			}else{
				echo wp_json_encode( $response );
				exit();
			}
			
		}
		
		/**
		 * Function for get edit coupon details
		 *
		 * @return void
		 */
		function bookingpress_edit_coupon_details() {
			global $wpdb, $BookingPress, $tbl_bookingpress_coupons;
			$response              = array();

			$bpa_check_authorization = $this->bpa_check_authentication( 'get_edit_coupon_details', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			$response['variant']        = 'success';
			$response['title']          = esc_html__( 'Success', 'bookingpress-appointment-booking' );
			$response['msg']            = esc_html__( 'Coupons fetched successfully', 'bookingpress-appointment-booking' );
			$response['coupon_details'] = array();

			if ( ! empty( $_POST['edit_id'] ) ) { // phpcs:ignore
				$edit_id = intval( $_POST['edit_id'] ); // phpcs:ignore

				$edit_coupon_details            = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_id = %d", $edit_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
				$bookingpress_selected_services = ! empty( $edit_coupon_details['bookingpress_coupon_services'] ) ? explode( ',', $edit_coupon_details['bookingpress_coupon_services'] ) : array();

				$bookingpress_selected_customers = ! empty( $edit_coupon_details['bookingpress_coupon_customers'] ) ? explode( ',', $edit_coupon_details['bookingpress_coupon_customers'] ) : array();

				$bookingpress_customer_selection_details = array();
                //if(!empty($bookingpress_customer_id)) {
                    $bookingpress_customer_selection_details = $BookingPress->bookingpress_get_appointment_customer_list('',$bookingpress_selected_customers);
                //}   

				$response['coupon_details']     = array(
					'coupon_title'         => (!empty($edit_coupon_details['bookingpress_coupon_title']) && !is_null($edit_coupon_details['bookingpress_coupon_title']))?$edit_coupon_details['bookingpress_coupon_title']:'',
					'coupon_code'          => $edit_coupon_details['bookingpress_coupon_code'],
					'coupon_discount'      => $edit_coupon_details['bookingpress_coupon_discount'],
					'coupon_discount_type' => $edit_coupon_details['bookingpress_coupon_discount_type'],
					'coupon_period_type'   => $edit_coupon_details['bookingpress_coupon_period_type'],
					'coupon_start_date'    => $edit_coupon_details['bookingpress_coupon_start_date'],
					'coupon_end_date'      => $edit_coupon_details['bookingpress_coupon_end_date'],
					'coupon_services'      => $bookingpress_selected_services,
					'coupon_customers_list'  => $bookingpress_customer_selection_details,
					'coupon_allowed_uses'  => $edit_coupon_details['bookingpress_coupon_allowed_uses'],
					'update_id'            => $edit_id,
				);
			}

			echo wp_json_encode( $response );
			exit();
		}
		
		/**
		 * Function for load all coupons details
		 *
		 * @return void
		 */
		function bookingpress_load_coupon_details() {
			global $wpdb, $BookingPress, $tbl_bookingpress_coupons, $bookingpress_global_options, $tbl_bookingpress_services;
			$response              = array();

			$bpa_check_authorization = $this->bpa_check_authentication( 'get_coupon_details', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }
			
			$response['variant'] = 'success';
			$response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
			$response['msg']     = esc_html__( 'Coupons fetched successfully', 'bookingpress-appointment-booking' );

			$perpage     = isset( $_POST['perpage'] ) ? intval( $_POST['perpage'] ) : 10; // phpcs:ignore
			$currentpage = isset( $_POST['currentpage'] ) ? intval( $_POST['currentpage'] ) : 1; // phpcs:ignore
			$offset      = ( ! empty( $currentpage ) && $currentpage > 1 ) ? ( ( $currentpage - 1 ) * $perpage ) : 0;

			$get_total_coupons = $wpdb->get_results( 'SELECT * FROM ' . $tbl_bookingpress_coupons, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
			$total_coupons     = $wpdb->get_results( 'SELECT * FROM ' . $tbl_bookingpress_coupons . ' ORDER BY bookingpress_coupon_id DESC LIMIT ' . $offset . ',' . $perpage, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm

			$bookingpress_global_options_arr  = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_default_date_format = $bookingpress_global_options_arr['wp_default_date_format'];

			$bookingpress_currency_name = $BookingPress->bookingpress_get_settings( 'payment_default_currency', 'payment_setting' );
			$currency_symbol            = ! empty( $bookingpress_currency_name ) ? $BookingPress->bookingpress_get_currency_symbol( $bookingpress_currency_name ) : '';

			$coupons_list = $customers_list_arr = array();
			foreach ( $total_coupons as $coupon_key => $coupon_val ) {
				$coupon_services      = '';
				$services_details_arr = ! empty( $coupon_val['bookingpress_coupon_services'] ) ? explode( ',', $coupon_val['bookingpress_coupon_services'] ) : array();
				if ( is_array( $services_details_arr ) && ! empty( $services_details_arr ) ) {
					$coupon_services = '<ul>';
					foreach ( $services_details_arr as $service_key => $service_val ) {
						$bookingpress_service_id = intval( $service_val );
						$service_data            = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_services} WHERE bookingpress_service_id = %d", $bookingpress_service_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
						$coupon_services        .= '<li>' . stripslashes_deep($service_data['bookingpress_service_name']) . '</li>';
					}
					$coupon_services .= '</ul>';
				}

				$customers_details_arr = ! empty( $coupon_val['bookingpress_coupon_customers'] ) ? explode( ',', $coupon_val['bookingpress_coupon_customers'] ) : array();
				$bookingpress_customer_selection_details = array();

                if(!empty($customers_details_arr)) {
					foreach($customers_details_arr as $customer_key => $customer_id){
						$get_customers_details = $BookingPress->bookingpress_get_appointment_customer_list('',$customer_id);
						$customers_list_arr[] = isset($get_customers_details[0]) ? $get_customers_details[0] : array();
					}
                }  
				$coupon_val['bookingpress_coupon_discount_org'] = $coupon_val['bookingpress_coupon_discount']; 
				$coupon_val['bookingpress_coupon_discount'] = !empty($coupon_val['bookingpress_coupon_discount_type']) && $coupon_val['bookingpress_coupon_discount_type'] == 'Fixed' ? $BookingPress->bookingpress_price_formatter_with_currency_symbol($coupon_val['bookingpress_coupon_discount'] ): $coupon_val['bookingpress_coupon_discount'];

				$coupon_usage = !empty( $coupon_val['bookingpress_coupon_used'] ) ? intval( $coupon_val['bookingpress_coupon_used'] ) : 0;

				$coupon_code = $coupon_val['bookingpress_coupon_code'];

				$locked_coupon = $this->bookingpress_get_coupon_locked_usage( $coupon_code );

				if( !empty( $locked_coupon ) ){
					$coupon_usage += $locked_coupon;
				}

				$coupons_list[] = array(
					'update_id'            => $coupon_val['bookingpress_coupon_id'],
					'coupon_id'            => $coupon_val['bookingpress_coupon_id'],
					'coupon_title'         => (!is_null($coupon_val['bookingpress_coupon_title']) && !empty($coupon_val['bookingpress_coupon_title']))?stripslashes_deep($coupon_val['bookingpress_coupon_title']):'',
					'coupon_code'          => $coupon_code,
					'coupon_discount'      => $coupon_val['bookingpress_coupon_discount_org'],
					'coupon_discount_display'      => $coupon_val['bookingpress_coupon_discount'],
					'coupon_discount_type' => $coupon_val['bookingpress_coupon_discount_type'],
					'coupon_period'        => $coupon_val['bookingpress_coupon_period_type'],
					'coupon_start_date'    => $coupon_val['bookingpress_coupon_start_date'],
					'coupon_end_date'      => $coupon_val['bookingpress_coupon_end_date'],
					'coupon_services'      => $services_details_arr,
					'coupon_allowed_customer' => $customers_details_arr,
					'coupon_customers_list' => $customers_list_arr,
					'coupon_start_date_formated' => date( $bookingpress_default_date_format, strtotime( $coupon_val['bookingpress_coupon_start_date'] ) ),
					'coupon_end_date_formated'   => date( $bookingpress_default_date_format, strtotime( $coupon_val['bookingpress_coupon_end_date'] ) ),
					'coupon_services_display'  => $coupon_services,					
					'coupon_allowed_uses'  => $coupon_val['bookingpress_coupon_allowed_uses'],
					'coupon_total_used'    => $coupon_usage,
					'coupon_status'        => (bool) $coupon_val['bookingpress_coupon_status'],
					'currency_symbol'      => $currency_symbol,
				);
			}

			$response['items']      = $coupons_list;
			$response['totalItems'] = count( $get_total_coupons );

			echo wp_json_encode( $response );
			exit();
		}
		
		/**
		 * Function for save coupon details
		 *
		 * @return void
		 */
		function bookingpress_save_coupon_details($is_api = false) {
			global $wpdb, $tbl_bookingpress_coupons, $BookingPress;
			$response              = array();
			
			if($is_api == true){
				$is_error = $this->bookingpress_validate_token();
                if( is_wp_error( $is_error ) ){
                    return $is_error;
                }
			}else{
				$bpa_check_authorization = $this->bpa_check_authentication( 'save_coupon_details', true, 'bpa_wp_nonce' );
            
				if( preg_match( '/error/', $bpa_check_authorization ) ){
					$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
					$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

					$response['variant'] = 'error';
					$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
					$response['msg'] = $bpa_error_msg;

					wp_send_json( $response );
					die;
				}
			}

			

			if ( ! empty( $_POST['bookingpress_coupon_details'] ) ) { // phpcs:ignore
				$coupon_code = ! empty( $_POST['bookingpress_coupon_details']['coupon_code'] ) ? sanitize_text_field( $_POST['bookingpress_coupon_details']['coupon_code'] ) : ''; // phpcs:ignore

				$coupon_discount = ! empty( $_POST['bookingpress_coupon_details']['coupon_discount'] ) ? floatval( $_POST['bookingpress_coupon_details']['coupon_discount'] ) : 0; // phpcs:ignore

				$coupon_discount_type = ! empty( $_POST['bookingpress_coupon_details']['coupon_discount_type'] ) ? sanitize_text_field( $_POST['bookingpress_coupon_details']['coupon_discount_type'] ) : 'Fixed'; // phpcs:ignore
				if($coupon_discount_type == 'Percentage' && $coupon_discount > 100){

					$response['variant'] = 'error';
					$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
					$response['msg'] = esc_html__( 'May not add more than a 100% discount.', 'bookingpress-appointment-booking');
					if($is_api){
						return $response;
					}else{
						wp_send_json( $response );
						die;
					}

				}
				$coupon_period_type = ! empty( $_POST['bookingpress_coupon_details']['coupon_period_type'] ) ? sanitize_text_field( $_POST['bookingpress_coupon_details']['coupon_period_type'] ) : 'unlimited'; // phpcs:ignore

				$coupon_start_date = ! empty( $_POST['bookingpress_coupon_details']['coupon_start_date'] ) ? sanitize_text_field( date('Y-m-d', strtotime($_POST['bookingpress_coupon_details']['coupon_start_date'])) ) : ''; // phpcs:ignore

				$coupon_end_date = ! empty( $_POST['bookingpress_coupon_details']['coupon_end_date'] ) ? sanitize_text_field( date('Y-m-d', strtotime($_POST['bookingpress_coupon_details']['coupon_end_date'])) ) : ''; // phpcs:ignore

				$coupon_services = ! empty( $_POST['bookingpress_coupon_details']['coupon_services'] ) ? implode( ',', array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['bookingpress_coupon_details']['coupon_services'] ) ) : ''; // phpcs:ignore

				$coupon_customer = ! empty( $_POST['bookingpress_coupon_details']['coupon_allowed_customer'] ) ? implode( ',', array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['bookingpress_coupon_details']['coupon_allowed_customer'] ) ) : ''; // phpcs:ignore

				$coupon_allowed_uses = ! empty( $_POST['bookingpress_coupon_details']['coupon_allowed_uses'] ) ? intval( $_POST['bookingpress_coupon_details']['coupon_allowed_uses'] ) : 0; // phpcs:ignore

				$update_id = ! empty( $_POST['bookingpress_coupon_details']['update_id'] ) ? sanitize_text_field( $_POST['bookingpress_coupon_details']['update_id'] ) : 0; // phpcs:ignore

				$coupon_title = ! empty( $_POST['bookingpress_coupon_details']['coupon_title'] ) ? sanitize_text_field( $_POST['bookingpress_coupon_details']['coupon_title'] ) : ''; // phpcs:ignore

				if($coupon_period_type == 'date_range' && $coupon_start_date > $coupon_end_date) {
					$response['variant'] = 'error';
					$response['title']   = esc_html__( 'Error', 'bookingpress-appointment-booking' );
					$response['msg']   = esc_html__( 'Coupn Period Start date is not greater than End date', 'bookingpress-appointment-booking' );
					if($is_api){
							return $response;
					}else{
						wp_send_json( $response );
						die;
					}
					
				}

				$coupon_db_fields = array(
					'bookingpress_coupon_title'         => $coupon_title,
					'bookingpress_coupon_code'          => stripslashes_deep($coupon_code),
					'bookingpress_coupon_discount'      => $coupon_discount,
					'bookingpress_coupon_discount_type' => $coupon_discount_type,
					'bookingpress_coupon_period_type'   => $coupon_period_type,
					'bookingpress_coupon_start_date'    => $coupon_start_date,
					'bookingpress_coupon_end_date'      => $coupon_end_date,
					'bookingpress_coupon_services'      => $coupon_services,
					'bookingpress_coupon_customers'      => $coupon_customer,					
					'bookingpress_coupon_allowed_uses'  => $coupon_allowed_uses,
				);

				if ( empty( $update_id ) ) {
					// Check coupon code already exists or not
					$coupon_is_exist = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(bookingpress_coupon_id) as total FROM {$tbl_bookingpress_coupons} WHERE bookingpress_coupon_code = %s", $coupon_code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_coupons is a table name. false alarm
					if ( $coupon_is_exist > 0 ) {
						$response['variant'] = 'error';
						$response['title']   = esc_html__( 'Error', 'bookingpress-appointment-booking' );
						$response['msg'] = esc_html__( 'Coupon Code already exists', 'bookingpress-appointment-booking' );
						if($is_api){
							return $response;
						}else{
							wp_send_json( $response );
							die;
						}
					}

					$wpdb->insert( $tbl_bookingpress_coupons, $coupon_db_fields );
					$bpa_coupon_id = $wpdb->insert_id;

					$response['coupon_id'] = $bpa_coupon_id;
					$response['msg'] = esc_html__( 'Coupon created successfully', 'bookingpress-appointment-booking' );
				} else {
					$coupon_update_where_condition = array(
						'bookingpress_coupon_id' => $update_id,
					);

					$wpdb->update( $tbl_bookingpress_coupons, $coupon_db_fields, $coupon_update_where_condition );
					$response['msg'] = esc_html__( 'Coupon updated successfully', 'bookingpress-appointment-booking' );
				}

				$response['variant'] = 'success';
				$response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
			}
			if($is_api){
				return $response;
			}else{
				echo wp_json_encode( $response );
				exit();
			}
			
		}
		
		/**
		 * Function for add dynamic helper variables for coupon module
		 *
		 * @return void
		 */
		function bookingpress_coupons_dynamic_helper_vars_func() {
			global $bookingpress_global_options;
			$bookingpress_options     = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_locale_lang = $bookingpress_options['locale'];
			?>
				var lang = ELEMENT.lang.<?php echo esc_html( $bookingpress_locale_lang ); ?>;
				ELEMENT.locale(lang)
			<?php
		}
		
		/**
		 * Function for add coupon module dynamic data variables
		 *
		 * @return void
		 */
		function bookingpress_coupons_dynamic_data_fields_func() {
			global $wpdb, $bookingpress_coupon_vue_data_fields, $BookingPress, $bookingpress_global_options, $tbl_bookingpress_services, $BookingPressPro;

			$bookingpress_default_perpage_option                          = $BookingPress->bookingpress_get_settings( 'per_page_item', 'general_setting' );

			$bookingpress_coupon_vue_data_fields['rules']                  = array(
				'coupon_code'          => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please enter coupon code', 'bookingpress-appointment-booking' ),
						'trigger'  => 'blur',
					),
				),
				'coupon_discount'      => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please enter coupon discount', 'bookingpress-appointment-booking' ),
						'trigger'  => 'blur',
					),
				),
				'coupon_discount_type' => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please select coupon discount type', 'bookingpress-appointment-booking' ),
						'trigger'  => 'change',
					),
				),
				'coupon_start_date' => array( 
					array(
						'required' => true,
						'message'  => esc_html__('Please select coupon start date', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
				'coupon_end_date' => array( 
					array(
						'required' => true,
						'message'  => esc_html__('Please select coupon end date', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
			);

			$bookingpress_coupon_vue_data_fields['bulk_options']           = array(
				array(
					'value' => 'bulk_action',
					'label' => __( 'Bulk Action', 'bookingpress-appointment-booking' ),
				),
				array(
					'value' => 'delete',
					'label' => __( 'Delete', 'bookingpress-appointment-booking' ),
				),
			);

			$bookingpress_coupon_vue_data_fields['perPage']               = ! empty( $bookingpress_default_perpage_option ) ? $bookingpress_default_perpage_option : '10';
			$bookingpress_coupon_vue_data_fields['pagination_length_val'] = ! empty( $bookingpress_default_perpage_option ) ? $bookingpress_default_perpage_option : '10';


			$bookingpress_payment_deafult_currency                           = $BookingPress->bookingpress_get_settings('payment_default_currency', 'payment_setting');
            $bookingpress_payment_deafult_currency                           = $BookingPress->bookingpress_get_currency_symbol($bookingpress_payment_deafult_currency);
            $bookingpress_coupon_vue_data_fields['default_currency_symbol']  = $bookingpress_payment_deafult_currency;

			$services_data = $BookingPress->get_bookingpress_service_data_group_with_category();
			$bookingpress_coupon_vue_data_fields['coupon_services_list'] = $services_data;

			$bookingpress_coupon_vue_data_fields['coupon_customers_list'] = array();

			$bookingpress_coupon_vue_data_fields['bookingpress_customer_loading'] = false;

			$save_coupon_details= $change_coupon_status = $get_edit_coupon_details = $delete_coupons = $bulk_coupon_actions = ( current_user_can('administrator') ) ? 1 : 0;
			if($BookingPressPro->bookingpress_check_capability( 'save_coupon_details' ) ){
				$save_coupon_details = 1;
			}
			if($BookingPressPro->bookingpress_check_capability( 'change_coupon_status' ) ){
				$change_coupon_status = 1;
			}
			if($BookingPressPro->bookingpress_check_capability( 'get_edit_coupon_details' ) ){
				$get_edit_coupon_details = 1;
			}
			if($BookingPressPro->bookingpress_check_capability( 'delete_coupons' ) ){
				$delete_coupons = 1;
			}
			if($BookingPressPro->bookingpress_check_capability( 'bulk_coupon_actions' ) ){
				$bulk_coupon_actions = 1;
			}

			$bookingpress_coupon_vue_data_fields["save_coupon_details"] = $save_coupon_details;
			$bookingpress_coupon_vue_data_fields["change_coupon_status"] = $change_coupon_status;
			$bookingpress_coupon_vue_data_fields["get_edit_coupon_details"] = $get_edit_coupon_details;
			$bookingpress_coupon_vue_data_fields["delete_coupons"] = $delete_coupons;
			$bookingpress_coupon_vue_data_fields["bulk_coupon_actions"] = $bulk_coupon_actions;
			$bookingpress_coupon_vue_data_fields = apply_filters('bookingpress_modify_coupon_data_fields',$bookingpress_coupon_vue_data_fields);

			echo wp_json_encode( $bookingpress_coupon_vue_data_fields );
		}
		
		/**
		 * Function for execute code on coupon module load
		 *
		 * @return void
		 */
		function bookingpress_coupons_on_load_methods_func() {
			?>
				const vm = this
				vm.loadCoupons()
			<?php
		}
		
		/**
		 * Function for add vue methods to coupon module
		 *
		 * @return void
		 */
		function bookingpress_coupons_vue_methods_func() {
			global $bookingpress_notification_duration;
			?>
				get_formatted_date(iso_date){

					if( true == /(\d{2})\T/.test( iso_date ) ){
						let date_time_arr = iso_date.split('T');
						return date_time_arr[0];
					}
					var __date = new Date(iso_date);
					var __year = __date.getFullYear();
					var __month = __date.getMonth()+1;
					var __day = __date.getDate();
					if (__day < 10) {
						__day = '0' + __day;
					}
					if (__month < 10) {
						__month = '0' + __month;
					}
					var formatted_date = __year+'-'+__month+'-'+__day;
					return formatted_date;
				},
				bookingpress_row_classname(row, rowIndex){
					if(row.row.coupon_status == false){
						return 'bpa-table__is-row-disabled';
					}
				},
				async loadCoupons(){
					const vm = this;
					sessionStorage.setItem("current_coupon_tabname", "coupon");
					vm.is_display_save_loader = 1
					vm.is_disabled = 1
					var loadCouponDetails = {};
					vm.multipleSelection = [];
					loadCouponDetails.action = 'bookingpress_load_coupon_details'
					loadCouponDetails.perpage= vm.perPage
					loadCouponDetails.currentpage = vm.currentPage
					loadCouponDetails._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>';
					axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( loadCouponDetails ) )
					.then(function(response){
						vm.is_display_save_loader = 0
						vm.is_disabled = 0
						vm.items = response.data.items;
						vm.totalItems = response.data.totalItems;
					}).catch(function(error){
						console.log(error);
						vm.$notify({
							title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
							message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
							type: 'error',
							customClass: 'error_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					});
				},
				bookingpress_copy_code(code)
				{
					const vm = this;
					var bookingpress_selected_placholder = code;
					var bookingpress_dummy_elem = document.createElement("textarea");
					document.body.appendChild(bookingpress_dummy_elem);
					bookingpress_dummy_elem.value = bookingpress_selected_placholder;
					bookingpress_dummy_elem.select();
					document.execCommand("copy");
					document.body.removeChild(bookingpress_dummy_elem);
					vm.$notify(
					{ 
						title: '<?php esc_html_e('Success', 'bookingpress-appointment-booking'); ?>',
						message: '<?php echo esc_html_e('Coupon code copied sucessfully.','bookingpress-appointment-booking'); ?>',
						type: 'success',
						customClass: 'success_notification',
						duration:<?php echo intval($bookingpress_notification_duration); ?>,
					});
				},
				handleSelectionChange(val) {
					const coupon_items_obj = val
					this.multipleSelection = [];
					Object.values(coupon_items_obj).forEach(val => {
						this.multipleSelection.push({coupon_id : val.coupon_id})
						this.bulk_action = 'bulk_action';
					});
				},
				handleSizeChange(val) {
					this.perPage = val
					this.loadCoupons()
				},
				handleCurrentChange(val) {
					this.currentPage = val;
					this.loadCoupons()
				},
				changeCurrentPage(perPage) {
					var total_item = this.totalItems;
					var recored_perpage = perPage;
					var select_page =  this.currentPage;				
					var current_page = Math.ceil(total_item/recored_perpage);
					if(total_item <= recored_perpage ) {
						current_page = 1;
					} else if(select_page >= current_page ) {
						
					} else {
						current_page = select_page;
					}
					return current_page;
				},
				changePaginationSize(selectedPage) { 	
					var total_recored_perpage = selectedPage;
					var current_page = this.changeCurrentPage(total_recored_perpage);										
					this.perPage = selectedPage;					
					this.currentPage = current_page;	
					this.loadCoupons()
				},
				closeBulkAction(){
					this.$refs.multipleTable.clearSelection();
					this.bulk_action = 'bulk_action';
				},
				openCouponModal(){
					const vm = this
					vm.bookingpress_reset_coupon_details();
					vm.open_coupon_modal = true
				},
				closeCouponModal(){
					const vm = this
					vm.open_coupon_modal = false
				},
				generate_coupon(){
					const vm = this
					var generated_coupon = '';
					var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
					for ( var i = 0; i < 8; i++ ) {
						generated_coupon += characters.charAt(Math.floor(Math.random() * 62));
					}
					vm.coupon_details.coupon_code = generated_coupon;
					vm.$refs['coupon_details'].clearValidate('coupon_code');
				},
				saveCouponDetails(coupon_details){
					const vm =  this;
					vm.$refs[coupon_details].validate((valid) => {
						if (valid) {
							vm.is_display_save_loader = 1
							vm.is_disabled = '1'
							var saveCouponDetails = {};
							saveCouponDetails.action = 'bookingpress_save_coupon_details';
							saveCouponDetails.bookingpress_coupon_details = vm.coupon_details;
							saveCouponDetails._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>';
							axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( saveCouponDetails ) )
							.then(function(response){
								if(response.data.variant == 'success'){
									vm.loadCoupons();
									vm.multipleSelection = [];
									vm.totalItems = vm.items.length;																										
									vm.$notify({
										title: response.data.title,
										message: response.data.msg,
										type: response.data.variant,
										customClass: response.data.variant+'_notification',
										duration:<?php echo intval($bookingpress_notification_duration); ?>,
									});
									vm.is_display_save_loader = 0;
									vm.is_disabled = 0;
									vm.closeCouponModal();																			
								}else{
									vm.is_display_save_loader = 0;
									vm.is_disabled = 0;								
									vm.$notify({
										title: response.data.title,
										message: response.data.msg,
										type: response.data.variant,
										customClass: response.data.variant+'_notification',
										duration:<?php echo intval($bookingpress_notification_duration); ?>,
									});	
								}							
							}).catch(function(error){
								console.log(error);
								vm.$notify({
									title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
									message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
									type: 'error',
									customClass: 'error_notification',
									duration:<?php echo intval($bookingpress_notification_duration); ?>,
								});
							});
						}
					});
				},
				editCoupon(edit_id,select_coupon_details){
					const vm = this
					vm.is_display_loader = 1
					vm.coupon_details.update_id = edit_id
					vm.openCouponModal()
					var editCouponData = {};
					editCouponData.edit_id = edit_id;
					
					if(typeof select_coupon_details !== 'undefined'){
						vm.is_display_loader = 0;
						if(typeof select_coupon_details.coupon_title !== 'undefined'){
							vm.coupon_details.coupon_title = select_coupon_details.coupon_title;
						}
						if(typeof select_coupon_details.coupon_code !== 'undefined'){
							vm.coupon_details.coupon_code = select_coupon_details.coupon_code;
						}
						if(typeof select_coupon_details.coupon_discount !== 'undefined'){
							vm.coupon_details.coupon_discount = select_coupon_details.coupon_discount;
						}
						if(typeof select_coupon_details.coupon_discount_type !== 'undefined'){
							vm.coupon_details.coupon_discount_type = select_coupon_details.coupon_discount_type;
						}
						if(typeof select_coupon_details.coupon_period !== 'undefined'){
							vm.coupon_details.coupon_period_type = select_coupon_details.coupon_period;
						}
						if(typeof select_coupon_details.coupon_start_date !== 'undefined'){
							vm.coupon_details.coupon_start_date = select_coupon_details.coupon_start_date;
						}
						if(typeof select_coupon_details.coupon_end_date !== 'undefined'){
							vm.coupon_details.coupon_end_date = select_coupon_details.coupon_end_date;
						}
						if(typeof select_coupon_details.coupon_services !== 'undefined'){
							vm.coupon_details.coupon_services = select_coupon_details.coupon_services;
						}
						if(typeof select_coupon_details.coupon_allowed_customer !== 'undefined'){
							vm.coupon_details.coupon_allowed_customer = select_coupon_details.coupon_allowed_customer;
						}
						if(typeof select_coupon_details.coupon_customers_list !== 'undefined'){
							vm.coupon_customers_list = select_coupon_details.coupon_customers_list;
						}
						if(typeof select_coupon_details.coupon_allowed_uses !== 'undefined'){
							vm.coupon_details.coupon_allowed_uses = select_coupon_details.coupon_allowed_uses;
						}
						if(typeof select_coupon_details.update_id !== 'undefined'){
							vm.coupon_details.update_id = select_coupon_details.update_id;
						}																																																						
					}else{
						editCouponData.action = 'bookingpress_edit_coupon_data'
						editCouponData.edit_id = edit_id
						editCouponData._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>';
						axios.post( appoint_ajax_obj.ajax_url, Qs.stringify(editCouponData))
						.then(function(response){
							vm.coupon_details = response.data.coupon_details;
							vm.is_display_loader = 0;
						}).catch(function(error){
							console.log(error);
							vm.$notify({
								title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
								message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
								type: 'error',
								customClass: 'error_notification',
								duration:<?php echo intval($bookingpress_notification_duration); ?>,
							});
						});
					}
				},
				bulk_actions(){
					const vm = new Vue()
					const vm2 = this
					if(this.bulk_action == "bulk_action")
					{
						vm2.$notify({
							title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
							message: '<?php esc_html_e( 'Please select any action.', 'bookingpress-appointment-booking' ); ?>',
							type: 'error',
							customClass: 'error_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					}
					else
					{
						if(this.multipleSelection.length > 0 && this.bulk_action == "delete")
						{
							var coupon_delete_data = {
								action:'bookingpress_coupon_bulk_actions',
								delete_ids: this.multipleSelection,
								bulk_action: 'delete',
								_wpnonce:'<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>' 
							}

							axios.post(appoint_ajax_obj.ajax_url, Qs.stringify(coupon_delete_data))
							.then(function(response){
								vm2.$notify({
									title: response.data.title,
									message: response.data.msg,
									type: response.data.variant,
								});
								vm2.loadCoupons()
							}).catch(function(error){
								console.log(error)
								vm2.$notify({
									title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
									message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
									type: 'error',
									customClass: 'error_notification',
								});
							});
						}
						else
						{
							vm2.$notify({
								title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
								message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
								type: 'error',
								customClass: 'error_notification',
							});
						}
					}
				},
				deleteCoupon(delete_id){
					const vm =  this;
					vm.is_display_loader = 1
					var deleteCouponDetails = {};
					deleteCouponDetails.action = 'bookingpress_delete_coupon_details';
					deleteCouponDetails.delete_id = delete_id;
					deleteCouponDetails._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>';
					axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( deleteCouponDetails ) )
					.then(function(response){
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
						vm.loadCoupons()
						vm.is_display_loader = 0
					}).catch(function(error){
						console.log(error);
						vm.$notify({
							title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
							message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
							type: 'error',
							customClass: 'error_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					});
				},
				bookingpress_coupon_status(coupon_id){
					const vm = this
					vm.is_display_loader = 1
					var changeCouponStatus = {};
					changeCouponStatus.action = 'bookingpress_change_coupon_status'
					changeCouponStatus.coupon_id = coupon_id
					changeCouponStatus._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>';
					axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( changeCouponStatus ) )
					.then(function(response){
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
						});
						vm.loadCoupons()
						vm.is_display_loader = 0
					}).catch(function(error){
						console.log(error);
						vm.$notify({
							title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
							message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
							type: 'error',
							customClass: 'error_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					});	
				},
				bookingpress_reset_coupon_details(){
					const vm = this
					vm.coupon_details.coupon_title = '';
					vm.coupon_details.coupon_code = '';
					vm.coupon_details.coupon_discount = '';
					vm.coupon_details.coupon_discount_type = 'Percentage';
					vm.coupon_details.coupon_period_type = 'unlimited';
					vm.coupon_details.coupon_start_date = '<?php echo esc_html( date( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>';
					vm.coupon_details.coupon_end_date = '<?php echo esc_html( date( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>';
					vm.coupon_details.coupon_services = '';
					vm.coupon_details.coupon_allowed_customer = '';
					vm.coupon_details.coupon_allowed_uses = 0;
					vm.coupon_details.update_id = 0;
				},
				bookingpress_get_customer_list(query){
					const vm = new Vue()
					const vm2 = this	
					if (query !== '') {
						vm2.bookingpress_customer_loading = true;                    
						var customer_action = { action:'bookingpress_get_customer_list',search_user_str:query,customer_id:vm2.customer_id,_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' }                    
						axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( customer_action ) )
						.then(function(response){
							vm2.bookingpress_customer_loading = false;
							vm2.coupon_customers_list = response.data.appointment_customers_details
						}).catch(function(error){
							console.log(error)
						});
					} else {
						vm2.coupon_customers_list = [];
					}	
				},
			<?php
			do_action( 'bookingpress_add_coupon_dynamic_external_vue_methods' );
		}
		
		/**
		 * Function for change view file path for coupon module
		 *
		 * @return void
		 */
		function bookingpress_load_coupons_view_func() {
			$bookingpress_load_file_name = BOOKINGPRESS_PRO_VIEWS_DIR . '/coupons/manage_coupons.php';
			require $bookingpress_load_file_name;
		}
		
		/**
		 * Function for check coupon module activated or not
		 *
		 * @return void
		 */
		function bookingpress_check_coupon_module_activation() {
			$is_coupon_module_activated    = 0;
			$woocommcerce_addon_option_val = get_option( 'bookingpress_coupon_module' );
			if ( ! empty( $woocommcerce_addon_option_val ) && ( $woocommcerce_addon_option_val == 'true' ) ) {
				$is_coupon_module_activated = 1;
			}
			return $is_coupon_module_activated;
		}
	}

	global $bookingpress_coupons, $bookingpress_coupon_vue_data_fields;
	$bookingpress_coupons = new bookingpress_coupons();

	$bookingpress_coupon_vue_data_fields = array(
		'bulk_action'            => 'bulk_action',
		'items'                  => array(),
		'totalItems'             => 0,
		'currentPage'            => 1,
		'is_display_loader'      => '0',
		'multipleSelection'      => array(),
		'is_disabled'            => false,
		'is_display_save_loader' => '0',
		'pagination_length'      => '',
		'pagination_val'         => array(
			array(
				'text'  => '10',
				'value' => '10',
			),
			array(
				'text'  => '20',
				'value' => '20',
			),
			array(
				'text'  => '50',
				'value' => '50',
			),
			array(
				'text'  => '100',
				'value' => '100',
			),
			array(
				'text'  => '200',
				'value' => '200',
			),
			array(
				'text'  => '300',
				'value' => '300',
			),
			array(
				'text'  => '400',
				'value' => '400',
			),
			array(
				'text'  => '500',
				'value' => '500',
			),
		),
		'open_coupon_modal'      => false,
		'coupon_details'         => array(
			'coupon_title'         => '',
			'coupon_code'          => '',
			'coupon_discount'      => '',
			'coupon_discount_type' => 'Percentage',
			'coupon_period_type'   => 'unlimited',
			'coupon_start_date'    => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'coupon_end_date'      => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'coupon_services'      => '',
			'coupon_allowed_customer' => '',
			'coupon_allowed_uses'  => 0,
			'update_id'            => '0',
		),
		'disabledPastDates'      => '',
	);
}
