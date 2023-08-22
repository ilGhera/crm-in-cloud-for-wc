<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 *
 * @since 1.1.0
 */
class CRMFWC_Settings {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		$this->email = get_option( 'crmfwc-email' );
		$this->passw = get_option( 'crmfwc-passw' );

		if ( $init ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_ajax_crmfwc-disconnect', array( $this, 'disconnect_callback' ) );
			add_action( 'wp_ajax_check-connection', array( $this, 'check_connection_callback' ) );

		}

		$this->crmfwc_call = new CRMFWC_Call();

	}


	/**
	 * Scripts and style sheets
	 *
	 * @return void
	 */
	public function enqueue() {

		wp_enqueue_script( 'chosen', CRMFWC_URI . '/vendor/harvesthq/chosen/chosen.jquery.min.js' );
		wp_enqueue_script( 'tzcheckbox', CRMFWC_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ) );

		wp_enqueue_style( 'chosen-style', CRMFWC_URI . '/vendor/harvesthq/chosen/chosen.min.css' );
		wp_enqueue_style( 'font-awesome', '//use.fontawesome.com/releases/v5.8.1/css/all.css' );
		wp_enqueue_style( 'tzcheckbox-style', CRMFWC_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css' );

	}


	/**
	 * Check if the current page is the plugin options page
	 *
	 * @return boolean
	 */
	public function is_crmfwc_admin() {

		$screen = get_current_screen();

		if ( isset( $screen->id ) && 'woocommerce_page_crm-in-cloud-for-wc' === $screen->id ) {

			return true;

		}

	}


	/**
	 * Get the current CRM in Cloud user info
     *
     * @return the call response
	 */
	public function user_information() {

		$response = $this->crmfwc_call->call( 'get', 'Auth/Me' );

		return $response;

	}


	/**
	 * Deletes the Agreement Grant Token from the db
	 *
	 * @return void
	 */
	public function disconnect_callback() {

		delete_option( 'crmfwc-email' );
		delete_option( 'crmfwc-passw' );
        delete_transient( 'crmfwc-access-token');

		exit;

	}


   	/**
	 * Display the status of the connection to CRM in Cloud
	 *
	 * @param bool   $return if true the method returns only if the connection is set.
     * @param string $email the email address.
     * @param string $passw the user password.
     *
	 * @return mixed
	 */
	public function check_connection_callback( $return = false, $email = null, $passw = null ) {

        $email = get_option( 'crmfwc-email' );
        $passw = get_option( 'crmfwc-passw' );

		if ( isset( $_POST['crmfwc-email'], $_POST['crmfwc-passw'], $_POST['crmfwc-login-nonce'] ) && wp_verify_nonce( $_POST['crmfwc-login-nonce'], 'crmfwc-login' ) ) {

            $email = sanitize_email( wp_unslash( $_POST['crmfwc-email'] ) );
            $passw = sanitize_text_field( wp_unslash( $_POST['crmfwc-passw'] ) );

            /* Update data in the db */
            update_option( 'crmfwc-email', $email );
            update_option( 'crmfwc-passw', $passw );

        }

        /* Access to CRM in Cloud */
        $connection = $this->crmfwc_call->get_access_token( $email, $passw ); 

		if ( isset( $connection->error ) || ! $connection ) {

			if ( $return || ! $connection ) {

				return false;

			} else {

                echo json_encode( $connection );

			}

		} else {

			if ( $return ) {

				return true;

			} else {

                $output = array(
                    'ok' => "<h4 class='wcefr-connection-status'><span class='label label-success'>" . __( 'Connected', 'wc-exporter-for-reviso' ) . "</span></h4>",
                );

                echo json_encode( $output );

			}

		}

		exit;
	}

}
new CRMFWC_Settings( true );

