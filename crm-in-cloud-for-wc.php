<?php
/**
 * Plugin Name: CRM in Cloud for WC
 * Plugin URI: https://www.ilghera.com/product/crm-in-cloud-for-woocommerce
 * Description: Synchronize your WordPress/ WooCommerce site with CRM in Cloud exporting users and orders in real time
 * Version: 1.2.5
 * Stable tag: 1.2.5
 * Requires at least: 5.0
 * Tested up to: 7.1
 * WC tested up to: 11.1.2
 * Author: ilGhera
 * Author URI: https://ilghera.com
 * Text Domain: crm-in-cloud-for-wc
 * Domain Path: /languages
 *
 * @package crm-in-cloud-for-wc
 */

defined( 'ABSPATH' ) || exit;

/* Define core plugin constants. */
if ( ! defined( 'CRMFWC_VERSION' ) ) {
    define( 'CRMFWC_VERSION', '1.2.5' );
}
if ( ! defined( 'CRMFWC_DIR' ) ) {
    define( 'CRMFWC_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CRMFWC_URI' ) ) {
    define( 'CRMFWC_URI', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'CRMFWC_FILE' ) ) {
    define( 'CRMFWC_FILE', __FILE__ );
}
if ( ! defined( 'CRMFWC_ADMIN' ) ) {
    define( 'CRMFWC_ADMIN', CRMFWC_DIR . 'admin/' );
}
if ( ! defined( 'CRMFWC_INCLUDES' ) ) {
    define( 'CRMFWC_INCLUDES', CRMFWC_DIR . 'includes/' );
}
if ( ! defined( 'CRMFWC_DIR_NAME' ) ) {
    define( 'CRMFWC_DIR_NAME', basename( dirname( __FILE__ ) ) );
}
if ( ! defined( 'CRMFWC_SETTINGS' ) ) {
    define( 'CRMFWC_SETTINGS', admin_url( 'admin.php?page=crm-in-cloud-for-wc' ) );
}


/* Load all core class files that will be used by the main plugin class or other parts. */
require_once CRMFWC_ADMIN . 'class-crmfwc-admin.php';
require_once CRMFWC_INCLUDES . 'crmfwc-functions.php';
require_once CRMFWC_INCLUDES . 'class-crmfwc-call.php';
require_once CRMFWC_INCLUDES . 'class-crmfwc-settings.php';
require_once CRMFWC_INCLUDES . 'class-crmfwc-products.php';
require_once CRMFWC_INCLUDES . 'class-crmfwc-contacts.php';
require_once CRMFWC_INCLUDES . 'class-crmfwc-progress-bar.php';
require_once CRMFWC_INCLUDES . 'wc-checkout-fields/class-crmfwc-checkout-fields.php';

/* Load Action Scheduler library from the main plugin directory. */
require_once CRMFWC_DIR . 'vendor/action-scheduler/action-scheduler.php';


/**
 * Main class of the CRM in Cloud for WooCommerce plugin.
 */
final class CrmFwc_Free {

    /* Stores the single instance of the plugin class. */
    private static $instance = null;

    /* The plugin version. */
    private $version = CRMFWC_VERSION;

    /**
     * The class constructor.
     */
    private function __construct() {
        $this->setup_hooks();
    }

    /**
     * Gets the single instance of the plugin.
     *
     * @return CrmFwc_Premium
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Sets up all WordPress action and filter hooks.
     */
    private function setup_hooks() {
        /* Hook for early plugin loading and essential checks. */
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -10 );

        /* Main plugin initialization hook: ideal for text domain and class instantiation. */
        add_action( 'init', array( $this, 'on_init' ), 0 );

        /* HPOS compatibility hook. */
        add_action( 'before_woocommerce_init', array( $this, 'hpos_compatibility' ) );
    }

    /**
     * Method hooked to 'plugins_loaded'.
     */
    public function on_plugins_loaded() {
        /* Ensure 'is_plugin_active' function is available for activation logic. */
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        /* Deactivate the free version of the plugin if it's active. */
        if ( function_exists( 'load_crmfwc' ) ) {
            deactivate_plugins( 'crm-in-cloud-for-wc/crm-in-cloud-for-wc.php' );
            remove_action( 'plugins_loaded', 'load_crmfwc' );
            wp_safe_redirect( admin_url( 'plugins.php?plugin_status=all&paged=1&s' ) );
            exit; /* Important to exit after redirect. */
        }

        /* WooCommerce must be installed and active. */
        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            add_action( 'admin_notices', array( $this, 'wc_not_installed_notice' ) );
            return; /* Prevent further initialization if WooCommerce is not active. */
        }
    }

    /**
     * Method hooked to 'init'.
     */
    public function on_init() {
        /* Load plugin text domain for internationalization. */
        /* This is the correct place to load the text domain to avoid the 'just_in_time' notice. */
        load_plugin_textdomain( 'crm-in-cloud-for-wc', false, basename( CRMFWC_DIR ) . '/languages' );
        $locale = apply_filters( 'plugin_locale', get_locale(), 'crm-in-cloud-for-wc' );
        load_textdomain( 'crm-in-cloud-for-wc', trailingslashit( WP_LANG_DIR ) . basename( CRMFWC_DIR ) . '/crm-in-cloud-for-wc-' . $locale . '.mo' );

        /* Instantiate core classes. */
        /* These classes are only instantiated if WooCommerce is active (checked in on_plugins_loaded). */
        new CRMFWC_Admin();
        new CRMFWC_Settings( true );
        new CRMFWC_Products( true );
        new CRMFWC_Contacts();
        new CRMFWC_Progress_Bar();
        new CRMFWC_Checkout_Fields();
    }

    /**
     * Declares compatibility with WooCommerce's High-Performance Order Storage (HPOS).
     */
    public function hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CRMFWC_FILE, true );
        }
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

/* Plugin Initialization */
CrmFwc_Free::get_instance();

/*
 * This hook ensures that the custom cron job (e.g., from Action Scheduler) is removed cleanly
 * when your plugin is deactivated, which is crucial for proper plugin management.
 */
register_deactivation_hook( CRMFWC_FILE, function() {
    /* Implement deactivation logic here if needed. */
    /* If you have specific Action Scheduler queues or custom data to clean up, add it here. */
    /* For instance, if you have a cleaner class like WCIFD_AS_Cleaner, you would call its static method here. */
});

