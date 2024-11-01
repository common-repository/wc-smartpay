<?php
/**
 * Smartpay Plugin SmartPay_Iframe_Redirect_Renderer
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Iframe_Redirect_Renderer
 */
class SmartPay_Iframe_Redirect_Renderer {

	/**
	 * Render content
	 *
	 * @param integer $order_id woocommerce order id.
	 * @return void
	 */
	public static function render( $order_id ) {

		if ( ! SmartPay::instance()->config()->is( 'jump_to_top_enabled' ) ) {
			return;
		}

		$template_path = apply_filters(
			'smartpay_iframe_template_path',
			SmartPay::instance()->get_plugin_dir() . 'templates/iframe-redirect.php'
		);

		/**
		 * Woocommerce order object
		 *
		 * @var WC_Order $order
		 */
		$order = wc_get_order( $order_id );

		if ( file_exists( $template_path ) && is_file( $template_path ) ) {
			require_once $template_path;
		}
	}
}
