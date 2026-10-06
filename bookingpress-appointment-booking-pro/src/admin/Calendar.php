<?php
namespace BookingPressPro\admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use BookingPressPro\data\ServicesProviders;

class Calendar extends Base {

    protected static $slug = 'bookingpress-calendar';

    public static function init() {
        // Initializes base hooks (like enqueuing)
        parent::init();

        add_filter( 'script_module_data_bookingpress-appointment-model', [ __CLASS__, 'add_script_module_data' ] );
        add_filter( 'script_module_data_bookingpress-calendar-loader', [ __CLASS__, 'add_script_module_data_calendar_loader' ] );

        add_filter( 'bookingpress_rest_get_dates_handler', [ __CLASS__, 'bookingpress_modify_dates_with_day_service'], 6, 2 );
    }

    public static function add_script_module_data_calendar_loader( $data ) {
        global $bookingpress_global_options, $BookingPress, $wpdb, $tbl_bookingpress_default_workhours, $tbl_bookingpress_services, $tbl_bookingpress_categories, $BookingPressPro, $tbl_bookingpress_form_fields;
        $bookingpress_days_arr =  ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $bookingpress_times_arr = [
            'start_time' => [],
            'end_time' => [],
        ];
        
        foreach( $bookingpress_days_arr as $days_key => $days_val ) {
            $selected_timing_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_bookingpress_default_workhours} WHERE bookingpress_workday_key = %s AND bookingpress_is_break = 0", $days_val ), ARRAY_A); //phpcs:ignore

            if( !empty( $selected_timing_data['bookingpress_start_time'] ) ) {
                $bookingpress_times_arr['start_time'][] = $selected_timing_data['bookingpress_start_time'];
                $bookingpress_times_arr['end_time'][] = ( '00:00:00' === $selected_timing_data['bookingpress_end_time'] ) ? '24:00:00' : $selected_timing_data['bookingpress_end_time'];
            }
        }

        $bookingpress_times_arr = apply_filters( 'bookingpress_expand_timerange_externally', $bookingpress_times_arr );

        $start_time = min( $bookingpress_times_arr['start_time'] );
        $end_time = max( $bookingpress_times_arr['end_time'] );

        $start_time_ = date('H:i', strtotime($start_time));
        $end_time_ = date('H:i', strtotime($end_time));

        $timerange = [
            'start' => $start_time_,
            'end' => ( '00:00' == $end_time_ ) ? '24:00' : $end_time_,
        ];

        $bookingpress_options     = $bookingpress_global_options->bookingpress_global_options();
        $bookingpress_appointment_status_arr    = $bookingpress_options['appointment_status'];

        $timeFormat = $bookingpress_options['wp_default_time_format'];

        $status_options = [];

        foreach( $bookingpress_appointment_status_arr as $status_key => $status_val ) {
            $status_options[] = [
                'value' => $status_val['value'],
                'label' => $status_val['text'],
            ];
        }
        $BookingPressAppointmentStatus = $status_options;

        $firstDayOfWeek = intval( $bookingpress_options['start_of_week'] );

