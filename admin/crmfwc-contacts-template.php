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
$export_company = get_option( 'crmfwc-export-company' ) ? get_option( 'crmfwc-export-company' ) : 0;
$export_orders  = get_option( 'crmfwc-export-orders' ) ? get_option( 'crmfwc-export-orders' ) : 0;
$delete_company = get_option( 'crmfwc-delete-company' ) ? get_option( 'crmfwc-delete-company' ) : 0;
?>

<!-- Export form -->
<form name="crmfwc-export-contacts" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User roles', 'crmfwc' ); ?></th>
			<td>
				<select class="crmfwc-contacts-role crmfwc-select" name="crmfwc-contacts-role[]" multiple>
					<?php

					global $wp_roles;

					$roles = $wp_roles->get_names();

					foreach ( $roles as $key => $value ) {
					
						$selected = is_array( $contacts_roles ) && in_array( $key, $contacts_roles ) ? ' selected="selected"' : '';

						echo '<option value="' . esc_attr( $key ) . '"' . $selected . '> ' . esc_html( __( $value, 'woocommerce' ) ) . '</option>';
					
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select the user role(s) to export to CRM in Cloud', 'crmfwc' ); ?></p>

			</td>
		</tr>
		<tr class="export-company">
			<th scope="row"><?php esc_html_e( 'Export company', 'crmfwc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-export-company" value="1"<?php echo 1 == $export_company ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export the company if present in the user profile', 'crmfwc' ); ?></p>
			</td>
		</tr>
		<tr class="export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crmfwc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-export-orders" value="1"<?php echo 1 == $export_orders ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export user orders as opportunities in CRM in Cloud', 'crmfwc' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc export-users contacts" value="<?php esc_html_e( 'Export to CRM in Cloud', 'crmfwc' ); ?>" />
	</p>

</form>

<!-- Delete form -->
<form name="crmfwc-delete-contacts" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Delete contacts', 'crmfwc' ); ?></th>
			<td>
				<p class="description"><?php esc_html_e( 'Delete all contacts on CRM in Cloud.', 'crmfwc' ); ?></p>
			</td>
		</tr>
		<tr class="delete-company">
			<th scope="row"><?php esc_html_e( 'Delete company', 'crmfwc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-delete-company" value="1"<?php echo 1 == $delete_company ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Delete the company linked to the contact in CRM in Cloud', 'crmfwc' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc red users contacts" value="<?php esc_html_e( 'Delete from CRM in Cloud', 'crmfwc' ); ?>" />
	</p>

</form>
