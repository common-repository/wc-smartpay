<?php
/**
 * Smartpay Plugin SmartPay_Billi_Observer
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Billi_Observer
 */
class SmartPay_Billi_Observer {
	/**
	 * Singletone instance
	 *
	 * @var SmartPay_Billi_Observer $_instance
	 */
	private static $instance;

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Cloning is forbidden.', 'wc-smartpay' ),
			'2.1'
		);
	}

	/**
	 * Wakeup
	 */
	public function __wakeup() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Unserializing instances of this class is forbidden.', 'wc-smartpay' ),
			'2.1'
		);
	}

	/**
	 * Mocking constructor for singltone
	 */
	private function __construct() {

	}

	/**
	 * Retrieve Smartpay API Adapter
	 *
	 * @return SmartPay_Adapter
	 */
	private function adapter() {
		if ( ! $this->adapter ) {
			$this->adapter = new SmartPay_Adapter();
		}
		return $this->adapter;
	}

	/**
	 * Singletone instance
	 *
	 * @return SmartPay_Billi_Observer
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if invoice can be synced or not
	 *
	 * @param bool     $result boolean result.
	 * @param WC_Order $order woocommerce order object.
	 * @return mixed
	 */
	public function can_sync_invoice( $result, $order ) {

		if ( ! $result
			|| ! $order->get_transaction_id()
			|| $order->get_payment_method() !== SmartPay_Payment_Gateway::METHOD_CODE
		) {
			return $result;
		}

		$response = (object) $this->adapter()->get_transaction( $order->get_transaction_id(), 'capture' );
		return $response && 'succeeded' === $response->status;
	}
}
