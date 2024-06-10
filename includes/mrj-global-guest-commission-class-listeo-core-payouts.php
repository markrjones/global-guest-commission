<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

class Mrj_Global_Guest_Commission_Listeo_Core_Payouts extends Listeo_Core_Payouts {

    private static $_instance = null;
    /**
     * Main plugin Instance
     *
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_item' ), 9 );        
    }

    public function add_menu_item() {

            $args = apply_filters( 'listeo_core_commissions_menu_items', array(
                    'page_title' => __( 'Commissions (Site)', 'listeo_core' ),
                    'menu_title' => __( 'Commissions (Site)', 'listeo_core' ),
                    'capability' => 'edit_products',
                    'menu_slug'  => 'listeo_payouts_mrj',
                    'function'   => array( $this, 'commissions_details_page' ),
                    'icon'       => 'dashicons-tickets',
                    'position'   => 58 /* After WC Products */
                )
            );

            extract( $args );

            add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon, $position );
            add_submenu_page( 'listeo_payouts_mrj', 'Payouts History', 'Payouts History', 'edit_products', 'listeo_payouts_list', array( $this, 'payouts_details_page' ) );
    
    
    }


    public function make_payout(){

        global $wpdb;
        
        $orders_list = array();  
        $balance = 0;  
        if(isset($_POST['commission']) && !empty($_POST['commission'])){
            if(is_array($_POST['commission'])){
                foreach ($_POST['commission'] as $key => $value) {
                    $orders_list[] = $key;
                    $wpdb->update( $wpdb->prefix . "listeo_core_commissions", array('status'=>'paid'), array( 'id' => $key ) );
                    $order_id = $wpdb->get_var( $wpdb->prepare( 
                        '
                            SELECT order_id 
                            FROM '.$wpdb->prefix.'listeo_core_commissions
                            WHERE id = %s
                        ', 
                        $key
                    ) );
                    $amount = $wpdb->get_var( $wpdb->prepare( 
                        '
                            SELECT amount 
                            FROM '.$wpdb->prefix.'listeo_core_commissions
                            WHERE id = %s
                        ', 
                        $key
                    ) );
                    
                    //get order_id where id=key

                    $order = wc_get_order( $order_id );
                    $total = $order->get_total();

                    $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
                    $guest_commission_rate = (float) $options['percentage'];
                    $total = $total / (1 + $guest_commission_rate / 100);

                    $earning = (float) $total - $amount;
                    $balance = $balance + $earning;
                }
            }
        }
        $orders = json_encode($orders_list);

        //$balance = $commission->calculate_totals( array( 'user_id'=> $_POST['user_id'],'status' => 'unpaid' ) );
        
        $args = array(
            'user_id'         => $_POST['user_id'],
            'status'          => 'paid',
            'orders'          => $orders,
            'date'            => current_time('mysql'),
            'amount'          => $balance,
            'payment_method'  => $_POST['payment_method'],
            'payment_details'  => isset($_POST['payment_details']) ? $_POST['payment_details'] : 'n/a'

        );        

        $wpdb->insert( $wpdb->prefix . "listeo_core_commissions_payouts", $args );

        if($wpdb->insert_id){
            echo '<div class="updated"><p>'.esc_html__('Payout was created.','listeo_core').'</p></div>';
            $id = $wpdb->insert_id; 
            $payout = $this->get_payout($id);

            if ($payout['payment_method'] === 'paypal'){
                $payment_method = 'PayPal';
            }else if ($payout['payment_method'] === 'paypal_payout'){
                $payment_method = 'PayPal Payout';
            }else {
                $payment_method = 'Bank Transfer';
            }
            
            $user_data = get_userdata($payout['user_id']); 
            ?>
            <div class="wrap">

                <h2>Payout details</h2>
                <div class="payout-make-box">
                <ul>
                    <li><span><?php esc_html_e( 'Payment for', 'listeo_core' ); ?></span> <?php echo $user_data->display_name; ?></li>
                    <li><span><?php esc_html_e('Payment date','listeo_core'); ?>:</span> <?php echo date(get_option( 'date_format' ), strtotime($payout['date']));  ?></li>
                    <li><span><?php esc_html_e('Payment amount','listeo_core'); ?>:</span>  <?php echo wc_price($payout['amount']); ?></li>
                    <li><span><?php esc_html_e('Payment method','listeo_core'); ?>:</span> <?php echo $payment_method ; ?></li>
                    <li><span><?php esc_html_e('Payment details','listeo_core'); ?>:</span>  
                        <textarea cols="30" rows="10" disabled="disabled"><?php echo ($payout['payment_details']); ?></textarea></li>
                       
                </ul>
                </div>

                <?php 
                $commission_class = new Listeo_Core_Commissions;
                $commissions = array();
                $commissions_ids = json_decode($payout['orders']);
                foreach ($commissions_ids as $id) {
                    $commissions[$id] = $commission_class->get_commission($id);
                }
                $balance = 0;
                ?>
                <?php if($commissions) {?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Listing Title</th>
                                <th>Total Order value</th>
                                <th>Site Fee</th>
                                <th>User Earning</th>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <?php 
                        foreach ($commissions as $commission) { 
                            $order = wc_get_order( $commission['order_id'] );
                            $total = $order->get_total();

                            $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
                            $guest_commission_rate = (float) $options['percentage'];
                            $total = $total / (1 + $guest_commission_rate / 100);

                            $earning = $total - $commission['amount'];
                            $balance = (float) $balance + $earning;
                            ?>
                            <tr>
                                <td><?php echo get_the_title($commission['listing_id']) ?></td>
                                <td class="paid"><?php echo wc_price($total); ?></td>
                                <td class="unpaid"><?php echo wc_price($commission['amount']); ?></td>
                                <td class="paid"> <span><?php echo wc_price($earning); ?></span></td>
                                <td>#<?php echo $commission['order_id']; ?></td>
                                <td><?php echo date(get_option( 'date_format' ), strtotime($commission['date']));  ?></td>
                                <td><?php echo $commission['status']; ?></td>
                                
                            </tr>
                        <?php } ?>
                    </table>
                <?php } ?>
            </div>
            <?php
        } else {
            echo '<div class="updated"><p>Something went wrong</p></div>';
        };

        //for each commission change status to paid
    }

    /**
     * Show the Commissions page
     *
     * @author Andrea Grillo <andrea.grillo@yithemes.com>
     * @since  1.0
     * @return void
     *
     */
    public function commissions_details_page() {

        $cancel_payout_options = [
            'unclaimed',
            'pending'
        ];

        if(isset($_POST['submit_new_payout'])){
               $this->make_payout();
        } else if ( isset( $_GET['make_payout'] ) ) {
            
            $user_id = absint( $_GET['make_payout'] ); 
            $user_data = get_userdata($user_id);
            $payment_type = get_user_meta($user_id, 'listeo_core_payment_type', true);
            if ($payment_type === 'paypal') {
                $payment_method = 'PayPal';
            } else if ($payment_type === 'paypal_payout') {
                $payment_method = 'PayPal Payout';
            } else {
                $payment_method = 'Bank Transfer';
            }

            ?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Newsss Payment for', 'listeo_core' ); ?> <?php echo $user_data->display_name; ?></h2>
                <div class="payout-make-box">
                    
                
                    <p>By clicking Submit Button you'll mark all his current <strong>Unpaid</strong> commissions as <strong>Paid</strong></p>
                    <p>User's requested Payment Method: 
                        <strong><?php echo $payment_method ; ?>
                        </strong> (payment details in next step)
                    </p>
          
                </div>
                <form method="POST"  id="listeo-make-payout">
                    <input type="submit" name="submit_new_payout" id="submit" class="button button-primary" value="Make Payout">
                    <input type="hidden" name="action" value="listeo_make_payout" />
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />                    
                    <?php if(isset($payment_type)) { 
                        $payment_method = $payment_type; 
                    } else {
                        $payment_method = "banktransfer";
                    }?>
                    <input type="hidden" name="payment_method" value="<?php echo esc_attr($payment_method); ?>" />  

                    <?php if($payment_method == 'paypal'): ?>
                        <input type="hidden" name="payment_details" value="<?php echo $user_data->listeo_core_ppemail ?>">
                    <?php endif; ?> 
                    <?php if($payment_method == 'banktransfer'): ?>
                        <input type="hidden" name="payment_details" value="<?php echo esc_attr($user_data->listeo_core_bank_details); ?>">
                    <?php endif; ?>
                    


                <h4>Commissions</h4>
                <?php 
                $commission_class = new Mrj_Global_Guest_Commission_Listeo_Core_Commissions;
                $commissions_ids = $commission_class->get_commissions( array( 'user_id'=>$user_id ) );
                $commissions = array();
                foreach ($commissions_ids as $id) {
                    $commissions[$id] = $commission_class->get_commission($id);
                }
                $balance = 0;
                ?>
                <?php if($commissions) {?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Listing Title</th>
                                <th>Total Order value</th>
                                <th>Site Fee</th>
                                <th>User Earning</th>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <?php 

                        foreach ($commissions as $commission) { 
                            $order = wc_get_order( $commission['order_id'] );
                             if(!$order){
                                return;
                            }
                            $total = $order->get_total();

                            $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
                            $guest_commission_rate = (float) $options['percentage'];
                            $total = $total / (1 + $guest_commission_rate / 100);
                    
                            $earning = $total - $commission['amount'];
                            if (strtolower($commission['payout_item_transaction_status']) !== 'unclaimed'){
                                $balance = (float) $balance + $earning;
                            }
                            ?>
                            <tr>

                                <?php if (listeo_is_payout_active()): ?>
                                    <?php if ( ! in_array(strtolower($commission['payout_item_transaction_status']), $cancel_payout_options, true) ): ?>
                                        <td><input type="checkbox" checked="checked" name="commission[<?php echo $commission['id']; ?>]"></td>
                                    <?php else: ?>
                                        <td><em><?php _e('First, you need to cancel automatic commission that was previously sent by using PayPal Payout. Then you can send the commission manually.', 'listeo'); ?></em></td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <td><input type="checkbox" checked="checked" name="commission[<?php echo $commission['id']; ?>]"></td>
                                <?php endif; ?>

                                <td><?php echo get_the_title($commission['listing_id']) ?></td>
                                <td class="paid"><?php echo wc_price($total); ?></td>
                                <td class="unpaid"><?php echo wc_price($commission['amount']); ?></td>
                                <td class="paid"> <span><?php echo wc_price($earning); ?></span></td>
                                <td>#<?php echo $commission['order_id']; ?></td>
                                <td><?php echo date(get_option( 'date_format' ), strtotime($commission['date']));  ?></td>

                                <?php if (listeo_is_payout_active()): ?>
                                    <?php if (isset($commission['payout_item_transaction_status']) && strtolower($commission['payout_item_transaction_status']) !== 'success'): ?>
                                        <td>
                                            <?php echo $commission['payout_item_transaction_status']; ?>
                                            <?php

                                            if ( in_array(strtolower($commission['payout_item_transaction_status']), $cancel_payout_options, true)):

                                            ?>
                                                <a class="button color cancel-payout-button" href="#" data-pp_item_id="<?php echo $commission['payout_item_id']; ?>" data-commission_id="<?php echo $commission['id']; ?>">
                                                    <i class="fa fa-close white"></i>
                                                    <?php _e('Cancel PayOut', 'listeo'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    <?php else: ?>
                                        <td><?php echo $commission['status']; ?></td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <td><?php echo $commission['status']; ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php } ?>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right;">Total </td>
                                <td><?php echo wc_price($balance); ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php } ?>
                <br>
                                   
                <input type="submit" name="submit_new_payout" id="submit" class="button button-primary" value="Make Payout">

                    <div class="loader-div listeo-hidden">
                        <span class="fa fa-spinner fa-spin fa-3x"></span>
                    </div>

                </form>
               
            </div>

        <?php } 
        
        else {
            if ( ! class_exists( 'WP_List_Table' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
            }
           
           
            $balances_table = new Mrj_Global_Guest_Commission_Listeo_Balances_List_Table();
            $balances_table->prepare_items();  ?>
            <div class="wrap">

                <h2>Users balances</h2>

                <?php $balances_table->views(); ?>

                <form id="commissions-filter" method="get">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php // $commissions_table->add_search_box( __( 'Search commissions', 'listeo_core' ), 's' ); ?>
                    <?php $balances_table->display(); ?>
                </form>

            </div>
            <?php
        }
    }

    public function payouts_details_page() {
        if ( isset( $_GET['view_payout'] ) ) {
            
            $id = absint( $_GET['view_payout'] ); 
            $payout = $this->get_payout($id);
            
            $user_data = get_userdata($payout['user_id']);

            if ($payout['payment_method'] === 'paypal'){
                $payment_method = 'Pay Pal';
            }else if ($payout['payment_method'] === 'paypal_payout'){
                $payment_method = 'PayPal Payout';
            }else {
                $payment_method = 'Bank Transfer';
            }


            ?>
            <div class="wrap">

                <h2>Payout details</h2>
                <div class="payout-make-box">
                <ul>
                    <li><span><?php esc_html_e( 'Payment for', 'listeo_core' ); ?></span> <?php echo $user_data->display_name; ?></li>
                    <li><span>Payment date:</span> <?php echo date(get_option( 'date_format' ), strtotime($payout['date']));  ?></li>
                    <li><span>Payment amount:</span>  <?php echo wc_price($payout['amount']); ?></li>
                    <li><span>Payment method:</span> <?php echo $payment_method ; ?></li>
                    <li><span>Payment details:</span>  
                        <textarea cols="30" rows="10" disabled="disabled"><?php echo ($payout['payment_details']); ?></textarea></li>
                       
                </ul>
                </div>

                <?php 
                $commission_class = new Listeo_Core_Commissions;
                $commissions = array();
                $commissions_ids = json_decode($payout['orders']);
                foreach ($commissions_ids as $id) {
                    $commissions[$id] = $commission_class->get_commission($id);
                }
                $balance = 0;
                ?>
                <?php if($commissions) {?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Listing Title</th>
                                <th>Total Order value</th>
                                <th>Site Fee</th>
                                <th>User Earning</th>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <?php 
                        foreach ($commissions as $commission) { 
                            $order = wc_get_order( $commission['order_id'] );
                            if($order) {


                                $total = $order->get_total();

                                $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
                                $guest_commission_rate = (float) $options['percentage'];
                                $total = $total / (1 + $guest_commission_rate / 100);
                        
                                $earning = $total - $commission['amount'];
                                $balance = (float) $balance + $earning;
                                ?>
                                <tr>
                                    <td><?php echo get_the_title($commission['listing_id']) ?></td>
                                    <td class="paid"><?php echo wc_price($total); ?></td>
                                    <td class="unpaid"><?php echo wc_price($commission['amount']); ?></td>
                                    <td class="paid"> <span><?php echo wc_price($earning); ?></span></td>
                                    <td>#<?php echo $commission['order_id']; ?></td>
                                    <td><?php echo date(get_option( 'date_format' ), strtotime($commission['date']));  ?></td>
                                    <td><?php echo $commission['status']; ?></td>
                                    
                                </tr>
                            <?php } 
                            }?>
                    </table>
                <?php } ?>
            </div>

        <?php } else {

        
            if ( ! class_exists( 'WP_List_Table' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
            }
           
           
            $payouts_table = new Listeo_Payouts_List_Table();
            $payouts_table->prepare_items();           

            ?>
            <div class="wrap">

            

                <h2>MRJ Payouts History</h2>

                <?php $payouts_table->views(); ?>

                <form id="commissions-filter" method="get">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php // $commissions_table->add_search_box( __( 'Search commissions', 'listeo_core' ), 's' ); ?>
                    <?php $payouts_table->display(); ?>
                </form>
            </div>
            <?php

        }
        
    }

    function get_payout($id){
              
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . "listeo_core_commissions_payouts WHERE ID = %d", $id ), ARRAY_A );  
    }
    function get_payouts($args){

        global $wpdb;

            $default_args = array(
                'order_id'     => 0,
                'user_id'      => 0,
                'status'       => 'unpaid',
                'm'            => false,
                'number'       => '',
                'offset'       => '',
                'paged'        => '',
                'orderby'      => 'date',
                'order'        => 'DESC',
                'fields'       => 'ids',
                'table'        => $wpdb->prefix . "listeo_core_commissions_payouts"
            );

            $args = wp_parse_args( $args, $default_args );

            $table = $args['table'];

        

            // First let's clear some variables
            $where = '';
            $limits = '';
            $join = '';
            $groupby = '';
            $orderby = '';

            // query parts initializating
            $pieces = array( 'where', 'groupby', 'join', 'orderby', 'limits' );

            
            if ( ! empty( $args['id'] ) ) {
                $where .= $wpdb->prepare( " AND c.order_id = %d", $args['order_id'] );
            }
            if ( ! empty( $args['user_id'] ) ) {
                $where .= $wpdb->prepare( " AND c.user_id = %d", $args['user_id'] );
            }
          
            if ( ! empty( $args['status'] ) && 'all' != $args['status'] ) {
                if ( is_array( $args['status'] ) ) {
                    $args['status'] = implode( "', '", $args['status'] );
                }
                $where .= sprintf( " AND c.status IN ( '%s' )", $args['status'] );
            }

            if ( 'ASC' === strtoupper( $args['order'] ) ) {
                $args['order'] = 'ASC';
            } else {
                $args['order'] = 'DESC';
            }

            // Order by.
            if ( empty( $args['orderby'] ) ) {
                /*
                 * Boolean false or empty array blanks out ORDER BY,
                 * while leaving the value unset or otherwise empty sets the default.
                 */
                if ( isset( $args['orderby'] ) && ( is_array( $args['orderby'] ) || false === $args['orderby'] ) ) {
                    $orderby = '';
                } else {
                    $orderby = "c.ID " . $args['order'];
                }
            } elseif ( 'none' == $args['orderby'] ) {
                $orderby = '';
            } else {
                $orderby_array = array();
                if ( is_array( $args['orderby'] ) ) {
                    foreach ( $args['orderby'] as $_orderby => $order ) {
                        $orderby = addslashes_gpc( urldecode( $_orderby ) );

                        if ( ! is_string( $order ) || empty( $order ) ) {
                            $order = 'DESC';
                        }

                        if ( 'ASC' === strtoupper( $order ) ) {
                            $order = 'ASC';
                        } else {
                            $order = 'DESC';
                        }

                        $orderby_array[] = $orderby . ' ' . $order;
                    }
                    $orderby = implode( ', ', $orderby_array );

                } else {
                    $args['orderby'] = urldecode( $args['orderby'] );
                    $args['orderby'] = addslashes_gpc( $args['orderby'] );

                    foreach ( explode( ' ', $args['orderby'] ) as $i => $orderby ) {
                        $orderby_array[] = $orderby;
                    }
                    $orderby = implode( ' ' . $args['order'] . ', ', $orderby_array );

                    if ( empty( $orderby ) ) {
                        $orderby = "c.ID " . $args['order'];
                    } elseif ( ! empty( $args['order'] ) ) {
                        $orderby .= " {$args['order']}";
                    }
                }
            }

            // Paging
            if ( ! empty($args['paged']) && ! empty($args['number']) ) {
                $page = absint($args['paged']);
                if ( !$page )
                    $page = 1;

                if ( empty( $args['offset'] ) ) {
                    $pgstrt = absint( ( $page - 1 ) * $args['number'] ) . ', ';
                }
                else { // we're ignoring $page and using 'offset'
                    $args['offset'] = absint( $args['offset'] );
                    $pgstrt      = $args['offset'] . ', ';
                }
                $limits = 'LIMIT ' . $pgstrt . $args['number'];
            }

            $clauses = compact( $pieces );

            $where   = isset( $clauses['where'] ) ? $clauses['where'] : '';
            $groupby = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';
            $join    = isset( $clauses['join'] ) ? $clauses['join'] : '';
            $orderby = isset( $clauses['orderby'] ) ? $clauses['orderby'] : '';
            $limits  = isset( $clauses['limits'] ) ? $clauses['limits'] : '';

            if ( ! empty($groupby) )
                $groupby = 'GROUP BY ' . $groupby;
            if ( !empty( $orderby ) )
                $orderby = 'ORDER BY ' . $orderby;

            $found_rows = '';
            if ( ! empty( $limits ) ) {
                $found_rows = 'SQL_CALC_FOUND_ROWS';
            }

            $fields = 'c.ID';

            if( 'count' != $args['fields'] && 'ids' != $args['fields'] ){
                if( is_array( $args['fields'] ) ){
                    $fields = implode( ',', $args['fields'] );
                }

                else {
                    $fields = $args['fields'];
                }
            }

            $res = $wpdb->get_col( "SELECT $found_rows DISTINCT $fields FROM $table c $join WHERE 1=1 $where $groupby $orderby $limits" );

            // return count
            if ( 'count' == $args['fields'] ) {
                return ! empty( $limits ) ? $wpdb->get_var( 'SELECT FOUND_ROWS()' ) : count( $res );
            }

            return $res;
        }

}


