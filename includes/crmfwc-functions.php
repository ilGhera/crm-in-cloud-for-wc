<?php
/**
 * Functions
 *
 * @author ilGhera
 * @package crm-in-cloud-for-wc/includes
 * @since 0.9.0
 */

/**
 * Go premium button
 */
function go_premium() {

	$title = __( 'This is a premium functionality, click here for more information', 'crm-in-cloud-for-wc' );
	$output = '<span class="crmfwc label label-warning premium">';
		$output .= '<a href="https://www.ilghera.com/product/crm-in-cloud-for-woocommerce-premium" target="_blank" title="' . esc_attr( $title ) . '">Premium</a>';
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
