<?php
/**
 * Export users to CRM in Cloud
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 *
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRMFWC_Contacts class
 *
 * @since 1.2.0
 */
class CRMFWC_Contacts {

	/**
	 * Instance of the class CRMFWC_Call
	 *
	 * @var object
	 */
	public $crmfwc_call;

	/**
	 * Export the user orders as opportunities in CRM in Cloud
	 *
	 * @var int
	 */
	private $export_orders;

	/**
	 * Export the company linked to the user if present
	 *
	 * @var int
	 */
	private $export_company;

	/**
	 * Delete remote company linked to the contacts
	 *
	 * @var int
	 */
	private $delete_company;

	/**
	 * Export the new orders as opportunities in CRM in Cloud
	 *
	 * @var int
	 */
	private $wc_export_orders;

	/**
	 * Create an opportunity for every single order item
	 *
	 * @var bool
	 */
	private $split_opportunities;

	/**
	 * Export opportunities for the company as well if it exists
	 *
	 * @var bool
	 */
	private $company_opportunities;

	/**
	 * Synchronize contacts in real time
	 *
	 * @var bool
	 */
	private $synchronize_contacts;

	/**
	 * Synchronize company in real time
	 *
	 * @var bool
	 */
	private $synchronize_companies;

	/**
	 * The product class instance
	 *
	 * @var object
	 */
	private $products;

	/**
	 * The completed phase
	 *
	 * @var string
	 */
	private $completed_phase;

	/**
	 * The lost phase
	 *
	 * @var string
	 */
	private $lost_phase;

	/**
	 * The pending payment phase
	 *
	 * @var string
	 */
	private $pending_payment_phase;

