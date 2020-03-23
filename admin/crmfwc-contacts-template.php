<?php
/**
 * Contacts options
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
 */

?>

<!-- Export form -->
<form name="crmfwc-export-contacts" class="crmfwc-form"  method="post" action="">

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo esc_html_e( 'User role', 'crmfwc' ); ?></th>
			<td>
				<select class="crmfwc-contacts-role crmfwc-select" name="crmfwc-contacts-role">
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();

					/*Get value from the db*/
					$contacts_role = get_option( 'crmfwc-contacts-role' );

					foreach ( $roles as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '"' . ( $key === $contacts_role ? ' selected="selected"' : '' ) . '> ' . esc_html( __( $value, 'woocommerce' ) ) . '</option>';
					}
					?>
				</select>
				<p class="description"><?php esc_html_e( 'Select your contacts user role', 'crmfwc' ); ?></p>

			</td>
		</tr>
	</table>
	
	<p class="submit">
		<input type="submit" name="download_csv" class="button-primary crmfwc export-users contacts" value="<?php esc_html_e( 'Export to CRM in Cloud', 'crmfwc' ); ?>" />
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
	</table>

	<p class="submit">
		<input type="submit" class="button-primary crmfwc red users contacts" value="<?php esc_html_e( 'Delete from CRM in Cloud', 'crmfwc' ); ?>" />
	</p>

</form>
