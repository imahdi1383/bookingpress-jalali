<?php
if ( ! class_exists( 'bookingpress_pro_myservices' ) ) {
	class bookingpress_pro_myservices Extends BookingPress_Core {
		function __construct() {
			add_action( 'bookingpress_myservices_dynamic_view_load', array( $this, 'bookingpress_load_myservices_view_func' ) );
			add_action( 'bookingpress_myservices_dynamic_data_fields', array( $this, 'bookingpress_myservices_dynamic_data_fields_func' ) );
			add_action( 'bookingpress_myservices_dynamic_on_load_methods', array( $this, 'bookingpress_myservices_dynamic_onload_methods_func' ) );
			add_action( 'bookingpress_myservices_dynamic_vue_methods', array( $this, 'bookingpress_myservices_dynamic_vue_methods_func' ) );
			add_action( 'bookingpress_myservices_dynamic_helper_vars', array( $this, 'bookingpress_myservices_dynamic_helper_vars_func' ) );
			add_action( 'wp_ajax_bookingpress_edit_staff_service_price', array( $this, 'bookingpress_edit_staff_service_price_func' ));
		}

		function bookingpress_load_myservices_view_func() {
			$bookingpress_load_file_name = BOOKINGPRESS_PRO_VIEWS_DIR . '/staff_members/staffmember_services.php';
			require $bookingpress_load_file_name;
		}

		function bookingpress_myservices_dynamic_data_fields_func() {
			global $wpdb, $BookingPress, $bookingpress_services, $bookingpress_deposit_payment, $bookingpress_service_extra, $tbl_bookingpress_services, $tbl_bookingpress_categories, $tbl_bookingpress_servicesmeta, $tbl_bookingpress_staffmembers, $tbl_bookingpress_staffmembers_services, $tbl_bookingpress_extra_services;

			$bookingpress_myservices_data_fields_arr = array();

			// Find bookingpress staffmember id
			$bookingpress_current_user_id  = get_current_user_id();

			$bookingpress_staffmember_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_staffmembers} WHERE bookingpress_wpuser_id = %d AND bookingpress_staffmember_status != '4'", $bookingpress_current_user_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers is a table name. false alarm

			$bookingpress_staffmember_id   = ! empty( $bookingpress_staffmember_data['bookingpress_staffmember_id'] ) ? intval( $bookingpress_staffmember_data['bookingpress_staffmember_id'] ) : 0;

			$bookingpress_assigned_services_details = array();

			// Get staffmembers assigned services
			$bookingpress_staffmember_assigned_services = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_staffmembers_services} WHERE bookingpress_staffmember_id = %d", $bookingpress_staffmember_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers_services is a table name. false alarm
			if ( ! empty( $bookingpress_staffmember_assigned_services ) ) {
				foreach ( $bookingpress_staffmember_assigned_services as $k => $v ) {

					$bookingpress_service_id          = $v['bookingpress_service_id'];
					$bookingpress_service_details     = $BookingPress->get_service_by_id( $bookingpress_service_id );
					$bookingpress_service_name        = ! empty( $bookingpress_service_details['bookingpress_service_name'] ) ? $bookingpress_service_details['bookingpress_service_name'] : '';
					$bookingpress_service_description = ! empty( $bookingpress_service_details['bookingpress_service_description'] ) ? $bookingpress_service_details['bookingpress_service_description'] : '';

					$bookingpress_service_price                 = $v['bookingpress_service_price'];
					$bookingpress_service_price_with_formatting = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_service_price );

					$bookingpress_service_duration_val  = ! empty( $bookingpress_service_details['bookingpress_service_duration_val'] ) ? $bookingpress_service_details['bookingpress_service_duration_val'] : '';
					$bookingpress_service_duration_unit = ! empty( $bookingpress_service_details['bookingpress_service_duration_unit'] ) ? $bookingpress_service_details['bookingpress_service_duration_unit'] : '';
					$bookingpress_service_duration      = '';
					if ( ! empty( $bookingpress_service_duration_unit ) && ! empty( $bookingpress_service_duration_val ) ) {
						$bookingpress_service_duration = $bookingpress_service_duration_val;
						if ( $bookingpress_service_duration_unit == 'm' ) {
							$bookingpress_service_duration .= ' ' . __( 'Min', 'bookingpress-appointment-booking' );
						} else {
							$bookingpress_service_duration .= ' ' . __( 'Hours', 'bookingpress-appointment-booking' );
						}
					}

					$bookingpress_max_capacity = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'max_capacity' );

					$bookingpress_category_id   = ! empty( $bookingpress_service_details['bookingpress_category_id'] ) ? $bookingpress_service_details['bookingpress_category_id'] : 0;
					$bookingpress_category_name = '';
					if($bookingpress_category_id == 0) {
						$bookingpress_category_name = esc_html__('Uncategorized', 'bookingpress-appointment-booking');
					} else { 					
						$bookingpress_cat_details   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_categories} WHERE bookingpress_category_id = %d", $bookingpress_category_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_categories is a table name. false alarm
						$bookingpress_category_name = ! empty( $bookingpress_cat_details['bookingpress_category_name'] ) ? $bookingpress_cat_details['bookingpress_category_name'] : '';
					}

					$bookingpress_service_image_details = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'service_image_details' );
					$bookingpress_service_img_url       = BOOKINGPRESS_IMAGES_URL . '/default-avatar.jpg';;
					$bookingpress_service_image_details = !empty($bookingpress_service_image_details ) ? maybe_unserialize( $bookingpress_service_image_details ) : array();
					if ( ! empty( $bookingpress_service_image_details[0]['url'] ) ) {
						$bookingpress_service_img_url = $bookingpress_service_image_details[0]['url'];
					}

					// Get Extra Service Details
					$bookingpress_extra_services         = array();
					$bookingpress_extra_services_details = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_service_id = %d", $bookingpress_service_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is a table name. false alarm
					if ( ! empty( $bookingpress_extra_services_details ) ) {
						foreach ( $bookingpress_extra_services_details as $k2 => $v2 ) {
							$bookingpress_extra_service_name            = $v2['bookingpress_extra_service_name'];
							$bookingpress_extra_service_price           = $v2['bookingpress_extra_service_price'];
							$bookingpress_extra_service_formatted_price = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_extra_service_price );

							$bookingpress_extra_services[] = array(
								'service_name'            => $bookingpress_extra_service_name,
								'service_price'           => $bookingpress_extra_service_price,
								'service_formatted_price' => $bookingpress_extra_service_formatted_price,
							);
						}
					}

					$bookingpress_deposit_amount = '0';
					if ( $bookingpress_deposit_payment->bookingpress_check_deposit_payment_module_activation() ) {	
						$bookingpress_deposit_type = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'deposit_type' );
						$bookingpress_deposit_amt  = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'deposit_amount' );
						$bookingpress_deposit_amt = $bookingpress_deposit_amt == '' ? 100 : $bookingpress_deposit_amt; 
						$bookingpress_deposit_type = $bookingpress_deposit_type == '' ? 'percentage' : $bookingpress_deposit_type;
						if ( $bookingpress_deposit_type == 'percentage' ) {
							$bookingpress_deposit_amount = $bookingpress_deposit_amt . '%';
						} else {
							$bookingpress_deposit_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_deposit_amt );
						}						
					}

					$bookingpress_assigned_services_details_arr = array(
						'bookingpress_service_img_url'     => $bookingpress_service_img_url,
						'bookingpress_deposit_amount'      => $bookingpress_deposit_amount,
						'bookingpress_service_id'          => $bookingpress_service_id,
						'bookingpress_service_name'        => stripslashes_deep($bookingpress_service_name),
						'bookingpress_service_duration'    => $bookingpress_service_duration,
						'bookingpress_max_capacity'        => $bookingpress_max_capacity,
						'bookingpress_service_price_before'=> $bookingpress_service_price,
						'bookingpress_service_price'       => $bookingpress_service_price,
						'bookingpress_service_formatted_price' => $bookingpress_service_price_with_formatting,
						'bookingpress_category'            => stripslashes_deep($bookingpress_category_name),
						'bookingpress_service_description' => $bookingpress_service_description,
						'bookingpress_extra_services'      => $bookingpress_extra_services,						
					);


					$bookingpress_assigned_services_details_arr = apply_filters( 'bookingpress_myservices_modify_service_fields', $bookingpress_assigned_services_details_arr, $bookingpress_service_id, $bookingpress_staffmember_id);

					$bookingpress_assigned_services_details[] = $bookingpress_assigned_services_details_arr;
				}
			}

			$bookingpress_myservices_data_fields_arr['is_mask_display'] = false;
			$bookingpress_myservices_data_fields_arr['edit_index'] = "";
			$bookingpress_myservices_data_fields_arr['assigned_services_details'] = $bookingpress_assigned_services_details;
			$bookingpress_myservices_data_fields_arr['deposit_activated']         = $bookingpress_deposit_payment->bookingpress_check_deposit_payment_module_activation();
			$bookingpress_myservices_data_fields_arr['service_extra_activated']   = $bookingpress_service_extra->bookingpress_check_service_extra_module_activation();

			$bookingpress_myservices_data_fields_arr['open_edit_service_price_model'] = false;
			$bookingpress_myservices_data_fields_arr['edit_service_details']  = "";

			$bookingpress_myservices_data_fields_arr['price_number_of_decimals'] = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
			$bookingpress_payment_deafult_currency  = $BookingPress->bookingpress_get_settings('payment_default_currency', 'payment_setting');
            $bookingpress_payment_deafult_currency  = $BookingPress->bookingpress_get_currency_symbol($bookingpress_payment_deafult_currency);
            $bookingpress_myservices_data_fields_arr['service_price_currency'] = $bookingpress_payment_deafult_currency;

			$bookingpress_myservices_data_fields_arr = apply_filters( 'bookingpress_myservices_modify_data_fields', $bookingpress_myservices_data_fields_arr, $bookingpress_staffmember_id );

			echo wp_json_encode( $bookingpress_myservices_data_fields_arr );
		}

		function bookingpress_myservices_dynamic_onload_methods_func() {
			
		}

		function bookingpress_myservices_dynamic_vue_methods_func() {
			global $bookingpress_notification_duration;
			?>
			edit_staff_member_price(currentElement, service_data, index){
				console.log("Test")
				const vm = this
				vm.open_edit_service_price_model = true;

				vm.edit_service_details = service_data;
				vm.edit_index = index;
				vm.bpa_adjust_popup_position( currentElement, 'div#service_price_edit_model .el-dialog.bpa-dialog--edit-service-price' );
			},
			staffmember_service_price_validate(evt) {
				const vm = this
				const regex = /^(?!.*(,,|,\.|\.,|\.\.))[\d.,]+$/gm;
				let m;
				if((m = regex.exec(evt)) == null ) {
					vm.edit_service_details.bookingpress_service_price = '';
				}
				var price_number_of_decimals = vm.price_number_of_decimals;                
				if((evt != null && evt.indexOf(".")>-1 && (evt.split('.')[1].length > price_number_of_decimals))){
					vm.edit_service_details.bookingpress_service_price = evt.slice(0, -1);
				}                
			},
			bookingpress_close_edit_staffmember_price_modal(){
				const vm = this
				vm.open_edit_service_price_model = false
			},
			bookingpress_save_edit_staffmember_price_data(edit_service_details){
				const vm = this
				<?php
				do_action('bookingpress_before_save_edit_staffmember_price_data');
				?>
				var postdata = [];
				postdata.action = 'bookingpress_edit_staff_service_price';
				postdata._wpnonce = '<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>';
				postdata.service_details = edit_service_details;

				if(edit_service_details.bookingpress_custom_durations_data != 'undefined' && edit_service_details.bookingpress_custom_durations_data != '' && edit_service_details.bookingpress_custom_durations_data != null) {
					edit_service_details.bookingpress_custom_durations_data.forEach(function(item2,index2,arr2) {
						if(index2 == 0 ){
							edit_service_details.bookingpress_service_price = item2.staff_service_price;
							edit_service_details.bookingpress_service_formatted_price = item2.staff_service_formatted_price;
						}
					});
				} 

                axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postdata ) )
				.then(function(response){
					if(response.data.variant != 'error'){
						vm.bookingpress_close_edit_staffmember_price_modal();
						if(typeof vm.edit_index != "undefined"){
							if (response.data.service_details && response.data.service_details.bookingpress_service_formatted_price !== undefined) {
								vm.assigned_services_details[vm.edit_index].bookingpress_service_formatted_price = response.data.service_details.bookingpress_service_formatted_price;
							}
							if (response.data.service_details && response.data.service_details.bookingpress_service_price !== undefined) {
								vm.assigned_services_details[vm.edit_index].bookingpress_service_price_before = response.data.service_details.bookingpress_service_price;
								vm.assigned_services_details[vm.edit_index].bookingpress_service_price = response.data.service_details.bookingpress_service_price;
							}
						}	

						if(vm.assigned_services_details[vm.edit_index].bookingpress_custom_durations_data != 'undefined' && vm.assigned_services_details[vm.edit_index].bookingpress_custom_durations_data != '' && vm.assigned_services_details[vm.edit_index].bookingpress_custom_durations_data != null) {
							vm.assigned_services_details[vm.edit_index].bookingpress_custom_durations_data.forEach(function(item2,index2,arr2) {
								vm.assigned_services_details[vm.edit_index].bookingpress_custom_durations_data[index2].staff_service_price_before = item2.staff_service_price;															
							});
						}

						vm.$notify({
							title: '<?php esc_html_e('Success', 'bookingpress-appointment-booking'); ?>',
							message: '<?php esc_html_e('Service price updated successfully..', 'bookingpress-appointment-booking'); ?>',
							type: 'success',
							customClass: 'success_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});					
					}
					else{
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: response.data.variant,
                            customClass: response.data.variant+'_notification',
                        });                        
                    }
					
				}).catch(function(error){
					console.log(error);
					vm.$notify({
						title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
						message: '<?php esc_html_e('Something went wrong..', 'bookingpress-appointment-booking'); ?>',
						type: 'error',
						customClass: 'error_notification',
						duration:<?php echo intval($bookingpress_notification_duration); ?>,
					});
				});
				vm.bookingpress_close_edit_staffmember_price_modal()
			},			
			<?php
		}

		function bookingpress_myservices_dynamic_helper_vars_func() {
			global $bookingpress_global_options;
			$bookingpress_options     = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_locale_lang = $bookingpress_options['locale'];
			?>
				var lang = ELEMENT.lang.<?php echo esc_html( $bookingpress_locale_lang ); ?>;
				ELEMENT.locale(lang)				
			<?php
		}

		function bookingpress_edit_staff_service_price_func(){

			global $wpdb, $tbl_bookingpress_staffmembers, $tbl_bookingpress_staffmembers_services, $BookingPress, $tbl_bookingpress_staffmembers_activities;
			$bpa_check_authorization = $this->bpa_check_authentication( 'bookingpress_edit_staff_service', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');
                $response = array();
                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			$service_details  = ! empty($_REQUEST['service_details']) ? array_map(array( $BookingPress, 'appointment_sanatize_field' ), $_REQUEST['service_details']) : array();
            
			do_action('bookingpress_add_staff_service_price_validation');

			$bookingpress_current_user_id  = get_current_user_id();

			$bookingpress_staffmember_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_staffmembers} WHERE bookingpress_wpuser_id = %d AND bookingpress_staffmember_status != '4'", $bookingpress_current_user_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers is a table name. false alarm

			$bookingpress_staffmember_id   = ! empty( $bookingpress_staffmember_data['bookingpress_staffmember_id'] ) ? intval( $bookingpress_staffmember_data['bookingpress_staffmember_id'] ) : 0;

			$bookingpress_service_id = isset($service_details['bookingpress_service_id']) ? $service_details['bookingpress_service_id']: 0;
			$bookingpress_service_price = isset($service_details['bookingpress_service_price']) ? floatval($service_details['bookingpress_service_price']) : 0;
			$bookingpress_service_price_before = isset($service_details['bookingpress_service_price_before']) ? floatval($service_details['bookingpress_service_price_before']) : 0;
			$bookingpress_service_price_with_formatting = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_service_price );

			$service_details['bookingpress_service_formatted_price'] = $bookingpress_service_price_with_formatting;

			$update_details = array(
				'bookingpress_service_price'=> $bookingpress_service_price,
				'bookingpress_created_date' => current_time('mysql'),
			);

			if(!empty($bookingpress_service_id) && !empty($bookingpress_staffmember_id)) {
				$wpdb->update($tbl_bookingpress_staffmembers_services, $update_details, array( 'bookingpress_staffmember_id' => $bookingpress_staffmember_id, 'bookingpress_service_id' => $bookingpress_service_id ));

				if($bookingpress_service_price != $bookingpress_service_price_before){
					$bookingpress_db_fields = array(
						'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
						'bookingpress_service_id' => $bookingpress_service_id,
						'bookingpress_service_price' => $bookingpress_service_price,
						'bookingpress_service_price_before' => $bookingpress_service_price_before,
						'bookingpress_service_custom_duration_id' => 0,
						'bookingpress_created_date' => current_time('mysql'),
					);
					$wpdb->insert( $tbl_bookingpress_staffmembers_activities, $bookingpress_db_fields );  
				}

				$bpa_success_msg = esc_html__( 'Service Price has been updated successfully', 'bookingpress-appointment-booking');

				$response['variant'] = 'success';
                $response['title'] = esc_html__( 'Success', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_success_msg;
				$response['service_details'] = $service_details;

				$response = apply_filters('bookingpress_after_edit_update_staff_service', $response, $bookingpress_staffmember_id, $_POST); 
				
			}

			wp_send_json($response);
		}
	}
}
global $bookingpress_pro_myservices;
$bookingpress_pro_myservices = new bookingpress_pro_myservices();
