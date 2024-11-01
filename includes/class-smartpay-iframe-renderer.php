<?php
/**
 * Smartpay Plugin Smartpay iframe renderer
 *
 * @package wc-smartpay
 */

/**
 * Smartpay SmartPay_Iframe_Renderer
 */
class SmartPay_Iframe_Renderer {

	/**
	 * Iframe height
	 *
	 * @var $iframe_height
	 */
	protected $iframe_height;

	/**
	 * IFrame width
	 *
	 * @var $iframe_width
	 */
	protected $iframe_width;

	/**
	 * Iframe URL
	 *
	 * @var $iframe_url
	 */
	protected $iframe_url;

	/**
	 * Message
	 *
	 * @var string $_message
	 */
	protected $message;

	/**
	 * Render
	 *
	 * @param integer $order_id woocommerce order id.
	 * @return void
	 */
	public function render( $order_id ) {
		$template_path = apply_filters(
			'smartpay_iframe_template_path',
			SmartPay::instance()->get_plugin_dir() . 'templates/iframe.php'
		);

		/**
		 * Woocommerce order object
		 *
		 * @var WC_Order $order
		 */
		$order             = wc_get_order( $order_id );
		$payment_gateway   = SmartPay::instance()->get_payment_gateway();
		$payment_processor = new SmartPay_Payment_Processor( $payment_gateway );
		$result            = $payment_processor->execute( $order );

		if ( 'success' !== $result['result'] ) {
			echo esc_html( $result['message'] );
			return;
		}

		$this->iframe_height = SmartPay::instance()->config()->get( 'iframe_height' );
		$this->iframe_width  = SmartPay::instance()->config()->get( 'iframe_width' );
		$this->iframe_url    = $result['redirect'];

		if ( file_exists( $template_path ) && is_file( $template_path ) ) {
			require_once $template_path;
		}
	}
}
