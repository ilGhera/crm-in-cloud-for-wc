<?php
/**
 * Products options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/admin
 * @since 0.9.0
 */

/*Get value from the db*/
$products_cats = get_option( 'crmfwc-products-cats' );
/* error_log( 'PRODUCTS CATS: ' . print_r( $products_cats, true ) ); */
?>

<!-- Export form -->
<form name="crmfwc-export-products" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User categories', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<select class="crmfwc-products-cats crmfwc-select" name="crmfwc-products-cats[]" multiple>
					<?php
					$cats = get_terms( 'product_cat' );
                    /* error_log( 'CATS: ' . print_r( $cats, true ) ); */

					foreach ( $cats as $cat ) {

						$selected = is_array( $products_cats ) && in_array( $cat->term_id, $products_cats ) ? ' selected="selected"' : '';

						echo '<option value="' . esc_attr( $cat->term_id ) . '"' . esc_html( $selected ) . '> ' . esc_html( __( $cat->name, 'woocommerce' ) ) . '</option>';

					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select the product categories to export to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>

			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc export-products products" value="<?php esc_html_e( 'Export to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>

<!-- Delete form -->
<form name="crmfwc-delete-products" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete products', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all products on CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc red products products" value="<?php esc_html_e( 'Delete from CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>
