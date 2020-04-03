<?php
/**
 * Contacts options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
 */

/*Get value from the db*/
$contacts_roles = get_option( 'crmfwc-users-roles' );
?>

<!-- Export form -->
<form name="crmfwc-export-contacts" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User roles', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<select class="crmfwc-contacts-role crmfwc-select" name="crmfwc-contacts-role[]" multiple>
					<?php

					global $wp_roles;

					$roles = $wp_roles->get_names();

					foreach ( $roles as $key => $value ) {

						$selected = is_array( $contacts_roles ) && in_array( $key, $contacts_roles ) ? ' selected="selected"' : '';

						echo '<option value="' . esc_attr( $key ) . '"' . esc_html( $selected ) . '> ' . esc_html( __( $value, 'woocommerce' ) ) . '</option>';

					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select the user role(s) to export to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>

			</td>
		</tr>
		<tr class="export-company">
			<th scope="row"><?php esc_html_e( 'Export company', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-export-company" disabled>
				<p class="description"><?php esc_html_e( 'Export the company if present in the user profile', 'crm-in-cloud-for-wc' ); ?></p>
				<?php go_premium(); ?>
			</td>
		</tr>
		<tr class="export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-export-orders" disabled>
				<p class="description"><?php esc_html_e( 'Export user orders as opportunities in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
				<?php go_premium(); ?>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc export-users contacts" value="<?php esc_html_e( 'Export to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>

<!-- Delete form -->
<form name="crmfwc-delete-contacts" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete contacts', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all contacts on CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<tr class="delete-company">
			<th scope="row"><?php esc_html_e( 'Delete company', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-delete-company">
				<p class="description"><?php esc_html_e( 'Delete the company linked to the contact in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
				<?php go_premium(); ?>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc red users contacts" value="<?php esc_html_e( 'Delete from CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>
