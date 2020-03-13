<?php
/**
 * Plugin Name: CRM in Cloud for WooCommerce - Premium
 * Plugin URI: https://www.ilghera.com/product/crm-in-cloud-for-woocommerce
 * Description: xxx
 * Author: ilGhera
 * Version: 0.9.0
 * Author URI: https://ilghera.com
 * Requires at least: 4.0
 * Tested up to: 5.3
 * WC tested up to: 3
 * Text Domain: crmfwc
 */

/**
 * Handles the plugin activation
 *
 * @return void
 */
function load_wc_exporter_for_reviso() {

	/*Function check */
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	/*Internationalization*/
	load_plugin_textdomain( 'crmfwc', false, basename( dirname( __FILE__ ) ) . '/languages' );

	/*Constants declaration*/
	define( 'CRMFWC_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CRMFWC_URI', plugin_dir_url( __FILE__ ) );
	define( 'CRMFWC_FILE', __FILE__ );
	define( 'CRMFWC_ADMIN', CRMFWC_DIR . 'admin/' );
	define( 'CRMFWC_DIR_NAME', basename( dirname( __FILE__ ) ) );
	define( 'CRMFWC_INCLUDES', CRMFWC_DIR . 'includes/' );
	define( 'CRMFWC_SETTINGS', admin_url( 'admin.php?page=wc-exporter-for-reviso' ) );

	/*Files required*/
	require( CRMFWC_ADMIN . 'class-crmfwc-admin.php' );
	require( CRMFWC_INCLUDES . 'crmfwc-functions.php' );
	require( CRMFWC_INCLUDES . 'class-crmfwc-call.php' );
	require( CRMFWC_INCLUDES . 'class-crmfwc-settings.php' );
	require( CRMFWC_INCLUDES . 'class-crmfwc-contacts.php' );

}
add_action( 'plugins_loaded', 'load_wc_exporter_for_reviso', 10 );