if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


if ( ! class_exists( 'Listeo_Payouts_List_Table' ) ) {
    /**
     *
     *
     * @class class.yith-commissions-list-table
     * @package    Yithemes
     * @since      Version 1.0.0
     * @author     Your Inspiration Themes
     *
     */
    class Listeo_Payouts_List_Table extends WP_List_Table {
    /** Class constructor */
        public function __construct() {

            parent::__construct( [
                'singular' => __( 'Payout', 'listeo-core' ), // singular name of the listed records
                'plural'   => __( 'Payouts', 'listeo-core' ), // plural name of the listed records
                'ajax'     => false // does this table support ajax?
            ] );

        }


        /**
         * Returns columns available in table
         *
         * @return array Array of columns of the table
         * @since 1.0.0
         */
        public function get_columns() {
            $columns = array(
                    'id'             => __( 'ID', 'listeo_core' ),
                    'user_id'        => __( 'User', 'listeo_core' ),
                    //'status'         => __( 'Status', 'listeo_core' ),
                    'orders'         => __( 'Orders number', 'listeo_core' ),
                    'payment_method' => __( 'Payment method', 'listeo_core' ),
                    'amount'         => __( 'Amount', 'listeo_core' ),
                    'date'           => __( 'Date', 'listeo_core' ),
                    'actions'   => __( 'Actions', 'listeo_core' ),
                
            );

            

            return $columns;
        }

         public function prepare_items() {            

                $columns = $this->get_columns();
                $hidden = $this->get_hidden_columns();
                $sortable = $this->get_sortable_columns();
                
                $data = $this->table_data();

                usort( $data, array( &$this, 'sort_data' ) );
                
                $perPage = 8;
                
                $currentPage = $this->get_pagenum();
                $totalItems = count($data);
                
                $this->set_pagination_args( array(
                    'total_items' => $totalItems,
                    'per_page'    => $perPage
                ) );
                
                $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
                
                $this->_column_headers = array($columns, $hidden, $sortable);
                $this->items = $data;


        }
        /**
         * Get the table data
         *
         * @return Array
         */
        private function table_data() {

            $data = array();

            $args = array(
                'status'           => 'all',
                
            );
            $payouts_class = new Listeo_Core_Payouts;
            $payouts = $payouts_class->get_payouts($args);
            foreach ($payouts as $key => $id) {
                $data[] = $payouts_class->get_payout($id);
                
            }
             
            return $data;
        }
         /**
         * Define what data to show on each column of the table
         *
         * @param  Array $item        Data
         * @param  String $column_name - Current column name
         *
         * @return Mixed
         */
        public function column_default( $item, $column_name ){
            switch( $column_name ) {
                case 'id':
                
                    return $item[ $column_name ];
                break;

                case 'user_id':
                    return '<a href="'.esc_url( get_author_posts_url($item['user_id'])).'">'.get_the_author_meta('display_name',$item['user_id']).'</a>';
                break;

                case 'status':
                    echo $item['status'];
                break;

                case 'orders':
                    echo count(json_decode($item['orders']));
                break;
                
                case 'amount':
                    if(function_exists('wc_price')) {
                        echo wc_price($item[ $column_name ]);
                    } else { echo $item[ $column_name ]; };
                break;
                
                case 'date':
                    echo date(get_option( 'date_format' ), strtotime($item['date']));
                break;

                case 'payment_method':

                    if ($item['payment_method'] === 'paypal'){
                        $payment_method = 'PayPal';
                    }else if ($item['payment_method'] === 'PayPal Payout'){
                        $payment_method = 'PayPal Payout';
                    }else {
                        $payment_method = 'Bank Transfer';
                    }
                    
                    echo $payment_method ;
                break;

               
                
                case 'actions':
                $url = admin_url( 'admin.php?page=listeo_payouts_list');
                
                $payout_url = esc_url( add_query_arg( 'view_payout', $item['id'], $url ) );
               
                printf( '<a class="button-primary view" href="%1$s" data-tip="%2$s">%2$s</a>', $payout_url, __( 'View Details', 'listeo_core' ) );
                break;
                default:
                    return print_r( $item, true ) ;
            }
        }
        public function get_hidden_columns() {
            return array();
        }
        function no_items() {
            _e( 'No payouts set.','listeo_core' );
        }

    }
}