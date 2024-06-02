<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Listeo_Core_Bookings class.
 */
class Mrj_Global_Guest_Commission_Listeo_Core_Bookings_Calendar {

    public function __construct() {

        // for booking widget
        add_action('wp_ajax_mrjgcc_check_avaliabity', array($this, 'ajax_check_avaliabity'));
        add_action('wp_ajax_nopriv_mrjgcc_check_avaliabity', array($this, 'ajax_check_avaliabity'));  

        add_action('wp_ajax_mrjgcc_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_mrjgcc_calculate_price', array($this, 'ajax_calculate_price'));

        add_action('wp_ajax_mrjgcc_listeo_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_nopriv_mrjgcc_listeo_validate_coupon', array($this, 'ajax_validate_coupon'));
      
        add_action('wp_ajax_mrjgcc_listeo_get_booking_states', array($this, 'ajax_get_states'));
        add_action('wp_ajax_nopriv_mrjgcc_listeo_get_booking_states', array($this, 'ajax_get_states'));
        
        add_action('wp_ajax_mrjgcc_listeo_calculate_booking_form_price', array($this, 'ajax_calculate_booking_form_price'));
        add_action('wp_ajax_nopriv_mrjgcc_listeo_calculate_booking_form_price', array($this, 'ajax_calculate_booking_form_price'));

        

        add_action('wp_ajax_mrjgcc_update_slots', array($this, 'ajax_update_slots'));
        add_action('wp_ajax_nopriv_mrjgcc_update_slots', array($this, 'ajax_update_slots'));       
        
       // add_action('wp_ajax_listeo_apply_coupon', array($this, 'ajax_widget_apply_coupon'));
       // add_action('wp_ajax_nopriv_listeo_apply_coupon', array($this, 'ajax_widget_apply_coupon'));  

        // for bookings dashboard
        add_action('wp_ajax_mrjgcc_listeo_bookings_manage', array($this, 'ajax_listeo_bookings_manage'));
        add_action('wp_ajax_mrjgcc_listeo_bookings_renew_booking', array($this, 'ajax_listeo_bookings_renew_booking'));

        // booking page shortcode and post handling
        add_shortcode( 'listeo_booking_confirmation', array( $this, 'listeo_core_booking' ) );
        add_shortcode( 'listeo_bookings', array( $this, 'listeo_core_dashboard_bookings' ) );
        add_shortcode( 'listeo_my_bookings', array( $this, 'listeo_core_dashboard_my_bookings' ) );

        //MRJ - Use this filter to add the commission valu to the price that is returned
        // add_filter('listeo_booking_price_calc', array( $this, 'mrjgcc_modify_calculate_price'), 10, 6);


        // when woocoommerce is paid trigger function to change booking status
        //add_action( 'woocommerce_order_status_completed', array( $this, 'booking_paid' ), 9, 3 ); 
        //add_action( 'woocommerce_order_status_refunded', array( $this, 'booking_refund' ), 9, 3 ); 
        // remove listeo booking products from shop
        //add_action( 'woocommerce_product_query', array($this,'listeo_wc_pre_get_posts_query' ));  

        //add_action( 'listeo_core_check_for_expired_bookings', array( $this, 'check_for_expired_booking' ) );
        //add_action( 'listeo_core_check_for_upcoming_booking', array( $this, 'check_for_upcoming_booking' ) );
        //add_action( 'listeo_core_check_for_past_booking', array( $this, 'check_for_past_booking' ) );
        //add_action( 'listeo_core_check_for_upcoming_payments', array( $this, 'check_for_upcoming_payments' ) );

        //add_action('wp_ajax_listeo_core_booking_author_suggest', array( $this, 'listeo_core_booking_author_suggest'));
        //add_action('wp_ajax_nopriv_listeo_core_booking_author_suggest', array( $this, 'listeo_core_booking_author_suggest'));
        
    }

//    function ajax_get_states() {
//        $country = sanitize_text_field($_POST['country']);
//        $states = WC()->countries->get_states( $country );
//        wp_send_json_success( $states );
//    }


//    static function listeo_core_booking_author_suggest()
//    {
//
//        $suggestions = array();
//        $posts = get_posts(array(
//            's' => $_REQUEST['term'],
//            'post_type'     => 'listing',
//        ));
//        global $post;
//        $results = array();
//        foreach ($posts as $post) {
//            setup_postdata($post);
//            $suggestion = array();
//            $suggestion['label'] =  html_entity_decode($post->post_title, ENT_QUOTES, 'UTF-8');
//            $suggestion['link'] = get_permalink($post->ID);
//
//            $suggestions[] = $suggestion;
//        }
//        // JSON encode and echo
//        $response = $_GET["callback"] . "(" . json_encode($suggestions) . ")";
//        echo $response;
//        // Don't forget to exit!
//        exit;
//    }
     /**
     * WP Kraken #w785816
     */
    public static function wpk_change_booking_hours( $date_start, $date_end ) {

        $start_date_time = new DateTime( $date_start );
        $end_date_time = new DateTime( $date_end );

        $is_the_same_date = $start_date_time->format( 'Y-m-d' ) == $end_date_time->format( 'Y-m-d' );

        // single day bookings are not alowed, this is owner reservation
        // set end of this date as the next day
        if ( $is_the_same_date ) {
            $end_date_time->add( DateInterval::createfromdatestring('+1 day') );
        }
        $end_date_time->add( DateInterval::createfromdatestring('-1 day') );
        $start_date_time->setTime( 12, 0 );
        $end_date_time->setTime( 11, 59, 59 );

        return array(
            'date_start'    => $start_date_time->format( 'Y-m-d H:i:s' ),
            'date_end'      => $end_date_time->format( 'Y-m-d H:i:s' )
        );

    }
     

