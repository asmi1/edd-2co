<?php

/**
 * 2Checkout for Easy Digital Downloads (Off-site Checkout Only)
 *
 * TT EDD 2Checkout is a 2Checkout payment gateway for easy digital download to accept Credit Card and PayPal 
 * payments (currently supports off-site checkout only).
 *
 * @package   edd-2co
 * @copyright 2018 Themient.com, Asmi Khalil
 *
 * Plugin Name: TT EDD 2Checkout
 * Description: 2Checkout payment gateway for easy digital download to accept Credit Card and PayPal 
 * payments (currently supports off-site checkout only).
 * Version:     1.0.0
 * Author:      Themient
 * Author URI:  http://themient.com
 * License: GPLv3 or later
 * Text Domain: edd2co
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// Bail if EDD is inactive
if ( !class_exists( 'Easy_Digital_Downloads' ) ) {
	return;
}

/**
 * Include the core class responsible for loading all necessary components of the plugin.
 */
require_once( plugin_dir_path( __FILE__ ) . 'class-edd2co.php' );

/**
 * Loads a single instance of EDD2CO
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $edd_2co = edd_2co(); ?>
 *
 * @since 1.0.0
 *
 * @see EDD2CO::get_instance()
 *
 * @return object Returns an instance of the EDD2CO class
 */
function edd_2co() {
    return EDD2CO::get_instance();
}

/**
 * Loads plugin after all the others have loaded and have registered their
 * hooks and filters
 */
add_action( 'plugins_loaded', 'edd_2co', apply_filters( 'edd2co_action_priority', 10 ) );

/**
 * Register plugin activation Hook
 */
register_activation_hook( __FILE__, 'edd2co_activation_hook' );
function edd2co_activation_hook() {
	set_transient( 'edd2co_activated', true, 5 );
}