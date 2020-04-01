<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 0.9.0
 */

/**
 * Update checker
 */
require( CRMFWC_DIR . 'plugin-update-checker/plugin-update-checker.php' );

$crmfwc_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://www.ilghera.com/wp-update-server-2/?action=get_metadata&slug=crm-in-cloud-for-wc',
	CRMFWC_FILE,
	'crm-in-cloud-for-wc'
);


/**
 * Secure update check with the Premium Key
 *
 * @param  array $query_args the default args.
 * @return array            the updated args
 */
function crmfwc_secure_update_check( $query_args ) {

	$key = base64_encode( get_option( 'crmfwc-premium-key' ) );

	if ( $key ) {

		$query_args['premium-key'] = $key;

	}

	return $query_args;

}
$crmfwc_update_checker->addQueryArgFilter( 'crmfwc_secure_update_check' );


/**
 * Plugin update message
 *
 * @param  array $plugin_data plugin information.
 * @param  array $response    available plugin update information.
 */
function crmfwc_update_message( $plugin_data, $response ) {

	$key = get_option( 'crmfwc-premium-key' );

	$message = null;

	if ( ! $key ) {

		$message = 'A <b>Premium Key</b> is required for keeping this plugin up to date. Please, add yours in the <a href="' . CRMFWC_SETTINGS . '">options page</a> or click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

	} else {

		$decoded_key = explode( '|', base64_decode( $key ) );
		$bought_date = date( 'd-m-Y', strtotime( $decoded_key[1] ) );
		$limit       = strtotime( $bought_date . ' + 365 day' );
		$now         = strtotime( 'today' );

		if ( $limit < $now ) {

			$message = 'It seems like your <strong>Premium Key</strong> is expired. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

		} elseif ( '7082' !== $decoded_key[2] ) { // temp.

			$message = 'It seems like your <strong>Premium Key</strong> is not valid. Please, click <a href="https://www.ilghera.com/product/wc-exporter-for-reviso-premium/" target="_blank">here</a> for prices and details.';

		}

	}

	$allowed_tags = array(
		'strong' => array(),
		'a'      => array(
			'href'   => array(),
			'target' => array(),
		),
	);

	echo ( $message ) ? '<br><span class="crmfwc-alert">' . wp_kses( $message, $allowed_tags ) . '</span>' : '';

}
add_action( 'in_plugin_update_message-' . CRMFWC_DIR_NAME . '/wc-exporter-for-reviso.php', 'crmfwc_update_message', 10, 2 );