    /**
    * Get bookings between dates filtred by arguments
    *
    * @param  date $date_start in format YYYY-MM-DD
    * @param  date $date_end in format YYYY-MM-DD
    * @param  array $args fot where [index] - name of column and value of index is value
    *
    * @return array all records informations between two dates
    */
    public static function get_bookings( $date_start, $date_end, $args = '', $by = 'booking_date', $limit = '', $offset = '' ,$all = '', $listing_type = '')  {

        global $wpdb;
        $result = false;
        // if(strlen($date_start)<10){
        //     if($date_start) { $date_start = $date_start.' 00:00:00'; }
        //     if($date_end) { $date_end = $date_end.' 23:59:59'; }
        // }

        // setting dates to MySQL style
        
        $date_start = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_start ) ) ) );
        $date_end = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_end ) ) ) );
        
        //TODO to powinno byc tylko dla rentals!!
          // WP Kraken
        if($listing_type == 'rental'){   
            $booking_hours = self::wpk_change_booking_hours( $date_start, $date_end );
            $date_start = $booking_hours[ 'date_start' ];
            $date_end = $booking_hours[ 'date_end' ];
        }
        
        // filter by parameters from args
        $WHERE = '';
        $FILTER_CANCELLED = "AND NOT status='cancelled' AND NOT status='expired' ";

        if ( is_array ($args) )
        {
            foreach ( $args as $index => $value ) 
            {

                $index = esc_sql( $index );
                $value = esc_sql( $value );

                if ( $value == 'approved' ){ 
                    $WHERE .=
                    " AND status IN ('confirmed','paid','approved')";
                } elseif ( $value == 'icalimports' ) { 

                } else {
                    $WHERE .= " AND (`$index` = '$value')";  
                } 
                if( $value == 'cancelled' || $value == 'special_price'){
                    $FILTER_CANCELLED = '';
                }
                if( $value == 'icalimports'){
                    $FILTER_CANCELLED = "AND NOT status='icalimports'";
                }
            
            }
        }

        if($all == 'users'){
            $FILTER = "AND NOT comment='owner reservations'";
        } else if( $all == 'owner') {
            $FILTER = "AND comment='owner reservations'";
        } else {
            $FILTER = '';
        }
        

        if ( $limit != '' ) $limit = " LIMIT " . esc_sql($limit);
        
        if ( is_numeric($offset)) $offset = " OFFSET " . esc_sql($offset);

        switch ($by)
        {

            case 'booking_date' :
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE ((' $date_start' >= `date_start` AND ' $date_start' <= `date_end`) OR ('$date_end' >= `date_start` AND '$date_end' <= `date_end`) OR (`date_start` >= ' $date_start' AND `date_end` <= '$date_end')) $WHERE $FILTER $FILTER_CANCELLED $limit $offset", "ARRAY_A" );
               
             break;

                
            case 'created_date' :
                // when we searching by created date automaticly we looking where status is not null because we using it for dashboard booking
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE (' $date_start' <= `created` AND ' $date_end' >= `created`) AND (`status` IS NOT NULL)  $WHERE $FILTER_CANCELLED $limit $offset", "ARRAY_A" );
                break;
            
        }
        
      
        return $result;

    }

    public static function get_slots_bookings( $date_start, $date_end, $args = '', $by = 'booking_date', $limit = '', $offset = '' ,$all = '')  {

        global $wpdb;
        
        // if(strlen($date_start)<10){
        //     if($date_start) { $date_start = $date_start.' 00:00:00'; }
        //     if($date_end) { $date_end = $date_end.' 23:59:59'; }
        // }
        
        // setting dates to MySQL style
        $date_start = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_start ) ) ) );
        $date_end = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_end ) ) ) );
        
        // filter by parameters from args
        $WHERE = '';
        $FILTER_CANCELLED = "AND NOT status='cancelled' ";
        if ( is_array ($args) )
        {
            foreach ( $args as $index => $value ) 
            {

                $index = esc_sql( $index );
                $value = esc_sql( $value );

                if ( $value == 'approved' ){ 
                    $WHERE .= " AND ( (`$index` = 'confirmed') OR (`$index` = 'paid') )";
                } else {
                  $WHERE .= " AND (`$index` = '$value')";  
                } 
                if( $value == 'cancelled' ){
                    $FILTER_CANCELLED = '';
                }
            
            }
        }
        if($all == 'users'){
            $FILTER = "AND NOT comment='owner reservations'";
        } else {
            $FILTER = '';
        }

        if ( $limit != '' ) $limit = " LIMIT " . esc_sql($limit);
        
        if ( is_numeric($offset)) $offset = " OFFSET " . esc_sql($offset);
        switch ($by)
        {

            case 'booking_date' :
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE (('$date_start' = `date_start` AND '$date_end' = `date_end`)) $WHERE $FILTER $FILTER_CANCELLED $limit $offset", "ARRAY_A" );
                break;

                
            case 'created_date' :
                // when we searching by created date automaticly we looking where status is not null because we using it for dashboard booking
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE (' $date_start' = `created` AND ' $date_end' = `created`) AND (`status` IS NOT NULL)  $WHERE $FILTER_CANCELLED $limit $offset", "ARRAY_A" );
                break;
            
        }
        
        
        return $result;

    }

    /**
    * Get maximum number of bookings between dates filtred by arguments, used for pagination
    *
    * @param  date $date_start in format YYYY-MM-DD
    * @param  date $date_end in format YYYY-MM-DD
    * @param  array $args fot where [index] - name of column and value of index is value
    *
    * @return array all records informations between two dates
    */
    public static function get_bookings_max( $date_start, $date_end, $args = '', $by = 'booking_date' )  {

        global $wpdb;

        // setting dates to MySQL style
        $date_start = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_start ) ) ) );
        $date_end = esc_sql ( date( "Y-m-d H:i:s", strtotime( $wpdb->esc_like( $date_end ) ) ) );

        // filter by parameters from args
        $WHERE = '';
        $FILTER_CANCELLED = "AND NOT status='cancelled' ";
        
        if ( is_array ($args) )
        {
            foreach ( $args as $index => $value ) 
            {

                $index = esc_sql( $index );
                $value = esc_sql( $value );

                if ( $value == 'approved' ){ 
                    $WHERE .= " AND ((`$index` = 'confirmed') OR (`$index` = 'paid'))";
                } else {
                  $WHERE .= " AND (`$index` = '$value')";  
                } 
                if( $value == 'cancelled' ){
                    $FILTER_CANCELLED = '';
                }
            
            }
        }
        
        switch ($by)
        {

            case 'booking_date' :
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE ((' $date_start' >= `date_start` AND ' $date_start' <= `date_end`) OR ('$date_end' >= `date_start` AND '$date_end' <= `date_end`) OR (`date_start` >= ' $date_start' AND `date_end` <= '$date_end')) AND NOT comment='owner reservations' $WHERE $FILTER_CANCELLED", "ARRAY_A" );
                break;

                
            case 'created_date' :
                // when we searching by created date automaticly we looking where status is not null because we using it for dashboard booking
                $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE (' $date_start' <= `created` AND ' $date_end' >= `created`) AND (`status` IS NOT NULL) AND  NOT comment = 'owner reservations' $WHERE $FILTER_CANCELLED", "ARRAY_A" );
                break;
            
        }
        
        
        return $wpdb->num_rows;

    }

    /**
    * Get latest bookings number of bookings between dates filtred by arguments, used for pagination
    *
    * @param  date $date_start in format YYYY-MM-DD
    * @param  date $date_end in format YYYY-MM-DD
    * @param  array $args fot where [index] - name of column and value of index is value
    *
    * @return array all records informations between two dates
    */
    public static function get_newest_bookings( $args = '', $limit = 5, $offset = 0 )  {

        global $wpdb;

        // setting dates to MySQL style
       
        // filter by parameters from args
        $WHERE = '';

        if ( is_array ($args) )
        {
            foreach ( $args as $index => $value ) 
            {

                $index = esc_sql( $index );
                $value = esc_sql( $value );

                if ( $value == 'approved' ){ 
                    $WHERE .= " AND status IN ('confirmed','paid','approved')";
                   
                } else 
                if ( $value == 'waiting' ){ 
                    $WHERE .= " AND status IN ('waiting','pay_to_confirm')";
                    
                } else {
                  $WHERE .= " AND (`$index` = '$value')";  
                } 
            
            
            }
        }
        
        if ( $limit != '' ) $limit = " LIMIT " . esc_sql($limit);
        //if(isset($args['status']) && $args['status'])
        $offset = " OFFSET " . esc_sql($offset);
       
        // when we searching by created date automaticly we looking where status is not null because we using it for dashboard booking
        $result  = $wpdb -> get_results( "SELECT * FROM `" . $wpdb->prefix . "bookings_calendar` WHERE  NOT comment = 'owner reservations' $WHERE ORDER BY `" . $wpdb->prefix . "bookings_calendar`.`created` DESC $limit $offset", "ARRAY_A" );

        
        return $result;

    }

    /**
    * Check gow may free places we have
    *
    * @param  date $date_start in format YYYY-MM-DD
    * @param  date $date_end in format YYYY-MM-DD
    * @param  array $args
    *
    * @return number $free_places that we have this time
    */
    public static function count_free_places( $listing_id, $date_start, $date_end, $slot = 0 )  {

         // get slots
         $_slots = self :: get_slots_from_meta ( $listing_id );
         $slots_status = get_post_meta ( $listing_id, '_slots_status', true );

         if(isset($slots_status) && !empty($slots_status)) {
            $_slots = self :: get_slots_from_meta ( $listing_id );
         } else {
            $_slots = false;
         }
        // get listing type
        $listing_type = get_post_meta ( $listing_id, '_listing_type', true );
     

         // default we have one free place
         $free_places = 1;

         // check if this is service type of listing and slots are added, then checking slots
         if ( $listing_type == 'service' && $_slots ) 
         {
             $slot = json_decode( wp_unslash($slot) );
 
             // converent hours to mysql format
             $hours = explode( ' - ', $slot[0] );
             $hour_start = date( "H:i:s", strtotime( $hours[0] ) );
             $hour_end = date( "H:i:s", strtotime( $hours[1] ) );
 
             // add hours to dates
             $date_start .= ' ' . $hour_start;
             $date_end .= ' ' . $hour_end;
 
             // get day and number of slot
             $day_and_number = explode( '|', $slot[1] );
             $slot_day = $day_and_number[0];
             $slot_number =  $day_and_number[1];

             // get amount of slots
             $slots_amount = explode( '|', $_slots[$slot_day][$slot_number] );
       
            $slots_amount = $slots_amount[1];
    
             $free_places = $slots_amount;

 
         } else if ( $listing_type == 'service' && ! $_slots )  {

             // if there are no slots then always is free place and owner menage himself

            // check for imported icals
            $result = self :: get_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'reservation' ) );
            if(!empty($result)) {
                return 0; 
            } else {
                return 1;
            }


         }

         if ( $listing_type == 'event' ) {

             // if its event then always is free place and owner menage himself
            $ticket_number = get_post_meta ( $listing_id, '_event_tickets', true );
            $ticket_number_sold = get_post_meta ( $listing_id, '_event_tickets_sold', true );
            return $ticket_number - $ticket_number_sold;
            

         }
 
         // get reservations to this slot and calculace amount
         if($listing_type == 'rental' ) {
            $minspan = (int) get_post_meta($listing_id, '_min_days', true);

            $date_start_time  = strtotime($date_start);
            $date_start_raw = new DateTime("@$date_start_time");

            $date_end_time = strtotime($date_end);
            $date_end_raw = new DateTime("@$date_end_time");

            $date_diff = $date_end_raw->diff($date_start_raw)->format("%a");
            $last_day_count = get_option('listeo_count_last_day_booking', 'off');
            if ($last_day_count == 'on') {
                $date_diff++;
            }
            if($date_diff < ($minspan-1)) {
                return 0;
            } else {
              
                            $result = self::get_bookings(
                                $date_start,
                                $date_end,
                                array('listing_id' => $listing_id, 'type' => 'reservation'),
                                $by = 'booking_date',
                                $limit = '',
                                $offset = '',
                                $all = '',
                                $listing_type = 'rental'
                            );
                         
            }
        
          
         } else {
                if($listing_type == 'service' && $_slots ){
                    $result = self ::  get_slots_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'reservation' ) );
                } else {
                    $result = self :: get_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'reservation' ), $by = 'booking_date', $limit = '', $offset = '',$all = '', $listing_type = 'service' );   
                }
             
         }
         

         // count how many reservations we have already for this slot
         $reservetions_amount = count( $result );   
        
         // minus temp reservations for this time
         // $free_places -= self :: temp_reservation_aval( array( 'listing_id' => $listing_id,
         // 'date_start' => $date_start, 'date_end' => $date_end) );

        // minus reservations from database
        $free_places -= $reservetions_amount;
        return $free_places;

    }

    /**
    * Ajax check avaliabity
    *
    * @return number $ajax_out['free_places'] amount or zero if not
    * 
    * @return number $ajax_out['price'] calculated from database prices
    *
    */
    public static function ajax_check_avaliabity(  )  {
        if(!isset($_POST['slot'])){
            $slot = false;
        } else {
            $slot = sanitize_text_field($_POST['slot']);
        }
        if(isset($_POST['hour'])){

            $_opening_hours_status = get_post_meta($_POST['listing_id'], '_opening_hours_status',true);
            $ajax_out['free_places'] = 1;

            // check if theres a booking between these hours on that date
            //check opening times
            if($_opening_hours_status){
                $currentTime = $_POST['hour'];
                $date = $_POST['date_start'];
                $timestamp = strtotime($date);
                $day = strtolower(date('l', $timestamp));
                //get opening hours for this day
                

                if(!empty($currentTime) && is_numeric(substr($currentTime, 0, 1)) ) {
                    if(substr($currentTime, -1)=='M'){
                        $currentTime = DateTime::createFromFormat('h:i A', $currentTime);
                        if($currentTime){
                            $currentTime = $currentTime->format('Hi');            
                        }

                        //
                    } else {
                        $currentTime = DateTime::createFromFormat('H:i', $currentTime);
                        if($currentTime){
                            $currentTime = $currentTime->format('Hi');
                        }
                    }
                    
                } 

                $opening_hours = get_post_meta( $_POST['listing_id'], '_'.$day.'_opening_hour', true);
                $closing_hours = get_post_meta( $_POST['listing_id'], '_'.$day.'_closing_hour', true);
                $ajax_out['free_places'] = 0;
                if(empty($opening_hours) && empty($closing_hours)){
                    $ajax_out['free_places'] = 0;
                } else {
                    $storeSchedule = array(
                        'opens' => $opening_hours,
                        'closes' => $closing_hours
                    );
                    
                    $startTime = $storeSchedule['opens'];
                    $endTime = $storeSchedule['closes'];
                    if(is_array($storeSchedule['opens'])){
                            foreach ($storeSchedule['opens'] as $key => $start_time) {
                                # code...
                                $end_time = $endTime[$key];
                               
                                if(!empty($start_time) && is_numeric(substr($start_time, 0, 1)) ) {
                                    if(substr($start_time, -1)=='M'){
                                        $start_time = DateTime::createFromFormat('h:i A', $start_time);
                                        if($start_time){
                                            $start_time = $start_time->format('Hi');            
                                        }
     
                                        //
                                    } else {
                                        $start_time = DateTime::createFromFormat('H:i', $start_time);
                                        if($start_time){
                                            $start_time = $start_time->format('Hi');
                                        }
                                    }
                                    
                                } 
                                   //create time objects from start/end times and format as string (24hr AM/PM)
                                if(!empty($end_time)  && is_numeric(substr($end_time, 0, 1))){
                                    if(substr($end_time, -1)=='M'){
                                        $end_time = DateTime::createFromFormat('h:i A', $end_time);         
                                        if($end_time){
                                            $end_time = $end_time->format('Hi');
                                        }
                                    } else {
                                        $end_time = DateTime::createFromFormat('H:i', $end_time);
                                        if($end_time){
                                            $end_time = $end_time->format('Hi');
                                        }
                                    }
                                } 
                               
                                if($end_time == '0000'){
                                    $end_time = 2400;
                                }

                                if((int)$start_time > (int)$end_time ) {
                                    // midnight situation
                                    $end_time = 2400 + (int)$end_time;
                                }

                               
                                // check if current time is within the range
                                if (((int)$start_time <= (int)$currentTime) && ((int)$currentTime <= (int)$end_time)) {
                                     $ajax_out['free_places'] = 1;
                                } 
                                
                            }
                    } else {
                         $ajax_out['free_places'] = 0;
                    }   
                } 
            }
            
            
            
          

        } else {
            $ajax_out['free_places'] = self :: count_free_places( $_POST['listing_id'], $_POST['date_start'], $_POST['date_end'], $slot );    
        }
        $multiply = 1;
        if(isset($_POST['adults'])) $multiply = $_POST['adults']; 
        if(isset($_POST['tickets'])) $multiply = $_POST['tickets'];
        
        
        $coupon = (isset($_POST['coupon'])) ? $_POST['coupon'] : false ;
        $services = (isset($_POST['services'])) ? $_POST['services'] : false ;
        // calculate price for all
        $decimals = get_option('listeo_number_decimals',2);
        $hour_start = (isset($_POST['hour'])) ? $_POST['hour']: false;
        $hour_end = (isset($_POST['end_hour'])) ? $_POST['end_hour']: false;
        if($slot && get_post_meta($_POST['listing_id'], '_count_by_hour', true) ){
            $slot = json_decode(wp_unslash($slot));
            //get hours and date to check reservation
            $hours = explode(' - ', $slot[0]);
            $hour_start = date("H:i", strtotime($hours[0]));

            $hour_end = date("H:i", strtotime($hours[1]));
       
        }
        if ($hour_end && $hour_start &&  get_post_meta($_POST['listing_id'], '_count_by_hour',true)) {
            if(!$slot){
                $start = $_POST['hour'];
                $end = $_POST['end_hour'];
                if (!empty($start) && is_numeric(substr($start, 0, 1))) {
                    if (substr($start, -1) == 'M') {
                        $start = DateTime::createFromFormat('h:i A', $start);
                        if ($start) {
                            $hour_start = $start->format('H:i');
                        }

                        //
                    } else {
                        $start = DateTime::createFromFormat('H:i', $start);
                        if ($start) {
                            $hour_start = $start->format('H:i');
                        }
                    }
                }
                if (!empty($end) && is_numeric(substr($end, 0, 1))) {
                    if (substr($end, -1) == 'M') {
                        $end = DateTime::createFromFormat('h:i A', $end);
                        if ($end) {
                            $hour_end = $end->format('H:i');
                        }

                        //
                    } else {
                        $end = DateTime::createFromFormat('H:i', $end);
                        if ($end) {
                            $hour_end = $end->format('H:i');
                        }
                    }
                } 
            }
          
            $price = self::calculate_price_per_hour($_POST['listing_id'],  $_POST['date_start'], $_POST['date_end'], $hour_start, $hour_end, $multiply, $services, '');
            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
            error_log($price);
            $ajax_out['price'] = number_format_i18n($price, $decimals);
            if (!empty($coupon)) {
                $price_discount = self::calculate_price_per_hour($_POST['listing_id'],  $_POST['date_start'], $_POST['date_end'], $hour_start, $hour_end, $multiply, $services, $coupon);
                $ajax_out['price_discount'] = number_format_i18n($price_discount, $decimals);
            }
        } else {
            $price = self::calculate_price($_POST['listing_id'],  $_POST['date_start'], $_POST['date_end'], $multiply, $services, '');
            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
            error_log($price);
            $ajax_out['price'] = number_format_i18n($price, $decimals);


            if (!empty($coupon)) {
                $price_discount = self::calculate_price($_POST['listing_id'],  $_POST['date_start'], $_POST['date_end'], $multiply, $services, $coupon);
                $ajax_out['price_discount'] = number_format_i18n($price_discount, $decimals);
            }
        }

        // MRJ - We add another output to the response, showing the amount of commission
        $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 10 ));
		$pctcommission = (int) $options['percentage'];
        $original_price = $price / (1 + ($pctcommission / 100));
        $commission_amount = $price - $original_price;
        $ajax_out['commission'] = number_format_i18n($commission_amount, $decimals);
        // MRJ END

        wp_send_json_success( $ajax_out );

    }


    public function check_if_coupon_exists($coupon){
            global $wpdb;
            $title = sanitize_text_field($coupon);
            $sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1;", $title );
            //check if coupon with that code exits
            $coupon_id = $wpdb->get_var( $sql );
            
            return ($coupon_id) ? true : false ;
    }

    public  function ajax_validate_coupon(){
        $listing_id = $_POST['listing_id'];
        $coupon = $_POST['coupon'];
        $coupons = (isset($_POST['coupons'])) ? $_POST['coupons'] : false ; 
        $price = (isset($_POST['price'])) ? $_POST['price'] : false;
        // if price is in format like 2.380 make sure it gets to the function as 2380
        

        if($price){
            $price = str_replace('.', '', $price);        
            $price  = (float) filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }
        if(empty($coupon)){
            $ajax_out['error'] = true;
            $ajax_out['error_type'] = 'no_coupon';
            $ajax_out['message'] = esc_html__('Coupon was not provided','listeo_core');
            wp_send_json( $ajax_out );
        }

        if(! self::check_if_coupon_exists($coupon) ){
            $ajax_out['error'] = true;
            $ajax_out['error_type'] = 'no_coupon_exists';
            $ajax_out['message'] = esc_html__('This coupon does not exist','listeo_core');
            wp_send_json( $ajax_out );
        }
        $wc_coupon = new WC_Coupon($coupon);
        
        //check price 
        if($wc_coupon->get_individual_use()){
            
            if(isset($coupons) && is_array($coupons) && count($coupons) > 1){
                $ajax_out['error'] = true;
                $ajax_out['error_type'] = 'coupon_used_once';
                $ajax_out['message'] =  __( 'This coupons can\'t be used with others.', 'listeo_core' );
                wp_send_json( $ajax_out );
            }
        }


        if($wc_coupon->get_minimum_amount() > 0 && $wc_coupon->get_minimum_amount() >= $price ) {
            $ajax_out['error'] = true;
            $ajax_out['error_type'] = 'coupon_minimum_spend';
            $ajax_out['message'] = sprintf( __( 'The minimum spend for this coupon is %s.', 'listeo_core' ), wc_price( $wc_coupon->get_minimum_amount() ) );
            wp_send_json( $ajax_out );
        }  

        if($wc_coupon->get_maximum_amount() > 0 && $wc_coupon->get_maximum_amount() < $price ) {
            $ajax_out['error'] = true;
            $ajax_out['error_type'] = 'coupon_maximum_spend';
            $ajax_out['message'] = sprintf( __( 'The maximum spend for this coupon is %s.', 'listeo_core' ), wc_price( $wc_coupon->get_maximum_amount() ) );
            wp_send_json( $ajax_out );
        }

        //validate_coupon_user_usage_limit
        $user_id = get_current_user_id();
        if($wc_coupon->get_usage_limit_per_user() && $user_id){
            $data_store  = $wc_coupon->get_data_store();
            $usage_count = $data_store->get_usage_by_user_id( $wc_coupon, $user_id );
            
            if ( $usage_count >= $wc_coupon->get_usage_limit_per_user() ) {
               $ajax_out['error'] = true;
                $ajax_out['error_type'] = 'coupon_limit_used';
                $ajax_out['message'] = sprintf( __( 'Coupon usage limit has been reached', 'listeo_core' ), wc_price( $wc_coupon->get_maximum_amount() ) );
                wp_send_json( $ajax_out );
            }   
        }
       
        if ( $wc_coupon->get_date_expires() &&  time() > $wc_coupon->get_date_expires()->getTimestamp()  ) {
                $ajax_out['error'] = true;
                $ajax_out['error_type'] = 'coupon_expired';
                $ajax_out['message'] = sprintf( __( 'This coupon has expired.', 'listeo_core' ), wc_price( $wc_coupon->get_maximum_amount() ) );
                 wp_send_json( $ajax_out );
        }

        //check author of coupon, check if he is admin
        $author_ID = get_post_field( 'post_author', $wc_coupon->get_ID() );
        $authorData = get_userdata( $author_ID );
        if (in_array( 'administrator', $authorData->roles)):
            $admins_coupon = true;
        else:
            $admins_coupon = false;
        endif;
        
        if($wc_coupon->get_usage_limit()>0) {

             $usage_left = $wc_coupon->get_usage_limit() - $wc_coupon->get_usage_count();
            
            if ($usage_left > 0) {
                
                if($admins_coupon){
                        $ajax_out['success'] = true;
                        $ajax_out['coupon'] = $coupon;
                        wp_send_json( $ajax_out );
                } else {
                   $available_listings = $wc_coupon->get_meta('listing_ids');
                    $available_listings_array = explode(',',$available_listings);
                    if(in_array($listing_id,$available_listings_array)) {
                        $ajax_out['success'] = true;
                        $ajax_out['coupon'] = $coupon;
                        wp_send_json( $ajax_out );
                    } else {
                        $ajax_out['error'] = true;
                        $ajax_out['error_type'] = 'coupon_wrong_listing';
                        $ajax_out['message'] =  esc_html__('This coupon is not applicable for this listing','listeo_core');
                        wp_send_json( $ajax_out );
                    } 
                }

                
                
            } 
            else {
                $ajax_out['error'] = true;
                $ajax_out['error_type'] = 'coupon_limit_used';
                $ajax_out['message'] =  esc_html__('Coupon usage limit has been reached','listeo_core');
                wp_send_json( $ajax_out );
            }  
             
        } else {

            if($admins_coupon){
                    $ajax_out['success'] = true;
                    $ajax_out['coupon'] = $coupon;
                    wp_send_json( $ajax_out );
            } else {
                $available_listings = $wc_coupon->get_meta('listing_ids');
                $available_listings_array = explode(',',$available_listings);
                if(in_array($listing_id,$available_listings_array)) {
                    $ajax_out['success'] = true;
                    $ajax_out['coupon'] = $coupon;
                    $ajax_out['message'] =  esc_html__('This coupon is not applicable for this listing','listeo_core');
                    wp_send_json( $ajax_out );
                } else {
                    $ajax_out['error'] = true;
                    $ajax_out['error_type'] = 'coupon_wrong_listing';
                    $ajax_out['message'] =  esc_html__('This coupon is not applicable for this listing','listeo_core');
                    wp_send_json( $ajax_out );
                }
            }

            
        }
       
    }


    public static function ajax_calculate_booking_form_price(){
        
        
        $price          = sanitize_text_field($_POST['price']);
        $coupon         = sanitize_text_field($_POST['coupon']);

        if(!empty($coupon)) {
            $coupons = explode(',',$coupon);
            foreach ($coupons as $key => $new_coupon) {
                $price = self::apply_coupon_to_price($price,$new_coupon);
            }    
        }
        
        if($price != $_POST['price']){
            $ajax_out['price'] = $price;
            wp_send_json( $ajax_out );
        } else {
            wp_send_json_success();
        }
    }

    public static function ajax_calculate_price( ) {
        $listing_id = $_POST['listing_id'];
        $tickets = isset($_POST['tickets']) ? $_POST['tickets'] : 1 ;
         
        
        $normal_price       = (float) get_post_meta ( $listing_id, '_normal_price', true);
        $reservation_price  =  (float) get_post_meta ( $listing_id, '_reservation_price', true);
        $services_price     = 0;
        $mandatory_fees = get_post_meta($listing_id, "_mandatory_fees", true);
        // MRJ
        // $mandatory_fees[] = ["Title", "Guest Commission", "Price", "35"]; // MRJ not required here
        if (is_array($mandatory_fees) && !empty($mandatory_fees)) {
            foreach ($mandatory_fees as $key => $fee) {
                $services_price += (float) $fee['price'];
            }
        }


        if(isset($_POST['services'])){
            $services = $_POST['services'];
        
            if(isset($services) && !empty($services)){

                $bookable_services = listeo_get_bookable_services($listing_id);
                $countable = array_column($services,'value');
        
                $i = 0;
                foreach ($bookable_services as $key => $service) {
                    
                    if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                        //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                        $services_price +=  listeo_calculate_service_price($service, $tickets, 1, $countable[$i] );
                       
                       $i++;
                    }
                   
                
                } 
            }
          
        }
        $total_price = ($normal_price * $tickets) + $reservation_price + $services_price;
        $decimals = get_option('listeo_number_decimals',2);
        $ajax_out['price'] = number_format_i18n($total_price,$decimals);
        //check if there's coupon
        $coupon = (isset($_POST['coupon'])) ? $_POST['coupon'] : false ;
        if($coupon) {
            $sale_price = $total_price;
            $coupons = explode(',',$coupon);
            foreach ($coupons as $key => $new_coupon) {
                $total_price = self::apply_coupon_to_price($total_price,$new_coupon);
            }
            $ajax_out['price_discount'] = number_format_i18n($total_price,$decimals);
        }
        
        wp_send_json_success( $ajax_out );
    }


    public static function apply_coupon_to_price($price, $coupon_code){

            if($price == 0) {
                return 0;
            }
            if(!$coupon_code) {
                return $price;
            }


        // Sanitize coupon code.
            $coupon_code = wc_format_coupon_code( $coupon_code );

            // Get the coupon.
            $the_coupon = new WC_Coupon( $coupon_code );
            if($the_coupon) {

                $amount = $the_coupon->get_amount();
                if($the_coupon->get_discount_type() == 'fixed_product'){
                    $discounted = $price - $amount;
                    return ($discounted < 0 ) ? 0 : $discounted ;
                } else {
                    return $price - ($price *  ($amount / 100) ) ;
                }    
            } else {
                return $price;
            }
            

    }

    public static function ajax_update_slots( ) {
           // get slots
        
            $listing_id = $_POST['listing_id'];
            $date_end = $_POST['date_start'];
            $date_start = $_POST['date_end'];
            
            $dayofweek = date('w', strtotime($date_start));
            
            $un_slots = get_post_meta( $listing_id, '_slots', true );
            
            $_slots = self :: get_slots_from_meta ( $listing_id );

            //sloty na dany dzien:
            if($dayofweek == 0){
                $actual_day = 6;    
            } else {
                $actual_day = $dayofweek-1;    
            }
            
           if(is_array($_slots) && !empty($_slots)){
            $_slots_for_day = $_slots[$actual_day];
            } else {
                $_slots_for_day = false;
            }
            $ajax_out = false;
            $new_slots = array();

            //MRJ - Get today's date and get the current hour, 3rd line saves the end date of the booking period
            //    - Note that this function uses a start and end date which are always the same in the case of the 
            //    - booking widget for a slot, which only ever deals with one day at a time.
            $today = date("Y-m-d");
            $hour_now = date("H");
            $pres_start_date = $date_end;
        //MRJ - END            if(is_array($_slots_for_day) && !empty($_slots_for_day)){
            if (is_array($_slots_for_day) && !empty($_slots_for_day)) {
                foreach ($_slots_for_day as $key => $slot) {
                    //$slot = json_decode( wp_unslash($slot) );
                    
                    $places = explode( '|', $slot );
                    $free_places = $places[1];


                    //get hours and date to check reservation
                    $hours = explode( ' - ', $places[0] );
                    $hour_start = date( "H:i:s", strtotime( $hours[0] ) );
                    $hour_end = date( "H:i:s", strtotime( $hours[1] ) );

                     // add hours to dates
                    $date_start = $_POST['date_start']. ' ' . $hour_start;
                    $date_end = $_POST['date_end']. ' ' . $hour_end;
  

                    $result = self ::  get_slots_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'reservation' ) );
                    $reservations_amount = count( $result );  
                  

                    // $free_places -= self :: temp_reservation_aval( array( 'listing_id' => $listing_id, 'date_start' => $date_start, 'date_end' => $date_end) );

                    $free_places -= $reservations_amount;
                    if($free_places>0){
                        // MRJ - For each slot found for the current day, checks to see if the hour that the slot starts
                        //     - at is earlier than the current hour, and if so ignores it. Otherwise, deals with adding 
                        //     - the slot
                        $hour_bit_of_slot = date( "H", strtotime( $hours[0] ) );
                        
                        
                        if( $today == $pres_start_date ) {
                            if ( $hour_now+1 < $hour_bit_of_slot) {
                                $new_slots[] = $places[0].'|'.$free_places;
                            }
                        } else {
                            $new_slots[] = $places[0].'|'.$free_places;
                        }                   
                    }
                }
                
                
                ?>

                <?php 
                $days_list = array(
                        0   => __('Monday','listeo_core'),
                        1   => __('Tuesday','listeo_core'),
                        2   => __('Wednesday','listeo_core'),
                        3   => __('Thursday','listeo_core'),
                        4   => __('Friday','listeo_core'),
                        5   => __('Saturday','listeo_core'),
                        6   => __('Sunday','listeo_core'),
                ); 
                ob_start();?><input id="slot" type="hidden" name="slot" value="" />
                <input id="listing_id" type="hidden" name="listing_id" value="<?php echo $listing_id; ?>" 
                <?php
                   
                foreach( $new_slots as $number => $slot) { 
                    
                    $slot = explode('|' , $slot); ?>
                    <!-- Time Slot -->
                    <div class="time-slot" day="<?php echo $actual_day; ?>">
                        <input type="radio" name="time-slot" id="<?php echo $actual_day.'|'.$number; ?>" value="<?php echo $actual_day.'|'.$number; ?>">
                        <label for="<?php echo $actual_day.'|'.$number; ?>">
                            <p class="day"><?php //echo $days_list[$day]; ?></p>
                            <strong><?php echo $slot[0]; ?></strong>
                            <span><?php echo $slot[1]; esc_html_e(' slots available','listeo_core') ?></span>
                        </label>
                    </div>
                    <?php } 
                $ajax_out = ob_get_clean();
            } else {
                //no slots for today
            }
            wp_send_json_success( $ajax_out );
            
    }



    public static function ajax_listeo_bookings_renew_booking() {
        
        //check if booking can be renewed
        $booking_data =  self :: get_booking(sanitize_text_field($_POST['booking_id']));

      
        if($booking_data['status'] == 'expired') {
            $listing_type = get_post_meta ( $booking_data['listing_id'], '_listing_type', true );
            if( $listing_type == 'rental'){
                $has_free = self :: count_free_places( $booking_data['listing_id'], $booking_data['date_start'], $booking_data['date_end'] );   

                if($has_free <= 1){
                     wp_send_json_success( self :: set_booking_status( sanitize_text_field($_POST['booking_id']), 'confirmed') );             
                } else {
                    wp_send_json_error( );
                }
            } else {

                  $result = self :: get_bookings( $booking_data['date_start'], $booking_data['date_end'], array( 'listing_id' => $booking_data['listing_id'], 'type' => 'reservation' ) );
                  if(!empty($result)){
                    wp_send_json_error( );
                } else {
                    wp_send_json_success( self :: set_booking_status( sanitize_text_field($_POST['booking_id']), 'confirmed') );  
                }
                    
            } 

        }
                
            
    }
    /**
    * Ajax bookings dashboard
    *
    *
    */
    public static function ajax_listeo_bookings_manage(  )  {
        $current_user_id = get_current_user_id();
        // when we only changing status
        if ( isset( $_POST['status']) ) {
            // changing status only for owner and admin
            //if ( $current_user_id != $owner_id && ! is_admin() ) return;
            wp_send_json_success( self :: set_booking_status( sanitize_text_field($_POST['booking_id']), sanitize_text_field($_POST['status'])) );              
           
        }

        $args = array (
            'owner_id' => get_current_user_id(),
            'type' => 'reservation'
        );
        
        $offset = ( absint( $_POST['page'] ) - 1 ) * absint( get_option('posts_per_page') );
        $limit =  get_option('posts_per_page');

        if ( isset($_POST['listing_id']) &&  $_POST['listing_id'] != 'show_all'  ) $args['listing_id'] = $_POST['listing_id'];
        if ( isset($_POST['listing_status']) && $_POST['listing_status'] != 'show_all'  ) $args['status'] = $_POST['listing_status'];
        if ( isset($_POST['booking_author']) && $_POST['booking_author'] != 'show_all'  ) $args['bookings_author'] = $_POST['booking_author'];


        if (  $_POST['dashboard_type'] != 'user' ){
            
            if($_POST['date_start']==''){
                $ajax_out = self :: get_newest_bookings( $args, $limit, $offset ); 
                $bookings_max_number = listeo_count_bookings(get_current_user_id(),$args['status'], $args['bookings_author']);    
            } else {
                
                $ajax_out = self :: get_bookings( $_POST['date_start'], $_POST['date_end'], $args, 'booking_date', $limit, $offset,'users' );    
                $bookings_max_number = self :: get_bookings_max( $_POST['date_start'], $_POST['date_end'], $args, 'booking_date');

            }
        }
           

//        if user dont have listings show his reservations
        if ( isset( $_POST['dashboard_type']) && $_POST['dashboard_type'] == 'user' ) {
            unset( $args['owner_id'] );
            unset($args['status']);
            unset($args['listing_id']);
            
            $args['bookings_author'] = get_current_user_id();
            if($_POST['date_start']==''){
                $ajax_out = self :: get_newest_bookings( $args, $limit, $offset ); 
                $bookings_max_number = listeo_count_my_bookings(get_current_user_id(),$args['status']);    
            } else {
                $ajax_out = self :: get_bookings( $_POST['date_start'], $_POST['date_end'], $args, 'booking_date', $limit, $offset, 'users' );    
                $bookings_max_number = self :: get_bookings_max( $_POST['date_start'], $_POST['date_end'], $args, 'booking_date');
            }

        }
        $result = array();
        $template_loader = new Listeo_Core_Template_Loader;
        $max_number_pages = ceil($bookings_max_number/$limit);
        
        ob_start();
        if($ajax_out){
        
            foreach ($ajax_out as $key => $value) {
                if ( isset($_POST['dashboard_type']) && $_POST['dashboard_type'] == 'user' ) {
                    $template_loader->set_template_data( $value )->get_template_part( 'booking/content-user-booking' );      
                } else {
                    $template_loader->set_template_data( $value )->get_template_part( 'booking/content-booking' );      
                }
                
            }
        } 
      
        $result['pagination'] = listeo_core_ajax_pagination( $max_number_pages, absint( $_POST['page'] ) );
        $result['html'] = ob_get_clean();
        wp_send_json_success( $result );

    }


    /**
    * Insert booking with args
    *
    * @param  array $args list of parameters
    *
    */
    public static function insert_booking( $args )  {

        global $wpdb;
        
        $insert_data = array(
            'bookings_author' => $args['bookings_author'] ?? get_current_user_id(),
            'owner_id' => $args['owner_id'],
            'listing_id' => $args['listing_id'],
            'date_start' => date( "Y-m-d H:i:s", strtotime( $args['date_start'] ) ),
            'date_end' => date( "Y-m-d H:i:s", strtotime( $args['date_end'] ) ),
            'comment' =>  $args['comment'],
            'type' =>  $args['type'],
            'created' => current_time('mysql')
        );

        if ( isset( $args['order_id'] ) ) $insert_data['order_id'] = $args['order_id'];
        if ( isset( $args['expiring'] ) ) $insert_data['expiring'] = $args['expiring'];
        if ( isset( $args['status'] ) ) $insert_data['status'] = $args['status'];
        if ( isset( $args['price'] ) ) $insert_data['price'] = $args['price'];

        $wpdb -> insert( $wpdb->prefix . 'bookings_calendar', $insert_data );

        return  $wpdb -> insert_id;

    }

    /**
    * Set booking status - we changing booking status only by this function
    *
    * @param  array $args list of parameters
    *
    * @return number of deleted records
    */
    public static function set_booking_status( $booking_id, $status ) {

        global $wpdb;

        $booking_id = sanitize_text_field($booking_id);
        $status = sanitize_text_field($status);
        $booking_data = $wpdb -> get_row( 'SELECT * FROM `'  . $wpdb->prefix .  'bookings_calendar` WHERE `id`=' . esc_sql( $booking_id ), 'ARRAY_A' );
        if(!$booking_data){
            return;
        }

        $user_id = $booking_data['bookings_author']; 
        $owner_id = $booking_data['owner_id'];
        $current_user_id = get_current_user_id();

        // get information about users
        $user_info = get_userdata( $user_id );
        
        $owner_info = get_userdata( $owner_id );
        $comment = json_decode($booking_data['comment']);
        $payment_option = get_post_meta($booking_data['listing_id'], '_payment_option', true);
        // only one time clicking blocking
        if ( $booking_data['status'] == $status ) return;
        

        switch ( $status ) 
        {

            // this is status when listing waiting for approval by owner
            case 'waiting' :

                $update_values['status'] = 'waiting';

                // mail for user
                $mail_to_user_args = array(
                    'email' => $user_info->user_email,
                    'booking'  => $booking_data,
                );
                do_action('listeo_mail_to_user_waiting_approval',$mail_to_user_args);
                // wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), __( 'Your reservation waiting for be approved by owner!', 'listeo_core' ) );
                
                // mail for owner
                $mail_to_owner_args = array(
                    'email'     => $owner_info->user_email,
                    'booking'  => $booking_data,
                );
                
                do_action('listeo_mail_to_owner_new_reservation',$mail_to_owner_args);
                // wp_mail( $owner_info->user_email, __( 'Welcome owner', 'listeo_core' ), __( 'In your panel waiting new reservation to be accepted!', 'listeo_core' ) );

            break;

            // this is status when listing is confirmed by owner and waiting to payment
            
            case 'pay_to_confirm' :
            case 'confirmed' :

                // get woocommerce product id
                $product_id = get_post_meta( $booking_data['listing_id'], 'product_id', true);

                // calculate when listing will be expired when will bo not pays
                $expired_after = get_post_meta( $booking_data['listing_id'], '_expired_after', true);
               
                $default_booking_expiration_time = get_option('listeo_default_booking_expiration_time');
               
                if(empty($expired_after)) {
                    $expired_after = $default_booking_expiration_time;
                }
               
                if(!empty($expired_after) && $expired_after > 0){
                    define( 'MY_TIMEZONE', (get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : date_default_timezone_get() ) );
                    date_default_timezone_set( MY_TIMEZONE );
                    $expiring_date = date( "Y-m-d H:i:s", strtotime('+'.$expired_after.' hours') );    
                }
               

              
                $instant_booking = apply_filters('listeo_instant_booking', get_post_meta( $booking_data['listing_id'], '_instant_booking', true));
                
                if($instant_booking) {

                    $mail_to_user_args = array(
                        'email' => $user_info->user_email,
                        'booking'  => $booking_data,
                    ); 
                    do_action('listeo_mail_to_user_instant_approval',$mail_to_user_args);
                    
                    // mail for owner
                    $mail_to_owner_args = array(
                        'email'     => $owner_info->user_email,
                        'booking'  => $booking_data,
                    );
                    
                    do_action('listeo_mail_to_owner_new_instant_reservation',$mail_to_owner_args);

                }
                if($payment_option == 'pay_cash') {
                    // mail for user
                    //wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), __( 'Your is paid!', 'listeo_core' ) );

               
                    $mail_args = array(
                        'email'     => $user_info->user_email,
                        'booking'  => $booking_data,
                    );
                    do_action('listeo_mail_to_user_pay_cash_confirmed', $mail_args);                
                    $update_values['expiring'] = '';
                    
                }
                
                // for free listings
                if ( $booking_data['price'] == 0 )
                {

                    // check if booking_data has coupon
                    $coupon = (isset($comment->coupon) && !empty($comment->coupon)) ? $comment->coupon : false;
                    // this is woocommerce coupon, check if it has usage limits
                    if ($coupon) {
                        $coupons = explode(',', $coupon);
                        foreach ($coupons as $key => $new_coupon) {
                            $coupon = new WC_Coupon($new_coupon);
                            $coupon->increase_usage_count();
                        }
                    }
                    // mail for user
                    //wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), __( 'Your is paid!', 'listeo_core' ) );
                    $mail_args = array(
                    'email'     => $user_info->user_email,
                    'booking'  => $booking_data,
                    );
                    do_action('listeo_mail_to_user_free_confirmed',$mail_args);

                    $update_values['status'] = 'paid';
                    $update_values['expiring'] = '';

                    break;
                    
                }



                $first_name = (isset($comment->first_name) && !empty($comment->first_name)) ? $comment->first_name : get_user_meta( $user_id, "billing_first_name", true) ;
                
                $last_name = (isset($comment->last_name) && !empty($comment->last_name)) ? $comment->last_name : get_user_meta( $user_id, "billing_last_name", true) ;
                
                $phone = (isset($comment->phone) && !empty($comment->phone)) ? $comment->phone : get_user_meta( $user_id, "billing_phone", true) ;
                
                $email = (isset($comment->email) && !empty($comment->email)) ? $comment->email : get_user_meta( $user_id, "user_email", true) ;
                
                $billing_address_1 = (isset($comment->billing_address_1) && !empty($comment->billing_address_1)) ? $comment->billing_address_1 : '';
                
                $billing_city = (isset($comment->billing_city) && !empty($comment->billing_city)) ? $comment->billing_city : '';
                
                $billing_postcode = (isset($comment->billing_postcode) && !empty($comment->billing_postcode)) ? $comment->billing_postcode : '';
                $billing_state = (isset($comment->billing_state) && !empty($comment->billing_state)) ? $comment->billing_state : '';
                
                $billing_country = (isset($comment->billing_country) && !empty($comment->billing_country)) ? $comment->billing_country : ''; 

                $coupon = (isset($comment->coupon) && !empty($comment->coupon)) ? $comment->coupon : false;

                $address = array(
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'address_1'  => $billing_address_1,
                    //billing_address_2
                    'city'       => $billing_city,
                    'state'     => $billing_state,
                    'postcode'  => $billing_postcode,
                    'country'   => $billing_country,
                    
                );
//                listeo_write_log($address);

                if(empty($booking_data['order_id'])){


                    if (empty($product_id) || FALSE === get_post_status($product_id)) {
                        //check if post with post_id exists

                        //we need to create product
                        $product_id = listeo_create_product($booking_data['listing_id']);
                    }
                // creating woocommerce order
                    $order = wc_create_order();
                    
                    $price_before_coupons = (isset($comment->price) && !empty($comment->price)) ? $comment->price : $booking_data['price'];

                    // $args['totals']['subtotal'] = $price_before_coupons;
                    // $args['totals']['total'] = $price_before_coupons;
                    $comment = json_decode($booking_data['comment']);
                    $product = wc_get_product($product_id);
                    $product->set_price($price_before_coupons);
                    $order->add_product($product, 1 );
              
                   
                    $order->set_address( $address, 'billing' );
                    $order->set_address( $address, 'shipping' );
                    $order->set_billing_phone( $phone );
                    $order->set_customer_id($user_id);
                    $order->set_billing_email( $email );
                    // if(isset($expiring_date)){
                    //     $order->set_date_paid( strtotime( $expiring_date ) );    
                    // }

                    
             
                    $note = listeo_get_extra_services_html($comment->service);

                    // Add the note
                    $order->add_order_note( $note );

                   $custom_fields = array(
                        '_vat_id',
                   );
                   foreach ($custom_fields as $key) {
                        $value = get_booking_meta($booking_id, $key);
                        
                        if(!empty($value)){
                            $order->update_meta_data($key, $value);
                        }
                    # code...
                   }

   

                    //TODO IF RENEWAL
                    $order->set_prices_include_tax('yes');
                    if ($coupon) {
                        
                        $coupons = explode(',', $coupon);
                        foreach ($coupons as $key => $new_coupon) {
                            
                            $order->apply_coupon(sanitize_text_field($new_coupon));
                        }
                    }

                    $payment_url = $order->get_checkout_payment_url();
                    
                 
                    $order->calculate_totals();
                    $order->save();
                    
                    $order->update_meta_data('booking_id', $booking_id);
                    $order->update_meta_data('owner_id', $owner_id);
                    //$order->update_meta_data('billing_phone', $phone);
                    $order->update_meta_data('listing_id', $booking_data['listing_id']);
                    if(isset($comment->service)){
                        
                        $order->update_meta_data('listeo_services', $comment->service);
                    }

                    $order->save_meta_data();

                   
                   
                    $update_values['order_id'] = $order->get_order_number();
                
                }


                if(isset($expiring_date)){
                    $update_values['expiring'] = $expiring_date;
                } else {
                    $expiring_date = false;
                }
                
                $update_values['status'] = $status;
                
                 // mail for user
                 //wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), sprintf( __( 'Your reservation waiting for payment! Ple ase do it before %s hours. Here is link: %s', 'listeo_core' ), $expired_after, $payment_url  ) );
                 $mail_args = array(
                    'email'         => $user_info->user_email,
                    'booking'       => $booking_data,
                    'expiration'    => $expiring_date,
                    'payment_url'   => $payment_url
                    );

                if ($payment_option != 'pay_cash') {
                do_action('listeo_mail_to_user_pay',$mail_args);
                }

             //end confirmed/ paid to confirm                  
            break;





            // this is status when listing is confirmed by owner and already paid
            case 'paid' :

                // mail for owner
                //wp_mail( $owner_info->user_email, __( 'Welcome owner', 'listeo_core' ), __( 'Your client paid!', 'listeo_core' ) );
                $mail_to_owner_args = array(
                    'email'     => $owner_info->user_email,
                    'booking'  => $booking_data,
                );


                do_action('listeo_mail_to_owner_paid',$mail_to_owner_args);

                $mail_to_user_args = array(
                    'email'     => $user_info->user_email,
                    'booking'   => $booking_data,
                );

                
                do_action('listeo_mail_to_user_paid',$mail_to_user_args);
                 // mail for user
                // wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), __( 'Your is paid!', 'listeo_core' ) );

                 $update_values['status'] = 'paid';
                 $update_values['expiring'] = '';                               
                

            break;

            // this is status when listing is confirmed by owner and already paid
            case 'cancelled' :

                // mail for user
                //wp_mail( $user_info->user_email, __( 'Welcome traveler', 'listeo_core' ), __( 'Your reservation was cancelled by owner', 'listeo_core' ) );
                $mail_to_user_args = array(
                    'email'     => $user_info->user_email,
                    'booking'  => $booking_data,
                );
                do_action('listeo_mail_to_user_canceled',$mail_to_user_args);
                // delete order if exist
                if ( $booking_data['order_id'] )
                {
                    $order = wc_get_order( $booking_data['order_id'] );
                    $order->update_status( 'cancelled', __( 'Order is cancelled.', 'listeo_core' ) );
                }
                $comment = json_decode($booking_data['comment']);
                if(isset( $comment->tickets )){
                       $tickets_from_order = $comment->tickets;
                
                        $sold_tickets = (int) get_post_meta( $booking_data['listing_id'],"_event_tickets_sold",true); 
                        
                        update_post_meta( $booking_data['listing_id'],"_event_tickets_sold",$sold_tickets-$tickets_from_order); 

                }
             
                $update_values['status'] = 'cancelled';
                $update_values['expiring'] = '';  

            break;
             // this is status when listing is confirmed by owner and already paid
            case 'deleted' :

               
               if($owner_id == $current_user_id || $user_id == $current_user_id  ){


                    if ( $booking_data['order_id'] )
                    {
                        $order = wc_get_order( $booking_data['order_id'] );
                        //$order->update_status( 'cancelled', __( 'Order is cancelled.', 'listeo_core' ) );
                    }
               
                    return $wpdb -> delete( $wpdb->prefix . 'bookings_calendar', array( 'id' => $booking_id ) );
                }

            break;

             case 'expired' :

              

                 $update_values['status'] = 'expired';
                delete_post_meta($booking_data['listing_id'], "_listing_expires");                      
                

            break;
        }
        
        return $wpdb -> update( $wpdb->prefix . 'bookings_calendar', $update_values, array( 'id' => $booking_id ) );

    }

    
    /**
    * Delete all booking wih parameters
    *
    * @param  array $args list of parameters
    *
    * @return number of deleted records
    */
    public static function delete_bookings( $args )  {

        global $wpdb;

        return $wpdb -> delete( $wpdb->prefix . 'bookings_calendar', $args );

    }

    /**
    * Update owner reservation list by delecting old one and add new ones
    *
    * @param  number $listing_id post id of current listing
    *
    * @return string $dates array with two dates
    */
    public static function update_reservations( $listing_id, $dates ) {

        // delecting old reservations
        self :: delete_bookings ( array(
            'listing_id' => $listing_id,  
            'owner_id' => get_current_user_id(),
            'type' => 'reservation',
            'comment' => 'owner reservations') );

        // update by new one reservations
        foreach ( $dates as $date) {
           
            $date_now = strtotime("-1 days");
            $date_format    = strtotime($date);
    
            if($date_format>=$date_now) {
                
                self :: insert_booking( array(
                    'listing_id' => $listing_id,  
                    'type' => 'reservation',
                    'owner_id' => get_current_user_id(),
                    'date_start' => $date,
                    'date_end' => date( 'Y-m-d H:i:s', strtotime('+23 hours +59 minutes +59 seconds', strtotime($date) ) ),
                    'comment' =>  'owner reservations',
                    'order_id' => NULL,
                    'status' => 'owner_reservations'
                )); 
            }
        }

       
    }

    /**
    * Update listing special prices
    *
    * @param  number $listing_id post id of current listing
    * @param  array $prices with dates and prices
    *
    * @return string $prices array with special prices
    */
    public static function update_special_prices( $listing_id, $prices ) {

        // delecting old special prices
        self :: delete_bookings ( array(
            'listing_id' => $listing_id,  
            'owner_id' => get_current_user_id(),
            'type' => 'special_price') );

        // update by new one special prices
        foreach ( $prices as $date => $price) {
            
            self :: insert_booking( array(
                'listing_id' => $listing_id,  
                'type' => 'special_price',
                'owner_id' => get_current_user_id(),
                'date_start' => $date,
                'date_end' => $date,
                'comment' =>  $price,
                'order_id' => NULL,
                'status' => NULL
            ));
            
        }

    }

    /**
     * MRJ Test function
     */
    public static function tester(){
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
    }


    /**
    * Calculate price
    *
    * @param  number $listing_id post id of current listing
    * @param  date  $date_start since we checking
    * @param  date  $date_end to we checking
    *
    * @return number $price of all booking at all
    */
    public static function calculate_price( $listing_id, $date_start, $date_end, $multiply = 1, $services = false, $coupon= false ) {

        // get all special prices between two dates from listeo settings special prices
        $special_prices_results = self :: get_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'special_price' ) );

        $listing_type = get_post_meta( $listing_id, '_listing_type', true);

        // prepare special prices to nice array
        foreach ($special_prices_results as $result) 
        {
            $special_prices[ $result['date_start'] ] = $result['comment'];
        }


        // get normal prices from listeo listing settings
        $normal_price = (float) get_post_meta ( $listing_id, '_normal_price', true);
        $weekend_price = (float)  get_post_meta ( $listing_id, '_weekday_price', true);
        // MRJ
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log("normal_price: " . $normal_price);
        // MRJEND

        if(empty($weekend_price)){
            
            $weekend_price = $normal_price;
        }
        $reservation_price  =  (float) get_post_meta ( $listing_id, '_reservation_price', true);
        $_count_per_guest  = get_post_meta ( $listing_id, '_count_per_guest', true);
        $services_price = 0;

        $mandatory_fees = get_post_meta($listing_id, "_mandatory_fees", true);
        if (is_array($mandatory_fees) && !empty($mandatory_fees)) {
            foreach ($mandatory_fees as $key => $fee) {
                $services_price += (float) $fee['price'];

            }
        }
        
        if($listing_type == 'event'){
            if(isset($services) && !empty($services)){
                $bookable_services = listeo_get_bookable_services($listing_id);
                $countable = array_column($services,'value');
              
                $i = 0;
                foreach ($bookable_services as $key => $service) {
                    
                    if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                        //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                        $services_price +=  listeo_calculate_service_price($service, $multiply, 1, $countable[$i] );
                        
                       $i++;
                    }
                   
                
                } 
            }
            $price = $services_price+$reservation_price+$normal_price*$multiply;
            //coupon
            if(isset($coupon) && !empty($coupon)){
                $wc_coupon = new WC_Coupon($coupon);
                
                $coupons = explode(',',$coupon);
                foreach ($coupons as $key => $new_coupon) {
                    
                    $price = self::apply_coupon_to_price($price,$new_coupon);
                }
                
            }
            return $price;
        }
        // prepare dates for loop
        // TODO CHECK THIS
    // $format = "d/m/Y  H:i:s";
    //     $firstDay =  DateTime::createFromFormat($format, $date_start. '00:00:01' );
    //     $lastDay =  DateTime::createFromFormat($format, $date_end. '23:59:59')
    //     ;
    //
     
     
        if($listing_type != 'rental') {
            $firstDay = new DateTime( $date_start );
           
            $lastDay = new DateTime( $date_end . '23:59:59') ;
         
        } else {
            $firstDay = new DateTime( $date_start );
            $lastDay = new DateTime( $date_end );
            if(get_option('listeo_count_last_day_booking')){
                $lastDay = $lastDay->modify('+1 day');     
            }
            
        }
        $days_between = $lastDay->diff($firstDay)->format("%a");
        $days_count = ($days_between == 0) ? 1 : $days_between ;
        //fix for not calculating last day of leaving
        //if ( $date_start != $date_end ) $lastDay -> modify('-1 day');
        
        $interval = DateInterval::createFromDateString('1 day');
        
        $period = new DatePeriod( $firstDay, $interval, $lastDay );

        // at start we have reservation price
         $price = 0;
      
        foreach ( $period as $current_day ) {

            // get current date in sql format
            $date = $current_day->format("Y-m-d 00:00:00");
            $day = $current_day->format("N");

            if ( isset( $special_prices[$date] ) ) 
            {
                $price += $special_prices[$date];
            }
            else {
                $start_of_week = intval( get_option( 'start_of_week' ) ); // 0 - sunday, 1- monday
                // when we have weekends
                if($start_of_week == 0 ) {
                    if ( isset( $weekend_price ) && $day == 5 || $day == 6) {
                        $price += $weekend_price;
                    }  else { $price += $normal_price; }
                } else {
                    if ( isset( $weekend_price ) && $day == 6 || $day == 7) {
                        $price += $weekend_price;
                     }  else { $price += $normal_price; }
                } 

            }

        }
        if($_count_per_guest){
            $price = $price * (int) $multiply;
        }
        $services_price = 0;
        $mandatory_fees = get_post_meta($listing_id, "_mandatory_fees", true);
        if (is_array($mandatory_fees) && !empty($mandatory_fees)) {
            foreach ($mandatory_fees as $key => $fee) {
                $services_price += (float) $fee['price'];

            }
        }
        
        if(isset($services) && !empty($services)){
            $bookable_services = listeo_get_bookable_services($listing_id);
            $countable = array_column($services,'value');
          
            $i = 0;
            foreach ($bookable_services as $key => $service) {
                
                if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                    //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                    $services_price +=  listeo_calculate_service_price($service, $multiply, $days_count, $countable[$i] );
                    
                   $i++;
                }
               
            
            } 
        }
        
        $price += $reservation_price + $services_price;
        // MRJ
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log("Price in rental section: " . $price);
        // MRJ END

        //coupon
        if(isset($coupon) && !empty($coupon)){
            $wc_coupon = new WC_Coupon($coupon);
            
            $coupons = explode(',',$coupon);
            foreach ($coupons as $key => $new_coupon) {
                
                $price = self::apply_coupon_to_price($price,$new_coupon);
            }
            
        }

        
        
       // $endprice = round($price,2);

        $decimals = get_option('listeo_number_decimals',2);
        $endprice = number_format_i18n($price,$decimals);
        // MRJ
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log("endprice: " . $endprice);

        //return apply_filters('listeo_booking_price_calc',$price, $listing_id, $date_start, $date_end, $multiply , $services);
        $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
        $pctcommission = (int) $options['percentage'];
        $price  = $price + ($price * ($pctcommission / 100));
        return $price;



    }
    /**
    * Calculate price
    *
    * @param  number $listing_id post id of current listing
    * @param  date  $date_start since we checking
    * @param  date  $date_end to we checking
    *
    * @return number $price of all booking at all
    */
    public static function calculate_price_per_hour( $listing_id, $date_start, $date_end, $start_hour, $end_hour, $multiply = 1, $services = false, $coupon= false ) {

        // get all special prices between two dates from listeo settings special prices
        $special_prices_results = self :: get_bookings( $date_start, $date_end, array( 'listing_id' => $listing_id, 'type' => 'special_price' ) );

        $listing_type = get_post_meta( $listing_id, '_listing_type', true);

        // prepare special prices to nice array
        foreach ($special_prices_results as $result) 
        {
            $special_prices[ $result['date_start'] ] = $result['comment'];
        }


        // get normal prices from listeo listing settings
        $normal_price = (float) get_post_meta ( $listing_id, '_normal_price', true);
        $weekend_price = (float)  get_post_meta ( $listing_id, '_weekday_price', true);
        
        if(empty($weekend_price)){
            
            $weekend_price = $normal_price;
        }
        $time1 = strtotime($start_hour);
        $time2 = strtotime($end_hour);
        //count difference in hours if 2nd day
        // if($date_start != $date_end){
        //     $difference = round(abs($time2 - $time1) / 3600, 2) + 24;
        // } else {
        //     $difference = round(abs($time2 - $time1) / 3600, 2);
        // }
        if ($time2 <= $time1) {
            $time2 += 24 * 60 * 60; 
        }
        $difference = ($time2 - $time1) / (60 * 60);

        
       // $difference = round(abs($time2 - $time1) / 3600, 2);
        
        $reservation_price  =  (float) get_post_meta ( $listing_id, '_reservation_price', true);
        $_count_per_guest  = get_post_meta ( $listing_id, '_count_per_guest', true);
        $services_price = 0;
        
     
    
        $firstDay = new DateTime( $date_start );
        
        $lastDay = new DateTime( $date_end . '23:59:59') ;
        
       
        $days_between = $lastDay->diff($firstDay)->format("%a");
        $days_count = ($days_between == 0) ? 1 : $days_between ;
        //fix for not calculating last day of leaving
        //if ( $date_start != $date_end ) $lastDay -> modify('-1 day');
        
        $interval = DateInterval::createFromDateString('1 day');
        
        $period = new DatePeriod( $firstDay, $interval, $lastDay );

        // at start we have reservation price
         $price = 0;
   
        foreach ( $period as $current_day ) {

            // get current date in sql format
            $date = $current_day->format("Y-m-d 00:00:00");
            $day = $current_day->format("N");

            if ( isset( $special_prices[$date] ) ) 
            {
                $price += $special_prices[$date] * $difference;
            }
            else {
                $start_of_week = intval( get_option( 'start_of_week' ) ); // 0 - sunday, 1- monday
                // when we have weekends
                if($start_of_week == 0 ) {
                    if ( isset( $weekend_price ) && $day == 5 || $day == 6) {
                        $price += $weekend_price*$difference;
                    }  else { $price += $normal_price * $difference; }
                } else {
                    if ( isset( $weekend_price ) && $day == 6 || $day == 7) {
                        $price += $weekend_price * $difference;
                     }  else { $price += $normal_price * $difference; }
                } 

            }

        }
        if($_count_per_guest){
            $price = $price * (int) $multiply;
        }
        $services_price = 0;
        
        $mandatory_fees = get_post_meta($listing_id, "_mandatory_fees", true);
        if (is_array($mandatory_fees) && !empty($mandatory_fees)) {
            foreach ($mandatory_fees as $key => $fee) {
                $services_price += (float) $fee['price'];

            }
        }
        
        if(isset($services) && !empty($services)){
            $bookable_services = listeo_get_bookable_services($listing_id);
            $countable = array_column($services,'value');
          
            $i = 0;
            foreach ($bookable_services as $key => $service) {
                
                if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                    //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                    $services_price +=  listeo_calculate_service_price($service, $multiply, $days_count, $countable[$i] );
                    
                   $i++;
                }
               
            
            } 
        }
        
        $price += $reservation_price + $services_price;


        //coupon
        if(isset($coupon) && !empty($coupon)){
            $wc_coupon = new WC_Coupon($coupon);
            
            $coupons = explode(',',$coupon);
            foreach ($coupons as $key => $new_coupon) {
                
                $price = self::apply_coupon_to_price($price,$new_coupon);
            }
            
        }

        
        
       // $endprice = round($price,2);

        $decimals = get_option('listeo_number_decimals',2);
        $endprice = number_format_i18n($price,$decimals);

        //return apply_filters('listeo_booking_price_calc',$price, $listing_id, $date_start, $date_end, $multiply , $services);
        $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
        $pctcommission = (int) $options['percentage'];
        $price  = $price + ($price * ($pctcommission / 100));
        return $price;
    }

    /**
     * MRJ
     * Define a filter to modify the value calculate price returns and uplift it by the defined percentage
     */
    public static function mrjgcc_modify_calculate_price($price, $listing_id, $date_start, $date_end, $multiply, $services){
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
        $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
        $pctcommission = (int) $options['percentage'];
        error_log("IN PRICE MODIFY");
        error_log("Price in: " . $price);
        $price  = $price + ($price * ($pctcommission / 100));
        error_log("Price out: " . $price);
        error_log($pctcommission);
        return $price;
    } 

    /**
    * Get all reservation of one listing
    *
    * @param  number $listing_id post id of current listing
    * @param  array $dates 
    *
    */
    public static function get_reservations( $listing_id, $dates ) {

        // delecting old reservations
        self :: delete_bookings ( array(
            'listing_id' => $listing_id,  
            'owner_id' => get_current_user_id(),
            'type' => 'reservation') );

        // update by new one reservations
        foreach ( $dates as $date) {

            self :: insert_booking( array(
                'listing_id' => $listing_id,  
                'type' => 'reservation',
                'owner_id' => get_current_user_id(),
                'date_start' => $date,
                'date_end' => $date,
                'comment' =>  'owner reservations',
                'order_id' => NULL,
                'status' => NULL
            ));

        }

    }

    public static function get_slots_from_meta( $listing_id ) {

        $_slots = get_post_meta( $listing_id, '_slots', true );

        // Check for hyphen, en dash, or em dash
        $containsHyphen = strpos($_slots, '-') !== false;
        $containsEnDash = strpos($_slots, '-') !== false; // en dash
        $containsEmDash = strpos($_slots, '-') !== false; // em dash

        // When we don't have any type of dash
        if (!$containsHyphen && !$containsEnDash && !$containsEmDash) return false;
        // when we have slots
        $_slots = json_decode( $_slots );
        return $_slots;
    }

    /**
     * User booking shortcode
    * 
    * 
     */
    public  function listeo_core_booking( ) {
        ob_start();
        if(!isset($_POST['value'])){
            esc_html_e("You shouldn't be here :)",'listeo_core');
            return;
        }
        $template_loader = new Listeo_Core_Template_Loader;
       
        // here we adding booking into database
        if ( isset($_POST['confirmed']) )
        {

            $new_user_with_booking = false;
            if (!is_user_logged_in()) :
                $email_required = true;
                $booking_without_login = get_option('listeo_booking_without_login', 'off');
                
                if($booking_without_login){
                    $email = $_POST['email'];

                    $registration_errors = array();
                    if (!get_option('users_can_register')) {
                        // Registration closed, display error
                        $registration_errors[] = "registration_closed";
                    }
                    if (get_option('listeo_registration_hide_username')) {
                        $email_arr = explode('@', $email);
                        $user_login = sanitize_user(trim($email_arr[0]), true);
                    } else {
                        $user_login = sanitize_user(trim($_POST['username']));
                    }
                    $role =  (isset($_POST['user_role'])) ? sanitize_text_field($_POST['user_role']) : get_option('default_role');
                    //$role = sanitize_text_field($_POST['role']);
                    if (!in_array($role, array('owner', 'guest', 'seller'))) {
                        $role = get_option('default_role');
                    }
                    $password = (!empty($_POST['password'])) ? sanitize_text_field($_POST['password']) : false;
                    $first_name = (isset($_POST['firstname'])) ? sanitize_text_field($_POST['firstname']) : '';
                    $last_name = (isset($_POST['lastname'])) ? sanitize_text_field($_POST['lastname']) : '';
                    $privacy_policy_status = get_option('listeo_privacy_policy');

                    $privacy_policy_pass = true;
                    if ($privacy_policy_status) {
                        $privacy_policy_pass = false;
                        if (isset($_POST['privacy_policy']) && !empty($_POST['privacy_policy'])) :
                            $privacy_policy_pass = true;
                        else :
                            $registration_errors[] = "policy-fail";

                        endif;
                    }


                    $terms_and_conditions_status =  get_option('listeo_terms_and_conditions_req');
                    $terms_and_conditions_pass = true;
                    if ($terms_and_conditions_status) {
                        $terms_and_conditions_pass = false;
                        if (isset($_POST['terms_and_conditions']) && !empty($_POST['terms_and_conditions'])) :
                            $terms_and_conditions_pass = true;
                        else :
                            $registration_errors[] = "terms-fail";

                        endif;
                    }


                    $recaptcha_status = get_option('listeo_recaptcha');
                    $recaptcha_version = get_option('listeo_recaptcha_version');
	    
                   
                    if ($recaptcha_status) {

                        if ($recaptcha_status && $recaptcha_version == "v2") {
                            if ($recaptcha_version == "v2" && isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) :
                                $secret = get_option('listeo_recaptcha_secretkey');
                                //get verify response data

                                $verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
                                $responseData = json_decode($verifyResponse['body']);
                                if ($responseData->success) :
                                    //passed captcha, proceed to register
                                
                                else :
                                    $registration_errors[] = 'captcha-fail';
                                endif;
                            else :
                                $registration_errors[] = 'captcha-no';
                            endif;
                        }


                        if ($recaptcha_status && $recaptcha_version == "v3") {
                            if ($recaptcha_version == "v3" && isset($_POST['token']) && !empty($_POST['token'])) :
                                //your site secret key
                                $secret = get_option('listeo_recaptcha_secretkey3');
                                //get verify response data
                                $verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['token']);
                                $responseData_w = wp_remote_retrieve_body($verifyResponse);
                                $responseData = json_decode($responseData_w);

                                if ($responseData->success == '1' && $responseData->action == 'login' && $responseData->score >= 0.5) :
                                    //passed captcha, proceed to register
                                    
                                else :
                                    $registration_errors[] = 'captcha-fail';
                                endif;
                            else :
                                $registration_errors[] = 'captcha-no';
                            endif;
                        }
                       
                    }

                    $custom_registration_fields = array();
                    // if all above ok, we can register user
                    if(empty($registration_errors)){
                        $user_class = new Listeo_Core_Users;
                        $phone = false;
                        $_user_id = $user_class->register_user($email, $user_login, $first_name, $last_name, $role, $phone, $password, $custom_registration_fields);
                        if (!is_wp_error($_user_id)) {
                            
                            $new_user_with_booking = true;
                        } else {

                            $registration_errors[] = $_user_id->get_error_code();
                            $data = json_decode(wp_unslash(htmlspecialchars_decode(wp_unslash($_POST['value']))), true);
                            
                            $this->booking_confirmation_form($data, $registration_errors);
                            return;
                        }
                    } else {
                        $data = json_decode(wp_unslash(htmlspecialchars_decode(wp_unslash($_POST['value']))), true);
                        
                        $this->booking_confirmation_form($data, $registration_errors);
                        return;
                    }
                    
                }
                
              //  $template_loader->set_template_data($data)->get_template_part('booking'); 
                // we have to register new user
                //what about recatpcha
                // check all required data, create user, and set the login further
                //if data is wrong or user exist, redirect back and show error
            
            endif;
         
           // $data = json_decode(wp_unslash(htmlspecialchars_decode(wp_unslash($_POST['value']))), true);

            if(is_user_logged_in()){
                $_user_id = get_current_user_id();
            }
///?
            $data = json_decode(wp_unslash($_POST['value']), true);
            $error = false;
            $listing_id = $data['listing_id'];
            $listing_type =  get_post_meta ( $data['listing_id'], '_listing_type', true );
            
            $services = (isset($data['services'])) ? $data['services'] : false ;
            $comment_services = false;


            if(!empty($services)){
                $currency_abbr = get_option( 'listeo_currency' );
                $currency_postion = get_option( 'listeo_currency_postion' );
                $currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);
                //$comment_services = '<ul>';
                $comment_services = array();
                $bookable_services = listeo_get_bookable_services( $data['listing_id'] );
                
                if ( $listing_type == 'rental' ) {

                    $firstDay = new DateTime( $data['date_start'] );
                    $lastDay = new DateTime( $data['date_end'] . '23:59:59') ;

                    $days_between = $lastDay->diff($firstDay)->format("%a");
                    $days_count = ($days_between == 0) ? 1 : $days_between ;
                    
                } else {
                    
                    $days_count = 1;
                
                }
                
                //since 1.3 change comment_service to json
                $countable = array_column($services,'value');
                if(isset($data['adults'])){
                    $guests = $data['adults'];
                } else if(isset($data['tickets'])){
                    $guests = $data['tickets'];
                } else {
                    $guests = 1;
                }

          
                $i = 0;
                foreach ($bookable_services as $key => $service) {
                    
                    if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                     
                     
                        $comment_services[] =  array(
                            'service' => $service, 
                            'guests' => $guests, 
                            'days' => $days_count, 
                            'countable' =>  $countable[$i],
                            'price' => listeo_calculate_service_price($service, $guests, $days_count, $countable[$i] ) 
                        );
                        
                       $i++;
                    
                    }
                   
                
                }                  
            } //eof if services

            $listing_meta = get_post_meta ( $data['listing_id'], '', true );
            // detect if website was refreshed
            $instant_booking = get_post_meta(  $data['listing_id'], '_instant_booking', true );
            $payment_option = get_post_meta(  $data['listing_id'], '_payment_option', true );
            
            if(get_option('listeo_block_bookings_period')){
                if ( get_transient('listeo_last_booking'.$_user_id) == $data['listing_id'] . ' ' . $data['date_start']. ' ' . $data['date_end'] )
                {
                 
                
                    $template_loader->set_template_data( 
                        array( 
                            'error' => true,
                            'message' => __('Sorry, it looks like you\'ve already made that reservation', 'listeo_core')
                        ) )->get_template_part( 'booking-success' ); 
                    
                    return;
                }
                set_transient('listeo_last_booking' . $_user_id, $data['listing_id'] . ' ' . $data['date_start'] . ' ' . $data['date_end'], 60 * 15);
            }

            
            
            // because we have to be sure about listing type
            $listing_meta = get_post_meta ( $data['listing_id'], '', true );

            $listing_owner = get_post_field( 'post_author', $data['listing_id'] );

            $billing_address_1 = (isset($_POST['billing_address_1'])) ? sanitize_text_field($_POST['billing_address_1']) : false ;
            $billing_postcode = (isset($_POST['billing_postcode'])) ? sanitize_text_field($_POST['billing_postcode']) : false ;
            $billing_city = (isset($_POST['billing_city'])) ? sanitize_text_field($_POST['billing_city']) : false ;
            $billing_country = (isset($_POST['billing_country'])) ? sanitize_text_field($_POST['billing_country']) : false ;
            $billing_state = (isset($_POST['billing_state'])) ? sanitize_text_field($_POST['billing_state']) : false ;
            $coupon = (isset($_POST['coupon_code'])) ? sanitize_text_field($_POST['coupon_code']) : false ;


            $fields = get_option("listeo_{$listing_type}_booking_fields");


            $custom_booking_fields = array();

            if (!empty($fields)) {
                //get fields for booking

                foreach ($fields as $key => $field) {

                    

                    $field_type = str_replace('-', '_', $field['type']);

                    if (
                        $handler = apply_filters("listeo_core_get_posted_{$field_type}_field", false)
                    ) {

                        $value = call_user_func($handler, $key, $field);
                    } elseif (method_exists('Listeo_Core_Bookings_Calendar', "get_posted_{$field_type}_field")) {

                        $value = call_user_func(array('Listeo_Core_Bookings_Calendar', "get_posted_{$field_type}_field"), $key, $field);
                    } else {
                        // $value = (new Listeo_Core_Bookings_Calendar())->get_posted_field($key, $field);
                        
                        $value = (new Mrj_Global_Guest_Commission_Listeo_Core_Bookings_Calendar())->get_posted_field($key, $field);
                        
                    }

                    // Set fields value

                    $field['value'] = $value;

                    $custom_booking_fields[] = $field;
                 
                  
                }
            }
		
            switch ( $listing_meta['_listing_type'][0] ) 
            {
                case 'event' :

                    $comment= array( 
                        'first_name'    => sanitize_text_field($_POST['firstname']),
                        'last_name'     => sanitize_text_field($_POST['lastname']),
                        'email'         => sanitize_email($_POST['email']),
                        'phone'         => sanitize_text_field($_POST['phone']),
                        'message'       => sanitize_textarea_field($_POST['message']),
                        'tickets'       => sanitize_text_field($data['tickets']),
                        'service'       => $comment_services,
                        'billing_address_1' => $billing_address_1,
                        'billing_postcode'  => $billing_postcode,
                        'billing_city'      => $billing_city,
                        'billing_country'   => $billing_country,
                        'billing_state'   => $billing_state,
                        'coupon'        => $coupon,
                        'price'         => self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'],$data['tickets'], $services, '' )
                    );
                    
                    $booking_id = self :: insert_booking ( array (
                        'bookings_author'      => $_user_id,
                        'owner_id'      => $listing_owner,
                        'listing_id'    => $data['listing_id'],
                        'date_start'    => $data['date_start'],
                        'date_end'      => $data['date_start'],
                        'comment'       =>  json_encode ( $comment ),
                        'type'          =>  'reservation',
                        'price'         => self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'],$data['tickets'], $services, $coupon ),
                    ));

                    $already_sold_tickets = (int) get_post_meta($data['listing_id'],'_event_tickets_sold',true);
                    $sold_now = $already_sold_tickets + $data['tickets'];
                    update_post_meta($data['listing_id'],'_event_tickets_sold',$sold_now);

                    $status = apply_filters( 'listeo_event_default_status', 'waiting');
                    if($instant_booking == 'check_on' || $instant_booking == 'on' ) { 
                        $status = 'confirmed'; 
                        if(get_option('listeo_instant_booking_require_payment')){
                            $status = "pay_to_confirm";
                        }
                    }
                    
                    $changed_status = self :: set_booking_status ( $booking_id, $status );

                break;

                case 'rental' :

                    // get default status
                    $status = apply_filters( 'listeo_rental_default_status', 'waiting');
                    
                    $booking_hours = self::wpk_change_booking_hours(  $data['date_start'], $data['date_end'] );
                    $date_start = $booking_hours[ 'date_start' ];
                    $date_end = $booking_hours[ 'date_end' ];
                
                    // count free places
                    $free_places = self :: count_free_places( $data['listing_id'], $data['date_start'], $data['date_end'] );

                    if ( $free_places > 0 ) 
                    {

                        $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest" , true ); 
                        //check count_per_guest


                            $multiply = 1;
                            if(isset($data['adults'])) $multiply = $data['adults'];

                            $price = self :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], $multiply, $services, $coupon   );
                            $price_before_coupons = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, ''   );

                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);error_log(__FILE__ . ' ' . __LINE__);
                            
                            // When here, price will be uplifted by whatever the % guest commission is, so we must subtract
                            
            				//$options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
            				//$pctcommission = (int) $options['percentage'];
            				//$starting_price = ($price / (1 + $pctcommission / 100));
            				//$price = $starting_price;
                            error_log($price);

                        $booking_id = self :: insert_booking ( array (
                            'bookings_author'      => $_user_id,
                            'owner_id' => $listing_owner,
                            'listing_id' => $data['listing_id'],
                            'date_start' => $data['date_start'],
                            'date_end' => $data['date_end'],
                            'comment' =>  json_encode ( array( 
                                'first_name'    => sanitize_text_field($_POST['firstname']),
                                'last_name'     => sanitize_text_field($_POST['lastname']),
                                'email'         => sanitize_email($_POST['email']),
                                'phone'         => sanitize_text_field($_POST['phone']),
                                'message'       => sanitize_textarea_field($_POST['message']),
                                //'childrens' => $data['childrens'],
                                'adults'            => sanitize_text_field($data['adults']),
                                'service'           => $comment_services,
                                'billing_address_1' => $billing_address_1,
                                'billing_postcode'  => $billing_postcode,
                                'billing_city'      => $billing_city,
                                'billing_country'   => $billing_country,
                                'billing_state'   => $billing_state,
                                'coupon'            => $coupon,
                                'price'             => $price_before_coupons,
                               // 'tickets' => $data['tickets']
                            )),
                            'type' =>  'reservation',
                            'price' => $price,
                        ));
    
                        $status = apply_filters( 'listeo_event_default_status', 'waiting');
                        if($instant_booking == 'check_on' || $instant_booking == 'on') { $status = 'confirmed'; 
                        if(get_option('listeo_instant_booking_require_payment') && $price > 0 ){
                            $status = "pay_to_confirm";
                        }}
                        $changed_status = self :: set_booking_status ( $booking_id, $status );
                        
                    } else
                    {

                        $error = true;
                        $message = __('Unfortunately those dates are not available anymore.', 'listeo_core');

                    }

                    break;

                case 'service' :

                    $status = apply_filters( 'listeo_service_default_status', 'waiting');
                    if($instant_booking == 'check_on' || $instant_booking == 'on') { $status = 'confirmed'; 
                        if(get_option('listeo_instant_booking_require_payment') ){
                            $status = "pay_to_confirm";
                        }
                    }
                   
                    // time picker booking
                    if ( ! isset( $data['slot'] ) ) 
                    {
                       
                        $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest" , true );
                        $count_by_hour = get_post_meta($data['listing_id'], "_count_by_hour" , true );
                        //check count_per_guest
                        $hour_start = (isset($data['_hour']) && !empty($data['_hour'])) ? $data['_hour'] : $data['_hour'];
                        $hour_end = (isset($data['_hour_end']) && !empty($data['_hour_end'])) ? $data['_hour_end'] : $data['_hour'];


                        if($count_per_guest){

                            $multiply = 1;
                            if(isset($data['adults'])) $multiply = $data['adults'];

                            $price = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply , $services, $coupon  );
                            $price_before_coupons = self :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], $multiply, $services, ''   );
                            if ($count_by_hour) {

                                $price = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, $coupon);
                                $price_before_coupons = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, '');
                            }
                        } else {
                            $price = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'] ,1, $services, $coupon );
                            $price_before_coupons = self :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], 1, $services, ''   );
                            if ($count_by_hour) {

                                $price = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, 1, $services, $coupon);
                                $price_before_coupons = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, 1, $services, '');
                            }
                        }
                       
                      
                      
                       

                        $booking_id = self :: insert_booking ( array (
                            'owner_id' => $listing_owner,
                            'bookings_author'      => $_user_id,
                            'listing_id' => $data['listing_id'],
                            'date_start' => $data['date_start'] . ' ' . $data['_hour'] . ':00',
                            'date_end' => $data['date_end'] . ' ' . $hour_end . ':00',
                            'comment' =>  json_encode ( array( 
                                'first_name'    => sanitize_text_field($_POST['firstname']),
                                'last_name'     => sanitize_text_field($_POST['lastname']),
                                'email'         => sanitize_email($_POST['email']),
                                'phone'         => sanitize_text_field($_POST['phone']),
                                'message'       => sanitize_text_field($_POST['message']),
                                'adults'        => sanitize_text_field($data['adults']),
                                'message'       => sanitize_textarea_field($_POST['message']),
                                'service'       => $comment_services,
                                'billing_address_1' => $billing_address_1,
                                'billing_postcode'  => $billing_postcode,
                                'billing_state'  => $billing_state,
                                'billing_city'      => $billing_city,
                                'billing_country'   => $billing_country,
                                'coupon'   => $coupon,
                                'price'         => $price_before_coupons
                               
                            )),
                            'type' =>  'reservation',
                            'price' => $price,
                        ));
                        
                        $changed_status = self :: set_booking_status ( $booking_id, $status );

                    } else {

                        // here when we have enabled slots

                        $free_places = self :: count_free_places( $data['listing_id'], $data['date_start'], $data['date_end'], $data['slot'] );
                       
                        if ( $free_places > 0 ) 
                        {

                            $slot = json_decode( wp_unslash($data['slot']) );
                            
                           
                            // converent hours to mysql format
                            $hours = explode( ' - ', $slot[0] );
                            $hour_start = date( "H:i:s", strtotime( $hours[0] ) );
                            $hour_end = date( "H:i:s", strtotime( $hours[1] ) );

                            $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest" , true );
                            $count_by_hour = get_post_meta($data['listing_id'], "_count_by_hour" , true ); 
                            //check count_per_guest
                            $services = (isset($data['services'])) ? $data['services'] : false ;
                            if($count_per_guest){

                                $multiply = 1;
                                if(isset($data['adults'])) $multiply = $data['adults'];

                                $price = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, $coupon  );
                                $price_before_coupons = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, ''  );
                                if($count_by_hour){
                                    $price = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, $coupon);
                                    $price_before_coupons = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, '');
                                }
                            } else {
                                $price = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services,  $coupon );
                                $price_before_coupons = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services, ''  );
                                if ($count_by_hour) {
                                    $price = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, 1, $services, $coupon);
                                    $price_before_coupons = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, 1, $services, '');
                                }
                            }

                            $booking_id = self :: insert_booking ( array (
                                'owner_id' => $listing_owner,
                                'bookings_author'      => $_user_id,
                                'listing_id' => $data['listing_id'],
                                'date_start' => $data['date_start'] . ' ' . $hour_start,
                                'date_end' => $data['date_end'] . ' ' . $hour_end,
                                'comment' =>  json_encode ( array( 'first_name' => $_POST['firstname'],
                                    'last_name'     => sanitize_text_field($_POST['lastname']),
                                    'email'         => sanitize_email($_POST['email']),
                                    'phone'         => sanitize_text_field($_POST['phone']),
                                    //'childrens' => $data['childrens'],
                                    'adults'        => sanitize_text_field($data['adults']),
                                    'message'       => sanitize_textarea_field($_POST['message']),
                                    'service'       => $comment_services,
                                    'billing_address_1' => $billing_address_1,
                                    'billing_postcode'  => $billing_postcode,
                                    'billing_state'  => $billing_state,
                                    'billing_city'      => $billing_city,
                                    'billing_country'   => $billing_country,
                                    'coupon'   => $coupon,
                                    'price'         => $price_before_coupons
                                   
                                )),
                                'type' =>  'reservation',
                                'price' => $price,
                            ));

      
                            $status = apply_filters( 'listeo_service_slots_default_status', 'waiting');
                            if($instant_booking == 'check_on' || $instant_booking == 'on') { $status = 'confirmed'; 
                         if(get_option('listeo_instant_booking_require_payment') && $price > 0 ){
                            $status = "pay_to_confirm";
                        }}
                            
                            $changed_status = self :: set_booking_status ( $booking_id, $status );

                        } else
                        {
    
                            $error = true;
                            $message = __('Those dates are not available.', 'listeo_core');
    
                        }

                    }
                    
                break;
            }
            
            foreach ($custom_booking_fields as $field) {
                if(!empty($field['value'])){
                    add_booking_meta($booking_id, $field['id'], $field['value']);
                }
                
            }
            // when we have database problem with statuses
            if ( ! isset($changed_status) )
            {
                $message = __( 'We have some technical problem, please try again later or contact administrator.', 'listeo_core' );
                $error = true;
            }               
        
            switch ( $status )  {

                case 'waiting' :

                    $message = esc_html__( 'Your booking is waiting for confirmation.', 'listeo_core' );

                    break;

                case 'confirmed' :
                    if($price > 0){
                        switch ($payment_option) {
                            
                            case 'pay_cash':
                                $message = esc_html__('See you soon!', 'listeo_core');
                                break;
                            case 'pay_maybe':
                                $message = esc_html__('Pay now or in cash. See you soon!', 'listeo_core');
                                break;
                            
                            default:
                                $message = esc_html__('We are waiting for your payment.', 'listeo_core');
                                break;
                        }
                    
                    } else {
                        $message = '';
                    }
                    

                    break;

               

                case 'cancelled' :

                    $message = esc_html__( 'Your booking was cancelled', 'listeo_core' );

                    break;
            }



            
            
            if(isset($booking_id)){
                $booking_data =  self :: get_booking($booking_id);
                $order_id = $booking_data['order_id'];
                $order_id = (isset($booking_data['order_id'])) ? $booking_data['order_id'] : false ;
            }
            $template_loader->set_template_data( 
                array( 
                    'status' => $status,
                    'message' => (isset($message)) ? $message : 0,
                    'error' => $error,
                    'new_user_with_booking' => $new_user_with_booking,
                    'booking_id' => (isset($booking_id)) ? $booking_id : 0,
                    'order_id' => (isset($order_id)) ? $order_id : 0,
                    'listing_id' => (isset($listing_id)) ? $listing_id : 0,
                ) )->get_template_part( 'booking-success' ); 
            $content = ob_get_clean();
            return $content;
        } 

        // not confirmed yet



