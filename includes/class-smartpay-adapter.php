<?php
/**
 * Smartpay Plugin SmartPay_Serializer
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Serializer
 */
class SmartPay_Adapter {
	/*
	 * Payment api base url
	 */
	const PROD_API_BASE_URL = 'https://api.protected-payment.com/v1/';

	/*
	 * Checkout api endpoint
	 */
	const SANDBOX_API_BASE_URL = 'https://app11.smartpay.co.il/v1/';

	/**
	 * Retrieve serializer
	 *
	 * @return SmartPay_Serializer
	 */
	private function serializer() {
		return SmartPay_Serializer::instance();
	}

	/**
	 * Decoding data
	 *
	 * @param string $json json string.
	 * @return array
	 */
	protected function decode( $json ) {
		try {
			return $this->serializer()->unserialize( $json );
		} catch ( Exception $e ) {
			return array(
				'error' => __( 'Could not decode response.', 'wc-smartpay' ),
			);
		}
	}

	/**
	 * Retrieve base url
	 *
	 * @return string
	 */
	private function get_base_url() {
		return SmartPay::instance()->config()->is( 'sandbox' ) ?
			self::SANDBOX_API_BASE_URL : self::PROD_API_BASE_URL;
	}

	/**
	 * Build endpoint
	 *
	 * @param string $url_part url path.
	 * @return string
	 */
	private function build_url( $url_part ) {
		return sprintf(
			'%s/%s',
			rtrim( $this->get_base_url(), '/' ),
			ltrim( $url_part, '/' )
		);
	}

	/**
	 * Retrieve authorization params
	 *
	 * @param bool $use_alternative flag to define if to use alternative API credentials.
	 * @return string
	 */
	private function get_authorization( $use_alternative = false ) {
        // @codingStandardsIgnoreStart
		return base64_encode(
			implode(
				':',
				array(
					SmartPay::instance()->config()->get( $use_alternative ? 'alternative_api_key' : 'api_key' ),
					SmartPay::instance()->config()->get( $use_alternative ? 'alternative_api_secret' : 'api_secret' ),
				)
			)
		);
        // @codingStandardsIgnoreEnd
	}

	/**
	 * Make api call
	 *
	 * @param array  $request_data API request data.
	 * @param string $endpoint API url path.
	 * @param bool   $use_alternative flag to define if to use alternative API credentials.
	 * @return mixed
	 * @throws Exception Throwing exception.
	 */
	private function request( $request_data, $endpoint, $use_alternative = false ) {
		wc_get_logger()->info( 'REQUEST: ' . $this->serializer()->serialize( $request_data ) );
		$response      = _wp_http_get_object()->post(
			$this->build_url( $endpoint ),
			array(
				'headers' => array(
					'Content-type'  => 'application/json; charset=utf-8',
					'Accept'        => 'application/json',
					'Authorization' => 'Basic ' . $this->get_authorization( $use_alternative ),
				),
				'body'    => $this->serializer()->serialize( $request_data ),
			)
		);
		$response_body = wp_remote_retrieve_body( $response );
		wc_get_logger()->error( 'RESPONSE: ' . $response_body );
		try {
			return $this->serializer()->unserialize( $response_body );
		} catch ( \Exception $e ) {
			return array(
				'status'           => 'failed',
				'terminal_message' => $response_body,
				'emessage'         => $e->getMessage(),
			);
		}
	}

	/**
	 * Execute charge
	 *
	 * @param array $request_data API request data.
	 * @return array
	 * @throws Exception Throwing exception.
	 */
	private function charge( $request_data ) {
		return $this->request(
			$request_data,
			'charges/create',
			SmartPay::instance()->config()->is( 'use_separate_terminal' )
		);
	}

	/**
	 * Charge
	 *
	 * @param array $request_data API request data.
	 * @return array
	 */
	public function capture( $request_data ) {
		return $this->charge( $request_data );
	}

	/**
	 * Refund
	 *
	 * @param array $request_data API request data.
	 * @return array
	 */
	public function refund( $request_data ) {
		return $this->charge( $request_data );
	}

	/**
	 * Capture transaction
	 *
	 * @param array $request_data API request data.
	 * @return array
	 */
	public function capture_transaction( $request_data ) {
		return $this->charge( $request_data );
	}

	/**
	 * Authorize
	 *
	 * @param array $request_data API request data.
	 * @return array
	 */
	public function authorize( $request_data ) {
		return $this->charge( $request_data );
	}

	/**
	 * Generate payment page link
	 *
	 * @param array $request_data API request data.
	 * @param bool  $use_alternative Flag identifying if to us alternative API credentials.
	 * @return array
	 * @throws Exception Throwing exception.
	 */
	public function generate_payment_page_link( $request_data, $use_alternative = false ) {
		$request_data = apply_filters( 'smartpay_generate_payment_page_link_before', $request_data );
		return $this->request( $request_data, 'checkout/pages', $use_alternative );
	}

	/**
	 * Create token
	 *
	 * @param array $request_data API request data.
	 * @return array
	 * @throws Exception Throwing exception.
	 */
	public function create_token( $request_data ) {
		return $this->request( $request_data, 'tokens/create' );
	}

	/**
	 * Retrieve token
	 *
	 * @param string $token token.
	 * @return array
	 * @throws Exception Throwing exception.
	 */
	public function get_token( $token ) {
		return $this->request( array( 'token' => $token ), 'tokens/create' );
	}

	/**
	 * Retrieve transaction
	 *
	 * @param string $transaction_id transaction id for api.
	 * @param string $action_param action param for api.
	 * @return mixed
	 * @throws Exception Throwing exception.
	 */
	public function get_transaction( $transaction_id, $action_param ) {
		return $this->request(
			array(
				'id'           => $transaction_id,
				'action_param' => $action_param,
			),
			'charges/get',
			SmartPay::instance()->config()->is( 'use_separate_terminal' )
		);
	}
}
