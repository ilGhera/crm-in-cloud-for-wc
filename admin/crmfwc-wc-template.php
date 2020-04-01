<?php
/**
 * WooCommerce options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
 */

/*Get value from the db*/
$wc_export_orders  = get_option( 'crmfwc-wc-export-orders' ) ? get_option( 'crmfwc-wc-export-orders' ) : 0;

if ( isset( $_POST['crmfwc-wc-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-wc-nonce'] ), 'crmfwc-wc' ) ) {
	
	$wc_export_orders = isset( $_POST['crmfwc-wc-export-orders'] ) ? sanitize_text_field(  wp_unslash( $_POST['crmfwc-wc-export-orders'] ) ) : 0;
	update_option( 'crmfwc-wc-export-orders', $wc_export_orders );

}
?>

<!-- Export form -->
<form name="crmfwc-wc" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr class="wc-export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crmfwc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-export-orders" value="1"<?php echo 1 == $wc_export_orders ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export new orders as opportunities in CRM in Cloud', 'crmfwc' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<?php wp_nonce_field( 'crmfwc-wc', 'crmfwc-wc-nonce' ); ?>
		<input type="hidden" name="crmfwc-wc-hidden" name="1">
		<input type="submit" class="button-primary crmfwc" value="<?php esc_html_e( 'Save', 'crmfwc' ); ?>" />
	</p>

</form>
