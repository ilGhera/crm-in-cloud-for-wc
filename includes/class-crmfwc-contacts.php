<?php
/**
 * Export users to CRM in Cloud
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 0.9.0
 */
class CRMFWC_Contacts {

	/**
	 * Class constructor
	 *
	 * @param boolean $init fire hooks if true.
	 */
	public function __construct( $init = false ) {

		if ( $init ) {

			add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
			add_action( 'crmfwc_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 2 );
			add_action( 'wp_ajax_export-users', array( $this, 'export_users' ) );
			add_action( 'crmfwc_export_single_user_event', array( $this, 'export_single_user' ), 10, 3 );

		}

		$this->crmfwc_call = new CRMFWC_Call();

	}


	/**
	 * Check if a company exists in CRM in Cloud
	 *
	 * @param  int $id the company id in CRM in Cloud.
	 * @return bool
	 */
	private function company_exists( $id = null ) {

		$output = false;

		if ( 0 < $id ) {

			$response = $this->crmfwc_call->call( 'get', 'Company/Exists/' . $id );

			if ( $response ) {

				$output = true;

			}

		}


		return $output;

	}


	/**
	 * Export a single comany to CRM in Cloud
	 *
	 * @param  int   $user_id the WP user id.
	 * @param  array $args    the company data.
	 * @return int   the CRM in Cloud company id
	 */
	private function export_single_company( $user_id, $args ) {

		$company_id = get_user_meta( $user_id, 'crmfwc-company-id', true );

		/*Update company if already in CRM in Cloud*/
		if ( $company_id ) {

			$args['id'] = $company_id;

		}

		$response = $this->crmfwc_call->call( 'post', 'Company/CreateOrUpdate', $args );

		if ( is_int( $response ) ) {

			update_user_meta( $user_id, 'crmfwc-company-id', $response );

			return $response;

		}

	}


	/**
	 * Get customers and suppliers from CRM in Cloud
	 *
	 * @param int $id the specific CRM in Cloud customer to get.
	 * @return array
	 */
	public function get_remote_users( $id = null ) {

		$output = $this->crmfwc_call->call( 'get', 'Contact/Get/' . $id );

		return $output;

	}


	/**
	 * Check if a customer/ supplier exists in CRM in Cloud
	 *
	 * @param  int $id the id user in CRM in Cloud.
	 * @return bool
	 */
	private function user_exists( $id = null ) {

		$output = false;

		if ( $id ) {

			$response = $this->crmfwc_call->call( 'get', 'Contact/Exists/' . $id );

			if ( $response ) {

				$output = true;

			}

		}

		return $output;

	}


	/**
	 * Prepare the single user data to export to Reviso
	 *
	 * @param  object $user  the WP user if exists.
	 * @param  object $order the WC order to get the customer details.
	 * @return array
	 */
	public function prepare_user_data( $user = null, $order = null ) {

		if ( $user ) {

			$user_details = get_userdata( $user->ID );

			$user_data = array_map(
				function( $a ) {
					return $a[0];
				},
				get_user_meta( $user->ID )
			);

			$name                    = $user_data['billing_first_name'];
			$surname                 = $user_data['billing_last_name'];
			$user_email              = $user_data['billing_email'];
			$country                 = $user_data['billing_country'];
			$city                    = $user_data['billing_city'];
			$state                   = $user_data['billing_state'];
			$address                 = $user_data['billing_address_1'];
			$postcode                = $user_data['billing_postcode'];
			$phone                   = $user_data['billing_phone'];
			$company                 = $user_data['billing_company'];
			$website                 = $user_details->user_url;
			$vat_number              = isset( $user_data['billing_wcexd_piva'] ) ? $user_data['billing_wcexd_piva'] : null;
			$identification_number   = isset( $user_data['billing_wcexd_cf'] ) ? $user_data['billing_wcexd_cf'] : null;
			$italian_certified_email = isset( $user_data['billing_wcexd_pec'] ) ? $user_data['billing_wcexd_pec'] : null;
			$public_entry_number     = isset( $user_data['billing_wcexd_pa_code'] ) ? $user_data['billing_wcexd_pa_code'] : null;

		} elseif ( $order ) {

			$name                    = $order->get_billing_first_name();
			$surname                 = $order->get_billing_last_name();
			$user_email              = $order->get_billing_email();
			$country                 = $order->get_billing_country();
			$city                    = $order->get_billing_city();
			$state                   = $order->get_billing_state();
			$address                 = $order->get_billing_address_1();
			$postcode                = $order->get_billing_postcode();
			$phone                   = $order->get_billing_phone();
			$company                 = $order->get_billing_company();
			$vat_number              = $order->get_meta( '_billing_wcexd_piva' ) ? $order->get_meta( '_billing_wcexd_piva' ) : null;
			$identification_number   = $order->get_meta( '_billing_wcexd_cf' ) ? $order->get_meta( '_billing_wcexd_cf' ) : null;
			$italian_certified_email = $order->get_meta( '_billing_wcexd_pec' ) ? $order->get_meta( '_billing_wcexd_pec' ) : null;
			$public_entry_number     = $order->get_meta( '_billing_wcexd_pa_code' ) ? $order->get_meta( '_billing_wcexd_pa_code' ) : null;

		} else {

			return;

		}

		$args = array(
			'companyName' => $company,
			'name'        => $name,
			'surname'     => $surname,
			'emails'      => array(
				array(
					'value' => $user_email,
				),
			),
			'state'       => $country,
			'city'        => $city,
			'address'     => $address,
			'zipCode'     => $postcode,
			'phones'      => array(
				array(
					'value' => $phone,
				),
			),
			'province'    => $state,
			'vatId'       => $vat_number,
			'webSite'     => $website,
		);

		if ( $company ) {

			$company_id        = $this->export_single_company( $user->ID, $args );
			$args['companyId'] = $company_id;

		}

		/*Update contact if already in CRM in Cloud*/
		$crmfwc_id = get_user_meta( $user->ID, 'crmfwc-id', true );

		if ( $crmfwc_id ) {

			$args['id'] = $crmfwc_id;

		}

		// if ( $italian_certified_email ) {
		// 	$args['italianCertifiedEmail'] = $italian_certified_email;
		// }

		// if ( $public_entry_number ) {
		// 	$args['publicEntryNumber'] = $public_entry_number;
		// }

		return $args;

	}


	/**
	 * Export single WP user to Reviso
	 *
	 * @param  int    $n the count number.
	 * @param  object $user the WP user.
	 * @param  object $order the WC order to get the customer details.
	 * @return void
	 */
	public function export_single_user( $n, $user = null, $order = null ) {

		$args      = $this->prepare_user_data( $user, $order );
		$crmfwc_id = get_user_meta( $user->ID, 'crmfwc-id', true );

		if ( $args ) {

			$response = $this->crmfwc_call->call( 'post', 'Contact/CreateOrUpdate', $args );

			if ( is_int( $response ) ) {

				update_user_meta( $user->ID, 'crmfwc-id', $response );

			}

		}

	}


	/**
	 * Export WP users as customers/ suppliers in Reviso
	 *
	 * @return void
	 */
	public function export_users() {

		if ( isset( $_POST['crmfwc-export-users-nonce'] ) && wp_verify_nonce( $_POST['crmfwc-export-users-nonce'], 'crmfwc-export-users' ) ) {

			$role  = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';

			/*Save to the db*/
			update_option( 'crmfwc-users-role', $role );

			$args     = array( 'role' => $role );
			$users    = get_users( $args );
			$response = array();

			if ( $users ) {

				$n = 0;

				foreach ( $users as $user ) {

					$n++;

					/*Cron event*/
					wp_schedule_single_event(
						time() + 1,
						'crmfwc_export_single_user_event',
						array(
							$n,
							$user,
						)
					);

				}

				$response[] = array(
					'ok',
					/* translators: 1: users count */
					esc_html( sprintf( __( '%1$d contact(s) export process has begun', 'crmfwc' ), $n ) ),
				);

			} else {

				$response[] = array(
					'error',
					esc_html__( 'No contacts to export', 'crmfwc' ),
				);

			}

			echo json_encode( $response );

		}

		exit;

	}


	/**
	 * Delete CRM in cloud contact id and company id from the db
	 *
	 * @param  int $id the CRM in Cloud contact id.
	 * @return void
	 */
	public function delete_remote_id( $id ) {

		$users = get_users(
			array(

				'meta_key' => 'crmfwc-id',
				'meta_value' => $id,

			)
		);

		if ( isset( $users[0]->ID ) ) {

			/*Contact id*/
			delete_user_meta( $users[0]->ID, 'crmfwc-id' );

			/*Company id*/
			$company_id = get_user_meta( $users[0]->ID, 'crmfwc-company-id', true );

			if ( $company_id ) {

				/*Delete from CRM in Cloud*/
				$this->crmfwc_call->call( 'delete', 'Company/Delete/' . $company_id );
				delete_user_meta( $users[0]->ID, 'crmfwc-company-id' );

			}

		}

	}


	/**
	 * Delete a single customer/ supplier in CRM in Cloud
	 *
	 * @param  int $n  the count number.
	 * @param  int $id the user id from CRM in Cloud.
	 */
	public function delete_remote_single_user( $n, $id ) {

		$output = $this->crmfwc_call->call( 'delete', 'Contact/Delete/' . $id );

		/*temp*/
		if ( isset( $output->errorCode ) || isset( $output->developerHint ) ) { // temp.

			$response = array(
				'error',
				/* translators: error message */
				esc_html( sprintf( __( 'ERROR! %s<br>', 'crmfwc' ), $output->message ) ),
			);

		} else {

			/*Delete id from the db*/
			$this->delete_remote_id( $id );

			$response = array(
				'ok',
				/* translators: 1: users count 2: user type */
				esc_html( sprintf( __( 'Deleted user: $d', 'crmfwc' ), $n ) ),
			);

		}

	}


	/**
	 * Delete all customers/ suppliers in Reviso
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['crmfwc-delete-users-nonce'] ) && wp_verify_nonce( $_POST['crmfwc-delete-users-nonce'], 'crmfwc-delete-users' ) ) {

			$users = $this->get_remote_users();
			error_log( 'GET: ' . print_r( $users, true ) );

			if ( is_array( $users ) && ! empty( $users ) ) {

				$n = 0;

				foreach ( $users as $user ) {

					$n++;

					/*Cron event*/
					wp_schedule_single_event(
						time() + 1,
						'crmfwc_delete_remote_single_user_event',
						array(
							$n,
							$user,
						)
					);

				}

				$response[] = array(
					'ok',
					/* translators: 1: users count */
					esc_html( sprintf( __( '%1$d users(s) delete process has begun', 'crmfwc' ), $n ) ),
				);

				echo json_encode( $response );

			} else {

				$response[] = array(
					'error',
					esc_html__( 'ERROR! There are not users to delete', 'crmfwc' ),
				);

				echo json_encode( $response );

			}

		}

		exit;

	}
}
new CRMFWC_Contacts( true );
