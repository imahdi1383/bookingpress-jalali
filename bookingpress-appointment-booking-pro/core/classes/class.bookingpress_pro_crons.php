<?php
if ( ! class_exists( 'bookingpress_pro_crons' ) ) {
	class bookingpress_pro_crons Extends BookingPress_Core {
		function __construct() {
			// To add custom timings for wp cron
			add_filter( 'cron_schedules', array( $this, 'bookingpress_mycron_schedules' ) );

			add_action( 'init', array( $this, 'bookingpress_add_crons' ), 10 );
			add_action( 'init', array( $this, 'bookingpress_schedule_report_crons'), 10 );

			add_action( 'bookingpress_before_appointment_approved_for_customer', array( $this, 'bookingpress_before_appointment_approved_for_customer' ) );
			add_action( 'bookingpress_before_appointment_pending_for_customer', array( $this, 'bookingpress_before_appointment_pending_for_customer' ) );
			add_action( 'bookingpress_after_appointment_approved_for_customer', array( $this, 'bookingpress_after_appointment_approved_for_customer' ) );
			add_action( 'bookingpress_after_appointment_pending_for_customer', array( $this, 'bookingpress_after_appointment_pending_for_customer' ) );
			add_action( 'bookingpress_after_appointment_canceled_for_customer', array( $this, 'bookingpress_after_appointment_canceled_for_customer' ) );
			add_action( 'bookingpress_after_appointment_rejected_for_customer', array( $this, 'bookingpress_after_appointment_rejected_for_customer' ) );
			add_action( 'bookingpress_after_appointment_completed_for_customer', array( $this, 'bookingpress_after_appointment_completed_for_customer' ) );

			add_action( 'bookingpress_before_appointment_approved_for_staffmember', array( $this, 'bookingpress_before_appointment_approved_for_staffmember' ) );
			add_action( 'bookingpress_before_appointment_pending_for_staffmember', array( $this, 'bookingpress_before_appointment_pending_for_staffmember' ) );
			add_action( 'bookingpress_after_appointment_approved_for_staffmember', array( $this, 'bookingpress_after_appointment_approved_for_staffmember' ) );
			add_action( 'bookingpress_after_appointment_pending_for_staffmember', array( $this, 'bookingpress_after_appointment_pending_for_staffmember' ) );
			add_action( 'bookingpress_after_appointment_canceled_for_staffmember', array( $this, 'bookingpress_after_appointment_canceled_for_staffmember' ) );
			add_action( 'bookingpress_after_appointment_rejected_for_staffmember', array( $this, 'bookingpress_after_appointment_rejected_for_staffmember' ) );
			add_action( 'bookingpress_after_appointment_completed_for_staffmember', array( $this, 'bookingpress_after_appointment_completed_for_staffmember' ) );

			add_action( 'wp', array( $this, 'bookingpress_execute_schedular') );

			add_action( 'bookingpress_weekly_statistic_report', array( $this, 'bookingpress_send_weekly_report') );
			add_action( 'bookingpress_biweekly_statistic_report', array( $this, 'bookingpress_send_biweekly_report') );
			add_action( 'bookingpress_monthly_statistic_report', array( $this, 'bookingpress_send_monthly_report') );

		}

		function bookingpress_send_weekly_report(){
			global $BookingPress;

			$is_report_enabled = $BookingPress->bookingpress_get_settings('enable_scheduled_report', 'general_setting');
			$report_frequency = $BookingPress->bookingpress_get_settings( 'scheduled_report_frequency', 'general_setting' );

			if( (true === $is_report_enabled || 'true' == $is_report_enabled) && 'weekly' == $report_frequency ){
				$start_date = date('Y-m-d', strtotime( ' -1 week', current_time('timestamp') ) );
				$end_date = date ( 'Y-m-d', current_time('timestamp') );

				$report_data = $this->bookingpress_fetch_report_data( $start_date, $end_date );

				$this->bookingpress_send_report_to_admin( $report_data, $start_date, $end_date, 'weekly' );
			}
		}

		function bookingpress_send_biweekly_report(){
			global $BookingPress;

			$is_report_enabled = $BookingPress->bookingpress_get_settings('enable_scheduled_report', 'general_setting');
			$report_frequency = $BookingPress->bookingpress_get_settings( 'scheduled_report_frequency', 'general_setting' );
			if( (true === $is_report_enabled || 'true' == $is_report_enabled) && 'biweekly' == $report_frequency ){
				$start_date = date('Y-m-d', strtotime( ' -15 days', current_time('timestamp') ) );
				$end_date = date ( 'Y-m-d', current_time('timestamp') );

				$report_data = $this->bookingpress_fetch_report_data( $start_date, $end_date );
				$this->bookingpress_send_report_to_admin( $report_data, $start_date, $end_date, 'biweekly' );
			}
		}

		function bookingpress_send_monthly_report(){
			global $BookingPress;

			$is_report_enabled = $BookingPress->bookingpress_get_settings('enable_scheduled_report', 'general_setting');
			$report_frequency = $BookingPress->bookingpress_get_settings( 'scheduled_report_frequency', 'general_setting' );
			if( (true === $is_report_enabled || 'true' == $is_report_enabled) && 'monthly' == $report_frequency ){
				$start_date = date('Y-m-d', strtotime( ' -1 month', current_time('timestamp') ) );
				$end_date = date ( 'Y-m-d', current_time('timestamp') );

				$report_data = $this->bookingpress_fetch_report_data( $start_date, $end_date );
				$this->bookingpress_send_report_to_admin( $report_data, $start_date, $end_date, 'monthly' );
			}
		}

		function bookingpress_fetch_report_data( $start_date, $end_date ){
			global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings, $tbl_bookingpress_reschedule_history, $tbl_bookingpress_customers, $tbl_bookingpress_payment_logs, $tbl_bookingpress_services;

			$start_date_time = $start_date .' 00:00:00';
			$end_date_time = $end_date .' 23:59:59';

			$return_data = [];

			$appointment_statistics = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT CASE
						WHEN bookingpress_appointment_status IN ( 1, 2, 6 ) THEN 'completed'
						WHEN bookingpress_appointment_status = 3 THEN 'cancelled'
						WHEN bookingpress_appointment_status = 4 THEN 'rejected'
						WHEN bookingpress_appointment_status = 5 THEN 'no_show'
						ELSE 'unknown'
					END AS bpa_status_group,
					COUNT(*) AS total_appointments
					FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_created_at BETWEEN %s AND %s
					GROUP BY bpa_status_group
					UNION ALL
					SELECT 'total', COUNT(*) FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_created_at BETWEEN %s AND %s",
					$start_date_time,
					$end_date_time,
					$start_date_time,
					$end_date_time,
				),
				ARRAY_A
			);

			$total_appointments = $total_cancelled_appointments = $total_completed_apopintments = $total_no_show_appointments = $total_rejected_appointments = 0;

			$rescheduled_appointments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT bpa.bookingpress_appointment_booking_id FROM {$tbl_bookingpress_appointment_bookings} bpa RIGHT JOIN {$tbl_bookingpress_reschedule_history} brh ON bpa.bookingpress_appointment_booking_id = brh.bookingpress_appointment_id WHERE bookingpress_created_at BETWEEN %s AND %s GROUP BY bpa.bookingpress_appointment_booking_id", $start_date_time, $end_date_time
				)
			);
			
			if( !empty( $appointment_statistics ) ){
				foreach( $appointment_statistics as $statistic_data ){
					if( 'completed' == $statistic_data['bpa_status_group'] ){
						$total_completed_apopintments = $statistic_data['total_appointments'];
					} else if( 'no_show' == $statistic_data['bpa_status_group'] ){
						$total_no_show_appointments = $statistic_data['total_appointments'];
					} else if( 'rejected' == $statistic_data['bpa_status_group'] ){
						$total_rejected_appointments = $statistic_data['total_appointments'];
					} else if( 'cancelled' == $statistic_data['bpa_status_group'] ){
						$total_cancelled_appointments = $statistic_data['total_appointments'];
					} else if( 'total' == $statistic_data['bpa_status_group'] ){
						$total_appointments = $statistic_data['total_appointments'];
					}
				}
			}
			
			$total_new_customers = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tbl_bookingpress_customers} WHERE bookingpress_user_created BETWEEN %s AND %s GROUP BY bookingpress_user_email",
					$start_date_time, $end_date_time
				)
			);
			
			$return_data = [
				'total_appointments' => $total_appointments,
				'total_cancelled' => $total_cancelled_appointments,
				'total_rescheduled' => count( $rescheduled_appointments ),
				'total_completed' => $total_completed_apopintments,
				'total_noshow' => $total_no_show_appointments,
				'total_rejected' => $total_rejected_appointments,
				'total_customers' => count( $total_new_customers )
			];

			if( 0 < $total_cancelled_appointments ){
				$return_data['cancellation_rate'] = number_format((( $total_cancelled_appointments * 100 ) / $total_appointments), 2 ) . '%';
			} else {
				$return_data['cancellation_rate'] = '0%';
			}

			$total_income = 0;
			$total_refunded = 0;
			$total_pending = 0;

			$total_refunded = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT
					COALESCE( SUM( CASE WHEN bookingpress_payment_status = 3 THEN ( bookingpress_due_amount + bookingpress_paid_amount ) ELSE 0 END ), 0 ) +
					COALESCE( SUM( CASE WHEN bookingpress_payment_status = 5 THEN bookingpress_refund_amount ELSE 0 END ), 0 ) as total_refunded
					FROM {$tbl_bookingpress_payment_logs}
					WHERE bookingpress_created_at BETWEEN %s AND %s",
					$start_date_time,
					$end_date_time
				)
			);

			$total_income = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 
						COALESCE( SUM( CASE WHEN bookingpress_payment_status = 1 THEN (bookingpress_due_amount + bookingpress_paid_amount) ELSE 0 END ), 0 ) +
						COALESCE( SUM( CASE WHEN bookingpress_payment_status = 5 THEN ( (bookingpress_due_amount + bookingpress_paid_amount) - bookingpress_refund_amount ) ELSE 0 END ), 0 ) as total_paid
					FROM {$tbl_bookingpress_payment_logs}
					WHERE bookingpress_created_at BETWEEN %s AND %s",
					$start_date_time,
					$end_date_time
				)
			);

			//echo $wpdb->last_query;die;

			$total_partial_paid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM( bookingpress_paid_amount ) as total_partial_paid
					FROM {$tbl_bookingpress_payment_logs}
					WHERE bookingpress_payment_status = 4
					AND bookingpress_created_at BETWEEN %s AND %s",
					$start_date_time,
					$end_date_time
				)
			);

			$total_pending = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT
					COALESCE(SUM(CASE WHEN bookingpress_payment_status = 4 THEN bookingpress_due_amount ELSE 0 END), 0) +
					COALESCE(SUM(CASE WHEN bookingpress_payment_status = 2 THEN bookingpress_paid_amount ELSE 0 END), 0) AS total_sum
					FROM {$tbl_bookingpress_payment_logs}
					WHERE bookingpress_created_at BETWEEN %s AND %s",
					$start_date_time,
					$end_date_time
				)
			);

			$final_income = $total_income + $total_partial_paid;

			$return_data['total_income'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($final_income);
			$return_data['total_refunded'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($total_refunded);
			$return_data['total_pending'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($total_pending);


			$peak_day = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						DAYNAME(bookingpress_appointment_date) AS day_name,
						COUNT( * ) as total_bookings
					FROM $tbl_bookingpress_appointment_bookings
					WHERE bookingpress_created_at BETWEEN %s AND %s
					GROUP BY day_name
					ORDER BY total_bookings DESC",
					$start_date_time,
					$end_date_time
				)
			);

			$return_data['peak_day'] = !empty( $peak_day ) ? $peak_day->day_name : '';

			$most_booked_service = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						bs.bookingpress_service_name,
						COUNT(*) as total_bookings
					FROM {$tbl_bookingpress_services} bs
					LEFT JOIN {$tbl_bookingpress_appointment_bookings} bpa ON bpa.bookingpress_service_id = bs.bookingpress_service_id
					WHERE bpa.bookingpress_created_at BETWEEN %s AND %s
					GROUP BY bs.bookingpress_service_id
					ORDER BY total_bookings DESC",
					$start_date_time,
					$end_date_time
				)
			);

			$return_data['most_booked_service'] = !empty( $most_booked_service ) ? $most_booked_service->bookingpress_service_name : '';

			return $return_data;
		}

		function bookingpress_send_report_to_admin( $report_data, $start_date, $end_date, $type ){

			global $BookingPress, $bookingpress_email_notifications;

			$subject = esc_html__( '{type_of_report} BookingPress Summary Report - {start_date} to {end_date}', 'bookingpress-appointment-booking' );

			$subject = str_replace( '{type_of_report}', ucfirst( $type ), $subject );
			$subject = str_replace( '{start_date}', date_i18n( get_option('date_format'), strtotime( $start_date ) ), $subject );
			$subject = str_replace( '{end_date}', date_i18n( get_option('date_format'), strtotime( $end_date ) ), $subject );

			$logo_url = BOOKINGPRESS_PRO_URL . '/images/bpa-main-logo.jpg';
			
			$user = get_user_by( 'email', get_option('admin_email') );

				$body = '<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>'.$subject.'</title>
        <style type="text/css">
            @media screen {
                /* latin */
                @font-face {
                    font-family: "Outfit";
                    font-style: normal;
                    font-weight: 400;
                    font-display: swap;
                    src: url("https://fonts.gstatic.com/s/outfit/v6/QGYvz_MVcBeNP4NJtEtq.woff2") format("woff2");
                    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
                }
                /* latin */
                @font-face {
                    font-family: "Outfit";
                    font-style: normal;
                    font-weight: 600;
                    font-display: swap;
                    src: url("https://fonts.gstatic.com/s/outfit/v6/QGYvz_MVcBeNP4NJtEtq.woff2") format("woff2");
                    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
                }
                /* latin */
                @font-face {
                    font-family: "Poppins";
                    font-style: normal;
                    font-weight: 400;
                    font-display: swap;
                    src: url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecg.woff2) format("woff2");
                    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
                }
                /* latin */
                @font-face {
                    font-family: "Poppins";
                    font-style: normal;
                    font-weight: 500;
                    font-display: swap;
                    src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kv8JHgFVrLGT9Z1xlFQ.woff2) format("woff2");
                    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
                }
            }
            a {
                color: #333333;
                text-decoration: none;
            }
            #outlook a {
                padding: 0;
            }
            h1,h2,h3,h4,h5,h6{margin: 0;}
            body {
                width: 100% !important;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                margin: 0;
                padding: 0;
                font-family: "Poppins", Arial, Helvetica, sans-serif;
                font-weight: 400;
                color: #202C45;
            }
            img {
                outline: none;
                text-decoration: none;
                -ms-interpolation-mode: bicubic;
                display: inline-block;
                max-width: 100%;
            }
            a img {
                border: none;
            }
            p {
                margin: 0;
            }
            .bpa-hb-body{
                padding-top: 52px;
            }
            .bpa-hb-body h1{
                font-family: "Poppins", Arial, Helvetica, sans-serif;
                font-weight: 500;
                font-size: 18px !important;
                line-height: 26px;
                color: #202C45;
                margin-top: 80px;
            }
            .bpa-hb-body h1 span{
                color: #12D488;
            }
            .bpa-sec-heading{
                font-family: "Poppins", Arial, Helvetica, sans-serif;
                font-weight: 400;
                font-size: 18px;
                line-height: 32px;
                color: #202C45;
                margin-top: 40px;
            }
            .key-benfit-title{
                display: inline-block;
                line-height: 24px;
                vertical-align: top;
            }
            .key-benfit-title p{
                font-family: "Poppins", Arial, Helvetica, sans-serif;
                font-weight: 400;
                font-size: 18px !important;
                line-height: 28px;
                color: #535D71;
            }       
            .bpa-demos-btns-belt .bpa-dbb_group{
                margin: 0 auto;
            }
            .bpa-head-logo-img{
                width: 200px;
                height: 68px;
                background: url("'.$logo_url.'") 0 0 no-repeat;
                display: inline-block;
                border-radius: 6px;
            }
            .sale-banner {
                width: 100%;
                margin: 0 auto;
                text-align: center;
                padding: 30px 0 30px 0;
            }
			.bpa-sec-summary-table td{ width: 50%; }
			.bpa-sec-summary-table tr td{ padding-bottom: 15px; }
			.bpa-sec-summary-table tr td p{  font-family: "Poppins", Arial, Helvetica, sans-serif; font-weight: 500; color: #727E95; font-size: 16px; }
			.bpa-sec-summary-table tr td:last-child p{ color: #202C45 !important; }
            .bpa-learn-more img{ width: auto; }
            .bkp-mon-report{ margin: 0 auto;padding: 40px; background-color: #F4F7FB; }
            .bkp-mon-report .bkp-mon-report-tbody{ background-color: #fff; }
            a{ color: #0170B9; }
			.bpa-sec-heading-main{  font-family: "Poppins", Arial, Helvetica, sans-serif;  font-weight: 600;  font-size: 26px;  color: #202C45;  margin-top: 50px; }
            @media only screen and (max-device-width: 576px) {
                .bpa-learn-more img{ width: 35%; }
            }
            @media (prefers-color-scheme: dark) {            
                .key-benfit-title p,
                .bpa-hb-body h1,
                .bpa-sec-heading,
                .bpa-demos-btns-belt .bpa-dbb_group a:first-child{
                    color: #fff;
                }
                a{ color: #0170B9; }
            } 
            @media only screen and (max-width: 600px) {
                *[class="gmail-fix"] {
                    display: none !important;
                }
                .bpa-hb-body h1{ font-size: 22px !important; line-height: 30px !important; }
                .bpa-sec-heading{ font-size: 22px !important; line-height: 36px !important; }
                .key-benfit-title p{ font-size: 22px !important; line-height: 32px !important; }
            }
        </style>
        <meta name="robots" content="noindex,nofollow">
    </head>
<body data-gr-ext-installed="" data-new-gr-c-s-check-loaded="14.1198.0" __processed_c21ef84e-eebf-4387-b286-959b607e38de__="true" bis_status="ok" bis_frame_id="907" __processed_a082e754-d26e-4020-a96b-a1596b6d2648__="true" __processed_e4d80f89-521c-4bca-9edb-41a9125c0db5__="true" __processed_0f232823-2853-4667-85fe-eb05a47dbd99__="true" __processed_3013a3f2-91f6-4e85-a913-64f1e43b90f5__="true" __processed_a1242ac5-ae00-4d9c-aaef-d5471c340c74__="true">
        <table class="bkp-mon-report">
            <tbody class="bkp-mon-report-tbody">
                <tr><td>
                <div class="bpa-hero-banner" style="width:100%;">
                    <table width="700" cellspacing="0" cellpadding="0" border="0" align="center">
                        <tbody>
                            <tr>
                                <td class="bpa-hb-body" align="center">
                                    <a target="_top" href="https://www.bookingpressplugin.com/" rel="noopener">
                                        <span class="bpa-head-logo-img"></span>
                                    </a>
                                    <h3 class="bpa-sec-heading-main" style="text-align: center; width: 90%;margin-bottom: 40px;">'.esc_html__('Appointment', 'bookingpress-appointment-booking').' <span style="color:#12D488">'.esc_html__('Summary Report','bookingpress-appointment-booking').'</span></h3> 
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <table width="700" cellspacing="0" cellpadding="0" align="center">
                    <tbody>
                        <tr>
                            <td>
                                <h3 class="bpa-sec-heading" style="width: 90%; padding: 20px 0 10px 0; margin: 0 auto !important; font-weight: 600;">'.esc_html__('Dear {display_name},','bookingpress-appointment-booking').'</h3>
                                <h3 class="bpa-sec-heading" style="width: 90%; padding: 0 0 0 0; margin: 0 auto !important;">'.esc_html__('Please find below the {type_of_report} summary report for the appointment system, covering the period from {start_date} to {end_date}', 'bookingpress-appointment-booking').'</h3>
                                <h3 class="bpa-sec-heading" style="width: 90%; padding: 24px 0 0 0; margin: 0 auto !important;"><span style="font-weight: 500; font-size: 18px; line-height: 28px;">'.esc_html__('Appointment Summary', 'bookingpress-appointment-booking').'</span></h3>
                                <table class="bpa-sec-summary-table" cellpadding="0" cellspacing="0" border="0" style="padding:30px; width:90%; margin:20px auto 0; background:#EDFCF6; border-radius: 8px; border:1px solid rgba(18,212,136,.2); border-bottom-width: 3px;">
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Total New Customers:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{total_new_customers}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Total Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{total_appointments}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Completed Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{completed_appointments}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Cancelled Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{cancelled_appointments}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Rejected Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{rejected_appointments}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Rescheduled Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{rescheduled_appointments}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p>'.esc_html__('No-Show Appointments:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{no_show_appointments}</p></td>
                                    </tr>
                                </table>
                                <h3 class="bpa-sec-heading" style="width: 90%; padding: 24px 0 0 0; margin: 0 auto !important;"><span style="font-weight: 500; font-size: 18px; line-height: 28px;">'.esc_html__('Revenue Summary','bookingpress-appointment-booking').'</span></h3>
                                <table class="bpa-sec-summary-table" cellpadding="0" cellspacing="0" border="0" style="padding:30px; width:90%; margin:20px auto 0; background:#EDFCF6; border-radius: 8px; border:1px solid rgba(18,212,136,.2); border-bottom-width: 3px;">
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Total Income:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{total_income}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Total Refunded:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{total_refunded}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p>'.esc_html__('Total Pending:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{total_pending}</p></td>
                                    </tr>
                                </table>
                                <h3 class="bpa-sec-heading" style="width: 90%; padding: 24px 0 0 0; margin: 0 auto !important;"><span style="font-weight: 500; font-size: 18px; line-height: 28px;">'.esc_html__('Hightlights','bookingpress-appointment-booking').'</span></h3>
                                <table class="bpa-sec-summary-table" cellpadding="0" cellspacing="0" border="0" style="padding:30px; width:90%; margin:20px auto 0; background:#EDFCF6; border-radius: 8px; border:1px solid rgba(18,212,136,.2); border-bottom-width: 3px;">
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Peak Booking Day:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{peak_booking_day}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p>'.esc_html__('Most Requested Service:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 15px !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{most_booked_service}</p></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p>'.esc_html__('Cancellation Rate:', 'bookingpress-appointment-booking').'</p></td>
                                        <td style="padding-bottom: 0 !important; width: 50% !important;"><p style="color: #202C45 !important; text-align:left !important;">{cancellation_rate}</p></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table width="700" cellspacing="0" cellpadding="0" border="0" align="center">
                    <tbody>
                        <tr>
                            <td class="bpa-hb-body" style="padding-top: 52px; padding-bottom: 40px;">
                                <h3 class="bpa-sec-heading" style="text-align: left; width: 90%; margin: 0px  auto !important;">'.esc_html__('Best Regards,','bookingpress-appointment-booking').'</h3>
                                <h3 class="bpa-sec-heading" style="width: 90%; margin: 0 auto !important;  font-weight: 500;">BookingPress Team</h3>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="footer" style="background:#ECFCF5; width:700px; max-width: 100%; margin: 0 auto 0 !important; border-radius: 0px 0px 8px 8px;">
                    <table style="background:#ECFCF5; padding: 20px 0;" width="700" cellspacing="0px;" cellpadding="0px" align="center">
                        <tbody>
                            <tr>
                                <td>
                                    <h4 style="text-align: center; font-size: 13px; font-weight: normal; font-family: Poppins, Arial, Helvetica, sans-serif;">'.esc_html__('Follow us on','bookingpress-appointment-booking').'</h4>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:center; border: none; padding-top:8px; padding-bottom:12px; justify-content: center;">
                                    <a target="_top" href="https://www.facebook.com/BookingPressPlugin" style="clear: none; display: inline-block; width:24px; height:24px; margin-right:12px;" rel="noopener"><img src="'.BOOKINGPRESS_PRO_URL.'/images/bfs-fb.png" style="display: inline-block;" alt="facebook" border="0"></a>
                                    <a target="_top" href="https://www.instagram.com/bookingpress/" style="clear: none; display: inline-block; width:24px; height:24px; margin-right:12px;" rel="noopener"><img src="'.BOOKINGPRESS_PRO_URL.'/images/arf-instagram.png" style="display: inline-block;" alt="instagram" border="0"></a>
                                    <a target="_top" href="https://twitter.com/bookingpress" style="clear: none; display: inline-block; width:24px; height:24px; margin-right:12px;" rel="noopener"><img src="'.BOOKINGPRESS_PRO_URL.'/images/bfs-twitter.png" style="display: inline-block;" alt="twitter" border="0"></a>
                                    <a target="_top" href="https://www.youtube.com/@BookingPress/videos?sub_confirmation=1" style="clear: none; display: inline-block; width:24px; height:24px; margin-left:0;" rel="noopener"><img src="'.BOOKINGPRESS_PRO_URL.'/images/bfs-ytube.png" style="display: inline-block;" alt="youtube" border="0"></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td style="text-align: center; border:none; padding-top:20px; padding-bottom:12px; justify-content: center;font-size:14px; font-family: Poppins, Arial, Helvetica, sans-serif;">&copy; Copyright bookingpressplugin.com 2025. All rights reserved</td>
                </tr>
            </tfoot>
        </table>
    </body>
</html>';
				

			$body = str_replace( '{display_name}', ucfirst( $user->data->display_name), $body );

			$body = str_replace( '{type_of_report}', '<span style="font-weight:500">'. $type . '</span>', $body );
			$body = str_replace( '{start_date}', '<span style="font-weight:500">'. date_i18n( get_option('date_format'), strtotime( $start_date ) ) . '</span>', $body );
			$body = str_replace( '{end_date}', '<span style="font-weight:500">'. date_i18n( get_option('date_format'), strtotime( $end_date ) ) . '</span>', $body );

			$body = str_replace( [
				'{total_new_customers}',
				'{total_appointments}',
				'{completed_appointments}',
				'{cancelled_appointments}',
				'{rejected_appointments}',
				'{rescheduled_appointments}',
				'{no_show_appointments}',
				'{total_income}',
				'{total_refunded}',
				'{total_pending}',
				'{peak_booking_day}',
				'{most_booked_service}',
				'{cancellation_rate}'
			],[
				$report_data['total_customers'],
				$report_data['total_appointments'],
				$report_data['total_completed'],
				$report_data['total_cancelled'],
				$report_data['total_rejected'],
				$report_data['total_rescheduled'],
				$report_data['total_noshow'],
				$report_data['total_income'],
				$report_data['total_refunded'],
				$report_data['total_pending'],
				$report_data['peak_day'],
				$report_data['most_booked_service'],
				$report_data['cancellation_rate'],
			], $body);

			$report_email_message = $body;

			$from_name = $BookingPress->bookingpress_get_settings('sender_name', 'notification_setting');
			$from_email = $BookingPress->bookingpress_get_settings('sender_email', 'notification_setting');

			$reply_to_name = $BookingPress->bookingpress_get_settings('sender_name', 'notification_setting');
			$reply_to = $BookingPress->bookingpress_get_settings('sender_email', 'notification_setting');

			$bookingpress_admin_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
			$summary_report_subject = $subject;
			
			$bookingpress_email_notifications->bookingpress_send_custom_email_notifications( $bookingpress_admin_email, stripslashes_deep( $summary_report_subject ), stripslashes_deep( $report_email_message ), stripslashes_deep( $from_name ), $from_email, $reply_to, stripslashes_deep( $reply_to_name ) );
		}

		function bookingpress_execute_schedular(){

			if( isset( $_GET['bpa_action'] ) && 'bpa_send_scheduled_notifications' == $_GET['bpa_action'] ){

				/** email cron functions */
				$bookingpress_cron_hooks = $this->bookingpress_cron_hooks();
				foreach( $bookingpress_cron_hooks as $k => $v ){
					do_action( $v ); //Execute the cron functions immediately
				}

				do_action( 'bookingpress_force_send_scheduled_notifications');

				exit;
			}
		}
				
		/**
		 * Function for add/change WordPress scheudle time
		 *
		 * @param  mixed $schedules
		 * @return void
		 */
		function bookingpress_mycron_schedules( $schedules ) {
			if ( ! isset( $schedules['5min'] ) ) {
				$schedules['5min'] = array(
					'interval' => 5 * 60,
					'display'  => __( 'Every 05 minutes', 'bookingpress-appointment-booking' ),
				);
			}

			if( !isset( $schedules['bpa_weekly'] ) ){
				$schedules['bpa_weekly'] = [
					'interval' => WEEK_IN_SECONDS,
					'display' => esc_html__( 'Every Week', 'bookingpress-appointment-booking' )
				];
			}

			if( !isset( $schedules['bpa_biweekly'] ) ){
				$schedules['bpa_biweekly'] = [
					'interval' => 15 * DAY_IN_SECONDS,
					'display' => esc_html__( 'Every 15 Days', 'bookingpress-appointment-booking' )
				];
			}

			if( !isset( $schedules['bpa_monthly'] ) ){
				$schedules['bpa_monthly'] = [
					'interval' => MONTH_IN_SECONDS,
					'display' => esc_html__( 'Every Month', 'bookingpress-appointment-booking' )
				];
			}

			return $schedules;
		}
		
		/**
		 * Function for add WordPress schedulers
		 *
		 * @return void
		 */
		function bookingpress_add_crons() {
			wp_get_schedules();
			$bookingpress_cron_hooks = $this->bookingpress_cron_hooks();
			foreach ( $bookingpress_cron_hooks as $k => $v ) {
				if ( ! wp_next_scheduled( $v ) ) {
					wp_schedule_event( time(), '5min', $v );
				}
			}
		}

		function bookingpress_schedule_report_crons(){
			wp_get_schedules();
			if( !wp_next_scheduled( 'bookingpress_weekly_statistic_report' ) ){
				wp_schedule_event( time(), 'bpa_weekly', 'bookingpress_weekly_statistic_report' );
			}

			if( !wp_next_scheduled( 'bookingpress_biweekly_statistic_report' ) ){
				wp_schedule_event( time(), 'bpa_biweekly', 'bookingpress_biweekly_statistic_report' );
			}

			if( !wp_next_scheduled( 'bookingpress_monthly_statistic_report' ) ){
				wp_schedule_event( time(), 'bpa_monthly', 'bookingpress_monthly_statistic_report' );
			}
		}
		
		/**
		 * Function for add cron hooks
		 *
		 * @return void
		 */
		function bookingpress_cron_hooks() {
			$bookingpress_customer_cron_hooks_arr = array(
				'bookingpress_before_appointment_approved_for_customer',
				'bookingpress_before_appointment_pending_for_customer',
				/* 'bookingpress_before_appointment_canceled_for_customer',
				'bookingpress_before_appointment_rejected_for_customer', */
				'bookingpress_after_appointment_approved_for_customer',
				'bookingpress_after_appointment_pending_for_customer',
				'bookingpress_after_appointment_canceled_for_customer',
				'bookingpress_after_appointment_rejected_for_customer',
				'bookingpress_after_appointment_completed_for_customer',
			);

			$bookingpress_staffmember_cron_hooks_arr = array(
				'bookingpress_before_appointment_approved_for_staffmember',
				'bookingpress_before_appointment_pending_for_staffmember',
				/* 'bookingpress_before_appointment_canceled_for_staffmember',
				'bookingpress_before_appointment_rejected_for_staffmember', */
				'bookingpress_after_appointment_approved_for_staffmember',
				'bookingpress_after_appointment_pending_for_staffmember',
				'bookingpress_after_appointment_canceled_for_staffmember',
				'bookingpress_after_appointment_rejected_for_staffmember',
				'bookingpress_after_appointment_completed_for_staffmember',
			);

			$bookingpress_cron_hooks_arr = array_merge( $bookingpress_customer_cron_hooks_arr, $bookingpress_staffmember_cron_hooks_arr );

			return $bookingpress_cron_hooks_arr;
		}
		
		/**
		 * Function for get difference time in minutes
		 *
		 * @param  mixed $bookingpress_duration_val
		 * @param  mixed $bookingpress_duration_val_unit
		 * @return void
		 */
		function bookingpress_get_difference_time_in_minutes( $bookingpress_duration_val, $bookingpress_duration_val_unit ) {
			$bookingpress_difference_time = 60; // In minutes
			if ( $bookingpress_duration_val_unit == 'h' ) {
				$bookingpress_difference_time = 60 * $bookingpress_duration_val;
			} elseif ( $bookingpress_duration_val_unit == 'd' ) {
				$bookingpress_difference_time = 1440 * $bookingpress_duration_val;
			} elseif ( $bookingpress_duration_val_unit == 'w' ) {
				$bookingpress_difference_time = 10080 * $bookingpress_duration_val;
			} elseif ( $bookingpress_duration_val_unit == 'm' ) {
				$bookingpress_difference_time = 43800 * $bookingpress_duration_val;
			}

			return $bookingpress_difference_time;
		}
		
		/**
		 * Function for check cron email notification sent or not
		 *
		 * @param  mixed $bookingpress_email_notification_id
		 * @param  mixed $bookingpress_customer_id
		 * @param  mixed $bookingpress_email_address
		 * @param  mixed $bookingpress_appointment_id
		 * @param  mixed $bookingpress_appointment_date
		 * @param  mixed $bookingpress_appointment_time
		 * @param  mixed $bookingpress_appointment_status
		 * @param  mixed $bookingpress_hook_name
		 * @param  mixed $bookingpress_staffmember_id
		 * @param  mixed $bookingpress_staffmember_email
		 * @return void
		 */
		function bookingpress_check_cron_email_sent_or_not( $bookingpress_email_notification_id, $bookingpress_customer_id, $bookingpress_email_address, $bookingpress_appointment_id, $bookingpress_appointment_date, $bookingpress_appointment_time, $bookingpress_appointment_status, $bookingpress_hook_name, $bookingpress_staffmember_id = 0, $bookingpress_staffmember_email = '', $appointment_start_time = '' ) {
			global $wpdb, $tbl_bookingpress_cron_email_notifications_logs;

			$where_clause = $wpdb->prepare( ' AND bookingpress_appointment_time = %s', $bookingpress_appointment_time );
			if( !empty( $appointment_start_time ) ){
				$where_clause = $wpdb->prepare( "AND (bookingpress_appointment_time = %s OR bookingpress_appointment_time = %s) ", $bookingpress_appointment_time, $appointment_start_time ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_cron_email_notifications_logs is a table name. false alarm
			}

			if(empty($bookingpress_staffmember_id)){
				
				$bookingpress_is_record_exists = $wpdb->get_var( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_cron_email_notifications_logs} WHERE bookingpress_email_notification_id = %d AND bookingpress_customer_id = %d AND bookingpress_email_address = %s AND bookingpress_appointment_id = %d AND bookingpress_appointment_date = %s AND bookingpress_appointment_status = %s AND bookingpress_email_cron_hook_name = %s AND bookingpress_staffmember_email = %s {$where_clause}", $bookingpress_email_notification_id, $bookingpress_customer_id, $bookingpress_email_address, $bookingpress_appointment_id, $bookingpress_appointment_date,$bookingpress_appointment_status, $bookingpress_hook_name, $bookingpress_staffmember_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_cron_email_notifications_logs is a table name. false alarm
 
			}else if(!empty($bookingpress_staffmember_id)){
				$bookingpress_is_record_exists = $wpdb->get_var( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_cron_email_notifications_logs} WHERE bookingpress_email_notification_id = %d AND bookingpress_customer_id = %d AND bookingpress_email_address = %s AND bookingpress_appointment_id = %d AND bookingpress_appointment_date = %s AND bookingpress_appointment_status = %s AND bookingpress_email_cron_hook_name = %s AND bookingpress_staffmember_id = %d AND bookingpress_staffmember_email = %s {$where_clause}", $bookingpress_email_notification_id, $bookingpress_customer_id, $bookingpress_email_address, $bookingpress_appointment_id, $bookingpress_appointment_date, $bookingpress_appointment_status, $bookingpress_hook_name, $bookingpress_staffmember_id, $bookingpress_staffmember_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_cron_email_notifications_logs is a table name. false alarm
			}

			return $bookingpress_is_record_exists;
		}

		/*
		 * Customer Cron Hooks
		 * ---------------------------
		 */


		/**
		 * Function for send notification to customer before apppiotment approved
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_approved_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_approved( 'customer', 'before' );
			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );
 
				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '1', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {

						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '1', 'bookingpress_before_appointment_approved_for_customer' );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );


						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {

							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '1',
								'bookingpress_email_sending_date' => $current_date,									
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_approved_for_customer',
							);

							if( empty( $bookingpress_is_email_sent )  ){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								
								
								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);
								
								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );
								
								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}
							
							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer before apppiotment pending
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_pending_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_pending( 'customer', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '2', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '2', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '2', 'bookingpress_before_appointment_pending_for_customer' );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '2',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_pending_for_customer',
							);

							if(empty( $bookingpress_is_email_sent ) ){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer before apppiotment canceled
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_canceled_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_canceled( 'customer', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '3', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm
				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '3', 'bookingpress_before_appointment_canceled_for_customer' );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_time <= $bookingpress_appointment_time && $current_time >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '3',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_canceled_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];							

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer before apppiotment rejected
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_rejected_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_rejected( 'customer', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '4', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm
				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '4', 'bookingpress_before_appointment_rejected_for_customer' );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_time <= $bookingpress_appointment_time && $current_time >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {

							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '4',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_rejected_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer after apppiotment approved
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_approved_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_approved( 'customer', 'after' );
			
			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '1', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '1', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '1', 'bookingpress_after_appointment_approved_for_customer', 0, '', $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}
						
						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {

							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '1',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_approved_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer after apppiotment pending
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_pending_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_pending( 'customer', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );
				
				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '2', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '2', 'bookingpress_after_appointment_pending_for_customer', 0, '', $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_end_time'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '2',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_pending_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer after apppiotment canceled
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_canceled_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_canceled( 'customer', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '3', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '3', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '3', 'bookingpress_after_appointment_canceled_for_customer', 0, '', $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_end_time'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '3',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_canceled_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to customer after apppiotment rejected
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_rejected_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_rejected( 'customer', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );
				
				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '4', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '4', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '4', 'bookingpress_after_appointment_rejected_for_customer', 0, '', $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_end_time'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '4',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_rejected_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}


		/**
		 * Function for send notification to customer after apppiotment rejected
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_completed_for_customer() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_completed( 'customer', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );
				
				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '4', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_end_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_end_time ) >= %s", '6', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '6', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '6', 'bookingpress_after_appointment_completed_for_customer', 0, '', $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '6',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_completed_for_customer',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
								$bookingpress_cc_emails = apply_filters('bookingpress_add_customer_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name, $bookingpress_appointment_id);
								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'customer', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_customer_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'customer',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}


		/*
		 * Staff Members Cron Hooks
		 * ---------------------------
		 */
		

		/**
		 * Function for send notification to staffmember before apppiotment approved
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_approved_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_approved( 'employee', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '1', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '1', 'bookingpress_before_appointment_approved_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '1',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_approved_for_staffmember',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails);
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];
								
								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember before apppiotment pending
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_pending_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_pending( 'employee', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '2', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '2', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '2', 'bookingpress_before_appointment_pending_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '2',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_pending_for_staffmember',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember before apppiotment canceled
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_canceled_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_canceled( 'employee', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '3', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '3', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '3', 'bookingpress_before_appointment_canceled_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '3',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_canceled_for_staffmember',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember before apppiotment rejected
		 *
		 * @return void
		 */
		function bookingpress_before_appointment_rejected_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_rejected( 'employee', 'before' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '+' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '4', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s", '4', $notification_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '4', 'bookingpress_before_appointment_rejected_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $current_datetime <= $bookingpress_appointment_datetime && $current_datetime >= $bookingpress_notification_sending_time ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '4',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_before_appointment_rejected_for_staffmember',
							);
							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}
							
							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember after apppiotment approved
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_approved_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_approved( 'employee', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '1', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT(bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '1', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '1', 'bookingpress_after_appointment_approved_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email, $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '1',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_approved_for_staffmember',
							);
							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];							

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember after apppiotment pending
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_pending_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_pending( 'employee', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				//$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND bookingpress_appointment_date = %s", '2', $current_date ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '2', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '2', 'bookingpress_after_appointment_pending_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email, $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '2',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_pending_for_staffmember',
							);

							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];								

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember after apppiotment canceled
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_canceled_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_canceled( 'employee', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '3', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '3', 'bookingpress_after_appointment_canceled_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email, $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {						
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '3',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_canceled_for_staffmember',
							);
							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}
		
		/**
		 * Function for send notification to staffmember after apppiotment rejected
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_rejected_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_rejected( 'employee', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '4', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '4', 'bookingpress_after_appointment_rejected_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email, $bookingpress_appointment_start_time );

						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '3',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_rejected_for_staffmember',
							);
							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}

		/**
		 * Function for send notification to staffmember after apppiotment Completed
		 *
		 * @return void
		 */
		function bookingpress_after_appointment_completed_for_staffmember() {
			global $wpdb, $BookingPress, $bookingpress_pro_manage_notifications, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_pro_staff_members;
			$bookingpress_custom_notification_list = $bookingpress_pro_manage_notifications->bookingpress_get_custom_notification_for_appointment_completed( 'employee', 'after' );

			$bookingpress_email_notification_send_data = array();

			foreach ( $bookingpress_custom_notification_list as $k => $v ) {
				$bookingpress_notification_duration_val      = ! empty( $v['bookingpress_notification_duration_val'] ) ? $v['bookingpress_notification_duration_val'] : 1;
				$bookingpress_notification_duration_val_unit = ! empty( $v['bookingpress_notification_duration_unit'] ) ? $v['bookingpress_notification_duration_unit'] : 'h';

				$bookingpress_difference_time = $this->bookingpress_get_difference_time_in_minutes( $bookingpress_notification_duration_val, $bookingpress_notification_duration_val_unit );

				$bookingpress_notification_services_arr = ! empty( $v['bookingpress_notification_service'] ) ? explode( ',', $v['bookingpress_notification_service'] ) : array();

				$current_date = date( 'Y-m-d', current_time( 'timestamp' ) );
				$current_time = date( 'H:i', current_time( 'timestamp' ) );

				$notification_time = date( 'Y-m-d H:i:s', strtotime( $current_date . ' ' . $current_time . '-' . $bookingpress_difference_time . ' minutes' ) );

				$enabled_time = date('Y-m-d H:i', strtotime( $v['bookingpress_notification_enabled_at'] ) );

				$bookingpress_appointments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_status = %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) <= %s AND CONCAT( bookingpress_appointment_date, ' ', bookingpress_appointment_time ) >= %s", '6', $notification_time, $enabled_time ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

				if ( ! empty( $bookingpress_appointments ) && is_array( $bookingpress_appointments ) ) {
					
					foreach ( $bookingpress_appointments as $k2 => $v2 ) {
						$bookingpress_appointment_time          = $v2['bookingpress_appointment_end_time'];
						$bookingpress_appointment_start_time 	= $v2['bookingpress_appointment_time'];
						$bookingpress_notification_sending_time = date( 'H:i', strtotime( '-' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_time ) ) );

						$bookingpress_notification_id   = ! empty( $v['bookingpress_notification_id'] ) ? $v['bookingpress_notification_id'] : 0;
						$bookingpress_notification_name = ! empty( $v['bookingpress_notification_name'] ) ? $v['bookingpress_notification_name'] : '';
						$bookingpress_appointment_id    = $v2['bookingpress_appointment_booking_id'];

						$bookingpress_customer_id      = $v2['bookingpress_customer_id'];
						$bookingpress_customer_email   = ! empty( $v2['bookingpress_customer_email'] ) ? $v2['bookingpress_customer_email'] : '';

						$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
						$bookingpress_staffmember_email = !empty($v2['bookingpress_staff_email_address']) ? $v2['bookingpress_staff_email_address'] : '';
						if( !$bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation() ){
							$bookingpress_staffmember_email = $BookingPress->bookingpress_get_settings('admin_email', 'notification_setting');
						}

						$bookingpress_is_email_sent = $this->bookingpress_check_cron_email_sent_or_not( $bookingpress_notification_id, $bookingpress_customer_id, $bookingpress_customer_email, $bookingpress_appointment_id, $v2['bookingpress_appointment_date'], $bookingpress_appointment_time, '6', 'bookingpress_after_appointment_completed_for_staffmember', $bookingpress_staffmember_id, $bookingpress_staffmember_email, $bookingpress_appointment_start_time );
 
						$bookingpress_service_id       = ! empty( $v2['bookingpress_service_id'] ) ? $v2['bookingpress_service_id'] : 0;
						$bookingpress_is_allow_service = 1;
						if ( ! empty( $bookingpress_notification_services_arr ) && is_array( $bookingpress_notification_services_arr ) && ! empty( $bookingpress_service_id ) ) {
							if ( in_array( $bookingpress_service_id, $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} elseif ( in_array( 'any', $bookingpress_notification_services_arr ) ) {
								$bookingpress_is_allow_service = 1;
							} else {
								$bookingpress_is_allow_service = 0;
							}
						}

						$current_datetime = date('Y-m-d H:i:s', current_time('timestamp') );
						$bookingpress_appointment_datetime = $v2['bookingpress_appointment_date'] .' ' . $v2['bookingpress_appointment_end_time'];
						$bookingpress_notification_sending_time = date('Y-m-d H:i:s', strtotime( '+' . $bookingpress_difference_time . ' minutes', strtotime( $bookingpress_appointment_datetime ) ) );

						if ( ( $bookingpress_is_allow_service == 1 ) && ( $bookingpress_notification_sending_time <= $current_datetime ) && ! empty( $bookingpress_notification_name ) && ! empty( $bookingpress_appointment_id ) && ! empty( $bookingpress_customer_email ) ) {
							
							$bookingpress_db_fields = array(
								'bookingpress_email_notification_id' => $bookingpress_notification_id,
								'bookingpress_customer_id' => $bookingpress_customer_id,
								'bookingpress_staffmember_id' => $bookingpress_staffmember_id,
								'bookingpress_staffmember_email' => $bookingpress_staffmember_email,
								'bookingpress_email_address' => $bookingpress_customer_email,
								'bookingpress_appointment_id' => $bookingpress_appointment_id,
								'bookingpress_appointment_date' => $v2['bookingpress_appointment_date'],
								'bookingpress_appointment_time' => $bookingpress_appointment_time,
								'bookingpress_appointment_status' => '6',
								'bookingpress_email_sending_date' => $current_date,
								'bookingpress_email_sending_time' => $bookingpress_notification_sending_time,								
								'bookingpress_email_cron_hook_name' => 'bookingpress_after_appointment_completed_for_staffmember',
							);
							if(empty( $bookingpress_is_email_sent )){
								$bookingpress_cc_emails = array();
                    			$bookingpress_cc_emails = apply_filters('bookingpress_add_cc_email_address', $bookingpress_cc_emails, $bookingpress_notification_name);

								$bookingpress_email_send_res = $bookingpress_email_notifications->bookingpress_send_email_notification( 'employee', $bookingpress_notification_name, $bookingpress_appointment_id, $bookingpress_staffmember_email, $bookingpress_cc_emails );
								$is_email_sent               = $bookingpress_email_send_res['is_mail_sent'];
								$email_configurations        = $bookingpress_email_send_res['configurations'];
								$email_error_response        = $bookingpress_email_send_res['error_response'];

								$bookingpress_db_fields['bookingpress_notification_type'] = 'email';
								$bookingpress_db_fields['bookingpress_email_is_sent'] = $is_email_sent;

								$bookingpress_email_posted_data = array(
									'template_type'     => 'employee',
									'notification_name' => $bookingpress_notification_name,
									'appointment_id'    => $bookingpress_appointment_id,
									'customer_email'    => $bookingpress_customer_email,
									'staffmember_email' => $bookingpress_staffmember_email,
									'template_details'  => $bookingpress_email_send_res['posted_data'],
								);

								$bookingpress_db_fields['bookingpress_email_posted_data']           = wp_json_encode( $bookingpress_email_posted_data );
								$bookingpress_db_fields['bookingpress_email_response']              = wp_json_encode( $email_error_response );
								$bookingpress_db_fields['bookingpress_email_sending_configuration'] = wp_json_encode( $email_configurations );

								$wpdb->insert( $tbl_bookingpress_cron_email_notifications_logs, $bookingpress_db_fields );
								$bookingpress_last_inserted_id = $wpdb->insert_id;
							}

							do_action('bookingpress_staff_cron_external_notification', $bookingpress_appointment_id, $bookingpress_notification_name, $bookingpress_notification_id,$bookingpress_db_fields);
						}
					}
				}
			}
		}

	}
}
global $bookingpress_pro_crons;
$bookingpress_pro_crons = new bookingpress_pro_crons();
