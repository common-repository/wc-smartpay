<?php
/**
 * Smartpay Plugin Smartpay iframe return type top footer renderer
 *
 * @package wc-smartpay
 */

/**
 * Smartpay Iframe return type top footer renderer
 */
class SmartPay_Iframe_Return_Type_Top_Footer_Renderer {

	/**
	 * Retrieve configuration
	 *
	 * @return SmartPay_Config
	 */
	private static function config() {
		$config = SmartPay::instance()->config();
		if ( $config ) {
			return $config;
		}
		SmartPay::instance()->init_config( new SmartPay_Payment_Gateway() );
		return SmartPay::instance()->config();
	}

	/**
	 * Render content
	 *
	 * @return void
	 */
	public static function render() {
		global $wp;

		$is_payment_page = is_checkout() && ! empty( $wp->query_vars['order-pay'] );

		if ( ! $is_payment_page ) {
			return;
		}

		$template_path = apply_filters(
			'smartpay_iframe_template_path',
			SmartPay::instance()->get_plugin_dir() . 'templates/iframe-return-type-top-footer.php'
		);

		if ( file_exists( $template_path ) && is_file( $template_path ) ) {
			require_once $template_path;
		}
	}
}
