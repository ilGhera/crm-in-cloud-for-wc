<?php
/**
 * Export products to CRM in Cloud
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 1.0.0
 */
class CRMFWC_Products {

    /**
     * Synchronize products in real time
     *
     * @var bool
     */
    private $synchronize_products;

    
    /**
     * Synchronize products taxonomy terms in real time
     *
     * @var bool
     */
    private $synchronize_products_terms;


	/**
	 * Class constructor
	 */
	public function __construct( $init = false ) {

        /*Class call instance*/
        $this->crmfwc_call = new CRMFWC_Call();

        if ( $init ) {

            /* Get options */
            $this->synchronize_products       = get_option( 'crmfwc-synchronize-products' );
            $this->synchronize_products_terms = get_option( 'crmfwc-synchronize-products-terms' );

            /* Hooks */
            add_action( 'admin_init', array( $this, 'products_settings' ), 10 );
            add_action( 'wp_ajax_export-products', array( $this, 'export_products' ) );
            add_action( 'crmfwc_export_single_product_event', array( $this, 'export_single_product' ), 10, 1 );
            add_action( 'wp_ajax_delete-remote-products', array( $this, 'delete_remote_products' ) );
            add_action( 'crmfwc_delete_remote_single_product_event', array( $this, 'delete_remote_single_product' ), 10, 1 );

            /* Conditionals hooks */
            if ( $this->synchronize_products ) {

                add_action( 'save_post_product', array( $this, 'export_single_product' ), 10, 1 );

            }

            if ( $this->synchronize_products_terms ) {

                add_action( 'saved_term', array( $this, 'export_single_product_cat' ), 10, 1 );

            }

        }

    }


