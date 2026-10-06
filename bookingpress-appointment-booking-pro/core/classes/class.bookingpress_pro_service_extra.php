<?php
if ( ! class_exists( 'bookingpress_service_extra' ) ) {
	class bookingpress_service_extra Extends BookingPress_Core {
		function __construct() {
			add_action('bookingpress_after_activate_bookingpress_module', array( $this,'bookingpress_after_activate_bookingpress_module_func'));
			if ( $this->bookingpress_check_service_extra_module_activation() ) {
				add_filter( 'bookingpress_modify_service_data_fields', array( $this, 'bookingpress_modify_service_data_fields_func' ) );
				add_filter( 'bookingpress_after_add_update_service', array( $this, 'bookingpress_save_service_details' ), 10, 3 );
				add_action( 'bookingpress_edit_service_more_vue_data', array( $this, 'bookingpress_edit_service_more_vue_data_func' ) );
				add_action( 'bookingpress_add_service_dynamic_vue_methods', array( $this, 'bookingpress_add_service_dynamic_vue_methods_func' ), 10 );
				add_filter( 'bookingpress_modify_edit_service_data', array( $this, 'bookingpress_modify_edit_service_data_func' ), 10,2 );
				add_action( 'wp_ajax_bookingpress_get_extra_service_data', array( $this, 'bookingpress_get_extra_services_data_func' ), 10 );
				add_action( 'bookingpress_after_reset_add_service_form', array( $this, 'bookingpress_after_reset_add_service_form_func' ), 10 );
				add_filter( 'bookingpress_dynamic_add_params_for_timeslot_request', array( $this, 'bookingpress_dynamic_add_params_for_timeslot_request_service_extra' ) );
				add_filter( 'bookingpress_modify_service_timeslot', array( $this, 'bookingpress_modify_service_timeslot_with_service_extras'), 10, 4 );
				add_filter('bookingpress_frontend_apointment_form_add_dynamic_data',array($this ,'bookingpress_frontend_apointment_form_add_dynamic_data_func'));
				add_filter('bookingpress_customize_add_dynamic_data_fields',array($this,'bookingpress_customize_add_dynamic_data_fields_func'),10);
				add_filter('bookingpress_get_booking_form_customize_data_filter',array($this, 'bookingpress_get_booking_form_customize_data_filter_func'),10,1);
				add_action( 'bookingpress_set_additional_appointment_xhr_data', array( $this, 'bookingpress_set_extras_appointment_xhr_data') );
				add_action('wp_ajax_bookingpress_format_assigned_service_extra_amounts', array($this,'bookingpress_format_assigned_service_extra_amounts_func'));

				/** Modify all services array & append service extras */
				add_filter( 'bookingpress_modify_all_retrieved_services', array( $this, 'bookingpress_append_service_extras' ), 11, 4 );

				add_filter( 'bookingpress_before_selecting_booking_service', array( $this, 'bookingpress_before_selecing_booking_service_for_extras'), 10 );

				add_filter( 'bookingpress_add_global_option_data', array( $this, 'bookingpress_add_global_option_data_func' ), 11 );
				
				add_filter( 'bookingpress_dynamic_next_page_request_filter', array( $this, 'bookingpress_set_extra_service_price' ), 8, 1 );

				add_action('bookingpress_add_appointment_model_reset',array($this,'bookingpress_add_appointment_model_reset_func'),11);

				add_action( 'bookingpress_dashboard_add_appointment_model_reset', array($this, 'bookingpress_add_appointment_model_reset_func'),11);
				
				add_action('bookingpress_calendar_add_appointment_model_reset', array( $this, 'bookingpress_add_appointment_model_reset_func'),11);

				add_action('bookingpress_change_backend_service', array($this, 'bookingpress_change_backend_extra_service_func'));

				add_filter('bookingpress_modified_book_again_page_url',array($this,'bookingpress_modified_book_again_page_url_func'),20,2);

				add_filter( 'bookingpress_step_navigation_before_validation', array( $this, 'bookingpress_extra_service_validation') );
				add_action('bookingpress_add_service_validation', array( $this, 'bookingpress_add_extra_service_validation_func'));

				if(is_plugin_active('bookingpress-multilanguage/bookingpress-multilanguage.php')) {
					add_filter('bookingpress_modified_language_translate_fields',array($this,'bookingpress_modified_language_translate_fields_func'),10);
                	add_filter('bookingpress_modified_customize_form_language_translate_fields',array($this,'bookingpress_modified_language_translate_fields_extra_func'),10);
				}

				add_action('bookingpress_add_posted_data_for_save_service',array($this,'bookingpress_add_posted_data_for_save_service_func'),10);
			}
		}
		function bookingpress_after_activate_bookingpress_module_func( $addon_key ){

			if( $addon_key == 'bookingpress_service_extra_module'){

				global $BookingPress;

				$bookingpress_background_color = $BookingPress->bookingpress_get_customize_settings('background_color', 'booking_form');
				$bookingpress_footer_background_color = $BookingPress->bookingpress_get_customize_settings('footer_background_color', 'booking_form');
				$bookingpress_primary_color = $BookingPress->bookingpress_get_customize_settings('primary_color', 'booking_form');
				$bookingpress_content_color = $BookingPress->bookingpress_get_customize_settings('content_color', 'booking_form');
				$bookingpress_label_title_color = $BookingPress->bookingpress_get_customize_settings('label_title_color', 'booking_form');
				$bookingpress_title_font_family = $BookingPress->bookingpress_get_customize_settings('title_font_family', 'booking_form');        
				$bookingpress_sub_title_color = $BookingPress->bookingpress_get_customize_settings('sub_title_color', 'booking_form');
				$bookingpress_price_button_text_color = $BookingPress->bookingpress_get_customize_settings('price_button_text_color', 'booking_form');    
				$bookingpress_primary_background_color = $BookingPress->bookingpress_get_customize_settings('primary_background_color', 'booking_form');
				$bookingpress_border_color= $BookingPress->bookingpress_get_customize_settings('border_color', 'booking_form');

				$bookingpress_background_color = !empty($bookingpress_background_color) ? $bookingpress_background_color : '#fff';
				$bookingpress_footer_background_color = !empty($bookingpress_footer_background_color) ? $bookingpress_footer_background_color : '#f4f7fb';
				$bookingpress_primary_color = !empty($bookingpress_primary_color) ? $bookingpress_primary_color : '#12D488';
				$bookingpress_content_color = !empty($bookingpress_content_color) ? $bookingpress_content_color : '#727E95';
				$bookingpress_label_title_color = !empty($bookingpress_label_title_color) ? $bookingpress_label_title_color : '#202C45';
				$bookingpress_title_font_family = !empty($bookingpress_title_font_family) ? $bookingpress_title_font_family : '';    
				$bookingpress_sub_title_color = !empty($bookingpress_sub_title_color) ? $bookingpress_sub_title_color : '#535D71';
				$bookingpress_price_button_text_color = !empty($bookingpress_price_button_text_color) ? $bookingpress_price_button_text_color : '#fff';    
				$bookingpress_primary_background_color = !empty($bookingpress_primary_background_color) ? $bookingpress_primary_background_color : '#e2faf1';
				$bookingpress_border_color = !empty($bookingpress_border_color) ? $bookingpress_border_color : '#CFD6E5';

				$booking_form = array(
					'background_color' => $bookingpress_background_color,
					'footer_background_color' => $bookingpress_footer_background_color,
					'primary_color' => $bookingpress_primary_color,
					'primary_background_color'=> $bookingpress_primary_background_color,
					'label_title_color' => $bookingpress_label_title_color,
					'title_font_family' => $bookingpress_title_font_family,                
					'content_color' => $bookingpress_content_color,                
					'price_button_text_color' => $bookingpress_price_button_text_color,
					'sub_title_color' => $bookingpress_sub_title_color,
					'border_color'         => $bookingpress_border_color,
				);
			
				$bookingpress_custom_data_arr['action'][] = 'bookingpress_save_booking_form_settings';
				$bookingpress_custom_data_arr['booking_form'] = $booking_form;
			
				$BookingPress->bookingpress_generate_customize_css_func($bookingpress_custom_data_arr);
			}
		}

		function bookingpress_add_posted_data_for_save_service_func(){
		?>
			postdata.extraServicesData = JSON.stringify(vm2.service.extraServicesData);
		<?php 
		}
		
		function bookingpress_add_extra_service_validation_func(){



			$bookingpress_extra_service_module_activate = $this->bookingpress_check_service_extra_module_activation(); 
			if( $bookingpress_extra_service_module_activate == 1 ){


				global $BookingPress;
				$bookingpress_extra_service_posted_data = (isset($_POST['extraServicesData']) && !empty($_POST['extraServicesData'])) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), json_decode(stripslashes_deep($_POST['extraServicesData']),true) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if(isset( $_POST['bookingpress_min_no_extra_service']) && $_POST['bookingpress_max_no_extra_service'] && $_POST['bookingpress_min_no_extra_service'] > $_POST['bookingpress_max_no_extra_service'] )  { // phpcs:ignore WordPress.Security.NonceVerification.Missing --Reason Nonce already verified from the caller function.				
					$response            = array();
					$response['variant'] = 'error';
					$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
					$response['msg']     = esc_html__('Extra Service min no. should not be greater than max no.', 'bookingpress-appointment-booking');
					wp_send_json($response);
					die();				
				}

				if(isset($bookingpress_extra_service_posted_data) && !empty($bookingpress_extra_service_posted_data)){
					$bookingpress_added_extra_service_count = count($bookingpress_extra_service_posted_data);
					if($_POST['bookingpress_max_no_extra_service'] != "nolimit" && $_POST['bookingpress_max_no_extra_service'] > $bookingpress_added_extra_service_count){
						$response            = array();
						$response['variant'] = 'error';
						$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
						$response['msg']     = esc_html__('Maximum no. of extra service could not greater than total no of extra services', 'bookingpress-appointment-booking');
						wp_send_json($response);
						die();	
					}
				}
			}
		}

		function bookingpress_extra_service_validation( $bookingpress_step_navigation_before_validation ){

			global $BookingPress;

			$bookingpress_select_min_extra_service_message = $BookingPress->bookingpress_get_settings('minimum_extra_service_selection', 'message_setting');
			$bookingpress_select_max_extra_service_message = $BookingPress->bookingpress_get_settings('maximum_allowed_extra_service', 'message_setting');

			$bookingpress_step_navigation_before_validation .= '
				if("service" == vm.bookingpress_current_tab){
					if( vm.bookingpress_is_extra_enable == "1" && typeof vm.appointment_step_form_data.selected_service != "" ){
						let selected_service_data = vm.bookingpress_all_services_data[ vm.appointment_step_form_data.selected_service ];
						if(typeof selected_service_data != "undefined") {
							let bpa_selected_service_extra_count = 0;
							for(var extra_key in vm.appointment_step_form_data.bookingpress_selected_extra_details){
								if(vm.appointment_step_form_data.bookingpress_selected_extra_details[extra_key].bookingpress_is_selected == true){
									bpa_selected_service_extra_count++;
								}
							}
							if("undefined" != selected_service_data.service_extras && "" != selected_service_data.service_extras && bpa_selected_service_extra_count < selected_service_data.bookingpress_min_no_extra_service){
								bookingpress_is_validate = 1;
								let errorMsg = "' . $bookingpress_select_min_extra_service_message . '";
								errorMsg = errorMsg.replace("[x]", selected_service_data.bookingpress_min_no_extra_service);
								vm.bookingpress_set_extra_service_error_msg(errorMsg);	
							}
							else if("undefined" != selected_service_data.service_extras && "" != selected_service_data.service_extras && bpa_selected_service_extra_count > selected_service_data.bookingpress_max_no_extra_service ){
								bookingpress_is_validate = 1;
								let errorMsg = "' . $bookingpress_select_max_extra_service_message . '";
								errorMsg = errorMsg.replace("[x]", selected_service_data.bookingpress_max_no_extra_service);
								vm.bookingpress_set_extra_service_error_msg(errorMsg);	
							}
						}
					}
				}				
			';

			return $bookingpress_step_navigation_before_validation;
		}


		/**
		 * Function for modified book again url
		 *
		 * @param  mixed $bookingpress_appointment_url
		 * @param  mixed $bookingpress_appointments_data
		 * @return void
		*/
		function bookingpress_modified_book_again_page_url_func($bookingpress_appointment_url, $bookingpress_appointments_data){

			$bookingpress_extra_service_details = (isset($bookingpress_appointments_data['bookingpress_extra_service_details']))?$bookingpress_appointments_data['bookingpress_extra_service_details']:'';
			if(!empty($bookingpress_extra_service_details)){
				$extra_service_data = '';
				$bookingpress_extra_service_details_arr_data = json_decode(stripslashes_deep($bookingpress_extra_service_details),true);
				if(is_array($bookingpress_extra_service_details_arr_data)){					
					foreach($bookingpress_extra_service_details_arr_data as $bookingpress_extra_service_details_arr){
						$bookingpress_extra_services_id = (isset($bookingpress_extra_service_details_arr['bookingpress_extra_service_details']['bookingpress_extra_services_id']))?$bookingpress_extra_service_details_arr['bookingpress_extra_service_details']['bookingpress_extra_services_id']:'';
						if($bookingpress_extra_services_id){
							$bookingpress_selected_qty = (isset($bookingpress_extra_service_details_arr['bookingpress_selected_qty']))?$bookingpress_extra_service_details_arr['bookingpress_selected_qty']:1;
							$extra_service_data.= $bookingpress_extra_services_id.'|'.$bookingpress_selected_qty.'~';
						}
					}
				}
				if(!empty($extra_service_data)){
					$bookingpress_appointment_url = add_query_arg( 'se_id',$extra_service_data,$bookingpress_appointment_url);
				}
			}
			return $bookingpress_appointment_url;
			
		}

		/**
		 * bpa function for get service extra list
		 *
		 * @return void
		 */
		function bookingpress_bpa_get_service_extras_func($user_detail = array()){
			global $BookingPress,$wpdb,$BookingPressPro,$tbl_bookingpress_appointment_bookings,$bookingpress_appointment_bookings;
			$result = array('bookingpress_selected_extra_details'=>array(),'bookingpress_service_extras'=>array());
			$response = array('status' => 0, 'message' => '', 'response' => array('result' => $result));
			if(class_exists('BookingPressPro') && method_exists( $BookingPressPro, 'bookingpress_bpa_check_valid_connection_callback_func') && $BookingPressPro->bookingpress_bpa_check_valid_connection_callback_func()){
				$user_detail = !empty($user_detail) ? array_map(array( $BookingPress, 'appointment_sanatize_field' ), $user_detail) : array();
				$service_extras = $this->bookingpress_get_all_service_extras();
				$result = $service_extras;
				$response = array('status' => 1, 'message' => '', 'response' => array('result' => $result));
			}
			return $response;
		}

		function bookingpress_get_all_service_extras(){
			global $wpdb,$BookingPress,$BookingPressPro,$tbl_bookingpress_extra_services;

			$bookingpress_selected_extra_details = $bookingpress_service_extras = array();	
			$bookingpress_service_extras = $wpdb->get_results("SELECT * FROM ".$tbl_bookingpress_extra_services, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_extra_services is a table name. false alarm
			if(!empty($bookingpress_service_extras)){
				foreach($bookingpress_service_extras as $k => $v){
					$bookingpress_service_extras[$k]['bookingpress_extra_formatted_price'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($v['bookingpress_extra_service_price']);
					$bookingpress_service_extras[$k]['bookingpress_is_display_description'] = 0;
					
					if(method_exists( $BookingPressPro, 'bookingpress_pro_front_language_translation_func') && isset($bookingpress_service_extras[$k]['bookingpress_extra_service_name'])) {
						$v['bookingpress_extra_service_name'] = $bookingpress_service_extras[$k]['bookingpress_extra_service_name'] = stripslashes_deep($BookingPressPro->bookingpress_pro_front_language_translation_func($bookingpress_service_extras[$k]['bookingpress_extra_service_name'],'service_extra','bookingpress_extra_service_name',$bookingpress_service_extras[$k]['bookingpress_extra_services_id']));  
					} 
					
					$bookingpress_selected_extra_details[$v['bookingpress_extra_services_id']] = array(
						'bookingpress_is_selected' => false,
						'bookingpress_selected_qty' => 1,
						'bookingpress_extra_price' => $v['bookingpress_extra_service_price'],
						'bookingpress_extra_name'  => $v['bookingpress_extra_service_name'],
						'bookingpress_extra_duration' => $v['bookingpress_extra_service_duration'].$v['bookingpress_extra_service_duration_unit'],
						'bookingpress_service_id'  => $v['bookingpress_service_id'],
					);
					$bookingpress_service_extras[$k]['bookingpress_hide_service_counter'] = false;
					if( !empty( $bookingpress_service_extras[$k]['bookingpress_extra_service_max_quantity'] ) && 1 == $bookingpress_service_extras[$k]['bookingpress_extra_service_max_quantity'] ){
						$bookingpress_service_extras[$k]['bookingpress_hide_service_counter'] = true;
					}					
				}
			}
			return array('bookingpress_selected_extra_details'=>$bookingpress_selected_extra_details,'bookingpress_service_extras'=>$bookingpress_service_extras);
		}

		function bookingpress_change_backend_extra_service_func() {
			?>
			vm.appointment_formdata.selected_extra_services_ids = '';
			for(m in vm.bookingpress_loaded_extras) {
				for(i in vm.bookingpress_loaded_extras[m]) {
					vm.bookingpress_loaded_extras[m][i]['bookingpress_is_selected'] = false;
				}					
			}			
			if(typeof vm.appointment_formdata.extras_total !== 'undefined'){
				vm.appointment_formdata.extras_total = 0;
			}
			if(typeof vm.appointment_formdata.extras_total !== 'undefined'){
				vm.appointment_formdata.extras_total_with_currency = vm.bookingpress_price_with_currency_symbol( 0 );	
			}
			<?php
		}

		function bookingpress_modified_language_translate_fields_extra_func($bookingpress_all_language_translation_fields){

			$bookingpress_extra_service_language_translation_fields = array(                
				'service_extra_title' => array('field_type'=>'text','field_label'=>__('Service extra title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),                 
			);  
			$bookingpress_all_language_translation_fields['customized_form_service_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_service_step_labels'], $bookingpress_extra_service_language_translation_fields);
			$bookingpress_extra_summary_language_translation_fields = array(                
				'service_extras_label' => array('field_type'=>'text','field_label'=>__('Service extras label', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),
			); 
			$bookingpress_all_language_translation_fields['customized_form_summary_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_summary_step_labels'], $bookingpress_extra_summary_language_translation_fields);

			return $bookingpress_all_language_translation_fields;
		}

		function bookingpress_modified_language_translate_fields_func($bookingpress_all_language_translation_fields){

			$bookingpress_extra_service_language_translation_fields = array(                
				'service_extra_title' => array('field_type'=>'text','field_label'=>__('Service extra title', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),                 
			);  
			$bookingpress_all_language_translation_fields['customized_form_service_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_service_step_labels'], $bookingpress_extra_service_language_translation_fields);
			$bookingpress_extra_summary_language_translation_fields = array(                
				'service_extras_label' => array('field_type'=>'text','field_label'=>__('Service extras label', 'bookingpress-appointment-booking'),'save_field_type'=>'booking_form'),
			); 
			$bookingpress_all_language_translation_fields['customized_form_summary_step_labels'] = array_merge($bookingpress_all_language_translation_fields['customized_form_summary_step_labels'], $bookingpress_extra_summary_language_translation_fields);


			$bookingpress_extra_service_message_translation_fields = array(                
				'minimum_extra_service_selection' => array('field_type'=>'text','field_label'=>__('Minimum required service extras message', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),

                'maximum_allowed_extra_service' => array('field_type'=>'text','field_label'=>__('Maximum allowed service extras message', 'bookingpress-appointment-booking'),'save_field_type'=>'message_setting'),                
			);						
			$bookingpress_all_language_translation_fields['message_setting'] = array_merge($bookingpress_all_language_translation_fields['message_setting'], $bookingpress_extra_service_message_translation_fields);

			return $bookingpress_all_language_translation_fields;
		}
		
		function bookingpress_add_appointment_model_reset_func() {
        ?>
			vm2.appointment_formdata.selected_extra_services_ids = '';
			for(m in vm2.bookingpress_loaded_extras) {
				for(i in vm2.bookingpress_loaded_extras[m]) {
					vm2.bookingpress_loaded_extras[m][i]['bookingpress_is_selected'] = false;
				}					
			}			
			if(typeof vm2.appointment_formdata.extras_total !== 'undefined'){
				vm2.appointment_formdata.extras_total = 0;
			}
			if(typeof vm2.appointment_formdata.extras_total !== 'undefined'){
				vm2.appointment_formdata.extras_total_with_currency = vm2.bookingpress_price_with_currency_symbol( 0 );	
			}
        <?php
        }

		/**
		 * Modify edit service data
		 *
		 * @param  mixed $response
		 * @param  mixed $service_id
		 * @return void
		 */
		function bookingpress_modify_edit_service_data_func($response,$service_id) {
			$response['extra_service_data']  = $this->bookingpress_get_extra_services_data_func($response['service_duration'],$response['service_duration_unit']);
			return $response;
		}
		
		/**
		 * Calculate service extras price when control moved to summary page ( without cart only )
		 *
		 * @param  mixed $bookingpress_dynamic_next_page_request_filter
		 * @return void
		 */
		function bookingpress_set_extra_service_price( $bookingpress_dynamic_next_page_request_filter ){

			$bookingpress_dynamic_next_page_request_filter .= '
				if( "summary" == next_tab && "summary" == vm.bookingpress_current_tab &&  bookingpress_is_validate == 0 ){
					
					if (typeof vm.appointment_step_form_data.cart_items == "undefined" || vm.appointment_step_form_data.cart_items.length == 0 ) {
						
						let selected_service_data = vm.bookingpress_all_services_data[ vm.appointment_step_form_data.selected_service ];
						if( "undefined" != typeof selected_service_data.enable_custom_service_duration && true == selected_service_data.enable_custom_service_duration ){

						} else {
						
							let service_extras = selected_service_data.service_extras;
							
							let service_extra_price = 0;
							for( let extra_id in service_extras ){
								if( "undefined" != typeof vm.appointment_step_form_data.bookingpress_selected_extra_details[ extra_id ] && true == vm.appointment_step_form_data.bookingpress_selected_extra_details[ extra_id ].bookingpress_is_selected ){
									let extra_price = parseFloat( service_extras[ extra_id ].bookingpress_extra_service_price );
									let extra_qty = vm.appointment_step_form_data.bookingpress_selected_extra_details[ extra_id ].bookingpress_selected_qty || 1;
									
									service_extra_price += ( extra_price * extra_qty );
								}
							}
							let total_payable_amount = vm.appointment_step_form_data.service_price_without_currency;
							if( true == vm.use_base_price_for_calculation ){
								total_payable_amount = vm.appointment_step_form_data.base_price_without_currency;
							}
														
							let final_price_with_extra = parseFloat( total_payable_amount ) + parseFloat( service_extra_price );
							vm.appointment_step_form_data.service_price_without_currency = final_price_with_extra;
							vm.appointment_step_form_data.selected_service_price = vm.bookingpress_price_with_currency_symbol( final_price_with_extra );
						}
					}
				}
			';

			return $bookingpress_dynamic_next_page_request_filter;
		}
		
		/**
		 * bookingpress_add_global_option_data_func
		 *
		 * @param  mixed $global_data
		 * @return void
		 */
		function bookingpress_add_global_option_data_func($global_data){

			$service_extra_activate = $this->bookingpress_check_service_extra_module_activation();
			if( $service_extra_activate == 1){

				$bookingpress_email_appointment_placeholders = json_decode($global_data['service_placeholders'], TRUE);
				$bookingpress_email_appointment_placeholders[] = array(
					'value' => '%service_extras%',
					'name' => '%service_extras%',
				);
				$global_data['service_placeholders'] = wp_json_encode($bookingpress_email_appointment_placeholders);
			}
			
            return $global_data;
        }
		
		/**
		 * Open drawer for service extras after selecting service ( after new service array changes );
		 *
		 * @param  mixed $bookingpress_before_selecting_booking_service_data
		 * @return void
		 */
		function bookingpress_before_selecing_booking_service_for_extras( $bookingpress_before_selecting_booking_service_data ){


			$bookingpress_before_selecting_booking_service_data .= '
				let current_selected_service = vm.appointment_step_form_data.selected_service;
				vm.bookingpress_remove_extra_service_error_msg();
				if( "undefined" != vm.bookingpress_all_services_data[ selected_service_id ].service_extras && "" != vm.bookingpress_all_services_data[ selected_service_id ].service_extras && vm.bookingpress_sidebar_step_data.service.is_display_step == 1 ){
					vm.bookingpress_open_extras_drawer = "true";
					document.body.classList.add( "--bpa-open-advance-drawer" );

					const params = new URLSearchParams(window.location.search);
					if (params.get("book_again") == 1 && vm.is_staffmember_activated == 1) {
						document.body.classList.remove("--bpa-open-advance-drawer");
					}

					vm.isServiceLoadTimeLoader = "0";
					vm.appointment_step_form_data.is_extra_service_exists = "1";
					is_drawer_opened = "true";
					is_move_to_next = false;
				} else {
					vm.appointment_step_form_data.is_extra_service_exists = "0";
				}
			';

			return $bookingpress_before_selecting_booking_service_data;
		}
		
		/**
		 * Function to filter all services array ( new ) and append services extras from it.
		 *
		 * @param  mixed $bpa_all_services
		 * @param  mixed $service
		 * @param  mixed $selected_service
		 * @param  mixed $bookingpress_category
		 * @return void
		 */
		function bookingpress_append_service_extras( $bpa_all_services, $service, $selected_service, $bookingpress_category ){

			global $wpdb, $tbl_bookingpress_extra_services, $BookingPress,$BookingPressPro, $bookingpress_services;

			if( !empty( $bpa_all_services ) ){
				$service_ids = array_keys( $bpa_all_services );
				foreach( $service_ids as $k => $sid ){
					$service_extras = array();
					$bookingpress_service_extra_min_no = $bookingpress_service_extra_max_no = 1;
					$get_service_extras = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tbl_bookingpress_extra_services WHERE bookingpress_service_id = %d", $sid ), ARRAY_A );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is a table name. false alarm
					if( !empty( $get_service_extras ) ){
						$service_extra_counter = 1;
						foreach( $get_service_extras as $service_extras_details ){

							if((is_plugin_active('bookingpress-multilanguage/bookingpress-multilanguage.php'))){
								if(method_exists( $BookingPressPro, 'bookingpress_pro_front_language_translation_func') ) {
								
									if(!empty($service_extras_details['bookingpress_extra_service_name'])){
										$service_extras_details['bookingpress_extra_service_name'] = $BookingPressPro->bookingpress_pro_front_language_translation_func($service_extras_details['bookingpress_extra_service_name'],'service_extra','bookingpress_extra_service_name',$service_extras_details['bookingpress_extra_services_id']);
									}
									if(!empty($service_extras_details['bookingpress_service_description'])){
										$service_extras_details['bookingpress_service_description'] = $BookingPressPro->bookingpress_pro_front_language_translation_func($service_extras_details['bookingpress_service_description'],'service_extra','bookingpress_service_description',$service_extras_details['bookingpress_extra_services_id']);
									}																			

								}
							}
							$service_extras_details['bookingpress_extra_formatted_price'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($service_extras_details['bookingpress_extra_service_price']);
							$service_extras_details['bookingpress_is_display_description'] = 0;
							$service_extras_details['bookingpress_hide_service_counter'] = ( !empty( $service_extras_details['bookingpress_extra_service_max_quantity'] ) && 1 == $service_extras_details['bookingpress_extra_service_max_quantity'] ) ? true : false;
							$service_extras_details['bookingpress_extra_counter'] = $service_extra_counter;
							$service_extras[ $service_extras_details['bookingpress_extra_services_id'] ] = $service_extras_details;
							$service_extra_counter++;
						}
						$bookingpress_service_extra_min_no = $this->bookingpress_get_service_extra_min_no( $sid );
						$bookingpress_service_extra_max_no = $this->bookingpress_get_service_extra_max_no( $sid );

					} else {
						$service_extras = array();
					}
					$bpa_all_services[ $sid ]['service_extras'] = $service_extras;
					$bpa_all_services[ $sid ]['bookingpress_min_no_extra_service'] = $bookingpress_service_extra_min_no;
					$bpa_all_services[ $sid ]['bookingpress_max_no_extra_service'] = $bookingpress_service_extra_max_no;
				}
			}
			
			return $bpa_all_services;
		}

		function bookingpress_get_service_extra_max_no( $bookingpress_service_id ) {
			global $bookingpress_services;
			$bookingpress_max_no = 'nolimit';
			if ( ! empty( $bookingpress_service_id ) ) {
				$bookingpress_tmp_max_no = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'bookingpress_max_no_extra_service' );
				$bookingpress_max_no     = ! empty( $bookingpress_tmp_max_no ) ? $bookingpress_tmp_max_no : 'nolimit';
			}
			return $bookingpress_max_no;
		}

		function bookingpress_get_service_extra_min_no( $bookingpress_service_id ) {
			global $bookingpress_services;
			$bookingpress_min_no = 0;
			if ( ! empty( $bookingpress_service_id ) ) {
				$bookingpress_tmp_min_no = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'bookingpress_min_no_extra_service' );
				$bookingpress_min_no     = isset( $bookingpress_tmp_min_no ) ? $bookingpress_tmp_min_no : 0;
			}
			return $bookingpress_min_no;
		}

		function bookingpress_format_assigned_service_extra_amounts_func(){
			global $wpdb, $bookingpress_global_options, $BookingPress;
			$response                    = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'format_service_extra_amount', true, 'bpa_wp_nonce' );           
			if( preg_match( '/error/', $bpa_check_authorization ) ){
				$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
				$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

				$response['variant'] = 'error';
				$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
				$response['msg'] = $bpa_error_msg;

				wp_send_json( $response );
				die;
			}

			$bookingpress_assign_extra_service_list = ! empty( $_POST['extraServicesData'] ) ? array_map(array( $BookingPress, 'appointment_sanatize_field' ), $_POST['extraServicesData']) : array(); //phpcs:ignore

			if(!empty($bookingpress_assign_extra_service_list)){
				foreach($bookingpress_assign_extra_service_list as $assign_service_list_key => $assign_service_list_val){
					$bookingpress_assign_extra_service_list[$assign_service_list_key]['extra_service_titles'] = stripslashes_deep($assign_service_list_val['extra_service_titles']);
					$bookingpress_assign_extra_service_list[$assign_service_list_key]['extra_service_descriptions'] = stripslashes_deep($assign_service_list_val['extra_service_descriptions']);
					$bookingpress_assign_extra_service_list[$assign_service_list_key]['extra_service_prices_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($assign_service_list_val['extra_service_prices']);
				}
				$response['variant'] = 'success';
				$response['title'] = esc_html__('Success', 'bookingpress-appointment-booking');
				$response['msg'] = esc_html__('Assigned extra service formatted successfully', 'bookingpress-appointment-booking');
				$response['extraServicesData'] = $bookingpress_assign_extra_service_list;
			}

			echo wp_json_encode($response);
			exit;
		}

		function bookingpress_set_extras_appointment_xhr_data(){
			?>
			if( "undefined" != typeof vm.bookingpress_loaded_extras && "undefined" != typeof vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service] ){
				let bpa_selected_extras = {};
				vm.bookingpress_loaded_extras[vm.appointment_formdata.appointment_selected_service].forEach(function( element ){
					let is_selected = element.bookingpress_is_selected;
					if( "true" == is_selected || true == is_selected ){
						bpa_selected_extras[ element.bookingpress_extra_services_id ] = {
							"bookingpress_is_selected":"true",
							"bookingpress_selected_qty":element.bookingpress_selected_qty
						};
					}
				});
				postData.appointment_data_obj.bookingpress_selected_extra_details = bpa_selected_extras;
			}
			<?php
		}

		function bookingpress_edit_service_more_vue_data_func() {
			?>	
			//vm2.loadExtraServicesData()
			vm2.service.extraServicesData = response.data.extra_service_data;
			vm2.updateExtraServiceSelection();
			vm2.service.bookingpress_min_no_extra_service = (response.data.bookingpress_min_no_extra_service !== undefined) ? response.data.bookingpress_min_no_extra_service : '0';
			vm2.service.bookingpress_max_no_extra_service = (response.data.bookingpress_max_no_extra_service !== undefined) ? response.data.bookingpress_max_no_extra_service : 'nolimit';
			<?php
		}
		function bookingpress_after_reset_add_service_form_func() {
			?>
				this.service.extraServicesData= [];
				this.service.bookingpress_min_no_extra_service= '0';
				this.service.bookingpress_max_no_extra_service= 'nolimit';
			<?php
		}

		function bookingpress_customize_add_dynamic_data_fields_func($bookingpress_customize_vue_data_fields) {
			$bookingpress_customize_vue_data_fields['sevice_container_data']['service_extra_title'] = '';			
			return $bookingpress_customize_vue_data_fields;
		}

		function bookingpress_get_booking_form_customize_data_filter_func($booking_form_settings){
			$booking_form_settings['service_container_data']['service_extra_title'] = __('Select Service Extras', 'bookingpress-appointment-booking');		
			return $booking_form_settings;
		}

		function bookingpress_frontend_apointment_form_add_dynamic_data_func($bookingpress_front_vue_data_fields){
			global $BookingPress, $bookingpress_services;
			$service_extra_title = $BookingPress->bookingpress_get_customize_settings('service_extra_title', 'booking_form');
			$bookingpress_front_vue_data_fields['service_extra_title'] = !empty($service_extra_title) ? stripslashes_deep($service_extra_title) : '';	
			$bookingpress_front_vue_data_fields['is_display_extra_service_error'] = 0;				
			$bookingpress_front_vue_data_fields['extra_service_error_msg'] = "";				
			return $bookingpress_front_vue_data_fields;
		}

		function bookingpress_save_service_details( $response, $service_id, $posted_data ) {
			global $wpdb,$tbl_bookingpress_extra_services,$BookingPress, $bookingpress_services;


			$posted_data['extraServicesData'] = (isset($posted_data['extraServicesData']) && !empty($posted_data['extraServicesData'])) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), json_decode(stripslashes_deep($posted_data['extraServicesData']),true) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized			

			if ( ! empty( $service_id ) && ! empty( $posted_data ) && !empty($posted_data['extraServicesData']) ) {

				$extra_service_min_no = isset( $posted_data['bookingpress_min_no_extra_service'] ) ? $posted_data['bookingpress_min_no_extra_service'] : 0;
				if (	isset( $extra_service_min_no ) ) {
					$bookingpress_services->bookingpress_add_service_meta( $service_id, 'bookingpress_min_no_extra_service', $extra_service_min_no );
				}

				$extra_service_max_no = ! empty( $posted_data['bookingpress_max_no_extra_service'] ) ? $posted_data['bookingpress_max_no_extra_service'] : 1;
				if ( ! empty( $extra_service_max_no ) ) {
					$bookingpress_services->bookingpress_add_service_meta( $service_id, 'bookingpress_max_no_extra_service', $extra_service_max_no );
				}

				// Update extra services data
				$extra_ids = array_column($posted_data['extraServicesData'], 'extra_service_ids');
				if(!empty($extra_ids) && is_array($extra_ids)) {
					$extra_ids = implode(',',$extra_ids);
					$wpdb->query($wpdb->prepare("DELETE FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_extra_services_id NOT IN($extra_ids) AND bookingpress_service_id = %d", $service_id));  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is table name.
				} else {
					$wpdb->delete( $tbl_bookingpress_extra_services, array( 'bookingpress_service_id' => $service_id ) );
				}
				if ( ! empty( $posted_data['extraServicesData'] ) ) {
					$bookingpress_extra_service_data = array_map( array( $BookingPress, 'appointment_sanatize_field' ), $posted_data['extraServicesData'] );										
					foreach ( $bookingpress_extra_service_data as $extra_service_key => $extra_service_val ) {																		
						$extra_service_ids 			 = ! empty( $extra_service_val['extra_service_ids'] ) ? $extra_service_val['extra_service_ids'] : 0;
						$extra_service_title         = ! empty( $extra_service_val['extra_service_titles'] ) ? stripslashes_deep($extra_service_val['extra_service_titles']) : '';
						$extra_service_duration      = ! empty( $extra_service_val['extra_service_durations'] ) ? $extra_service_val['extra_service_durations'] : '';
						$extra_service_duration_unit = ! empty( $extra_service_val['extra_service_duration_units'] ) ? $extra_service_val['extra_service_duration_units'] : '';
						$extra_service_price         = ! empty( $extra_service_val['extra_service_prices'] ) ? floatval( $extra_service_val['extra_service_prices'] ) : 0;
						$extra_service_min_quantity  =  isset( $extra_service_val['extra_service_minimum_quantitys'] ) ? $extra_service_val['extra_service_minimum_quantitys'] : '';
						$extra_service_max_quantity  = ! empty( $extra_service_val['extra_service_maximum_quantitys'] ) ? $extra_service_val['extra_service_maximum_quantitys'] : '';
						$extra_service_description   = ! empty( $extra_service_val['extra_service_descriptions'] ) ? stripslashes_deep($extra_service_val['extra_service_descriptions']) : '';
						if(empty($extra_service_ids) ) {
							$wpdb->insert(
								$tbl_bookingpress_extra_services,
								array(
									'bookingpress_service_id' => $service_id,
									'bookingpress_extra_service_name' => $extra_service_title,
									'bookingpress_extra_service_duration' => $extra_service_duration,
									'bookingpress_extra_service_duration_unit' => $extra_service_duration_unit,
									'bookingpress_extra_service_price' => $extra_service_price,
									'bookingpress_extra_service_min_quantity' => $extra_service_min_quantity,
									'bookingpress_extra_service_max_quantity' => $extra_service_max_quantity,
									'bookingpress_service_description' => $extra_service_description,
								)
							);
							$extra_service_ids = $wpdb->insert_id;
						} else {
							$wpdb->update(
								$tbl_bookingpress_extra_services,
								array(
									'bookingpress_service_id' => $service_id,
									'bookingpress_extra_service_name' => $extra_service_title,
									'bookingpress_extra_service_duration' => $extra_service_duration,
									'bookingpress_extra_service_duration_unit' => $extra_service_duration_unit,
									'bookingpress_extra_service_price' => $extra_service_price,
									'bookingpress_extra_service_min_quantity' => $extra_service_min_quantity,
									'bookingpress_extra_service_max_quantity' => $extra_service_max_quantity,
									'bookingpress_service_description' => $extra_service_description,
								),
								array( 'bookingpress_extra_services_id' => $extra_service_ids) 
							);
						}
						do_action('bookingpress_after_save_service_extra',$extra_service_ids,$extra_service_key,$posted_data);
					}
				}
			}else if ( ! empty( $service_id ) && ! empty( $posted_data ) && empty($posted_data['extraServicesData']) ) {
				//If there is only one extra service exist and that extra service delete then this condition executes
				$bookingpress_existing_extras_counter = $wpdb->query($wpdb->prepare("SELECT COUNT(bookingpress_extra_services_id) as total FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_service_id = %d", $service_id));  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is table name.

				if($bookingpress_existing_extras_counter > 0){
					$wpdb->query($wpdb->prepare("DELETE FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_service_id = %d", $service_id));  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services 
				}
			}
			return $response;
		}

		function bookingpress_check_service_extra_module_activation() {
			$is_service_extra_module_activated = 0;
			$service_extra_addon_option_val    = get_option( 'bookingpress_service_extra_module' );
			if ( ! empty( $service_extra_addon_option_val ) && ( $service_extra_addon_option_val == 'true' ) ) {
				$is_service_extra_module_activated = 1;
			}
			return $is_service_extra_module_activated;
		}

		function bookingpress_modify_service_data_fields_func( $bookingpress_services_vue_data_fields ) {

			$bookingpress_services_vue_data_fields['service_extra_inputs_form'] = array(
				'extra_service_title'            => '',
				'extra_service_duration'         => '0',
				'extra_service_duration_unit'    => 'm',
				'extra_service_price'            => '',
				'extra_service_minimum_quantity' => '1',
				'extra_service_maximum_quantity' => '1',
				'extra_service_description'      => '',
			);

			$bookingpress_services_vue_data_fields['service']['extraServicesData']  = array();
			$bookingpress_services_vue_data_fields['service']['bookingpress_min_no_extra_service'] = 0;    
			$bookingpress_services_vue_data_fields['service']['bookingpress_max_no_extra_service'] = 'nolimit';    

			$bookingpress_services_vue_data_fields['min_no_of_extra_service_option'] = array(
				array(
					'text'  => '0',
					'value' => '0',
				),							
			);

			$bookingpress_services_vue_data_fields['max_no_of_extra_service_option'] = array(
				array(
					'text'  => __( 'No Limit', 'bookingpress-appointment-booking' ),
					'value' => 'nolimit',
				)								
			);

			$bookingpress_services_vue_data_fields['open_add_extra_services_modal'] = false;
			$bookingpress_services_vue_data_fields['extra_service_modal_pos']       = '200';
			$bookingpress_services_vue_data_fields['extra_service_modal_pos_right'] = '0';

			$bookingpress_services_vue_data_fields['serviceExtraInputRules'] = array(
				'extra_service_title'    => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please enter service title', 'bookingpress-appointment-booking' ),
						'trigger'  => 'blur',
					),
				),
				'extra_service_duration' => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please select service duration', 'bookingpress-appointment-booking' ),
						'trigger'  => 'blur',
					),
				),
				'extra_service_price'    => array(
					array(
						'required' => true,
						'message'  => esc_html__( 'Please enter service price', 'bookingpress-appointment-booking' ),
						'trigger'  => 'blur',
					),
				),
			);		
			return $bookingpress_services_vue_data_fields;
		}

		function bookingpress_get_extra_services_data_func() {
			global $wpdb, $BookingPress, $tbl_bookingpress_extra_services;
			$response = array();
			if(!empty($_POST['action']) && $_POST['action'] == 'bookingpress_get_extra_service_data' )  {
				$bpa_check_authorization = $this->bpa_check_authentication( 'get_extra_services_data', true, 'bpa_wp_nonce' );            
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

			$wpnonce               = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$bpa_verify_nonce_flag = wp_verify_nonce( $wpnonce, 'bpa_wp_nonce' );
			if ( ! $bpa_verify_nonce_flag ) {
				$response                       = array();
				$response['variant']            = 'error';
				$response['title']              = esc_html__( 'Error', 'bookingpress-appointment-booking' );
				$response['msg']                = esc_html__( 'Sorry, Your request can not be processed due to security reason.', 'bookingpress-appointment-booking' );
				$resposne['extra_service_data'] = array();
				echo wp_json_encode( $response );
				die();
			}

			$response['variant']            = 'error';
			$response['title']              = esc_html__( 'Error', 'bookingpress-appointment-booking' );
			$response['msg']                = esc_html__( 'Something went wrong..', 'bookingpress-appointment-booking' );
			$response['extra_service_data'] = array();

			$service_id                              = ! empty( $_REQUEST['service_id'] ) ? intval( $_REQUEST['service_id'] ) : 0;
			$bookingpress_extra_service_modified_arr = array();
			if ( ! empty( $service_id ) ) {
				$bookingpress_extra_services_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_service_id = %d", $service_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is a table name. false alarm

				if ( ! empty( $bookingpress_extra_services_data ) ) {
					$bookingpress_counter = 1;
					foreach ( $bookingpress_extra_services_data as $extra_service_key => $extra_service_val ) {
						$bookingpress_extra_service_modified_arr[] = array(
							'id'                           => $bookingpress_counter,
							'extra_service_ids'            => $extra_service_val['bookingpress_extra_services_id'],
							'extra_service_titles'         => stripslashes_deep($extra_service_val['bookingpress_extra_service_name']),
							'extra_service_durations'      => $extra_service_val['bookingpress_extra_service_duration'],
							'extra_service_duration_units' => $extra_service_val['bookingpress_extra_service_duration_unit'],
							'extra_service_prices'         => $extra_service_val['bookingpress_extra_service_price'],
							'extra_service_prices_with_currency' => $BookingPress->bookingpress_price_formatter_with_currency_symbol($extra_service_val['bookingpress_extra_service_price']),
							'extra_service_maximum_quantitys' => $extra_service_val['bookingpress_extra_service_max_quantity'],
							'extra_service_minimum_quantitys' => $extra_service_val['bookingpress_extra_service_min_quantity'],
							'extra_service_descriptions'   => stripslashes_deep($extra_service_val['bookingpress_service_description']),
						);
						$bookingpress_counter++;
					}
					$response['variant']            = 'success';
					$response['title']              = esc_html__( 'Success', 'bookingpress-appointment-booking' );
					$response['msg']                = esc_html__( 'Extra services retrieved successfully', 'bookingpress-appointment-booking' );
					$response['extra_service_data'] = $bookingpress_extra_service_modified_arr;
				}
			}
			if(!empty($_POST['action']) && $_POST['action'] == 'bookingpress_get_extra_service_data' )  {
				echo wp_json_encode( $response );
				die();				
			} else {
				return $bookingpress_extra_service_modified_arr;
			}
		}

		function bookingpress_dynamic_add_params_for_timeslot_request_service_extra( $bookingpress_dynamic_add_params_for_timeslot_request ){
			$bookingpress_dynamic_add_params_for_timeslot_request .= 'postData.service_extra_details = JSON.stringify( vm.appointment_step_form_data.bookingpress_selected_extra_details );';
			return $bookingpress_dynamic_add_params_for_timeslot_request;
		}

		function bookingpress_modify_service_timeslot_with_service_extras( $default_time_slot, $service_id, $service_time_duration_unit, $bpa_fetch_data = false ){
			
			global $wpdb, $tbl_bookingpress_extra_services, $BookingPress;

			$bookingpress_service_extras = array();
			
			if( !empty( $_POST['appointment_data_obj']['bookingpress_selected_extra_details'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Missing --Reason Nonce already verified from the caller function.
				$bookingpress_service_extras = array_map( array( $BookingPress, 'appointment_sanatize_field'), $_POST['appointment_data_obj']['bookingpress_selected_extra_details'] ); // phpcs:ignore
			} else if( !empty( $_POST['service_extra_details'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Missing --Reason Nonce already verified from the caller function.
				if( !is_array( $_POST['service_extra_details'] ) ){ // phpcs:ignore WordPress.Security.NonceVerification.Missing --Reason Nonce already verified from the caller function.
					$_POST['service_extra_details'] = json_decode( stripslashes_deep( $_POST['service_extra_details'] ), true ); //phpcs:ignore
					$_POST['service_extra_details'] =  !empty($_POST['service_extra_details']) ? array_map(array($this,'bookingpress_boolean_type_cast'), $_POST['service_extra_details'] ) : array(); // phpcs:ignore
				}
				$bookingpress_service_extras = array_map( array( $BookingPress, 'appointment_sanatize_field'), $_POST['service_extra_details'] );  // phpcs:ignore
			}


			if( !empty( $service_id ) && !empty( $bookingpress_service_extras ) ){

				$selected_extra_service_ids = array();
				$extra_selected_services = $bookingpress_service_extras;
				
				if( !empty( $extra_selected_services ) ){
					foreach( $extra_selected_services as $extra_service_id => $extra_service_data ){
						if( 'true' == $extra_service_data['bookingpress_is_selected'] ){
							$selected_extra_service_ids[ $extra_service_id ] = $extra_service_data['bookingpress_selected_qty'];
						}
					}
				}
				
				$time_end_extra = 0;
				
				if( !empty( $selected_extra_service_ids ) ){
					
					foreach( $selected_extra_service_ids as $extra_service_id => $service_qty ){
						$bpa_service_extra_time_data = $wpdb->get_row( $wpdb->prepare( 'SELECT bookingpress_extra_service_duration, bookingpress_extra_service_duration_unit, bookingpress_extra_service_max_quantity FROM `'.$tbl_bookingpress_extra_services.'` WHERE bookingpress_service_id = %d AND bookingpress_extra_services_id = %d', $service_id, $extra_service_id ) );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_extra_services is table name.
						
						if( !empty( $bpa_service_extra_time_data ) ){
							$extra_service_duration = $bpa_service_extra_time_data->bookingpress_extra_service_duration;
							$extra_service_duration_unit = $bpa_service_extra_time_data->bookingpress_extra_service_duration_unit;
							$extra_service_max_qty = $bpa_service_extra_time_data->bookingpress_extra_service_max_quantity;
							if( $extra_service_max_qty < $service_qty ){
								$service_qty = $extra_service_max_qty;
							}
							
							if( $extra_service_duration_unit == 'h' ){
								$extra_service_duration = $extra_service_duration * 60;
							}
							$extra_service_total_duraction = $extra_service_duration * $service_qty;
							$time_end_extra = $time_end_extra + $extra_service_total_duraction;
						}
					}
				}
				
				$default_time_slot += $time_end_extra;
			}
			

			return $default_time_slot;
		}

		function bookingpress_add_service_dynamic_vue_methods_func() {
			?>
			loadExtraServicesData(){
				const vm = this
				var extra_service_post_data = {}
				extra_service_post_data.action = 'bookingpress_get_extra_service_data'
				extra_service_post_data.service_id = vm.service.service_update_id
				extra_service_post_data._wpnonce = '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>'
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( extra_service_post_data ) )
				.then(function(response){
					vm.service.extraServicesData = response.data.extra_service_data
				}).catch(function(error){
					console.log(error)
				});
			},
			deleteExtraService(index) {
				this.service.extraServicesData.splice(index,1)
				this.updateExtraServiceSelection();
			},
			updateExtraServiceSelection() {
				const vm1 = this
				let totalExtras = vm1.service.extraServicesData.length;
				let options = [];
				options.push({'text': '<?php esc_html_e('No Limit', 'bookingpress-appointment-booking'); ?>','value': 'nolimit'});
				for (let i = 1; i <= totalExtras; i++) {
					options.push({
						'text': i,
						'value': i
					});
				}

				let minOptions = [{ 'text': '0', 'value': '0' }]; // Adding 0 for min option
				for (let i = 1; i <= totalExtras; i++) {
					minOptions.push({
						'text': i,
						'value': i
					});
				}
				if (vm1.service.bookingpress_min_no_extra_service !== undefined){
					if(vm1.service.bookingpress_min_no_extra_service > totalExtras){
						vm1.service.bookingpress_min_no_extra_service = '0';
					}
				}
				if (vm1.service.bookingpress_max_no_extra_service !== undefined){
					if(vm1.service.bookingpress_max_no_extra_service > totalExtras){
						vm1.service.bookingpress_max_no_extra_service = 'nolimit';
					}
				}

				vm1.min_no_of_extra_service_option = minOptions;
    			vm1.max_no_of_extra_service_option = options;
			},
			resetExtraServiceForm(){
				const vm = this
				vm.service_extra_inputs_form.extra_service_title = ''
				vm.service_extra_inputs_form.extra_service_duration = '0'
				vm.service_extra_inputs_form.extra_service_duration_unit = 'm'
				vm.service_extra_inputs_form.extra_service_price = ''
				vm.service_extra_inputs_form.extra_service_minimum_quantity = '1'
				vm.service_extra_inputs_form.extra_service_maximum_quantity = '1'
				vm.service_extra_inputs_form.extra_service_description = ''; 						
			},
			open_extra_services_modal(currentElement){
				const vm = this
				vm.resetExtraServiceForm()
				vm.bookingpress_update_index = ''
				var dialog_pos = currentElement.target.getBoundingClientRect();
				vm.extra_service_modal_pos = (dialog_pos.top - 90)+'px'
				vm.extra_service_modal_pos_right = '-'+(dialog_pos.right - 430)+'px';				
				vm.open_add_extra_services_modal = true
				setTimeout(function(){					
					vm.$refs['service_extra_inputs_form'].resetFields();
				},100);

				if( typeof vm.bpa_adjust_popup_position != 'undefined' ){
					vm.bpa_adjust_popup_position( currentElement, 'div#service_extras_modal .el-dialog.bpa-dialog--add-extra-service');
				}

			},
			editExtraService(currentElement, edit_index){
				const vm = this
				var dialog_pos = currentElement.target.getBoundingClientRect();
				vm.extra_service_modal_pos = (dialog_pos.top - 90)+'px'
				vm.extra_service_modal_pos_right = '-'+(dialog_pos.right - 470)+'px';
				vm.open_add_extra_services_modal = true
				vm.bookingpress_update_index = edit_index
				vm.service.extraServicesData.forEach(function(currentValue, index, arr){
					if(edit_index == index){
						vm.service_extra_inputs_form.extra_service_title = currentValue.extra_service_titles
						vm.service_extra_inputs_form.extra_service_duration = currentValue.extra_service_durations
						vm.service_extra_inputs_form.extra_service_duration_unit = currentValue.extra_service_duration_units
						vm.service_extra_inputs_form.extra_service_price = currentValue.extra_service_prices
						vm.service_extra_inputs_form.extra_service_maximum_quantity = currentValue.extra_service_maximum_quantitys
						vm.service_extra_inputs_form.extra_service_minimum_quantity = currentValue.extra_service_minimum_quantitys
						vm.service_extra_inputs_form.extra_service_description = currentValue.extra_service_descriptions 

					}
				});
				
				if( typeof vm.bpa_adjust_popup_position != 'undefined' ){
					vm.bpa_adjust_popup_position( currentElement, 'div#service_extras_modal .el-dialog.bpa-dialog--add-extra-service');
				}

			},
			close_extra_services_modal(){
				const vm = this
				vm.$refs['service_extra_inputs_form'].resetFields();
				vm.resetExtraServiceForm()				
				vm.open_add_extra_services_modal = false
			},
			saveServiceExtraDetails(){
				const vm = this
				var serviceExtraForm = 'service_extra_inputs_form'
				this.$refs[serviceExtraForm].validate((valid) => {
					if (valid) {
						var bookingpress_appointment_time_minutes = 0;
						var bookingpress_extra_service_time_minutes = 0;

						if(vm.service.service_duration_unit == 'd'){
							bookingpress_appointment_time_minutes = Math.floor(parseFloat(vm.service.service_duration_val) * 24 * 60);
						}else if(vm.service.service_duration_unit == 'h'){
							bookingpress_appointment_time_minutes = Math.floor(parseFloat(vm.service.service_duration_val) * 60);
						}else if(vm.service.service_duration_unit == 'm'){
							bookingpress_appointment_time_minutes = Math.floor(parseFloat(vm.service.service_duration_val));
						}

						if(vm.service_extra_inputs_form.extra_service_duration_unit == 'd'){
							bookingpress_extra_service_time_minutes = Math.floor(parseFloat(vm.service_extra_inputs_form.extra_service_duration) * 24 * 60);
						}else if(vm.service_extra_inputs_form.extra_service_duration_unit == 'h'){
							bookingpress_extra_service_time_minutes = Math.floor(parseFloat(vm.service_extra_inputs_form.extra_service_duration) * 60);
						}else if(vm.service_extra_inputs_form.extra_service_duration_unit == 'm'){
							bookingpress_extra_service_time_minutes = Math.floor(parseFloat(vm.service_extra_inputs_form.extra_service_duration));
						}

						 if(vm.service_extra_inputs_form.extra_service_maximum_quantity < vm.service_extra_inputs_form.extra_service_minimum_quantity){
							vm.$notify({
								title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
								message: '<?php esc_html_e( 'Service extra maximum qunatity should not less than minimum qunatity', 'bookingpress-appointment-booking' ); ?>',
								type: 'error',
								customClass: 'error_notification',
							});
						}
						else if(vm.service_extra_inputs_form.extra_service_minimum_quantity > vm.service_extra_inputs_form.extra_service_maximum_quantity){
							vm.$notify({
								title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
								message: '<?php esc_html_e( 'Service extra minimum qunatity should not grater than maximum qunatity', 'bookingpress-appointment-booking' ); ?>',
								type: 'error',
								customClass: 'error_notification',
							});
						}						
						else{
							if(vm.bookingpress_update_index !== ''){
								vm.service.extraServicesData.forEach(function(currentValue, index, arr){
									if(vm.bookingpress_update_index == index){
										currentValue.extra_service_titles = vm.service_extra_inputs_form.extra_service_title
										currentValue.extra_service_durations = vm.service_extra_inputs_form.extra_service_duration
										currentValue.extra_service_duration_units = vm.service_extra_inputs_form.extra_service_duration_unit
										currentValue.extra_service_prices = vm.service_extra_inputs_form.extra_service_price
										currentValue.extra_service_minimum_quantitys = vm.service_extra_inputs_form.extra_service_minimum_quantity
										currentValue.extra_service_maximum_quantitys = vm.service_extra_inputs_form.extra_service_maximum_quantity
										currentValue.extra_service_descriptions = vm.service_extra_inputs_form.extra_service_description
									}
								});
							}else{
								vm.service_extra_inputs_form.id = (vm.service.extraServicesData.length) + 1
								vm.service_extra_inputs_form.extra_service_id = 0
								var extra_services_data = vm.service_extra_inputs_form							
								var ilength = parseInt(vm.service.extraServicesData.length) + 1;
								let ServiceExtra = {};
								Object.assign(ServiceExtra, {id: ilength})
								Object.assign(ServiceExtra, {extra_service_descriptions: vm.service_extra_inputs_form.extra_service_description})
								Object.assign(ServiceExtra, {extra_service_durations: vm.service_extra_inputs_form.extra_service_duration})
								Object.assign(ServiceExtra, {extra_service_duration_units: vm.service_extra_inputs_form.extra_service_duration_unit})
								Object.assign(ServiceExtra, {extra_service_ids: vm.service_extra_inputs_form.extra_service_id})
								Object.assign(ServiceExtra, {extra_service_prices: vm.service_extra_inputs_form.extra_service_price})
								Object.assign(ServiceExtra, {extra_service_minimum_quantitys: vm.service_extra_inputs_form.extra_service_minimum_quantity})
								Object.assign(ServiceExtra, {extra_service_maximum_quantitys: vm.service_extra_inputs_form.extra_service_maximum_quantity})
								Object.assign(ServiceExtra, {extra_service_titles: vm.service_extra_inputs_form.extra_service_title})							
								vm.service.extraServicesData.push(ServiceExtra)
							}
							vm.close_extra_services_modal()
							vm.bookinpgress_service_extra_format();
						}					
					}
				});				
			},	
			bookinpgress_service_extra_format() {
				const vm = this;
				var bookingpress_format_assigned_service_amts = { action:'bookingpress_format_assigned_service_extra_amounts', extraServicesData : vm.service.extraServicesData, _wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>' }
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( bookingpress_format_assigned_service_amts ) )
				.then(function(response) {
					vm.service.extraServicesData = response.data.extraServicesData;
					vm.updateExtraServiceSelection();
				}).catch(function(error){
					console.log(error);
					vm.$notify({
						title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
						message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
						type: 'error',
						customClass: 'error_notification',
					});
				});
			},
			is_extra_service_price_validate(evt) {
				if(evt != '') {
					const regex = /^(?!.*(,,|,\.|\.,|\.\.))[\d.,]+$/gm;
					let m;
					if((m = regex.exec(evt)) == null ) {
						this.service_extra_inputs_form.extra_service_price = '';
					}
				}
			},
			service_extra_name_validation(value){
				const vm = this;
				vm.service_extra_inputs_form.extra_service_title = value.trim();				
			},
			<?php
		}
	}

	global $bookingpress_service_extra;
	$bookingpress_service_extra = new bookingpress_service_extra();
}

