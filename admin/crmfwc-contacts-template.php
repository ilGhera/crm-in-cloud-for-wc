<?php
/**
 * Contacts options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc-premium/admin
 * @since 0.9.0
 */

/*Get value from the db*/
$contacts_roles       = get_option( 'crmfwc-users-roles' );
$export_company       = get_option( 'crmfwc-export-company' ) ? get_option( 'crmfwc-export-company' ) : 0;
$export_orders        = get_option( 'crmfwc-export-orders' ) ? get_option( 'crmfwc-export-orders' ) : 0;
$delete_company       = get_option( 'crmfwc-delete-company' ) ? get_option( 'crmfwc-delete-company' ) : 0;
$synchronize_contacts = get_option( 'crmfwc-synchronize-contacts' ) ? get_option( 'crmfwc-synchronize-contacts' ) : 0;


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
				<input type="checkbox" name="crmfwc-export-company" value="1"<?php echo 1 == $export_company ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export the company if present in the user profile', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
		<tr class="export-orders">
			<th scope="row"><?php esc_html_e( 'Export orders', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-export-orders" value="1"<?php echo 1 == $export_orders ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Export user orders as opportunities in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" class="button-primary crmfwc export-users contacts" value="<?php esc_html_e( 'Export to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>

<!-- Delete form -->
<form name="crmfwc-delete-contacts" class="crmfwc-form one-of"  method="post" action="">

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
				<input type="checkbox" name="crmfwc-delete-company" value="1"<?php echo 1 == $delete_company ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Delete the company linked to the contact in CRM in Cloud', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc red users contacts" value="<?php esc_html_e( 'Delete from CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>

<form name="crmfwc-contacts-settings" class="crmfwc-form"  method="post" action="">

    <h2><?php esc_html_e( 'Settings', 'crm-in-cloud-for-wc' ); ?></h2>

	<table class="form-table">
		<tr class="synchronize-contacts">
			<th scope="row"><?php esc_html_e( 'Synchronize contacts', 'crm-in-cloud-for-wc' ); ?></th>
			<td>
				<input type="checkbox" name="crmfwc-synchronize-contacts" value="1"<?php echo 1 == $synchronize_contacts ? ' checked="checked"' : ''; ?>>
				<p class="description"><?php esc_html_e( 'Update contacts on CRM in Cloud in real time', 'crm-in-cloud-for-wc' ); ?></p>
			</td>
		</tr>
        <?php wp_nonce_field( 'crmfwc-contacts-settings', 'crmfwc-contacts-settings-nonce' ); ?>
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc contacts-settings" value="<?php esc_html_e( 'Save settings', 'crm-in-cloud-for-wc' ); ?>" />
	</p>

</form>
