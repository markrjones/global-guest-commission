<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
/**
 * Listeo_Core_Listing class
 * 
 * Extended for the plugin. Don't really need to extend it, we could put this in the plugin's
 * main file but doing this for consistency.
 * 
 * The listeo-core version of this status change function is still active but this one has the 
 * priority to execute first. The other executes too but the function contains a check to see
 * if the commission has already been processed, so we can let that happen.
 * 
 */
class Mrj_Global_Guest_Commission_Listeo_Core_Commissions extends Listeo_Core_Commissions {

	private static $_instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.26
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	//fixed/percentage

	public function __construct() {
			
        add_action( 'woocommerce_order_status_changed', array( $this, 'a_order_status_change' ), 9, 3 );
		// add_action( 'woocommerce_refund_created', array( $this, 'register_commission_refund' ) );
		
		add_shortcode( 'listeo_wallet', array( $this, 'listeo_wallet' ) );

		
	}


	public function a_order_status_change( $order_id, $old_status, $new_status ) {        

			switch ( $new_status ) {

				case 'completed' :
					$this->register_commission( $order_id );
					
					break;

				// case 'refunded' :
				// 	$this->register_commissions_refunded( $order_id );
				// 	break;

				case 'refunded' :
				case 'cancelled' :
				case 'failed' :
					$this->delete_commission( $order_id );
					break;

				// case 'pending':
				// case 'on-hold':
				// 	$this->register_commissions_pending( $order_id );
				// 	break;

			}
	}

	function delete_commission($order_id){
		if ( ! $order_id ) {
				return;
			}

			global $wpdb;
			$wpdb->delete(  $wpdb->prefix . "listeo_core_commissions", array( 'order_id' => $order_id ) );
	}

	function register_commission($order_id){
        error_log("in register commission");
        error_log(($order_id));
		$order = wc_get_order( $order_id );
		if(!$order){
			return;
		}

		// check payment method based on order id
		$payment_method = $order->get_payment_method();
		//skip if payment method is cod
		if($payment_method == 'cod'){
			return;
		}
		$processed = $order->get_meta( '_listeo_commissions_processed', true );

		if ( $processed && $processed == 'yes' ) {
			return;
		}

		$order_data = $order->get_data();
		
		$args['order_id'] = $order_id;
		$args['user_id'] = $order->get_meta('owner_id');
		$args['booking_id'] = $order->get_meta('booking_id');
		$args['listing_id'] = $order->get_meta('listing_id');
		$args['rate'] = (float) get_option('listeo_commission_rate',10)/100;

		$connect_processed = $order->get_meta('listeo_stripe_connect_processed', true);
	
		if ($connect_processed) {
			$args['status'] = 'paid';
		} else {
			$args['status'] = 'unpaid';
		}

		$order_total = $order->get_total();

		// MRJ
		// the order_total includes the guest commission we added, so we need to subtract that from 
		// the total before deal with the Listeo site commission set in the options

		$options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
		$guest_commission_rate = (float) $options['percentage'];
		$amount_before_site_fee = $order_total / (1 + $guest_commission_rate / 100);
        //$guest_commission_value = $order_total - $originalAmount . "\n";
		error_log("final total: " . $amount_before_site_fee);

		//get order value before tax
		//$order_total = $order_total - $order_data['total_tax'];
		$args['amount'] = (float) $amount_before_site_fee * $args['rate'];
		$args['type'] = "percentage";	

		$commission_id = $this->insert_commission( $args );
        error_log("comm id");
        error_log($commission_id);
		if($commission_id){
			$order->add_meta_data( '_listeo_commissions_id', $commission_id, true );
			$order->add_meta_data( '_listeo_commissions_processed', 'yes', true );
	        $order->save_meta_data();
		}
		// Mark commissions as processed
		
	}
}