    /**
     * User synchronization options 
     *
     * @return void 
     */
    public function products_settings() {


		if ( isset( $_POST['crmfwc-products-settings-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-products-settings-nonce'] ), 'crmfwc-products-settings' ) ) {

            $synchronize_products = isset( $_POST['crmfwc-synchronize-products'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-synchronize-products'] ) ) : 0;
            $synchronize_products_terms = isset( $_POST['crmfwc-synchronize-products-terms'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-synchronize-products-terms'] ) ) : 0;

            /*Save to the db*/
            update_option( 'crmfwc-synchronize-products', $synchronize_products );
            update_option( 'crmfwc-synchronize-products-terms', $synchronize_products_terms );

        }

    }


    /**
     * Get all the products from CRM in Cloud
     *
     * @param int $remote_id the remote product id.
     *
'     * @return array the remote products IDs
     */
    public function get_remote_products( $remote_id = null ) {
        
        $response = $this->crmfwc_call->call( 'get', 'Catalog' );

        return $response;

    }


    /**
     * Prepare the product data to export with the opportunity 
     *
     * @param int $remote_id the remote product ID.
     * @param int $qta       the item quantity in the order.
     *
     * @return object
     */
    public function prepare_opportunity_product_data( $remote_id, $qta = 1 ) {

        $output  = null;
        $product = $this->crmfwc_call->call( 'get', 'Catalog/' . $remote_id );
 
        if ( is_object( $product ) && isset( $product->id ) ) {

            $output = array(
                    'active'               => 1,
                    'productId'            => $product->id,
                    'productName'          => $product->productName,
                    'productPrice'         => $product->price,
                    'productQta'           => $qta,
                    /* 'productTaxableAmount' => $product->price, */
                    /* 'discountFormula' => */ 
                    /* 'id'        => */ 
                    /* 'productUm' => */ 
                    /* 'productUmId' => 0 */
            );

            /* Taxable amount based on the WC options */
            $taxable_amount = $product->price;

            /* if ( get_option( 'woocommerce_prices_include_tax' ) ) { */
                //....
            /* } */

            $output['productTaxableAmount'] = $taxable_amount;

        }

        return $output;

    }


    /**
     * Get all the products categories from CRM in Cloud
     *
     * @return array
     */
    public function get_remote_products_cats() {
        
        $response = $this->crmfwc_call->call( 'get', 'CatalogCategories' );

        if ( is_array( $response ) ) {

            foreach ( $response as $id ) {

                $cat = $this->crmfwc_call->call( 'get', 'CatalogCategories/' . $id );

            }

        }

    }


    /**
     * Check if a specific WC produt is alreay in CRM in Cloud
     *
     * @param string $sku the remote id.
     *
     * @return bool
     */
    private function remote_product_exists( $remote_id ) {

        $response = $this->crmfwc_call->call( 'get', 'Catalog/' . $remote_id . '/Exists');

        return $response;

    }

    
    /**
     * Check if a WC product was already exported
     *
     * @param int  $product_id the WC product id.
     * @param bool $export     add the product to CRM in cloud if necessary.
     *
     * @return int the remote product id
     */
    public function get_remote_product_id( $product_id, $export = false ) {

        $remote_product_id = get_post_meta( $product_id, 'crmfwc-remote-id', true );

        /* Export the product if necessary */
        if ( ! $remote_product_id && $export ) {

            $remote_product_id = $this->export_single_product( $product_id );

        }

        return $remote_product_id;

    }


    /**
     * Export a dingle product category to CRM in Cloud
     *
     * @return int the remote cat id
     */
    public function export_single_product_cat( $term_id, $update = true ) {

        /* Get the term */
        $term                 = get_term( $term_id, 'product_cat' );
        $remote_products_cats = get_option( 'crmfwc-remote-products-cats' ) ? get_option( 'crmfwc-remote-products-cats' ) : array();

        if ( is_object( $term ) && isset( $term->slug ) ) {
                
            $remote_cat_id = array_key_exists( $term->slug, $remote_products_cats ) ? $remote_products_cats[ $term->slug ] : null;

            if ( $remote_cat_id && ! $update ) {

                /* Output directly if already in the db */
                return $remote_cat_id;

            } else {

                $args = array(
                    'description' => $term->name,
                );

                /* Update remote product cat providing the ID */
                if ( $remote_cat_id && $update ) {

                    $args['id'] = $remote_cat_id;

                }

                /* Check for a parent term */
                if ( $term->parent ) {

                    /* Get the parent term */
                    $parent_term = get_term( $term->parent, 'product_cat' );

                    if ( in_array( $parent_term->slug, $remote_products_cats ) ) {

                        /* Add parent to args if already in the db */
                        $args['parentId'] = $remote_products_cats[ $parent_term->slug ];

                    } else {

                        $data = array(
                            'description' => $parent_term->name,
                        );

                        /* Add parent to CRM in Cloud */
                        $parent = $this->crmfwc_call->call( 'post', 'CatalogCategories', $data );

                        if ( is_int( $parent ) ) {

                            /* Add parent to args */
                            $args['parentId'] = $parent;
                            
                            /* Prepare parent for the db */
                            $remote_products_cats[ $parent_term->slug ] = $parent;

                        }
                        
                    }

                }

                /* Add term to CRM in Cloud */
                $response = $this->crmfwc_call->call( 'post', 'CatalogCategories', $args );

                if ( is_int( $response ) ) {

                    /* Prepare term for the db */
                    $remote_products_cats[ $term->slug ] = $response;

                    /* Update terms in the db */
                    update_option( 'crmfwc-remote-products-cats', $remote_products_cats );

                    /* Output the response */
                    return $response; 

                }

            }

        }

    }


    /**
     * Export a list of products categories
     *
     * @param array $cat_ids the WC product categories IDs.
     *
     * $return array the remote cats IDs
     */
    public function export_product_cats( $cat_ids ) {

        $output = array();

        if ( is_array( $cat_ids ) ) {
        
            foreach ( $cat_ids as $id ) {
                
                $output[] = $this->export_single_product_cat( $id, false );

            }

        }
        
        return $output;

    }


    /**
     * Get the tax values from CRM in Cloud
     *
     * @return array
     */
    public function get_remote_tax_codes() {

        $output    = array();
        $transient = get_transient( 'crmfwc-remote-tax-codes' );

        if ( $transient ) {

            $output = $transient;

        } else {

            $response = $this->crmfwc_call->call( 'get', 'TaxValue' );

            if ( is_array( $response ) ) {

                foreach ( $response as $code ) {
                    
                    $tax = $this->crmfwc_call->call( 'get', 'TaxValue/' . $code );

                    if ( is_object( $tax ) && isset( $tax->taxCode ) ) {

                        $output[ $tax->taxValue ] = $tax->taxCode;

                    }
                }

                set_transient( 'crmfwc-remote-tax-codes', $output, DAY_IN_SECONDS );

            }

        }

        return $output;

    }


    /**
     * Add a new tax code to CRM in Cloud
     *
     * @param int @tax_rate the product tax rate.
     *
     * @return int the tax code
     */
    private function add_remote_tax_code( $tax_rate ) {

        $args = array(
            'active'         => 1,
            'taxValue'       => $tax_rate,
            'taxCode'        => $tax_rate,
            'taxDescription' => sprintf( __( 'Taxable %d%%', 'crm-in-cloud-for-wc' ), $tax_rate ),
        );

        $response = $this->crmfwc_call->call( 'post', 'TaxValue', $args );

        if ( is_int( $response ) ) {

            delete_transient( 'crmfwc-remote-tax-codes' );

            return $tax_rate;

        }

    }


    /**
	 * Get the product tax rate 
	 *
	 * @param  int $product_id the WC product ID.
     *
	 * @return int
	 */
	private function get_tax_rate( $product_id  ) {

		$output = 'FC';

		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) {

			$output            = 0;
			$tax_status        = get_post_meta( $product_id, '_tax_status', true );
			$parent_id         = wp_get_post_parent_id( $product_id );
			$parent_tax_status = $parent_id ? get_post_meta( $parent_id, '_tax_status', true ) : '';

			if ( 'taxable' == $tax_status || ( '' == $tax_status && 'taxable' === $parent_tax_status ) ) {

				/* Null with VAT 22 */
				$tax_class = $tax_status ? get_post_meta( $product_id, '_tax_class', true ) : get_post_meta( $parent_id, '_tax_class', true );

				if ( 'parent' === $tax_class && 'taxable' === $parent_tax_status ) {
                    
					$tax_class = get_post_meta( $parent_id, '_tax_class', true );

				}

				global $wpdb;

				$query = "SELECT tax_rate, tax_rate_name FROM " . $wpdb->prefix . "woocommerce_tax_rates WHERE tax_rate_class = '" . $tax_class . "'";

				$results = $wpdb->get_results( $query, ARRAY_A );

				if ( $results ) {

					$output = intval( $results[0]['tax_rate'] );

				}
			}
		}
		
		return $output;

	}


    /**
     * Get the remote tax code corresponding to the product tax rat
     *
	 * @param  int $product_id the WC product ID.
     *
     * @return int the tax code
     */
    private function get_remote_tax_code( $product_id ) {

        $tax_rate  = $this->get_tax_rate( $product_id );
        $tax_codes = $this->get_remote_tax_codes();

        if ( isset( $tax_codes[ $tax_rate ] ) ) {

            $output = $tax_codes[ $tax_rate ];

        } else {

            $output = $this->add_remote_tax_code( $tax_rate );

        }

        return $output;

    }


    /**
     * Export the product image to CRM in Cloud
     *
     * @param int $image_id  the WC product image id.
     * @param int $remote_id the remote product id.
     *
     * @return void
     */
    private function export_product_image( $image_id, $remote_id ) {

        $image_path = wp_get_original_image_path( $image_id, true );
        $filename   = basename( $image_path );

        /* Generate a boundary delimiter */
        $boundary = wp_generate_password( 24, false );

        /* The bosy payload */
        $payload  = '--' . $boundary;
        $payload .= "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . "\r\n";
        $payload .= "Content-Type: jpg\r\n"; // If you      know the mime-type
        $payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
        $payload .= "\r\n";
        $payload .= file_get_contents( $image_path );
        $payload .= "\r\n";
        $payload .= '--' . $boundary . '--';
        $payload .= "\r\n\r\n";

        /* The call */
        $response = $this->crmfwc_call->call( 'post', 'Catalog/' . $remote_id . '/Photo', $payload, false, true, $boundary );

    }


	/**
	 * Export single WP product to CRM in Cloud
	 *
     * @param object @product the wp product.
     *
	 * @return void
	 */
	public function export_single_product( $product_id ) {

        if ( wp_is_post_autosave( $product_id ) ) {

            return;

        }

        $product   = wc_get_product( $product_id );
        $remote_id = get_post_meta( $product_id, 'crmfwc-remote-id', true );

        if ( ! is_object( $product ) ) {
            
            return;

        } elseif ( 'trash' === $product->get_status() && $remote_id ) {

            /* Delete the product in CRM in Cloud */
            $delete = $this->delete_remote_single_product( $remote_id );

            return;

        } elseif ( 'publish' === $product->get_status() ) {

            $product_code = $product->get_sku() ? $product->get_sku() : 'crmfwc-' . $product_id;

            $args = array(
                'active'      => 1,
                'code'        => $product_code,
                'codeEAN'     => $product_code,
                'description' => $product->get_description(),
                'productName' => $product->get_title(),
                'taxCode'     => $this->get_remote_tax_code( $product_id ), 
                'price'       => $product->get_price(),
            );

            $cat_ids = $product->get_category_ids();

            /* Add categories to CRM in Cloud */
            $remote_cats = $this->export_product_cats( $cat_ids );

            if ( is_array( $remote_cats ) && isset( $remote_cats[0] ) ) {

                $args['category'] = $remote_cats[0];
            }

            /* Delete the remote product if exists */
            if ( $remote_id ) {

                $args['id'] = $remote_id;

            }

            /* Add product to CRM in Cloud */
            $response = $this->crmfwc_call->call( 'post', 'Catalog',  $args );

            if ( ! is_int( $response ) ) {

                /* Try to get the remote product by code (sku) */
                $results = $this->crmfwc_call->call( 'get', "Catalog/SearchIds?filter=code eq '" . $product_code . "'" );

                if ( isset( $results[0] ) && is_int( $results[0] ) ) {

                    $response = $results[0];

                }

            }

            update_post_meta( $product->get_id(), 'crmfwc-remote-id', intval( $response ) );

            if ( $product->get_image_id() ) {

                /* Export product image */
                $this->export_product_image( $product->get_image_id(), $response );

            }

        }

    }


	/**
	 * Export WP products to CRM in Cloud
	 *
	 * @return void
	 */
	public function export_products() {

		if ( isset( $_POST['crmfwc-export-products-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-export-products-nonce'] ), 'crmfwc-export-products' ) ) {

            $response = array();

			/*Check options*/
			$cats = isset( $_POST['cats'] ) ? CRMFWC_Contacts::sanitize_array( $_POST['cats'] ) : array();

            /* Update data */
            update_option( 'crmfwc-products-cats', $cats );
            
            $args = array(
                'post_type'   => 'product',
                'post_status' => 'publish',
                'numberposts' => -1,
            );

            if ( ! empty( $cats ) ) {

                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $cats,
                    ),
                );

            }

            $products = get_posts( $args );

            if ( is_array( $products ) ) {

                $n = 0;

                foreach ( $products as $prod ) {

                    $n++;

					/*Schedule action*/
					as_enqueue_async_action(
						'crmfwc_export_single_product_event',
						array(
							'product-id' => $prod->ID,
						),
						'crmfwc-export-products'
					);

                }

				$response[] = array(
					'ok',
					/* translators: 1: products count */
					esc_html( sprintf( __( '%1$d product(s) export process has begun', 'crm-in-cloud-for-wc' ), $n ) ),
				);

            } else {

				$response[] = array(
					'error',
					esc_html__( 'No products to export', 'crm-in-cloud-for-wc' ),
				);

            }

			echo json_encode( $response );

        }

