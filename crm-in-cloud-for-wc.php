<?php
/**
 * Plugin Name: CRM in Cloud for WC - Premium
 * Plugin URI: https://www.ilghera.com/product/crm-in-cloud-for-woocommerce
 * Description: Synchronize your WordPress/ WooCommerce site with CRM in Cloud exporting users and orders in real time
 * Version: 1.3.1
 * Stable tag: 1.3.1
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC tested up to: 9
 * Author: ilGhera
 * Author URI: https://ilghera.com
 * Text Domain: crm-in-cloud-for-wc
 * Domain Path: /languages
 *
 * @package crm-in-cloud-for-wc
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class of the CRM in Cloud for WooCommerce plugin.
 */
class CrmFwc_Premium {

    /**
     * The class constructor.
     */
    public function __construct() {
        /* Adds HPOS support before WooCommerce initialization. */
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );

        /*
         * Loads dependencies and starts the plugin on the 'plugins_loaded' action.
         * We use a higher priority (-10) to ensure it runs before other plugins.
         */
        add_action( 'plugins_loaded', array( $this, 'load_dependencies' ), -10 );

        /*
         * Performs the main plugin initialization on the 'init' action.
         * This is the ideal point to load the text domain.
         */
        add_action( 'init', array( $this, 'init_plugin' ) );
    }

    /**
     * Declares compatibility with Custom Order Tables (HPOS).
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }

    /**
     * Loads essential dependencies and checks prerequisites.
     * This function runs on the 'plugins_loaded' action.
     */
    public function load_dependencies() {
        /* Checks if the is_plugin_active function exists. */
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        /* Deactivates the free version if present. */
        if ( function_exists( 'load_crmfwc' ) ) {
            deactivate_plugins( 'crm-in-cloud-for-wc/crm-in-cloud-for-wc.php' );
            remove_action( 'plugins_loaded', 'load_crmfwc' );
            wp_safe_redirect( admin_url( 'plugins.php?plugin_status=all&paged=1&s' ) );
            exit; /* Important to exit after redirect. */
        }

        /* WooCommerce must be installed and active. */
        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            add_action( 'admin_notices', array( $this, 'wc_not_installed_notice' ) );
        }
    }

    /**
     * Initializes the plugin: defines constants and includes necessary files.
     * This function runs on the 'init' action.
     */
    public function init_plugin() {
        /* If WooCommerce is not active, do not proceed with initialization. */
        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            return;
        }

        /* Declaration of constants. */
        define( 'CRMFWC_VERSION', '1.3.1' );
        define( 'CRMFWC_DIR', plugin_dir_path( __FILE__ ) );
        define( 'CRMFWC_URI', plugin_dir_url( __FILE__ ) );
        define( 'CRMFWC_FILE', __FILE__ );
        define( 'CRMFWC_ADMIN', CRMFWC_DIR . 'admin/' );
        define( 'CRMFWC_DIR_NAME', basename( dirname( __FILE__ ) ) );
        define( 'CRMFWC_INCLUDES', CRMFWC_DIR . 'includes/' );
        define( 'CRMFWC_SETTINGS', admin_url( 'admin.php?page=crm-in-cloud-for-wc' ) );

        /*
         * Internationalization.
         * Moved here to respect the 'init' hook.
         */
        $locale = apply_filters( 'plugin_locale', get_locale(), 'crm-in-cloud-for-wc' );
        load_plugin_textdomain( 'crm-in-cloud-for-wc', false, basename( CRMFWC_DIR ) . '/languages' );
        load_textdomain( 'crm-in-cloud-for-wc', trailingslashit( WP_LANG_DIR ) . basename( CRMFWC_DIR ) . '/crm-in-cloud-for-wc-' . $locale . '.mo' );

        /* Required files. */
        require_once CRMFWC_ADMIN . 'class-crmfwc-admin.php';
        require_once CRMFWC_ADMIN . 'ilghera-notice/class-ilghera-notice.php';
        require_once CRMFWC_INCLUDES . 'crmfwc-functions.php';
        require_once CRMFWC_INCLUDES . 'class-crmfwc-call.php';
        require_once CRMFWC_INCLUDES . 'class-crmfwc-settings.php';
        require_once CRMFWC_INCLUDES . 'class-crmfwc-products.php';
        require_once CRMFWC_INCLUDES . 'class-crmfwc-contacts.php';
        require_once CRMFWC_INCLUDES . 'class-crmfwc-progress-bar.php';
        require_once CRMFWC_INCLUDES . 'wc-checkout-fields/class-crmfwc-checkout-fields.php';
        require_once CRMFWC_DIR . 'vendor/action-scheduler/action-scheduler.php';
    }

    /**
     * Admin notice for WooCommerce not installed.
     */
    public function wc_not_installed_notice() {
        echo '<div class="notice notice-error is-dismissible">';
        esc_html_e( 'WARNING! CRM in Cloud for WC requires WooCommerce to be activated.', 'crm-in-cloud-for-wc' );
        echo '</div>';
    }
}

/* Initializes the main plugin class. */
new CrmFwc_Premium();