	/**
	 * Class constructor
	 */
	public function __construct() {

		/*Classes instance*/
		$this->crmfwc_call = new CRMFWC_Call();
		$this->products    = new CRMFWC_Products();

		/*Get the complete phase to use with orders as opportunities*/
		$this->completed_phase       = $this->get_completed_opportunity_phase();
		$this->lost_phase            = $this->get_lost_opportunity_phase();
		$this->pending_payment_phase = $this->get_pending_payment_opportunity_phase();

		/*Get options*/
		$this->export_orders         = get_option( 'crmfwc-export-orders' );
		$this->export_company        = get_option( 'crmfwc-export-company' );
		$this->delete_company        = get_option( 'crmfwc-delete-company' );
		$this->wc_export_orders      = get_option( 'crmfwc-wc-export-orders' );
		$this->split_opportunities   = get_option( 'crmfwc-wc-split-opportunities' );
		$this->company_opportunities = get_option( 'crmfwc-wc-company-opportunities' );
		$this->synchronize_contacts  = get_option( 'crmfwc-synchronize-contacts' );
		$this->synchronize_companies = get_option( 'crmfwc-synchronize-companies' );

		/* Hooks */
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'eg_increase_time_limit' ) );
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'eg_increase_action_scheduler_batch_size' ) );
		add_action( 'admin_init', array( $this, 'contacts_settings' ), 10 );
		add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
		add_action( 'crmfwc_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 1 );
		add_action( 'wp_ajax_export-users', array( $this, 'export_users' ) );
		add_action( 'crmfwc_export_single_user_event', array( $this, 'export_single_user' ), 10, 2 );
		add_action( 'woocommerce_checkout_process', array( $this, 'deactivate_profile_update' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'wc_order_callback' ), 10, 1 );
		add_action( 'woocommerce_update_order', array( $this, 'wc_order_update_callback' ), 10, 2 );
		add_action( 'woocommerce_delete_order', array( $this, 'wc_order_update_callback' ), 10, 2 );
		add_action( 'woocommerce_trash_order', array( $this, 'wc_order_update_callback' ), 10, 2 );
		add_action( 'woocommerce_untrash_order', array( $this, 'wc_order_update_callback' ), 10, 2 );

		/* Conditional hooks */
		if ( $this->synchronize_contacts ) {

			add_action( 'user_register', array( $this, 'update_remote_contact' ), 10 );
			add_action( 'profile_update', array( $this, 'update_remote_contact' ), 10 );
			add_action( 'delete_user', array( $this, 'delete_remote_contact' ), 10, 3 );

		}

	}


	/**
	 * Increase the time limit for porocessing the actions
	 *
	 * @param  int $time_limit the time limit in seconds.
	 *
	 * @return int the updated time
	 */
	public function eg_increase_time_limit( $time_limit ) {

		return 60;

	}


	/**
	 * Increase the number of actions executed in a single process
	 *
	 * @param  int $batch_size the number of actions.
	 *
	 * @return int the number updated
	 */
	public function eg_increase_action_scheduler_batch_size( $batch_size ) {

		return 100;

	}

	/**
	 * Sanitize every single array element
	 *
	 * @param  array $array the array to sanitize.
	 *
	 * @return array        the sanitized array.
	 */
	public static function sanitize_array( $array ) {

		$output = array();

		if ( is_array( $array ) && ! empty( $array ) ) {

			foreach ( $array as $key => $value ) {

				$output[ $key ] = sanitize_text_field( wp_unslash( $value ) );

			}
		}

		return $output;

	}

	/**
	 * Check if a company exists in CRM in Cloud
	 *
	 * @param  int $id the company id in CRM in Cloud.
	 *
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
	 * @param  int    $user_id the WP user id.
	 * @param  array  $args    the  company data.
	 * @param  string $company_name the company name.
	 *
	 * @return int    the CRM in Cloud company id
	 */
	private function export_single_company( $user_id, $args, $company_name = null ) {

		if ( 0 !== $user_id ) {

			$company_id = get_user_meta( $user_id, 'crmfwc-company-id', true );

		} else {

			$company_id = $this->search_remote_company( $company_name );

		}

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
	 *
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
	 * Add the pending payment opportunity phase to CRM in Cloud
	 *
	 * @return string the new phase description
	 */
	private function add_pending_payment_opportunity_phase() {

		$args = array(
			'description' => __( 'Pending payment', 'crm-in-cloud-for-wc' ),
			'status'      => 1,
			'weight'      => 80,
		);

		$phase = $this->crmfwc_call->call( 'post', 'OpportunityPhase/CreateOrUpdate', $args );

		if ( is_int( $phase ) && 1 < $phase ) {

			update_option( 'crmfwc-pending-payment-phase', $args['description'] );

			return $args['description'];

		}

	}


	/**
	 * Get the pending payment phase from CRM in Cloud
	 *
	 * @return int the phase description
	 */
	private function get_pending_payment_opportunity_phase() {

		$phase_description = get_option( 'crmfwc-pending-payment-phase' );

		if ( $phase_description ) {

			return $phase_description;

		} else {

			$phases = $this->crmfwc_call->call( 'get', 'OpportunityPhase/Get' );

			if ( $phases ) {

				$done = false;

				foreach ( $phases as $key => $value ) {

					$phase = $this->crmfwc_call->call( 'get', 'OpportunityPhase/View/' . $value );

					if ( isset( $phase ) ) {

						if ( 80 === $phase->weight && __( 'Pending payment', 'crm-in-cloud-for-wc' ) === $phase->description ) {

							$done = true;

							update_option( 'crmfwc-pending-payment-phase', $phase->description );

							return $phase->description;

						}
					}
				}

				if ( ! $done ) {

					$this->add_pending_payment_opportunity_phase();

				}
			}
		}

	}


	/**
	 * Get the completed phase from CRM in Cloud
	 *
	 * @return int the phase description
	 */
	private function get_completed_opportunity_phase() {

		$phase_description = get_option( 'crmfwc-completed-phase' );

		if ( $phase_description ) {

			return $phase_description;

		} else {

			$phases = $this->crmfwc_call->call( 'get', 'OpportunityPhase/Get' );

			if ( $phases ) {

				foreach ( $phases as $key => $value ) {

					$phase = $this->crmfwc_call->call( 'get', 'OpportunityPhase/View/' . $value );

					if ( isset( $phase ) ) {

						if ( 3 === $phase->status && 100 === $phase->weight ) {

							update_option( 'crmfwc-completed-phase', $phase->description );

							return $phase->description;

						}
					}
				}
			}
		}

	}


	/**
	 * Get the lost phase from CRM in Cloud
	 *
	 * @return int the phase description
	 */
	private function get_lost_opportunity_phase() {

		$phase_description = get_option( 'crmfwc-lost-phase' );

		if ( $phase_description ) {

			return $phase_description;

		} else {

			$phases = $this->crmfwc_call->call( 'get', 'OpportunityPhase/Get' );

			if ( $phases ) {

				foreach ( $phases as $key => $value ) {

					$phase = $this->crmfwc_call->call( 'get', 'OpportunityPhase/View/' . $value );

					if ( isset( $phase ) ) {

						if ( 4 === $phase->status && 0 === $phase->weight ) {

							update_option( 'crmfwc-lost-phase', $phase->description );

							return $phase->description;

						}
					}
				}
			}
		}

	}
	/**
	 * Setup all the opportunities of a single order
	 *
	 * @param  object $order      the WC order.
	 * @param  int    $remote_id  the CRM in Cloud user id.
	 * @param  bool   $cross_type export opportunities to company (0) or contact (1).
	 *
	 * @return array the opportunities data
	 */
	public function get_single_order_opportunities( $order, $remote_id = null, $cross_type = 0 ) {

		$output            = array();
		$phase_information = array();
		$title             = __( 'Order: ', 'crm-in-cloud-for-wc' ) . ' #' . $order->get_id();
		$create_date       = $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d G:i:s' ) : '';

		/* Opportunity args */
		$args = array(
			'closeDate'   => $order->get_date_completed() ? $order->get_date_completed()->format( 'Y-m-d G:i:s' ) : $create_date,
			'createdDate' => $create_date,
			'crossId'     => $remote_id,
			'crossType'   => $cross_type,
			'title'       => $title,
		);

		/* Phase information */
		switch ( $order->get_status() ) {
			case 'failed':
			case 'cancelled':
			case 'refunded':
				$phase_information = array(
					'phase'       => $this->lost_phase,
					'probability' => 0,
					'status'      => 4,
				);
				break;
			case 'completed':
				$phase_information = array(
					'phase'       => $this->completed_phase,
					'probability' => 100,
					'status'      => 3,
				);
				break;
			default:
				$phase_information = array(
					'phase'       => $this->pending_payment_phase,
					'probability' => 80,
					'status'      => 1,
				);
		}

		if ( $this->split_opportunities ) {

			foreach ( $order->get_items() as $item_id => $item ) {

				$quantity          = 1 < $item->get_quantity() ? ' (' . $item->get_quantity() . ')' : '';
				$remote_product_id = $this->products->get_remote_product_id( $item->get_product_id(), true );

				/* Add specific information about the order item */
				$more = array(
					'amount'      => wc_format_decimal( $order->get_item_total( $item, false, false ), 2 ),
					'budget'      => wc_format_decimal( $order->get_item_total( $item, false, false ), 2 ) * $item->get_quantity(),
					'description' => $item['name'] . $quantity,
					'products'    => array(
						$this->products->prepare_opportunity_product_data( $remote_product_id, $item->get_quantity() ),
					),
				);

				/* Complete the opportunity data */
				$data = array_merge( $args, $more, $phase_information );

				$output[ $item_id ] = $data;

			}
		} else {

			$description = null;
			$products    = array();

			/* Get the items products names */
			foreach ( $order->get_items() as $item_id => $item ) {

				$remote_product_id = $this->products->get_remote_product_id( $item->get_product_id(), true );
				$products[]        = $this->products->prepare_opportunity_product_data( $remote_product_id, $item->get_quantity() );
				$separator         = $description ? ' | ' : null;
				$quantity          = 1 < $item->get_quantity() ? ' (' . $item->get_quantity() . ')' : '';
				$description      .= isset( $item['name'] ) ? $separator . $item['name'] . $quantity : $title;

			}

			$more = array(
				'amount'      => wc_format_decimal( $order->get_total(), 2 ),
				'budget'      => wc_format_decimal( $order->get_total(), 2 ),
				'description' => $description,
				'products'    => $products,
			);

			/* Complete the opportunity data */
			$data = array_merge( $args, $more, $phase_information );

			$output[] = $data;

		}

		return $output;

	}


	/**
	 * Prepare data to set opportunities in CRM in Cloud from the user WC orders
	 *
	 * @param  int  $user_id    the WP user id.
	 * @param  int  $remote_id  the CRM in Cloud user id.
	 * @param  bool $cross_type export opportunities to company (0) or contact (1).
	 * @param  int  $order_id   the WC order ID.
	 *
	 * @return array the opportunities data
	 */
	private function get_user_opportunities( $user_id, $remote_id, $cross_type = 0, $order_id = null ) {

		$orders = array();
		$output = array();

		/* Get only the specific order */
		if ( $order_id ) {

			$orders[] = wc_get_order( $order_id );

		} else {

			$data = array(
				'customer_id' => $user_id,
				'limit'       => -1,
			);

			$orders = wc_get_orders( $data );

		}

		if ( $orders ) {

			foreach ( $orders as $order ) {

				$args                       = $this->get_single_order_opportunities( $order, $remote_id, $cross_type );
				$output[ $order->get_id() ] = $args;

			}
		}

		return $output;

	}


	/**
	 * Export orders data to CRM in Cloud as opportunities
	 *
	 * @param int  $user_id   the WP user id.
	 * @param int  $remote_id the CRM in Cloud user id.
	 * @param bool $cross_type export opportunities to company (0) or contact (1).
	 * @param int  $order_id the order ID.
	 *
	 * @return void
	 */
	private function export_opportunities( $user_id, $remote_id, $cross_type = 0, $order_id = null ) {

		$endpoint = 'Opportunity/CreateOrUpdate/';
		$data     = $this->get_user_opportunities( $user_id, $remote_id, $cross_type, $order_id );

		if ( is_array( $data ) ) {

			/* Remove the action on order update */
			remove_action( 'woocommerce_update_order', array( $this, 'wc_order_update_callback' ), 10, 2 );

			foreach ( $data as $key => $value ) {

				$meta_key            = 1 === $cross_type ? 'crmfwc-contact-opportunities' : 'crmfwc-company-opportunities';
				$order               = wc_get_order( $key );
				$order_data          = $order->get_data();
				$saved_opportunities = $order->get_meta( $meta_key, true );

				if ( ! $saved_opportunities || $order_id === $key ) {

					if ( is_array( $value ) && ! empty( $value ) ) {

						$test2 = $order->update_meta_data( $meta_key, 1 );
						$order->save();

						foreach ( $value as $k => $val ) {

							if ( $order_id === $key ) {

								$opportunity_id = $order->get_meta( 'crmfwc-opportunity-' . $cross_type . '-' . $k, true );

								if ( $opportunity_id ) {

									if ( 'trash' === $order->get_status() ) {

										$response = $this->crmfwc_call->call( 'delete', 'Opportunity/' . $opportunity_id );

										$order->delete_meta_data( 'crmfwc-opportunity-' . $cross_type . '-' . $k );
										$order->save();

										continue;

									}

									/* Update an existing opportunity */
									$val['id'] = $opportunity_id;

								}
							}

							$response = $this->crmfwc_call->call( 'post', $endpoint, $val );

							if ( is_int( $response ) ) {

								$test = $order->update_meta_data( 'crmfwc-opportunity-' . $cross_type . '-' . $k, $response );
								$order->save();

							}
						}
					}
				}
			}

			/* Restart the action */
			add_action( 'woocommerce_update_order', array( $this, 'wc_order_update_callback' ), 10, 2 );

		}

	}


	/**
	 * Check if a contact exists in CRM in Cloud
	 *
	 * @param  int $id the id user in CRM in Cloud.
	 *
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
	 *
	 * @return string the meta_key that will be used to get data from the db
	 */
	public function get_tax_field_name( $field, $order_meta = false ) {

		$cf_name      = null;
		$pi_name      = null;
		$pec_name     = null;
		$pa_code_name = null;

		/*Generated by the plugin*/
		if ( get_option( 'crmfwc_company_invoice' ) || get_option( 'crmfwc_private_invoice' ) ) {

			$cf_name      = 'billing_crmfwc_cf';
			$pi_name      = 'billing_crmfwc_piva';
			$pec_name     = 'billing_crmfwc_pec';
			$pa_code_name = 'billing_crmfwc_pa_code';

		} elseif ( function_exists( 'wcexd_options' ) && ( get_option( 'wcexd_company_invoice' ) || get_option( 'wcexd_private_invoice' ) ) ) {

			/*WC Exporter for Danea*/
			$cf_name      = 'billing_wcexd_cf';
			$pi_name      = 'billing_wcexd_piva';
			$pec_name     = 'billing_wcexd_pec';
			$pa_code_name = 'billing_wcexd_pa_code';

		} elseif ( class_exists( 'WCEFR_Admin' ) && ( get_option( 'wcefr_company_invoice' ) || get_option( 'wcefr_private_invoice' ) ) ) {

			/*WC Exporter for Danea*/
			$cf_name      = 'billing_wcefr_cf';
			$pi_name      = 'billing_wcefr_piva';
			$pec_name     = 'billing_wcefr_pec';
			$pa_code_name = 'billing_wcefr_pa_code';

		} else {

			/*Plugin supportati*/
			if ( class_exists( 'WC_BrazilianCheckoutFields' ) ) {
				/*WooCommerce Aggiungere CF e P.IVA*/
				$cf_name = 'billing_cpf';
				$pi_name = 'billing_cnpj';
			} elseif ( class_exists( 'WooCommerce_Piva_Cf_Invoice_Ita' ) || class_exists( 'WC_Piva_Cf_Invoice_Ita' ) ) {
				/*WooCommerce P.IVA e Codice Fiscale per Italia*/
				$cf_name      = 'billing_cf';
				$pi_name      = 'billing_piva';
				$pec_name     = 'billing_pec';
				$pa_code_name = 'billing_pa_code';
			} elseif ( function_exists( 'ywccp_init' ) ) {
				/*YITH WooCommerce Checkout Manager*/
				$cf_name = 'billing_Codice_Fiscale';
				$pi_name = 'billing_Partita_IVA';
			} elseif ( function_exists( 'woocf_on_checkout' ) ) {
				/*WOO Codice Fiscale*/
				$cf_name = 'billing_CF';
				$pi_name = 'billing_iva';
			}
		}

		switch ( $field ) {
			case 'cf_name':
				return $order_meta ? '_' . $cf_name : $cf_name;
			case 'pi_name':
				return $order_meta ? '_' . $pi_name : $pi_name;
			case 'pec_name':
				return $order_meta ? '_' . $pec_name : $pec_name;
			case 'pa_code_name':
				return $order_meta ? '_' . $pa_code_name : $pa_code_name;
		}

	}


	/**
	 * User synchronization options
	 *
	 * @return void
	 */
	public function contacts_settings() {

		if ( isset( $_POST['crmfwc-contacts-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['crmfwc-contacts-settings-nonce'] ) ), 'crmfwc-contacts-settings' ) ) {

			$synchronize_contacts = isset( $_POST['crmfwc-synchronize-contacts'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-synchronize-contacts'] ) ) : 0;

			/*Save to the db*/
			update_option( 'crmfwc-synchronize-contacts', $synchronize_contacts );

		}

	}


	/**
	 * Update remote contact in real time
	 *
	 * @param int $user_id the WP user id.
	 *
	 * @return void
	 */
	public function update_remote_contact( $user_id ) {

		$response = $this->export_single_user( $user_id, null, true );

	}


	/**
	 * Delete remote contact in real time
	 *
	 * @param int $user_id  the WP user id.
	 * @param int $reassign ID of the user to reassign posts and links to.
	 * @param int $user     WP_User object of the user to delete.
	 *
	 * @return void
	 */
	public function delete_remote_contact( $user_id, $reassign, $user ) {

		$crmfwc_id = get_user_meta( $user_id, 'crmfwc-id', true );
		$response  = $this->delete_remote_single_user( $crmfwc_id, null, true );

	}


	/**
	 * Prepare the single user data to export to CRM in Cloud
	 *
	 * @param  int    $user_id  the WP user id.
	 * @param  object $order the WC order to get the customer details.
	 * @param  bool   $update user update with true.
	 *
	 * @return array
	 */
	public function prepare_user_data( $user_id = 0, $order = null, $update = false ) {

		$website               = null;
		$vat_number            = null;
		$identification_number = null;
		$certified_email       = null;
		$public_entry_number   = null;

		if ( $order ) {

			$surname    = $order->get_billing_last_name() ? ucwords( $order->get_billing_last_name() ) : '-';
			$name       = $order->get_billing_first_name() ? ucwords( $order->get_billing_first_name() ) : null;
			$user_email = $order->get_billing_email();
			$country    = $order->get_billing_country();
			$city       = $order->get_billing_city() ? ucwords( $order->get_billing_city() ) : null;
			$state      = $order->get_billing_state();
			$address    = $order->get_billing_address_1() ? ucwords( $order->get_billing_address_1() ) : null;
			$postcode   = $order->get_billing_postcode();
			$phone      = $order->get_billing_phone();
			$company    = $order->get_billing_company() ? ucwords( $order->get_billing_company() ) : null;

			/*Fiscal data*/
			$pi_name = $this->get_tax_field_name( 'pi_name', true );
			if ( $pi_name ) {
				$vat_number = $order->get_meta( $pi_name );
			}

			$cf_name = $this->get_tax_field_name( 'cf_name', true );
			if ( $cf_name ) {
				$identification_number = $order->get_meta( $cf_name );
			}

			$pec_name = $this->get_tax_field_name( 'pec_name', true );
			if ( $pec_name ) {
				$certified_email = $order->get_meta( $pec_name );
			}

			$pa_code_name = $this->get_tax_field_name( 'pa_code_name', true );
			if ( $pa_code_name ) {
				$public_entry_number = $order->get_meta( $pa_code_name );
			}
		} elseif ( 0 !== $user_id ) {

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

			$user_email = $user_details->user_email;
			$country    = isset( $user_data['billing_country'] ) ? $user_data['billing_country'] : null;
			$city       = isset( $user_data['billing_city'] ) ? ucwords( $user_data['billing_city'] ) : null;
			$state      = isset( $user_data['billing_state'] ) ? ucwords( $user_data['billing_state'] ) : null;
			$address    = isset( $user_data['billing_address_1'] ) ? ucwords( $user_data['billing_address_1'] ) : null;
			$postcode   = isset( $user_data['billing_postcode'] ) ? $user_data['billing_postcode'] : null;
			$phone      = isset( $user_data['billing_phone'] ) ? $user_data['billing_phone'] : null;
			$company    = isset( $user_data['billing_company'] ) ? ucwords( $user_data['billing_company'] ) : null;
			$website    = $user_details->user_url;

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
		} else {

			return;

		}

		$args = array(
			'name'                    => $name,
			'surname'                 => $surname,
			'emails'                  => array(
				array(
					'value' => $user_email,
				),
			),
			'state'                   => $country,
			'city'                    => $city,
			'address'                 => $address,
			'zipCode'                 => $postcode,
			'phones'                  => array(
				array(
					'value' => $phone,
				),
			),
			'province'                => $state,
			'vatId'                   => $vat_number,
			'taxIdentificationNumber' => $identification_number,
		);

		if ( $company ) {

			$args['companyName'] = $company;

			/*Create the company in CRM in Cloud only if set in the options*/
			if ( $this->export_company || ( $update && $this->synchronize_companies ) ) {

				/*Export the company to CRM in Cloud*/
				$company_id = $this->export_single_company( $user_id, $args, $company );

				/*Add the company id to the contact information*/
				$args['companyId'] = $company_id;

			}
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

			array_push( $args['emails'], array( 'value' => $certified_email ) );

		}

		return $args;

	}


	/**
	 * Export the user image to CRM in Cloud
	 *
	 * @param int $user_id  the WP user id.
	 * @param int $remote_id the remote product id.
	 *
	 * @return void
	 */
	private function export_contact_image( $user_id, $remote_id ) {

		$image_url = get_avatar_url( $user_id );
		$headers   = @get_headers( $image_url );

		if ( isset( $headers[0] ) && preg_match( '|200|', $headers[0] ) ) {

			$info     = pathinfo( $image_url );
			$filename = isset( $info['filename'] ) && $info['filename'] ? $info['filename'] : 'user-avatar';
			$ext      = isset( $info['extension'] ) && $info['extension'] ? $info['extension'] : 'png';
			$filename = $filename . '.' . $ext;

			/* Generate a boundary delimiter */
			$boundary = wp_generate_password( 24, false );

			/* Define a specif timeout */
			$context = stream_context_create(
				array(
					'http' => array(
						'timeout' => 5, // Timeout in seconds.
					),
				)
			);

			/* The body payload */
			$payload  = '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . "\r\n";
			$payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
			$payload .= "\r\n";
			$payload .= file_get_contents( $image_url, true, $context );
			$payload .= "\r\n";
			$payload .= '--' . $boundary . '--';
			$payload .= "\r\n\r\n";

			/* The call */
			$response = $this->crmfwc_call->call( 'post', 'Contact/' . $remote_id . '/Photo', $payload, false, true, $boundary );

		}

	}


	/**
	 * Export single WP user to CRM in Cloud
	 *
	 * @param int    $user_id the WP user id.
	 * @param object $order the WC order to get the customer details.
	 * @param bool   $update user update with true.
	 *
	 * @return void
	 */
	public function export_single_user( $user_id = 0, $order = null, $update = false ) {

		$order_id   = is_object( $order ) ? $order->get_id() : null;
		$remote_id  = $user_id ? get_user_meta( $user_id, 'crmfwc-id', true ) : $order->get_meta( 'crmfwc-user-id', true );
		$company_id = get_user_meta( $user_id, 'crmfwc-company-id', true );

		if ( $order_id || $remote_id || $user_id ) {

			$args       = $this->prepare_user_data( $user_id, $order, $update );
			$company_id = isset( $args['companyId'] ) ? $args['companyId'] : $company_id;
			$remote_id  = $this->crmfwc_call->call( 'post', 'Contact/CreateOrUpdate', $args );

			if ( is_int( $remote_id ) ) {

				/*Update user_meta only if wp user exists*/
				if ( 0 !== $user_id ) {

					update_user_meta( $user_id, 'crmfwc-id', $remote_id );

				} elseif ( $order_id ) {

					$order->update_meta_data( 'crmfwc-user-id', $remote_id );
					$order->save();

				}
			}
		}

		/*Export orders ad opportunities only if set in the options*/
		if ( ( $this->export_orders && ! $update ) || $order_id ) {

			if ( $remote_id ) {

				/*Export user opportunities*/
				$this->export_opportunities( $user_id, $remote_id, 1, $order_id ); // temp.

			}

			if ( $company_id ) {

				/*Export company opportunities*/
				if ( $this->company_opportunities ) {

					$this->export_opportunities( $user_id, $company_id, 0, $order_id );

				}
			}
		}

		/* Export avatar */
		if ( $user_id && $remote_id ) {

			$image_response = $this->export_contact_image( $user_id, $remote_id );

		}

	}


	/**
	 * Export WP users as customers/ suppliers in CRM in Cloud
	 *
	 * @return void
	 */
	public function export_users() {

		if ( isset( $_POST['crmfwc-export-users-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['crmfwc-export-users-nonce'] ) ), 'crmfwc-export-users' ) ) {

			/*Check options*/
			$roles          = isset( $_POST['roles'] ) ? self::sanitize_array( $_POST['roles'] ) : array();
			$export_company = isset( $_POST['export-company'] ) ? sanitize_text_field( wp_unslash( $_POST['export-company'] ) ) : 0;
			$export_orders  = isset( $_POST['export-orders'] ) ? sanitize_text_field( wp_unslash( $_POST['export-orders'] ) ) : 0;

			/*Save to the db*/
			update_option( 'crmfwc-users-roles', $roles );
			update_option( 'crmfwc-export-company', $export_company );
			update_option( 'crmfwc-export-orders', $export_orders );

			$args     = array( 'role__in' => $roles );
			$users    = get_users( $args );
			$response = array();

			if ( $users ) {

				$n = 0;

				/* Set transient for progress bar */
				set_transient( 'crmfwc-total-contacts-actions', count( $users ), DAY_IN_SECONDS );

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

			echo wp_json_encode( $response );

		}

		exit;

	}


	/**
	 * Search contact on CRM in Cloud by email
	 *
	 * @param  string $email the contact email.
	 *
	 * @return int the remote contact id
	 */
	public function search_remote_contact( $email ) {

		$response = $this->crmfwc_call->call( 'get', "Contact/Search?filter=startswith(emails, '$email')" );

		if ( isset( $response[0]->id ) ) {

			return $response[0]->id;

		}

	}

	/**
	 * Search company on CRM in Cloud by company name
	 *
	 * @param  string $company_name the company name.
	 *
	 * @return int the remote company id
	 */
	public function search_remote_company( $company_name ) {

		$response = $this->crmfwc_call->call( 'get', "Company/Search?filter=startswith(companyName, '$company_name')" );

		if ( isset( $response[0]->id ) ) {

			return $response[0]->id;

		}

	}


	/**
	 * Export user and his opportunities when a WC order is completed
	 *
	 * @param  int $order_id the WC order id.
	 *
	 * @return void
	 */
	public function wc_order_callback( $order_id ) {

		/*Export new WC orders only if set in the options*/
		if ( $this->wc_export_orders && ! wp_is_post_autosave( $order_id ) ) {

			$order = wc_get_order( $order_id );

			if ( is_object( $order ) ) {

				$this->export_single_user( $order->get_customer_id(), $order );

			}
		}

	}


	/**
	 * Do not update user by this hook while a new order is createrd
	 *
	 * @return void
	 */
	public function deactivate_profile_update() {

		remove_action( 'profile_update', array( $this, 'update_remote_contact' ), 10 );

	}


	/**
	 * Fired when a WC order is updated
	 *
	 * @param  int    $order_id the WC order id.
	 * @param  object $post     the post.
	 *
	 * @return void
	 */
	public function wc_order_update_callback( $order_id, $post = null ) {

		$this->wc_order_callback( $order_id );

	}


	/**
	 * Delete CRM in cloud contact id and company id from the db
	 *
	 * @param  int $id the CRM in Cloud contact id.
	 *
	 * @return void
	 */
	public function delete_remote_id( $id ) {

		$users = get_users(
			array(

				'meta_key'   => 'crmfwc-id',
				'meta_value' => $id,

			)
		);

		if ( isset( $users[0]->ID ) ) {

			/*Contact id*/
			delete_user_meta( $users[0]->ID, 'crmfwc-id' );

			/*Company id*/
			$company_id = get_user_meta( $users[0]->ID, 'crmfwc-company-id', true );

			/*Delete company only if set in the options*/
			if ( $this->delete_company && $company_id ) {

				/*Delete from CRM in Cloud*/
				$this->crmfwc_call->call( 'delete', 'Company/Delete/' . $company_id );

				/*delete info from the db*/
				delete_user_meta( $users[0]->ID, 'crmfwc-company-id' );

			}
		}

	}


	/**
	 * Delete all opportunities information from the db
	 *
	 * @return void
	 */
	public function delete_opportunities() {

        /* Remove the action on order update */
        remove_action( 'woocommerce_update_order', array( $this, 'wc_order_update_callback' ), 10, 2 );

		$orders = wc_get_orders(
			array(
				'meta_query' => array(
					array(
						'key' => 'crmfwc-contact-opportunities',
					),
				),
			)
		);

		if ( is_array( $orders ) ) {

			foreach ( $orders as $order ) {

				/*Delete contact opportunities*/
				$order->delete_meta_data( 'crmfwc-contact-opportunities' );

				/*Delete company opportunities only if set in the options*/
				if ( $this->delete_company ) {

					$order->delete_meta_data( 'crmfwc-company-opportunities' );

				}

				$test = $order->save();

			}
		}

        /* Restart the action */
        add_action( 'woocommerce_update_order', array( $this, 'wc_order_update_callback' ), 10, 2 );

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
	 * Delete all customers/ suppliers in CRM in Cloud
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['crmfwc-delete-users-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['crmfwc-delete-users-nonce'] ) ), 'crmfwc-delete-users' ) ) {

			/*Check option*/
			$delete_company = isset( $_POST['delete-company'] ) ? sanitize_text_field( wp_unslash( $_POST['delete-company'] ) ) : 0;

			/*Save to the db*/
			update_option( 'crmfwc-delete-company', $delete_company );

			$users = $this->get_remote_users();

			if ( is_array( $users ) && ! empty( $users ) ) {

				$n = 0;

				/* Set transient for progress bar */
				set_transient( 'crmfwc-total-contacts-delete-actions', count( $users ), DAY_IN_SECONDS );

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

				/*Delete opportunities*/
				/* $this->delete_opportunities(); */

				$response[] = array(
					'ok',
					/* translators: 1: users count */
					esc_html( sprintf( __( '%1$d users(s) delete process has begun', 'crm-in-cloud-for-wc' ), $n ) ),
				);

				echo wp_json_encode( $response );

			} else {

				$response[] = array(
					'error',
					esc_html__( 'ERROR! There are not users to delete', 'crm-in-cloud-for-wc' ),
				);

				echo wp_json_encode( $response );

			}
		}

		exit;

	}

}
new CRMFWC_Contacts();

