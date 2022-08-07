<?php
/**
 * Export products to CRM in Cloud
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 0.9.0
 */
class CRMFWC_Products {

	/**
	 * Class constructor
	 */
	public function __construct() {

		/*Class call instance*/
		$this->crmfwc_call = new CRMFWC_Call();

        /* $this->get_remote_products(); */
        /* $this->get_remote_products_cats(); */

		add_action( 'wp_ajax_export-products', array( $this, 'export_products' ) );
		add_action( 'crmfwc_export_single_product_event', array( $this, 'export_single_product' ), 10, 1 );
        add_action( 'save_post_product', array( $this, 'export_single_product' ), 10, 1 );
        add_action( 'saved_term', array( $this, 'export_single_product_cat' ), 10, 1 );

    }

    public function get_remote_products() {
        
        $response = $this->crmfwc_call->call( 'get', 'Catalog' );
        /* error_log( 'REMOTE PRODUCTS: ' . print_r( $response, true ) ); */

        if ( is_array( $response ) ) {

            foreach ( $response as $id ) {

                $prod = $this->crmfwc_call->call( 'get', 'Catalog/' . $id );
                /* error_log( 'REMOTE PROD.: ' . print_r( $prod, true ) ); */

            }

        }

    }


    public function get_remote_products_cats() {
        
        $response = $this->crmfwc_call->call( 'get', 'CatalogCategories' );
        error_log( 'REMOTE PRODUCTS CATS: ' . print_r( $response, true ) );

        if ( is_array( $response ) ) {

            foreach ( $response as $id ) {

                $cat = $this->crmfwc_call->call( 'get', 'CatalogCategories/' . $id );
                error_log( 'REMOTE PROD. CAT: ' . print_r( $cat, true ) );

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
        error_log( 'EXISTS: ' . $response );

        return $response;

    }


    public function export_single_product_cat( $term_id, $update = true ) {

        /* Get the term */
        $term                 = get_term( $term_id, 'product_cat' );
        $remote_products_cats = get_option( 'crmfwc-remote-products-cats' ) ? get_option( 'crmfwc-remote-products-cats' ) : array();
        error_log( 'REMOTE PRODUCTS CATS: ' . print_r( $remote_products_cats, true ) );
        error_log( 'TERM SLUG: ' . $term->slug );

        if ( is_object( $term ) && isset( $term->slug ) ) {
                
            $remote_cat_id = array_key_exists( $term->slug, $remote_products_cats ) ? $remote_products_cats[ $term->slug ] : null;

            if ( $remote_cat_id && ! $update ) {

                error_log( 'CAT EXISTS!' );

                /* Output directly if already in the db */
                return $remote_cat_id;

            } else {

                if ( $remote_cat_id && $update ) {

                    $delete = $this->crmfwc_call->call( 'delete', 'CatalogCategories/' . $remote_cat_id );
                    error_log( 'DELETE REMOTE CAT: ' . print_r( $delete, true ) );

                }

                $args = array(
                    'description' => $term->name,
                );

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
	 * Export single WP product to CRM in Cloud
	 *
     * @param object @product the wp product.
     *
	 * @return void
	 */
	public function export_single_product( $product_id ) {

        $product = wc_get_product( $product_id );

        /* error_log( 'PRODUCT: ' . print_r( $product, true ) ); */

        $args = array(
            'active'  => 1,
            'code'    => $product->get_sku(),
            'codeEAN' => $product->get_sku(),
            'description' => $product->get_description(),
            'productName' => $product->get_title(),
            'taxCode'     => 22,
            'price'       => $product->get_price(),
        );

        $cat_ids = $product->get_category_ids();
        error_log( 'CAT IDS: ' . print_r( $cat_ids, true ) );

        /* Add categories to CRM in Cloud */
        $remote_cats = $this->export_product_cats( $cat_ids );
        error_log( 'REMOTE CATS 2: ' . print_r( $remote_cats, true ) );

        if ( is_array( $remote_cats ) && isset( $remote_cats[0] ) ) {

            $args['category'] = $remote_cats[0];
        }
        error_log( 'EXPORT ARGS: ' . print_r( $args, true ) );

        /* Define the endpoint */
        /* $endpoint = $this->remote_product_exists( $product->get_sku() ) ? 'Catalog/' . $product->get_sku() : 'Catalog'; */
        $remote_id = get_post_meta( $product->get_id(), 'crmfwc-remote-id', true );

        /* Delete the remote product if exists */
        if ( $remote_id ) {

            $delete = $this->crmfwc_call->call( 'delete', 'Catalog/' . $remote_id );
            error_log( 'DELETE: ' . print_r( $delete, true ) );

        }

        /* Add product to CRM in Cloud */
        $response = $this->crmfwc_call->call( 'post', 'Catalog',  $args );
        error_log( 'PRODUCT SENT: ' . print_r( $response, true ) );

        if ( is_int( $response ) ) {

            update_post_meta( $product->get_id(), 'crmfwc-remote-id', $response );

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
            error_log( 'POST CATS: ' . print_r( $cats, true ) );

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
            error_log( 'ARGS: ' . print_r( $args, true ) );

            $products = get_posts( $args );
            /* error_log( 'PRODUCTS: ' . print_r( $products, true ) ); */

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

}
new CRMFWC_Products();

