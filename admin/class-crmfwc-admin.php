<?php
/**
 * Admin class
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
 */
class CRMFWC_Admin {

	/**
	 * Construct
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'crmfwc_add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'crmfwc_register_scripts' ) );

	}


	/**
	 * Scripts and style sheets
	 *
	 * @return void
	 */
	public function crmfwc_register_scripts() {

		$screen = get_current_screen();
		if ( 'woocommerce_page_crm-in-cloud-for-wc' === $screen->id ) {

			/*js*/
			wp_enqueue_script( 'crmfwc-js', CRMFWC_URI . 'js/crmfwc.js', array( 'jquery' ), '1.0', true );

		}

		wp_enqueue_style( 'crmfwc-style', CRMFWC_URI . 'css/crm-in-cloud-for-wc.css' );

		/*Nonces*/
		$export_users_nonce = wp_create_nonce( 'crmfwc-export-users' );
		$delete_users_nonce = wp_create_nonce( 'crmfwc-delete-users' );

		wp_localize_script(
			'crmfwc-js',
			'crmfwcSettings',
			array(
				'exportNonce'     => $export_users_nonce,
				'deleteNonce'     => $delete_users_nonce,
				'responseLoading' => CRMFWC_URI . 'images/loader.gif',
			)
		);

	}


	/**
	 * Menu page
	 *
	 * @return string
	 */
	public function crmfwc_add_menu() {

		$crmfwc_page = add_submenu_page( 'woocommerce', 'CRMFWC Options', 'CRM in Cloud', 'manage_woocommerce', 'crm-in-cloud-for-wc', array( $this, 'crmfwc_options' ) );

		return $crmfwc_page;

	}


	/**
	 * Options page
	 *
	 * @return mixed
	 */
	public function crmfwc_options() {

		/*Right of access*/
		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html( __( 'It seems like you don\'t have permission to see this page', 'crm-in-cloud-for-wc' ) ) );

		}

		/*Page template start*/
		echo '<div class="wrap">';
			echo '<div class="wrap-left">';

				/*Check if WooCommerce is installed ancd activated*/
				if ( ! class_exists( 'WooCommerce' ) ) {
					echo '<div id="message" class="error">';
						echo '<p>';
							echo '<strong>' . esc_html( __( 'ATTENTION! It seems like Woocommerce is not installed.', 'crm-in-cloud-for-wc' ) ) . '</strong>';
						echo '</p>';
					echo '</div>';
					exit;
				}

				echo '<div id="crmfwc-generale">';

					/*Header*/
					echo '<h1 class="crmfwc main">' . esc_html( __( 'CRM in Cloud for WooCommerce', 'crm-in-cloud-for-wc' ) ) . '</h1>';

					/*Plugin options menu*/
					echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>';
					echo '<h2 id="crmfwc-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
						echo '<a href="#" data-link="crmfwc-settings" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html( __( 'Settings', 'crm-in-cloud-for-wc' ) ) . '</a>';
						echo '<a href="#" data-link="crmfwc-users" class="nav-tab" onclick="return false;">' . esc_html( __( 'Contacts', 'crm-in-cloud-for-wc' ) ) . '</a>';
						echo '<a href="#" data-link="crmfwc-wc" class="nav-tab" onclick="return false;">' . esc_html( __( 'WooCommerce', 'crm-in-cloud-for-wc' ) ) . '</a>';
					echo '</h2>';

					/*Settings*/
					echo '<div id="crmfwc-settings" class="crmfwc-admin" style="display: block;">';

						include( CRMFWC_ADMIN . 'crmfwc-settings-template.php' );

					echo '</div>';

					/*Users*/
					echo '<div id="crmfwc-users" class="crmfwc-admin">';

						include( CRMFWC_ADMIN . 'crmfwc-contacts-template.php' );

					echo '</div>';

					/*WooCommerce*/
					echo '<div id="crmfwc-wc" class="crmfwc-admin">';

						include( CRMFWC_ADMIN . 'crmfwc-wc-template.php' );

					echo '</div>';

				echo '</div>';

				/*Admin message*/
				echo '<div class="crmfwc-message">';
					echo '<div class="yes"></div>';
					echo '<div class="not"></div>';
				echo '</div>';

			echo '</div>';

			echo '<div class="wrap-right">';

				echo '<iframe width="300" height="1300" scrolling="no" src="https://www.ilghera.com/images/crmfwc-iframe.html"></iframe>';

			echo '</div>';

			echo '<div class="clear"></div>';

		echo '</div>';

	}

}
new CRMFWC_Admin();
