<?php
/*
Plugin Name: SAR Update Gravity Forms License Networkwide
Description: Update Gravity Forms license key in all sites of a WordPress multisite network
Author: Samuel Aguilera
Version: 1.0
Author URI: http://www.samuelaguilera.com
License: GPL3
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('network_admin_menu', 'sar_update_gf_license_key_submenu_page');

function sar_update_gf_license_key_submenu_page() {
    add_submenu_page(
        'index.php',
        'Update GF License',
        'Update GF License',
        'manage_network',
        'network-update-gf-license',
        'sar_update_gf_key_callback' );
}

function sar_update_gf_key_callback() {
    if ( isset( $_POST['update_gf_license'] ) ) {

		if ( ! wp_verify_nonce( $_POST['update_gf_license_nonce'], 'update-gf-license' ) ) {
			wp_die('Security check not passed!');
		}

        // Start the party!
        sar_update_gf_key_networkwide();

        // The party is over!
        echo '<div id="message" class="updated fade"><p>Job done!</p></div>';
	}

  $gf_settings = esc_url( get_admin_url( null, 'admin.php?page=gf_settings' ) );

	echo '<div class="wrap">';
	echo '<h1>Update Gravity Forms License key Networkwide</h1>';

	if ( defined ( 'GF_LICENSE_KEY' ) ){
		echo '<p><b>NOTE: Before start, make sure to generate a full database backup, just in case.</b></p>';
		echo '<p>License key: ' . GF_LICENSE_KEY . '</p>';
		echo '<p>The purpose of this tool is to allow you to update the license key in all sites of a WordPress multisite network with only one click. It will save the key, remove the UNLICENSED COPY message (if the key used is valid), and validate the key in the Gravity Forms settings page of each site with it activated. To do it simply click on the button below and wait. It is recommended to enable Logging in <a href="' . $gf_settings . '">Forms -> Settings</a> and set it to <b>log all messages for Gravity Forms core before using this tool</b>.</p>';
		echo '<p>If you have a lot of sites in your network it could take a while (and you may need to increase the value for max_execution_time in your PHP settings), <b>be patient and DO NOT close the browser before time, also DO NOT click the button more than once, BE PATIENT!</b>, a notice indicating the process was successful will be shown at the top of this page, just wait for it.</p>';
		echo '<p><form action="" method="post" enctype="multipart/form-data" name="sarfsmtp_test_email_form">';
		wp_nonce_field( 'update-gf-license', 'update_gf_license_nonce' );
		echo '<input type="hidden" name="update_gf_license" value="update_gf_license" />';
		echo '<input type="submit" class="button-primary" value="Update License" />';
		echo '</form></p>';
	} else {
		echo '<p class="notice notice-error"><a href="https://www.gravityhelp.com/documentation/article/advanced-configuration-options/#gf_license_key">GF_LICENSE_KEY</a> is not set in your wp-config.php file, you need to follow instructions on the link before being able to use this tool.</p>';
	}
	echo '</div>';

}

function sar_update_gf_key_networkwide(){

if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) && ! empty( GF_LICENSE_KEY ) ) { // Make sure we're running WP 4.6 or newer and we have a key
	$blog_ids = get_sites( array( 'fields' => 'ids' ) ); // Return only blog_id for each site of the network

	/*
  	$already_updated = get_option( 'sar_gf_key_updated' );

  	if ( $already_updated ) {
		GFCommon::log_debug( 'Key was already updated on all sites. Aborting.' );
		return;
	}
	*/

	GFCommon::log_debug( 'Number of sites in the network => ' . count( $blog_ids ) );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
			if ( class_exists ( 'GFForms' ) ) { // Prevent running if license key is empty or GF is not active for this site.
				RGFormsModel::save_key( GF_LICENSE_KEY ); // Save the key
				GFCommon::cache_remote_message(); // Remove the UNLICENSED COPY message once we have the valid key saved
				GFCommon::get_version_info( false ); // Update key validaton in Forms -> Settings page
			}
		restore_current_blog();
	  GFCommon::log_debug( 'Done for blog_id => ' . $blog_id );
	}

 	GFCommon::log_debug( 'Process done in all sites!' );

    /*
  	switch_to_blog( BLOG_ID_CURRENT_SITE ); // Switch to main site
	add_option( 'sar_gf_key_updated', true, '', 'no' ); // Add option to main site to prevent running the update again
  	restore_current_blog();
 	GFCommon::log_debug( 'Added sar_gf_key_updated to main site to prevent running this again.' );
    */

	return; // All done!

	}

}
