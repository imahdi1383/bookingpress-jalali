<?php
if ( ! class_exists( 'bookingpress_pro_payment' ) ) {
	class bookingpress_pro_payment Extends BookingPress_Core {
		var $bookingpress_global_data;
		function __construct() {

			add_filter( 'bookingpress_modify_payment_data_fields', array( $this, 'bookingpress_modify_payment_data_fields_func' ), 10 );
			add_filter( 'bookingpress_modify_payment_view_file_path', array( $this, 'bookingpress_modify_payment_file_path_func' ), 10 );
			add_action( 'bookingpress_payment_dynamic_bulk_action', array( $this, 'bookingpress_payment_dynamic_bulk_action_func' ), 10 );
			add_action( 'wp_ajax_bookingpress_pro_bulk_payment_logs_action', array( $this, 'bookingpress_pro_bulk_payment_logs_action_func' ), 10 );

			add_action( 'bookingpress_payment_add_dynamic_vue_methods', array( $this, 'bookingpress_payment_add_dynamic_vue_methods_func' ), 10 );
			add_action( 'wp_ajax_bookingpress_export_payment_data', array( $this, 'bookingpress_export_payment_data_func' ), 10 );
			add_filter( 'bookingpress_modify_modal_payment_log_details', array( $this, 'bookingpress_modify_modal_payment_log_details_func' ), 10 );

			add_filter('bookingpress_modify_payments_listing_data', array($this, 'bookingpress_modify_payments_listing_data_func'), 10, 1);

			add_action('bookingpress_dynamic_add_onload_payment_methods', array($this, 'bookingpress_dynamic_add_onload_payment_methods_func'));

			add_action('bookingpress_payment_reset_filter',array($this,'bookingpress_payment_reset_filter_func'));
			
			add_action('bookingpress_update_payment_details_externally_after_update_status', array($this, 'bookingpress_update_payment_details_externally_after_update_status_func'));

			//Modify complete payment url content
			add_filter('bookingpress_modify_email_notification_content', array( $this, 'bookingpress_modify_email_content_func' ), 12, 4);
			add_filter('bookingpress_modify_email_content_filter', array( $this, 'bookingpress_modify_email_content_filter_func' ), 12, 4);
			
			//Send complete payment url notification
			add_action('bookingpress_after_add_appointment_from_backend', array($this, 'bookingpress_send_complete_payment_url_notification'), 11, 3);

			add_action('wp_ajax_bookingpress_send_complete_payment_link', array($this, 'bookingpress_send_complete_payment_link_func'));

			add_action('bookingpress_after_approve_appointment',array($this,'bookingpress_after_approve_appointment_func'),10);

			add_action( 'bookingpress_after_book_appointment', array( $this, 'bookingpress_generate_appointment_url' ), 10, 3 );	
			add_action('bookingpress_after_rescheduled_appointment', array($this,'bookingpress_after_rescheduled_appointment_update_token'), 11);
			add_action('bookingpress_after_cancel_appointment', array($this,'bookingpress_after_cancel_appointment_update_token'), 11);
			add_action('bookingpress_after_change_appointment_status', array( $this, 'bookingpress_after_change_appointment_status_update_token'), 11, 2 );

			add_action( 'bookingpress_after_get_payment_response', array( $this, 'bookingpress_after_get_payment_response_modification') );

			add_action('wp_ajax_bookingpress_apply_for_manual_adjustment',array($this,'bookingpress_apply_for_manual_adjustment_func'),10);
			
			add_action( 'bookingpress_payment_page_dialog_outside', [ $this, 'bookingpress_payment_page_dialog_outside'] );
			add_action( 'bookingpress_admin_vue_data_variables_script', function(){
				$valid_key = hash_hmac('sha256', '_9112021029174191151512', home_url() );
				$valid_key_value = get_option( $valid_key, false );

				$stnemtnioppa = strrev( 'stnemtnioppa_latot' );
				$wenever = strrev( 'eunever_latot' );

				$total_appointments = get_option( $stnemtnioppa ) ?? 0;
				$total_payments = get_option( $wenever ) ?? 0;

				?>
				bookingpress_return_data['rprt_model'] = <?php echo ( '0' === $valid_key_value && ( 100 <= $total_appointments || 5000 <= $total_payments ) ) ? 'true' : 'false'; ?>;
				bookingpress_return_data['rprt_license_key'] = '';
				bookingpress_return_data['rprt_verify_btn_loading'] = false;
				<?php
			} );

			add_action( 'bookingpress_admin_panel_vue_methods', function(){
				global $bookingpress_notification_duration;
				?>
				bookingpress_verify_license_key_func: function(rprt_license_form){
					const vm = this;
					let license_key = vm.rprt_license_key;

					if( '' == license_key ){
						vm.$refs[rprt_license_form].validateField('rprt_license_key');
						return false;
					}
					vm.rprt_verify_btn_loading = true;

					let bookingpress_request_data = {
						action: 'bookingpress_verify_key',
						_wpnonce: '<?php echo esc_html(wp_create_nonce('bpa_license_verify_nonce')); ?>',
						license_key: license_key,
					};

					axios.post("<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",Qs.stringify( bookingpress_request_data ) ).then(function(response){
						vm.rprt_verify_btn_loading = false;
						if( 'undefined' != typeof response.data.variant && response.data.variant == 'success' ){
							vm.$notify({
								title: '<?php esc_html_e('Success', 'bookingpress-appointment-booking'); ?>',
								message: response.data.msg,
								type: 'success',
								customClass: 'success_notification',
								duration:<?php echo intval($bookingpress_notification_duration); ?>,
							});
						}else{
							vm.$notify({
								title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
								message: response.data.msg ?? '<?php esc_html_e('Something went wrong..', 'bookingpress-appointment-booking'); ?>',
								type: 'error',
								customClass: 'error_notification',
								duration:<?php echo intval($bookingpress_notification_duration); ?>,
							});
						}
					}).catch(function(error){
						vm.rprt_verify_btn_loading = false;
						
					});
				},
				<?php
			},1);

		}

		function bookingpress_payment_page_dialog_outside(){
			$atad_dezirohtuanu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dezirohtuanU'  );
			extract( $atad_dezirohtuanu );
			$gnirts_atad_dezirohtuanu = strrev( "{$d}{$e}{$z}{$i}{$r}{$o}{$h}{$t}{$u}{$a}{$n}{$U}" );

			$atad_noitallatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'noitallatsnI' );
			extract( $atad_noitallatsni );
			$gnirts_atad_noitallatsni = strrev( "{$n}{$o}{$i}{$t}{$a}{$l}{$l}{$a}{$t}{$s}{$n}{$I}" );

			$atad_detceted = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'detceteD' );
			extract( $atad_detceted );
			$gnirts_atad_detceted = strrev( "{$d}{$e}{$t}{$c}{$e}{$t}{$e}{$D}" );

			$atad_siht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sihT' );
			extract( $atad_siht );
			$gnirts_atad_siht = strrev( "{$s}{$i}{$h}{$T}" );

			$atad_etisbew = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etisbew' );
			extract( $atad_etisbew );
			$gnirts_atad_etisbew = strrev( "{$e}{$t}{$i}{$s}{$b}{$e}{$w}" );

			$atad_si = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'si' );
			extract( $atad_si );
			$gnirts_atad_si = strrev( "{$s}{$i}" );

			$atad_gnitarepo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitarepo' );
			extract( $atad_gnitarepo );
			$gnirts_atad_gnitarepo = strrev( "{$g}{$n}{$i}{$t}{$a}{$r}{$e}{$p}{$o}" );

			$atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
			extract( $atad_sserpgnikoob );
			$gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

			$atad_tuohtiw = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tuohtiw' );
			extract( $atad_tuohtiw );
			$gnirts_atad_tuohtiw = strrev( "{$t}{$u}{$o}{$h}{$t}{$i}{$w}" );

			$atad_a = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'a' );
			extract( $atad_a );
			$gnirts_atad_a = strrev( "{$a}" );

			$atad_dilav = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dilav' );
			extract( $atad_dilav );
			$gnirts_atad_dilav = strrev( "{$d}{$i}{$l}{$a}{$v}" );

			$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
			extract( $atad_esnecil );
			$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

			$atad_etipsed = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etipsed' );
			extract( $atad_etipsed );
			$gnirts_atad_etipsed = strrev( "{$e}{$t}{$i}{$p}{$s}{$e}{$d}" );

			$atad_tnacifingis = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tnacifingis' );
			extract( $atad_tnacifingis );
			$gnirts_atad_tnacifingis = strrev( "{$t}{$n}{$a}{$c}{$i}{$f}{$i}{$n}{$g}{$i}{$s}" );

			$atad_laicremmoc = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laicremmoc' );
			extract( $atad_laicremmoc );
			$gnirts_atad_laicremmoc = strrev( "{$l}{$a}{$i}{$c}{$r}{$e}{$m}{$m}{$o}{$c}" );
			
			$atad_egasu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egasu' );
			extract( $atad_egasu );
			$gnirts_atad_egasu = strrev( "{$e}{$g}{$a}{$s}{$u}" );

			$atad_esaelp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esaelP' );
			extract( $atad_esaelp );
			$gnirts_atad_esaelp = strrev( "{$e}{$s}{$a}{$e}{$l}{$P}" );

			$atad_eton = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eton' );
			extract( $atad_eton );
			$gnirts_atad_eton = strrev( "{$e}{$t}{$o}{$n}" );

			$atad_taht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'taht' );
			extract( $atad_taht );
			$gnirts_atad_taht = strrev( "{$t}{$a}{$h}{$t}" );

			$atad_erutuf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'erutuf' );
			extract( $atad_erutuf );
			$gnirts_atad_erutuf = strrev( "{$e}{$r}{$u}{$t}{$u}{$f}" );

			$atad_setadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'setadpu' );
			extract( $atad_setadpu );
			$gnirts_atad_setadpu = strrev( "{$s}{$e}{$t}{$a}{$d}{$p}{$u}" );

			$atad_yam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yam' );
			extract( $atad_yam );
			$gnirts_atad_yam = strrev( "{$y}{$a}{$m}" );

			$atad_yllacitamotua = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yllacitamotua' );
			extract( $atad_yllacitamotua );
			$gnirts_atad_yllacitamotua = strrev( "{$y}{$l}{$l}{$a}{$c}{$i}{$t}{$a}{$m}{$o}{$t}{$u}{$a}" );

			$atad_elbasid = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'elbasid' );
			extract( $atad_elbasid );
			$gnirts_atad_elbasid = strrev( "{$e}{$l}{$b}{$a}{$s}{$i}{$d}" );

			$atad_muimerp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'muimerp' );
			extract( $atad_muimerp );
			$gnirts_atad_muimerp = strrev( "{$m}{$u}{$i}{$m}{$e}{$r}{$p}" );

			$atad_serutaef = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'serutaef' );
			extract( $atad_serutaef );
			$gnirts_atad_serutaef = strrev( "{$s}{$e}{$r}{$u}{$t}{$a}{$e}{$f}" );

			$atad_dna = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dna' );
			extract( $atad_dna );
			$gnirts_atad_dna = strrev( "{$d}{$n}{$a}" );

			$atad_evomer = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'evomer' );
			extract( $atad_evomer );
			$gnirts_atad_evomer = strrev( "{$e}{$v}{$o}{$m}{$e}{$r}" );

			$atad_derots = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'derots' );
			extract( $atad_derots );
			$gnirts_atad_derots = strrev( "{$d}{$e}{$r}{$o}{$t}{$s}" );

			$atad_gnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnikoob' );
			extract( $atad_gnikoob );
			$gnirts_atad_gnikoob = strrev( "{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

			$atad_detaler = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'detaler' );
			extract( $atad_detaler );
			$gnirts_atad_detaler = strrev( "{$d}{$e}{$t}{$a}{$l}{$e}{$r}" );

			$atad_atad = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'atad' );
			extract( $atad_atad );
			$gnirts_atad_atad = strrev( "{$a}{$t}{$a}{$d}" );

			$atad_morf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'morf' );
			extract( $atad_morf );
			$gnirts_atad_morf = strrev( "{$m}{$o}{$r}{$f}" );

			$atad_non = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'non' );
			extract( $atad_non );
			$gnirts_atad_non = strrev( "{$n}{$o}{$n}" );

			$atad_etamigitel = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etamigitel' );
			extract( $atad_etamigitel );
			$gnirts_atad_etamigitel = strrev( "{$e}{$t}{$a}{$m}{$i}{$g}{$i}{$t}{$e}{$l}" );

			$atad_snoitallatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitallatsni' );
			extract( $atad_snoitallatsni );
			$gnirts_atad_snoitallatsni = strrev( "{$s}{$n}{$o}{$i}{$t}{$a}{$l}{$l}{$a}{$t}{$s}{$n}{$i}" );

			$atad_retfa = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'retfa' );
			extract( $atad_retfa );
			$gnirts_atad_retfa = strrev( "{$r}{$e}{$t}{$f}{$a}" );

			$atad_ecarg = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ecarg' );
			extract( $atad_ecarg );
			$gnirts_atad_ecarg = strrev( "{$e}{$c}{$a}{$r}{$g}" );

			$atad_doirep = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'doirep' );
			extract( $atad_doirep );
			$gnirts_atad_doirep = strrev( "{$d}{$o}{$i}{$r}{$e}{$p}" );

			$atad_esahcrup = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esahcruP' );
			extract( $atad_esahcrup );
			$gnirts_atad_esahcrup = strrev( "{$e}{$s}{$a}{$h}{$c}{$r}{$u}{$P}" );

			$atad_na = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'na' );
			extract( $atad_na );
			$gnirts_atad_na = strrev( "{$n}{$a}" );

			$atad_laiciffo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laiciffo' );
			extract( $atad_laiciffo );
			$gnirts_atad_laiciffo = strrev( "{$l}{$a}{$i}{$c}{$i}{$f}{$f}{$o}" );

			$atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
			extract( $atad_sserpgnikoob );
			$gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

			$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
			extract( $atad_esnecil );
			$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

			$atad_ot = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ot' );
			extract( $atad_ot );
			$gnirts_atad_ot = strrev( "{$o}{$t}" );

			$atad_tneverp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tneverp' );
			extract( $atad_tneverp );
			$gnirts_atad_tneverp = strrev( "{$t}{$n}{$e}{$v}{$e}{$r}{$p}" );

			$atad_noitpursid = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'noitpursid' );
			extract( $atad_noitpursid );
			$gnirts_atad_noitpursid = strrev( "{$n}{$o}{$i}{$t}{$p}{$u}{$r}{$s}{$i}{$d}" );

			$atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
			extract( $atad_ruoy );
			$gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

			$atad_ssenisub = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ssenisub' );
			extract( $atad_ssenisub );
			$gnirts_atad_ssenisub = strrev( "{$s}{$s}{$e}{$n}{$i}{$s}{$u}{$b}" );

			$atad_snoitarepo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitarepo' );
			extract( $atad_snoitarepo );
			$gnirts_atad_snoitarepo = strrev( "{$s}{$n}{$o}{$i}{$t}{$a}{$r}{$e}{$p}{$o}" );

			?>
			<el-dialog custom-class="bpa-dialog bpa-dialog--rprt bpa-dialog--rprt-wrapper" :show-close="true" modal-append-to-body=false :visible.sync="rprt_model" :close-on-press-escape="close_modal_on_esc" v-cloak>
				<div class="bpa-dialog-body bpa-rprt-mdl-popup-wrapper">
					<div class="bpa-dialog-rprt-model-inner_wrapper">
						<svg width="134" height="134" viewBox="0 0 134 134" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M74.9557 25.6444L116.848 98.2087C120.388 104.34 115.963 112 108.884 112H25.0995C18.0206 112 13.5963 104.34 17.1357 98.2087L59.0281 25.6444C62.5676 19.5184 71.4162 19.5184 74.9557 25.6444Z" fill="#D63638"/>
							<path d="M108.884 104.523H25.0996C24.2776 104.523 23.8377 104.057 23.6126 103.664C23.3875 103.272 23.2042 102.654 23.6126 101.942L65.505 29.3829C65.9134 28.6708 66.5417 28.5242 66.992 28.5242C67.4475 28.5242 68.0706 28.6708 68.479 29.3829L110.371 101.942C110.78 102.649 110.596 103.267 110.371 103.659C110.146 104.057 109.706 104.523 108.884 104.523ZM25.0734 102.387L25.0996 103.455V102.387C25.0891 102.387 25.0839 102.387 25.0734 102.387ZM25.8169 102.387H108.162L66.992 31.0793L25.8169 102.387Z" fill="white"/>
							<path d="M70.5104 88.0196C70.5104 88.7002 70.4581 89.2762 70.3481 89.7422C70.2382 90.2082 70.0497 90.5799 69.7722 90.8627C69.4999 91.1454 69.1386 91.3496 68.6884 91.4753C68.2381 91.6009 67.6726 91.6638 66.9919 91.6638C66.3113 91.6638 65.7405 91.6009 65.285 91.4753C64.8295 91.3496 64.4682 91.1454 64.2012 90.8627C63.9342 90.5799 63.7457 90.2082 63.6409 89.7422C63.531 89.2762 63.4786 88.7055 63.4786 88.0196C63.4786 87.3232 63.531 86.7368 63.6409 86.2603C63.7509 85.7839 63.9342 85.4016 64.2012 85.1137C64.4682 84.8257 64.8295 84.6163 65.285 84.4906C65.7405 84.3649 66.3113 84.3021 66.9919 84.3021C67.6726 84.3021 68.2381 84.3649 68.6884 84.4906C69.1386 84.6163 69.4999 84.8205 69.7722 85.1137C70.0444 85.4069 70.2382 85.7891 70.3481 86.2603C70.4528 86.7316 70.5104 87.318 70.5104 88.0196ZM69.7355 80.7103C69.7198 80.8778 69.6675 81.0297 69.5837 81.1606C69.4999 81.2915 69.3533 81.4067 69.1491 81.4957C68.9397 81.5899 68.6569 81.658 68.3009 81.7103C67.9396 81.7627 67.505 81.7836 66.9919 81.7836C66.4579 81.7836 66.018 81.7575 65.6672 81.7103C65.3164 81.658 65.0389 81.5899 64.8295 81.4957C64.6201 81.4067 64.4787 81.2915 64.3949 81.1606C64.3111 81.0297 64.264 80.8778 64.2483 80.7103L63.5991 58.065C63.5991 57.8504 63.6514 57.6566 63.7509 57.4891C63.8504 57.3215 64.0284 57.1854 64.2902 57.0755C64.5468 56.9707 64.8976 56.887 65.3374 56.8398C65.7772 56.7875 66.3322 56.7665 66.9972 56.7665C67.6621 56.7665 68.2119 56.798 68.6412 56.8555C69.0758 56.9131 69.4161 56.9969 69.6779 57.1069C69.9345 57.2168 70.1178 57.3477 70.2277 57.5048C70.3377 57.6619 70.39 57.8504 70.39 58.065L69.7355 80.7103Z" fill="white"/>
						</svg>
						<div class="bpa-dialog-rprt-model-content-wrapper">
							<h2><?php echo $gnirts_atad_dezirohtuanu .' '. $gnirts_atad_noitallatsni . ' ' . $gnirts_atad_detceted; ?></h2>
							
							<p><?php echo $gnirts_atad_siht. ' ' . $gnirts_atad_etisbew . ' ' . $gnirts_atad_si . ' ' . $gnirts_atad_gnitarepo . ' '. $gnirts_atad_sserpgnikoob . ' ' . $gnirts_atad_tuohtiw.' ' . $gnirts_atad_a.' ' .$gnirts_atad_dilav. ' ' . $gnirts_atad_esnecil. ' ' . $gnirts_atad_etipsed. ' ' . $gnirts_atad_tnacifingis . ' ' . $gnirts_atad_laicremmoc . ' ' . $gnirts_atad_egasu . '.'; ?></p>
							
							<p><?php echo $gnirts_atad_esaelp .' ' . $gnirts_atad_eton .' ' . $gnirts_atad_taht .' ' . $gnirts_atad_erutuf .' ' . $gnirts_atad_setadpu .' ' . $gnirts_atad_yam .' ' . $gnirts_atad_yllacitamotua .' ' . $gnirts_atad_elbasid .' ' . $gnirts_atad_muimerp .' ' . $gnirts_atad_serutaef .' ' . $gnirts_atad_dna .' ' . $gnirts_atad_evomer .' ' . $gnirts_atad_derots .' ' . $gnirts_atad_gnikoob .'-' . $gnirts_atad_detaler .' ' . $gnirts_atad_atad .' ' . $gnirts_atad_morf .' ' . $gnirts_atad_non .' ' . $gnirts_atad_etamigitel .' ' . $gnirts_atad_snoitallatsni .' ' . $gnirts_atad_retfa .' ' . $gnirts_atad_a .' ' . $gnirts_atad_ecarg .' ' . $gnirts_atad_doirep . '.'; ?></p>

							<p><?php echo $gnirts_atad_esahcrup . ' ' . $gnirts_atad_na . ' ' . $gnirts_atad_laiciffo . ' ' . $gnirts_atad_sserpgnikoob . ' ' . $gnirts_atad_esnecil . ' ' . $gnirts_atad_ot . ' ' . $gnirts_atad_tneverp . ' ' . $gnirts_atad_noitpursid . ' ' . $gnirts_atad_ot . ' ' . $gnirts_atad_ruoy . ' ' . $gnirts_atad_ssenisub . ' ' . $gnirts_atad_snoitarepo ?></p>
						</div>
						<div class="bpa-dialog-rprt-model-content-btns">
							<?php
								$atad_yub = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yuB' );
								extract( $atad_yub );
								$gnirts_atad_yub = strrev( "{$y}{$u}{$B}" );

								$atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
								extract( $atad_ruoy );
								$gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

								$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
								extract( $atad_esnecil );
								$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );
							?>
							<el-button class="bpa-btn bpa-btn--primary" @click="window.open('https://www.bookingpressplugin.com/pricing/', '_blank')"> 
								<?php echo $gnirts_atad_yub . ' ' . $gnirts_atad_ruoy . ' ' . $gnirts_atad_esnecil; ?>
							</el-button>
							<?php
								$atad_fi = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'fi' );
								extract( $atad_fi );
								$gnirts_atad_fi = strrev( "{$f}{$i}" );

								$atad_uoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'uoy' );
								extract( $atad_uoy );
								$gnirts_atad_uoy = strrev( "{$u}{$o}{$y}" );

								$atad_evah = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'evah' );
								extract( $atad_evah );
								$gnirts_atad_evah = strrev( "{$e}{$v}{$a}{$h}" );

								$atad_na = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'na' );
								extract( $atad_na );
								$gnirts_atad_na = strrev( "{$n}{$a}" );

								$atad_gnitsixe = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitsixe' );
								extract( $atad_gnitsixe );
								$gnirts_atad_gnitsixe = strrev( "{$g}{$n}{$i}{$t}{$s}{$i}{$x}{$e}" );

								$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
								extract( $atad_esnecil );
								$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

								$atad_yek = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yek' );
								extract( $atad_yek );
								$gnirts_atad_yek = strrev( "{$y}{$e}{$k}" );

								$atad_etacol = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etacol' );
								extract( $atad_etacol );
								$gnirts_atad_etacol = strrev( "{$e}{$t}{$a}{$c}{$o}{$l}" );

								$atad_ti = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ti' );
								extract( $atad_ti );
								$gnirts_atad_ti = strrev( "{$t}{$i}" );

								$atad_no = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'no' );
								extract( $atad_no );
								$gnirts_atad_no = strrev( "{$n}{$o}" );

								$atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
								extract( $atad_ruoy );
								$gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

								$atad_dna = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'dna' );
								extract( $atad_dna );
								$gnirts_atad_dna = strrev( "{$d}{$n}{$a}" );

								$atad_retne = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'retne' );
								extract( $atad_retne );
								$gnirts_atad_retne = strrev( "{$r}{$e}{$t}{$n}{$e}" );

								$atad_woleb = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'woleb' );
								extract( $atad_woleb );
								$gnirts_atad_woleb = strrev( "{$w}{$o}{$l}{$e}{$b}" );

								$atad_tnuocca = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tnuoccA' );
								extract( $atad_tnuocca );
								$gnirts_atad_tnuocca = strrev( "{$t}{$n}{$u}{$o}{$c}{$c}{$A}" );

								$atad_egap = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egaP' );
								extract( $atad_egap );
								$gnirts_atad_egap = strrev( "{$e}{$g}{$a}{$P}" );

								$atad_sptth = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sptth' );
								extract( $atad_sptth );
								$gnirts_atad_sptth = strrev( "{$s}{$p}{$t}{$t}{$h}" );

								$atad_www = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'www' );
								extract( $atad_www );
								$gnirts_atad_www = strrev( "{$w}{$w}{$w}" );

								$atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserpgnikoob' );
								extract( $atad_sserpgnikoob );
								$gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$p}{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

								$atad_nigulp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'nigulp' );
								extract( $atad_nigulp );
								$gnirts_atad_nigulp = strrev( "{$n}{$i}{$g}{$u}{$l}{$p}" );

								$atad_moc = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'moc' );
								extract( $atad_moc );
								$gnirts_atad_moc = strrev( "{$m}{$o}{$c}" );

								$atad_eganam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eganam' );
								extract( $atad_eganam );
								$gnirts_atad_eganam = strrev( "{$e}{$g}{$a}{$n}{$a}{$m}" );

								$atad_sesnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sesnecil' );
								extract( $atad_sesnecil );
								$gnirts_atad_sesnecil = strrev( "{$s}{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );
							?>
							<p><?php echo $gnirts_atad_fi . ' ' . $gnirts_atad_uoy . ' ' . $gnirts_atad_evah . ' ' . $gnirts_atad_na . ' ' . $gnirts_atad_gnitsixe . ' ' . $gnirts_atad_esnecil . ' ' . $gnirts_atad_yek . ' ' . $gnirts_atad_etacol . ' ' . $gnirts_atad_ti . ' ' . $gnirts_atad_no . ' ' . $gnirts_atad_ruoy . ' ' . sprintf( '%1$s', "<a href='{$gnirts_atad_sptth}://{$gnirts_atad_www}.{$gnirts_atad_sserpgnikoob}{$gnirts_atad_nigulp}.{$gnirts_atad_moc}/{$gnirts_atad_eganam}-{$gnirts_atad_sesnecil}' target='_blank'>". $gnirts_atad_tnuocca. ' ' . $gnirts_atad_egap . '</a>' ) .' ' . $gnirts_atad_dna . ' ' . $gnirts_atad_retne . ' ' . $gnirts_atad_woleb; ?></p>
							<div class="bpa-rpt-license-input-wrapper">
								<el-form ref="rprt_license_form" class="bpa-rprt-license-form">
									<el-form-item prop="rprt_license_key">
										<?php
											$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esneciL' );
											extract( $atad_esnecil );
											$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$L}" );

											$atad_yek = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yeK' );
											extract( $atad_yek );
											$gnirts_atad_yek = strrev( "{$y}{$e}{$K}" );

											$atad_yfirev = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yfireV' );
											extract( $atad_yfirev );
											$gnirts_atad_yfirev = strrev( "{$y}{$f}{$i}{$r}{$e}{$V}" );
										?>
										<el-input placeholder="<?php echo $gnirts_atad_esnecil . ' ' . $gnirts_atad_yek; ?>" v-model="rprt_license_key" class="bpa-rprt-license-input"></el-input>&nbsp;
										<el-button class="bpa-btn bpa-btn--primary bpa-rprt-license-verify-btn" :class="{'bpa-btn--is-loader': rprt_verify_btn_loading}" @click="bookingpress_verify_license_key_func('rprt_license_form')">
											<span class="bpa-btn__label"><?php echo $gnirts_atad_yfirev . ' ' . $gnirts_atad_yek; ?></span>
											<div class="bpa-btn--loader__circles">				    
												<div></div>
												<div></div>
												<div></div>
											</div>
										</el-button>
									</el-form-item>
								</el-form>
							</div>
						</div>
						<?php
							$atad_ecno = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ecnO' );
							extract( $atad_ecno );
							$gnirts_atad_ecno = strrev( "{$e}{$c}{$n}{$O}" );

							$atad_ruoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ruoy' );
							extract( $atad_ruoy );
							$gnirts_atad_ruoy = strrev( "{$r}{$u}{$o}{$y}" );

							$atad_esnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'esnecil' );
							extract( $atad_esnecil );
							$gnirts_atad_esnecil = strrev( "{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

							$atad_si = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'si' );
							extract( $atad_si );
							$gnirts_atad_si = strrev( "{$s}{$i}" );

							$atad_deifirev = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'deifirev' );
							extract( $atad_deifirev );
							$gnirts_atad_deifirev = strrev( "{$d}{$e}{$i}{$f}{$i}{$r}{$e}{$v}" );

							$atad_uoy = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'uoy' );
							extract( $atad_uoy );
							$gnirts_atad_uoy = strrev( "{$u}{$o}{$y}" );

							$atad_tsum = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'tsum' );
							extract( $atad_tsum );
							$gnirts_atad_tsum = strrev( "{$t}{$s}{$u}{$m}" );

							$atad_etadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etadpu' );
							extract( $atad_etadpu );
							$gnirts_atad_etadpu = strrev( "{$e}{$t}{$a}{$d}{$p}{$u}" );

							$atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserPgnikooB' );
							extract( $atad_sserpgnikoob );
							$gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$P}{$g}{$n}{$i}{$k}{$o}{$o}{$B}" );

							$atad_htiw = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'htiw' );
							extract( $atad_htiw );
							$gnirts_atad_htiw = strrev( "{$h}{$t}{$i}{$w}" );

							$atad_eht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'eht' );
							extract( $atad_eht );
							$gnirts_atad_eht = strrev( "{$e}{$h}{$t}" );

							$atad_laiciffo = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'laiciffo' );
							extract( $atad_laiciffo );
							$gnirts_atad_laiciffo = strrev( "{$l}{$a}{$i}{$c}{$i}{$f}{$f}{$o}" );

							$atad_desnecil = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'desnecil' );
							extract( $atad_desnecil );
							$gnirts_atad_desnecil = strrev( "{$d}{$e}{$s}{$n}{$e}{$c}{$i}{$l}" );

							$atad_egakcap = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'egakcap' );
							extract( $atad_egakcap );
							$gnirts_atad_egakcap = strrev( "{$e}{$g}{$a}{$k}{$c}{$a}{$p}" );

							$atad_ot = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ot' );
							extract( $atad_ot );
							$gnirts_atad_ot = strrev( "{$o}{$t}" );

							$atad_diova = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'diova' );
							extract( $atad_diova );
							$gnirts_atad_diova = strrev( "{$d}{$i}{$o}{$v}{$a}" );

							$atad_erutuf = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'erutuf' );
							extract( $atad_erutuf );
							$gnirts_atad_erutuf = strrev( "{$e}{$r}{$u}{$t}{$u}{$f}" );

							$atad_snoitcirtser = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'snoitcirtser' );
							extract( $atad_snoitcirtser );
							$gnirts_atad_snoitcirtser = strrev( "{$s}{$n}{$o}{$i}{$t}{$c}{$i}{$r}{$t}{$s}{$e}{$r}" );

							$atad_ro = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ro' );
							extract( $atad_ro );
							$gnirts_atad_ro = strrev( "{$r}{$o}" );

							$atad_elbissop = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'elbissop' );
							extract( $atad_elbissop );
							$gnirts_atad_elbissop = strrev( "{$e}{$l}{$b}{$i}{$s}{$s}{$o}{$p}" );

							$atad_gnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnikoob' );
							extract( $atad_gnikoob );
							$gnirts_atad_gnikoob = strrev( "{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );

							$atad_atad = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'atad' );
							extract( $atad_atad );
							$gnirts_atad_atad = strrev( "{$a}{$t}{$a}{$d}" );

							$atad_seussi = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'seussi' );
							extract( $atad_seussi );
							$gnirts_atad_seussi = strrev( "{$s}{$e}{$u}{$s}{$s}{$i}" );

							$atad_wollof = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'wolloF' );
							extract( $atad_wollof );
							$gnirts_atad_wollof = strrev( "{$w}{$o}{$l}{$l}{$o}{$F}" );

							$atad_siht = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'siht' );
							extract( $atad_siht );
							$gnirts_atad_siht = strrev( "{$s}{$i}{$h}{$t}" );

							$atad_ediug = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'ediug' );
							extract( $atad_ediug );
							$gnirts_atad_ediug = strrev( "{$e}{$d}{$i}{$u}{$g}" );

							$atad_yllaunam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'yllaunam' );
							extract( $atad_yllaunam );
							$gnirts_atad_yllaunam = strrev( "{$y}{$l}{$l}{$a}{$u}{$n}{$a}{$m}" );

							$atad_nigulp = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'nigulp' );
							extract( $atad_nigulp );
							$gnirts_atad_nigulp = strrev( "{$n}{$i}{$g}{$u}{$l}{$p}" );

							$atad_stnemucod = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'stnemucod' );
							extract( $atad_stnemucod );
							$gnirts_atad_stnemucod = strrev( "{$s}{$t}{$n}{$e}{$m}{$u}{$c}{$o}{$d}" );

							$atad_gnillatsni = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnillatsni' );
							extract( $atad_gnillatsni );
							$gnirts_atad_gnillatsni = strrev( "{$g}{$n}{$i}{$l}{$l}{$a}{$t}{$s}{$n}{$i}" );

							$atad_gnitadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'gnitadpu' );
							extract( $atad_gnitadpu );
							$gnirts_atad_gnitadpu = strrev( "{$g}{$n}{$i}{$t}{$a}{$d}{$p}{$u}" );

							$atad_launam = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'launam' );
							extract( $atad_launam );
							$gnirts_atad_launam = strrev( "{$l}{$a}{$u}{$n}{$a}{$m}" );

							$atad_etadpu = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'etadpu' );
							extract( $atad_etadpu );
							$gnirts_atad_etadpu = strrev( "{$e}{$t}{$a}{$d}{$p}{$u}" );

							$v2_atad_sserpgnikoob = bookingpress_fileupload_class::bpa_file_fragment_chunk( 'sserpgnikoob' );
							extract( $v2_atad_sserpgnikoob );
							$v2_gnirts_atad_sserpgnikoob = strrev( "{$s}{$s}{$e}{$r}{$p}{$g}{$n}{$i}{$k}{$o}{$o}{$b}" );
						?>
						<div class="bpa-dialog-rprt-model-footer">
							<p>
								<?php
									echo $gnirts_atad_ecno. ' ' . $gnirts_atad_ruoy. ' ' . $gnirts_atad_esnecil. ' ' . $gnirts_atad_si. ' ' . $gnirts_atad_deifirev. ' ' . $gnirts_atad_uoy. ' ' . $gnirts_atad_tsum. ' ' . $gnirts_atad_etadpu. ' ' . $gnirts_atad_sserpgnikoob. ' ' . $gnirts_atad_htiw. ' ' . $gnirts_atad_eht. ' ' . $gnirts_atad_laiciffo. ' ' . $gnirts_atad_desnecil. ' ' . $gnirts_atad_egakcap. ' ' . $gnirts_atad_ot. ' ' . $gnirts_atad_diova. ' ' . $gnirts_atad_erutuf. ' ' . $gnirts_atad_snoitcirtser. ' ' . $gnirts_atad_ro. ' ' . $gnirts_atad_elbissop. ' ' . $gnirts_atad_gnikoob. ' ' . $gnirts_atad_atad. ' ' . $gnirts_atad_seussi. '. ' . $gnirts_atad_wollof. ' ' . sprintf( '%1$s', "<a href='{$gnirts_atad_sptth}://{$gnirts_atad_www}.{$v2_gnirts_atad_sserpgnikoob}{$gnirts_atad_nigulp}.{$gnirts_atad_moc}/{$gnirts_atad_stnemucod}/{$gnirts_atad_gnillatsni}-{$gnirts_atad_gnitadpu}-{$v2_gnirts_atad_sserpgnikoob}/#{$v2_gnirts_atad_sserpgnikoob}-{$gnirts_atad_launam}-{$gnirts_atad_etadpu}' target='_blank'>". $gnirts_atad_siht. ' ' . $gnirts_atad_ediug. '</a>' ) . ' ' . $gnirts_atad_ot. ' ' . $gnirts_atad_yllaunam . ' ' . $gnirts_atad_etadpu .' ' . $gnirts_atad_eht . ' '. $gnirts_atad_nigulp.'.';
								?>
							</p>
						</div>
					</div>
				</div>
			</el-dialog>
			<?php
		}


		function bookingpress_apply_for_manual_adjustment_func(){

			global $wpdb, $BookingPress, $BookingPressPro,$bookingpress_pro_payment_gateways, $bookingpress_tax, $tbl_bookingpress_payment_logs;
			$response = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'apply_for_manual_payment_adjustment', true, 'bpa_wp_nonce' );           

			if( preg_match( '/error/', $bpa_check_authorization ) ){
				$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
				$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

				$response['variant'] = 'error';
				$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
				$response['msg'] = $bpa_error_msg;

				wp_send_json( $response );
				die;
			}

			$response['variant'] = 'error';
			$response['title'] = esc_html__('Error', 'bookingpress-appointment-booking');
			$response['msg'] = esc_html__('Something went wrong while processing...', 'bookingpress-appointment-booking');

			$bookingpress_payment_adjust_data = ! empty( $_REQUEST['bookingpress_payment_adjust_data'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_REQUEST['bookingpress_payment_adjust_data'] ) : array();// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason $_REQUEST contains mixed array and will be sanitized using 'appointment_sanatize_field' function

			if(!empty($bookingpress_payment_adjust_data)) {

				$bookingpress_payment_id = !empty($bookingpress_payment_adjust_data['adjust_payment_id']) ? $bookingpress_payment_adjust_data['adjust_payment_id'] : 0;
				$bookingpress_appointment_id = !empty($bookingpress_payment_adjust_data['adjust_appointment_id']) ? $bookingpress_payment_adjust_data['adjust_appointment_id'] : 0;

				$bookingpress_payment_adjust_amount = !empty($bookingpress_payment_adjust_data['amount']) ? $bookingpress_payment_adjust_data['amount'] : 0;
				$bookingpress_payment_adjust_reason = !empty($bookingpress_payment_adjust_data['details']) ? sanitize_text_field($bookingpress_payment_adjust_data['details']) : "";
				$bookingpress_appointment_payment_logs_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_payment_logs} WHERE bookingpress_payment_log_id = %d", $bookingpress_payment_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_payment_logs is a table name. false alarm

				$bookingpress_payment_adjust_tax_amount = 0;

				$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
                $bookingpress_decimal_points = intval($bookingpress_decimal_points);
				
				$bookingpress_tax_percentage = ! empty( $bookingpress_appointment_payment_logs_data['bookingpress_tax_percentage'] ) ? sanitize_text_field( $bookingpress_appointment_payment_logs_data['bookingpress_tax_percentage'] ) : '';

				if ( class_exists('bookingpress_tax') && method_exists($bookingpress_tax, 'bookingpress_get_current_tax_percentage')) {
					if( !empty($bookingpress_appointment_payment_logs_data['bookingpress_price_display_setting']) ) {
						$bookingpress_tax_percentage = $bookingpress_tax->bookingpress_get_current_tax_percentage();
						if($bookingpress_appointment_payment_logs_data['bookingpress_price_display_setting'] == "include_taxes"){
							$bookingpress_payment_adjust_tax_amount = ($bookingpress_payment_adjust_amount * $bookingpress_tax_percentage) / (100 + $bookingpress_tax_percentage);
						}else{
							$bookingpress_payment_adjust_tax_amount = $bookingpress_payment_adjust_amount * ($bookingpress_tax_percentage / 100);
						}			
						$bookingpress_payment_adjust_tax_amount = floatval($bookingpress_payment_adjust_tax_amount);
					}
				}

				$bookingpress_payment_status = (isset($bookingpress_appointment_payment_logs_data['bookingpress_payment_status']))?$bookingpress_appointment_payment_logs_data['bookingpress_payment_status']:'';
				if($bookingpress_payment_status == 1){
					if($bookingpress_payment_adjust_amount > 0){
						$bookingpress_payment_status = 4;
					}
				}

				$wpdb->update($tbl_bookingpress_payment_logs, 
					array('bookingpress_payment_adjustment_reason' => $bookingpress_payment_adjust_reason,
					'bookingpress_payment_adjustment_amount' => $bookingpress_payment_adjust_amount,
					'bookingpress_payment_adjustment_tax_amount' => number_format($bookingpress_payment_adjust_tax_amount, $bookingpress_decimal_points),	
					'bookingpress_payment_status' => $bookingpress_payment_status,		
					'bookingpress_payment_adjustment_status' => 1),		 
				array('bookingpress_payment_log_id' => $bookingpress_payment_id));	

				$response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
				$response['variant'] = 'success';
				$response['msg'] = esc_html__('Payment details have been updated successfully.', 'bookingpress-appointment-booking');
			}

			wp_send_json( $response );
			die;			
		}	

		function bookingpress_after_get_payment_response_modification(){
			?>
			let items = (('undefined' != typeof response.data.items) ? response.data.items : ( 'undefined' != typeof response.data.upcoming_appointments ? response.data.upcoming_appointments : [] ));
			if( 0 < items.length ){
				let response_items = items;
				for( let index in response_items ){
					let item_data = response_items[index];
					
					vm.adjustAmountForm.details = item_data.bookingpress_payment_adjustment_reason;
					vm.adjustAmountForm.amount = item_data.bookingpress_payment_adjustment_amount;

					let appointment_details = item_data.appointment_details;
					let appointment_index = 0;
					let new_appointment_data = [];
					for( let appointment_data of appointment_details ){
						
						appointment_data.is_service_extra_row = false;
						appointment_data.appointment_index = (appointment_index + 1);
						appointment_data.bookingpress_deposit_amount_with_currency_symbol = vm.bookingpress_price_with_currency_symbol( appointment_data.bookingpress_deposit_amount );

						appointment_data.bookingpress_deposit_amount_with_currency_symbol = vm.bookingpress_price_with_currency_symbol( appointment_data.bookingpress_deposit_amount );
						appointment_data.bookingpress_deposit_amount_with_currency_symbol = vm.bookingpress_price_with_currency_symbol( appointment_data.bookingpress_deposit_amount );
						appointment_data.bookingpress_deposit_amount_with_currency_symbol = vm.bookingpress_price_with_currency_symbol( appointment_data.bookingpress_deposit_amount );

						new_appointment_data.push( appointment_data );
						if( 0 < appointment_data.extra_service_details.length ){
							let extras_details = appointment_data.extra_service_details;
							for( let extra_data of extras_details ){
								extra_data.is_service_extra_row = true;
								new_appointment_data.push( extra_data );
							}
						}

						if( 'undefined' != typeof appointment_data.bookingpress_applied_package_data_arr && 'undefined' != typeof appointment_data.bookingpress_package_discount_amount_with_currency && 0 != appointment_data.bookingpress_package_discount_amount_with_currency ){
							let package_data = {};
							package_data.is_service_package_row = true;
							package_data.package_name = appointment_data.bookingpress_applied_package_data_arr.bookingpress_package_name;
							package_data.package_discount = appointment_data.bookingpress_package_discount_amount_with_currency;
							new_appointment_data.push( package_data );
						}

						appointment_index++;
					}
					item_data.appointment_details_arr2 = new_appointment_data;
				}
			}
			<?php
		}

		function bookingpress_generate_appointment_url( $appointment_id, $entry_id = '', $payment_gateway_data = array()){
			if( empty( $appointment_id ) ){
				return;
			}
			$this->bookingpress_update_appointment_email_token($appointment_id);

		}

		function bookingpress_after_change_appointment_status_update_token($bookingpress_appointment_id, $appointment_status){
			$this->bookingpress_update_appointment_email_token($bookingpress_appointment_id);
		}

		function bookingpress_after_cancel_appointment_update_token($bookingpress_appointment_id){
			$this->bookingpress_update_appointment_email_token($bookingpress_appointment_id);
		}

		function bookingpress_after_rescheduled_appointment_update_token($bookingpress_appointment_id){
			$this->bookingpress_update_appointment_email_token($bookingpress_appointment_id);
		}

		function bookingpress_update_appointment_email_token($bookingpress_appointment_id){
			global $wpdb, $tbl_bookingpress_appointment_meta;
			$get_token_data = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_bookingpress_appointment_meta} WHERE bookingpress_appointment_meta_key = %s AND bookingpress_appointment_id = %d", 'appointment_approve_reject_token', $bookingpress_appointment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_meta is a table name. false alarm
			$bpa_uniq_token = uniqid("bpa", true);
			$expiration = time() + (48 * HOUR_IN_SECONDS);
			if( 1 > $get_token_data ){
				$wpdb->insert(
					$tbl_bookingpress_appointment_meta,
					array(
						'bookingpress_appointment_meta_key' => 'appointment_approve_reject_token',
						'bookingpress_appointment_meta_value' => $bpa_uniq_token,
						'bookingpress_appointment_id' => $bookingpress_appointment_id
					)
				);

				$wpdb->insert(
					$tbl_bookingpress_appointment_meta,
					array(
						'bookingpress_appointment_meta_key' => 'appointment_approve_reject_token_expiry',
						'bookingpress_appointment_meta_value' => $expiration,
						'bookingpress_appointment_id' => $bookingpress_appointment_id
					)
				);

			} else {
				$bookingpress_db_fields = array(
					'bookingpress_appointment_meta_value' => $bpa_uniq_token,						
				);							
				$wpdb->update($tbl_bookingpress_appointment_meta, $bookingpress_db_fields, array('bookingpress_appointment_id' => $bookingpress_appointment_id, 'bookingpress_appointment_meta_key' => 'appointment_approve_reject_token'));

				$bookingpress_db_fields = array(
					'bookingpress_appointment_meta_value' => $expiration,						
				);							
				$wpdb->update($tbl_bookingpress_appointment_meta, $bookingpress_db_fields, array('bookingpress_appointment_id' => $bookingpress_appointment_id, 'bookingpress_appointment_meta_key' => 'appointment_approve_reject_token_expiry'));
			}
		}

		function bookingpress_after_approve_appointment_func($payment_data) {

			global $tbl_bookingpress_appointment_bookings,$wpdb,$bookingpress_email_notifications;
			$bookingpress_payment_log_id = !empty($payment_data['bookingpress_payment_log_id']) ? intval($payment_data['bookingpress_payment_log_id']) : 0;
			$bookingpress_is_cart = !empty($payment_data['bookingpress_is_cart']) ? intval($payment_data['bookingpress_is_cart']) : 0;
			$bookingpress_order_id = !empty($payment_data['bookingpress_order_id']) ? intval($payment_data['bookingpress_order_id']) : 0;
			$bookingpress_customer_email = ! empty($payment_data['bookingpress_customer_email']) ? $payment_data['bookingpress_customer_email'] : '';

			if(!empty($bookingpress_order_id) && $bookingpress_is_cart  == 1 ) {
				$bookingpress_appointment_data = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_appointment_booking_id FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_payment_id = %d AND bookingpress_order_id = %d", $bookingpress_payment_log_id,$bookingpress_order_id ), ARRAY_A);// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_payment_logs is table name defined globally. False Positive alarm
				foreach($bookingpress_appointment_data as $key => $val) {
					$bookingpress_appointment_booking_id = !empty( $val['bookingpress_appointment_booking_id'] ) ? $val['bookingpress_appointment_booking_id'] : 0;
					if(!empty($bookingpress_appointment_booking_id)) {

						$bookingpress_check_appointment_status = $wpdb->get_var( $wpdb->prepare( "SELECT bookingpress_appointment_status FROM $tbl_bookingpress_appointment_bookings WHERE bookingpress_appointment_booking_id = %d", $bookingpress_appointment_booking_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_double_bookings is a table name. false alarm
						if($bookingpress_check_appointment_status != 1){  
							
							$wpdb->update($tbl_bookingpress_appointment_bookings, array( 'bookingpress_appointment_status' => '1' ), array( 'bookingpress_appointment_booking_id' => $bookingpress_appointment_booking_id ));                            
							$bookingpress_email_notifications->bookingpress_send_after_payment_log_entry_email_notification('Appointment Approved', $bookingpress_appointment_booking_id, $bookingpress_customer_email);
							do_action('bookingpress_after_change_appointment_status', $bookingpress_appointment_booking_id, '1');

						}

					}
				}
			}

		}

		function bookingpress_send_complete_payment_link_func(){
			global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications;
			$response              = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'send_payment_link', true, 'bpa_wp_nonce' );           
			if( preg_match( '/error/', $bpa_check_authorization ) ){
				$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
				$bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

				$response['variant'] = 'error';
				$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
				$response['msg'] = $bpa_error_msg;

				wp_send_json( $response );
				die;
			}

			$response['variant'] = 'error';
			$response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
			$response['msg'] = esc_html__('Something went wrong while sending payment link', 'bookingpress-appointment-booking');

			$bookingpress_payment_log_id = !empty($_POST['payment_log_id']) ? intval($_POST['payment_log_id']) : 0; //phpcs:ignore
			$bookingpress_selected_options = !empty($_POST['selected_options']) ? $_POST['selected_options'] : array(); //phpcs:ignore
			if(!empty($bookingpress_payment_log_id) && !empty($bookingpress_selected_options) ){
				$bookingpress_appointment_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_payment_id = %d", $bookingpress_payment_log_id), ARRAY_A); //phpcs:ignore

				$bookingpress_is_cart = !empty($bookingpress_appointment_details['bookingpress_is_cart']) ? intval($bookingpress_appointment_details['bookingpress_is_cart']) : 0;
				$bookingpress_order_id = 0;
				$bookingpress_appointment_id = $bookingpress_appointment_details['bookingpress_appointment_booking_id'];
				if($bookingpress_is_cart){
					$bookingpress_order_id = !empty($bookingpress_appointment_details['bookingpress_order_id']) ? intval($bookingpress_appointment_details['bookingpress_order_id']) : 0;

					if(!empty($bookingpress_order_id)){
						$bpa_uniq_token = uniqid("bpa", true);

						$wpdb->update($tbl_bookingpress_appointment_bookings, array('bookingpress_complete_payment_token' => $bpa_uniq_token), array('bookingpress_order_id' => $bookingpress_order_id));		
					}
				}else{
					$bpa_uniq_token = uniqid("bpa", true);

					$wpdb->update($tbl_bookingpress_appointment_bookings, array('bookingpress_complete_payment_token' => $bpa_uniq_token), array('bookingpress_appointment_booking_id' => $bookingpress_appointment_id));
				}

				if(in_array("email", $bookingpress_selected_options)){
					$bookingpress_email_notifications->bookingpress_send_after_payment_log_entry_email_notification('Complete Payment URL', $bookingpress_appointment_id, $bookingpress_appointment_details['bookingpress_customer_email']);
				}

				do_action('bookingpress_send_complete_payment_link_externally', $bookingpress_appointment_details, $bookingpress_selected_options);

				$response['variant'] = 'success';
				$response['title'] = esc_html__( 'Success', 'bookingpress-appointment-booking');
				$response['msg'] = esc_html__('Payment Link Send Successfully', 'bookingpress-appointment-booking');
			}else if(empty($bookingpress_selected_options)){
				$response['msg'] = esc_html__('Please select any method for send payment link', 'bookingpress-appointment-booking');
			}

			echo wp_json_encode($response);
			exit;
		}

		function bookingpress_send_complete_payment_url_notification($appointment_booking_id, $bookingpress_appointment_data, $entry_id){
			global $wpdb, $BookingPress, $tbl_bookingpress_appointment_bookings, $bookingpress_email_notifications, $tbl_bookingpress_payment_logs;
			$bookingpress_appointment_details = $wpdb->get_row($wpdb->prepare("SELECT bookingpress_payment_id,bookingpress_customer_email FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_booking_id), ARRAY_A);//phpcs:ignore
			if(!empty($bookingpress_appointment_details['bookingpress_customer_email']) && !empty($_POST['appointment_data']['complete_payment_url_selection']) && $_POST['appointment_data']['complete_payment_url_selection'] == 'send_payment_link' && !empty($_POST['appointment_data']['complete_payment_url_selected_method'] && in_array('email',$_POST['appointment_data']['complete_payment_url_selected_method']))){ //phpcs:ignore
				$bookingpress_email_notifications->bookingpress_send_after_payment_log_entry_email_notification('Complete Payment URL', $appointment_booking_id, $bookingpress_appointment_details['bookingpress_customer_email']);
				/** Set payment status log to pending */
				$wpdb->update(
					$tbl_bookingpress_payment_logs,
					array(
						'bookingpress_payment_status' => 2
					),
					array(
						'bookingpress_payment_log_id' => $bookingpress_appointment_details['bookingpress_payment_id']
					)
				);
			}			
		}

		function bookingpress_generate_complete_payment_url($appointment_id, $payment_unique_token){
			global $BookingPress;
			$bookingpress_generated_complete_payment_url = "";
			if( !empty($appointment_id) && !empty($payment_unique_token) ){
				$bookingpress_page_id = $BookingPress->bookingpress_get_settings('complete_payment_page_id','general_setting');
				if(!empty($bookingpress_page_id)){
					$bookingpress_generated_complete_payment_url = get_permalink($bookingpress_page_id);
					$bookingpress_generated_complete_payment_url = add_query_arg('bkp_pay', $payment_unique_token, $bookingpress_generated_complete_payment_url);
				}
			}

			return $bookingpress_generated_complete_payment_url;
		}
		
		/**
		 * Function for modify email content of complete payment url for the integration 
		 *
		 * @param  mixed $template_content
		 * @param  mixed $bookingpress_appointment_data
		 * @return void
		 */
		function bookingpress_modify_email_content_filter_func($template_content, $bookingpress_appointment_data){
			global $BookingPress, $BookingPressPro, $wpdb, $tbl_bookingpress_notifications, $tbl_bookingpress_appointment_meta;
			$bookingpress_complete_payment_url = "";

			$bookingpress_appointment_id = intval($bookingpress_appointment_data['bookingpress_appointment_booking_id']);
			$bookingpress_payment_uniq_token = !empty($bookingpress_appointment_data['bookingpress_complete_payment_token']) ? $bookingpress_appointment_data['bookingpress_complete_payment_token'] : '';

			if(!empty($bookingpress_payment_uniq_token)){
				$bookingpress_complete_payment_url = $this->bookingpress_generate_complete_payment_url($bookingpress_appointment_id, $bookingpress_payment_uniq_token);
			}

			$template_content = str_replace('%complete_payment_url%', $bookingpress_complete_payment_url, $template_content);			
			/*Approve Reject appointment link code starts from here */
			$bookingpress_is_cart = isset($bookingpress_appointment_data['bookingpress_is_cart']) ? intval($bookingpress_appointment_data['bookingpress_is_cart']) : 0;
			$bookingpress_appointment_approve_url = $bookingpress_appointment_reject_url = '';

			if($bookingpress_is_cart != 1){
				$appointment_approve_reject_token = $BookingPressPro->bookingpress_get_appointment_meta('appointment_approve_reject_token', $bookingpress_appointment_id);
				if(!empty($appointment_approve_reject_token)){
					$appointment_approve_reject_token = isset($appointment_approve_reject_token['bookingpress_appointment_meta_value']) ? $appointment_approve_reject_token['bookingpress_appointment_meta_value'] : ''; 
				}
				if(!empty($appointment_approve_reject_token)){
					$bookingpress_appointment_approve_url = $this->bookingpress_generate_appointment_approve_reject_url($bookingpress_appointment_id, $appointment_approve_reject_token, 'approve');
				}
				if(!empty($appointment_approve_reject_token)){
					$bookingpress_appointment_reject_url = $this->bookingpress_generate_appointment_approve_reject_url($bookingpress_appointment_id, $appointment_approve_reject_token, 'reject');
				}
			}

			$template_content = str_replace('%approve_appointment_url%', $bookingpress_appointment_approve_url, $template_content);
			$template_content = str_replace('%reject_appointment_url%', $bookingpress_appointment_reject_url, $template_content);

			return $template_content;
		}

		/**
		 * Function for modify email content of complete payment url
		 *
		 * @param  mixed $template_content
		 * @param  mixed $bookingpress_appointment_data
		 * @param  mixed $notification_name
		 * @return void
		 */
		function bookingpress_modify_email_content_func($template_content, $bookingpress_appointment_data, $notification_name = '',$template_type=''){
			global $BookingPress, $BookingPressPro, $wpdb, $tbl_bookingpress_notifications, $tbl_bookingpress_appointment_meta;
			$bookingpress_complete_payment_url = "";

			$bookingpress_appointment_id = intval($bookingpress_appointment_data['bookingpress_appointment_booking_id']);
			$bookingpress_payment_uniq_token = !empty($bookingpress_appointment_data['bookingpress_complete_payment_token']) ? $bookingpress_appointment_data['bookingpress_complete_payment_token'] : '';

			if(!empty($bookingpress_payment_uniq_token)){
				$bookingpress_complete_payment_url = $this->bookingpress_generate_complete_payment_url($bookingpress_appointment_id, $bookingpress_payment_uniq_token);
			}
			
			$template_content = str_replace('%complete_payment_url%', $bookingpress_complete_payment_url, $template_content);			
			/*Approve Reject appointment link code starts from here */
			$bookingpress_is_cart = isset($bookingpress_appointment_data['bookingpress_is_cart']) ? intval($bookingpress_appointment_data['bookingpress_is_cart']) : 0;
			$bookingpress_appointment_approve_url = $bookingpress_appointment_reject_url = '';

			if($template_type == 'employee' && $bookingpress_is_cart != 1){

				$appointment_approve_reject_token = $BookingPressPro->bookingpress_get_appointment_meta('appointment_approve_reject_token', $bookingpress_appointment_id);
				if(!empty($appointment_approve_reject_token)){
					$appointment_approve_reject_token = isset($appointment_approve_reject_token['bookingpress_appointment_meta_value']) ? $appointment_approve_reject_token['bookingpress_appointment_meta_value'] : ''; 
				}
				if(!empty($appointment_approve_reject_token)){
					$bookingpress_appointment_approve_url = $this->bookingpress_generate_appointment_approve_reject_url($bookingpress_appointment_id, $appointment_approve_reject_token, 'approve');
				}
				if(!empty($appointment_approve_reject_token)){
					$bookingpress_appointment_reject_url = $this->bookingpress_generate_appointment_approve_reject_url($bookingpress_appointment_id, $appointment_approve_reject_token, 'reject');
				}

				/* Check for trigger email notification */
				$notification_type = 'custom';
				$bookingpress_email_data = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_notification_id, bookingpress_custom_notification_type FROM {$tbl_bookingpress_notifications} WHERE bookingpress_notification_name = %s AND bookingpress_notification_receiver_type = %s AND bookingpress_notification_status = 1 AND bookingpress_notification_type = %s", $notification_name, $template_type, $notification_type ), ARRAY_A);// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_notifications is table name defined globally. False Positive alarm
				$bookingpress_custom_notification_type = '';
				if(!empty($bookingpress_email_data)) { 
					$bookingpress_custom_notification_type = isset($bookingpress_email_data['bookingpress_custom_notification_type']) ? $bookingpress_email_data['bookingpress_custom_notification_type'] : '';
					if($bookingpress_custom_notification_type == 'scheduled') {
						$bookingpress_appointment_approve_url = $bookingpress_appointment_reject_url = '';
					}
				}
				/* Check for trigger email notification */
			}

			$template_content = str_replace('%approve_appointment_url%', $bookingpress_appointment_approve_url, $template_content);
			$template_content = str_replace('%reject_appointment_url%', $bookingpress_appointment_reject_url, $template_content);
			/*Approve Reject appointment link code ends from here */

			return $template_content;
		}

		function bookingpress_generate_appointment_approve_reject_url($appointment_id, $unique_token, $action){
			global $BookingPress;
			$bookingpress_appointment_approve_reject_url = "";
			if( !empty($appointment_id) && !empty($unique_token) ){
				$args = array(
					'bkp_token' => $unique_token,
					'a_id' =>  base64_encode($appointment_id),					
					'bkp_action' => $action,	
				);
				$bookingpress_appointment_approve_reject_url = add_query_arg($args, BOOKINGPRESS_HOME_URL);
			}

			return $bookingpress_appointment_approve_reject_url;
		}


		function bookingpress_update_payment_details_externally_after_update_status_func($bookingpress_posted_data){
			global $wpdb, $BookingPress, $tbl_bookingpress_payment_logs;
			$bookingpress_payment_log_id = !empty($bookingpress_posted_data['payment_log_id']) ? intval($bookingpress_posted_data['payment_log_id']) : 0;
			$bookingpress_payment_status = !empty($bookingpress_posted_data['payment_status']) ? sanitize_text_field($bookingpress_posted_data['payment_status']) : '';

			if( !empty($bookingpress_payment_log_id) && !empty($bookingpress_payment_status) && ($bookingpress_payment_status == 1) ){
				$wpdb->update($tbl_bookingpress_payment_logs, array('bookingpress_mark_as_paid' => 1), array('bookingpress_payment_log_id' => $bookingpress_payment_log_id));
			}
		}

		function bookingpress_payment_reset_filter_func(){
			?>
			vm.search_data.search_staff_member = '';
			<?php
		}

		function bookingpress_calculate_payment_details($payment_log_id, $staffmember_id = ''){
			global $BookingPress, $wpdb, $tbl_bookingpress_payment_logs, $tbl_bookingpress_appointment_bookings, $bookingpress_pro_staff_members, $bookingpress_global_options, $tbl_bookingpress_staffmembers_commission;

			$bookingpress_global_settings = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_default_date_format = $bookingpress_global_settings['wp_default_date_format'];
			$bookingpress_default_time_format = $bookingpress_global_settings['wp_default_time_format'];

			$payment_logs_data = array();

			$payment_log_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_payment_logs} WHERE bookingpress_payment_log_id = %d", $payment_log_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_payment_logs is a table name. false alarm
			$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
			$bookingpress_decimal_points = intval($bookingpress_decimal_points);
			
			$bookingpress_selected_currency = '';
			if(!empty($payment_log_details)){
				$bookingpress_payment_status = $payment_log_details['bookingpress_payment_status'];
				$bookingpress_selected_gateway = $payment_log_details['bookingpress_payment_gateway'];
				$bookingpress_tax_percentage = $payment_log_details['bookingpress_tax_percentage'];
				$bookingpress_tax_amount = floatval($payment_log_details['bookingpress_tax_amount']);
				$bookingpress_coupon_details = ( !empty( $payment_log_details['bookingpress_coupon_details'] )  ) ? json_decode($payment_log_details['bookingpress_coupon_details'], TRUE) : array();
				$bookingpress_coupon_discount_amount = !empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) ? floatval($payment_log_details['bookingpress_coupon_discount_amount']) : 0;
				$bookingpress_deposit_details = $payment_log_details['bookingpress_deposit_payment_details'];
				$bookingpress_deposit_amount = $payment_log_details['bookingpress_deposit_amount'];
				$bookingpress_paid_amount = $payment_log_details['bookingpress_paid_amount'];
				$bookingpress_due_amount = $payment_log_details['bookingpress_due_amount'];
				$bookingpress_total_amount = $payment_log_details['bookingpress_total_amount'];
				$bookingpress_is_cart = intval($payment_log_details['bookingpress_is_cart']);
				$bookingpress_order_id = intval($payment_log_details['bookingpress_order_id']);
		
				$bookingpress_adjustment_reason = isset($payment_log_details['bookingpress_payment_adjustment_reason']) ? $payment_log_details['bookingpress_payment_adjustment_reason']: "";
				$bookingpress_adjustment_amount = isset($payment_log_details['bookingpress_payment_adjustment_amount']) ? floatval($payment_log_details['bookingpress_payment_adjustment_amount']) : 0;
				$bookingpress_adjustment_tax_amount = isset($payment_log_details['bookingpress_payment_adjustment_tax_amount']) ? floatval($payment_log_details['bookingpress_payment_adjustment_tax_amount']) : 0;
				
				if($bookingpress_is_cart == 1){
					$bookingpress_coupon_details = ( !empty( $payment_log_details['bookingpress_coupon_details'] )  ) ? json_decode($payment_log_details['bookingpress_coupon_details'], TRUE) : array();
					
					$is_particular_service_based = false;
					if(!empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details)){
						foreach($bookingpress_coupon_details as $bcdk){
							if(!empty($bcdk["bookingpress_coupon_services"])){
								$is_particular_service_based = true;
							}
						}
					}	
					
					if($is_particular_service_based || $bookingpress_payment_status == 1){
						$bookingpress_coupon_discount_amount = !empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) ? floatval($payment_log_details['bookingpress_coupon_discount_amount']) : 0;
					}else{
						$bookingpress_coupon_discount_amount = !empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) && isset($bookingpress_coupon_details[0]['coupon_discount_amount']) ? floatval($bookingpress_coupon_details[0]['coupon_discount_amount']) : 0;

						if($bookingpress_coupon_discount_amount <= 0){
							$bookingpress_coupon_discount_amount = !empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) ? floatval($payment_log_details['bookingpress_coupon_discount_amount']) : 0;
						}						
					}
					
				}else{
					$bookingpress_coupon_details = ( !empty( $payment_log_details['bookingpress_coupon_details'] )  ) ? json_decode($payment_log_details['bookingpress_coupon_details'], TRUE) : array();
					$bookingpress_coupon_discount_amount = !empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) ? floatval($payment_log_details['bookingpress_coupon_discount_amount']) : 0;
				}

				$bookingpress_currency_name = $payment_log_details['bookingpress_payment_currency'];
				$bookingpress_selected_currency = $BookingPress->bookingpress_get_currency_symbol($bookingpress_currency_name);

				$bookingpress_selected_gateway_label = $bookingpress_selected_gateway;
				$bookingpress_selected_gateway_label = apply_filters('bookingpress_selected_gateway_label_name', $bookingpress_selected_gateway_label, $bookingpress_selected_gateway);
				$payment_logs_data['selected_gateway'] = $bookingpress_selected_gateway;
				$payment_logs_data['selected_gateway_label'] = $bookingpress_selected_gateway_label;
				$payment_logs_data['payment_status'] = $bookingpress_payment_status;
				
				$payment_logs_data['is_cart'] = $bookingpress_is_cart;
				$payment_logs_data['order_id'] = $bookingpress_order_id;
				
				if($bookingpress_is_cart == 1){
					$payment_logs_data['staff_member_name'] = ' - ';
					$payment_logs_data['payment_service'] = ' - ';
					$payment_logs_data['appointment_date'] = ' - ';
				}

				$payment_logs_data['bookingpress_payment_adjustment_reason'] = $bookingpress_adjustment_reason;
				$payment_logs_data['bookingpress_payment_adjustment_amount'] = $bookingpress_adjustment_amount;
				$payment_logs_data['bookingpress_payment_adjustment_tax_amount'] = $bookingpress_adjustment_tax_amount;
				$payment_logs_data['bookingpress_payment_adjustment_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_adjustment_amount, $bookingpress_selected_currency);
				$payment_logs_data['bookingpress_payment_adjustment_tax_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_adjustment_tax_amount, $bookingpress_selected_currency);

				//Returns tax amount		
				$bookingpress_tax_amount = $bookingpress_tax_amount + $bookingpress_adjustment_tax_amount;
				
				$payment_logs_data['tax_amount'] = $bookingpress_tax_amount;
				$payment_logs_data['tax_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_tax_amount, $bookingpress_selected_currency);

				//Returns coupon details
				$payment_logs_data['coupon_discount_amount'] = $bookingpress_coupon_discount_amount;
				$payment_logs_data['coupon_discount_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_coupon_discount_amount, $bookingpress_selected_currency);
				$bookingpress_applied_coupon_code = "";
				if(!empty($bookingpress_coupon_details) && is_array($bookingpress_coupon_details) ){
					$bookingpress_applied_coupon_code = !empty($bookingpress_coupon_details['bookingpress_coupon_code']) ?$bookingpress_coupon_details['bookingpress_coupon_code'] : '';
					if(empty($bookingpress_applied_coupon_code) && !empty($bookingpress_coupon_details['coupon_data']['bookingpress_coupon_code'])){
						$bookingpress_applied_coupon_code = $bookingpress_coupon_details['coupon_data']['bookingpress_coupon_code'];
					}

					if(empty($bookingpress_applied_coupon_code) && !empty($bookingpress_coupon_details[0]['bookingpress_coupon_code'])){
						$bookingpress_applied_coupon_code = $bookingpress_coupon_details[0]['bookingpress_coupon_code'];
					}

					if (empty($bookingpress_applied_coupon_code)) {
						foreach ($bookingpress_coupon_details as $item) {
							if (!empty($item['coupon_data']['bookingpress_coupon_code'])) {
								$bookingpress_applied_coupon_code = $item['coupon_data']['bookingpress_coupon_code'];
								break; // Stop at the first valid code
							}
						}
					}				
				}
				$payment_logs_data['applied_coupon_code'] = $bookingpress_applied_coupon_code;


				$bookingpress_is_deposit_enable = 0;
				$bookingpress_subtotal_amount = 0;
				$staff_commission_amount = 0;
				$staff_commission_details = array();

				if(!empty($payment_log_details)){

					$where_clause = 'WHERE 1=1';

					if( !empty( $staffmember_id ) ){
						$where_clause .= $wpdb->prepare( ' AND bookingpress_staff_member_id = %d', $staffmember_id );
					}

					$bookingpress_appointment_details = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tbl_bookingpress_appointment_bookings} $where_clause AND bookingpress_payment_id = %d", $payment_log_id), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm

					$bookingpress_appointment_details = apply_filters('bookingpress_modify_outside_appointment_details',$bookingpress_appointment_details,$payment_log_id);
					
					$bookingpress_service_base_price_total_other = $past_edited_appointment = $bookingpress_service_base_price_total_discounted = 0;

					$bookingpress_price_display_setting = !empty($payment_log_details['bookingpress_price_display_setting']) ? $payment_log_details['bookingpress_price_display_setting'] : 'exclude_taxes';

					if(!empty($bookingpress_appointment_details) && is_array($bookingpress_appointment_details)){
						foreach($bookingpress_appointment_details as $k2 => $v2){
							
							$bookingpress_appointment_details[$k2]['bookingpress_service_name'] = !empty($bookingpress_appointment_details[$k2]['bookingpress_service_name']) ? stripslashes_deep($bookingpress_appointment_details[$k2]['bookingpress_service_name']) :'';
							if(isset($v2['bookingpress_enable_custom_duration'])  && $v2['bookingpress_enable_custom_duration'] == 1) {
								if( !empty( $v2['bookingpress_staff_member_details'] ) && !empty( $v2['bookingpress_staff_member_price'] ) ){                 
									$v2['bookingpress_staff_member_price'] = $v2['bookingpress_service_price'];									
								}
							}

							if( !empty( $v2['bookingpress_staff_member_details'] ) && !empty( $v2['bookingpress_staff_member_price'] ) ){
								$v2['bookingpress_service_price'] = $v2['bookingpress_staff_member_price'];
							}

							$bookingpress_tmp_subtotal_amount = $bookingpress_service_price = $v2['bookingpress_service_price'];
							$bookingpress_service_with_currency = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_service_price, $bookingpress_selected_currency);
							$bookingpress_appointment_details[$k2]['bookingpress_service_price_with_currency'] = $bookingpress_service_with_currency;

							$bookingpress_appointment_details[$k2]['bookingpress_appointment_date'] = date_i18n($bookingpress_default_date_format, strtotime($v2['bookingpress_appointment_date']));
							$bookingpress_appointment_details[$k2]['bookingpress_appointment_time'] = date($bookingpress_default_time_format, strtotime($v2['bookingpress_appointment_time']));
							$bookingpress_appointment_details[$k2]['bookingpress_appointment_end_time'] = date($bookingpress_default_time_format, strtotime($v2['bookingpress_appointment_end_time']));
							$bookingpress_appointment_details[$k2]['bookingpress_selected_extra_members'] = intval($bookingpress_appointment_details[$k2]['bookingpress_selected_extra_members']);

							//Check Deposit Applied or Not
							$bookingpress_appointment_details[$k2]['bookingpress_deposit_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_deposit_amount, $bookingpress_selected_currency);
							$bookingpress_deposit_details = !empty($v2['bookingpress_deposit_payment_details']) ? json_decode($v2['bookingpress_deposit_payment_details'], TRUE) : array();
							if(!empty($bookingpress_deposit_details) && !empty($bookingpress_deposit_details['deposit_selected_type']) && ($bookingpress_selected_gateway != 'on-site') ){
								$bookingpress_is_deposit_enable = $bookingpress_appointment_details[$k2]['is_deposit_applied'] = 1;
							}
							$bookingpress_staffmember_id = $v2['bookingpress_staff_member_id'];
							$bookingpress_staff_avatar_url = "";
							if(!empty($bookingpress_staffmember_id)){
								$bookingpress_staffmember_avatar = $bookingpress_pro_staff_members->get_bookingpress_staffmembersmeta($bookingpress_staffmember_id, 'staffmember_avatar_details');
								$bookingpress_staffmember_avatar = !empty($bookingpress_staffmember_avatar) ? maybe_unserialize($bookingpress_staffmember_avatar) : array();
								if (!empty($bookingpress_staffmember_avatar[0]['url'])) {
									$bookingpress_staff_avatar_url = $bookingpress_staffmember_avatar[0]['url'];
								}else{
									$bookingpress_staff_avatar_url = BOOKINGPRESS_IMAGES_URL . '/default-avatar.jpg';
								}

								$bookingpress_tmp_subtotal_amount = isset($v2['bookingpress_staff_member_price']) ? floatval($v2['bookingpress_staff_member_price']) : $bookingpress_tmp_subtotal_amount;
							}
							
							$bookingpress_appointment_details[$k2]['staff_avatar_url'] = $bookingpress_staff_avatar_url;

							//$bookingpress_selected_bring_anyone_members = intval($v2['bookingpress_selected_extra_members']) - 1;
							$bookingpress_selected_bring_anyone_members = !empty( $v2['bookingpress_selected_extra_members'] ) ? intval($v2['bookingpress_selected_extra_members']) - 1 : 0;

							if(!empty($bookingpress_selected_bring_anyone_members)){
								if(isset($v2['bookingpress_enable_service_slot_capacity'])  && $v2['bookingpress_enable_service_slot_capacity'] == 1) {
								}else {
									$bookingpress_tmp_subtotal_amount = $bookingpress_tmp_subtotal_amount + ($bookingpress_tmp_subtotal_amount * $bookingpress_selected_bring_anyone_members);
								}
							}

							$bookingpress_extra_total = 0;
							$bookingpress_extra_service_details = !empty($v2['bookingpress_extra_service_details']) ? json_decode($v2['bookingpress_extra_service_details'], TRUE) : array();
							$bookingpress_extra_service_data = array();
							if(!empty($bookingpress_extra_service_details)){
								foreach($bookingpress_extra_service_details as $k3 => $v3){
									$bookingpress_extra_total = $bookingpress_extra_total + $v3['bookingpress_final_payable_price'];
									$bookingpress_extra_service_price = ($v3['bookingpress_extra_service_details']['bookingpress_extra_service_price']) * ($v3['bookingpress_selected_qty']);
									$bookingpress_extra_service_data[] = array(
										'selected_qty' => $v3['bookingpress_selected_qty'],
										'extra_name' => $v3['bookingpress_extra_service_details']['bookingpress_extra_service_name'],
										'extra_service_price' => $bookingpress_extra_service_price,
										'extra_service_price_with_currency' => $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_extra_service_price, $bookingpress_selected_currency),
									);
								}
							}
							$bookingpress_appointment_details[$k2]['extra_service_details'] = $bookingpress_extra_service_data;

							$bookingpress_tmp_subtotal_amount = $bookingpress_tmp_subtotal_amount + $bookingpress_extra_total;

							$bookingpress_tmp_subtotal_amount = apply_filters('bookingpress_modify_outside_sub_total_amount_appointment_details',$bookingpress_tmp_subtotal_amount, $bookingpress_appointment_details[$k2], $bookingpress_selected_currency);

							$bookingpress_service_coupon_discount_amount = !empty($v2['bookingpress_coupon_discount_amount']) ? $v2['bookingpress_coupon_discount_amount']: 0;
							$bookingpress_service_coupon_details = !empty($v2['bookingpress_coupon_details']) ? json_decode($v2['bookingpress_coupon_details'], TRUE) : array();

							if( !empty($bookingpress_price_display_setting) && $bookingpress_price_display_setting == "include_taxes" && $bookingpress_service_coupon_discount_amount >0 && !empty($bookingpress_service_coupon_details) ){
								$bookingpress_service_base_price = $bookingpress_tmp_subtotal_amount - ( $bookingpress_tmp_subtotal_amount * $bookingpress_tax_percentage ) / ( 100 + $bookingpress_tax_percentage ); 

								$bookingpress_service_base_price_total_discounted = $bookingpress_service_base_price_total_discounted + $bookingpress_service_base_price;
							} else {
								$bookingpress_service_base_price = $bookingpress_tmp_subtotal_amount;		
								$bookingpress_service_base_price_total_other = $bookingpress_service_base_price_total_other + $bookingpress_service_base_price;
							}						
							
							$bookingpress_subtotal_amount = $bookingpress_subtotal_amount + $bookingpress_tmp_subtotal_amount;

							$past_edited_appointment = isset($bookingpress_appointment_details[$k2]['bookingpress_is_past_appointment_edited']) ? $bookingpress_appointment_details[$k2]['bookingpress_is_past_appointment_edited'] : 0;
							
							$bookingpress_appointment_booking_id = $v2['bookingpress_appointment_booking_id'];

							if($bookingpress_pro_staff_members->bookingpress_check_staffmember_commission_module_activation()){								
								$staff_commissions = $wpdb->get_results($wpdb->prepare("SELECT bookingpress_staff_first_name, bookingpress_staff_last_name, bookingpress_staff_email_address, bookingpress_commission_amount FROM {$tbl_bookingpress_staffmembers_commission} WHERE bookingpress_commission_appointment_id = %d",$bookingpress_appointment_booking_id), ARRAY_A);
								
								if (!empty($staff_commissions)) {
									foreach ($staff_commissions as $commission) {								
										$amount = isset($commission['bookingpress_commission_amount']) ? floatval($commission['bookingpress_commission_amount']) : 0;								
										$staff_name = trim(($commission['bookingpress_staff_first_name'] ?? '') . ' ' . ($commission['bookingpress_staff_last_name'] ?? ''));								
										$staff_email = $commission['bookingpress_staff_member_email'] ?? '';			
										$staff_commission_amount += $amount;
										$staff_commission_details[] = array(
											'staff_name' => $staff_name,
											'staff_email' => $staff_email,
											'commission_amount' => $BookingPress->bookingpress_price_formatter_with_currency_symbol($amount, $bookingpress_selected_currency)
										);
									}
								}								
							}
						}
						
						$payment_logs_data['appointment_details'] = $bookingpress_appointment_details;
					}
					$payment_logs_data['is_deposit_enable'] = $bookingpress_is_deposit_enable;
					$payment_logs_data['bookingpress_is_past_appointment_edited'] = (isset($past_edited_appointment))?$past_edited_appointment:0;
					
				}
				
				$bookingpress_price_display_setting = !empty($payment_log_details['bookingpress_price_display_setting']) ? $payment_log_details['bookingpress_price_display_setting'] : 'exclude_taxes';
				$payment_logs_data['price_display_setting'] = $bookingpress_price_display_setting;

				$bookingpress_tax_amount_in_order_summary = !empty($payment_log_details['bookingpress_display_tax_order_summary']) ? 'true' : 'false';
				$payment_logs_data['display_tax_amount_in_order_summary'] = $bookingpress_tax_amount_in_order_summary;

				$bookingpress_included_tax_label = !empty($payment_log_details['bookingpress_included_tax_label']) ? $payment_log_details['bookingpress_included_tax_label'] : '';
				$payment_logs_data['included_tax_label'] = $bookingpress_included_tax_label;

				$bookingpress_subtotal_amount_discounted =0;
				
				if( !empty($bookingpress_price_display_setting) && $bookingpress_price_display_setting == "exclude_taxes" ){
					$bookingpress_final_total_amount = ($bookingpress_subtotal_amount + $bookingpress_tax_amount) - $bookingpress_coupon_discount_amount;	
				}else{
					$bpa_tmp_subtotal_amount = $bookingpress_subtotal_amount - ( $bookingpress_subtotal_amount * $bookingpress_tax_percentage ) / ( 100 + $bookingpress_tax_percentage ); /* fetch the base price */

					$bookingpress_service_base_price_total_other = floatval(number_format($bookingpress_service_base_price_total_other, $bookingpress_decimal_points));
					$bookingpress_service_base_price_total_discounted = floatval(number_format($bookingpress_service_base_price_total_discounted, $bookingpress_decimal_points));
					$bookingpress_coupon_discount_amount_formatted = floatval(number_format($bookingpress_coupon_discount_amount, $bookingpress_decimal_points));

					$bpa_tmp_subtotal_with_discount = $bpa_tmp_subtotal_amount - $bookingpress_coupon_discount_amount; /** deduct coupon discount from the base price */

					$bookingpress_coupon_discount_amount_formatted = ( $bookingpress_coupon_discount_amount_formatted > $bookingpress_service_base_price_total_discounted ) ? $bookingpress_service_base_price_total_discounted : $bookingpress_coupon_discount_amount_formatted;

					$discounted = $bookingpress_service_base_price_total_discounted - $bookingpress_coupon_discount_amount_formatted;

					if( isset($discounted) && $discounted == 0 ){
						$bookingpress_subtotal_amount_discounted = number_format(floatval($bookingpress_service_base_price_total_discounted),$bookingpress_decimal_points ) + number_format(floatval($bookingpress_service_base_price_total_other),$bookingpress_decimal_points );
					} else {
						$bookingpress_subtotal_amount_discounted = 0;
					}	

					$bookingpress_final_total_amount = $bpa_tmp_subtotal_with_discount + ( $bpa_tmp_subtotal_with_discount  * ( $bookingpress_tax_percentage / 100 ) ); /** add tax on price after discount coupon */					
				}

				if($bookingpress_selected_gateway == "on-site"){
					//$bookingpress_paid_amount = $bookingpress_subtotal_amount;
					$bookingpress_deposit_amount = 0;
				}
				
				$currency_name   = $payment_log_details['bookingpress_payment_currency'];
				$currency_symbol = $BookingPress->bookingpress_get_currency_symbol($currency_name);

				$purchase_type   = isset($payment_log_details['bookingpress_purchase_type']) ? $payment_log_details['bookingpress_purchase_type'] : 1;
				$payment_logs_data['bookingpress_purchase_type'] = $purchase_type;

				$bookingpress_payment_adjustment_status   = isset($payment_log_details['bookingpress_payment_adjustment_status']) ? $payment_log_details['bookingpress_payment_adjustment_status'] : 1;
				$payment_logs_data['bookingpress_payment_adjustment_status'] = $bookingpress_payment_adjustment_status;

				$payment_logs_data['deposit_amount'] = $bookingpress_deposit_amount;
				$payment_logs_data['deposit_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_deposit_amount, $bookingpress_selected_currency);

				$bookingpress_refund_amount = !empty($payment_log_details['bookingpress_refund_amount']) ? $payment_log_details['bookingpress_refund_amount'] : 0;
				$bookingpress_refund_type = !empty($payment_log_details['bookingpress_refund_type']) ? $payment_log_details['bookingpress_refund_type'] : "";

				$bookingpress_due_amount = 0;
				if($bookingpress_deposit_amount != 0){
					if($bookingpress_is_cart == 1){
						$bookingpress_paid_amount = number_format((float)$bookingpress_paid_amount, 2, '.', '');
						$bookingpress_due_amount = floatval($bookingpress_final_total_amount) -  floatval($bookingpress_paid_amount);
					}else{
						$bookingpress_due_amount = $bookingpress_final_total_amount - $bookingpress_deposit_amount;
					}
					
				}
				if($bookingpress_refund_amount != 0){
					if($bookingpress_refund_type != "past_edit_refund" && $bookingpress_refund_type !="edit_refund"){
						$bookingpress_final_total_amount = $bookingpress_final_total_amount - $bookingpress_refund_amount;
						$bookingpress_paid_amount = $bookingpress_paid_amount - $bookingpress_refund_amount;
					}
				}

				$bookingpress_payment_status = (isset($payment_log_details['bookingpress_payment_status']))?$payment_log_details['bookingpress_payment_status']:'';
				$bookingpress_payment_adjustment_status = (isset($payment_log_details['bookingpress_payment_adjustment_status']))?$payment_log_details['bookingpress_payment_adjustment_status']:'';
				
				if($bookingpress_adjustment_amount != 0 && $bookingpress_payment_adjustment_status == 1){
					$bookingpress_due_amount = $bookingpress_due_amount + $bookingpress_adjustment_amount;
					if($bookingpress_payment_status == 2){ //when payment status is pending add unpaid amount in due amount
						$bookingpress_due_amount = $bookingpress_due_amount + $bookingpress_subtotal_amount;
					}
				}		
				
				if($bookingpress_adjustment_amount != 0 && $bookingpress_payment_adjustment_status != 0){
					$bookingpress_final_total_amount = $bookingpress_final_total_amount + $bookingpress_adjustment_amount;
				}

				$payment_logs_data['due_amount'] = floatval($bookingpress_due_amount);
				$payment_logs_data['due_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_due_amount, $bookingpress_selected_currency);

				$payment_logs_data['subtotal_amount'] = $bookingpress_subtotal_amount;
				$payment_logs_data['subtotal_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_subtotal_amount, $bookingpress_selected_currency);

				$payment_logs_data['subtotal_amount_discounted'] = $bookingpress_subtotal_amount_discounted;
				$payment_logs_data['subtotal_amount_with_discounted_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_subtotal_amount_discounted, $bookingpress_selected_currency);

				$payment_logs_data['payment_numberic_amount'] = floatval($bookingpress_paid_amount);
				$payment_logs_data['payment_amount'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_paid_amount, $bookingpress_selected_currency);

				$payment_logs_data['total_amount'] = $bookingpress_final_total_amount;

				$bookingpress_net_revenue_amount = $bookingpress_final_total_amount;				
				if($staff_commission_amount > 0){
					$bookingpress_net_revenue_amount = floatval($bookingpress_final_total_amount) -  floatval($staff_commission_amount);
				}
				
				$payment_logs_data['staff_commission_amount'] = $staff_commission_amount;
				$payment_logs_data['staff_commission_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($staff_commission_amount, $bookingpress_selected_currency);

				$payment_logs_data['staff_commission_details'] = $staff_commission_details;

				$payment_logs_data['staff_commission_net_revenue'] = $bookingpress_net_revenue_amount;
				$payment_logs_data['staff_commission_net_revenue_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_net_revenue_amount, $bookingpress_selected_currency);

				$payment_logs_data['total_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_final_total_amount, $bookingpress_selected_currency);

				$payment_logs_data['refund_amount'] = $bookingpress_refund_amount;
				$payment_logs_data['refund_amount_with_currency'] = $BookingPress->bookingpress_price_formatter_with_currency_symbol($bookingpress_refund_amount, $bookingpress_selected_currency);
				
			}
			$payment_logs_data = apply_filters('bookingpress_modify_outside_total_amount',$payment_logs_data, $payment_log_id, $bookingpress_selected_currency);

			return $payment_logs_data;
		}

		function bookingpress_modify_payments_listing_data_func($payment_logs_data){
			global $BookingPress, $BookingPressPro, $wpdb, $tbl_bookingpress_payment_logs, $tbl_bookingpress_appointment_bookings, $bookingpress_pro_staff_members, $bookingpress_global_options,$bookingpress_pro_appointment;
			
			if(!empty($payment_logs_data) && is_array($payment_logs_data) ){
				$bookingpress_global_settings = $bookingpress_global_options->bookingpress_global_options();
				$bookingpress_default_date_format = $bookingpress_global_settings['wp_default_date_format'];

				foreach($payment_logs_data as $k => $v){
					$payment_log_id = $v['payment_log_id'];

					$payment_log_details = $this->bookingpress_calculate_payment_details($payment_log_id);

					$payment_logs_data[$k]['staff_commission_amount'] = isset($payment_log_details['staff_commission_amount']) ? $payment_log_details['staff_commission_amount']: 0;
					$payment_logs_data[$k]['staff_commission_amount_with_currency'] = isset($payment_log_details['staff_commission_amount_with_currency']) ? $payment_log_details['staff_commission_amount_with_currency']: 0;
					$payment_logs_data[$k]['staff_commission_net_revenue'] = isset($payment_log_details['staff_commission_net_revenue'])? $payment_log_details['staff_commission_net_revenue']: 0;
					$payment_logs_data[$k]['staff_commission_net_revenue_with_currency'] = isset($payment_log_details['staff_commission_net_revenue_with_currency']) ? $payment_log_details['staff_commission_net_revenue_with_currency']: 0;

					$payment_logs_data[$k]['staff_commission_details'] = isset($payment_log_details['staff_commission_details']) ? $payment_log_details['staff_commission_details']: "";

					$payment_logs_data[$k]['payment_gateway_label'] = $payment_log_details['selected_gateway_label'];
					$payment_logs_data[$k]['is_cart'] = $payment_log_details['is_cart'];
					$payment_logs_data[$k]['order_id'] = $payment_log_details['order_id'];

					$payment_logs_data[$k]['bookingpress_is_past_appointment_edited'] = $payment_log_details['bookingpress_is_past_appointment_edited'];
					
					if($payment_log_details['is_cart'] == '1'){
						$payment_logs_data[$k]['staff_member_name'] = ' - ';
						$payment_logs_data[$k]['payment_service'] = ' - ';
						$payment_logs_data[$k]['appointment_date'] = ' - ';
					}

					$payment_logs_data[$k]['price_display_setting'] = $payment_log_details['price_display_setting'];
					$payment_logs_data[$k]['display_tax_order_summary'] = $payment_log_details['display_tax_amount_in_order_summary'];
					$payment_logs_data[$k]['included_tax_label'] = $payment_log_details['included_tax_label'];

					//Returns tax amount
					$payment_logs_data[$k]['tax_amount'] = $payment_log_details['tax_amount'];
					$payment_logs_data[$k]['tax_amount_with_currency'] = $payment_log_details['tax_amount_with_currency'];

					//Returns coupon details
					$payment_logs_data[$k]['coupon_discount_amount'] = $payment_log_details['coupon_discount_amount'];
					$payment_logs_data[$k]['coupon_discount_amount_with_currency'] = $payment_log_details['coupon_discount_amount_with_currency'];
					$payment_logs_data[$k]['applied_coupon_code'] = $payment_log_details['applied_coupon_code'];


					$bookingpress_is_deposit_enable = $payment_log_details['is_deposit_enable'];
					$bookingpress_subtotal_amount = $payment_log_details['subtotal_amount'];

					if(!empty($payment_log_details)){
						$payment_logs_data[$k]['appointment_details'] = !empty($payment_log_details['appointment_details']) ? $payment_log_details['appointment_details'] : array();
						$payment_logs_data[$k]['is_deposit_enable'] = $bookingpress_is_deposit_enable;
					}

					$payment_logs_data[$k]['deposit_amount'] = $payment_log_details['deposit_amount'];
					$payment_logs_data[$k]['deposit_amount_with_currency'] = $payment_log_details['deposit_amount_with_currency'];

					$payment_logs_data[$k]['due_amount'] = $payment_log_details['due_amount'];
					$payment_logs_data[$k]['due_amount_with_currency'] = $payment_log_details['due_amount_with_currency'];
					$due_amount = 0;
					$bookingpress_service_currency = "";
					if($payment_logs_data[$k]['is_cart']){
						if(!empty($payment_logs_data[$k]['appointment_details'])){
							foreach($payment_logs_data[$k]['appointment_details'] as $adk){
								if(!empty($adk["bookingpress_due_amount"])){
									$due_amount = $due_amount + (float)$adk["bookingpress_due_amount"];
									$bookingpress_service_currency = $adk["bookingpress_service_currency"];
								}
								
							}
						}
						if(!empty($due_amount) && !empty($payment_logs_data[$k]['coupon_discount_amount'])){
							$due_amount = $due_amount - $payment_logs_data[$k]['coupon_discount_amount'];
						}

					}
					if(!empty($bookingpress_service_currency)){

						if($payment_logs_data[$k]['price_display_setting'] == "exclude_taxes"){
							$due_amount = (float)$due_amount + (float)$payment_logs_data[$k]['tax_amount'];
						}

						$currency_symbol                     = $BookingPress->bookingpress_get_currency_symbol($bookingpress_service_currency);
						$due_amount_with_currency = $BookingPress->bookingpress_price_formatter_with_currency_symbol($due_amount, $currency_symbol);

						$payment_logs_data[$k]['due_amount'] = $due_amount;
						$payment_logs_data[$k]['due_amount_with_currency'] = $due_amount_with_currency;
					}

					$payment_logs_data[$k]['subtotal_amount'] = $payment_log_details['subtotal_amount'];
					$payment_logs_data[$k]['subtotal_amount_with_currency'] = $payment_log_details['subtotal_amount_with_currency'];

					$payment_logs_data[$k]['subtotal_amount_discounted'] = $payment_log_details['subtotal_amount_discounted'];
					$payment_logs_data[$k]['subtotal_amount_with_discounted_currency'] = $payment_log_details['subtotal_amount_with_discounted_currency'];		

					$payment_logs_data[$k]['payment_amount'] = $payment_log_details['payment_amount'];
					$payment_logs_data[$k]['payment_numberic_amount'] = floatval($payment_log_details['payment_numberic_amount']);

					$payment_logs_data[$k]['total_amount'] = $payment_log_details['total_amount'];
					$payment_logs_data[$k]['total_amount_with_currency'] = $payment_log_details['total_amount_with_currency'];

					$payment_logs_data[$k]['refund_amount'] = $payment_log_details['refund_amount'];
					$payment_logs_data[$k]['refund_amount_with_currency'] = $payment_log_details['refund_amount_with_currency'];

					$payment_logs_data[$k]['bookingpress_payment_adjustment_reason'] = $payment_log_details['bookingpress_payment_adjustment_reason'];
					$payment_logs_data[$k]['bookingpress_payment_adjustment_amount'] = $payment_log_details['bookingpress_payment_adjustment_amount'];
					$payment_logs_data[$k]['bookingpress_payment_adjustment_tax_amount'] = $payment_log_details['bookingpress_payment_adjustment_tax_amount'];
					$payment_logs_data[$k]['bookingpress_payment_adjustment_status'] = $payment_log_details['bookingpress_payment_adjustment_status'];
					$payment_logs_data[$k]['bookingpress_purchase_type'] = $payment_log_details['bookingpress_purchase_type'];

					$payment_logs_data[$k]['bookingpress_payment_adjustment_amount_with_currency'] = $payment_log_details['bookingpress_payment_adjustment_amount_with_currency'];
					$payment_logs_data[$k]['bookingpress_payment_adjustment_tax_amount_with_currency'] = $payment_log_details['bookingpress_payment_adjustment_tax_amount_with_currency'];
					

					if(isset($payment_logs_data[$k]['is_cart']) && $payment_logs_data[$k]['is_cart'] == 0 && isset($payment_logs_data[$k]['appointment_details'][0]['bookingpress_appointment_booking_id'])) {
						$refund_data = $bookingpress_pro_appointment->bookingpress_allow_to_refund($payment_logs_data[$k]['appointment_details'][0],0,0);
						$payment_logs_data[$k]['payment_refund_status'] = $refund_data['allow_refund'];
						$payment_logs_data[$k]['payment_partial_refund'] = $refund_data['allow_partial'];
						$payment_logs_data[$k]['appointment_id'] = $payment_logs_data[$k]['appointment_details'][0]['bookingpress_appointment_booking_id'];
						$bookingpress_service_currecy = $payment_logs_data[$k]['appointment_details'][0]['bookingpress_service_currency'];
						$payment_logs_data[$k]['appointment_currency_symbol'] = $BookingPress->bookingpress_get_currency_symbol($bookingpress_service_currecy);
					}
					
				}
			}

			if( $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' ) ){

				$bookingpress_user_id        = get_current_user_id();
				$bookingpress_staffmember_id = $bookingpress_pro_staff_members->bookingpress_get_staffmember_id_using_wp_user_id( $bookingpress_user_id );

				$loaded_payment_log_id = [];
				foreach( $payment_logs_data as $k => $v ){
					$loaded_payment_log_id[] = $v['payment_log_id'];
				}

				$where_clause = 'WHERE 1=1';
				$get_payments_from_appointments = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT pl.* FROM {$tbl_bookingpress_payment_logs} pl LEFT JOIN {$tbl_bookingpress_appointment_bookings} ap ON pl.bookingpress_order_id = ap.bookingpress_order_id {$where_clause} AND pl.bookingpress_is_cart = %d AND pl.bookingpress_staff_member_id IS NULL AND ap.bookingpress_staff_member_id = %d GROUP BY pl.bookingpress_payment_log_id", 1, $bookingpress_staffmember_id
					),
					ARRAY_A
				);

				if( !empty( $get_payments_from_appointments ) ){
					$payment_logs_staff_data = [];
					foreach ( $get_payments_from_appointments as $payment_log_key => $payment_log_val ) {
						$bookingpress_customer = ! empty($payment_log_val['bookingpress_customer_firstname']) ? ($payment_log_val['bookingpress_customer_firstname'] . ' ' . $payment_log_val['bookingpress_customer_lastname']) : ($payment_log_val['bookingpress_customer_email']);

						$service_name = $payment_log_val['bookingpress_service_name'];

						$appointment_date = $payment_log_val['bookingpress_appointment_date'];
						$payment_date     = $payment_log_val['bookingpress_payment_date_time'];
						$payment_gateway  = $payment_log_val['bookingpress_payment_gateway'];
						if ($payment_gateway == 'on-site' ) {
							$payment_gateway = 'on site';
						}


						$currency_name   = $payment_log_val['bookingpress_payment_currency'];
						$currency_symbol = $BookingPress->bookingpress_get_currency_symbol($currency_name);
						if ($payment_log_val['bookingpress_payment_amount'] == '0' ) {
							//$payment_amount = '';
							$payment_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol(0, $currency_symbol);
						} else {
							$payment_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol($payment_log_val['bookingpress_payment_amount'], $currency_symbol);
						}
						
						// get appointment status
						$appointment_ref_id = $payment_log_val['bookingpress_appointment_booking_ref'];
						$appointmentData    = $wpdb->get_row( $wpdb->prepare( "SELECT bookingpress_appointment_status FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_appointment_booking_id = %d", $appointment_ref_id ), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: $tbl_bookingpress_appointment_bookings is table name defined globally. False Positive alarm

						$bookingpress_global_settings = $bookingpress_global_options->bookingpress_global_options();
						$bookingpress_default_date_format = $bookingpress_global_settings['wp_default_date_format'];
						$bookingpress_default_time_format = $bookingpress_global_settings['wp_default_time_format'];
						$bookingpress_payment_status = $bookingpress_global_settings['payment_status'];

						$bookingpress_appointment_date = '';
						if ($appointment_date != '0000-00-00' ) {
							$bookingpress_appointment_date = date_i18n($bookingpress_default_date_format, strtotime($appointment_date));
						}

						$bookingpress_get_existing_avatar_url = $BookingPress->get_bookingpress_customersmeta($payment_log_val['bookingpress_customer_id'], 'customer_avatar_details');
						$bookingpress_get_existing_avatar_url = ! empty($bookingpress_get_existing_avatar_url) ? maybe_unserialize($bookingpress_get_existing_avatar_url) : array();
						if (! empty($bookingpress_get_existing_avatar_url[0]['url']) ) {
							$bookingpress_avatar_url = $bookingpress_get_existing_avatar_url[0]['url'];
						} else {
							$bookingpress_avatar_url = BOOKINGPRESS_IMAGES_URL . '/default-avatar.jpg';
						}

						$bookingpress_payment_status_label = $payment_log_val['bookingpress_payment_status'];
						foreach($bookingpress_payment_status as $payment_status_key => $payment_status_val){
							if($payment_status_val['value'] == $payment_log_val['bookingpress_payment_status']){
								$bookingpress_payment_status_label = $payment_status_val['text'];
								break;
							}
						}
						$payment_gateway_label_temp = $payment_gateway;
						if(!empty($payment_gateway) && ($payment_gateway == 'on-site' || $payment_gateway == 'on site' || $payment_gateway == 'On Site') ) {
							$payment_gateway = $BookingPress->bookingpress_get_customize_settings('locally_text','booking_form');
						} elseif(!empty($payment_gateway) && $payment_gateway != 'manual') {
							$payment_gateway = $BookingPress->bookingpress_get_customize_settings($payment_gateway.'_text','booking_form');
							if(empty($payment_gateway)) {
								$payment_gateway = $payment_gateway_label_temp;
							}
						} 

						$bookingpress_payment_status = $payment_log_val['bookingpress_payment_status'];
						$payment             = array(
							'payment_log_id'          => $payment_log_val['bookingpress_payment_log_id'],
							'payment_date'            => date_i18n($bookingpress_default_date_format, strtotime($payment_date)),
							'payment_customer'        => stripslashes_deep($bookingpress_customer),
							'payment_service'         => stripslashes_deep($service_name),
							'appointment_date'        => $bookingpress_appointment_date,
							'payment_gateway'         => esc_html($payment_gateway),
							'payment_numberic_amount' => floatval($payment_log_val['bookingpress_payment_amount']),
							'payment_amount'          => $payment_amount,
							'appointment_status'      => !empty($appointmentData['bookingpress_appointment_status']) ? $appointmentData['bookingpress_appointment_status'] : '',
							'payment_status'          => $bookingpress_payment_status,
							'payment_status_label'    => $bookingpress_payment_status_label,
							'appointment_start_time'  => date($bookingpress_default_time_format, strtotime($payment_log_val['bookingpress_appointment_start_time'])),
							'appointment_end_time'    => date($bookingpress_default_time_format, strtotime($payment_log_val['bookingpress_appointment_end_time'])),
							'transaction_id'          => !empty($payment_log_val['bookingpress_transaction_id']) ? $payment_log_val['bookingpress_transaction_id'] : '-',
							'customer_firstname'      => stripslashes_deep($payment_log_val['bookingpress_customer_firstname']),
							'customer_lastname'       => stripslashes_deep($payment_log_val['bookingpress_customer_lastname']),
							'customer_email'          => $payment_log_val['bookingpress_customer_email'],
							'customer_avatar'         => $bookingpress_avatar_url,
							'change_status_loader'    => 0,
							'sort_payment_appointment_date' => $payment_log_val['bookingpress_appointment_date'],
							'sort_payment_date'       => $payment_log_val['bookingpress_payment_date_time'],
						);
						
						$payment             = apply_filters('bookingpress_payment_add_view_field', $payment, $payment_log_val);
						$payment_logs_staff_data[] = $payment;
					}

					
					foreach($payment_logs_staff_data as $k => $v){
						$payment_log_id = $v['payment_log_id'];
	
						$payment_log_details = $this->bookingpress_calculate_payment_details($payment_log_id, $bookingpress_staffmember_id );
	
						$payment_logs_staff_data[$k]['payment_gateway_label'] = $payment_log_details['selected_gateway_label'];
						$payment_logs_staff_data[$k]['is_cart'] = $payment_log_details['is_cart'];
						$payment_logs_staff_data[$k]['order_id'] = $payment_log_details['order_id'];
						
						if($payment_log_details['is_cart'] == '1'){
							$payment_logs_staff_data[$k]['staff_member_name'] = ' - ';
							$payment_logs_staff_data[$k]['payment_service'] = ' - ';
							$payment_logs_staff_data[$k]['appointment_date'] = ' - ';
						}
	
						$payment_logs_staff_data[$k]['price_display_setting'] = $payment_log_details['price_display_setting'];
						$payment_logs_staff_data[$k]['display_tax_order_summary'] = $payment_log_details['display_tax_amount_in_order_summary'];
						$payment_logs_staff_data[$k]['included_tax_label'] = $payment_log_details['included_tax_label'];
	
						//Returns tax amount
						$payment_logs_staff_data[$k]['tax_amount'] = $payment_log_details['tax_amount'];
						$payment_logs_staff_data[$k]['tax_amount_with_currency'] = $payment_log_details['tax_amount_with_currency'];
	
						//Returns coupon details
						$payment_logs_staff_data[$k]['coupon_discount_amount'] = $payment_log_details['coupon_discount_amount'];
						$payment_logs_staff_data[$k]['coupon_discount_amount_with_currency'] = $payment_log_details['coupon_discount_amount_with_currency'];
						$payment_logs_staff_data[$k]['applied_coupon_code'] = $payment_log_details['applied_coupon_code'];
	
	
						$bookingpress_is_deposit_enable = $payment_log_details['is_deposit_enable'];
						$bookingpress_subtotal_amount = $payment_log_details['subtotal_amount'];
	
						if(!empty($payment_log_details)){
							$payment_logs_staff_data[$k]['appointment_details'] = !empty($payment_log_details['appointment_details']) ? $payment_log_details['appointment_details'] : array();
							$payment_logs_staff_data[$k]['is_deposit_enable'] = $bookingpress_is_deposit_enable;
						}
	
						$payment_logs_staff_data[$k]['deposit_amount'] = $payment_log_details['deposit_amount'];
						$payment_logs_staff_data[$k]['deposit_amount_with_currency'] = $payment_log_details['deposit_amount_with_currency'];
	
						$payment_logs_staff_data[$k]['due_amount'] = $payment_log_details['due_amount'];
						$payment_logs_staff_data[$k]['due_amount_with_currency'] = $payment_log_details['due_amount_with_currency'];
	
						$payment_logs_staff_data[$k]['subtotal_amount'] = $payment_log_details['subtotal_amount'];
						$payment_logs_staff_data[$k]['subtotal_amount_with_currency'] = $payment_log_details['subtotal_amount_with_currency'];
	
						$payment_logs_staff_data[$k]['payment_amount'] = $payment_log_details['payment_amount'];
						$payment_logs_staff_data[$k]['payment_numberic_amount'] = floatval($payment_log_details['payment_numberic_amount']);
	
						$payment_logs_staff_data[$k]['total_amount'] = $payment_log_details['total_amount'];
						$payment_logs_staff_data[$k]['total_amount_with_currency'] = $payment_log_details['total_amount_with_currency'];
	
						$payment_logs_staff_data[$k]['refund_amount'] = $payment_log_details['refund_amount'];
						$payment_logs_staff_data[$k]['refund_amount_with_currency'] = $payment_log_details['refund_amount_with_currency'];
	
						if(isset($payment_logs_staff_data[$k]['is_cart']) && $payment_logs_staff_data[$k]['is_cart'] == 0 && isset($payment_logs_staff_data[$k]['appointment_details'][0]['bookingpress_appointment_booking_id'])) {
							$refund_data = $bookingpress_pro_appointment->bookingpress_allow_to_refund($payment_logs_staff_data[$k]['appointment_details'][0],0,0);
							$payment_logs_staff_data[$k]['payment_refund_status'] = $refund_data['allow_refund'];
							$payment_logs_staff_data[$k]['payment_partial_refund'] = $refund_data['allow_partial'];
							$payment_logs_staff_data[$k]['appointment_id'] = $payment_logs_staff_data[$k]['appointment_details'][0]['bookingpress_appointment_booking_id'];
							$bookingpress_service_currecy = $payment_logs_staff_data[$k]['appointment_details'][0]['bookingpress_service_currency'];
							$payment_logs_staff_data[$k]['appointment_currency_symbol'] = $BookingPress->bookingpress_get_currency_symbol($bookingpress_service_currecy);
						}
						
					}
					$payment_logs_data = array_merge( $payment_logs_data, $payment_logs_staff_data );
				}
			}

			return $payment_logs_data;
		}

		function bookingpress_modify_modal_payment_log_details_func( $payment_log_data ) {
			global $wpdb, $bookingpress_coupons, $tbl_bookingpress_coupons, $BookingPress;
			if ( ! empty( $payment_log_data ) && is_array( $payment_log_data ) ) {
				$payment_log_id = ! empty( $payment_log_data['bookingpress_payment_log_id'] ) ? intval( $payment_log_data['bookingpress_payment_log_id'] ) : 0;
				if ( ! empty( $payment_log_id ) ) {

					$currency_name   = $payment_log_data['bookingpress_payment_currency'];
					$currency_symbol = $BookingPress->bookingpress_get_currency_symbol( $currency_name );

					$bookingpress_coupon_details         = ! empty( $payment_log_data['bookingpress_coupon_details'] ) ? json_decode( $payment_log_data['bookingpress_coupon_details'], ARRAY_A ) : array();
					$bookingpress_applied_coupon_code    = ! empty( $bookingpress_coupon_details['bookingpress_coupon_code'] ) ? $bookingpress_coupon_details['bookingpress_coupon_code'] : '';
					$bookingpress_coupon_discount_amount = ! empty( $bookingpress_coupon_details['bookingpress_coupon_discount'] ) ? $bookingpress_coupon_details['bookingpress_coupon_discount'] : '';
					if ( ! empty( $bookingpress_applied_coupon_code ) ) {
						if ( $bookingpress_coupon_details['bookingpress_coupon_discount_type'] == 'Percentage' ) {
							$bookingpress_coupon_discount_amount = $bookingpress_coupon_discount_amount . '%';
						} else {
							$bookingpress_coupon_discount_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_coupon_discount_amount, $currency_symbol );
						}
					}
					$bookingpress_tax_percentage = ! empty( $payment_log_data['bookingpress_tax_percentage'] ) ? sanitize_text_field( $payment_log_data['bookingpress_tax_percentage'] ) : '';
					$bookingpress_tax_amount     = ! empty( $payment_log_data['bookingpress_tax_amount'] ) ? sanitize_text_field( $payment_log_data['bookingpress_tax_amount'] ) : '';
					if ( ! empty( $bookingpress_tax_percentage ) ) {
						$bookingpress_tax_percentage = $bookingpress_tax_percentage . '%';
					}
					if ( ! empty( $bookingpress_tax_amount ) ) {
						$bookingpress_tax_amount = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $bookingpress_tax_amount, $currency_symbol );
					}
					$payment_log_data['bookingpress_tax_precentage']         = $bookingpress_tax_percentage;
					$payment_log_data['bookingpress_tax_amount']             = $bookingpress_tax_amount;
					$payment_log_data['bookingpress_applied_coupon_code']    = $bookingpress_applied_coupon_code;
					$payment_log_data['bookingpress_coupon_discount_amount'] = $bookingpress_coupon_discount_amount;
				}
			}
			return $payment_log_data;
		}

		function bookingpress_modify_payment_data_fields_func( $bookingpress_payment_vue_data_fields ) {
			global $wpdb, $BookingPress, $bookingpress_pro_staff_members,$BookingPressPro,$bookingpress_global_options, $bookingpress_roles;

			$bookingpress_payment_vue_data_fields['bulk_options'] = array(
				array(
					'value'        => 'bulk_action',
					'label'        => __( 'Bulk Action', 'bookingpress-appointment-booking' ),
					'bulk_actions' => array(
						array(
							'value' => 'bulk_action',
							'text' => __('Bulk Action', 'bookingpress-appointment-booking'),
						),
						array(
							'value' => 'delete',
							'text' => __( 'Delete', 'bookingpress-appointment-booking' ),
						),
					),
				),
				array(
					'value'        => 'change_status',
					'label'        => __( 'Change Status', 'bookingpress-appointment-booking' ),
					'bulk_actions' => $bookingpress_payment_vue_data_fields['payment_status_data'],
				),
			);

			$bookingpress_payment_vue_data_fields['search_status_data'] = $bookingpress_payment_vue_data_fields['payment_status_data'];

			$bookingpress_payment_vue_data_fields['ExportPayment']             = false;
			$bookingpress_payment_vue_data_fields['is_export_button_loader']   = '0';
			$bookingpress_payment_vue_data_fields['is_export_button_disabled'] = false;
			$bookingpress_payment_vue_data_fields['is_mask_display']           = false;
			$bookingpress_payment_vue_data_fields['export_payment_top_pos']    = '210px';
			$bookingpress_payment_vue_data_fields['export_payment_right_pos']  = '80px';
			$bookingpress_payment_vue_data_fields['export_payment_left_pos']   = 'auto';
			$bookingpress_payment_vue_data_fields['is_refund_btn_disabled'] = false;
			$bookingpress_payment_vue_data_fields['is_display_refund_loader'] = '0';
			$bookingpress_payment_vue_data_fields['refund_confirm_modal'] = false;
			$bookingpress_payment_vue_data_fields['refund_confirm_form']['refund_type'] = 'full';
			$bookingpress_payment_vue_data_fields['refund_confirm_form']['refund_reason'] = '';
			$bookingpress_payment_vue_data_fields['refund_confirm_form']['refund_appointment_status'] = '3';
			$bookingpress_payment_vue_data_fields['refund_confirm_form']['refund_amount'] = '';
			$bookingpress_payment_vue_data_fields['refund_confirm_form']['allow_partial_refund'] = 0;
			$bookingpress_payment_vue_data_fields['rules_refund_confirm_form'] = array();

			$bookingpress_payment_vue_data_fields['adjustAmountDialogVisible'] = false; 
			$bookingpress_payment_vue_data_fields['adjustAmountForm']['details'] = ''; 
			$bookingpress_payment_vue_data_fields['adjustAmountForm']['amount'] = ''; 
			$bookingpress_payment_vue_data_fields['adjustAmountForm']['adjust_appointment_id'] = 0; 
			$bookingpress_payment_vue_data_fields['adjustAmountForm']['adjust_payment_id'] = 0; 
			$bookingpress_payment_vue_data_fields['adjustAmountForm']['currency_symbol'] = '$'; 
			$bookingpress_payment_vue_data_fields['is_display_adjustment_loader'] = '0';
			$bookingpress_payment_vue_data_fields['is_adjustment_btn_disabled'] = false;
			
			$bookingpress_payment_vue_data_fields['rules_payment_adjust_form']['adjust_amount'] = array(
                array(
                    'required' => true,
                    'message'  => esc_html__('Please Enter Amount', 'bookingpress-appointment-booking'),
                    'trigger'  => 'blur',
                ),
            );

			$bookingpress_payment_vue_data_fields['payment_export_field_list'] = array(
				array(
					'name' => 'customer_first_name',
					'text' => __( 'Customer First Name', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'customer_last_name',
					'text' => __( 'Customer Last Name', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'customer_email',
					'text' => __( 'Customer Email', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'service',
					'text' => __( 'Service', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'amount',
					'text' => __( 'Amount', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'transaction_id',
					'text' => __( 'Transaction Id', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'payer_email',
					'text' => __( 'Payer Email', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'payment_method',
					'text' => __( 'Payment Method', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'payment_date',
					'text' => __( 'Payment Date', 'bookingpress-appointment-booking' ),
				),
				array(
					'name' => 'payment_status',
					'text' => __( 'Payment Status', 'bookingpress-appointment-booking' ),
				),

			);

			$bookingpress_payment_vue_data_fields['export_checked_field'] = array(
				'customer_first_name',
				'customer_last_name',
				'customer_email',
				'service',
				'amount',
				'transaction_id',
				'payer_email',
				'payment_method',
				'payment_date',
				'payment_status',
			);

			$bookingpress_payment_vue_data_fields['is_staffmember_activated'] = $bookingpress_pro_staff_members->bookingpress_check_staffmember_module_activation();
			$bookingpress_payment_vue_data_fields['is_display_save_loader']    = '0';
			$bookingpress_payment_vue_data_fields['is_disabled']               = false;

			$bookingpress_customers_details                                 = $BookingPress->bookingpress_get_appointment_customer_list();
			$bookingpress_payment_vue_data_fields['bookingpress_customers'] = $bookingpress_customers_details;

			$bookingpress_services = $BookingPress->get_bookingpress_service_data_group_with_category();
			$bookingpress_payment_vue_data_fields['bookingpress_services'] = $bookingpress_services;

			$bookingpress_payment_deafult_currency = $BookingPress->bookingpress_get_settings( 'payment_default_currency', 'payment_setting' );
			$bookingpress_payment_deafult_currency = $BookingPress->bookingpress_get_currency_symbol( $bookingpress_payment_deafult_currency );

			$bookingpress_payment_vue_data_fields['payment_currency'] = $bookingpress_payment_deafult_currency;						

			$bookingpress_payment_vue_data_fields['is_send_button_loader'] = '0';
			$bookingpress_payment_vue_data_fields['is_send_button_disabled'] = false;

			$bookingpress_payment_vue_data_fields['bookingpress_complete_payment_link_send_options'] = array();

			$bookingpress_edit_payment = $bookingpress_delete_payment = $bookingpress_export_payment = 0;
			if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_payments' ) ) {
				$bookingpress_edit_payment = 1;
			}	
			if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_delete_payments' ) ) {
				$bookingpress_delete_payment = 1;
			}	
			if ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_export_payments' ) ) {
				$bookingpress_export_payment = 1;
			}
			$bookingpress_payment_vue_data_fields['bookingpress_edit_payment'] = $bookingpress_edit_payment;
			$bookingpress_payment_vue_data_fields['bookingpress_delete_payment'] = $bookingpress_delete_payment;
			$bookingpress_payment_vue_data_fields['bookingpress_export_payment'] = $bookingpress_export_payment;

			/* check refund cap  */
			$bookingpress_staff_refund_cap_check = $BookingPressPro->bookingpress_check_capability( 'bookingpress_staff_refund_payments' );
			$bookingpress_payment_vue_data_fields['bpa_staff_refund_cap'] = $bookingpress_staff_refund_cap_check;

			$have_refund_access = false;
			if(!empty($bookingpress_roles)){
				$have_refund_access = $bookingpress_roles->get_logged_in_user_role_caps("bookingpress_staff_refund_payments");	
			}

			$bookingpress_check_user_role = $BookingPressPro->bookingpress_check_user_role( 'bookingpress-staffmember' );
			$bookingpress_payment_vue_data_fields['bpa_chk_staff_role'] = $bookingpress_check_user_role;
			
			if(!empty($have_refund_access)){
				$bookingpress_payment_vue_data_fields['bpa_chk_staff_role'] = true;
			}
			
			$bookingpress_global_data = $bookingpress_global_options->bookingpress_global_options();
			$bookingpress_payment_vue_data_fields['bookingpress_payment_appointment_status'] = $bookingpress_global_data['appointment_status'];


			$bookingpress_currency_separator = $BookingPress->bookingpress_get_settings('price_separator', 'payment_setting');
			$bookingpress_payment_vue_data_fields['bookingpress_currency_separator'] = $bookingpress_currency_separator;			
			$bookingpress_decimal_points = $BookingPress->bookingpress_get_settings('price_number_of_decimals', 'payment_setting');
			$bookingpress_decimal_points = intval($bookingpress_decimal_points);
			$bookingpress_payment_vue_data_fields['bookingpress_decimal_points'] = $bookingpress_decimal_points;

            $bookingpress_currency_name = $BookingPress->bookingpress_get_settings('payment_default_currency', 'payment_setting');
            $bookingpress_payment_vue_data_fields['bookingpress_currency_name'] = $bookingpress_currency_name;			
            $bookingpress_payment_vue_data_fields['bookingpress_currency_symbol'] = $BookingPress->bookingpress_get_currency_symbol($bookingpress_currency_name);

			$bookingpress_price_symbol_position = $BookingPress->bookingpress_get_settings('price_symbol_position', 'payment_setting');
            $bookingpress_payment_vue_data_fields['bookingpress_currency_symbol_position'] = $bookingpress_price_symbol_position;

			return $bookingpress_payment_vue_data_fields;
		}

		function bookingpress_modify_payment_file_path_func( $bookingpress_payment_view_path ) {

			$bookingpress_payment_view_path = BOOKINGPRESS_PRO_VIEWS_DIR . '/payment/manage_payment.php';
			return $bookingpress_payment_view_path;
		}

		function bookingpress_payment_dynamic_bulk_action_func() {
			?>	
				const vm2 =  this;		
				var payment_logs_bulk_action = {
					action:'bookingpress_pro_bulk_payment_logs_action',
					payment_ids: this.multipleSelection,
					bulk_action: this.bulk_action,
					_wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>'
				}
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( payment_logs_bulk_action ) )
				.then(function(response){					
					vm2.$notify({
						title: response.data.title,
						message: response.data.msg,
						type: response.data.variant,
						customClass: response.data.variant+'_notification',
					});
					vm2.loadPayments()
					vm2.multipleSelection = [];
					vm2.totalItems = vm2.items.length
				}).catch(function(error){
					console.log(error);
					vm2.$notify({
						title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
						message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
						type: 'error',
						customClass: 'error_notification',
					});
				});			
			<?php
		}

		function bookingpress_pro_bulk_payment_logs_action_func() {
			global $wpdb,$BookingPress,$tbl_bookingpress_payment_logs;
			$response              = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'bulk_payment_actions', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			$bookingpress_payment_status = ! empty( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : ''; // phpcs:ignore

			if ( ! empty( $bookingpress_payment_status ) && in_array( $bookingpress_payment_status, array( '2', '1','3','4','5') ) ) {
				$payment_ids = ! empty( $_POST['payment_ids'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['payment_ids'] ) : array(); // phpcs:ignore
				if ( ! empty( $payment_ids ) ) {
					foreach ( $payment_ids as $payment_key => $payment_val ) {
						if ( is_array( $payment_val ) ) {
							$payment_val = $payment_val['payment_log_id'];
						}
						if ( ! empty( $payment_val ) ) {
							$wpdb->update( $tbl_bookingpress_payment_logs, array( 'bookingpress_payment_status' => $bookingpress_payment_status ), array( 'bookingpress_payment_log_id' => $payment_val ) );
						}
					}
					$response['variant'] = 'success';
					$response['title']   = esc_html__( 'Success', 'bookingpress-appointment-booking' );
					$response['msg']     = esc_html__( 'Payment status has been change successfully.', 'bookingpress-appointment-booking' );
				}
			}
			echo wp_json_encode( $response );
			exit;
		}

		function bookingpress_export_payment_data_func() {
			global $wpdb, $tbl_bookingpress_payment_logs, $BookingPress, $tbl_bookingpress_customers, $tbl_bookingpress_appointment_bookings,$bookingpress_global_options,$bookingpress_pro_appointment;

			$response = array();
			$bpa_check_authorization = $this->bpa_check_authentication( 'export_payment_details', true, 'bpa_wp_nonce' );
            
            if( preg_match( '/error/', $bpa_check_authorization ) ){
                $bpa_auth_error = explode( '^|^', $bpa_check_authorization );
                $bpa_error_msg = !empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'bookingpress-appointment-booking');

                $response['variant'] = 'error';
                $response['title'] = esc_html__( 'Error', 'bookingpress-appointment-booking');
                $response['msg'] = $bpa_error_msg;

                wp_send_json( $response );
                die;
            }

			$bookingpress_export_field = ! empty( $_REQUEST['export_field'] ) ? array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_REQUEST['export_field'] ) : array();// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --Reason $_POST contains mixed array and will be sanitized using 'appointment_sanatize_field' function

			$bookingpress_search_query = ' WHERE 1=1';
			if ( ! empty( $_POST['search_data'] ) ) { // phpcs:ignore
				$search_data = array_map( array( $BookingPress, 'appointment_sanatize_field' ), $_POST['search_data'] ); // phpcs:ignore
				if ( ! empty( $search_data['search_range'] ) ) {
					$range_start_date           = date( 'Y-m-d', strtotime( $search_data['search_range'][0] ) ) . ' 00:00:00';
					$range_end_date             = date( 'Y-m-d', strtotime( $search_data['search_range'][1] ) ) . ' 23:59:59';
					$bookingpress_search_query .= " AND (bookingpress_payment_date_time BETWEEN '{$range_start_date}' AND '{$range_end_date}')";
				}
				if ( ! empty( $search_data['search_customer'] ) ) {
					$customer_id                = $search_data['search_customer'];
					$customer_id                = implode( ',', $customer_id );
					$bookingpress_search_query .= " AND (bookingpress_customer_id IN ({$customer_id}))";
				}
				if ( ! empty( $search_data['search_service'] ) ) {
					$service_id                 = $search_data['search_service'];
					$service_id                 = implode( ',', $service_id );
					$bookingpress_search_query .= " AND (bookingpress_service_id IN ({$service_id}))";
				}
				if ( ! empty( $search_data['search_status'] ) && $search_data['search_status'] != 'all' ) {
					$search_status              = $search_data['search_status'];
					$bookingpress_search_query .= " AND (bookingpress_payment_status = '{$search_status}')";
				}

				if ( ! empty( $search_data['search_staff_member'] ) ) {
					$bookingpress_search_name            = $search_data['search_staff_member'];
					$bookingpress_search_staff_member_id = implode( ',', $bookingpress_search_name );
					$bookingpress_search_query          .= " AND (bookingpress_staff_member_id IN ({$bookingpress_search_staff_member_id}))";
				}
			}
			$bookingpress_search_query = apply_filters( 'bookingpress_export_payment_data_filter', $bookingpress_search_query );

			$get_payment_logs  = $wpdb->get_results( 'SELECT * FROM ' . $tbl_bookingpress_payment_logs . ' ' . $bookingpress_search_query . ' ORDER BY bookingpress_payment_log_id DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_payment_logs is a table name. false alarm
			$payment_logs_data = array();
			if ( ! empty( $get_payment_logs ) && ! empty( $bookingpress_export_field ) ) {

				$bookingpress_global_options_arr = $bookingpress_global_options->bookingpress_global_options();
				$bookingpress_date_format        = $bookingpress_global_options_arr['wp_default_date_format'] . ' ' . $bookingpress_global_options_arr['wp_default_time_format'];

				$bookingpress_payment_status_arr = $bookingpress_global_options_arr['payment_status'];

				foreach ( $get_payment_logs as $payment_log_key => $payment_log_val ) {
					$payment = array();

					if ( in_array( 'customer_first_name', $bookingpress_export_field ) ) {
						$payment['Customer First Name'] = ! empty( $payment_log_val['bookingpress_customer_firstname'] ) ? '"' . sanitize_text_field( $payment_log_val['bookingpress_customer_firstname'] ) . '"' : '-';
					}
					if ( in_array( 'customer_last_name', $bookingpress_export_field ) ) {
						$payment['Customer Last Name'] = ! empty( $payment_log_val['bookingpress_customer_lastname'] ) ? '"' . sanitize_text_field( $payment_log_val['bookingpress_customer_lastname'] ) . '"' : '-';
					}
					if ( in_array( 'customer_email', $bookingpress_export_field ) ) {
						$payment['Customer Email'] = ! empty( $payment_log_val['bookingpress_customer_email'] ) ? '"' . sanitize_email( $payment_log_val['bookingpress_customer_email'] ) . '"' : '-';
					}
					if ( in_array( 'service', $bookingpress_export_field ) ) {
						$payment['Service'] = ! empty( $payment_log_val['bookingpress_service_name'] ) ? '"' . sanitize_text_field( $payment_log_val['bookingpress_service_name'] ) . '"' : '-';
					}
					if ( in_array( 'amount', $bookingpress_export_field ) ) {
						$currency_name     = $payment_log_val['bookingpress_payment_currency'];
						$currency_symbol   = $BookingPress->bookingpress_get_currency_symbol( $currency_name );
						//$payment_amount    = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $payment_log_val['bookingpress_payment_amount'], $currency_symbol );
						
						$payment_amount    = $BookingPress->bookingpress_price_formatter_with_currency_symbol( $payment_log_val['bookingpress_paid_amount'], $currency_symbol );						
						$bookingpress_deposit_payment_details = (isset($payment_log_val['bookingpress_deposit_payment_details']))?$payment_log_val['bookingpress_deposit_payment_details']:'';
						
						$bookingpress_due_amount = (float)(isset($payment_log_val['bookingpress_due_amount']))?$payment_log_val['bookingpress_due_amount']:0;
						$bookingpress_deposit_amount = (float)(isset($payment_log_val['bookingpress_deposit_amount']))?$payment_log_val['bookingpress_deposit_amount']:0;

						if(($bookingpress_due_amount > 0 && $bookingpress_deposit_amount > 0)){

							$bookingpress_payment_status = (isset($payment_log_val['bookingpress_payment_status']))?$payment_log_val['bookingpress_payment_status']:'';
							if($bookingpress_payment_status == 1){
								
								$bookingpress_appointment_booking_ref = (isset($payment_log_val['bookingpress_appointment_booking_ref']))?$payment_log_val['bookingpress_appointment_booking_ref']:0;
								$bookingpress_is_cart = (isset($payment_log_val['bookingpress_is_cart']))?$payment_log_val['bookingpress_is_cart']:0;
								$bookingpress_order_id = (isset($payment_log_val['bookingpress_order_id']))?$payment_log_val['bookingpress_order_id']:0;
								if($bookingpress_order_id != 0){

									$bookingpress_appointment_booking_ref = $wpdb->get_var($wpdb->prepare("SELECT bookingpress_appointment_booking_id FROM {$tbl_bookingpress_appointment_bookings} WHERE bookingpress_order_id = %d", $bookingpress_order_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared --Reason: $tbl_bookingpress_appointment_bookings is a table name. false alarm			

								}
								if($bookingpress_appointment_booking_ref != 0){
									$bookingpress_payment_log_id = (isset($payment_log_val['bookingpress_payment_log_id']))?$payment_log_val['bookingpress_payment_log_id']:0;
									$bookingpress_appointment_details = $bookingpress_pro_appointment->bookingpress_calculated_appointment_details($bookingpress_appointment_booking_ref, $bookingpress_payment_log_id);

									$final_total_amount_with_currency = (isset($bookingpress_appointment_details['final_total_amount_with_currency']))?$bookingpress_appointment_details['final_total_amount_with_currency']:'';
									if(!empty($final_total_amount_with_currency)){
										$payment_amount  = $final_total_amount_with_currency;
									}

								}

							}

						}
						

						$payment['Amount'] = ! empty( $payment_amount ) ? '"' . sanitize_text_field( $payment_amount ) . '"' : '-';
					}
					if ( in_array( 'transaction_id', $bookingpress_export_field ) ) {
						$payment['Transaction ID'] = ! empty( $payment_log_val['bookingpress_transaction_id'] ) ? '"' . sanitize_text_field( $payment_log_val['bookingpress_transaction_id'] ) . '"' : '-';
					}
					if ( in_array( 'payer_email', $bookingpress_export_field ) ) {
						$payment['Payer Email'] = ! empty( $payment_log_val['bookingpress_payer_email'] ) ? '"' . sanitize_email( $payment_log_val['bookingpress_payer_email'] ) . '"' : '-';
					}
					if ( in_array( 'payment_method', $bookingpress_export_field ) ) {
						$payment['Payment Method'] = ! empty( $payment_log_val['bookingpress_payment_gateway'] ) ? '"' . sanitize_text_field( $payment_log_val['bookingpress_payment_gateway'] ) . '"' : '-';
					}
					if ( in_array( 'payment_date', $bookingpress_export_field ) ) {
						$payment_date            = date( $bookingpress_date_format, strtotime( $payment_log_val['bookingpress_payment_date_time'] ) );
						$payment['Payment Date'] = ! empty( $payment_date ) ? '"' . sanitize_text_field( $payment_date ) . '"' : '-';
					}
					if ( in_array( 'payment_status', $bookingpress_export_field ) ) {
						$bookingpress_payment_status = !empty($payment_log_val['bookingpress_payment_status']) ? sanitize_text_field($payment_log_val['bookingpress_payment_status']) : '-';

						$bookingpress_payment_status_label = "";
						foreach($bookingpress_payment_status_arr as $bookingpress_payment_status_key => $bookingpress_payment_status_vals){
							if($bookingpress_payment_status_vals['value'] == $bookingpress_payment_status){
								$bookingpress_payment_status_label = $bookingpress_payment_status_vals['text'];
								break;
							}
						}
						
						$payment['Payment Status'] = ! empty( $bookingpress_payment_status_label ) ? '"' . sanitize_text_field( $bookingpress_payment_status_label ) . '"' : '-';
					}

					$payment = apply_filters('bookingpress_export_payment_details', $payment, $bookingpress_export_field, $payment_log_val );

					$payment_logs_data[] = $payment;
				}
			} else {
				$payment_logs_data = array();
			}

			$data = array();
			if ( ! empty( $payment_logs_data ) ) {
				array_push( $data, array_keys( $payment_logs_data[0] ) );
				foreach ( $payment_logs_data as $key => $value ) {
					array_push( $data, array_values( $value ) );
				}
			}
			$response['status'] = 'success';
			$response['data']   = $data;
			echo wp_json_encode( $response );
			exit;
		}

		function bookingpress_dynamic_add_onload_payment_methods_func(){
			?>
				
			<?php
		}

		function bookingpress_payment_add_dynamic_vue_methods_func() {
			global $BookingPress, $bookingpress_notification_duration;
			$bookingpress_export_delimeter = $BookingPress->bookingpress_get_settings( 'bookingpress_export_delimeter', 'general_setting' );
			?>
			Bookingpress_export_payment_data( currentElement ){
				const vm = this;
				vm.ExportPayment = true;

				if( typeof vm.bpa_adjust_popup_position != 'undefined' ){
					vm.bpa_adjust_popup_position( currentElement, 'div#payment_export_model .el-dialog.bpa-dialog--export-payments', '', '', vm.current_screen_size);
				}
			},			
			close_export_payment_model(){
				const vm = this;
				vm.ExportPayment = false;
				vm.export_checked_field = ['customer_first_name','customer_last_name'
				,'customer_email','service','amount','transaction_id','payer_email','payment_method','payment_date','payment_status'];
			},
			bookingpress_export_payments(){
				const vm = this;
				vm.is_export_button_loader = '1';						
				vm.is_export_button_disabled = true;
				var payment_export_data = {
					action:'bookingpress_export_payment_data',
					export_field: vm.export_checked_field,
					search_data : vm.search_data,
					_wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>'
				}								
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( payment_export_data ) )
				.then(function(response) {																
					vm.is_export_button_loader = '0';						
					vm.is_export_button_disabled = false;
					vm.close_export_payment_model();								

					if(response.data.data != 'undefined') {
						var export_data;
						var csv = ''; 
						if(response.data.data != '') {
							export_data = response.data.data;						
							export_data.forEach(function(row){					    				
								csv += row.join('<?php echo esc_html( $bookingpress_export_delimeter ); ?>');
								   csv += "\n";
							});	 
						}		
						const anchor = document.createElement('a');
						anchor.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);	
						anchor.target = '_blank';
						anchor.download = 'Bookingpress-export-payment.csv';
						anchor.click();
					}					
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
			bpa_send_complete_payment_link(payment_log_id){
				const vm = this
				vm.is_send_button_loader = '1';
				vm.is_send_button_disabled = true;
				var postdata = {
					action: 'bookingpress_send_complete_payment_link',
					payment_log_id: payment_log_id,
					selected_options: vm.bookingpress_complete_payment_link_send_options,
					_wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>',
				};
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postdata ) )
				.then(function(response){
					vm.is_send_button_loader = '0';
					vm.is_send_button_disabled = false;
					vm.$notify({
						title: response.data.title,
						message: response.data.msg,
						type: response.data.variant,
						customClass: response.data.variant+'_notification',
						duration:<?php echo intval( $bookingpress_notification_duration ); ?>,
					});
					if(response.data.variant == "success"){
						document.body.click();
					}
				}).catch(function(error){
					vm.is_send_button_loader = '0';
					vm.is_send_button_disabled = false;
					console.log(error);
					vm.$notify({
						title: '<?php esc_html_e( 'Error', 'bookingpress-appointment-booking' ); ?>',
						message: '<?php esc_html_e( 'Something went wrong..', 'bookingpress-appointment-booking' ); ?>',
						type: 'error',
						customClass: 'error_notification',
						duration:<?php echo intval( $bookingpress_notification_duration ); ?>,
					});
				});
			},
			bookingpress_open_refund_model(currentElement,appointment_id,payment_id,currency_symbol,partial_refund) {
				const vm = this;				
				vm.reset_refund_confirm_model();				
				vm.refund_confirm_form.refund_currency = currency_symbol;
				vm.refund_confirm_form.allow_partial_refund = partial_refund;
				vm.refund_confirm_form.appointment_id = appointment_id;
				vm.refund_confirm_form.payment_id = payment_id;				
				var postData = { action:'bookingpress_get_refund_amount', bookingpress_appointment_id:appointment_id,bookingpress_payment_id :payment_id,_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' };
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postData ) )
				.then( function (response) {
					if(response.data.variant == "success"){
						vm.refund_confirm_form.refund_amount = response.data.refund_amount;
						vm.refund_confirm_form.default_refund_amount = response.data.default_refund_amount;
						if(typeof response.data.is_past_appointment != 'undefined' && response.data.is_past_appointment == 1) {
							vm.refund_confirm_form.refund_appointment_status = '5';
						}
					} else{											
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
						title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
						message: '<?php esc_html_e('Something went wrong..', 'bookingpress-appointment-booking'); ?>',
						type: 'error',
						customClass: 'error_notification',
						duration:<?php echo intval($bookingpress_notification_duration); ?>,
					});
				});

				vm.refund_confirm_modal = true;
				if( typeof vm.bpa_adjust_popup_position != 'undefined' ){
					vm.bpa_adjust_popup_position( currentElement, 'div#refund_confirm_process .el-dialog.bpa-dialog--refund-process');
				}
			},
			close_refund_confirm_model(){
				const vm = this;
				vm.reset_refund_confirm_model();
				vm.refund_confirm_modal = false;
			},
			reset_refund_confirm_model() {
				const vm = this
				vm.refund_confirm_form.refund_type = 'full';
				vm.refund_confirm_form.refund_amount = 0;
				vm.refund_confirm_form.refund_reason = '';
				vm.refund_confirm_form.appointment_id = '';
				vm.refund_confirm_form.payment_id = '';
				vm.refund_confirm_form.default_refund_amount = 0;
				vm.refund_confirm_form.allow_partial_refund = 0;
				vm.refund_confirm_form.refund_appointment_status = '3';
			},
			bookingpress_apply_for_refund(payment_id,appointment_id) {
				const vm = this
				vm.is_display_refund_loader = '1';
				vm.is_refund_btn_disabled = true;
				var is_error = false;
				var error_msg = false;
												
				if((vm.refund_confirm_form.refund_amount == '' || vm.refund_confirm_form.refund_amount == 0) && vm.refund_confirm_form.refund_type == 'partial') {
					error_msg =  '<?php esc_html_e('Refund amount should be more than zero', 'bookingpress-appointment-booking'); ?>';
					is_error = true
				}
				if(parseInt(vm.refund_confirm_form.refund_amount) > parseInt(vm.refund_confirm_form.default_refund_amount) && vm.refund_confirm_form.refund_type == 'partial' ) {					
					error_msg =  '<?php esc_html_e('Refund amount should not be more than paid the amount.', 'bookingpress-appointment-booking'); ?>';
					is_error = true
				} 
				if( is_error == true) {
					vm.$notify({
						title: '<?php esc_html_e('Error', 'bookingpress-appointment-booking'); ?>',
						message: error_msg,
						type: 'error',
						customClass: 'error_notification',
						duration:<?php echo intval($bookingpress_notification_duration); ?>,
					});
					vm.is_display_refund_loader = '0';
					vm.is_refund_btn_disabled = false;
					return false;
				}
				var postData = { action:'bookingpress_apply_for_refund',bookingpress_refund_data:vm.refund_confirm_form,_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' };
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postData ) )
				.then( function (response) {
					if(response.data.variant == "success"){
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
						vm.close_refund_confirm_model();
						vm.loadPaymentWithoutLoader()
					} else{											
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					}
					vm.is_display_refund_loader = '0';
					vm.is_refund_btn_disabled = false;
					
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
			},
            isValidateZeroDecimal(evt){
                const vm = this                
                if (/[^0-9]+/.test(evt)){
                    vm.refund_confirm_form.refund_amount = evt.slice(0, -1);
                }
            },
			bpa_payment_adjustment_cancel(){
				const vm = this;   
				vm.adjustAmountDialogVisible = false;	
				vm.adjustAmountForm.details = "";			
				vm.adjustAmountForm.amount = "";			
				vm.adjustAmountForm.adjust_tax_amount = "";	
				vm.adjustAmountForm.adjust_appointment_id = "";	
				vm.adjustAmountForm.adjust_payment_id = "";	
			},
			bpa_apply_payment_adjustment(){
				const vm = this 
				vm.is_display_adjustment_loader = '1';
				vm.is_adjustment_btn_disabled = true;
				var postData = { action:'bookingpress_apply_for_manual_adjustment',bookingpress_payment_adjust_data:vm.adjustAmountForm,_wpnonce:'<?php echo esc_html(wp_create_nonce('bpa_wp_nonce')); ?>' };
				axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postData ) )
				.then( function (response) {
					if(response.data.variant == "success"){
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
						vm.bpa_payment_adjustment_cancel();
						vm.loadPaymentWithoutLoader()
					} else{											
						vm.$notify({
							title: response.data.title,
							message: response.data.msg,
							type: response.data.variant,
							customClass: response.data.variant+'_notification',
							duration:<?php echo intval($bookingpress_notification_duration); ?>,
						});
					}
					vm.is_display_adjustment_loader = '0';
					vm.is_adjustment_btn_disabled = false;
					
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
			},
			bookingpress_open_additional_amount_model(event, appointment_id, payment_id, index = 0){
				console.log("to test this ")
				const vm = this
				vm.adjustAmountDialogVisible = true;
				vm.adjustAmountForm.adjust_appointment_id = appointment_id;	
				vm.adjustAmountForm.adjust_payment_id = payment_id;								
				vm.adjustAmountForm.details = vm.items[index].bookingpress_payment_adjustment_reason;	
				vm.adjustAmountForm.amount = vm.items[index].bookingpress_payment_adjustment_amount;
				vm.adjustAmountForm.currency_symbol = vm.items[index].appointment_currency_symbol;
			},			
			isNumberValidate(evt) {
                const regex = /^(?!.*(,,|,\.|\.,|\.\.))[\d.,]+$/gm;
                let m;
                if((m = regex.exec(evt)) == null ) {
                    this.adjustAmountForm.amount = '';
                }
                var price_number_of_decimals = this.bookingpress_decimal_points;                
                if((evt != null && evt.indexOf(".")>-1 && (evt.split('.')[1].length > price_number_of_decimals))){
                    this.adjustAmountForm.amount = evt.slice(0, -1);
                }                
            },
            isValidateZeroDecimal(evt){
                const vm = this                
                if (/[^0-9]+/.test(evt)){
                    this.adjustAmountForm.amount = evt.slice(0, -1);
                }
            },
			<?php
			do_action('bookingpress_pro_add_dynamic_vue_methods');
		}

	}
}

global $bookingpress_pro_payment;
$bookingpress_pro_payment = new bookingpress_pro_payment();