$values = false;
        $this->booking_confirmation_form($values);
 
        // if slots are sended change them into good form
        if ( isset( $data['slot'] ) ) {

             // converent hours to mysql format
             $hours = explode( ' - ', $slot[0] );
             $hour_start = date( "H:i:s", strtotime( $hours[0] ) );
             $hour_end = date( "H:i:s", strtotime( $hours[1] ) );
 
             // add hours to dates
             $data['date_start'] .= ' ' . $hour_start;
             $data['date_end'] .= ' ' . $hour_end;
        

        } else if ( isset( $data['_hour'] ) ) {

            // when we dealing with normal hour from input we have to add second to make it real date format
            $hour_start = date( "H:i:s", strtotime( $hour ) );
            $data['date_start'] .= ' ' . $hour . ':00';
            $data['date_end'] .= ' ' . $hour . ':00';

        }

        // make temp reservation for short time
        //self :: save_temp_reservation( $data );

    }
    public function booking_confirmation_form($values, $registration_errors = null) {
        
        if(isset($values)&& !empty($values)){
            $data = $values;
        } else {
            $data = json_decode(wp_unslash($_POST['value']), true);
        }
        $template_loader = new Listeo_Core_Template_Loader;
        if(!$data){
            $template_loader->set_template_data(
                array(
                    'error' => true,
                    'message' => __('Please try again', 'listeo_core')
                )
            )->get_template_part('booking-success');

            return;
            return;
        }
        

        if (isset($registration_errors) && !empty($registration_errors)) {
            $data['registration_errors'] = $registration_errors;
         
        }
        if (isset($data['services'])) {
            $services =  $data['services'];
        } else {
            $services = false;
        }

        // for slots get hours
        if (isset($data['slot'])) {
            $slot = json_decode(wp_unslash($data['slot']));
            $hour = $slot[0];
        } else if (isset($data['_hour'])) {
            $hour = $data['_hour'];
            if (isset($data['_hour_end'])) {
                $hour_end = $data['_hour_end'];
            }
        } else {
            $hour = false;
            $hour_end = false;
        }

        if (isset($data['coupon']) && !empty($data['coupon'])) {
            $coupon = $data['coupon'];
        } else {
            $coupon = false;
        }

        

        // prepare some data to template
        $data['submitteddata'] = htmlspecialchars(stripslashes($_POST['value']));

        //check listin type
        $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest", true);
        //check count_per_guest

        //  if($count_per_guest || $data['listing_type'] == 'event' ){

        $multiply = 1;
        if (isset($data['adults'])) $multiply = $data['adults'];
        if (isset($data['tickets'])) $multiply = $data['tickets'];



        $data['price'] = self::calculate_price($data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, '');
        if (!empty($coupon)) {
            $data['price_sale'] = self::calculate_price($data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, $coupon);
        }
        if (get_post_meta($data['listing_id'], '_count_by_hour', true)) {
            if (isset($data['slot'])) {

                $hours = explode(' - ', $slot[0]);
                $hour_start = date("H:i", strtotime($hours[0]));

                $hour_end = date("H:i", strtotime($hours[1]));
            } else {
                $hour_start = $hour;
            }
            $data['price'] = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, '');
            if (!empty($coupon)) {
                $data['price_sale'] = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, $coupon);
            }
        }

        // } else {

        //     $data['price'] = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services  );
        // }

        if (isset($hour)) {
            $data['_hour'] = $hour;
        }
        if (isset($hour_end)) {
            $data['_hour_end'] = $hour_end;
        }


        //check listin type
        $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest", true);
        //check count_per_guest

        //  if($count_per_guest || $data['listing_type'] == 'event' ){

        $multiply = 1;
        if (isset($data['adults'])) $multiply = $data['adults'];
        if (isset($data['tickets'])) $multiply = $data['tickets'];



        $data['price'] = self::calculate_price($data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, '');
        if (!empty($coupon)) {
            $data['price_sale'] = self::calculate_price($data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, $coupon);
        }
        if (get_post_meta($data['listing_id'], '_count_by_hour', true)) {
            if (isset($data['slot'])) {

                $hours = explode(' - ', $slot[0]);
                $hour_start = date("H:i", strtotime($hours[0]));

                $hour_end = date("H:i", strtotime($hours[1]));
            } else {
                $hour_start = $hour;
            }
            $data['price'] = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, '');
            if (!empty($coupon)) {
                $data['price_sale'] = self::calculate_price_per_hour($data['listing_id'],  $data['date_start'], $data['date_end'], $hour_start, $hour_end, $multiply, $services, $coupon);
            }
        }

        // } else {

        //     $data['price'] = self :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services  );
        // }

        if (isset($hour)) {
            $data['_hour'] = $hour;
        }
        if (isset($hour_end)) {
            $data['_hour_end'] = $hour_end;
        }

        error_log(json_encode($data));

        //MRJ
        $mrj_template_loader = new Mrj_Global_Guest_Commission_Template_Loader;
        $mrj_template_loader->set_template_data($data)->get_template_part('booking'); 
 
    }
    /**
     * Save temp reservation
     * 
     * @param array $atts with 'date_start', 'date_end' and 'listing_id'
     * 
     * @return array $temp_reservations with all reservations for this id, also expired if will be
     * 
     */
    public static function save_temp_reservation( $atts ) {

        // get temp reservations for current listing
        $temp_reservations = get_transient( $atts['listing_id'] );

        // get current date + time setted as temp booking time
        $expired_date = date( 'Y-m-d H:i:s', strtotime( '+' . apply_filters( 'listeo_expiration_booking_minutes', 15) . ' minutes', time() ) );

        // set array for current temp reservations
        $reservation_data = array(
            'user_id' => get_current_user_id(),
            'date_start' => $atts['date_start'],
            'date_end' => $atts['date_end'],
            'expired_date' => $expired_date
        );

        // add reservation to end of array with all reservations for this listing
        $temp_reservations[] = $reservation_data;

        // set transistence on time setted as temp booking time
        set_transient( $atts['listing_id'], $temp_reservations, apply_filters( 'listeo_expiration_minutes', 15) * 60 );

        // return all temp reservations for this id
        return $temp_reservations;

    }

    /**
     * Temp reservation aval
     * 
     * @param array $atts with 'date_start', 'date_end' and 'listing_id'
     *
     * @return number $reservation_amount of all temp reservations form tranistenc fittid this id and time
     * 
     */
    public static function temp_reservation_aval( $args ) {

        // get temp reservations for current listing
        $temp_reservations = get_transient( $args['listing_id'] );

        // loop where we will count only reservations fitting to time and user id
        $reservation_amount = 0;

        if ( is_array($temp_reservations) ) 
        {
            foreach ( $temp_reservations as $reservation) {
            
                // if user id is this same then not count
                if ( $reservation['user_id'] == get_current_user_id() ) 
                {
                    continue;
                }

                // when its too old and expired also not count, it will be deleted automaticly with wordpress transistend
                if ( date( 'Y-m-d H:i:s', strtotime( $reservation['expired_date'] ) ) < date( 'Y-m-d H:i:s', time() ) ) 
                {
                    continue;
                }

                // now we converenting strings into dates
                $args['date_start'] = date( 'Y-m-d H:i:s', strtotime( $args['date_start']  ) );
                $args['date_end'] = date( 'Y-m-d H:i:s', strtotime( $args['date_end']  ) );
                $reservations['date_start'] = date( 'Y-m-d H:i:s', strtotime( $reservations['date_start']  ) );
                $reservations['date_end'] = date( 'Y-m-d H:i:s', strtotime( $reservations['date_end']  ) );

                // and compating dates
                if ( ! ( ($args['date_start'] >= $reservation['date_start'] AND $args['date_start'] <= $reservation['date_end']) 
                OR ($args['date_end'] >= $reservation['date_start'] AND $args['date_end'] <= $reservation['date_end']) 
                OR ($reservation['date_start'] >= $args['date_start'] AND $reservation['date_end'] <= $args['date_end']) ) )
                {
                    continue; 
                } 
    
                $reservation_amount++;

            }
        }

        return $reservation_amount;

    }


    /**
     * Owner booking menage shortcode
    * 
    * 
     */
    public static function listeo_core_dashboard_bookings( ) {
    
          
        $users = new Listeo_Core_Users;
        
        $listings = $users->get_agent_listings('',0,-1);
        $args = array (
            'owner_id' => get_current_user_id(),
            'type' => 'reservation',
            
        );

        $limit =  get_option('posts_per_page');
        $pages = '';
        if(isset($_GET['status']) ){
            $booking_max = listeo_count_bookings(get_current_user_id(),$_GET['status']); 
            $pages = ceil($booking_max/$limit);
            $args['status'] = $_GET['status'];
        }
        $bookings = self :: get_newest_bookings($args,$limit );
        ob_start();
        $template_loader = new Listeo_Core_Template_Loader;
        $template_loader->set_template_data( 
            array( 
                'message' => '',
                'bookings' => $bookings,
                'pages' => $pages,
                'listings' => $listings->posts,
            ) )->get_template_part( 'dashboard-bookings' ); 
        $content = ob_get_clean();
        return $content;
 
    }

    public static function listeo_core_dashboard_my_bookings( ) {
    
        ob_start();
        $users = new Listeo_Core_Users;
        $args_default = array (
            'bookings_author' => get_current_user_id(),
            'type' => 'reservation'
        );
        $args =  apply_filters( 'listeo_core_my_bookings_args', $args_default);

     
        $limit =  get_option('posts_per_page');

        $bookings = self :: get_newest_bookings($args,$limit );
        $booking_max = listeo_count_my_bookings(get_current_user_id());
        $pages = ceil($booking_max/$limit);
        $template_loader = new Listeo_Core_Template_Loader;
        $template_loader->set_template_data( 
            array( 
                'message' => '',
                'type'    => 'user_booking',
                'bookings' => $bookings,
                'pages' => $pages,
            ) )->get_template_part( 'dashboard-bookings' ); 
        $content = ob_get_clean();
        return $content;
        
    }

    /**
    * Booking Paid
    *
    * @param number $order_id with id of order
    * 
     */
    public static function booking_paid( $order_id ) {
    
        $order = wc_get_order( $order_id );

        $booking_id = $order->get_meta('booking_id');
        if($booking_id){
                self :: set_booking_status( $booking_id, 'paid' );
        }
    }

    /**
    * Booking refund
    *
    * @param number $order_id with id of order
    * 
     */
    public static function booking_refund( $order_id ) {
    
        $order = wc_get_order( $order_id );

        $booking_id = get_post_meta( $order_id, 'booking_id', true );
        if($booking_id){
                self :: set_booking_status( $booking_id, 'refund' );
        }
    }

    public function listeo_wc_pre_get_posts_query( $q ) {

        $tax_query = (array) $q->get( 'tax_query' );

        $tax_query[] = array(
               'taxonomy' => 'product_type',
               'field' => 'slug',
               'terms' => array( 'listing_booking' ), // 
               'operator' => 'NOT IN'
        );


        $q->set( 'tax_query', $tax_query );

    }

    public static function get_booking($id){
        global $wpdb;
        return $wpdb -> get_row( 'SELECT * FROM `'  . $wpdb->prefix .  'bookings_calendar` WHERE `id`=' . esc_sql( $id ), 'ARRAY_A' );
    }
    public static function is_booking_external( $booking_status ): bool {
        $external = false;
        if($booking_status){
            if ( 0 === strpos( $booking_status, 'external' ) ) {
                $external = true;
            }
        }

        return $external;
    }


    public function check_for_expired_booking(){
        
        global $wpdb;
        $date_format = 'Y-m-d H:i:s';
        // Change status to expired
        $table_name = $wpdb->prefix . 'bookings_calendar';
        $bookings_ids = $wpdb->get_col( $wpdb->prepare( "
            SELECT ID FROM {$table_name}
            WHERE status not in ('paid','owner_reservations','icalimports','cancelled')
            AND expiring > '0000-00-00 00:00:00'      
            AND expiring < %s
            
        ", date( $date_format, current_time( 'timestamp' ) ) ));

        if ( $bookings_ids ) {
            foreach ( $bookings_ids as $booking ) {
                  // delecting old reservations
                self :: set_booking_status ( $booking, 'expired' );
                do_action('listeo_expire_booking',$booking);
            }
        }
    }

    public function check_for_expiring_booking()
    {

        global $wpdb;
        $date_format = 'Y-m-d H:i:s';
        // Change status to expired
        $table_name = $wpdb->prefix . 'bookings_calendar';
        $bookings_ids = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$table_name}
            WHERE status not in ('paid','owner_reservations','icalimports','cancelled')
            AND expiring > '0000-00-00 00:00:00'      
            AND expiring < %s
            
       ", date($date_format, strtotime('+1 hour', current_time('timestamp')))));

        if ($bookings_ids) {
            foreach ($bookings_ids as $booking) {
                // delecting old reservations
                self::set_booking_status($booking, 'expired');
                do_action('listeo_expiring_booking', $booking);
            }
        }
    }

    public function check_for_upcoming_booking(){
        
        global $wpdb;
        $date_format = 'Y-m-d H:i:s';
        // Change status to expired
        $table_name = $wpdb->prefix . 'bookings_calendar';
        $bookings_ids = $wpdb->get_col(
            "
            SELECT ID FROM {$table_name}
            WHERE status in ('paid')      
            AND date_start = DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
        );

        ////AND date_start BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)"

        if ( $bookings_ids ) {
            foreach ( $bookings_ids as $booking ) {
                // delecting old reservations
                $booking_data =  self::get_booking($booking);
                
                do_action('listeo_mail_to_user_upcoming_booking', $booking_data);
                do_action('listeo_upcoming_booking',$booking);
            }
        }
    }
    public function check_for_past_booking(){
        
        global $wpdb;
      
        // Change status to expired
        $table_name = $wpdb->prefix . 'bookings_calendar';
        $bookings_ids = $wpdb->get_col(
            "
            SELECT ID FROM {$table_name}
            WHERE status in ('paid')      
            AND date_end <  DATE_SUB(CURDATE(), INTERVAL 1 DAY)"
        );

        if ( $bookings_ids ) {
            foreach ( $bookings_ids as $booking ) {
                // delecting old reservations
                $booking_data =  self::get_booking($booking);
                
                do_action('listeo_mail_to_user_past_booking', $booking_data);
                do_action('listeo_past_booking',$booking);
            }
        }
    }

    public function check_for_upcoming_payments(){
        global $wpdb;
        $date_format = 'Y-m-d H:i:s';
        // Change status to expired
        $now = current_time('mysql'); 
        $table_name = $wpdb->prefix . 'bookings_calendar';
        $bookings_ids = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$table_name}
            WHERE status not in ('paid','owner_reservations','icalimports','cancelled')      
            AND expiring > %s
			AND expiring < %s
            
        ", 
		date($date_format, strtotime($now)), 
		date($date_format, strtotime($now) + 3600 )
        ));

        if ($bookings_ids) {
            foreach ($bookings_ids as $booking) {
                // delecting old reservations
                $booking_data =  self::get_booking($booking);
              do_action('listeo_upcoming_payment', $booking_data);
            }
        }
        
    }


    protected  function get_posted_field($key, $field)
    {

        return isset($_POST[$key]) ? $this->sanitize_posted_field($_POST[$key]) : '';
    }


    protected function get_posted_file_field($key, $field)
    {

        $file = $this->upload_file($key, $field);

        if (!$file) {
            $file = $this->get_posted_field('current_' . $key, $field);
        } elseif (is_array($file)) {
            $file = array_filter(array_merge($file, (array) $this->get_posted_field('current_' . $key, $field)));
        }

        return $file;
    }
    /**
     * Handles the uploading of files.
     *
     * @param string $field_key
     * @param array  $field
     * @throws Exception When file upload failed
     * @return  string|array
     */
    protected function upload_file($field_key, $field)
    {
        if (isset($_FILES[$field_key]) && !empty($_FILES[$field_key]) && !empty($_FILES[$field_key]['name'])) {
            if (!empty($field['allowed_mime_types'])) {
                $allowed_mime_types = $field['allowed_mime_types'];
            } else {
                $allowed_mime_types = listeo_get_allowed_mime_types();
            }

            $file_urls       = array();
            $files_to_upload = listeo_prepare_uploaded_files($_FILES[$field_key]);

            foreach ($files_to_upload as $file_to_upload) {
                $uploaded_file = listeo_upload_file($file_to_upload, array(
                    'file_key'           => $field_key,
                    'allowed_mime_types' => $allowed_mime_types,
                ));

                if (is_wp_error($uploaded_file)) {
                    throw new Exception($uploaded_file->get_error_message());
                } else {
                    $file_urls[] = $uploaded_file->url;
                }
            }

            if (!empty($field['multiple'])) {
                return $file_urls;
            } else {
                return current($file_urls);
            }
        }
    }
    /**
     * Navigates through an array and sanitizes the field.
     *
     * @param array|string $value The array or string to be sanitized.
     * @return array|string $value The sanitized array (or string from the callback).
     */
    protected function sanitize_posted_field($value)
    {
        // Santize value
        $value = is_array($value) ? array_map(array($this, 'sanitize_posted_field'), $value) : sanitize_text_field(stripslashes(trim($value)));

        return $value;
    }

    /**
     * Gets the value of a posted textarea field.
     *
     * @param  string $key
     * @param  array  $field
     * @return string
     */
    protected  function get_posted_textarea_field($key, $field)
    {
        return isset($_POST[$key]) ? wp_kses_post(trim(stripslashes($_POST[$key]))) : '';
    }

    /**
     * Gets the value of a posted textarea field.
     *
     * @param  string $key
     * @param  array  $field
     * @return string
     */
    function  get_posted_wp_editor_field($key, $field)
    {
        return $this->get_posted_textarea_field($key, $field);
    }

    protected function create_attachment($attachment_url)
    {
        include_once(ABSPATH . 'wp-admin/includes/image.php');
        include_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload_dir     = wp_upload_dir();
        $attachment_url = str_replace(array($upload_dir['baseurl'], WP_CONTENT_URL, site_url('/')), array($upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH), $attachment_url);

        if (empty($attachment_url) || !is_string($attachment_url)) {
            return 0;
        }

        $attachment     = array(
            'post_title'   =>  wp_generate_password(8, false),
            'post_content' => '',
            'post_status'  => 'inherit',
            'guid'         => $attachment_url
        );

        if ($info = wp_check_filetype($attachment_url)) {
            $attachment['post_mime_type'] = $info['type'];
        }

        $attachment_id = wp_insert_attachment($attachment, $attachment_url);

        if (!is_wp_error($attachment_id)) {
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $attachment_url));
            return $attachment_id;
        }

        return 0;
    }


}

?>