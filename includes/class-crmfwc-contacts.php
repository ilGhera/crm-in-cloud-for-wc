<?php
/**
 * Export users to CRM in Cloud
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 1.1.0
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
	 * Class constructor
	 */
	public function __construct() {


		/*Classes instance*/
		$this->crmfwc_call = new CRMFWC_Call();
        $this->products    = new CRMFWC_Products();

        /*Check access*/
        $token = $this->crmfwc_call->get_access_token();

        /*Exit if not connected*/
        if ( ! $token || is_object( $token ) && isset( $token->error ) ) {

            return;

        }

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
        
        /* Hooks */
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'eg_increase_time_limit' ) );
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'eg_increase_action_scheduler_batch_size' ) );
		add_action( 'wp_ajax_delete-remote-users', array( $this, 'delete_remote_users' ) );
		add_action( 'crmfwc_delete_remote_single_user_event', array( $this, 'delete_remote_single_user' ), 10, 1 );
		add_action( 'wp_ajax_export-users', array( $this, 'export_users' ) );
		add_action( 'crmfwc_export_single_user_event', array( $this, 'export_single_user' ), 10, 2 );

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
        error_log( 'COMPANY ARGS: ' . print_r( $args, true ) );

		$response = $this->crmfwc_call->call( 'post', 'Company/CreateOrUpdate', $args );
        error_log( 'COMPANY RESPONSE: ' . error_log( $response, true ) );

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

		$output = array();
        $data   = array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
        );

        /* Get only the specific order */
        if ( $order_id ) {

            $data['p'] = $order_id;
            $data['post_status'][] = 'trash';

        }

		$posts = get_posts( $data );

		if ( $posts ) {

			foreach ( $posts as $post ) {

				$order               = new WC_Order( $post->ID );
				$args                = $this->get_single_order_opportunities( $order, $remote_id, $cross_type );
				$output[ $post->ID ] = $args;

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
     * @param int
     *
	 * @return void
	 */
	private function export_opportunities( $user_id, $remote_id, $cross_type = 0, $order_id = null ) {

        $endpoint = 'Opportunity/CreateOrUpdate/';
		$data     = $this->get_user_opportunities( $user_id, $remote_id, $cross_type, $order_id );
        error_log( 'DATA: ' . print_r( $data, true ) );

		if ( is_array( $data ) ) {

			foreach ( $data as $key => $value ) {

				$meta_key            = 1 === $cross_type ? 'crmfwc-contact-opportunities' : 'crmfwc-company-opportunities';
				$saved_opportunities = get_post_meta( $key, $meta_key, true );

				if ( ! $saved_opportunities || $order_id === $key ) {

					if ( is_array( $value ) && ! empty( $value ) ) {

                        update_post_meta( $key, $meta_key, 1 );

						foreach ( $value as $k => $val ) {

                            if ( $order_id === $key ) {

                                $opportunity_id  = get_post_meta( $key, 'crmfwc-opportunity-' . $cross_type . '-' . $k, true );

                                if ( $opportunity_id ) {

                                    if ( 'trash' === get_post_status( $order_id ) ) {

                                        $response = $this->crmfwc_call->call( 'delete', 'Opportunity/' . $opportunity_id );

                                        delete_post_meta( $key, 'crmfwc-opportunity-' . $cross_type . '-' . $k );

                                        continue;

                                    }

                                    /* Update an existing opportunity */
                                    $val['id'] = $opportunity_id;

                                }

                            }

							$response = $this->crmfwc_call->call( 'post', $endpoint, $val );
                            error_log( 'RESPONSE OPPORTUNITY: ' . print_r( $response, true ) );

                            if ( is_int( $response ) ) {

                                update_post_meta( $key, 'crmfwc-opportunity-' . $cross_type . '-' . $k, $response );

                            } 

						}

					}

				}

			}

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
     * @param int $user_id the WP user id.
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

		$website = null;

		if ( $order ) {

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

			/*Create the company in CRM in Cloud only if set in the options*/
			if ( $this->export_company ) {

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

			array_push( $args['emails'] , array( 'value' => $certified_email ) );
		
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

        /* $image_url = get_avatar_url( $user_id, array( 'default' => '404' ) ); */
        $image_url = get_avatar_url( $user_id );
        error_log( 'IMAGE URL: ' . $image_url );

        $headers   = @get_headers( $image_url );

        if ( isset( $headers[0] ) && preg_match( "|200|", $headers[0] ) ) {

            $info     = pathinfo( $image_url );
            $filename = isset( $info['filename'] ) && $info['filename'] ? $info['filename'] : 'user-avatar';
            $ext      = isset( $info['extension'] ) && $info['extension'] ? $info['extension'] : 'png'; 
            $filename = $filename . '.' . $ext; 

            /* Generate a boundary delimiter */
            $boundary = wp_generate_password( 24, false );

            /* Define a specif timeout */
            $context = stream_context_create(array(
                'http' => array(
                    'timeout' => 5, // Timeout in seconds
                )
            ));

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
        $remote_id  = $user_id ? get_user_meta( $user_id, 'crmfwc-id', true ) : get_post_meta( $order_id, 'crmfwc-user-id', true );
        $company_id = get_user_meta( $user_id, 'crmfwc-company-id', true );

        error_log( 'ORDER ID: ' . $order_id );
        error_log( 'REMOTE ID: ' . $remote_id );

		/* if ( $order_id || $remote_id ) { */

            $args       = $this->prepare_user_data( $user_id, $order, $update );
            $company_id = isset( $args['companyId'] ) ? $args['companyId'] : $company_id;
			$remote_id  = $this->crmfwc_call->call( 'post', 'Contact/CreateOrUpdate', $args );
            error_log( 'REMOTE ID 2: ' . print_r( $remote_id, true ) );

			if ( is_int( $remote_id ) ) {

				/*Update user_meta only if wp user exists*/
				if ( 0 !== $user_id ) {

					update_user_meta( $user_id, 'crmfwc-id', $remote_id );

                } elseif ( $order_id ) {

					update_post_meta( $order_id, 'crmfwc-user-id', $remote_id );

                }

            }

		/* } */

        /*Export orders ad opportunities only if set in the options*/
        if ( ( $this->export_orders && ! $update ) || $order_id ) {

            error_log( 'ESPORTO ORDINI' );

            /*Export user opportunities*/
            $this->export_opportunities( $user_id, $remote_id, 1, $order_id ); // temp.

            /*Export company opportunities*/
            if ( $this->company_opportunities ) {

                $this->export_opportunities( $user_id, $company_id, 0, $order_id );

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

		if ( isset( $_POST['crmfwc-export-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-export-users-nonce'] ), 'crmfwc-export-users' ) ) {

			/*Check options*/
			$roles                = isset( $_POST['roles'] ) ? self::sanitize_array( $_POST['roles'] ) : array();
			$export_company       = isset( $_POST['export-company'] ) ? sanitize_text_field( wp_unslash( $_POST['export-company'] ) ) : 0;
			$export_orders        = isset( $_POST['export-orders'] ) ? sanitize_text_field( wp_unslash( $_POST['export-orders'] ) ) : 0;

			/*Save to the db*/
			update_option( 'crmfwc-users-roles', $roles );
			update_option( 'crmfwc-export-company', $export_company );
			update_option( 'crmfwc-export-orders', $export_orders );

			$args     = array( 'role__in' => $roles );
			$users    = get_users( $args );
			$response = array();

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
	 * Delete CRM in cloud contact id and company id from the db
	 *
	 * @param  int $id the CRM in Cloud contact id.
     *
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

		global $wpdb;

		$table = $wpdb->prefix . 'postmeta';

		/*Delete contact opportunities*/
		$wpdb->delete( $table, array( 'meta_key' => 'crmfwc-contact-opportunities' ) );

		/*Delete company opportunities only if set in the options*/
		if ( $this->delete_company ) {

			$wpdb->delete( $table, array( 'meta_key' => 'crmfwc-company-opportunities' ) );

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
	 * Delete all customers/ suppliers in CRM in Cloud
	 */
	public function delete_remote_users() {

		if ( isset( $_POST['crmfwc-delete-users-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-delete-users-nonce'] ), 'crmfwc-delete-users' ) ) {

			/*Check option*/
			$delete_company = isset( $_POST['delete-company'] ) ? sanitize_text_field( wp_unslash( $_POST['delete-company'] ) ) : 0;

			/*Save to the db*/
			update_option( 'crmfwc-delete-company', $delete_company );

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

				/*Delete opportunities*/
				$this->delete_opportunities();

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
new CRMFWC_Contacts();

