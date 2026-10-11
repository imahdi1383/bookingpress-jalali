<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('bookingpress_appointment_bookings_popup')  && class_exists('BookingPress_Core')) {
    class bookingpress_appointment_bookings_popup Extends BookingPress_Core{
        function __construct(){
            add_shortcode('bookingpress_form_popup', array( $this, 'bookingpress_front_popup_booking_form' ));
            add_action( 'admin_init', array($this, 'bookingpress_add_gutenbergblock_popup' ));
        }


        function bookingpress_add_gutenbergblock_popup() {
            register_block_type( BOOKINGPRESS_DIR_PRO . '/js/build/bookingform_popup' );             
        }

        function bookingpress_front_popup_booking_form($atts, $content, $tag){
            global $bookingpress_appointment_bookings ,$BookingPress ;

            $defaults = array(
                'service'  => 0,
                'category' => 0,
                'selected_service' => 0,
                'selected_staff' => 0,
            );
            $args = shortcode_atts($defaults, $atts, $tag);
            ob_start();
            //$bpa_btn_text = !empty($args['text']) ? $args['text'] : esc_html__('Book Now', 'bookingpress-appointment-booking');
            $bpa_btn_popup_txt = $BookingPress->bookingpress_get_customize_settings('bookingpress_popup_btn_title','booking_form');
            $bpa_btn_popup_txt = stripslashes_deep( $bpa_btn_popup_txt );
            $bpa_btn_text = isset( $bpa_btn_popup_txt ) ? $bpa_btn_popup_txt : esc_html__('Book Now', 'bookingpress-appointment-booking');
            $bookingpress_uniq_id = uniqid();

            if (isset($_GET['book_again']) && $_GET['book_again'] == '1') {
                $content .= '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var btn = document.querySelector(".bookingpress-booking-popup-btn");
                        if (btn) {btn.click();}
                    });
                </script>';
            }
            if (isset($_GET['s_id']) || isset($_GET['sm_id']) || isset($_GET['g_id'])) {
                $content .= '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var btn = document.querySelector(".bookingpress-booking-popup-btn");
                        if (btn) {btn.click();}
                    });
                </script>';
            }

            // Button to open popup
            $content .= '<div class="bookingpress-booking-popup-btn-wrapper">';
                $content .= '<button class="bookingpress-booking-popup-btn" onclick="bookingpressOpenPopup(\'' . $bookingpress_uniq_id . '\')">' . $bpa_btn_text . '</button>';
            $content .= '</div>';

            // Build shortcode with passed attributes
            $form_shortcode = '[bookingpress_form';
            foreach ($args as $key => $value) {
                if (!empty($value)) {
                    $form_shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
                }
            }
            $form_shortcode .= ']';

            // Popup modal container
            $content .= '<div class="bookingpress-popup-form-wrapper">';
                $content .= '<div id="bookingpress-popup-' . $bookingpress_uniq_id . '" class="bookingpress-popup-modal">';
                    $content .= '<div class="bookingpress-popup-content">';
                        $content .= '<span class="popup_close_btn bpa_popup_close_btn" onclick="document.body.classList.remove(\'bookingpress-popup-active\'); document.getElementById(\'bookingpress-popup-' . $bookingpress_uniq_id . '\').style.display=\'none\'"></span>';
                        $content .= do_shortcode($form_shortcode);
                    $content .= '</div>';
                $content .= '</div>';
            $content .= '</div>';
            
            $content .= '<style>
                .bookingpress-booking-popup-btn {
                    cursor: pointer;
                }
                .bookingpress-popup-form-wrapper .bookingpress-popup-modal {
                    display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999;
                }
                .bookingpress-popup-form-wrapper .bookingpress-popup-content {
                    position:relative; width:60%; margin:0; background:#fff; padding:50px 20px 20px 20px; border-radius:8px;top: 50%;left: 50%;transform: translate(-50%, -50%);
                }
                .bpa_popup_close_btn {
                    background: url(' . BOOKINGPRESS_PRO_IMAGES_URL . '/close_btn.png) no-repeat center center rgba(0, 0, 0, 0);
                    cursor: pointer; float: right; height: 28px; position: absolute; right: 20px; top: 15px; width: 28px; padding: 0;
                }
                .bookingpress-popup-active .bpa-fm--service__advance-options-popper { z-index: 99999 !important; }
                .bookingpress-popup-active .bpa-custom-recurring-datepicker {z-index: 99999 !important;}
                .bookingpress-popup-active .bpa-custom-duration-dropdown {z-index: 99999 !important;}
                .bookingpress-popup-active .bpa-front-staff-reviews_popover {z-index: 99999 !important;}
                .bpa_popup_close_btn {top: 11px !important;right: 13px;}
                ..bookingpress-popup-form-wrapper .bpa-front-tabs.--bpa-top .bpa-front-tabs--panel-body .bpa-front-dc--body{ max-height: 0; }
                @media ( max-width: 1368px ){
                    .bookingpress-popup-form-wrapper .bookingpress-popup-content { height: 80%; overflow: scroll; }
                }
                @media (min-width: 1200px) and (max-width: 1367px) {
                    .bookingpress-popup-form-wrapper .bpa-frontend-main-container{ padding: 0; }
                }
                @media (max-width: 1024px) {
                    .bookingpress-popup-form-wrapper .bookingpress-popup-content {padding: 0;padding-top: 50px; width: 90%;}
                }   
                @media (max-width: 570px) { 
                    .bookingpress-popup-form-wrapper .bookingpress-popup-content {width:auto;padding:10px; padding-top: 60px; border-radius: 0; height: calc(100% - 15%); }
                    .bookingpress-popup-form-wrapper .bookingpress-popup-modal { overflow-y: scroll; }
                    .bpa_popup_close_btn { top: 16px !important; right: 13px;} 
                    
                } 
            </style>';

            $content .= '<script>
                function bookingpressOpenPopup(uniqId) {

                    var thankyouScreen = document.getElementById("bpa-thankyou-screen-div");
                    var isVisibleThankYouContent = window.getComputedStyle(thankyouScreen).display !== "none";
                    
                    if (isVisibleThankYouContent) {
                        Object.assign( app, bpaInitialState() );
                        app.$mount();
                        app.bookingpress_load_booking_form();
                    }
                    
                    /*var bkp_wpnonce_pre = "";
                    var bkp_wpnonce_pre_fetch = document.getElementById("_wpnonce");
                    if(typeof bkp_wpnonce_pre_fetch=="undefined" || bkp_wpnonce_pre_fetch==null){
                        bkp_wpnonce_pre_fetch = bkp_wpnonce_pre;
                    }
                    else {
                        bkp_wpnonce_pre_fetch = bkp_wpnonce_pre_fetch.value;
                        var postData = { action: "bookingpress_generate_spam_captcha", _wpnonce:bkp_wpnonce_pre_fetch };
                            axios.post( appoint_ajax_obj.ajax_url, Qs.stringify( postData ) )
                        .then( function (response) {
                            if(response.variant=="error"){
                                var bkp_wpnonce_pre_fetch = document.getElementById("_wpnonce");
                                if(typeof bkp_wpnonce_pre_fetch!="undefined" && bkp_wpnonce_pre_fetch!=null && response.data.updated_nonce!=""){
                                    document.getElementById("_wpnonce").value = response.data.updated_nonce;
                                }
                            }
                        }.bind(this) )
                        .catch( function (error) {
                            console.log(error);
                        });
                    }*/

                    document.body.classList.add("bookingpress-popup-active");
                    var popup = document.getElementById("bookingpress-popup-" + uniqId);
                    if (popup) {
                        popup.style.display = "block";
                    }

                    let bpa_parent_container = document.querySelector( ".bpa-frontend-main-container" );
					if( null != bpa_parent_container && 1 > bpa_parent_container.offsetWidth ){
						bpa_parent_container.style.display = "block";
					}
                    var failedScreen = document.getElementById("bpa-failed-screen-div");
                    var thankyouScreen = document.getElementById("bpa-thankyou-screen-div");
                    if (failedScreen) {
                        failedScreen.innerHTML = "";
                    }
                    if (thankyouScreen && isVisibleThankYouContent) {
                        thankyouScreen.innerHTML = "";
                    }
                }
            </script>';
            
            return do_shortcode($content);
        }
    }
    global $bookingpress_appointment_bookings_popup;
    $bookingpress_appointment_bookings_popup = new bookingpress_appointment_bookings_popup();
}
?>