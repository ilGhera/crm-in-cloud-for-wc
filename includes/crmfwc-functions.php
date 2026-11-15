<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 *
 * @since 1.2.0
 */

if ( ! function_exists( 'crmfwc_ilghera_url' ) ) {

	/**
	 * Indirizzo di una pagina prodotto su ilghera.com: lingua dell'utente e tracciamento.
	 *
	 * @param string $slug     lo slug del prodotto, uguale nelle due lingue.
	 * @param string $campaign utm_campaign: settings (upgrade) o license (rinnovo).
	 *
	 * @return string
	 */
	function crmfwc_ilghera_url( $slug, $campaign = 'settings' ) {

		$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$path   = ( 0 === strpos( $locale, 'it' ) ) ? 'it/prodotto' : 'product';

		return add_query_arg(
			array(
				'utm_source'   => 'wp-plugin',
				'utm_medium'   => 'crm-in-cloud-for-wc',
				'utm_campaign' => $campaign,
			),
			'https://www.ilghera.com/' . $path . '/' . $slug . '/'
		);
	}
}

/**
 * Go premium button
 */
function crmfwc_go_premium() {

	$title = __( 'This is a premium functionality, click here for more information', 'crm-in-cloud-for-wc' );
	$output = '<span class="crmfwc label label-warning premium">';
		$output .= '<a href="' . esc_url( crmfwc_ilghera_url( 'crm-in-cloud-for-woocommerce-premium', 'settings' ) ) . '" target="_blank" title="' . esc_attr( $title ) . '">Premium</a>';
	$output .= '</span>';

	$allowed = array(
		'span' => array(
			'class' => array(),
		),
		'a'    => array(
			'target' => array(),
			'title'  => array(),
			'href'   => array(),
		),
	);

	echo wp_kses( $output, $allowed );

}

