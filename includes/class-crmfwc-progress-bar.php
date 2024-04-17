<?php
/**
 * Progress bar 
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/includes
 *
 * @since 1.2.4
 */

/**
 * Class CRMFWC_Progress_Bar
 *
 * @since 1.2.4
 */
class CRMFWC_Progress_Bar {


    /**
     * The constructor
     *
     * @return void 
     */
    public function __construct() {

        add_action( 'admin_notices', array( $this, 'catalog_update_admin_notice' ) );
        add_action( 'wp_ajax_get-total-products-actions', array( $this, 'get_total_products_actions' ) );
        add_action( 'wp_ajax_get-scheduled-products-actions', array( $this, 'get_scheduled_products_actions' ) );
        add_action( 'wp_ajax_get-total-products-delete-actions', array( $this, 'get_total_products_delete_actions' ) );
        add_action( 'wp_ajax_get-scheduled-products-delete-actions', array( $this, 'get_scheduled_products_delete_actions' ) );
        add_action( 'wp_ajax_get-total-contacts-actions', array( $this, 'get_total_contacts_actions' ) );
        add_action( 'wp_ajax_get-scheduled-contacts-actions', array( $this, 'get_scheduled_contacts_actions' ) );
        add_action( 'wp_ajax_get-total-contacts-delete-actions', array( $this, 'get_total_contacts_delete_actions' ) );
        add_action( 'wp_ajax_get-scheduled-contacts-delete-actions', array( $this, 'get_scheduled_contacts_delete_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    }


    /**
     * Enqueue scripts
     *
     * @return void 
     */
    public function enqueue_scripts() {

        $screen = get_current_screen();

        if ( 'woocommerce_page_crm-in-cloud-for-wc' === $screen->id ) {

            wp_enqueue_script( 'crmfwc-progress-bar', CRMFWC_URI . 'js/crmfwc-progress-bar.js', array( 'jquery' ), CRMFWC_VERSION, true );

            $options = array(
                'contactsExportRunning'   => __( "The export of n°<span></span> contacts is running!", 'crm-in-cloud-for-wc' ),
                'contactsExportCompleted' => __( 'The export of n°<span></span> contacts was completed!', 'crm-in-cloud-for-wc' ),
                'contactsDeleteRunning'   => __( 'The delete of n°<span></span> contacts is running!', 'crm-in-cloud-for-wc' ),
                'contactsDeleteCompleted' => __( 'The delete of n°<span></span> contacts was completed!', 'crm-in-cloud-for-wc' ),
                'productsExportRunning'   => __( 'The export of n°<span></span> products is running!', 'crm-in-cloud-for-wc' ),
                'productsExportCompleted' => __( 'The export of n°<span></span> products was completed!', 'crm-in-cloud-for-wc' ),
                'productsDeleteRunning'   => __( 'The delete of n°<span></span> products is running!', 'crm-in-cloud-for-wc' ),
                'productsDeleteCompleted' => __( 'The delete of n°<span></span> products was completed!', 'crm-in-cloud-for-wc' ),
            );

            wp_localize_script( 'crmfwc-progress-bar', 'options', $options );

        }

    }


    /**
     * Get the total number of actions scheduled
     *
     * @return void
     */
    public function get_total_products_actions() {

        $transient = get_transient( 'crmfwc-total-products-actions' );

        echo intval( $transient );

        exit;

    }


    /**
     * Get the actions pending
     *
     * @return void
     */
    public function get_scheduled_products_actions() {

        $actions = as_get_scheduled_actions(
            array(
                'hook'     => 'crmfwc_export_single_product_event',
                'group'    => 'crmfwc-export-products',
                'status'   => ActionScheduler_Store::STATUS_PENDING,
                'per_page' => -1,
            ),
            'ids'
        );

        if ( 0 === count( $actions ) ) {

            delete_transient( 'crmfwc-total-products-actions' );

        }

        echo intval( count( $actions ) );

        exit;

    }


    /**
     * Get the total number of actions scheduled
     *
     * @return void
     */
    public function get_total_products_delete_actions() {

        $transient = get_transient( 'crmfwc-total-products-delete-actions' );

        echo intval( $transient );

        exit;

    }


    /**
     * Get the actions pending
     *
     * @return void
     */
    public function get_scheduled_products_delete_actions() {

        $actions = as_get_scheduled_actions(
            array(
                'hook'     => 'crmfwc_delete_remote_single_product_event',
                'group'    => 'crmfwc-delete-remote-products',
                'status'   => ActionScheduler_Store::STATUS_PENDING,
                'per_page' => -1,
            ),
            'ids'
        );

        if ( 0 === count( $actions ) ) {

            delete_transient( 'crmfwc-total-products-delete-actions' );

        }

        echo intval( count( $actions ) );

        exit;

    }


    /**
     * Get the total number of actions scheduled
     *
     * @return void
     */
    public function get_total_contacts_actions() {

        $transient = get_transient( 'crmfwc-total-contacts-actions' );

        echo intval( $transient );

        exit;

    }


    /**
     * Get the actions pending
     *
     * @return void
     */
    public function get_scheduled_contacts_actions() {

        $actions = as_get_scheduled_actions(
            array(
                'hook'     => 'crmfwc_export_single_user_event',
                'group'    => 'crmfwc-export-users',
                'status'   => ActionScheduler_Store::STATUS_PENDING,
                'per_page' => -1,
            ),
            'ids'
        );

        if ( 0 === count( $actions ) ) {

            delete_transient( 'crmfwc-total-contacts-actions' );

        }

        echo intval( count( $actions ) );

        exit;

    }


    /**
     * Get the total number of actions scheduled
     *
     * @return void
     */
    public function get_total_contacts_delete_actions() {

        $transient = get_transient( 'crmfwc-total-contacts-delete-actions' );

        echo intval( $transient );

        exit;

    }


    /**
     * Get the actions pending
     *
     * @return void
     */
    public function get_scheduled_contacts_delete_actions() {

        $actions = as_get_scheduled_actions(
            array(
                'hook'     => 'crmfwc_delete_remote_single_user_event',
                'group'    => 'crmfwc-delete-remote-users',
                'status'   => ActionScheduler_Store::STATUS_PENDING,
                'per_page' => -1,
            ),
            'ids'
        );

        if ( 0 === count( $actions ) ) {

            delete_transient( 'crmfwc-total-contacts-delete-actions' );

        }

        echo intval( count( $actions ) );

        exit;

    }


    /**
     * The progress bar as admin notice
     *
     * @return void */
    public function catalog_update_admin_notice() {

        $screen = get_current_screen();

        if ( 'woocommerce_page_crm-in-cloud-for-wc' === $screen->id ) {

            $output      = '<div class="update-nag notice notice-warning ilghera-notice-warning crmfwc-export is-dismissible">';
                $output     .= '<div class="ilghera-notice__content">';
                    $output      .= '<div class="ilghera-notice__message">';
                    $output      .= '<b>' . esc_html__( 'CRM in Cloud for WC', 'crm-in-cloud-for-wc' ) . '</b> - '; 
                    $output      .= '<span class="crmfwc-progress-bar-text"></span>'; 
                    $output      .= '</div>';
                    $output      .= '<div id="crmfwc-progress-bar">';
                        $output     .= '<div id="crmfwc-progress"></div>';
                        $output     .= '<span class="precentage">0%</span>';
                    $output     .= '</div>';
                $output     .= '</div>';
            $output     .= '</div>';

            echo wp_kses_post( $output );

        }

    }
}
new CRMFWC_Progress_Bar();