        $allowAllDayService = false;
        $check_for_all_day_service = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl_bookingpress_services} WHERE bookingpress_service_duration_unit = %s", 'd' ) ); //phpcs:ignore
        if( $check_for_all_day_service > 0 ) {
            $allowAllDayService = true;
        }
	
	$categories         = $wpdb->get_results('SELECT * FROM ' . $tbl_bookingpress_categories . ' order by bookingpress_category_position ASC', ARRAY_A);// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Reason: $tbl_bookingpress_categories is table name defined globally. False Positive alarm

        $all_categories = [
            [
                'value'    => 0,
                'label' => esc_html__( 'Uncategorized', 'bookingpress-appointment-booking')
            ]
        ];

        foreach( $categories as $category ) {
            $all_categories[] = [
                'value' => $category['bookingpress_category_id'],
                'label' => stripslashes_deep($category['bookingpress_category_name'])
            ];
        }

        $all_services = ServicesProviders::get_all_services_with_name();

        $services_list = [];
        foreach( $all_services as $service_data ){
            $services_list[] = [
                'value' => $service_data['bookingpress_service_id'],
                'label' => stripslashes_deep($service_data['bookingpress_service_name'])
            ];
        }

        $manage_appontments = ( $BookingPressPro->bookingpress_check_capability( 'bookingpress_edit_appointments' ) );

        $data['calendar_config'] = [
            'locale' => str_replace('_', '-', get_locale()),
            'uiText'=> [
                'header'=> [
                    'logo'=> __('Calendar','bookingpress-appointment-booking'),
                    'today' => __('Today','bookingpress-appointment-booking'),
                    'month'=> __('Month','bookingpress-appointment-booking'),
                    'week' => __('Week','bookingpress-appointment-booking'),
                    'day'=> __('Day','bookingpress-appointment-booking'),
                    'timeline'=> __('Timeline','bookingpress-appointment-booking'),
                    'filter'=> __('Filter','bookingpress-appointment-booking'),
                    'addNew'=>__('Add new','bookingpress-appointment-booking'),
                    'weekLayoutTog' => '',
                ],
                'bookingFilters' => [
                    'title' => __('Filter','bookingpress-appointment-booking'),
                    'apply' => __('Apply','bookingpress-appointment-booking')
                ],
                'allDay' => [
                        'title' => __('Full day & Multi Day Bookings','bookingpress-appointment-booking'),
                        'empty' => __('No full day or multi day bookings','bookingpress-appointment-booking'),
                        'fullDay' => __('Full Day','bookingpress-appointment-booking'),
                        'multiDay' => __('Multi Day','bookingpress-appointment-booking'),
                        'expand' => __('Expand','bookingpress-appointment-booking'),
                        'collapse'=> __('Collapse','bookingpress-appointment-booking'),
                ],
                'dayHeader' => [
                    'expand' => __('Expand','bookingpress-appointment-booking'),
                    'collapse'=> __('Collapse','bookingpress-appointment-booking'),
                ],
                'month' => [
                    'more' => __('more','bookingpress-appointment-booking'),
                ],
                'displaySettings' => [
                    'title' => __('Card Fields','bookingpress-appointment-booking'),
                    'save' => __('Save','bookingpress-appointment-booking'),
                    'reorder' => __('reorder','bookingpress-appointment-booking'),
                ],
            ],
            'showAllDaySection' => $allowAllDayService,
            'timeRange' => $timerange,
            'timeFormat' => $timeFormat,
            'appShell'  => [
                'enabled' => true,
                'sidebarTemplateSelector' => '#bookingpress-calendar-mobile-navmenu',
                'sidebarWidth' => '220px',
                'sidebarSide' => 'right',
                'mobileDrawerOffsetTop' => '76px'
            ],
            'interactionConfig' => [
                'enableCardDragDrop' => $manage_appontments,
                'enableCardResize' => $manage_appontments,
                'showAddAppointmentButton' => $manage_appontments,
            ],
            'bookingFilterConfig' => [
                'service' => [
                    'visible' => true,
                    'multiple' => true,
                    'options' => $services_list
                ],
                'status' => [
                    'visible' => true,
                    'allowedValues' => ['1', '2']
                ],
                'category' => [
                    'visible' => true,
                    'multiple' => true,
                    'options' => $all_categories
                ]
            ],
            'firstDayOfWeek' => $firstDayOfWeek,
            'popover' => [
                'price' => true,
                'statusOptions' => $BookingPressAppointmentStatus,
                'enableEditAppointmentButton' => $manage_appontments,
                'enableRescheduleAppointmentButton' => $manage_appontments,
                'enableStatusDropdown' => $manage_appontments
            ]
        ];

        $default_form_fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT bookingpress_form_field_id, bookingpress_field_label, bookingpress_form_field_name FROM {$tbl_bookingpress_form_fields} WHERE (bookingpress_form_field_name = %s OR bookingpress_form_field_name = %s) AND bookingpress_field_is_default = %d",
                'email_address',
                'phone_number',
                1
            )
        );

        $email_field_label = esc_html__( 'Email Address', 'bookingpress-appointment-booking' );
        $phone_field_label = esc_html__( 'Phone Number', 'bookingpress-appointment-booking' );

        $email_field_id = 4; // Default field id for email address
        $phone_field_id = 5; // Default field id for phone number

        foreach( $default_form_fields as $field_data ){
            if( $field_data->bookingpress_form_field_name == 'email_address' ){
                $email_field_id = $field_data->bookingpress_form_field_id;
                $email_field_label = $field_data->bookingpress_field_label;
            } else if( $field_data->bookingpress_form_field_name == 'phone_number' ){
                $phone_field_id = $field_data->bookingpress_form_field_id;
                $phone_field_label = $field_data->bookingpress_field_label;
            }
        }

        $extraFieldSettings = [
            [
                'id' => $email_field_id,
                'label' => $email_field_label,
                'visible' => true
            ],
            [
                'id' => $phone_field_id,
                'label' => $phone_field_label,
                'visible' => true
            ]
        ];

        $extraFieldSettings = apply_filters( 'bookingpress_modify_extra_fields_data', $extraFieldSettings );

        $data['calendar_config']['displaySettings']['extraDisplayFields'] = $extraFieldSettings;

        $data = apply_filters( 'bookingpress_modify_calendar_model_data', $data );

        return $data;
    }

    public static function add_script_module_data( $data ) {

        global $bookingpress_global_options, $BookingPress, $BookingPressPro, $bookingpress_service_extra;
        $bookingpress_options     = $bookingpress_global_options->bookingpress_global_options();
        $bookingpress_appointment_status_arr    = $bookingpress_options['appointment_status'];

        $bookingpress_pagination               = $bookingpress_options['pagination'];
        $bookingpress_pagination_arr           = json_decode($bookingpress_pagination, true);
        $bookingpress_pagination_selected      = $bookingpress_pagination_arr[0];


        $data = array(
            'bulk_action'                => 'bulk_action',
            'bulk_options'               => array(
                array(
                    'value' => 'bulk_action',
                    'label' => esc_html__('Bulk Action', 'bookingpress-appointment-booking'),
                ),
                array(
                    'value' => 'delete',
                    'label' => esc_html__('Delete', 'bookingpress-appointment-booking'),
                ),
            ),
            'items'                      => array(),
            'multipleSelection'          => array(),
            'appointment_customers_list' => array(),
            'appointment_services_list'  => array(),
            'perPage'                    => $bookingpress_pagination_selected,
            'totalItems'                 => 0,
            'pagination_selected_length' => $bookingpress_pagination_selected,
            'pagination_length'          => $bookingpress_pagination,
            'currentPage'                => 1,
            'search_appointment'         => '',
            'search_appointment_id'         => '',
            'appointment_date_range'     => array( date('Y-m-d', strtotime('-3 Day')), date('Y-m-d', strtotime('+3 Day')) ),
            'search_customer_name'       => '',
            'search_service_name'        => '',
            'search_service_employee'    => '',
            'search_appointment_status'  => '',
            'search_customer_list'       => '',
            'search_status'              => $bookingpress_appointment_status_arr,
            'appointment_time_slot'      => array(),
            'appointment_status'         => $bookingpress_appointment_status_arr,
            'service_employee'           => array(),
            'appointment_services_data'  => array(),
            'modal_loader'               => 1,
            'rules'                      => array(
                'appointment_selected_customer' => array(
                    array(
                        'required' => true,
                        'message'  => esc_html__('Please select customer', 'bookingpress-appointment-booking'),
                        'trigger'  => 'change',
                    ),
                ),
                'appointment_selected_service'  => array(
                    array(
                        'required' => true,
                        'message'  => esc_html__('Please select service', 'bookingpress-appointment-booking'),
                        'trigger'  => 'change',
                    ),
                ),
                'appointment_booked_date'       => array(
                    array(
                        'required' => true,
                        'message'  => esc_html__('Please select booking date', 'bookingpress-appointment-booking'),
                        'trigger'  => 'change',
                    ),
                ),
                'appointment_booked_time'       => array(
                    array(
                        'required' => true,
                        'message'  => esc_html__('Please select booking time', 'bookingpress-appointment-booking'),
                        'trigger'  => 'change',
                    ),
                ),
            ),
            'appointment_formdata'       => array(
                'appointment_selected_customer'     => '',
                'appointment_selected_staff_member' => '',
                'appointment_selected_service'      => '',
                'appointment_booked_date'           => date('Y-m-d', current_time('timestamp')),
                'appointment_booked_time'           => '',
                'appointment_booked_end_time'       => '',
                'appointment_internal_note'         => '',
                'appointment_send_notification'     => false,
                'appointment_status'                => '1',
                'appointment_update_id'             => 0,
            ),
            'pagination_length_val'      => '10',
            'pagination_val'             => array(
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
            'savebtnloading'             => false,
            'open_appointment_modal'     => false,
            'is_display_loader'          => '0',
            'is_disabled'                => false,
            'is_display_save_loader'     => '0',
        );

        $data['is_compitible_with_pro'] = 1;

        $status_options = [];

        foreach( $bookingpress_appointment_status_arr as $status_key => $status_val ) {
            $status_options[] = [
                'value' => $status_val['value'],
                'label' => $status_val['text'],
            ];
        }
        $data['BookingPressAppointmentStatus'] = $status_options;

        $bookingpress_allow_customer_create = $BookingPress->bookingpress_get_settings('allow_wp_user_create', 'customer_setting');
        $bookingpress_allow_customer_create = ! empty($bookingpress_allow_customer_create) ? $bookingpress_allow_customer_create : 'false';
        $bookingpress_allow_customer_create = $bookingpress_allow_customer_create == 'true' ? true : false;

        $data['allow_customer_wp_user_create'] = $bookingpress_allow_customer_create;

        $bookingpress_default_date_format = $BookingPress->bookingpress_check_common_date_format_for_picker($bookingpress_options['wp_default_date_format']);
        $data['bookingpress_date_common_date_format'] = $bookingpress_default_date_format;

        $ServiceProviders = ServicesProviders::get_services_group_with_category();
        
        $data['BookingPressServiceProviders']   = $ServiceProviders;
        $data['appointment_services_list']      = $ServiceProviders;

        $data['openAddNewAppointmentModel']     = false;
        $data['closeModelOnEscape']             = true;
        $data['bookingpress_loading']           = esc_html__( "Loading...", "bookingpress-appointment-booking");
        $data['loading_from_server']            = false;

        $all_services = [];
        foreach( $ServiceProviders as $serviceProvider ) {
            foreach( $serviceProvider['category_services'] as $service ) {
                if( 0 !== $service['service_id'] ) {
                    $all_services[] = [
                        'value' => $service['service_id'],
                        'label' => stripslashes_deep($service['service_name']),
                    ];
                }
            }
        }
        $data['BookingPressAllServices'] = $all_services;

        if( current_user_can( 'bookingpress_customers' ) ) {
            $data['bookingpress_edit_customers'] = 1;
        } else {
            $data['bookingpress_edit_customers'] = 0;
        }

        if( current_user_can( 'bookingpress_payments') ){
            $data['bookingpress_payments'] = 1;
        } else {
            $data['bookingpress_payments'] = 0;
        }

        $default_daysoff_details = $BookingPress->bookingpress_get_default_dayoff_dates();
        if (! empty($default_daysoff_details) ) {
            $default_daysoff_details                                = array_map(
                function ( $date ) {
                    return date('Y-m-d', strtotime($date));
                },
                $default_daysoff_details
            );
            $data['disabledDates'] = $default_daysoff_details;
        } else {
            $data['disabledDates'] = [];
        }

        $bookingpress_phone_country_option = $BookingPress->bookingpress_get_settings('default_phone_country_code', 'general_setting');
        $data['customer_phone_country'] = $bookingpress_phone_country_option;

        $data['bookingpress_tel_input_props'] = array(
            'defaultCountry' => $bookingpress_phone_country_option,
            'inputOptions' => array(
                'placeholder' => '',
            ),
            'validCharactersOnly' => true,
        );

        $data['firstDayOfWeek'] = intval( $bookingpress_options['start_of_week'] );

        $default_timings = $BookingPressPro->bookingpress_get_default_appointment_timing();
        $data['default_appointment_timing'] = $default_timings;

        $valid_key = hash_hmac('sha256', '_9112021029174191151512', home_url() );
        $valid_key_value = get_option( $valid_key, false );

        $stnemtnioppa = strrev( 'stnemtnioppa_latot' );
        $wenever = strrev( 'eunever_latot' );

        $total_appointments = get_option( $stnemtnioppa ) ?? 0;
        $total_payments = get_option( $wenever ) ?? 0;

        $data['rprt_model'] = ( '0' === $valid_key_value && ( 100 <= $total_appointments || 5000 <= $total_payments ) ) ? true : false;
        $data['rprt_license_key'] = '';
        $data['rprt_verify_btn_loading'] = false;
        
        $data = apply_filters( 'bookingpress_appointment_model_data', $data );

        $data['default_appointment_formdata'] = $data['appointment_formdata'];

        return $data;
    }


    /**
     * Specific enqueuing for Calendar
     */
    public static function enqueue_assets( $hook ) {
        // Check if we are on the calendar page        
        if ( strpos( $hook, static::$slug ) === false ) {
            return;
        }

        wp_register_script_module(
            'vue',
            BOOKINGPRESS_URL .'/src/assets/js/vue.min.js',
            [],
            BOOKINGPRESS_VERSION
        );

        wp_register_script_module(
            'bookingpress-ui',
            BOOKINGPRESS_URL . '/src/assets/js/bookingpress-ui.min.js',
            [],
            BOOKINGPRESS_VERSION
        );

        $script = ( defined( 'SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? 'calendar.js' : 'calendar.min.js';

        wp_register_script_module(
            'bookingpress-calendar',
            BOOKINGPRESS_URL . '/src/assets/js/' . $script,
            ['vue', 'bookingpress-ui'],
            BOOKINGPRESS_VERSION
        );

        wp_enqueue_script_module( 'bookingpress-calendar' );

        wp_register_script_module(
            'bookingpress-calendar-loader',
            BOOKINGPRESS_URL . '/src/assets/js/calendar-loader.js',
            [ 'bookingpress-calendar', 'bookingpress-ui'],
            BOOKINGPRESS_VERSION
        );

        wp_enqueue_script_module( 'bookingpress-calendar-loader' );

        wp_register_script_module(
            'bookingpress-appointment-model',
            BOOKINGPRESS_URL . '/src/assets/js/appointment-model.js',
            [ 'bookingpress-ui'],
            BOOKINGPRESS_VERSION
        );

        wp_enqueue_script_module( 'bookingpress-appointment-model' );

        wp_register_script_module(
            'bookingpress-customer-model',
            BOOKINGPRESS_URL . '/src/assets/js/customer-model.js',
            [ 'bookingpress-ui'],
            BOOKINGPRESS_VERSION
        );
        wp_enqueue_script_module( 'bookingpress-customer-model' );

        wp_enqueue_style(
            'bookingpress-calendar',
            BOOKINGPRESS_URL . '/src/assets/css/calendar.min.css',
            [],
            BOOKINGPRESS_VERSION
        );

        wp_enqueue_style(
            'bookingpress-ui',
            BOOKINGPRESS_URL . '/src/assets/css/bookingpress-ui.min.css',
            [],
            BOOKINGPRESS_VERSION
        );

        wp_register_script_module(
            'bookingpress-pro-main',
            BOOKINGPRESS_PRO_URL .'/src/assets/js/main.js',
            [],
            BOOKINGPRESS_PRO_VERSION
        );
        wp_enqueue_script_module( 'bookingpress-pro-main' );

        wp_enqueue_style( 'bookingpress_admin_css' );
        wp_enqueue_style( 'bookingpress_pro_admin_css' );
        wp_enqueue_style( 'bookingpress_components_css' );
        wp_enqueue_style( 'bookingpress_fonts_css' );
        wp_enqueue_script( 'moment' );

        wp_enqueue_style(
            'bookingpress-common',
            BOOKINGPRESS_URL . '/src/assets/css/common.css',
            [],
            BOOKINGPRESS_VERSION
        );

        do_action( 'bookingpress_enqueue_additional_assets' );
    }

    public static function render_page() {
        self::render_view( 'Calendar', [
            'title' => esc_html__( 'Calendar', 'bookingpress-appointment-booking' )
        ] );
    }

    public static function bookingpress_modify_dates_with_day_service( $response, $request ){

        global $bookingpress_pro_appointment_bookings;

        $data_object = $request->get_param( 'appointment_data_obj' );
        if( !empty( $data_object['selected_service_duration_unit'] ) && 'd' != $data_object['selected_service_duration_unit'] ){
            return $response;
        }

        $bookingpress_get_disable_date_response = $bookingpress_pro_appointment_bookings->bookingpress_get_disable_dates_for_days_services_legacy(true);

        if( !empty( $bookingpress_get_disable_date_response ) ){
            return $bookingpress_get_disable_date_response;
        }

        return $response;
    }
}
