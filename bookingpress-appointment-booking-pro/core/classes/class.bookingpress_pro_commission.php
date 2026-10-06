<?php
if ( ! class_exists( 'bookingpress_pro_commission' ) ) {
	class bookingpress_pro_commission Extends BookingPress_Core {
        function __construct() {
            if ( $this->bookingpress_check_staff_commission_module_activation() ) {
                add_action( 'bookingpress_commission_dynamic_view_load', array( $this, 'bookingpress_load_commission_view_func' ) );
                add_action( 'bookingpress_commission_dynamic_data_fields', array( $this, 'bookingpress_commission_dynamic_data_fields_func' ));
                add_action( 'bookingpress_commission_dynamic_on_load_methods', array( $this, 'bookingpress_commission_dynamic_on_load_methods_func' ));
                add_action( 'bookingpress_commission_dynamic_vue_methods', array( $this, 'bookingpress_commission_dynamic_vue_methods_func' )); 
                
                add_action( 'bookingpress_commission_dynamic_helper_vars', array( $this, 'bookingpress_commission_dynamic_helper_vars_func' ));

                add_action('bookingpress_after_change_appointment_status',array($this,'bookingpress_after_change_appointment_status_add_commission_func'),10,2);
                add_action('wp_ajax_bookingpress_get_commission', array( $this, 'bookingpress_get_commission' ));  

                add_filter( 'bookingpress_update_summary_data', array( $this, 'bookingpress_update_summary_commission_data_func' ), 10, 3 );  
                
                add_filter( 'bookingpress_modify_dashboard_data_fields', array( $this, 'bookingpress_modify_dashboard_data_fields_comm_func' ), 10 );
                
                add_action( 'bookingpress_appointment_add_post_data', array( $this, 'bookingpress_appointment_add_post_data_func' ), 10 );
                add_action('bookingpress_dashboard_redirect_filter',array($this,'bookingpress_dashboard_redirect_filter_func'));
                
                add_action('bookingpress_before_delete_appointment',array($this,'bookingpress_before_delete_appointment_func'),16);    

                /* Added for when appointment direclty added from the backend with the complete status */
                add_action( 'bookingpress_after_book_appointment', array( $this, 'bookingpress_after_book_appointment_assign_comm_fun' ), 30, 3 );
            }      
        }

        function bookingpress_after_book_appointment_assign_comm_fun($appointment_id, $entry_id = '', $payment_gateway_data = array()){
			global $BookingPress,$wpdb,$tbl_bookingpress_appointment_bookings;
			if($appointment_id){
                $appointment_data = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_appointment_status FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_id ), ARRAY_A );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings are table name.
                if(!empty($appointment_data)){
                    $bookingpress_appointment_status = (isset($appointment_data['bookingpress_appointment_status']))?$appointment_data['bookingpress_appointment_status']:0;
                    if($bookingpress_appointment_status == 6){
                        $this->bookingpress_after_change_appointment_status_add_commission_func($appointment_id, 6);
                    }
                }
			}
		}

        function bookingpress_before_delete_appointment_func($appointment_id){
            global $wpdb,$tbl_bookingpress_staffmembers_commission;
            $bookingperss_commission_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_staffmembers_commission}  WHERE bookingpress_commission_appointment_id = %d",$appointment_id),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_staffmembers_commission is a table name. false alarm
            if(!empty($bookingperss_commission_data) && is_array($bookingperss_commission_data)) {
                $wpdb->delete($tbl_bookingpress_staffmembers_commission, array( 'bookingpress_commission_appointment_id' => $appointment_id ), array( '%d' )); 
            }
        }

        function bookingpress_dashboard_redirect_filter_func(){
            global $bookingpress_slugs;
			?>
			 else if(module == 'commission') {
				bookingpress_redirect_url ="<?php echo  add_query_arg('page', esc_html($bookingpress_slugs->bookingpress_commission), esc_url(admin_url() . 'admin.php?page=bookingpress')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason: data has been escaped properly ?>"    
			}
			<?php
        }

        function bookingpress_appointment_add_post_data_func(){
            ?>
            if(bookingpress_dashboard_filter_appointment_status == '6') {
                this.search_appointment_status = '6';
                bookingpress_search_data.appointment_status = '6';
            } 
            <?php
        }

        function bookingpress_modify_appointment_comm_data_fields_func($appointments){

            $return_data['upcoming_appointments_total'] = count($appointments);

            return $appointments;
        }

        function bookingpress_modify_dashboard_data_fields_comm_func( $bookingpress_dashboard_vue_data_fields ) {
			global $BookingPressPro;

			if ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
				$bookingpress_dashboard_vue_data_fields['summary_data']['total_completed_appointment'] = 0;
			}
            if ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
				$bookingpress_dashboard_vue_data_fields['summary_data']['total_upcoming_appointment'] = 0;
			}
            if ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
				$bookingpress_dashboard_vue_data_fields['summary_data']['total_staff_commission'] = 0;
			}
			return $bookingpress_dashboard_vue_data_fields;
		}

        function bookingpress_update_summary_commission_data_func($return_data, $bookingpress_start_date,$bookingpress_end_date){

            global $tbl_bookingpress_staffmembers_commission, $bookingpress_pro_staff_members, $tbl_bookingpress_appointment_bookings, $wpdb, $BookingPress;
            
            $bookingpress_user_id        = get_current_user_id();
            $bookingpress_staffmember_id = $bookingpress_pro_staff_members->bookingpress_get_staffmember_id_using_wp_user_id( $bookingpress_user_id );

            /* Commission */
            $commission_search_query = " AND (bookingpress_commission_appointment_date BETWEEN '".$bookingpress_start_date."' AND '".$bookingpress_end_date."')";
            $total_commission = $wpdb->get_var($wpdb->prepare("SELECT SUM(bookingpress_commission_amount) FROM $tbl_bookingpress_staffmembers_commission 
            WHERE bookingpress_commission_staff_member_id = %d {$commission_search_query}", $bookingpress_staffmember_id));            
            $total_commission = !empty($total_commission) ? $total_commission : 0;  
            $total_commission = $BookingPress->bookingpress_price_formatter_with_currency_symbol($total_commission);  
            $return_data['total_staff_commission'] = $total_commission;
            /* Commission */            

            /* Upcoming */
            $search_where = 'WHERE 1=1';
            /* $search_where .= $wpdb->prepare(' AND bookingpress_staff_member_id = %d', $bookingpress_staffmember_id);            
            $search_where .= $wpdb->prepare(' AND CONCAT(bookingpress_appointment_date, " ", bookingpress_appointment_time) >= %s',date('Y-m-d H:i:s', current_time('timestamp')));        */           
            $search_where .= $wpdb->prepare(' AND bookingpress_staff_member_id = %d', $bookingpress_staffmember_id);
            $search_where .= $wpdb->prepare(' AND bookingpress_appointment_date BETWEEN %s AND %s',$bookingpress_start_date,$bookingpress_end_date);
            $search_where .= $wpdb->prepare(' AND CONCAT(bookingpress_appointment_date, " ", bookingpress_appointment_time) >= %s',current_time('mysql'));
            $query = "SELECT * FROM {$tbl_bookingpress_appointment_bookings} {$search_where} ORDER BY bookingpress_appointment_date ASC";
            $upcoming_appointments = $wpdb->get_results($query, ARRAY_A);
            $return_data['total_upcoming_appointment'] = count($upcoming_appointments);
            /* Upcoming */

            return $return_data;
        }

        function bookingpress_get_staff_commission_rate($staff_id){

            global $bookingpress_pro_staff_members, $BookingPress;

            $commission_rate = 0;

            $bookingpress_staffmember_enable_commission   = $BookingPress->bookingpress_get_settings('bookingpress_staffmember_enable_commission', 'staffmember_setting');
            $bookingpress_staffmember_commission_rate     = $BookingPress->bookingpress_get_settings('bookingpress_staffmember_commission_rate', 'staffmember_setting');        

            $bookingpress_enable_staff_wise_commission = $bookingpress_pro_staff_members->get_bookingpress_staffmembersmeta( $staff_id, 'bookingpress_enable_staff_wise_commission' );
            $bookingpress_staffmember_wise_commission_rate = $bookingpress_pro_staff_members->get_bookingpress_staffmembersmeta( $staff_id, 'bookingpress_staffmember_wise_commission_rate' );

            if ( ( $bookingpress_enable_staff_wise_commission === 'true' || $bookingpress_enable_staff_wise_commission === true ) && floatval( $bookingpress_staffmember_wise_commission_rate ) > 0 ) {
                $commission_rate = floatval( $bookingpress_staffmember_wise_commission_rate );
            } elseif ( $bookingpress_staffmember_enable_commission === 'true' || $bookingpress_staffmember_enable_commission === true ) {
                if ( floatval( $bookingpress_staffmember_commission_rate ) > 0 ) {
                    $commission_rate = floatval( $bookingpress_staffmember_commission_rate );
                }
            }  
            return $commission_rate;
        }

        function bookingpress_check_staff_commission_module_activation() {
			$is_staffmember_commission_module_activated = 0;
			$staffmember_commission_addon_option_val    = get_option( 'bookingpress_staffmember_commission_module' );
			if ( ! empty( $staffmember_commission_addon_option_val ) && ( $staffmember_commission_addon_option_val == 'true' ) ) {
				$is_staffmember_commission_module_activated = 1;
			}
			return $is_staffmember_commission_module_activated;
		}

        /**
         * Calculate commission base amount for an appointment.
         *
         * Base calculation matches booking flow:
         * (service price x persons unless flat-rate) + extras + tax - discounts (coupon/package/etc).
         * Additional discount/payment mechanisms (gift card, advance discounts, custom discounts) can
         * adjust via the provided filter without requiring core edits.
         *
         * @param int   $appointment_id
         * @param array $bookingpress_appointment_data Row from bookingpress_appointment_bookings
         * @return float
         */
        function bookingpress_get_commission_base_amount( $appointment_id, $bookingpress_appointment_data ) {

            if(empty($bookingpress_appointment_data) || empty($appointment_id)){
                return;
            }

            $base_amount = isset( $bookingpress_appointment_data['bookingpress_total_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_total_amount'] ) : 0;

            $service_price = isset( $bookingpress_appointment_data['bookingpress_service_price'] ) ? floatval( $bookingpress_appointment_data['bookingpress_service_price'] ) : 0;

            if(isset($bookingpress_appointment_data['bookingpress_enable_custom_duration'])  && $bookingpress_appointment_data['bookingpress_enable_custom_duration'] == 1) {
                if( !empty( $bookingpress_appointment_data['bookingpress_staff_member_details'] ) && !empty( $bookingpress_appointment_data['bookingpress_staff_member_price'] ) ){                 
                    $bookingpress_appointment_data['bookingpress_staff_member_price'] = $bookingpress_appointment_data['bookingpress_service_price'];
                }
            }

            if(isset($bookingpress_appointment_data['bookingpress_staff_member_id']) && $bookingpress_appointment_data['bookingpress_staff_member_id'] > 0){
                $service_price = isset( $bookingpress_appointment_data['bookingpress_staff_member_price'] ) ? floatval( $bookingpress_appointment_data['bookingpress_staff_member_price'] ) : 0;
            }

            // Persons/bring-members. Stored as selected extra members in booking table.
            $persons = isset( $bookingpress_appointment_data['bookingpress_selected_extra_members'] ) ? intval( $bookingpress_appointment_data['bookingpress_selected_extra_members'] ) : 1;
            if ( $persons <= 0 ) {
                $persons = 1;
            }

            $is_multi_service_booking = isset( $bookingpress_appointment_data['is_multi_service_booking'] ) ? intval( $bookingpress_appointment_data['is_multi_service_booking'] ) : 0;

            $bookingpress_is_multistaff = isset( $bookingpress_appointment_data['bookingpress_is_multistaff'] ) ? intval( $bookingpress_appointment_data['bookingpress_is_multistaff'] ) : 0;         
            
            $bookingpress_is_cart = isset( $bookingpress_appointment_data['bookingpress_is_cart'] ) ? intval( $bookingpress_appointment_data['bookingpress_is_cart'] ) : 0;   

            if($is_multi_service_booking == 0){
                // Flat rate means do not multiply by persons.
                $bookingpress_enable_slot_capacity = isset( $bookingpress_appointment_data['bookingpress_enable_service_slot_capacity'] ) ? intval( $bookingpress_appointment_data['bookingpress_enable_service_slot_capacity'] ) : 0; 
                
                if($bookingpress_enable_slot_capacity == 1){
                    $service_total = $service_price;
                } else {
                    $service_total = $service_price * $persons;
                }

                // Extras total from stored details (json). Fallback to 0 if not parsable.
                $extras_total = 0;
                if ( ! empty( $bookingpress_appointment_data['bookingpress_extra_service_details'] ) ) {
                    $extras_details = $bookingpress_appointment_data['bookingpress_extra_service_details'];
                    if ( is_string( $extras_details ) ) {
                        $extras_details = json_decode( $extras_details, true );
                    }
                    if ( is_array( $extras_details ) ) {
                        foreach ( $extras_details as $extra ) {
                            if ( ! is_array( $extra ) ) {
                                continue;
                            }
                            $price = 0;
                            if ( isset( $extra['bookingpress_extra_service_details']['bookingpress_extra_service_price'] ) ) {
                                $price = floatval( $extra['bookingpress_extra_service_details']['bookingpress_extra_service_price'] );
                            } elseif ( isset( $extra['bookingpress_extra_price_org'] ) ) {
                                $price = floatval( $extra['bookingpress_extra_price_org'] );
                            }

                            $qty = 1;
                            if ( isset( $extra['bookingpress_selected_qty'] ) ) {
                                $qty = intval( $extra['bookingpress_selected_qty'] );
                            } elseif ( isset( $extra['qty'] ) ) {
                                $qty = intval( $extra['qty'] );
                            } elseif ( isset( $extra['quantity'] ) ) {
                                $qty = intval( $extra['quantity'] );
                            }
                            if ( $qty < 0 ) {
                                $qty = 0;
                            }

                            // Some structures store selection flag; if present and false, skip.
                            if ( isset( $extra['bookingpress_is_selected'] ) && ( 'false' === $extra['bookingpress_is_selected'] || false === $extra['bookingpress_is_selected'] ) ) {
                                continue;
                            }

                            $extras_total += ( $price * $qty );
                        }
                    }
                }

                $tax_amount = isset( $bookingpress_appointment_data['bookingpress_tax_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_tax_amount'] ) : 0;

                $price_display_setting = isset( $bookingpress_appointment_data['bookingpress_price_display_setting'] ) ? sanitize_text_field( $bookingpress_appointment_data['bookingpress_price_display_setting'] ) : 'exclude_taxes';

                if ( 'include_taxes' === $price_display_setting ) {
                    $tax_amount = 0;
                }

                $offer_discount = $online_discount = $mycred_discount = $arm_plan_discount = 0;
                $coupon_discount = isset( $bookingpress_appointment_data['bookingpress_coupon_discount_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_coupon_discount_amount'] ) : 0;
                $package_discount = isset( $bookingpress_appointment_data['bookingpress_package_discount_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_package_discount_amount'] ) : 0;
                $offer_discount = isset( $bookingpress_appointment_data['bookingpress_offer_payment_discount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_offer_payment_discount'] ) : 0;
                if($offer_discount > 0){
                    $bookingpress_offer_payment_discount_detail = isset( $bookingpress_appointment_data['bookingpress_offer_payment_discount_detail'] ) ? $bookingpress_appointment_data['bookingpress_offer_payment_discount_detail'] : "";
                    if ( is_string( $bookingpress_offer_payment_discount_detail ) ) {
                        $bookingpress_offer_payment_discount_detail = json_decode( $bookingpress_offer_payment_discount_detail, true );
                    }
                    if ( is_array( $bookingpress_offer_payment_discount_detail ) && isset( $bookingpress_offer_payment_discount_detail['discount_price'] ) ) {
                        $offer_discount = floatval( $bookingpress_offer_payment_discount_detail['discount_price'] );
                    }                         
                }
                $online_discount = isset( $bookingpress_appointment_data['bookingpress_online_payment_discount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_online_payment_discount'] ) : 0;
                $mycred_discount = isset( $bookingpress_appointment_data['bookingpress_mycred_discount_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_mycred_discount_amount'] ) : 0;
                $arm_plan_discount = isset( $bookingpress_appointment_data['bookingpress_arm_plan_payment_discount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_arm_plan_payment_discount'] ) : 0;

                $gift_card_discount = 0;
                if ( isset($bookingpress_appointment_data['bookingpress_gift_card_details']) && ! empty( $bookingpress_appointment_data['bookingpress_gift_card_details'] ) ) {
                    $gift_card_details = $bookingpress_appointment_data['bookingpress_gift_card_details'];
                    if ( is_string( $gift_card_details ) ) {
                        $gift_card_details = json_decode( $gift_card_details, true );
                    }
                    if ( is_array( $gift_card_details ) && isset( $gift_card_details['bookingpress_gift_card_discounted_value'] ) ) {
                        $gift_card_discount = floatval( $gift_card_details['bookingpress_gift_card_discounted_value'] );
                    }
                }

                $service_total = apply_filters( 'bookingpress_commission_base_amount_before_calcualte', $service_total, $appointment_id, $bookingpress_appointment_data );

                $base_amount = ( $service_total + $extras_total + $tax_amount ) - ( $coupon_discount + $package_discount + $gift_card_discount + $offer_discount + $online_discount + $mycred_discount + $arm_plan_discount);

                $base_amount = apply_filters( 'bookingpress_commission_base_amount', $base_amount, $appointment_id, $bookingpress_appointment_data );
            }
            else {
                $base_amount = isset( $bookingpress_appointment_data['bookingpress_total_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_total_amount'] ) : 0;

                $bookingpress_tip_amount = isset( $bookingpress_appointment_data['bookingpress_tip_amount'] ) ? floatval( $bookingpress_appointment_data['bookingpress_tip_amount'] ) : 0;

                if ($bookingpress_tip_amount > 0) {
                    $base_amount = $base_amount - $bookingpress_tip_amount;
                }

                $base_amount = apply_filters( 'bookingpress_commission_base_amount_outside_multi_booking', $base_amount, $appointment_id, $bookingpress_appointment_data );
            }

            if ( $base_amount < 0 ) {
                $base_amount = 0;
            }
            return floatval( $base_amount );
        }

        function bookingpress_commission_dynamic_helper_vars_func(){
            global $bookingpress_global_options;
            $bookingpress_options     = $bookingpress_global_options->bookingpress_global_options();
            $bookingpress_locale_lang = $bookingpress_options['locale'];
            ?>
            var lang = ELEMENT.lang.<?php echo esc_html($bookingpress_locale_lang); ?>;
            ELEMENT.locale(lang)
            const createSortable = (el, options, vnode) => {
                return Sortable.create(el, {
                    ...options
                });
            };
            const sortable = {
                name: 'sortable',
                bind(el, binding, vnode) {
                    const table = el;
                    table._sortable = createSortable(table.querySelector("tbody"), binding.value, vnode);
                }
            };
            <?php
        }

        function bookingpress_get_commission(){
            global $wpdb, $tbl_bookingpress_staffmembers_commission, $bookingpress_global_options, $bookingpress_appointment, $bookingpress_pro_staff_members, $BookingPress, $BookingPressPro;

            $response = array();
        
            $bpa_check_authorization = $this->bpa_check_authentication('retrieve_commission', true, 'bpa_wp_nonce');
        
            if (preg_match('/error/', $bpa_check_authorization)) {
        
                $response['variant'] = 'error';
                $response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
                $response['msg'] = esc_html__('Authorization failed', 'bookingpress-appointment-booking');
        
                wp_send_json($response);
                die;
            }
        
            $perpage     = isset($_POST['perpage']) ? intval($_POST['perpage']) : 10;
            $currentpage = isset($_POST['currentpage']) ? intval($_POST['currentpage']) : 1;
            $sort_by     = isset($_POST['sort_by']) ? esc_html($_POST['sort_by']) : '';
            $sort_order  = isset($_POST['sort_order'])? esc_html($_POST['sort_order']) : 'DESC';
            $offset      = ($currentpage > 1) ? (($currentpage - 1) * $perpage) : 0;

            if ( ! in_array( $sort_order, array( 'ASC', 'DESC' ), true ) ) {
                $sort_order = 'DESC';
            }

            $bookingpress_search_data  = ! empty($_REQUEST['search_data']) ? array_map(array( $BookingPress, 'appointment_sanatize_field' ), $_REQUEST['search_data']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason $_REQUEST['search_data'] contains array and sanitized properly using appointment_sanatize_field function
            
            $bookingpress_search_query       = '';

            $bookingpress_search_query_where = 'WHERE 1=1 ';

            if (! empty($bookingpress_search_data) ) {
                if (! empty($bookingpress_search_data['selected_date_range']) ) {
                    $bookingpress_search_date         = $bookingpress_search_data['selected_date_range'];
                    $start_date                       = date('Y-m-d', strtotime($bookingpress_search_date[0]));
                    $end_date                         = date('Y-m-d', strtotime($bookingpress_search_date[1]));
                    $bookingpress_search_query_where .= $wpdb->prepare( " AND (bpa.bookingpress_commission_appointment_date BETWEEN %s AND %s)", $start_date, $end_date );
                }                
                if (! empty($bookingpress_search_data['service_name']) ) {
                    $bookingpress_search_name = $bookingpress_search_data['service_name'];
                    $search_name_query = ' AND ( bpa.bookingpress_commission_service_id IN(';
                    $search_name_query .= rtrim( str_repeat( '%d,', count( $bookingpress_search_name) ), ',' ).' ) )';
                    array_unshift( $bookingpress_search_name, $search_name_query );
                    $search_name_query_str = call_user_func_array( array( $wpdb, 'prepare' ), $bookingpress_search_name );                    
                    //$search_name_query_str = apply_filters( 'bookingpress_modify_search_query_where_after_service_id', $search_name_query_str, $bookingpress_search_data['service_name'] );
                    $bookingpress_search_query_where .= $search_name_query_str;
                }
                
                if ( ! empty( $bookingpress_search_data['staff_member_name'] ) ) {

                    if(isset($bookingpress_search_data["is_api"]) && $bookingpress_search_data["is_api"] == "1"){
                        $bookingpress_search_name            = $bookingpress_search_data['staff_member_name'];                       
                        $search_name_query 					  =$wpdb->prepare(' AND (bpa.bookingpress_staff_first_name like %s OR bpa.bookingpress_staff_last_name like %s )', '%'.$bookingpress_search_name.'%', '%'.$bookingpress_search_name.'%'); ;                        
                        $bookingpress_search_query_where    .= $search_name_query;
                    }else{
                        $bookingpress_search_name            = $bookingpress_search_data['staff_member_name'];
                        $bookingpress_search_staff_member_id = implode( ',', $bookingpress_search_name );                        
                        $multi_staff_filter = "";
                        //$multi_staff_filter = apply_filters( 'bookingpress_appointment_report_view_multi_staff_add_filter', $multi_staff_filter, $bookingpress_search_staff_member_id );
        
                        if(empty($multi_staff_filter)){        
                            $search_name_query 					 = ' AND (bpa.bookingpress_commission_staff_member_id IN(';
                            $search_name_query 					.= rtrim( str_repeat( '%d,', count( $bookingpress_search_name) ), ',' ).' ) )';
                            array_unshift( $bookingpress_search_name, $search_name_query );
                            $search_name_query_str 				 = call_user_func_array( array( $wpdb, 'prepare' ), $bookingpress_search_name );
                            $bookingpress_search_query_where    .= $search_name_query_str;        
                        }else{
                            $bookingpress_search_query_where          .= $multi_staff_filter;
                        }
                    }                  
                };
                if ( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ) {
                    $bookingpress_user_id             = get_current_user_id();
                    $bookingpress_staffmember_id      = $bookingpress_pro_staff_members->bookingpress_get_staffmember_id_using_wp_user_id( $bookingpress_user_id );    
                    $multi_staff_filter = "";
                    //$multi_staff_filter = apply_filters( 'bookingpress_appointment_report_view_multi_staff_add_filter', $multi_staff_filter, $bookingpress_staffmember_id );
                    if(empty($multi_staff_filter)){
                        $bookingpress_search_query_where .= $wpdb->prepare( " AND (bpa.bookingpress_commission_staff_member_id = %d)", $bookingpress_staffmember_id );
                    }else{
                        $bookingpress_search_query_where .= $multi_staff_filter;
                    }  
                }
            }
            
            
            $order_by_column = 'bookingpress_commission_id';            
            $bookingpress_commission_sortable_columns = array(
                'commission_amount' => 'bookingpress_commission_amount',
                'appointment_date' => 'bookingpress_commission_appointment_date',
                'staffmember_name' => 'bookingpress_staff_first_name',
                'service_name' => 'bookingpress_service_name',
            );            
            if ( isset( $bookingpress_commission_sortable_columns[ $sort_by ] ) ) {
                $order_by_column = $bookingpress_commission_sortable_columns[ $sort_by ];
            }            
            
            $total_commission = $wpdb->get_var("SELECT COUNT(*) FROM $tbl_bookingpress_staffmembers_commission bpa {$bookingpress_search_query}{$bookingpress_search_query_where} ORDER BY {$order_by_column} {$sort_order}");  

            $bpa_commission_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl_bookingpress_staffmembers_commission bpa {$bookingpress_search_query}{$bookingpress_search_query_where} ORDER BY {$order_by_column} {$sort_order} LIMIT %d OFFSET %d", $perpage,$offset), ARRAY_A);            
            $items = array();
            $counter = 1;     
            
            $bookingpress_global_options_arr  = $bookingpress_global_options->bookingpress_global_options();
            $bookingpress_default_date_format = $bookingpress_global_options_arr['wp_default_date_format'];
            $bookingpress_default_time_format = $bookingpress_global_options_arr['wp_default_time_format'];
            $bookingpress_default_date_time_format = $bookingpress_default_date_format . ' ' . $bookingpress_default_time_format;

            if (!empty($bpa_commission_results)) {

                foreach ($bpa_commission_results as $get_commission) {  
            
                    $appointment_date  = isset($get_commission['bookingpress_commission_appointment_date']) ? $get_commission['bookingpress_commission_appointment_date'] : '';
                    $appointment_time  = isset($get_commission['bookingpress_commission_appointment_time']) ? $get_commission['bookingpress_commission_appointment_time'] : '';
                    $appointment_date_time = $appointment_date . ' ' . $appointment_time;
            
                    $item = array();        
                    $item['id'] = $counter;
                    $commission_id  = intval($get_commission['bookingpress_commission_id']);
                    $item['commission_id'] = $commission_id;

                    $staff_member_firstname = (isset($get_commission['bookingpress_staff_first_name']) && !empty($get_commission['bookingpress_staff_first_name'])) ? $get_commission['bookingpress_staff_first_name'] : '';
                    $staff_member_lastname = (isset($get_commission['bookingpress_staff_last_name']) && !empty($get_commission['bookingpress_staff_last_name'])) ? $get_commission['bookingpress_staff_last_name'] : '';
                    $staff_member_email = (isset($get_commission['bookingpress_staff_email_address']) && !empty($get_commission['bookingpress_staff_email_address'])) ? $get_commission['bookingpress_staff_email_address'] : '';
                    
                    $item['staff_id'] = isset($get_commission['bookingpress_commission_staff_member_id']) ? $get_commission['bookingpress_commission_staff_member_id'] : '';

                    $staffmember_name = ! empty( $staff_member_firstname ) ? sanitize_text_field( $staff_member_firstname . ' ' . $staff_member_lastname ) : sanitize_email( $staff_member_email );
                    if(empty(trim($staffmember_name))) {
                        $staffmember_name = $bookingpress_pro_staff_members->bookingpress_get_staffmembername_using_id($item['staff_id'] );
                    }

                    $item['appointment_id'] = isset($get_commission['bookingpress_commission_booking_id']) ? $get_commission['bookingpress_commission_booking_id'] : '';
            
                    $item['service_id'] = isset($get_commission['bookingpress_commission_service_id']) ? $get_commission['bookingpress_commission_service_id'] : '';

                    $service_names  = isset($get_commission['bookingpress_service_name']) ? $get_commission['bookingpress_service_name'] : '';
                    $service_array = !empty($service_names) ? array_map('trim', explode(',', $service_names)) : array();

                    $item['service_name'] = '';
                    $item['bookingpress_multiple_service_total'] = 0;
                    $item['bookingpress_multiple_service_extra_name'] = '';
                    if(count($service_array) > 2){
                        $first_two = array_slice($service_array,0,2);
                        $extra_services = array_slice($service_array,2);
                        $item['service_name'] = implode(', ', $first_two);
                        $item['bookingpress_multiple_service_total'] = count($extra_services);
                        $item['bookingpress_multiple_service_extra_name'] = implode(', ', $extra_services);
                    }else{
                        $item['service_name'] = implode(', ', $service_array);
                    }
            
                    $item['staffmember_name'] = $staffmember_name;

                    $bookingpress_currency_name = $BookingPress->bookingpress_get_settings('payment_default_currency', 'payment_setting');

                    $bpa_currency_symbol = $BookingPress->bookingpress_get_currency_symbol($bookingpress_currency_name);

                    $commission_raw = isset($get_commission['bookingpress_commission_amount']) ? floatval($get_commission['bookingpress_commission_amount']) : 0;

                    $commission_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol($commission_raw, $bpa_currency_symbol);
            
                    $item['commission_amount_with_currency'] = $commission_amount;
                    $item['commission_amount'] = $commission_raw;                    
                    $item['appointment_date'] = !empty($appointment_date_time) ? date_i18n($bookingpress_default_date_time_format, strtotime($appointment_date_time)) : '';
                    $item['created_date'] = isset($get_commission['bookingpress_commission_created_date']) ? date_i18n($bookingpress_default_date_time_format, strtotime($get_commission['bookingpress_commission_created_date'])) : '';            
                    $items[] = $item;            
                    $counter++;
                }
            }
            $data['variant'] = "success";
            $data['items'] = $items;
            $data['totalItems'] = intval($total_commission);
        
            wp_send_json($data);
        }

        function bookingpress_commission_dynamic_on_load_methods_func(){
            ?>
            this.loadCommissions().catch(error => {
                console.error(error)
            })
            <?php
            do_action('bookingpress_add_commission_dynamic_on_load_methods');
        }

        function bookingpress_commission_dynamic_vue_methods_func(){
            global $bookingpress_notification_duration;
            ?>
            async loadCommissions( resetPagination = false ) {
                this.toggleBusy();
                const vm = this;
                if (true == resetPagination) {
                    vm.currentPage = 1;
                }
                var bookingpress_module_type = bookingpress_dashboard_filter_start_date = bookingpress_dashboard_filter_end_date = 
                bookingpress_module_type = sessionStorage.getItem("bookingpress_module_type");                
                bookingpress_dashboard_filter_start_date = sessionStorage.getItem("bookingpress_dashboard_filter_start_date");
                bookingpress_dashboard_filter_end_date = sessionStorage.getItem("bookingpress_dashboard_filter_end_date");
                sessionStorage.removeItem("bookingpress_module_type");
                sessionStorage.removeItem("bookingpress_dashboard_filter_start_date");
                sessionStorage.removeItem("bookingpress_dashboard_filter_end_date");
                if(bookingpress_module_type != '' && bookingpress_module_type == 'commission' && bookingpress_dashboard_filter_start_date != '' && bookingpress_dashboard_filter_end_date != '' ) {                   
                    var appointment_date_range = [bookingpress_dashboard_filter_start_date,bookingpress_dashboard_filter_end_date];
                    this.appointment_date_range = appointment_date_range;
                }   

                var bookingpress_search_data = { 'selected_date_range': this.appointment_date_range,'service_name': this.search_service_name,'staff_member_name' : vm.search_staff_member_name}  

                var postData = {
                    action: 'bookingpress_get_commission',
                    perpage: this.perPage,
                    currentpage: this.currentPage,
                    search_data: bookingpress_search_data,
                    _wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>'
                };

                axios.post(appoint_ajax_obj.ajax_url, Qs.stringify(postData)).then(function (response) {
                    vm.toggleBusy();
                    if ("error" == response.data.variant) {
                        vm.$notify({
                            title: response.data.title,
                            message: response.data.msg,
                            type: 'error'
                        });
                    } else {
                        vm.commission_items = response.data.items;
                        vm.totalItems = response.data.totalItems;
                    }
                }).catch(function () {
                    vm.toggleBusy();
                    vm.$notify({title: 'Error', message: 'Something went wrong..', type: 'error'});
                });

            },
            toggleBusy() {
                if(this.is_display_loader == '1'){
                    this.is_display_loader = '0'
                }else{
                    this.is_display_loader = '1'
                }
            },
            resetFilter(){
                const vm = this
                vm.appointment_date_range = ''
                vm.search_service_name = ''
                vm.search_staff_member_name = ''
                <?php 
                do_action('bookingpress_commission_reset_filter');
                ?>
                vm.loadCommissions()
            },
            handleSizeChange(val) {
                this.perPage = val
                this.loadCommissions()
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.loadCommissions()
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
            <?php
        }

        function bookingpress_after_change_appointment_status_add_commission_func($appointment_id, $appointment_new_status){
            global $wpdb, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_staffmembers_commission, $BookingPress, $bookingpress_pro_staff_members;
            
            if($bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation()){

                if($appointment_new_status != 6){
                    return;
                }

                $bookingpress_appointment_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_id),ARRAY_A);

                if(empty($bookingpress_appointment_data)){ return; }

                $multi_staff_members = array();

                $bookingpress_booking_id    = isset($bookingpress_appointment_data['bookingpress_booking_id']) ? $bookingpress_appointment_data['bookingpress_booking_id'] : 0;

                if ( is_plugin_active('bookingpress-multi-staffmembers/bookingpress-multi-staffmembers.php') ) {                    
                    $multi_staff_table = $wpdb->prefix . 'bookingpress_multi_staff_bookings'; 
                    $multi_staff_members = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_staff_member_id,bookingpress_service_name,bookingpress_staff_first_name,bookingpress_staff_last_name,bookingpress_staff_email_address FROM {$multi_staff_table} WHERE bookingpress_appointment_booking_id = %d",$appointment_id),ARRAY_A);
                }

                $bookingpress_service_name = isset($bookingpress_appointment_data['bookingpress_service_name']) ? $bookingpress_appointment_data['bookingpress_service_name'] : '';

                if ( is_plugin_active('bookingpress-multiservice-booking/bookingpress-multiservice-booking.php') ) {    
                    global $tbl_bookingpress_multi_service_bookings;
                    $bpa_multiservices = $wpdb->get_col($wpdb->prepare("SELECT bookingpress_service_name FROM {$tbl_bookingpress_multi_service_bookings} WHERE bookingpress_booking_id = %d",$bookingpress_booking_id));                    
                    $bookingpress_service_name = !empty($bpa_multiservices) ? implode(', ', $bpa_multiservices) : '';
                }   

                $staff_id = isset($bookingpress_appointment_data['bookingpress_staff_member_id']) ? intval($bookingpress_appointment_data['bookingpress_staff_member_id']) : 0;
                $service_id       = isset($bookingpress_appointment_data['bookingpress_service_id']) ? intval($bookingpress_appointment_data['bookingpress_service_id']) : 0;
                $appointment_date = isset($bookingpress_appointment_data['bookingpress_appointment_date']) ? $bookingpress_appointment_data['bookingpress_appointment_date'] : '';
                $bookingpress_appointment_time  = isset($bookingpress_appointment_data['bookingpress_appointment_time']) ? $bookingpress_appointment_data['bookingpress_appointment_time'] : '';
                $appointment_end_date = isset($bookingpress_appointment_data['bookingpress_appointment_end_date']) ? $bookingpress_appointment_data['bookingpress_appointment_end_date'] : '';
                $bookingpress_appointment_end_time  = isset($bookingpress_appointment_data['bookingpress_appointment_end_time']) ? $bookingpress_appointment_data['bookingpress_appointment_end_time'] : '';
                $duration_val     = isset($bookingpress_appointment_data['bookingpress_service_duration_val']) ? $bookingpress_appointment_data['bookingpress_service_duration_val'] : 0;
                $duration_unit    = isset($bookingpress_appointment_data['bookingpress_service_duration_unit']) ? $bookingpress_appointment_data['bookingpress_service_duration_unit'] : '';
                $service_price    = isset($bookingpress_appointment_data['bookingpress_service_price']) ? $bookingpress_appointment_data['bookingpress_service_price'] : 0;
                $bookingpress_total_amount    = isset($bookingpress_appointment_data['bookingpress_total_amount']) ? $bookingpress_appointment_data['bookingpress_total_amount'] : 0;

                $commission_base_amount = $this->bookingpress_get_commission_base_amount( $appointment_id, $bookingpress_appointment_data );  

                $bookingpress_staff_first_name = isset($bookingpress_appointment_data['bookingpress_staff_first_name']) ? $bookingpress_appointment_data['bookingpress_staff_first_name'] : '';
                $bookingpress_staff_last_name = isset($bookingpress_appointment_data['bookingpress_staff_last_name']) ? $bookingpress_appointment_data['bookingpress_staff_last_name'] : '';
                $bookingpress_staff_email_address = isset($bookingpress_appointment_data['bookingpress_staff_email_address']) ? $bookingpress_appointment_data['bookingpress_staff_email_address'] : '';

                $staff_list = array();

                if(!empty($multi_staff_members)){
                    foreach($multi_staff_members as $staff){
                        $staff_list[] = array(
                            'staff_id'   => isset($staff['bookingpress_staff_member_id']) ? intval($staff['bookingpress_staff_member_id']) : 0,
                            'first_name' => isset($staff['bookingpress_staff_first_name']) ? $staff['bookingpress_staff_first_name'] : '',
                            'last_name'  => isset($staff['bookingpress_staff_last_name']) ? $staff['bookingpress_staff_last_name'] : '',
                            'email'      => isset($staff['bookingpress_staff_email_address']) ? $staff['bookingpress_staff_email_address'] : '',
                        );
                    }
                }else{
                    if($staff_id > 0){
                        $staff_list[] = array(
                            'staff_id'   => $staff_id,
                            'first_name' => isset($bookingpress_staff_first_name) ? $bookingpress_staff_first_name : '',
                            'last_name'  => isset($bookingpress_staff_last_name) ? $bookingpress_staff_last_name : '',
                            'email'      => isset($bookingpress_staff_email_address) ? $bookingpress_staff_email_address : '',
                        );
                    }
                }

                $bookingpress_staffmember_enable_commission   = $BookingPress->bookingpress_get_settings('bookingpress_staffmember_enable_commission', 'staffmember_setting');
                $bookingpress_staffmember_commission_rate     = $BookingPress->bookingpress_get_settings('bookingpress_staffmember_commission_rate', 'staffmember_setting');   
                
                foreach($staff_list as $staff){

                    $staff_id = isset($staff['staff_id']) ? $staff['staff_id'] : 0;

                    $bookingpress_enable_staff_wise_commission = $bookingpress_pro_staff_members->get_bookingpress_staffmembersmeta( $staff_id, 'bookingpress_enable_staff_wise_commission' );
                    $bookingpress_staffmember_wise_commission_rate = $bookingpress_pro_staff_members->get_bookingpress_staffmembersmeta( $staff_id, 'bookingpress_staffmember_wise_commission_rate' );

                    $commission_rate = 0;

                    // Prefer staff-wise commission rate when enabled and valid, otherwise fall back to general rate
                    if ( ( $bookingpress_enable_staff_wise_commission === 'true' || $bookingpress_enable_staff_wise_commission === true ) && floatval( $bookingpress_staffmember_wise_commission_rate ) > 0 ) {
                        $commission_rate = floatval( $bookingpress_staffmember_wise_commission_rate );
                    } elseif ( $bookingpress_staffmember_enable_commission === 'true' || $bookingpress_staffmember_enable_commission === true ) {
                        if ( floatval( $bookingpress_staffmember_commission_rate ) > 0 ) {
                            $commission_rate = floatval( $bookingpress_staffmember_commission_rate );
                        }
                    }            

                    $bookingpress_staff_first_name = isset($staff['first_name']) ? $staff['first_name'] : '';
                    $bookingpress_staff_last_name = isset($staff['last_name']) ? $staff['last_name'] : '';
                    $bookingpress_staff_email_address = isset($staff['email']) ? $staff['email'] : '';

                    $existing_commission = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl_bookingpress_staffmembers_commission} WHERE bookingpress_commission_appointment_id = %d AND bookingpress_commission_staff_member_id = %d", $appointment_id, $staff_id));

                    if ( $commission_rate > 0 && $existing_commission == 0 ) {
                        $commission_amount = ( $commission_base_amount * $commission_rate ) / 100;
                        $wpdb->insert(
                            $tbl_bookingpress_staffmembers_commission,
                            array(
                                'bookingpress_commission_appointment_id'   => $appointment_id,
                                'bookingpress_commission_booking_id'       => $bookingpress_booking_id,
                                'bookingpress_commission_staff_member_id'  => $staff_id,
                                'bookingpress_commission_service_id'       => $service_id,
                                'bookingpress_commission_rate'             => $commission_rate,
                                'bookingpress_service_name'                => $bookingpress_service_name,
                                'bookingpress_staff_first_name'            => $bookingpress_staff_first_name,
                                'bookingpress_staff_last_name'             => $bookingpress_staff_last_name,
                                'bookingpress_staff_email_address'         => $bookingpress_staff_email_address,
                                'bookingpress_commission_amount'           => $commission_amount,
                                'bookingpress_commission_appointment_date' => $appointment_date,
                                'bookingpress_commission_appointment_time' => $bookingpress_appointment_time,
                                'bookingpress_commission_appointment_end_date' => $appointment_end_date,
                                'bookingpress_commission_appointment_end_time' => $bookingpress_appointment_end_time,
                                'bookingpress_service_duration_val'        => $duration_val,
                                'bookingpress_service_duration_unit'       => $duration_unit,
                                'bookingpress_commission_created_date'     => current_time('mysql'),
                            ),
                            array('%d','%d','%d','%d','%f','%s','%s','%s','%s','%f','%s','%s','%s','%s','%d','%s','%s')
                        );
                    }   
                }         
            }
        }


        function bookingpress_load_commission_view_func() {
            $bookingpress_load_file_name = BOOKINGPRESS_PRO_VIEWS_DIR . '/commission/manage_commission.php';
			require $bookingpress_load_file_name;
		}

        /**
         * Add more data variables for commission module
         *
         * @return void
         */
        function bookingpress_commission_dynamic_data_fields_func(){

            global $bookingpress_global_options, $BookingPress, $bookingpress_pro_staff_members, $BookingPressPro;

            $bookingpress_global_options_arr       = $bookingpress_global_options->bookingpress_global_options();
            $bookingpress_default_date_time_format = $bookingpress_global_options_arr['wp_default_date_format'];

            $first_day_of_week = (int)  $bookingpress_global_options_arr['start_of_week'];
            $first_day_of_week_inc = $first_day_of_week + 1;
            $bookingpress_site_current_language = $bookingpress_global_options->bookingpress_get_site_current_language();

            $bookingpress_pagination  = $bookingpress_global_options_arr['pagination'];
            $bookingpress_pagination_arr      = json_decode($bookingpress_pagination, true);
            $bookingpress_pagination_selected = $bookingpress_pagination_arr[0];

            $bookingpress_default_perpage_option  = $BookingPress->bookingpress_get_settings('per_page_item', 'general_setting');

            $perPage = ! empty($bookingpress_default_perpage_option) ? $bookingpress_default_perpage_option : '20';
            $pagination_length_val = ! empty($bookingpress_default_perpage_option) ? $bookingpress_default_perpage_option : '20';

            $bookingpress_services_details2   = array();
            $bookingpress_services_details2[] = array(
            'category_name'     => '',
            'category_services' => array(
            '0' => array(
                        'service_id'    => 0,
                        'service_name'  => esc_html__('Select service', 'bookingpress-appointment-booking'),
                        'service_price' => '',
            ),
            ),
            );
            $bookingpress_services_details    = $BookingPress->get_bookingpress_service_data_group_with_category();
            $bookingpress_services_details2   = array_merge($bookingpress_services_details2, $bookingpress_services_details);  

            $is_staffmember_activated = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();   
            $bookingpress_user_id            = get_current_user_id();
			$staffmember_id                  = $bookingpress_pro_staff_members->bookingpress_get_staffmember_id_using_wp_user_id( $bookingpress_user_id );

            $search_staff_member_list = $bookingpress_pro_staff_members->bookingpress_staffmember_search_list();

            $current_logged_in_staff_commission_rate = $this->bookingpress_get_staff_commission_rate($staffmember_id);

            $bookingpress_commission_data_fields_arr = array(
                'is_mask_display'   => false,
                'is_staffmember_activated' => $is_staffmember_activated,
                'commission_items'             => array(),
                'totalItems'        => 0,
                'currentPage'       => 1,
                'filter_pickerOptions' => array(
                    'firstDayOfWeek' => $first_day_of_week
                ),
                'pagination_length_val'  => $pagination_length_val,
                'perPage'                    => $perPage,               
                'pagination_selected_length' => $bookingpress_pagination_selected,
                'pagination_length'          => $bookingpress_pagination,
                'currentPage'                => 1,
                'search_staff_member_list' => $search_staff_member_list,
                'site_locale'  => $bookingpress_site_current_language,
                'appointment_date_range' => array( date('Y-m-d', strtotime('-3 Day')), date('Y-m-d', strtotime('+3 Day')) ),
                'is_display_loader' => '0',
                'appointment_services_data'  => array(),
                'search_service_name'        => '',
                'search_service_employee'    => '',
                'search_staff_member_name' => '',
                'multipleSelection'          => array(),
                'pagination_val'    => array(
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
            );

            $bookingpress_commission_data_fields_arr['appointment_services_list'] = $bookingpress_services_details2;
            $bookingpress_commission_data_fields_arr['appointment_services_data'] = $bookingpress_services_details;
            $bookingpress_commission_data_fields_arr['staffmember_commission_rate'] = $current_logged_in_staff_commission_rate;

            echo wp_json_encode( $bookingpress_commission_data_fields_arr );
        }
    }   

}
global $bookingpress_pro_commission;
$bookingpress_pro_commission = new bookingpress_pro_commission();