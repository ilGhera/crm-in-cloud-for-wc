<?php
/**
 * General settings
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/admin
 * @since 0.9.0
 */

$email = get_option( 'crmfwc-email' );
$passw = get_option( 'crmfwc-passw' );

?>

<!-- CRM in Cloud login -->
<form name="crmfwc-settings" class="crmfwc-form connection"  method="post" action="">

	<form name="crmfwc-login" type="post" action="post">
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Connect', 'crm-in-cloud-for-wc' ); ?></th>
				<td>
					<input type="email" class="regular-text" name="crmfwc-email" placeholder="<?php esc_html_e( 'Email', 'crm-in-cloud-for-wc' ); ?>" value="<?php echo esc_attr( $email ); ?>" required>
				</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<input type="password" class="regular-text" name="crmfwc-passw" placeholder="<?php esc_html_e( 'Password', 'crm-in-cloud-for-wc' ); ?>" value="<?php echo esc_attr( $passw ); ?>" required>
					<p class="description"><?php esc_html_e( 'Connect with your CRM in Cloud credentials', 'crm-in-cloud-for-wc' ); ?></p>	
					<div class="check-connection">
						<input type="submit" class="button-primary crmfwc-connect" name="crmfwc-connect" value="<?php esc_html_e( 'Connect to CRM in Cloud', 'crm-in-cloud-for-wc' ); ?>">
					</div>
				</td>
			</tr>
		</table>

		<?php wp_nonce_field( 'crmfwc-login', 'crmfwc-login-nonce' ); ?>
	
	</form>

</form>