        exit;

     }

    
    /*
     * Delete a single product in CRM in Cloud
     *
     * @param int $remote_id the remote product id.
     *
     * @return void
     */
    public function delete_remote_single_product( $remote_id ) {

        $delete = $this->crmfwc_call->call( 'delete', 'Catalog/' . $remote_id );

        return $delete;

    }


	/**
	 * Delete all customers/ suppliers in CRM in Cloud
     *
     * @return void
	 */
	public function delete_remote_products() {

		if ( isset( $_POST['crmfwc-delete-products-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-delete-products-nonce'] ), 'crmfwc-delete-products' ) ) {

			$products = $this->get_remote_products();

			 if ( is_array( $products ) && ! empty( $products ) ) { 

				$n = 0;

				foreach ( $products as $product_id ) {

					$n++;

                    /* Schedule action */
					as_enqueue_async_action(
						'crmfwc_delete_remote_single_product_event',
						array(
							'product-id' => $product_id,
						),
						'crmfwc-delete-remote-products'
					);

				}

                /* Delete all the remote products keys from the db */
                delete_post_meta_by_key( 'crmfwc-remote-id' );

				 $response[] = array( 
				 	'ok', 
				 	/* translators: 1: products count */ 
				 	esc_html( sprintf( __( '%1$d products(s) delete process has begun', 'crm-in-cloud-for-wc' ), $n ) ), 
				 ); 

				 echo json_encode( $response ); 

			 } else { 

				 $response[] = array( 
				 	'error', 
				 	esc_html__( 'ERROR! There are not products to delete', 'crm-in-cloud-for-wc' ), 
				 ); 

				 echo json_encode( $response ); 

			 } 

		}

		exit;

	}

}
new CRMFWC_Products( true );

