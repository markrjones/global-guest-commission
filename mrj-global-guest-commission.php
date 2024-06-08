<?php
   /*
   Plugin Name: Global Guest Commission
   Plugin URI: 
   description: Applies a % commission fee to all bookings.
   Version: 0.6
   Author: Mark Jones
   Author URI: https://devlisteo.ownersclub.eu
   */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function mrjgcc_enqueue_scripts() {
    wp_dequeue_script( 'listeo_core-bookings');
    wp_deregister_script( 'listeo_core-bookings' );

    wp_register_script( 'mrjgcc', plugin_dir_url(__FILE__) . 'assets/js/mrjbooking.js', array( 'jquery' ), '1.0' );
    wp_enqueue_script('mrjgcc');
}
add_action( 'wp_enqueue_scripts', 'mrjgcc_enqueue_scripts', 999 );

include( 'includes/mrj-global-guest-commission-class-listeo-core-bookings-calendar.php' );
include( 'includes/mrj-global-guest-commission-class-template-loader.php');
include( 'includes/mrj-global-guest-commission-class-listeo-core-commissions.php');

$my_commissions = new Mrj_Global_Guest_Commission_Listeo_Core_Commissions();

define( 'MRJ_GLOBAL_GUEST_COMMISSION_PATH', plugin_dir_path( __FILE__ ) );

$wp_content_dir = trailingslashit( WP_CONTENT_DIR );

/**
 * Widgets init (this is the way listeo-core does it)
 */
function mrj_widgets_init() {
    include_once( 'includes/mrj-global-guest-commission-class-widget.php' );
}
add_action( 'widgets_init', 'mrj_widgets_init' );

// All below here deals with the settings menu
add_action( 'admin_menu', 'mrjgcc_create_menu' );
function mrjgcc_create_menu() {
             
    //create custom top-level menu
    add_menu_page( 'Guest Commission', 'Guest Commission Settings',
        'manage_options', 'mrjgcc-options', 'mrjgcc_settings_page',
        'dashicons-money-alt', 99 );
            
}

add_action('admin_init', 'mrjgcc_plugin_admin_init');
function mrjgcc_plugin_admin_init(){

	// Define the setting args
	$args = array(
	    'type' 				=> 'string', 
	    'sanitize_callback' => 'mrjgcc_plugin_validate_options',
	    'default' 			=> NULL
	);

    // Register our settings
    register_setting( 'mrjgcc_plugin_options', 'mrjgcc_plugin_options', $args );
    
    // Add a settings section
    add_settings_section( 
    	'mrjgcc_plugin_main', 
    	'Guest Commission Settings',
        'mrjgcc_plugin_section_text', 
        'mrjgcc_plugin' 
    );
    
    // Create our settings field for name
    add_settings_field( 
    	'mrjgcc_plugin_title', 
    	'Description Text',
        'mrjgcc_plugin_setting_title', 
        'mrjgcc_plugin', 
        'mrjgcc_plugin_main' 
    );

    
    // Create our settings field for favorite holiday
    add_settings_field( 
    	'mrjgcc_plugin_commission_pc', 
    	'Percentage',
        'mrjgcc_plugin_setting_commission_pc', 
        'mrjgcc_plugin', 
        'mrjgcc_plugin_main' 
    );


}

// Draw the section header
function mrjgcc_plugin_section_text() {
        echo '<p>Settings to control the % rate charged as commission to guests, and the text to describe the charge</p>';
}
        
// Display and fill the Name text form field
function mrjgcc_plugin_setting_title() {

    // Get option 'text_string' value from the database
    $options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "Percentage" => 5 ));
    $title = $options['title'];

    // Echo the field
    echo "<input id='title' name='mrjgcc_plugin_options[title]'
        type='text' value='" . esc_attr( $title ) . "' />";

}

// Display and select the favorite holiday select field
function mrjgcc_plugin_setting_commission_pc() {

	$options = get_option('mrjgcc_plugin_options', [ 'Percentage' => 5 ] );
	$percentage = $options['percentage'];
	
	echo "<input id='percentage' name='mrjgcc_plugin_options[percentage]' value='" . esc_attr( $percentage ) . "' />";

}

// Validate user input for all three options
function mrjgcc_plugin_validate_options( $input ) {

	// Only allow letters and spaces for the name
    $valid['title'] = preg_replace(
        '/[^a-zA-Z\s]/',
        '',
        $input['title'] );
        
    if( $valid['title'] !== $input['title'] ) {

        add_settings_error(
            'mrjgcc_plugin_text_string',
            'mrjgcc_plugin_texterror',
            'Incorrect value entered! Please only input letters and spaces.',
            'error'
        );

    }
        
    // Sanitize the data we are receiving 
    $valid['title'] = sanitize_text_field( $input['title'] );
    $valid['percentage'] = sanitize_text_field( $input['percentage'] );

    return $valid;
}

//placerholder function for the settings page
function mrjgcc_settings_page() {
    ?>
    <div class="wrap">
        <form action="options.php" method="post">
        <?php 
            settings_fields( 'mrjgcc_plugin_options' );
    	    do_settings_sections( 'mrjgcc_plugin' );
		    submit_button( 'Save Changes', 'primary' ); 
        ?>
        </form>
    </div>
    <?php
}

?>