<?php
/**
 * Smartpay Plugin LSmartPay_Logger
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Logger
 */
class SmartPay_Logger {
	/**
	 * Singleton instance
	 *
	 * @var SmartPay_Logger $instance
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
	 * Mocking class __construct
	 */
	private function __construct() {

	}

	/**
	 * Singletone instance
	 *
	 * @return SmartPay_Logger
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Retrieve serializer instance
	 *
	 * @return SmartPay_Serializer
	 */
	private function serializer() {
		return SmartPay_Serializer::instance();
	}

	/**
	 * Log api request
	 *
	 * @param string $ref_id log reference id.
	 * @param mixed  $body log body.
	 * @return void
	 */
	public function log_request( $ref_id, $body ) {
		$this->log( $ref_id, 'REQUEST', $body );
	}

	/**
	 * Log api response
	 *
	 * @param string $ref_id log reference id.
	 * @param mixed  $body log body.
	 * @return void
	 */
	public function log_response( $ref_id, $body ) {
		$this->log( $ref_id, 'RESPONSE', $body );
	}

	/**
	 * Log data
	 *
	 * @param string $ref_id log reference id.
	 * @param string $type log type.
	 * @param string $body log body.
	 * @return void
	 */
	public function log( $ref_id, $type, $body ) {
		global $wpdb;
		$ref_id = 'log-' . $ref_id;
		if ( is_array( $body ) ) {
			$body = $this->serializer()->serialize( $body );
		}

        // @codingStandardsIgnoreStart
		$wpdb->insert(
			$wpdb->prefix . 'smartpay_log',
			array(
				'ref_id'  => $ref_id,
				'type'    => $type,
				'message' => $body,
				'date'    => wp_date( 'Y-m-d H:i:s' ),
			)
		);
        // @codingStandardsIgnoreEnd
	}
}
