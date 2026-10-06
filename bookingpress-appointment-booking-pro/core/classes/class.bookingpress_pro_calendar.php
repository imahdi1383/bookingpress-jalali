<?php
$bookingpress_geoip_file = BOOKINGPRESS_PRO_LIBRARY_DIR . '/geoip/autoload.php';
require $bookingpress_geoip_file;
use GeoIp2\Database\Reader;

if ( ! class_exists( 'bookingpress_pro_calendar' ) ) {
	class bookingpress_pro_calendar Extends BookingPress_Core {
		function __construct() {
			add_filter( 'bookingpress_modify_calendar_view_file_path', array( $this, 'bookingpress_modify_calendar_file_path_func' ), 10 );
			add_filter( 'bookingpress_modify_calendar_data_fields', array( $this, 'bookingpress_modify_calendar_data_fields_func' ), 10 );
			add_filter( 'bookingpress_modify_calendar_appointment_class', array( $this, 'bookingpress_modify_calendar_appointment_class_func' ), 10, 2 );

			//Modify calendar loading data
			add_filter('bookingpress_modify_calendar_loading_data', array($this, 'bookingpress_modify_calendar_loading_data_func'));

			add_action('bookingpress_add_dynamic_vue_methods_for_calendar', array($this, 'bookingpress_add_dynamic_vue_methods_for_calendar_func'), 10);
			add_action('bookingpress_calendar_add_appointment_model_reset', array( $this, 'bookingpress_calendar_add_appointment_model_reset_callback' ) );

			add_action('bookingpress_calendar_reset_filter',array($this,'bookingpress_calendar_reset_filter_func'));
			
			add_filter('bookingpress_modify_calendar_appointment_details', array($this, 'bookingpress_modify_calendar_appointment_details_func'), 10, 2);

			add_filter('bookingpress_check_edit_is_appointment_already_booked', array($this, 'bookingpress_check_edit_is_appointment_already_booked_func'), 10, 2);

			add_filter('bookingpress_modify_popover_appointment_data', array($this, 'bookingpress_modify_popover_appointment_data_func'), 10);

			add_filter('bookingpress_modify_popover_appointment_data_query', array($this, 'bookingpress_modify_popover_appointment_data_query_func'), 10, 2);

			/* Function for check custom time Validation In Pro Day Service */
			add_filter('bookingpress_check_custom_time_validation',array($this,'bookingpress_check_custom_time_validation_func'),10,2);

			add_action('wp_ajax_bookingpress_validate_before_save_appointment_booking', array( $this, 'bookingpress_validate_before_save_appointment_booking_func' ), 10);

			add_filter( 'bookingpress_booked_appointment_where_clause', array( $this, 'bookingpress_booked_appointment_custom_time_where_clause_func'), 15, 2 );

			add_filter( 'bookingpress_booked_appointment_with_share_timeslot_where_clause_check', array( $this, 'bookingpress_booked_appointment_custom_time_where_clause_func'), 15, 2 );

			add_action('bookingpress_modified_appointment_data_for_backend_appointment_booking',array($this,'bookingpress_modified_appointment_data_for_backend_appointment_booking_func'),10);

			add_filter('bookingpress_customize_timeing_bookingpress_validation',array($this,'bookingpress_customize_timeing_bookingpress_validation_func'),10,9);

			add_filter('bookingpress_modify_check_duplidate_appointment_time_slot',array($this,'bookingpress_modify_check_duplidate_appointment_time_slot_func'),20,2);

			add_filter('bookingpress_modify_appointment_booking_fields',array($this,'bookingpress_modify_appointment_booking_fields_func'),15,3);

			add_filter('bookingpress_modify_appointment_booking_fields_before_insert',array($this,'bookingpress_modify_appointment_booking_fields_before_insert_func'),10,2);

			add_filter('bookingpress_backend_get_special_day_break_hours',array($this,'bookingpress_backend_get_break_hours_func'),25,3);

			add_action('bookingpress_after_update_appointment',array($this,'bookingpress_after_update_appointment_func'),40);

			add_action('wp_ajax_bookingpress_refund_before_save_appointment_booking',array($this,'bookingpress_refund_before_save_appointment_booking_func'),10);
		}

		function bookingpress_refund_before_save_appointment_booking_func($is_return = false) {
			global $wpdb, $BookingPress, $BookingPressPro,$bookingpress_pro_payment_gateways, $tbl_bookingpress_payment_logs, $tbl_bookingpress_appointment_meta;
			$response = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'apply_for_refund', true, 'bpa_wp_nonce' );           
			
			if( preg_match( '/error/', $bpa_check_authorization ) ){
				$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
				$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');
				$response['variant'] = 'error';
				$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
				$response['msg'] = $bpa_error_msg;
				if( $is_return ) {
					return $response;
				}
				wp_send_json( $response );
				die;
			}
			
			$response['variant'] = 'error';
			$response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
			$response['msg'] = esc_html__('Something went wrong while process with refund', 'bookingpress-appointment-booking');			

			if( !empty( $_REQUEST['appointment_data'] ) && !is_array( $_REQUEST['appointment_data'] ) ){ //phpcs:ignore
				$appointment_data = json_decode( stripslashes_deep( $_POST['appointment_data'] ), true ); //phpcs:ignore
				$bookingpress_appointment_data =  !empty($appointment_data) ? array_map(array($this,'bookingpress_boolean_type_cast'), $appointment_data ) : array(); //phpcs:ignore
			}
			
			if(!empty($bookingpress_appointment_data)){
				$bookingpress_update_id = isset($bookingpress_appointment_data['appointment_update_id']) ? $bookingpress_appointment_data['appointment_update_id'] : 0;
				$bookingpress_payment_id = isset($bookingpress_appointment_data['bookingpress_payment_id']) ? $bookingpress_appointment_data['bookingpress_payment_id'] : 0;

				$bookingpress_appointment_payment_logs_data= $wpdb->get_row($wpdb->prepare("SELECT bookingpress_paid_amount,bookingpress_payment_gateway,bookingpress_payment_currency, bookingpress_transaction_id FROM {$tbl_bookingpress_payment_logs} WHERE bookingpress_payment_log_id = %d", $bookingpress_payment_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_payment_logs is a table name. false alarm

				$bookingpress_paid_amount = isset($bookingpress_appointment_payment_logs_data['bookingpress_paid_amount']) ? $bookingpress_appointment_payment_logs_data['bookingpress_paid_amount'] : 0;

				$is_partial_refund_supported = isset($bookingpress_appointment_payment_logs_data['is_partial_refund_supported']) ? $bookingpress_appointment_payment_logs_data['is_partial_refund_supported'] : 0;

				$is_allow_edit_past_appointment = isset($bookingpress_appointment_payment_logs_data['is_allow_edit_past_appointment']) ? $bookingpress_appointment_payment_logs_data['is_allow_edit_past_appointment'] : 0;

				$bookingpress_currency_name = isset($bookingpress_appointment_payment_logs_data['bookingpress_currency_name']) ? $bookingpress_appointment_payment_logs_data['bookingpress_currency_name'] : "";

				$bookingpress_payment_gateway = isset($bookingpress_appointment_payment_logs_data['bookingpress_payment_gateway']) ? $bookingpress_appointment_payment_logs_data['bookingpress_payment_gateway'] : "";

				$transaction_id = isset($bookingpress_appointment_payment_logs_data['bookingpress_transaction_id']) ? $bookingpress_appointment_payment_logs_data['bookingpress_transaction_id'] : "";

				$bookingpress_payment_currency = isset($bookingpress_appointment_payment_logs_data['bookingpress_payment_currency']) ? $bookingpress_appointment_payment_logs_data['bookingpress_payment_currency'] : "";

				$total_amount = isset($bookingpress_appointment_data['total_amount']) ? $bookingpress_appointment_data['total_amount'] : 0;

				if($bookingpress_paid_amount > $total_amount){
					$refund_amount = $bookingpress_paid_amount - $total_amount;

					$refund_amount = apply_filters('bookingpress_modify_refund_data_amount', $refund_amount, $bookingpress_payment_id, $bookingpress_appointment_data);
					//$refund_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $refund_amount, '', false);
					
					if(!empty($bookingpress_payment_gateway)) {
						if($is_allow_edit_past_appointment == 1){
							$refund_reason = esc_html__( 'Past Edit Appointment Refund initialted', 'bookingpress-appointment-booking' );
						} else {
							$refund_reason = esc_html__( 'Edit Appointment Refund initialted', 'bookingpress-appointment-booking' );
						}

						$bookingpress_send_refund_data = array(
							'bookingpress_transaction_id' => $transaction_id,
							'refund_type' => 'partial', //passing parameter of refund type to full
							'refund_reason' => $refund_reason,
							'refund_amount' => $refund_amount,
							'default_refund_amount' => $refund_amount,
							'bookingpress_payment_currency' => $bookingpress_payment_currency
						);
						
						$past_refund_db_update = array(
							'bookingpress_refund_reason' => $refund_reason
						);
						$past_refund_db_update['bookingpress_is_refund_supported'] = 1;
						
						$refund_response = apply_filters('bookingpress_'.$bookingpress_payment_gateway.'_apply_refund',$response,$bookingpress_send_refund_data);

						$refund_response['variant'] = 'success';

						$past_refund_db_update['bookingpress_refund_response'] = wp_json_encode( $refund_response );
						if(!empty($refund_response['variant']) && $refund_response['variant'] == 'success' ) {
							$response['title'] = esc_html__('Success', 'bookingpress-appointment-booking');
							$response['msg']   = esc_html__( 'Refund successfully initiated', 'bookingpress-appointment-booking' );						
							$response['variant'] = "success";
							$past_refund_db_update['bookingpress_is_refunded'] = 1;

							if($bookingpress_payment_gateway != 'on-site'){         
								$payment_new_status = 5; //Partial refunded 
								if($is_allow_edit_past_appointment == 1)
									$bookingpres_refund_type = "past_edit_refund";
								else {
									$bookingpres_refund_type = "edit_refund";
								}
								$current_user_id = get_current_user_id();
								$refund_update_data = array(
									'bookingpress_payment_status' => $payment_new_status,
									'bookingpress_refund_reason' => $refund_reason,
									'bookingpress_refund_initiate_from' => $current_user_id,
									'bookingpress_refund_amount' => $refund_amount,
									'bookingpress_refund_type' => $bookingpres_refund_type,
									'bookingpress_refund_response' => wp_json_encode($refund_response)
								);

								$res = $wpdb->update($tbl_bookingpress_payment_logs, $refund_update_data, array('bookingpress_payment_log_id' => $bookingpress_payment_id));
								
							}	

						} else {
							$past_refund_db_update['bookingpress_is_refunded'] = 0;
						}						
					}
				}								
			}
			if( $is_return ) {
				return $response;
			}
			wp_send_json( $response );
			die;
		}
		
		/**
		 * Function for after update appointment update payment record 
		 *
		 * @param  mixed $bookingpress_update_id
		 * @return void
		*/
		function bookingpress_after_update_appointment_func($bookingpress_update_id = 0){
			
			global $wpdb,$tbl_bookingpress_appointment_bookings,$tbl_bookingpress_payment_logs,$BookingPress;
			$action = ( isset( $_REQUEST['action'] ) ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
			
			$bookingpress_update_id = intval($bookingpress_update_id);
			if($bookingpress_update_id != 0){
				$current_user_id = get_current_user_id();
				$appointment_update_details = array(
					'bookingpress_is_edited' => 1,
					'bookingpress_edit_user_id' => $current_user_id
				);
				$wpdb->update($tbl_bookingpress_appointment_bookings, $appointment_update_details, array( 'bookingpress_appointment_booking_id' => $bookingpress_update_id ));
			}

			if($action == "bookingpress_save_appointment_booking"){
				$appointment_update_id = (isset($_REQUEST['appointment_data']['appointment_update_id']))?intval($_REQUEST['appointment_data']['appointment_update_id']):0;

				if($appointment_update_id != 0){

					$bookingpress_edit_appointment_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_update_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name.					
					$bookingpress_order_id = (isset($bookingpress_edit_appointment_data['bookingpress_order_id']))?$bookingpress_edit_appointment_data['bookingpress_order_id']:'';
					$bookingpress_payment_id = (isset($bookingpress_edit_appointment_data['bookingpress_payment_id']))?$bookingpress_edit_appointment_data['bookingpress_payment_id']:'';

					if($bookingpress_order_id == 0 && $bookingpress_payment_id){

						$update_data = array(
							'bookingpress_coupon_details' => $bookingpress_edit_appointment_data['bookingpress_coupon_details'],
							'bookingpress_coupon_discount_amount' => $bookingpress_edit_appointment_data['bookingpress_coupon_discount_amount'],
							'bookingpress_tax_percentage' => $bookingpress_edit_appointment_data['bookingpress_tax_amount'],
							'bookingpress_tax_amount' => $bookingpress_edit_appointment_data['bookingpress_tax_amount'],
							'bookingpress_staff_member_id' => $bookingpress_edit_appointment_data['bookingpress_staff_member_id'],
							'bookingpress_staff_member_id' => $bookingpress_edit_appointment_data['bookingpress_staff_member_id'],
							'bookingpress_staff_member_price' => $bookingpress_edit_appointment_data['bookingpress_staff_member_price'],
							'bookingpress_staff_first_name' => $bookingpress_edit_appointment_data['bookingpress_staff_first_name'],
							'bookingpress_staff_last_name' => $bookingpress_edit_appointment_data['bookingpress_staff_last_name'],
							'bookingpress_staff_email_address' => $bookingpress_edit_appointment_data['bookingpress_staff_email_address'],
							'bookingpress_staff_member_details' => $bookingpress_edit_appointment_data['bookingpress_staff_member_details'],
							'bookingpress_total_amount' => $bookingpress_edit_appointment_data['bookingpress_total_amount'],								
						);
						
						$bookingpress_deposit_payment_method = (isset($_REQUEST['appointment_data']['bookingpress_deposit_payment_method']))?sanitize_text_field($_REQUEST['appointment_data']['bookingpress_deposit_payment_method']):'';
						if(!empty($bookingpress_deposit_payment_method)){							
							$bookingpress_applied_deposit = (isset($_REQUEST['appointment_data']['bookingpress_applied_deposit']))?sanitize_text_field($_REQUEST['appointment_data']['bookingpress_applied_deposit']):'';
							if($bookingpress_applied_deposit == "1"){
								if($bookingpress_deposit_payment_method == "deposit_or_full_price"){

									$bookingpress_deposit_amt_without_currency = (isset($_REQUEST['appointment_data']['bookingpress_deposit_amt_without_currency']))?sanitize_text_field($_REQUEST['appointment_data']['bookingpress_deposit_amt_without_currency']):'';
									$bookingpress_deposit_due_amt_without_currency = floatval((isset($_REQUEST['appointment_data']['bookingpress_deposit_due_amt_without_currency']))?sanitize_text_field($_REQUEST['appointment_data']['bookingpress_deposit_due_amt_without_currency']):'');
									$service_price_without_currency = (isset($_REQUEST['appointment_data']['service_price_without_currency']))?sanitize_text_field($_REQUEST['appointment_data']['service_price_without_currency']):'';
									$bookingpress_deposit_payment_details = array();

									if($bookingpress_deposit_due_amt_without_currency > 0){

										$deposit_selected_type = (isset($_REQUEST['appointment_data']['deposit_type']))?sanitize_text_field($_REQUEST['appointment_data']['deposit_type']):'';
										$deposit_value = (isset($_REQUEST['appointment_data']['deposit_amount']))?sanitize_text_field($_REQUEST['appointment_data']['deposit_amount']):'';
										$update_data['bookingpress_deposit_amount'] = $bookingpress_deposit_amt_without_currency;
										$update_data['bookingpress_due_amount'] = $bookingpress_deposit_due_amt_without_currency;										
										$update_data['bookingpress_paid_amount'] = $bookingpress_deposit_amt_without_currency;

										if(!empty($service_price_without_currency)){
											$update_data['bookingpress_service_price'] = $service_price_without_currency;
										}

										$bookingpress_deposit_payment_details = array(
											'deposit_selected_type' => $deposit_selected_type,
											'deposit_value'         => $deposit_value,
											'deposit_amount'        => $bookingpress_deposit_amt_without_currency,
											'deposit_due_amount'    => $bookingpress_deposit_due_amt_without_currency,
										);
										$update_data['bookingpress_deposit_payment_details'] = json_encode($bookingpress_deposit_payment_details);
										
										$appointment_data_update = array();
										/*
										$appointment_data_update['bookingpress_paid_amount'] = $bookingpress_edit_appointment_data['bookingpress_total_amount'];
										$update_data['bookingpress_paid_amount'] = $bookingpress_edit_appointment_data['bookingpress_total_amount'];
										*/


										$appointment_data_update['bookingpress_deposit_amount'] = $update_data['bookingpress_deposit_amount'];
										$appointment_data_update['bookingpress_due_amount'] = $update_data['bookingpress_due_amount'];
										$appointment_data_update['bookingpress_deposit_payment_details'] = $update_data['bookingpress_deposit_payment_details'];

										if(!empty($service_price_without_currency)){
											$appointment_data_update['bookingpress_service_price'] = $update_data['bookingpress_service_price'];
										}

										$wpdb->update(
											$tbl_bookingpress_appointment_bookings,
											$appointment_data_update,
											array(
												'bookingpress_appointment_booking_id' => $appointment_update_id,								
											)
										);

									}



								}else{
									if($bookingpress_deposit_payment_method == "allow_customer_to_pay_full_amount"){

										$appointment_data_update = array();										
										$appointment_data_update['bookingpress_deposit_amount'] = 0;
										$appointment_data_update['bookingpress_due_amount'] = 0;
										$appointment_data_update['bookingpress_deposit_payment_details'] = '[]';
										//$appointment_data_update['bookingpress_paid_amount'] = $update_data['bookingpress_paid_amount'];

										$appointment_data_update['bookingpress_paid_amount'] = $bookingpress_edit_appointment_data['bookingpress_total_amount'];
										$update_data['bookingpress_paid_amount'] = $bookingpress_edit_appointment_data['bookingpress_total_amount'];

										$wpdb->update(
											$tbl_bookingpress_appointment_bookings,
											$appointment_data_update,
											array(
												'bookingpress_appointment_booking_id' => $appointment_update_id,								
											)
										);

										$update_data['bookingpress_deposit_amount'] = 0;
										$update_data['bookingpress_due_amount'] = 0;
									}
								}

							}


						}
						
						

						$wpdb->update(
							$tbl_bookingpress_payment_logs,
							$update_data,
							array(
								'bookingpress_payment_log_id' => $bookingpress_payment_id,								
							)
						);						

					}



				}												


			}

		}		

		/**
		 * Function for Special Days Break Hours Get For Backend Custom Time
		 *
		*/
		function bookingpress_backend_get_break_hours_func($break_hours_applied, $bookingpress_appointment_data,$current_day){
			
			global $wpdb,$tbl_bookingpress_default_special_day,$tbl_bookingpress_default_special_day_breaks,$tbl_bookingpress_service_special_day,$tbl_bookingpress_service_special_day_breaks;

			$appointment_booked_date = (isset($bookingpress_appointment_data['appointment_booked_date']))?$bookingpress_appointment_data['appointment_booked_date']:'';
			$selected_staffmember = (isset($bookingpress_appointment_data['selected_staffmember']))?$bookingpress_appointment_data['selected_staffmember']:'';
			$appointment_selected_service = (isset($bookingpress_appointment_data['appointment_selected_service']))?$bookingpress_appointment_data['appointment_selected_service']:'';

			//$break_hours_applied = apply_filters( 'bookingpress_backend_get_special_days_break_hours',$break_hours_applied, $bookingpress_appointment_data, $current_day);
			if(!$break_hours_applied['applied']){
				if($appointment_selected_service){
					$service_special_days = $wpdb->get_row( $wpdb->prepare("SELECT bookingpress_service_special_day_id, bookingpress_special_day_start_time as bpa_staff_start_time, bookingpress_special_day_end_time as bpa_staff_end_time FROM {$tbl_bookingpress_service_special_day} WHERE bookingpress_special_day_start_date <= %s AND bookingpress_special_day_end_date >= %s AND bookingpress_special_day_start_time IS NOT NULL AND bookingpress_service_id = %d", $appointment_booked_date . ' 00:00:00', $appointment_booked_date .' 00:00:00', $appointment_selected_service ), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_service_special_day is a table name.

					if(!empty($service_special_days)){
						$bookingpress_service_special_day_id = intval($service_special_days['bookingpress_service_special_day_id']);

						$service_special_day_breaks = $wpdb->get_results( $wpdb->prepare( "SELECT bookingpress_special_day_break_start_time, bookingpress_special_day_break_end_time FROM {$tbl_bookingpress_service_special_day_breaks} WHERE bookingpress_special_day_id = %d", $bookingpress_service_special_day_id ), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_service_special_day_breaks is a table name.
						$bookingpress_breaks_arr = array();
						$break_hours_applied['applied'] = true;
						if(!empty($service_special_day_breaks)){												
							foreach($service_special_day_breaks as $bookingpress_specialday_break){
								$bookingpress_breaks_arr[] = array(
									'start' => $bookingpress_specialday_break['bookingpress_special_day_break_start_time'],								
									'end'   => $bookingpress_specialday_break['bookingpress_special_day_break_end_time'],															
								);	
							}
							$break_hours_applied['break_hours'] = $bookingpress_breaks_arr;	
						}					
					}
				}			
			}


			if(!$break_hours_applied['applied']){

				$bookingpress_default_special_days = $wpdb->get_row($wpdb->prepare("SELECT bookingpress_special_day_id FROM {$tbl_bookingpress_default_special_day} WHERE bookingpress_special_day_start_date <= %s AND bookingpress_special_day_end_date >= %s", $appointment_booked_date, $appointment_booked_date), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_default_special_day is a table name. false alarm			
		
				if(!empty($bookingpress_default_special_days)){
					$bookingpress_special_day_id = intval($bookingpress_default_special_days['bookingpress_special_day_id']);
					$bookingpress_special_day_breaks = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_special_day_break_start_time, bookingpress_special_day_break_end_time FROM {$tbl_bookingpress_default_special_day_breaks} WHERE bookingpress_special_day_id = %d", $bookingpress_special_day_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_default_special_day_breaks is table name.
					$bookingpress_breaks_arr = array();
					$break_hours_applied['applied'] = true;
					if(!empty($bookingpress_special_day_breaks)){											
						foreach($bookingpress_special_day_breaks as $bookingpress_specialday_break){
							$bookingpress_breaks_arr[] = array(
								'start' => $bookingpress_specialday_break['bookingpress_special_day_break_start_time'],								
								'end'   => $bookingpress_specialday_break['bookingpress_special_day_break_end_time'],															
							);	
						}
						$break_hours_applied['break_hours'] = $bookingpress_breaks_arr;
	
					}
				}				
			}


			return $break_hours_applied;
		}
        
		function bookingpress_modify_appointment_booking_fields_func($appointment_details, $entry_details, $bookingpress_appointment_data){

			$bookingpress_appointment_is_cusomize_timing = (isset($_REQUEST['appointment_data']['appointment_custom_timing']) && sanitize_text_field($_REQUEST['appointment_data']['appointment_custom_timing']) == 'true' ) ? 1 : 0;
			$appointment_details['bookingpress_appointment_customize_timing'] = $bookingpress_appointment_is_cusomize_timing;

			if($bookingpress_appointment_is_cusomize_timing){

				$selected_service_duration_unit = (isset($bookingpress_appointment_data['selected_service_duration_unit']))?$bookingpress_appointment_data['selected_service_duration_unit']:'';
				if($selected_service_duration_unit == 'd'){

					$appointment_booked_date = (isset($bookingpress_appointment_data['appointment_booked_date']))?$bookingpress_appointment_data['appointment_booked_date']:'';
					$appointment_booked_end_date = (isset($bookingpress_appointment_data['appointment_booked_end_date']))?$bookingpress_appointment_data['appointment_booked_end_date']:'';

					$date1 = new DateTime($appointment_booked_date);
					$date2 = new DateTime($appointment_booked_end_date);                                                    
					$interval = $date1->diff($date2);
					$total_days_between_dates = $interval->days;
					$total_days_between_dates = intval($total_days_between_dates) + 1;
					$appointment_details['bookingpress_service_duration_val'] = $total_days_between_dates;                            										
				
				}

			}

			return $appointment_details;
		}

		function bookingpress_modify_appointment_booking_fields_before_insert_func($appointment_booking_fields, $entry_data){

			$bookingpress_appointment_customize_timing = (isset($entry_data['bookingpress_appointment_customize_timing']))?$entry_data['bookingpress_appointment_customize_timing']:0;
			$appointment_booking_fields['bookingpress_appointment_customize_timing'] = $bookingpress_appointment_customize_timing;

			return $appointment_booking_fields;
		}

        /**
         * Function for check validation
         *
         * @param  mixed $bpa_check_duplidate_appointment_time_slot
         * @param  mixed $posted_data
         * @return void
        */
        function bookingpress_modify_check_duplidate_appointment_time_slot_func($bpa_check_duplidate_appointment_time_slot,$posted_data){
			$bookingpress_appointment_is_cusomize_timing = (isset($posted_data['appointment_data']['appointment_custom_timing']))?sanitize_text_field($posted_data['appointment_data']['appointment_custom_timing']):'';
			$selected_service_duration_unit = ( !empty( $posted_data['appointment_data'] ) && !empty( $posted_data['appointment_data']['selected_service_duration_unit'] ) ) ? $posted_data['appointment_data']['selected_service_duration_unit'] : '';
			$bookingpress_appointment_is_cusomize_timing = ($bookingpress_appointment_is_cusomize_timing == 'true')?1:0;
            if($bookingpress_appointment_is_cusomize_timing && $selected_service_duration_unit == 'd'){
                $bpa_check_duplidate_appointment_time_slot = true;
            }            
            return $bpa_check_duplidate_appointment_time_slot;
        }
				
		/**
		 * Function for customize timing 
		 *
		 */
		function bookingpress_customize_timeing_bookingpress_validation_func($return_data,$service_id, $booking_date, $booking_start_time, $booking_end_time,$appointment_id = 0, $prevent_double_booking = false, $appointment_data = array()){

			global $bookingpress_other_debug_log_id,$BookingPress;
			$prevent_booking =  $BookingPress->bookingpress_is_appointment_booked($service_id, $booking_date, $booking_start_time, $booking_end_time,$appointment_id,true,$appointment_data);

			if( !empty( $prevent_booking ) && !empty( $prevent_booking['prevent_validation_process'] ) && true == $prevent_booking['prevent_validation_process'] ){

				$appointment_prevent_reason = $prevent_booking['response'];
				do_action( 'bookingpress_other_debug_log_entry', 'appointment_debug_logs', 'Backend Customize Time Appointment Already Exists', 'bookingpress_admin_add_update_appointment', $prevent_booking, $bookingpress_other_debug_log_id );
				
				return $appointment_prevent_reason;
			}

			return $return_data;
		}	

		/**
		 * Function for modified backend appointment validation for backend
		 *
		 * @return void
		*/
		function bookingpress_modified_appointment_data_for_backend_appointment_booking_func(){

			global $BookingPress;
			if(isset($_REQUEST['appointment_data']['appointment_selected_service'])){
				$_REQUEST['appointment_data']['selected_service'] = sanitize_text_field($_REQUEST['appointment_data']['appointment_selected_service']);
			}
			if(isset($_REQUEST['appointment_data']['selected_staffmember'])){
				$_REQUEST['appointment_data']['bookingpress_selected_staff_member_details']['selected_staff_member_id'] = sanitize_text_field($_REQUEST['appointment_data']['selected_staffmember']);
			}
			if(isset($_REQUEST['appointment_data']['selected_location'])){
				$_REQUEST['appointment_data']['bookingpress_selected_bring_members'] = sanitize_text_field($_REQUEST['appointment_data']['selected_location']);
			}
			if(isset($_REQUEST['appointment_data'])){
				$_REQUEST['appointment_data_obj'] = array_map(array( $BookingPress, 'appointment_sanatize_field' ), $_REQUEST['appointment_data']); // phpcs:ignore
			}
			
		}

		function bookingpress_booked_appointment_custom_time_where_clause_func($where_clause){
			global $BookingPress,$wpdb;
			$bookingpress_appointment_is_cusomize_timing = (isset($_REQUEST['appointment_data']['appointment_custom_timing']))?sanitize_text_field($_REQUEST['appointment_data']['appointment_custom_timing']):'';
			$bookingpress_appointment_is_cusomize_timing = ($bookingpress_appointment_is_cusomize_timing == 'true')?1:0;
			if($bookingpress_appointment_is_cusomize_timing){
				$appointment_update_id = (isset($_REQUEST['appointment_data']['appointment_update_id']))?intval($_REQUEST['appointment_data']['appointment_update_id']):'';
				if($appointment_update_id){					
					$where_clause.= $wpdb->prepare( " AND bookingpress_appointment_booking_id <> %d", $appointment_update_id );
				}
			}
			return $where_clause;
		}

		
        function bookingpress_validate_before_save_appointment_booking_func(){
			global $bookingpress_calendar;
            $bookingpress_calendar->bookingpress_save_appointment_booking_func(true);           
        }

		function bookingpress_check_custom_time_validation_func($bookingpress_check_custom_time_validation,$bookingpress_appointment_data){

			$selected_service_duration_unit = (isset($bookingpress_appointment_data['selected_service_duration_unit']))?$bookingpress_appointment_data['selected_service_duration_unit']:'';
			if($selected_service_duration_unit == 'd'){

				$selected_service_duration_unit = (isset($bookingpress_appointment_data['selected_service_duration_unit']))?$bookingpress_appointment_data['selected_service_duration_unit']:'';
				if($selected_service_duration_unit == 'd'){

					$appointment_booked_date = (isset($bookingpress_appointment_data['appointment_booked_date']))?$bookingpress_appointment_data['appointment_booked_date']:'';
					$appointment_booked_end_date = (isset($bookingpress_appointment_data['appointment_booked_end_date']))?$bookingpress_appointment_data['appointment_booked_end_date']:'';

					if(empty($appointment_booked_end_date) || empty($appointment_booked_date)){
						$response['variant'] = 'error';
						$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
						$response['msg']     = esc_html__('Please select end date.', 'bookingpress-appointment-booking');
						return $response;						
					}else{
						if($appointment_booked_end_date < $appointment_booked_date){
							$response['variant'] = 'error';
							$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
							$response['msg']     = esc_html__('Please select valid end date.', 'bookingpress-appointment-booking');
							return $response;							
						}
					}

					$date1 = new DateTime($appointment_booked_date);
					$date2 = new DateTime($appointment_booked_end_date);                                                    
					$interval = $date1->diff($date2);
					$total_days_between_dates = $interval->days;
					$total_days_between_dates = intval($total_days_between_dates) + 1;
					$bookingpress_appointment_data['selected_service_duration'] = $total_days_between_dates;                            
					$_REQUEST['appointment_data']['selected_service_duration'] = $total_days_between_dates;
					/*
					$bookingpress_appointment_data['appointment_data']['selected_service_duration'] = $total_days_between_dates;
					$bookingpress_appointment_data['appointment_data']['selected_service_duration_unit'] = $selected_service_duration_unit;
					*/

				}

				$appointment_booked_date = (isset($bookingpress_appointment_data['appointment_booked_date']))?$bookingpress_appointment_data['appointment_booked_date']:'';
				$appointment_booked_end_date = (isset($bookingpress_appointment_data['appointment_booked_end_date']))?$bookingpress_appointment_data['appointment_booked_end_date']: $appointment_booked_date;
				$response = array();

				if(empty($appointment_booked_end_date)){
					$response['variant'] = 'error';
					$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
					$response['msg']     = esc_html__('Please select end date.', 'bookingpress-appointment-booking');
				}else if(empty($appointment_booked_date)){
					$response['variant'] = 'error';
					$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
					$response['msg']     = esc_html__('Please select date.', 'bookingpress-appointment-booking');
				}else if($appointment_booked_end_date < $appointment_booked_date){
					$response['variant'] = 'error';
					$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
					$response['msg']     = esc_html__('Please select end date is bigger then start date.', 'bookingpress-appointment-booking');
				}
				return $response;
			}else{

				$appointment_booked_date = (isset($bookingpress_appointment_data['appointment_booked_date']))?$bookingpress_appointment_data['appointment_booked_date']:'';
				if($appointment_booked_date){
					
					global $wpdb,$tbl_bookingpress_default_workhours;
					$current_day  = ! empty( $appointment_booked_date ) ? strtolower( date( 'l', strtotime( $appointment_booked_date ) ) ) : strtolower( date( 'l', current_time( 'timestamp' ) ) );
					$start_time = (isset($bookingpress_appointment_data['appointment_booked_time']))?$bookingpress_appointment_data['appointment_booked_time']:'';
					$end_time = (isset($bookingpress_appointment_data['appointment_booked_end_time']))?$bookingpress_appointment_data['appointment_booked_end_time']:'';
					
					$appointment_booked_date_time = $appointment_booked_date . ' ' . $start_time;
					$appointment_booked_end_date_time = $appointment_booked_date .' ' . $end_time;

					if ( $appointment_booked_end_date_time <= $appointment_booked_date_time ){
						$response['variant'] = 'error';
						$response['title']   = esc_html__('Error', 'bookingpress-appointment-booking');
						$response['msg']     = esc_html__('Selected end time must be bigger than start time', 'bookingpress-appointment-booking');
						return $response;
					}
					$selected_hours_in_break = false;
					if(!empty($start_time) && !empty($end_time)){
						$break_hours = array();
						$break_hours_applied = array('applied'=>false,'break_hours'=>array());
						
						$break_hours_applied = apply_filters( 'bookingpress_backend_get_special_day_break_hours',$break_hours_applied, $bookingpress_appointment_data, $current_day);

						if(!$break_hours_applied['applied']){
							$break_hours_applied = apply_filters( 'bookingpress_backend_get_break_hours',$break_hours_applied, $bookingpress_appointment_data, $current_day);
						}
						
						if(!$break_hours_applied['applied']){
							$break_hours_applied['applied'] = true;
							$get_default_work_hous_break_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_default_workhours} WHERE bookingpress_workday_key = %s AND bookingpress_is_break = 1 AND bookingpress_start_time IS NOT NULL", $current_day), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_default_workhours is table name defined globally. False Positive alarm
							if(!empty($get_default_work_hous_break_data)){
								foreach($get_default_work_hous_break_data as $working_break_hour){
									$break_hours[] = array('start'=>$working_break_hour['bookingpress_start_time'],'end'=>$working_break_hour['bookingpress_end_time']);
								}	
								$break_hours_applied['break_hours'] = $break_hours;
							}
						}
						if(!empty($break_hours_applied['break_hours'])){
							foreach($break_hours_applied['break_hours'] as $break_hour){
								if(!$selected_hours_in_break){
									$start = $break_hour['start'];
									$end = $break_hour['end'];
									if(($start_time < $start &&  $end_time > $end) || ($start_time < $end && $end_time> $end) || ($start_time > $start && $end_time <= $end) || ($start_time >= $start && $end_time < $end) || ($start_time < $start && $end_time < $end && $end_time > $start) || ($start >= $start_time && $end <= $end_time)){
										$selected_hours_in_break = true;
										break;
									}
								}
							}
						}
					}
					if($selected_hours_in_break){
						$response['variant'] = 'warning';
						$response['title']   = esc_html__('Warning', 'bookingpress-appointment-booking');
						$response['msg']     = esc_html__('You have selected break hours time, Are you sure to add appointment ?', 'bookingpress-appointment-booking');
						return $response;
					}
				}
			}

			return $bookingpress_check_custom_time_validation;
		}

		function bookingpress_modify_popover_appointment_data_query_func($appointment_query_dynamic_arr, $posted_data){
			global $bookingpress_pro_staff_members, $wpdb, $BookingPressPro;
			$is_staffmember_module_activated = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();
			if($is_staffmember_module_activated) {


				$bookingpress_modify_popover_appointment_data_query = "";
				$bookingpress_modify_popover_appointment_data_query = apply_filters( 'bookingpress_modify_popover_appointment_data_query_staff', $bookingpress_modify_popover_appointment_data_query, $appointment_query_dynamic_arr, $posted_data );
	
				if(!empty($bookingpress_modify_popover_appointment_data_query)){
					return $bookingpress_modify_popover_appointment_data_query;
				}
				


				$staff_member_filter_val = (isset($posted_data['search_data']) && isset($posted_data['search_data']['selected_staff_member'])) ? $posted_data['search_data']['selected_staff_member'] : array();
				if(!empty($staff_member_filter_val)) {
					$bookingpress_search_staff_member_id  = implode(',', $staff_member_filter_val);
					$where_query = " AND (appointment.bookingpress_staff_member_id IN ({$bookingpress_search_staff_member_id}))";
					$appointment_query_dynamic_arr['where_query'] = $where_query;
				}
				if ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
					$bookingpress_user_id        = get_current_user_id();
					$bookingpress_staffmember_id = $bookingpress_pro_staff_members->bookingpress_get_staffmember_id_using_wp_user_id( $bookingpress_user_id );
					$where_query = " AND (bookingpress_staff_member_id = {$bookingpress_staffmember_id})";
					$appointment_query_dynamic_arr['where_query'] = $where_query;
				}
				$appointment_columns=',appointment.bookingpress_staff_first_name, appointment.bookingpress_staff_last_name, appointment.bookingpress_staff_email_address';
				$appointment_query_dynamic_arr['appointment_columns'] = $appointment_columns;
			}
			return $appointment_query_dynamic_arr;
		}

		function bookingpress_modify_popover_appointment_data_func($appointment_data)
		{
			if($appointment_data) {
				global $bookingpress_pro_staff_members, $wpdb, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_staffmembers, $bookingpress_pro_appointment;
				$is_staffmember_module_activated = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();
				if($is_staffmember_module_activated) {
					if(!empty($appointment_data['bookingpress_popover_appointemnt_data'])) {
						foreach ($appointment_data['bookingpress_popover_appointemnt_data'] as $key => $appointment_userdata) {
							$staff_first_name = isset($appointment_userdata['bookingpress_staff_first_name']) ? $appointment_userdata['bookingpress_staff_first_name'] : '';
							$staff_last_name = isset($appointment_userdata['bookingpress_staff_last_name']) ? $appointment_userdata['bookingpress_staff_last_name'] : '';
							$staff_email = isset($appointment_userdata['bookingpress_staff_email_address']) ? $appointment_userdata['bookingpress_staff_email_address'] : '';
							$staff_display_name = !empty($staff_first_name) ? $staff_first_name : '';
							$staff_display_name .= !empty($staff_display_name) ? ' ' : '';
							$staff_display_name .= !empty($staff_last_name) ? $staff_last_name : '';
							$staff_display_name = empty($staff_display_name) && !empty($staff_email) ? $staff_email : $staff_display_name;
							$appointment_data['bookingpress_popover_appointemnt_data'][$key]['bookingpress_staff_displayname'] = $staff_display_name;

						}
					}
				}
				if(!empty($appointment_data['bookingpress_popover_appointemnt_data'])) {
					foreach ($appointment_data['bookingpress_popover_appointemnt_data'] as $key => $appointment_userdata) {
						$bookingpress_appointment_booking_id = isset($appointment_userdata['bookingpress_appointment_booking_id']) ? $appointment_userdata['bookingpress_appointment_booking_id'] : 0;
						$allowed_past_appointment_edit = $bookingpress_pro_appointment->bookingpress_check_allow_edit_past_appointment($bookingpress_appointment_booking_id, $appointment_data);
						$appointment_data['bookingpress_popover_appointemnt_data'][$key]['is_allow_edit_past_appointment'] = $allowed_past_appointment_edit;
					}
				}
			}
			return $appointment_data;
		}

		/**
		 * Function for check is editted appointment already booked or not
		 *
		 * @param  mixed $is_appointment_already_booked
		 * @param  mixed $bookingpress_appointment_id
		 * @return void
		 */
		function bookingpress_check_edit_is_appointment_already_booked_func($is_appointment_already_booked, $bookingpress_appointment_id){
			global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings, $bookingpress_pro_services, $tbl_bookingpress_staffmembers_services, $bookingpress_services;

			$booked_appointment_details = !empty($_POST['appointment_data']) ? $_POST['appointment_data'] : array(); //phpcs:ignore
			$selected_bring_members = ! empty($booked_appointment_details['selected_bring_members']) ? intval($booked_appointment_details['selected_bring_members']) - 1 : 0;
			$total_required_slot = 1 + $selected_bring_members;

			if(!empty($booked_appointment_details)){
				
				$bookingpress_appointment_date       = $booked_appointment_details['appointment_booked_date'];
				$bookingpress_appointment_start_time = $booked_appointment_details['appointment_booked_time'];

				if(!empty($bookingpress_appointment_id)){
					
					$bookingpress_service_id = !empty($booked_appointment_details['appointment_selected_service']) ? intval($booked_appointment_details['appointment_selected_service']) : 0;
					$bookingpress_staff_id = !empty($booked_appointment_details['selected_staffmember']) ? intval($booked_appointment_details['selected_staffmember']) : 0;

					if(!empty($bookingpress_service_id)){
						//Get Service Max Capacity
						$bookingpress_max_capacity = $bookingpress_pro_services->bookingpress_get_service_max_capacity($bookingpress_service_id);

						$bookingpress_slot_capacity = $bookingpress_pro_services->bookingpress_get_service_slot_capacity($bookingpress_service_id);

						$enable_service_slot_capacity = $bookingpress_services->bookingpress_get_service_meta( $bookingpress_service_id, 'enable_service_slot_capacity' );						
						$enable_service_slot_capacity = ($enable_service_slot_capacity == "1") ? true : false;
						$total_booked_appointment = 0;

						if(!empty($bookingpress_staff_id)){
							$bookingpress_get_staff_cap_data = $wpdb->get_row($wpdb->prepare("SELECT bookingpress_service_capacity FROM {$tbl_bookingpress_staffmembers_services} WHERE bookingpress_staffmember_id = %d AND bookingpress_service_id = %d", $bookingpress_staff_id, $bookingpress_service_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_staffmembers_services is table name defined globally. False Positive alarm

							if(!empty($bookingpress_get_staff_cap_data['bookingpress_service_capacity'])){
								$bookingpress_max_capacity = floatval($bookingpress_get_staff_cap_data['bookingpress_service_capacity']);
							}

							$total_booked_appointment_data = $wpdb->get_row($wpdb->prepare("SELECT count(bookingpress_appointment_booking_id) as total_appointment,SUM(bookingpress_selected_extra_members - 1) as total_extra_members FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id != %d AND (bookingpress_appointment_status = %s OR bookingpress_appointment_status = %s) AND bookingpress_appointment_date = %s AND bookingpress_appointment_time = %s AND bookingpress_service_id = %d AND bookingpress_staff_member_id = %d", $bookingpress_appointment_id, '2', '1', $bookingpress_appointment_date, $bookingpress_appointment_start_time, $bookingpress_service_id, $bookingpress_staff_id),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm							

							if(!empty($total_booked_appointment_data)) {
								$total_booked_appointment = $total_booked_appointment_data['total_appointment'] + $total_booked_appointment_data['total_extra_members'];
							}

						}else{
							$total_booked_appointment_data = $wpdb->get_row($wpdb->prepare("SELECT count(bookingpress_appointment_booking_id) as total_appointment,SUM(bookingpress_selected_extra_members - 1) as total_extra_members FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id != %d AND (bookingpress_appointment_status = %s OR bookingpress_appointment_status = %s) AND bookingpress_appointment_date = %s AND bookingpress_appointment_time = %s AND bookingpress_service_id = %d", $bookingpress_appointment_id, '2', '1', $bookingpress_appointment_date, $bookingpress_appointment_start_time, $bookingpress_service_id),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm

							if(!empty($total_booked_appointment_data)) {
								$total_booked_appointment = $total_booked_appointment_data['total_appointment'] + $total_booked_appointment_data['total_extra_members'];	
							}
						}

						if($enable_service_slot_capacity){
							$bookingpress_max_capacity = intval($bookingpress_max_capacity) * intval($bookingpress_slot_capacity);
						}

						if( $total_booked_appointment < $bookingpress_max_capacity) {
							$total_available_slot = $bookingpress_max_capacity - $total_booked_appointment;							
							if(	$total_required_slot > $total_available_slot ) {							
								$is_appointment_already_booked = 1;
							} else {
								$is_appointment_already_booked = 0;
							}
						} else {
							$is_appointment_already_booked = 1;
						}
					}
				}
			}

			return $is_appointment_already_booked;
		}
		
		/**
		 * Function for modify calendar appointment details listing
		 *
		 * @param  mixed $calendar_bookings_data
		 * @param  mixed $appointment_details
		 * @return void
		 */
		function bookingpress_modify_calendar_appointment_details_func($calendar_bookings_data, $appointment_details){
			global $BookingPress, $BookingPressPro;
			$use_event_title = ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) && !$BookingPressPro->bookingpress_check_capability( 'bookingpress_customers' ) ) ? true : false;
			if(!empty($appointment_details)) {
				$bookingpress_appointment_id = $appointment_details['bookingpress_appointment_booking_id'];
				foreach($calendar_bookings_data as $calendar_booking_key => $calendar_booking_val){
					if ( $use_event_title && $bookingpress_appointment_id == $calendar_booking_val['appointment_id']) {
						$bookingpress_event_slot_title = stripslashes_deep($appointment_details['bookingpress_service_name']);
						$calendar_bookings_data[$calendar_booking_key]['title'] = $bookingpress_event_slot_title;
					}
				}
			}
			if(!empty($appointment_details) && !empty($appointment_details['bookingpress_service_duration_unit']) && ($appointment_details['bookingpress_service_duration_unit'] == 'd') ){
				$bookingpress_service_duration = intval($appointment_details['bookingpress_service_duration_val']);
				$bookingpress_appointment_start_date = date('Y-m-d', strtotime($appointment_details['bookingpress_appointment_date']));
				$bookingpress_appointment_end_date = date('Y-m-d', strtotime("+{$bookingpress_service_duration} days", strtotime($bookingpress_appointment_start_date)));
				$bookingpress_appointment_id = $appointment_details['bookingpress_appointment_booking_id'];

				foreach($calendar_bookings_data as $calendar_booking_key => $calendar_booking_val){
					if($bookingpress_appointment_id == $calendar_booking_val['appointment_id']){						
						$calendar_bookings_data[$calendar_booking_key]['end'] = $bookingpress_appointment_end_date.' 00:00:00';
					}
				}
			}
			return $calendar_bookings_data;
		}
		
		/**
		 * Function for add execute code for reset the form
		 *
		 * @return void
		 */
		function bookingpress_calendar_reset_filter_func(){
			?>
			vm.search_data.selected_staff_member = '';
			vm.appointment_formdata.complete_payment_url_selection = 'do_nothing';
			vm.appointment_formdata.complete_payment_url_selected_method = [];
			<?php
		}
		
		/**
		 * Function for add execute code for reset the form
		 *
		 * @return void
		 */
		function bookingpress_calendar_add_appointment_model_reset_callback(){
			?>
			let appointment_meta_fields = vm.appointment_formdata.bookingpress_appointment_meta_fields_value;				
			for( let k in appointment_meta_fields ){
				let currentVal = appointment_meta_fields[k];
				if( "boolean" == typeof currentVal ){
					vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = false;
				} else if( "string" == typeof currentVal ){
					vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = "";
				} else if( "object" == typeof currentVal ){
					vm.appointment_formdata.bookingpress_appointment_meta_fields_value[k] = [];
				}
			}

			vm.appointment_formdata.appointment_booked_end_time = '';
			vm.appointment_formdata.appointment_send_notification = '';
            vm.appointment_formdata.appointment_custom_timing = false;
			
			let appointment_form_fields  = vm.bookingpress_form_fields;
			for( let m in appointment_form_fields ){
				let currentval = appointment_form_fields[m];					
				if(currentval.bookingpress_field_type == 'file') {
					vm.bookingpress_form_fields[m]['bpa_file_list'] = [];
				}
			}				

			vm.appointment_formdata.selected_extra_services_ids = '';
			for(m in vm.bookingpress_loaded_extras) {
				for(i in vm.bookingpress_loaded_extras[m]) {
					vm.bookingpress_loaded_extras[m][i]['bookingpress_is_selected'] = false;
				}					
			}
			vm.appointment_formdata.total_amount = 0;
			vm.appointment_formdata.total_amount_with_currency = vm2.bookingpress_price_with_currency_symbol( 0 );	
			vm.appointment_formdata.subtotal = 0;
			vm.appointment_formdata.subtotal_with_currency = vm2.bookingpress_price_with_currency_symbol( 0 );			

			vm.appointment_formdata.complete_payment_url_selection = 'do_nothing';
			vm.appointment_formdata.complete_payment_url_selected_method = [];


			/** deposit reset */
			vm.appointment_formdata.bookingpress_remove_deposit = 0;
			vm.appointment_formdata.bookingpress_applied_deposit = 0;
			vm.deposit_type = '';
			vm.deposit_amount = 0;
			vm.bookingpress_deposit_amt_without_currency = 0;
			vm.bookingpress_deposit_due_amt_without_currency = 0;
			vm.bookingpress_deposit_amt_with_currency = '' ;
			vm.bookingpress_deposit_due_amt_with_currency = '';

			<?php
		}
		
		/**
		 * Function for add dynamic vue methods for calendar module
		 *
		 * @return void
		 */
		function bookingpress_add_dynamic_vue_methods_for_calendar_func(){
			global $BookingPress, $bookingpress_notification_duration;
			?>
				validateAppointmentBeforeSave(postData){				
					const vm2 = this;
					var postDataCheck = { action:'bookingpress_validate_before_save_appointment_booking',_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' };
					postDataCheck.appointment_data = JSON.stringify(vm2.appointment_formdata);
					if(postDataCheck) {
						axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postDataCheck ) )
						.then(function(response){                            
							vm2.is_disabled = false;
							vm2.is_display_save_loader = '0';
							if(response.data.variant == 'warning') { 
								vm2.$confirm(response.data.msg, 'Warning', {
								confirmButtonText: '<?php esc_html_e( 'Ok', 'bookingpress-appointment-booking' ); ?>',
								cancelButtonText: '<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>',
								type: 'warning',
								center: true,
								customClass: 'bpa_custom_timing_warning_notification',
								}).then(() => {
									vm2.is_disabled = true;
									vm2.is_display_save_loader = '1';
									vm2.saveProAppointmentBooking_final(postData);
								}).catch(()=>{
									vm2.is_disabled = false;
									vm2.is_display_save_loader = '0';                                
								});
							}else if(response.data.variant == 'error'){
								vm2.$notify({
									title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
									message: response.data.msg,
									type: 'error',
									customClass: 'error_notification',
									duration:<?php echo intval($bookingpress_notification_duration); ?>,
								});                            
							} else if(response.data.variant == 'success') { 
								vm2.is_disabled = true;
								vm2.is_display_save_loader = '1';
								vm2.saveProAppointmentBooking_final(postData);
							}
						}).catch(function(error){
							console.log(error);
							vm2.$notify({
								title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
								message: '<?php esc_html_e('Something went wrong..', 'bookingpress-appointment-booking'); ?>',
								type: 'error',
								customClass: 'error_notification',
								duration:<?php echo intval($bookingpress_notification_duration); ?>,
							});
						});
					}
				},
				handleMarkAsPaid_calendar(event_data){
					const vm3 = this;

					if( event_data == "mark_as_paid") {
						vm3.appointment_formdata.bookingpress_remove_deposit = 1;
					} else {
						vm3.appointment_formdata.bookingpress_remove_deposit = 0;
					}
					<?php do_action( 'bookingpress_mark_as_paid_action_external'); ?>
					 
				},
				 
				saveProAppointmentBooking_final(bookingAppointment){
					const vm = this;
					

					let is_past_appointment_edit = typeof vm.appointment_formdata.is_allow_edit_past_appointment !== 'undefined' ? vm.appointment_formdata.is_allow_edit_past_appointment : 0;

					if(is_past_appointment_edit == 1){
						if(vm.bookingpress_payment_gateway == "on-site" || vm.bookingpress_payment_gateway == "manual"){
							vm.saveAppointmentBooking(bookingAppointment);
						}
						else {
							let actual_paid_amount = parseFloat(vm.appointment_formdata.bookingpress_paid_amount);
							let total_after = parseFloat(vm.appointment_formdata.total_amount);
							let is_partial_refund_supported = vm.appointment_formdata.is_partial_refund_supported;

							let show_refund_confirmation = true;
							if( -1 < [3,5,'3','5'].indexOf( vm.appointment_formdata.bookingpress_payment_status ) ){
								show_refund_confirmation = false;
							}

							if(vm.appointment_formdata.applied_coupon_code !="" && true == show_refund_confirmation && actual_paid_amount != total_after && (typeof vm.appointment_formdata.bookingpress_is_cart === 'undefined' ||vm.appointment_formdata.bookingpress_is_cart == 0) ){
								if(actual_paid_amount > total_after){
									let confirmMessage = is_partial_refund_supported 
										? '<?php esc_html_e( 'You want to refund remaining amount?', 'bookingpress-appointment-booking' ); ?>'
										: '<?php esc_html_e( 'You have applied coupon code, Do you want to proceed further?', 'bookingpress-appointment-booking' ); ?>';

									let applied_coupon_code = vm.appointment_formdata.applied_coupon_code;

									let confirmButton = is_partial_refund_supported ? '<?php esc_html_e( 'Refund', 'bookingpress-appointment-booking' ); ?>' : '<?php esc_html_e( 'Ok', 'bookingpress-appointment-booking' ); ?>';
									vm.$confirm(confirmMessage, 'Warning', {
										confirmButtonText: confirmButton,
										cancelButtonText: '<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>',
										type: 'warning',
										center: true,
										customClass: '',
									}).then(() => {
										vm.is_disabled = true;
										vm.is_display_save_loader = '1';
										if(is_partial_refund_supported == 1){			
											vm.refundBeforeSave(bookingAppointment);															
										} else {
											vm.saveAppointmentBooking(bookingAppointment);
										}
									}).catch(()=>{
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
					}else {
						vm.saveAppointmentBooking(bookingAppointment);
					}
				},		
				saveProAppointmentBooking(bookingAppointment){
					const vm = new Vue();
					const vm2 = this;
					let is_timeslot_display = vm2.is_timeslot_display;
					if( '0' == is_timeslot_display ){
						vm2[bookingAppointment].appointment_booked_time = "00:00:00";
					}
					if(vm2.appointment_formdata.appointment_custom_timing == false) {
						vm2.saveProAppointmentBooking_final(bookingAppointment);

						
					}else{
						this.$refs[bookingAppointment].validate((valid) => {
							if (valid) {
								let bookingpress_confirm_validate = 1;
								if(vm2.appointment_formdata.appointment_booked_time > vm2.appointment_formdata.appointment_booked_end_time && vm2.appointment_formdata.appointment_custom_timing == true && vm2.appointment_formdata.selected_service_duration_unit != 'd') {
									bookingpress_confirm_validate = 0;
									vm2.is_disabled = false;
									vm2.is_display_save_loader = '0';
									vm2.$notify({
										title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
										message: '<?php echo addslashes( esc_html__('Start time is not greater than End time', 'bookingpress-appointment-booking') ); //phpcs:ignore ?>',
										type: 'error',
										customClass: 'error_notification',
										duration:<?php echo intval($bookingpress_notification_duration); ?>,
									});
								}else if(vm2.appointment_formdata.appointment_booked_time == vm2.appointment_formdata.appointment_booked_end_time && vm2.appointment_formdata.appointment_custom_timing == true && vm2.appointment_formdata.selected_service_duration_unit != 'd') {    
									bookingpress_confirm_validate = 0;  
									vm2.is_disabled = false;
									vm2.is_display_save_loader = '0';              
									vm2.$notify({
										title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
										message: '<?php echo addslashes( esc_html__('Start time and End time are not same', 'bookingpress-appointment-booking') ); //phpcs:ignore ?>',
										type: 'error',
										customClass: 'error_notification',
										duration:<?php echo intval($bookingpress_notification_duration); ?>,
									});
								}else if(vm2.appointment_formdata.appointment_custom_timing == true){
									let selected_date_time = new Date(`${vm2.appointment_formdata.appointment_booked_date} ${vm2.appointment_formdata.appointment_booked_time}`);
									let is_past_date = selected_date_time < new Date();
									if(is_past_date) {
										bookingpress_confirm_validate = 0;
										vm2.$confirm('<?php esc_html_e( 'You have selected past time for the appointment, Do you still want to continue?', 'bookingpress-appointment-booking' ); ?>', 'Warning', {
											confirmButtonText: '<?php esc_html_e( 'Ok', 'bookingpress-appointment-booking' ); ?>',
											cancelButtonText: '<?php esc_html_e( 'Cancel', 'bookingpress-appointment-booking' ); ?>',
											type: 'warning',
											center: true,
											customClass: 'bpa_custom_timing_warning_notification',
										}).then(() => {
											vm2.is_disabled = true;
											vm2.is_display_save_loader = '1';								
											vm2.validateAppointmentBeforeSave(bookingAppointment);								
										}).catch(()=>{
											vm2.is_disabled = false;
											vm2.is_display_save_loader = '0';                                
										});
									}
								}
								if(vm2.appointment_formdata.appointment_custom_timing == true && bookingpress_confirm_validate == 1) {
									vm2.validateAppointmentBeforeSave(bookingAppointment);
								}					
								
							}
						});
					}					
				},
				refundBeforeSave(postData){				
					const vm2 = this;
					console.log("called")
					var postDataCheck = { action:'bookingpress_refund_before_save_appointment_booking',_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' };
					postDataCheck.appointment_data = JSON.stringify(vm2.appointment_formdata);
					if(postDataCheck) {
						axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postDataCheck ) )
						.then(function(response){   
							vm2.saveAppointmentBooking(postData);
						}).catch(function(error){
							console.log(error);
							vm2.$notify({
								title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
								message: '<?php esc_html_e('Something went wrong..', 'bookingpress-appointment-booking'); ?>',
								type: 'error',
								customClass: 'error_notification',
								duration:<?php echo intval($bookingpress_notification_duration); ?>,
							});
						});
					}
            	}, 
				bookingpress_calendar_staffmember_filter(currentelement, value) {
					const vm = this;
					vm.bpa_display_calendar_loader = 1;
					let all_staff_target = document.querySelector( '.bpa-sf-item.bpa-sf-item__is-all-staff' );
					let staff_member_arr = [];
					if(value=='all') {
						let list_items = document.querySelectorAll( '.bpa-sf-item-data.__bpa-is-active' );
						if( list_items.length > 0 ){
							for( l = 0; l < list_items.length; l++ ){
								list_items[l].classList.remove("__bpa-is-active")
							}
						}
						if(currentelement.target.classList.contains('__bpa-is-active')) {
							all_staff_target.classList.remove('__bpa-is-active');
						}
						else {
							all_staff_target.classList.add('__bpa-is-active');
						}
						vm.search_data.selected_staff_member = staff_member_arr;
					}
					else {
						all_staff_target.classList.remove('__bpa-is-active');
						
						let staffItem = document.querySelector(`.bpa-sf-item[value="${value}"]`);
						if (staffItem) {
							staffItem.classList.toggle('__bpa-is-active');
							if (staffItem.classList.contains('__bpa-is-active')) {
								// Add staff member if selected
								vm.search_data.selected_staff_member.push(value);
							} else {
								// Remove staff member if deselected
								vm.search_data.selected_staff_member = vm.search_data.selected_staff_member.filter(item => item !== value);
							}
						}
						if (vm.search_data.selected_staff_member.length === 0) {
                			all_staff_target.classList.add('__bpa-is-active');
						}
					}					
					vm.loadCalendar(vm.activeView);
				},
				bookingpress_calendar_staffmember_filter_change(selected_val){
					const vm= this;
					vm.bpa_display_calendar_loader = 1;
					vm.search_data.selected_staff_member=selected_val;
					vm.loadCalendar(vm.activeView);
				},
				bpa_move_staff_nav_next(){
					let element = document.getElementById( "bpa-sf-items-wrapper" );

					let scrollLeft = element.scrollLeft;

					element.scrollTo({
						left: ( scrollLeft + 150 ),
						behavior: "smooth",
					});
				},
				bpa_move_staff_nav_prev(){
					let element = document.getElementById( "bpa-sf-items-wrapper" );

					let scrollLeft = element.scrollLeft;

					element.scrollTo({
						left: ( scrollLeft - 150 ),
						behavior: "smooth",
					});
				},
				change_custom_end_time(worktime){
					const vm = this;
					let start_time = vm.appointment_formdata.appointment_booked_time;
					let end_time = worktime;

					vm.appointment_formdata.is_next_day = false;
					vm.appointment_formdata.is_both_next_day = false;
					vm.appointment_formdata.appointment_temp_booked_end_time;
					vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.appointment_booked_date;
					
					if( start_time >= '24:00:00' ){
						vm.appointment_formdata.is_next_day = true;
						vm.appointment_formdata.is_both_next_day = true;
					}

					if( end_time >= '24:00:00' ){
						vm.appointment_formdata.is_next_day = true;
					}

					if( true == vm.appointment_formdata.is_next_day ){
						let booked_date = new Date( vm.appointment_formdata.appointment_booked_date );
						booked_date.setDate( booked_date.getDate() + 1 );
						vm.appointment_formdata.appointment_booked_end_date = booked_date.toISOString().split("T")[0];
					}
					<?php do_action( 'bookingpress_after_change_custom_end_timing_backend'); ?>				
				},				
				change_custom_start_time(event){
					const vm = this;
					if(vm.appointment_formdata.appointment_custom_timing == true){
						vm.appointment_formdata.appointment_booked_end_time = '';
					}

					let worktime = event;

					/* Logic to auto select End Time */
					if(typeof vm.appointment_formdata.appointment_selected_service != "undefined" && vm.appointment_formdata.appointment_selected_service !=""){
						if(typeof vm.appointment_formdata.selected_service_duration != "undefined" && vm.appointment_formdata.selected_service_duration !=""){
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
								} 
							}
						}
					}
					/* Logic to auto select End Time */

					vm.appointment_formdata.default_appointment_timing.forEach( (element,index) =>{
						vm.appointment_formdata.default_appointment_timing[index].is_visible = false;
					});

					vm.appointment_formdata.default_appointment_timing.forEach( (element,index) =>{
						if( element.start_time_val == worktime ){
							for( let i = 0; i <= 287; i++ ){
								vm.appointment_formdata.default_appointment_timing[ index + i ].is_visible = true;
							}
						}
					});

					vm.appointment_formdata.is_next_day = false;
					vm.appointment_formdata.is_both_next_day = false;

					let start_time = worktime;
					let end_time = vm.appointment_formdata.appointment_booked_end_time;

					if( "" != end_time && end_time > '24:00:00' ){
						vm.appointment_formdata.is_next_day = true;
					}

					<?php do_action( 'bookingpress_after_change_custom_start_timing_backend'); ?>
				},    
				default_timeConvertToTimestamp(timeString) {
					let [hours, minutes, seconds] = timeString.split(":").map(Number);
					return hours * 3600 + minutes * 60 + seconds;
				},        
				handleCustomTimingChange(event){
					const vm = this;
					if(vm.appointment_formdata.appointment_custom_timing == false){
						vm.appointment_formdata.appointment_booked_time = '';
						vm.appointment_formdata.appointment_booked_end_time = '';
					}
					<?php do_action( 'bookingpress_after_select_custom_timing_backend'); ?>
					if(vm.appointment_formdata.appointment_custom_timing == true && vm.appointment_formdata.selected_service_duration_unit == 'd'){
						vm.filter_pickerOptions.disabledDate = function(Time){
							return false;
						};
					}
					if(vm.is_timeslot_display != '0' && vm.appointment_formdata.appointment_custom_timing == true){
						vm.appointment_formdata.appointment_booked_time = '';
						vm.appointment_formdata.appointment_booked_end_time = '';
					}					
					if(vm.is_timeslot_display == '0' && vm.appointment_formdata.appointment_custom_timing == true){
						vm.appointment_formdata.appointment_booked_end_date = vm.appointment_formdata.appointment_booked_date; 
					}else{
						vm.appointment_formdata.appointment_booked_end_date = '';
					}					
				},				
            <?php
            do_action('bookingpress_customer_add_dynamic_vue_methods');
		}
		
		/**
		 * Function for modify calendar loading data
		 *
		 * @param  mixed $calendar_bookings_data
		 * @return void
		 */
		function bookingpress_modify_calendar_loading_data_func($calendar_bookings_data){
			global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings;
			if(!empty($calendar_bookings_data) && is_array($calendar_bookings_data) ){
				foreach($calendar_bookings_data as $k => $v){
					$bookingpress_appointment_id = $v['appointment_id'];

					$bookingpress_appointment_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $bookingpress_appointment_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm

					if(!empty($bookingpress_appointment_data['bookingpress_service_duration_unit']) && ($bookingpress_appointment_data['bookingpress_service_duration_unit'] == "d") ){
						$bookingpress_service_duration_val = $bookingpress_appointment_data['bookingpress_service_duration_val'];

						$bookingpress_appointment_end_date = date('Y-m-d H:i:s', strtotime($v['start']." +".$bookingpress_service_duration_val." days"));
						$calendar_bookings_data[$k]['end'] = $bookingpress_appointment_end_date;
					}
				}
			}
			return $calendar_bookings_data;
		}
		
		/**
		 * Function for modify calendar appointment class as per appointment status
		 *
		 * @param  mixed $bookingpress_appointment_class
		 * @param  mixed $bookingpress_appointment_status
		 * @return void
		 */
		function bookingpress_modify_calendar_appointment_class_func( $bookingpress_appointment_class, $bookingpress_appointment_status ) {
			if($bookingpress_appointment_status == '5'){
				$bookingpress_appointment_class .= ' bpa-cal-event-card--no-show';
			}else if($bookingpress_appointment_status == '6'){
				$bookingpress_appointment_class .= ' bpa-cal-event-card--completed';
			}
			return $bookingpress_appointment_class;
		}
		
		/**
		 * Function for modify calendar module data fields
		 *
		 * @param  mixed $bookingpress_calendar_vue_data_fields
		 * @return void
		 */
		function bookingpress_modify_calendar_data_fields_func( $bookingpress_calendar_vue_data_fields ) {
			global $wpdb, $BookingPressPro, $bookingpress_pro_staff_members, $BookingPress, $bookingpress_service_extra, $bookingpress_bring_anyone_with_you, $tbl_bookingpress_staffmembers, $bookingpress_coupons, $tbl_bookingpress_form_fields, $bookingpress_global_options, $bookingpress_pro_services, $tbl_bookingpress_extra_services, $tbl_bookingpress_staffmembers_services,$bookingpress_services,$bookingpress_deposit_payment;

			$bookingpress_calendar_vue_data_fields['is_timeslot_display'] = '1';

			$bookingpress_calendar_vue_data_fields['is_staffmember_activated'] = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();
			
			$bookigpress_time_format_for_booking_form =  $BookingPress->bookingpress_get_customize_settings('bookigpress_time_format_for_booking_form','booking_form');
			$bookigpress_time_format_for_booking_form =  !empty($bookigpress_time_format_for_booking_form) ? $bookigpress_time_format_for_booking_form : '2';
			$bookingpress_calendar_vue_data_fields['bookigpress_time_format_for_booking_form'] = $bookigpress_time_format_for_booking_form;			

			//Add appointment data variables
			$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
			
			$bookingpress_singular_staffmember_name = !empty($bookingpress_global_options_arr['bookingpress_staffmember_singular_name']) ? $bookingpress_global_options_arr['bookingpress_staffmember_singular_name'] : esc_html_e('Staff Member', 'bookingpress-appointment-booking');


			/* Deposit Add Appointment Data Start Here */
			$deposit_payment_module = $bookingpress_deposit_payment->bookingpress_check_deposit_payment_module_activation();
			$bookingpress_calendar_vue_data_fields['deposit_payment_module'] = $deposit_payment_module;			
			$bookingpress_deposit_payment_method = $BookingPress->bookingpress_get_settings( 'bookingpress_allow_customer_to_pay', 'payment_setting' );
			$bookingpress_calendar_vue_data_fields['bookingpress_deposit_payment_method'] = $bookingpress_deposit_payment_method;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_deposit_payment_method'] = $bookingpress_deposit_payment_method;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_applied_deposit'] = "0";
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_remove_deposit'] = "0";
			/* Deposit Add Appointment Data Over Here */

			/** multiple file upload max limit reached message */
			$bpa_file_upload_max_limit_msg = $BookingPress->bookingpress_get_customize_settings('fileupload_max_limit_reach_msg','booking_form');

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bpa_fileupload_max_limit_reach_msg'] = !empty( $bpa_file_upload_max_limit_msg ) ? stripslashes_deep( $bpa_file_upload_max_limit_msg ) : __("You've reached the maximum file limit", 'bookingpress-appointment-booking');

			$bookingpress_calendar_vue_data_fields['selected_staffmember'] = '';
			$bookingpress_calendar_vue_data_fields['rules']['selected_staffmember'] = array(
                array(
                    'required' => true,
                    'message'  => esc_html__('Please select', 'bookingpress-appointment-booking')." ".esc_html($bookingpress_singular_staffmember_name),
                    'trigger'  => 'change',
                ),
            );
			$bookingpress_calendar_vue_data_fields['rules']['appointment_booked_end_date'] = array(
				array(
					'required' => true,
					'message'  => __('Please select booking end date', 'bookingpress-appointment-booking'),
					'trigger'  => 'change',
				),
            );			
			$bookingpress_calendar_vue_data_fields['rules']['appointment_booked_end_time'] = array(
				array(
					'required' => true,
					'message'  => __('Please select booking end time', 'bookingpress-appointment-booking'),
					'trigger'  => 'change',
				),
            );

			$bookingpress_check_user_role = $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' );
			$bookingpress_calendar_vue_data_fields['bpa_chk_staff_role'] = $bookingpress_check_user_role;

			$bookingpress_calendar_vue_data_fields['bpa_get_current_staff_id'] = get_current_user_id();

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['appointment_booked_end_date'] = '';			
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['appointment_custom_timing'] = false;

            $default_appointment_timing = $BookingPressPro->bookingpress_get_default_appointment_timing();
            $bookingpress_calendar_vue_data_fields['appointment_formdata']['default_appointment_timing'] = $default_appointment_timing;

			$bookingpress_calendar_vue_data_fields['bookingpress_extras_popover_modal'] = false;
			$bookingpress_calendar_vue_data_fields['bookingpress_service_extras'] = array();

			$bookingpress_calendar_vue_data_fields['is_tax_enable'] = (is_plugin_active('bookingpress-tax/bookingpress-tax.php'))?1:0;
			$bookingpress_calendar_vue_data_fields['is_custom_service_duration'] = (is_plugin_active('bookingpress-custom-service-duration/bookingpress-custom-service-duration.php'))?1:0;			

			$bookingpress_calendar_vue_data_fields['is_extras_enable'] = $bookingpress_service_extra->bookingpress_check_service_extra_module_activation();
			$bookingpress_calendar_vue_data_fields['is_staff_enable'] = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();
			$bookingpress_calendar_vue_data_fields['is_bring_anyone_with_you_enable'] = $bookingpress_bring_anyone_with_you->bookingpress_check_bring_anyone_module_activation();
			$bookingpress_calendar_vue_data_fields['is_coupon_enable'] = $bookingpress_coupons->bookingpress_check_coupon_module_activation();
			$bookingpress_calendar_vue_data_fields['bookingpress_allow_coupon_code'] = $bookingpress_coupons->bookingpress_check_coupon_module_activation();

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_staffmembers_lists'] = array();
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_bring_anyone_max_capacity'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_bring_anyone_min_capacity'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_bring_anyone_step'] = 1;

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['selected_extra_services'] = array();
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['selected_extra_services_ids'] = '';
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['selected_staffmember'] = '';
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['selected_bring_members'] = 0;

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['subtotal'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['subtotal_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0);
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['extras_total'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['extras_total_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0);
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['tax_percentage'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['tax'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['tax_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0);

			$bookingpress_price_setting_display_option = $BookingPress->bookingpress_get_settings('price_settings_and_display', 'payment_setting');
            $bookingpress_calendar_vue_data_fields['appointment_formdata']['tax_price_display_options'] = $bookingpress_price_setting_display_option;

            $bookingpress_tax_order_summary = $BookingPress->bookingpress_get_settings('display_tax_order_summary', 'payment_setting');
            $bookingpress_calendar_vue_data_fields['appointment_formdata']['display_tax_order_summary'] = $bookingpress_tax_order_summary;

            $bookingpress_tax_order_summary_text = $BookingPress->bookingpress_get_settings('included_tax_label', 'payment_setting');
            $bookingpress_calendar_vue_data_fields['appointment_formdata']['included_tax_label'] = stripslashes_deep($bookingpress_tax_order_summary_text);

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['applied_coupon_code'] = '';
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['applied_coupon_details'] = array();
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['coupon_discounted_amount'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['coupon_discounted_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0);
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['total_amount'] = 0;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['total_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0);

			$bookingpress_calendar_vue_data_fields['appointment_formdata']['mark_as_paid'] = false;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['complete_payment_url_selection'] = 'do_nothing';
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['complete_payment_url_selected_method'] = array();

			$bookingpress_calendar_vue_data_fields['coupon_apply_loader'] = 0;
			$bookingpress_calendar_vue_data_fields['coupon_code_msg'] = '';
			$bookingpress_calendar_vue_data_fields['bpa_coupon_apply_disabled'] = 0;
			$bookingpress_calendar_vue_data_fields['bpa_multi_appoitnment_coupon_apply_disabled'] = 0;
			$bookingpress_calendar_vue_data_fields['coupon_applied_status'] = '';

			//Get custom fields
			$bookingpress_form_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_form_fields} WHERE bookingpress_field_is_default = %d AND bookingpress_is_customer_field = %d ORDER BY bookingpress_field_position ASC", 0, 0), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_form_fields is table name defined globally. False Positive alarm

			$bookingpress_listing_fields_value = $bookingpress_appointment_meta_fields_value = array();
			if(!empty($bookingpress_form_fields)){
				foreach($bookingpress_form_fields as $k3 => $v3){
					
					$bookingpress_form_fields[$k3]['bookingpress_field_error_message']= stripslashes_deep($v3['bookingpress_field_error_message']);
					$bookingpress_form_fields[$k3]['bookingpress_field_label'] = stripslashes_deep($v3['bookingpress_field_label']);
					$bookingpress_form_fields[$k3]['bookingpress_field_placeholder'] = stripslashes_deep($v3['bookingpress_field_placeholder']);

					$bookingpress_field_meta_key = $v3['bookingpress_field_meta_key'];
					$bookingpress_field_options = json_decode($v3['bookingpress_field_options'], TRUE);
					$bookingpress_form_fields[$k3]['bookingpress_field_options'] = $bookingpress_field_options;
					if($v3['bookingpress_field_type'] == "checkbox"){
						$bookingpress_field_values = json_decode($v3['bookingpress_field_values'], TRUE);


						$temp_form_fields_data = array();
						$fmeta_key = $bookingpress_field_meta_key;

						foreach( $bookingpress_field_values as $k4 => $v4 ){
							$bookingpress_form_fields[$k3][ $fmeta_key] [ $k4 ] = '';	
						}

						$bookingpress_appointment_meta_fields_value[$fmeta_key] = array();
						
						$bookingpress_form_fields[$k3]['selected_services'] = (isset($bookingpress_field_options['selected_services']))?$bookingpress_field_options['selected_services']:'';
					}else{
						$bookingpress_form_fields[$k3]['selected_services'] = (isset($bookingpress_field_options['selected_services']))?$bookingpress_field_options['selected_services']:'';
						$bookingpress_appointment_meta_fields_value[$bookingpress_field_meta_key] = '';
						$bookingpress_listing_fields_value[$bookingpress_field_meta_key] = array(
							'label' => $v3['bookingpress_field_label'],
							'value' => '',
						);
					}
				}
			}

			if(!empty($bookingpress_form_fields)){
				foreach($bookingpress_form_fields as $k4 => $v4){
					if(($v4['bookingpress_form_field_name'] == "Repeater") || ($v4['bookingpress_form_field_name'] == "2 Col") || ($v4['bookingpress_form_field_name'] == "3 Col") || ($v4['bookingpress_form_field_name'] == "4 Col") ){
						unset($bookingpress_form_fields[$k4]);
					}
				}

				$bookingpress_form_fields = array_values($bookingpress_form_fields);
			}

			if( !empty( $bookingpress_form_fields ) ) {
				$bookingpress_temp_form_fields = [];
				$n5 = 0;
				foreach( $bookingpress_form_fields as $k5 => $v5 ){

					if( 'file' == $v5['bookingpress_field_type'] ){
						$action_url = admin_url('admin-ajax.php');
						$action_data = array(
							'action' => 'bpa_front_file_upload',
							'_wpnonce' => wp_create_nonce( 'bpa_file_upload_' . $v5['bookingpress_field_meta_key'] ),
							'field_key' => $v5['bookingpress_field_meta_key']
						);
						$v5['bpa_action_url'] = $action_url;
						$v5['bpa_ref_name'] = str_replace('_', '', $v5['bookingpress_field_meta_key']);
						$action_data['bpa_ref'] =$v5['bpa_ref_name'];
						$v5['bpa_file_list'] = array();
						$v5['bpa_action_data'] = $action_data;
						$action_data['bpa_accept_files'] = !empty( $v5['bookingpress_field_options']['allowed_file_ext'] ) ?  base64_encode( $v5['bookingpress_field_options']['allowed_file_ext'] ) : '';
					}

					$v5['is_repeater_field_inner_field'] = false;
					if(!empty($bookingpress_repeater_inner_field_ids)) {
						if(in_array($v5['bookingpress_form_field_id'], $bookingpress_repeater_inner_field_ids)) {
							$v5['is_repeater_field_inner_field'] = true;
						}
					}

					if( ( ( $n5 + 1 ) % 3 ) == 0 ){
						$v5['is_separator'] = false;
						$bookingpress_temp_form_fields[] = $v5;
						$bookingpress_temp_form_fields[] = array(
							'is_separator' => true
						);
					} else {
						$v5['is_separator'] = false;
						$bookingpress_temp_form_fields[] = $v5;
					}
					$n5++;
				}
				$bookingpress_form_fields = $bookingpress_temp_form_fields;
			}

			$bookingpress_calendar_vue_data_fields['bookingpress_form_fields'] = $bookingpress_form_fields;
			$bookingpress_calendar_vue_data_fields['appointment_formdata']['bookingpress_appointment_meta_fields_value'] = $bookingpress_appointment_meta_fields_value;
			$bookingpress_calendar_vue_data_fields['bookingpress_listing_fields_value'] = $bookingpress_listing_fields_value;

			//Add Customer Data Variables
			$bookingpress_calendar_vue_data_fields['open_customer_modal'] = false;
			$bookingpress_options = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_country_list = $bookingpress_options['country_lists'];
			$bookingpress_phone_country_option = $BookingPress->bookingpress_get_settings('default_phone_country_code', 'general_setting');

			$bookingpress_calendar_vue_data_fields['phone_countries_details'] = json_decode($bookingpress_country_list);
			$bookingpress_calendar_vue_data_fields['loading'] = false;
			$bookingpress_calendar_vue_data_fields['customer'] = array(
				'avatar_url' => '',
				'avatar_name' => '',
				'avatar_list' => array(),
				'wp_user' => null,
				'firstname' => '',
				'lastname' => '',
				'username' => '',
				'email' => '',
				'phone' => '',
				'customer_phone_country' => $bookingpress_phone_country_option,
				'customer_phone_dial_code' => '',
				'note' => '',
				'update_id' => 0,
				'_wpnonce' => '',
				'password' => '',
			);
			
			$bpa_customer_form_fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$tbl_bookingpress_form_fields}` WHERE bookingpress_is_customer_field = %d ORDER BY bookingpress_field_position ASC", 1 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_form_fields is table name.
            $bpa_customer_fields = array();
            if( !empty( $bpa_customer_form_fields ) ){
                foreach( $bpa_customer_form_fields as $x => $cs_form_fields ){
                    //$bpa_customer_fields['field_id'] = $cs_form_fields['']   
                    $bpa_customer_fields[ $x ] = $cs_form_fields;
                    $bpa_customer_fields[ $x ]['bookingpress_field_values'] = json_decode( $cs_form_fields['bookingpress_field_values'], true );
                    $bpa_customer_fields[ $x ]['bookingpress_field_options'] = json_decode( $cs_form_fields['bookingpress_field_options'], true );
                    $bpa_customer_fields[ $x ]['bookingpress_field_key'] = '';//$cs_form_fields['bookingpress_field_meta_key'];
                    if( 'checkbox' == $cs_form_fields['bookingpress_field_type'] ){
                        $bpa_customer_fields[ $x ]['bookingpress_field_key'] = array();
                        foreach( $bpa_customer_fields[ $x ]['bookingpress_field_values'] as $chk_key => $chk_val ){
                            //$bpa_customer_fields[ $x ]['bookingpress_field_key'][ $chk_key ] = false;
							$bookingpress_calendar_vue_data_fields['customer']['bpa_customer_field'][ $cs_form_fields['bookingpress_field_meta_key'] . '_' . $chk_key ] = false;
                        }
						$bookingpress_calendar_vue_data_fields['customer']['bpa_customer_field'][ $cs_form_fields['bookingpress_field_meta_key']] = array();
                    } else {
						$bookingpress_calendar_vue_data_fields['customer']['bpa_customer_field'][$cs_form_fields['bookingpress_field_meta_key']] = $bpa_customer_fields[ $x ]['bookingpress_field_key'];
					}
                }
            }
            $bookingpress_calendar_vue_data_fields['bookingpress_customer_fields'] = $bpa_customer_fields;

			$bookingpress_custom_fields = $bookingpress_calendar_vue_data_fields['bookingpress_form_fields'];
			$bookingpress_custom_fields_validation_arr = array();
			if(!empty($bookingpress_custom_fields)){
				foreach($bookingpress_custom_fields as $custom_field_key => $custom_field_val){
					if(isset($custom_field_val['bookingpress_field_is_default']) && $custom_field_val['bookingpress_field_is_default'] == 0 ) {
						$bookingpress_field_meta_key = $custom_field_val['bookingpress_field_meta_key'];

						if(isset($custom_field_val['bookingpress_field_required']) && $custom_field_val['bookingpress_field_required'] == 1) {
							$bookingpress_field_err_msg = stripslashes_deep($custom_field_val['bookingpress_field_error_message']);						
							$bookingpress_field_err_msg = empty($bookingpress_field_err_msg) && !empty($custom_field_val['bookingpress_field_label']) ? stripslashes_deep($custom_field_val['bookingpress_field_label']).' '.__('is required','bookingpress-appointment-booking') : $bookingpress_field_err_msg;
							$bookingpress_custom_fields_validation_arr[$bookingpress_field_meta_key][] = array(
								'required' => 1,
								'message' => $bookingpress_field_err_msg,
								'trigger' => 'change'
							);					
						}
											
						if(!empty($custom_field_val['bookingpress_field_options']['minimum'])) {
							$bookingpress_custom_fields_validation_arr[ $bookingpress_field_meta_key][] = array( 
								'min' => intval($custom_field_val['bookingpress_field_options']['minimum']),
								'message'  => __('Minimum','bookingpress-appointment-booking').' '.$custom_field_val['bookingpress_field_options']['minimum'].' '.__('character required','bookingpress-appointment-booking'),
								'trigger'  => 'blur',
							);
						}
						if(!empty($custom_field_val['bookingpress_field_options']['maximum'])) {
							$bookingpress_custom_fields_validation_arr[$bookingpress_field_meta_key][] = array( 
								'max' => intval($custom_field_val['bookingpress_field_options']['maximum']),
								'message'  => __('Maximum','bookingpress-appointment-booking').' '.$custom_field_val['bookingpress_field_options']['maximum'].' '.__('character allowed','bookingpress-appointment-booking'),
								'trigger'  => 'blur',
							);
						}
					}
				}
			}
			$bookingpress_allow_customer_create = $BookingPress->bookingpress_get_settings('allow_wp_user_create', 'customer_setting');
            $bookingpress_allow_customer_create = ! empty($bookingpress_allow_customer_create) ? $bookingpress_allow_customer_create : 'false';
            $bookingpress_allow_customer_create = $bookingpress_allow_customer_create == 'true' ? true : false;

			$bookingpress_calendar_vue_data_fields['custom_field_rules'] = $bookingpress_custom_fields_validation_arr;

			$bookingpress_calendar_vue_data_fields['customer_detail_save'] = false;
			$bookingpress_calendar_vue_data_fields['wpUsersList'] = array();
			$bookingpress_calendar_vue_data_fields['savebtnloading'] = false;
			$bookingpress_calendar_vue_data_fields['customer_rules'] = array(
				'firstname' => array(
					array(
						'required' => true,
						'message'  => esc_html__('Please enter firstname', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
				'lastname'  => array(
					array(
						'required' => true,
						'message'  => esc_html__('Please enter lastname', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
				'username' => array(
					array(
						'required' => true,
						'message'  => esc_html__('Please enter username', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
				'email'     => array(
					array(
						'required' => true,
						'message'  => esc_html__('Please enter email address', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
					array(
						'type'    => 'email',
						'message' => esc_html__('Please enter valid email address', 'bookingpress-appointment-booking'),
						'trigger' => 'blur',
					),
				),
				'wp_user' => array(
					array(
						'required' => $bookingpress_allow_customer_create,
						'message'  => esc_html__('Please select Wordpress User', 'bookingpress-appointment-booking'),
						'trigger'  => 'blur',
					),
				),
			);

			$bookingpress_calendar_vue_data_fields['cusShowFileList'] = false;
			$bookingpress_calendar_vue_data_fields['is_display_loader'] = '0';
			$bookingpress_calendar_vue_data_fields['bpa_display_calendar_loader'] = 0;
			$bookingpress_calendar_vue_data_fields['is_disabled'] = false;
			$bookingpress_calendar_vue_data_fields['is_display_save_loader'] = '0';
			$bookingpress_calendar_vue_data_fields['bookingpress_tel_input_props'] = array(
				'defaultCountry' => $bookingpress_phone_country_option,
				'validCharactersOnly' => true,
				'inputOptions' => array(
					'placeholder' => '',
				)
			);
			if ( ! empty( $bookingpress_phone_country_option ) && $bookingpress_phone_country_option == 'auto_detect' ) {
				// Get visitors ip address
				$bookingpress_ip_address = $BookingPressPro->boookingpress_get_visitor_ip();
				try {
					$bookingpress_country_reader = new Reader( BOOKINGPRESS_PRO_LIBRARY_DIR . '/geoip/inc/GeoLite2-Country.mmdb' );
					$bookingpress_country_record = $bookingpress_country_reader->country( $bookingpress_ip_address );
					if ( ! empty( $bookingpress_country_record->country ) ) {
						$bookingpress_country_name     = $bookingpress_country_record->country->name;
						$bookingpress_country_iso_code = $bookingpress_country_record->country->isoCode;
						$bookingpress_calendar_vue_data_fields['bookingpress_tel_input_props']['defaultCountry'] = $bookingpress_country_iso_code;
					}
				} catch ( Exception $e ) {
					$bookingpress_error_message = $e->getMessage();
				}
			}

			$bookingpress_calendar_vue_data_fields['wordpress_user_id'] = '';

			$bookingpress_loaded_services = $bookingpress_calendar_vue_data_fields['appointment_services_list'];
			$bookingpress_service_extras = $bookingpress_service_staffmembers = array();
			
			if(!empty($bookingpress_loaded_services)){
				foreach($bookingpress_loaded_services as $service_key => $service_val){
					$category_services = !empty($service_val['category_services']) ? $service_val['category_services'] : array();
					if(!empty($category_services)){
						foreach($category_services as $ser_key => $ser_val){
							$service_id = intval($ser_val['service_id']);
							if(!empty($service_id)){
								
								$bookingpress_service_enabled            = $bookingpress_services->bookingpress_get_service_meta($service_id, 'show_service_on_site');
								$bookingpress_service_enabled            = ( empty($bookingpress_service_enabled) ) ? 'true' : $bookingpress_service_enabled;
								$bookingpress_loaded_services[ $service_key ]['category_services'][ $ser_key ]['service_enabled'] = $bookingpress_service_enabled;								

								/** service max capacity */
								$service_max_capacity = $bookingpress_pro_services->bookingpress_get_service_max_capacity($service_id);
								
								if( empty( $service_max_capacity ) ){
									$service_max_capacity = 1;
								}
								$bookingpress_loaded_services[ $service_key ]['category_services'][ $ser_key ]['service_max_capacity'] = $service_max_capacity;

								$bookingpress_extra_services_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_extra_services} WHERE bookingpress_service_id = %d", $service_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_extra_services is a table name. false alarm

								if(!empty($bookingpress_extra_services_data)){
									foreach($bookingpress_extra_services_data as $extra_key => $extra_val){
										$bookingpress_extra_service_price_with_currency = $BookingPress->bookingpress_price_formatter_with_currency_symbol($extra_val['bookingpress_extra_service_price']);

										$bookingpress_extra_services_data[$extra_key]['bookingpress_extra_service_price_with_currency'] = $bookingpress_extra_service_price_with_currency;
										$bookingpress_extra_services_data[$extra_key]['bookingpress_is_display_description'] = 0;

										$bookingpress_extra_services_data[$extra_key]['bookingpress_selected_qty'] = 1;
										$bookingpress_extra_services_data[$extra_key]['bookingpress_is_selected'] = false;

										$bookingpress_calendar_vue_data_fields['appointment_formdata']['selected_extra_services'][$extra_val['bookingpress_extra_services_id']] = $bookingpress_extra_services_data[$extra_key];
									}
								}

								$bookingpress_service_extras[$service_id] = $bookingpress_extra_services_data;


								//Get service staff members details
								$bookingpress_staffmembers_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_staffmembers_services} WHERE bookingpress_service_id = %d", $service_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers_services is table name.
								if(!empty($bookingpress_staffmembers_details)){
									foreach($bookingpress_staffmembers_details as $bookingpress_staff_key => $bookingpress_staff_val){
										$bookingpress_staffmember_id = intval($bookingpress_staff_val['bookingpress_staffmember_id']);

										$bookingpress_staff_price_with_currency = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_staff_val['bookingpress_service_price']);
										$bookingpress_staffmembers_details[$bookingpress_staff_key]['staff_price_with_currency'] = $bookingpress_staff_price_with_currency;

										//Get staff profile details
										$bookingpress_staff_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_staffmembers} WHERE bookingpress_staffmember_id = %d", $bookingpress_staffmember_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers is table name.

										$bookingpress_staffmembers_details[$bookingpress_staff_key]['profile_details'] = $bookingpress_staff_details;
									}
								}

								$bookingpress_service_staffmembers[$service_id] = $bookingpress_staffmembers_details;
							}
						}
					}
				}
			}

			$bookingpress_calendar_vue_data_fields['appointment_services_list'] = $bookingpress_loaded_services;
			$bookingpress_calendar_vue_data_fields['bookingpress_loaded_extras'] = $bookingpress_service_extras;
			$bookingpress_calendar_vue_data_fields['bookingpress_loaded_staff'] = $bookingpress_service_staffmembers;

			$bookingpress_currency_separator = $BookingPress->bookingpress_get_settings('price_separator', 'payment_setting');
			$bookingpress_calendar_vue_data_fields['bookingpress_currency_separator'] = $bookingpress_currency_separator;			
			$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
			$bookingpress_decimal_points = intval($bookingpress_decimal_points);
			$bookingpress_calendar_vue_data_fields['bookingpress_decimal_points'] = $bookingpress_decimal_points;

            $bookingpress_currency_name = $BookingPress->bookingpress_get_settings('payment_default_currency', 'payment_setting');
            $bookingpress_calendar_vue_data_fields['bookingpress_currency_name'] = $bookingpress_currency_name;
            $bookingpress_calendar_vue_data_fields['bookingpress_currency_symbol'] = $BookingPress->bookingpress_get_currency_symbol($bookingpress_currency_name);

            $bookingpress_price_symbol_position = $BookingPress->bookingpress_get_settings('price_symbol_position', 'payment_setting');
            $bookingpress_calendar_vue_data_fields['bookingpress_currency_symbol_position'] = $bookingpress_price_symbol_position;			

			$bookingpress_calendar_vue_data_fields['allow_multi_select_service'] = false;
			$bookingpress_calendar_vue_data_fields['bpa_allow_custom_duration'] = true;
			$bookingpress_calendar_vue_data_fields['bpa_allow_multiple_quantity'] = true;
			$bookingpress_calendar_vue_data_fields['allow_staff_member_selection'] = true;

			$bookingpress_calendar_vue_data_fields['bookingpress_is_extra_enable'] = $bookingpress_service_extra->bookingpress_check_service_extra_module_activation();
			
			if(($bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation()) && (! $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ))  ){
				$bookingpress_calendar_vue_data_fields['bookingpress_calenar_filter_no_staff_class'] = '';
				$bookingpress_calendar_vue_data_fields['bookingpress_calenar_filter_class'] = 'bpa-fsc__addon-filter-belt';
			}else {
				$bookingpress_calendar_vue_data_fields['bookingpress_calenar_filter_no_staff_class'] = '__bpa-fsc-no-staff';
				$bookingpress_calendar_vue_data_fields['bookingpress_calenar_filter_class'] = '';//__bpa-fsc-is-location
			}
			return $bookingpress_calendar_vue_data_fields;
		}
		
		/**
		 * Function for modify calendar view file path 
		 *
		 * @param  mixed $bookingpress_calendar_view_path
		 * @return void
		 */
		function bookingpress_modify_calendar_file_path_func( $bookingpress_calendar_view_path ) {

			$bookingpress_calendar_view_path = BOOKINGPRESS_PRO_VIEWS_DIR . '/calendar/manage_calendar.php';
			return $bookingpress_calendar_view_path;
		}
	}
}

global $bookingpress_pro_calendar;
$bookingpress_pro_calendar = new bookingpress_pro_calendar();
