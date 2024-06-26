<?php
/**
 * Plugin Name: CRM in Cloud for WC
 * Plugin URI: https://www.ilghera.com/product/crm-in-cloud-for-woocommerce
 * Description: Synchronize your WordPress/ WooCommerce site with CRM in Cloud exporting users and orders in real time
 * Version: 1.2.0
 * Stable tag: 1.2.0
 * Requires at least: 4.0
 * Tested up to: 6.5
 * WC tested up to: 8
 * Author: ilGhera
 * Author URI: https://ilghera.com
 * Text Domain: crm-in-cloud-for-wc
 * Domain Path: /languages
 *
 * @package crm-in-cloud-for-wc
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin notice for WooCommerce not installed
 *
 * @return void
 */
function crmfwc_wc_not_installed() {

	echo '<div class="notice notice-error is-dismissible">';

		esc_html_e( 'WARNING! CRM in Cloud for WC requires WooCommerce to be activated.', 'crm-in-cloud-for-wc' );

	echo '</div>';

}


/**
 * HPOS
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


/**
 * Handles the plugin activation
 *
 * @return void
 */
function load_crmfwc() {

	/*Function check */
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	/*WooCommerce must be installed*/
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

		add_action( 'admin_notices', 'crmfwc_wc_not_installed' );

	} else {

		/*Constants declaration*/
		define( 'CRMFWC_VERSION', '1.3.0' );
		define( 'CRMFWC_DIR', plugin_dir_path( __FILE__ ) );
		define( 'CRMFWC_URI', plugin_dir_url( __FILE__ ) );
		define( 'CRMFWC_FILE', __FILE__ );
		define( 'CRMFWC_ADMIN', CRMFWC_DIR . 'admin/' );
		define( 'CRMFWC_DIR_NAME', basename( dirname( __FILE__ ) ) );
		define( 'CRMFWC_INCLUDES', CRMFWC_DIR . 'includes/' );
		define( 'CRMFWC_SETTINGS', admin_url( 'admin.php?page=crm-in-cloud-for-wc' ) );

		/*Internationalization*/
		$locale = apply_filters( 'plugin_locale', get_locale(), 'crm-in-cloud-for-wc' );
		load_plugin_textdomain( 'crm-in-cloud-for-wc', false, basename( CRMFWC_DIR ) . '/languages' );
		load_textdomain( 'crm-in-cloud-for-wc', trailingslashit( WP_LANG_DIR ) . basename( CRMFWC_DIR ) . '/crm-in-cloud-for-wc-' . $locale . '.mo' );

		/*Files required*/
		require_once CRMFWC_ADMIN . 'class-crmfwc-admin.php';
		require_once CRMFWC_INCLUDES . 'crmfwc-functions.php';
		require_once CRMFWC_INCLUDES . 'class-crmfwc-call.php';
		require_once CRMFWC_INCLUDES . 'class-crmfwc-settings.php';
		require_once CRMFWC_INCLUDES . 'class-crmfwc-products.php';
		require_once CRMFWC_INCLUDES . 'class-crmfwc-contacts.php';
		require_once CRMFWC_INCLUDES . 'class-crmfwc-progress-bar.php';
		require_once CRMFWC_INCLUDES . 'wc-checkout-fields/class-crmfwc-checkout-fields.php';
		require_once CRMFWC_DIR . 'vendor/action-scheduler/action-scheduler.php';

	}

}
add_action( 'plugins_loaded', 'load_crmfwc', -1 );

