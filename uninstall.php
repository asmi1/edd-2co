<?php

// If uninstall.php is not called by WordPress, die
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Bail if EDD is inactive
if ( !class_exists( 'Easy_Digital_Downloads' ) ) {
	return;
}

// Options
$options = array( 'edd2co_account_number', 'edd2co_secret_word', 'edd2co_is_sandbox' );

foreach ( $options as $option ) {

	// Removes edd setting value in both the db and the global variable
	edd_delete_option( $option );
}