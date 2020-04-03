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
	 */
	public function __construct() {

		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'eg_increase_time_limit' ) );
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'eg_increase_action_scheduler_batch_size' ) );
		add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
		add_action( 'crmfwc_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 1 );
		add_action( 'wp_ajax_export-users', array( $this, 'export_users' ) );
		add_action( 'crmfwc_export_single_user_event', array( $this, 'export_single_user' ), 10, 2 );

		/*Class call instance*/
		$this->crmfwc_call = new CRMFWC_Call();

	}


	/**
	 * Increase the time limit for porocessing the actions
	 *
	 * @param  int $time_limit the time limit in seconds.
	 * @return int the updated time
	 */
	public function eg_increase_time_limit( $time_limit ) {

		return 60;

	}


	/**
	 * Increase the number of actions executed in a single process
	 *
	 * @param  int $batch_size the number of actions.
	 * @return int the number updated
	 */
	public function eg_increase_action_scheduler_batch_size( $batch_size ) {

		return 100;

	}

	/**
	 * Sanitize every single array element
	 *
	 * @param  array $array the array to sanitize.
	 * @return array        the sanitized array.
	 */
	public function sanitize_array( $array ) {

		$output = array();

		if ( is_array( $array ) && ! empty( $array ) ) {

			foreach ( $array as $key => $value ) {

				$output[ $key ] = sanitize_text_field( wp_unslash( $value ) );

			}

		}

		return $output;

	}


	/**
	 * Get customers and suppliers from CRM in Cloud
	 *
	 * @param int $id the specific CRM in Cloud customer to get.
	 * @return array
	 */
	public function get_remote_users( $id = null ) {

		if ( ! $id ) {

			$output = array();
			$count  = $this->crmfwc_call->call( 'get', 'Contact/CountByODataCriteria?filter=id+ne+0' );
			$steps  = is_int( $count ) ? intval( $count / 100 ) + 1 : null;

			if ( $steps ) {

				for ( $i = 0; $i < $steps; $i++ ) {

					$skip     = $i * 100;
					$response = $this->crmfwc_call->call( 'get', 'Contact/SearchIdsByODataCriteria?filter=id+ne+0&top=100&skip=' . $skip );

					if ( is_array( $response ) ) {

						$output = array_merge( $output, $response );

						if ( 100 >= count( $response ) ) {

							continue;

						}

					}

				}

			}

		} else {

			$output = $this->crmfwc_call->call( 'get', 'Contact/Get/' . $id );

		}

		return $output;

	}


	/**
	 * Check if a contact exists in CRM in Cloud
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
	 * Get the Italian fiscal fields names based on the plugin installed
	 *
	 * @param  string $field the field to retrieve.
	 * @param  bool   $order_meta prepend an undescore if true.
	 * @return string the meta_key that will be used to get data from the db
	 */
	public function get_tax_field_name( $field, $order_meta = false ) {

		$cf_name      = null;
		$pi_name      = null;
		$pec_name     = null;
		$pa_code_name = null;

		if ( get_option( 'wcexd_company_invoice' ) || get_option( 'wcexd_private_invoice' ) ) {

			/*WC Exporter for Danea*/
			$cf_name      = 'billing_wcexd_cf';
			$pi_name      = 'billing_wcexd_piva';
			$pec_name     = 'billing_wcexd_pec';
			$pa_code_name = 'billing_wcexd_pa_code';

		} elseif ( get_option( 'wcefr_company_invoice' ) || get_option( 'wcefr_private_invoice' ) ) {

			/*WC Exporter for Danea*/
			$cf_name      = 'billing_wcefr_cf';
			$pi_name      = 'billing_wcefr_piva';
			$pec_name     = 'billing_wcefr_pec';
			$pa_code_name = 'billing_wcefr_pa_code';

		} else {

			/*Plugin supportati*/

			/*WooCommerce Aggiungere CF e P.IVA*/
			if ( class_exists( 'WC_BrazilianCheckoutFields' ) ) {
				$cf_name = 'billing_cpf';
				$pi_name = 'billing_cnpj';
			}

			/*WooCommerce P.IVA e Codice Fiscale per Italia*/
			elseif ( class_exists( 'WooCommerce_Piva_Cf_Invoice_Ita' ) || class_exists( 'WC_Piva_Cf_Invoice_Ita' ) ) {
				$cf_name      = 'billing_cf';
				$pi_name      = 'billing_piva';
				$pec_name     = 'billing_pec';
				$pa_code_name = 'billing_pa_code';
			}

			/*YITH WooCommerce Checkout Manager*/
			elseif ( function_exists( 'ywccp_init' ) ) {
				$cf_name = 'billing_Codice_Fiscale';
				$pi_name = 'billing_Partita_IVA';
			}

			/*WOO Codice Fiscale*/
			elseif ( function_exists( 'woocf_on_checkout' ) ) {
				$cf_name = 'billing_CF';
				$pi_name = 'billing_iva';
			}

		}

		switch ( $field ) {
			case 'cf_name':
				return $order_meta ? '_' . $cf_name : $cf_name;
				break;
			case 'pi_name':
				return $order_meta ? '_' . $pi_name : $pi_name;
				break;
			case 'pec_name':
				return $order_meta ? '_' . $pec_name : $pec_name;
				break;
			case 'pa_code_name':
				return $order_meta ? '_' . $pa_code_name : $pa_code_name;
				break;
		}

	}


	/**
	 * Prepare the single user data to export to CRM in Cloud
	 *
	 * @param  int    $user_id  the WP user id.
	 * @param  object $order the WC order to get the customer details.
	 * @return array
	 */
	public function prepare_user_data( $user_id = 0, $order = null ) {

		$website = null;

		if ( 0 !== $user_id ) {

			$user_details = get_userdata( $user_id );

			$user_data = array_map(
				function( $a ) {
					return $a[0];
				},
				get_user_meta( $user_id )
			);

			$surname = ( isset( $user_data['last_name'] ) && $user_data['last_name'] ) ? ucwords( $user_data['last_name'] ) : '-';
			$name    = null;

			/*Use WP display name with no first and last user name*/
			if ( isset( $user_data['first_name'] ) && $user_data['first_name'] ) {

				$name = ucwords( $user_data['first_name'] );

			} elseif ( '-' === $surname ) {

				$name = $user_details->display_name ? ucwords( $user_details->display_name ) : $user_details->user_login;

			}

			$user_email              = $user_details->user_email;
			$country                 = isset( $user_data['billing_country'] ) ? $user_data['billing_country'] : null;
			$city                    = isset( $user_data['billing_city'] ) ? ucwords( $user_data['billing_city'] ) : null;
			$state                   = isset( $user_data['billing_state'] ) ? ucwords( $user_data['billing_state'] ) : null;
			$address                 = isset( $user_data['billing_address_1'] ) ? ucwords( $user_data['billing_address_1'] ) : null;
			$postcode                = isset( $user_data['billing_postcode'] ) ? $user_data['billing_postcode'] : null;
			$phone                   = isset( $user_data['billing_phone'] ) ? $user_data['billing_phone'] : null;
			$company                 = isset( $user_data['billing_company'] ) ? ucwords( $user_data['billing_company'] ) : null;
			$website                 = $user_details->user_url;

			/*Fiscal data*/
			$pi_name = $this->get_tax_field_name( 'pi_name' );
			if ( $pi_name ) {
				$vat_number = isset( $user_data[ $pi_name ] ) ? $user_data[ $pi_name ] : '';
			}

			$cf_name = $this->get_tax_field_name( 'cf_name' );
			if ( $cf_name ) {
				$identification_number = isset( $user_data[ $cf_name ] ) ? strtoupper( $user_data[ $cf_name ] ) : '';
			}

			$pec_name = $this->get_tax_field_name( 'pec_name' );
			if ( $pec_name ) {
				$certified_email = isset( $user_data[ $pec_name ] ) ? $user_data[ $pec_name ] : '';
			}

			$pa_code_name = $this->get_tax_field_name( 'pa_code_name' );
			if ( $pa_code_name ) {
				$public_entry_number = isset( $user_data[ $pa_code_name ] ) ? strtoupper( $user_data[ $pa_code_name ] ) : '';
			}

		} elseif ( $order ) {

			$surname                 = $order->get_billing_last_name() ? ucwords( $order->get_billing_last_name() ) : '-';
			$name                    = $order->get_billing_first_name() ? ucwords( $order->get_billing_first_name() ) : null;
			$user_email              = $order->get_billing_email();
			$country                 = $order->get_billing_country();
			$city                    = $order->get_billing_city() ? ucwords( $order->get_billing_city() ) : null;
			$state                   = $order->get_billing_state();
			$address                 = $order->get_billing_address_1() ? ucwords( $order->get_billing_address_1() ) : null;
			$postcode                = $order->get_billing_postcode();
			$phone                   = $order->get_billing_phone();
			$company                 = $order->get_billing_company() ? ucwords( $order->get_billing_company() ) : null;

			/*Fiscal data*/
			$pi_name = $this->get_tax_field_name( 'pi_name', true );
			if ( $pi_name ) {
				$vat_number = isset( $user_data[ $pi_name ] ) ? $user_data[ $pi_name ] : '';
			}

			$cf_name = $this->get_tax_field_name( 'cf_name', true );
			if ( $cf_name ) {
				$identification_number = isset( $user_data[ $cf_name ] ) ? strtoupper( $user_data[ $cf_name ] ) : '';
			}

			$pec_name = $this->get_tax_field_name( 'pec_name', true );
			if ( $pec_name ) {
				$certified_email = isset( $user_data[ $pec_name ] ) ? $user_data[ $pec_name ] : '';
			}

			$pa_code_name = $this->get_tax_field_name( 'pa_code_name', true );
			if ( $pa_code_name ) {
				$public_entry_number = isset( $user_data[ $pa_code_name ] ) ? strtoupper( $user_data[ $pa_code_name ] ) : '';
			}

		} else {

			return;

		}

		$args = array(
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
			'taxIdentificationNumber' => $identification_number,
		);

		if ( $company ) {

			$args['companyName'] = $company;

		}

		/*Update contact if already in CRM in Cloud*/
		$crmfwc_id = null;

		/*WP user exists*/
		if ( 0 !== $user_id ) {

			$crmfwc_id = get_user_meta( $user_id, 'crmfwc-id', true );

		} else {

			$crmfwc_id = $this->search_remote_contact( $user_email );

		}

		if ( $crmfwc_id ) {

			$args['id'] = $crmfwc_id;

		}

		if ( $website ) {

			$args['webSite'] = $website;

		}

		if ( $certified_email ) {

			array_push( $args['emails'] , array( 'value' => $certified_email ) );
		
		}

		return $args;

	}


	/**
	 * Export single WP user to CRM in Cloud
	 *
	 * @param  int    $user_id the WP user id.
	 * @param  object $order the WC order to get the customer details.
	 * @return void
	 */
	public function export_single_user( $user_id = 0, $order = null ) {

		$args = $this->prepare_user_data( $user_id, $order );

		if ( $args ) {

			$response = $this->crmfwc_call->call( 'post', 'Contact/CreateOrUpdate', $args );

			if ( is_int( $response ) ) {

				/*Update user:meta only if wp user exists*/
				if ( 0 !== $user_id ) {

					update_user_meta( $user_id, 'crmfwc-id', $response );

				}

			}

		}

	}


	/**
	 * Export WP users as contacts to CRM in Cloud
	 *
	 * @return void
	 */
	public function export_users() {

		if ( isset( $_POST['crmfwc-export-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-export-users-nonce'] ), 'crmfwc-export-users' ) ) {

			/*Check options*/
			$roles    = isset( $_POST['roles'] ) ? $this->sanitize_array( $_POST['roles'] ) : array();
			$args     = array( 'role__in' => $roles );
			$users    = get_users( $args );
			$response = array();

			/*Save to the db*/
			update_option( 'crmfwc-users-roles', $roles );

			if ( $users ) {

				$n = 0;

				foreach ( $users as $user ) {

					$n++;

					/*Schedule action*/
					as_enqueue_async_action(
						'crmfwc_export_single_user_event',
						array(
							'user-id' => $user->ID,
						),
						'crmfwc-export-users'
					);

				}

				$response[] = array(
					'ok',
					/* translators: 1: users count */
					esc_html( sprintf( __( '%1$d contact(s) export process has begun', 'crm-in-cloud-for-wc' ), $n ) ),
				);

			} else {

				$response[] = array(
					'error',
					esc_html__( 'No contacts to export', 'crm-in-cloud-for-wc' ),
				);

			}

			echo json_encode( $response );

		}

		exit;

	}


	/**
	 * Search contact on CRM in Cloud by email
	 *
	 * @param  string $email the contact email.
	 * @return int the remote contact id
	 */
	public function search_remote_contact( $email ) {

		$response = $this->crmfwc_call->call( 'get', "Contact/Search?filter=startswith(emails, '$email')" );

		if ( isset( $response[0]->id ) ) {

			return $response[0]->id;

		}

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

		}

	}


	/**
	 * Delete a single customer/ supplier in CRM in Cloud
	 *
	 * @param  int $id the user id from CRM in Cloud.
	 */
	public function delete_remote_single_user( $id ) {

		$output = $this->crmfwc_call->call( 'delete', 'Contact/Delete/' . $id );

		if ( $output ) {

			/*Delete the remote id in the db*/
			$this->delete_remote_id( $id );

		}

	}


	/**
	 * Delete all contacts in CRM in Cloud
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['crmfwc-delete-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-delete-users-nonce'] ), 'crmfwc-delete-users' ) ) {

			$users = $this->get_remote_users();

			if ( is_array( $users ) && ! empty( $users ) ) {

				$n = 0;

				foreach ( $users as $user ) {

					$n++;

					/*Schedule action*/
					as_enqueue_async_action(
						'crmfwc_delete_remote_single_user_event',
						array(
							'contact-id' => $user,
						),
						'crmfwc-delete-remote-users'
					);

				}

				$response[] = array(
					'ok',
					/* translators: 1: users count */
					esc_html( sprintf( __( '%1$d users(s) delete process has begun', 'crm-in-cloud-for-wc' ), $n ) ),
				);

				echo json_encode( $response );

			} else {

				$response[] = array(
					'error',
					esc_html__( 'ERROR! There are not users to delete', 'crm-in-cloud-for-wc' ),
				);

				echo json_encode( $response );

			}

		}

		exit;

	}

}
new CRMFWC_Contacts( true );
