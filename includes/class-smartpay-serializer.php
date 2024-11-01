<?php
/**
 * Smartpay Plugin SmartPay_Serializer
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Serializer
 */
class SmartPay_Serializer {

	/**
	 * Singletone instance
	 *
	 * @var $instance
	 */
	protected static $instance;

	/**
	 * Preventing from instantiating new objects of the class
	 */
	private function __construct() {

	}

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
	 * Preventing object from unserializing
	 */
	public function __wakeup() {
		wc_doing_it_wrong(
			__FUNCTION__,
			__( 'Unserializing instances of this class is forbidden.', 'wc-smartpay' ),
			'2.1'
		);
	}

	/**
	 * Retrieving SmartPay serialize instance
	 *
	 * @return SmartPay_Serializer
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Encoding data into json
	 *
	 * @param array $data input data.
	 * @return false|string
	 */
	public function serialize( array $data ) {
		return wp_json_encode( $data );
	}

	/**
	 * Decoding json string
	 *
	 * @param string $json json string.
	 * @return mixed
	 * @throws Exception Exception type thrown.
	 */
	public function unserialize( $json ) {
		$result = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Exception( 'Unable to unserialize value. Error: ' . json_last_error_msg() );
		}
		return $result;
	}
}
