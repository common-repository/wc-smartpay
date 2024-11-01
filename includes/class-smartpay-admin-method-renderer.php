<?php
/**
 * Smartpay Plugin Admin Method Renderer
 *
 * @package wc-smartpay
 */

/**
 * SmartPay_Admin_Method_Renderer
 */
class SmartPay_Admin_Method_Renderer {

	/**
	 * Render payment method information
	 *
	 * @param WC_Order $order order object.
	 * @return void
	 */
	public function render( $order ) {
		$template_path = apply_filters(
			'smartpay_admin_method_template_path',
			SmartPay::instance()->get_plugin_dir() . 'templates/admin-method.php'
		);

		if ( file_exists( $template_path ) && is_file( $template_path ) ) {
			require_once $template_path;
		}
	}
}
