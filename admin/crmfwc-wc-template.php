<?php
/**
 * WooCommerce options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/admin
 * @since 1.0.0
 */

/*Get value from the db*/
$wc_export_orders       = get_option( 'crmfwc-wc-export-orders' ) ? get_option( 'crmfwc-wc-export-orders' ) : 0;
$wc_split_opportunities = get_option( 'crmfwc-wc-split-opportunities' ) ? get_option( 'crmfwc-wc-split-opportunities' ) : 0;
$wc_company_opportunities = get_option( 'crmfwc-wc-company-opportunities' ) ? get_option( 'crmfwc-wc-company-opportunities' ) : 0;

if ( isset( $_POST['crmfwc-wc-nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['crmfwc-wc-nonce'] ), 'crmfwc-wc' ) ) {

	$wc_export_orders = isset( $_POST['crmfwc-wc-export-orders'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-wc-export-orders'] ) ) : 0;
	update_option( 'crmfwc-wc-export-orders', $wc_export_orders );

	$wc_split_opportunities = isset( $_POST['crmfwc-wc-split-opportunities'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-wc-split-opportunities'] ) ) : 0;
	update_option( 'crmfwc-wc-split-opportunities', $wc_split_opportunities );

	$wc_company_opportunities = isset( $_POST['crmfwc-wc-company-opportunities'] ) ? sanitize_text_field( wp_unslash( $_POST['crmfwc-wc-company-opportunities'] ) ) : 0;
	update_option( 'crmfwc-wc-company-opportunities', $wc_company_opportunities );
}
?>

<!-- Export form -->
<form name="crmfwc-wc" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr class="wc-export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-export-orders" value="1"<?php echo 1 == $wc_export_orders ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export new orders as opportunities in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<tr class="wc-split-opportunities">
			<th scope="row"><?php esc_html_e( 'Split opportunities', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-split-opportunities" value="1"<?php echo 1 == $wc_split_opportunities ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Create an opportunity for every single order item', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<tr class="wc-company-opportunities">
			<th scope="row"><?php esc_html_e( 'Export company opportunities', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-company-opportunities" value="1"<?php echo 1 == $wc_company_opportunities ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export opportunities for the company as well if it exists', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<?php wp_nonce_field( 'crmfwc-wc', 'crmfwc-wc-nonce' ); ?>
		<input type="hidden" name="crmfwc-wc-hidden" name="1">
		<input type="submit" class="button-primary crmfwc" value="<?php esc_html_e( 'Save', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>
