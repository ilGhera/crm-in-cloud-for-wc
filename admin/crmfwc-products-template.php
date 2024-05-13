<?php
/**
 * Products options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/admin
 *
 * @since 1.3.0
 */

/*Get value from the db*/
$products_cats              = get_option( 'crmfwc-products-cats' );
$synchronize_products       = get_option( 'crmfwc-synchronize-products' );
$synchronize_products_terms = get_option( 'crmfwc-synchronize-products-terms' );

?>

<!-- Export form -->
<form name="crmfwc-export-products" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'Products categories', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<select class="crmfwc-products-cats crmfwc-select" name="crmfwc-products-cats[]" multiple>
					<?php
					$cats = get_terms( 'product_cat' );

					foreach ( $cats as $single_cat ) {

						$selected = is_array( $products_cats ) && in_array( $single_cat->term_id, $products_cats, true ) ? ' selected="selected"' : '';

						echo '<option value="' . esc_attr( $single_cat->term_id ) . '"' . esc_html( $selected ) . '> ' . esc_html( __( $single_cat->name, 'woocommerce' ) ) . '</option>';

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
<form name="crmfwc-delete-products" class="crmfwc-form one-of"  method="post" action="">

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

<!-- Settings form -->
<form name="crmfwc-products-settings" class="crmfwc-form"  method="post" action="">

	<h2><?php esc_html_e( 'Synchronization options', 'crm-in-cloud-for-wc' ); ?></h2>

	<table class="form-table">
		<tr class="synchronize-products">
			<th scope="row"><?php esc_html_e( 'Products', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-synchronize-products" value="1"<?php echo 1 === intval( $synchronize_products ) ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Update products on CRM in Cloud in real time', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<tr class="synchronize-products-terms">
			<th scope="row"><?php esc_html_e( 'Products categories', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-synchronize-products-terms" value="1"<?php echo 1 === intval( $synchronize_products_terms ) ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Update products categories on CRM in Cloud in real time', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<?php wp_nonce_field( 'crmfwc-products-settings', 'crmfwc-products-settings-nonce' ); ?>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc products-settings" value="<?php esc_html_e( 'Save settings', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>

