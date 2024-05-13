<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 *
 * @since 1.2.0
 */

$email = get_option( 'crmfwc-email' );
$passw = get_option( 'crmfwc-passw' );

?>

<!-- CRM in Cloud login -->
<form name="crmfwc-settings" class="crmfwc-form-login connection"  method="post" action="">

	<form name="crmfwc-login" type="post" action="post">
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Connect', 'crm-in-cloud-for-wc' ); ?></th>
				<td>
					<div class="bootstrap-iso">
						<div class="check-connection">
							<input type="email" class="regular-text crmfwc-email" name="crmfwc-email" placeholder="<?php esc_html_e( 'Email', 'crm-in-cloud-for-wc' ); ?>" value="<?php echo esc_attr( $email ); ?>" required>
							<input type="password" class="regular-text crmfwc-passw" name="crmfwc-passw" placeholder="<?php esc_html_e( 'Password', 'crm-in-cloud-for-wc' ); ?>" value="<?php echo esc_attr( $passw ); ?>" required>
						</div>
						<p class="description"><?php esc_html_e( 'Connect with your CRM in Cloud credentials', 'crm-in-cloud-for-wc' ); ?></p>	
						<input type="submit" class="button-primary crmfwc-connect" name="crmfwc-connect" value="<?php esc_html_e( 'Connect to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>">
						<input type="submit" class="button-primary red crmfwc-disconnect" name="crmfwc-connect" value="<?php esc_html_e( 'Disconnect from CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>">

						<div class="crmfwc-login-error alert alert-danger"></div>
					</div>
				</td>
			</tr>
		</table>

		<?php wp_nonce_field( 'crmfwc-login', 'crmfwc-login-nonce' ); ?>

	</form>

</form>

