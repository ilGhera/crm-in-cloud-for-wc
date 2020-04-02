<?php
/**
 * WooCommerce options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
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
				<?php go_premium(); ?>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc" value="<?php esc_html_e( 'Save', 'crm-in-cloud-for-wc' ); ?>" disabled>
	</p>

</form>
