<?php
/**
 * WooCommerce options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 1.0.0
 */
?>

<!-- Export form -->
<form name="crmfwc-wc" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr class="wc-export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-export-orders" disabled>
				<p class="description"><?php esc_html_e( 'Export new orders as opportunities in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
				<?php crmfwc_go_premium(); ?>
			</td>
		</tr>
		<tr class="wc-split-opportunities">
			<th scope="row"><?php esc_html_e( 'Split opportunities', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-split-opportunities" disabled>
				<p class="description"><?php esc_html_e( 'Create an opportunity for every single order item', 'crm-in-cloud-for-wc' ); ?></p>
				<?php crmfwc_go_premium(); ?>
			</td>
		</tr>
		<tr class="wc-company-opportunities">
			<th scope="row"><?php esc_html_e( 'Export company opportunities', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-wc-company-opportunities" disabled>
				<p class="description"><?php esc_html_e( 'Export opportunities for the company as well if it exists', 'crm-in-cloud-for-wc' ); ?></p>
				<?php crmfwc_go_premium(); ?>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc" value="<?php esc_html_e( 'Save settings', 'crm-in-cloud-for-wc' ); ?>" disabled>
	</p>

</form>
