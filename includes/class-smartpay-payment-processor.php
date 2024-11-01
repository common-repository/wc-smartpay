<?php
/**
 * Smartpay Plugin payment processor
 *
 * @package wc-smartpay
 */

/**
 * SmartPay Payment Processor
 */
class SmartPay_Payment_Processor {

	/**
	 * Payment gateway class instance
	 *
	 * @var WC_Payment_Gateway $paymente_gateway
	 */
	private $payment_gateway;

	/**
	 * Adapter
	 *
	 * @var Dintero_Adapter $adapter
	 */
	protected $adapter;

	/**
	 * Initializing payment processor
	 *
	 * @param WC_Payment_Gateway $payment_gateway payment gateway object.
	 */
	public function __construct( WC_Payment_Gateway $payment_gateway ) {
		$this->payment_gateway = $payment_gateway;
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
	 * Retrieve card type resolver
	 *
	 * @return SmartPay_Card_Type_Resolver
	 */
	private function card_type_resolver() {
		return SmartPay_Card_Type_Resolver::instance();
	}

	/**
	 * Retrieve serializer
	 *
	 * @return SmartPay_Serializer
	 */
	private function serializer() {
		return SmartPay_Serializer::instance();
	}

	/**
	 * Retrieve logger model
	 *
	 * @return SmartPay_Logger
	 */
	private function logger() {
		return SmartPay_Logger::instance();
	}

	/**
	 * Calculate payment steps
	 *
	 * @param int $amount amount.
	 * @return bool|string|true
	 */
	private function calculate_payment_steps( $amount ) {
		$payment_steps = SmartPay::instance()->config()->get( 'num_of_payments_options' );
		$payment_steps = apply_filters( 'smartpay_payment_steps', $payment_steps );

		$result = array();
		if ( empty( $payment_steps ) ) {
			return '';
		}

		foreach ( explode( '|', $payment_steps ) as $step ) {
			$result[]                              = $step;
			list( $step_amount, $num_of_payments ) = explode( ':', $step );
			if ( $step_amount >= $amount ) {
				break;
			}
		}

		if ( count( $result ) === 1 ) {
			array_unshift( $result, '1:1' );
		}

		return implode( '|', $result );
	}

	/**
	 * Retrieve IPN Url
	 *
	 * @return string
	 */
	private function get_ipn_url() {
		return get_bloginfo( 'url' ) . '/wc-api/smartpay_ipn';
	}

	/**
	 * Resolve success order status option name
	 *
	 * @param string $action_param action param.
	 * @return string
	 */
	private function resolve_order_status_option_name( $action_param ) {
		return 'capture' === $action_param ? 'success_capture_status' : 'success_check_authorize_status';
	}

	/**
	 * Prepare amount for sending in json
	 *
	 * @param float $amount formatting amount into integer.
	 * @return int
	 */
	private function format_amount( $amount ) {
		return intval( (string) ( round( $amount, 2 ) * 100 ) );
	}

    /**
     * Retrieve request
     *
     * @return SmartPay_Request
     */
    private function request() {
        return SmartPay_Request::instance();
    }

	/**
	 * Processing payment
	 *
	 * @param WC_Order $order woocommerce order object.
	 * @return array
	 */
	public function execute( $order ) {

		if ( empty( $order ) || ! ( $order instanceof WC_Order ) ) {
			return array(
				'result'  => 'failure',
				'message' => __( 'Order is not valid for Smartpay payment method.', 'wc-smartpay' ),
			);
		}

		$send_currency = SmartPay::instance()->config()->get( 'currency' );
		$params        = array(
			'page_uuid'                => SmartPay::instance()->config()->get( 'page_uuid' ),
			'expired_at_minutes'       => 200,
			'credit_terms'             => 'regular',
			'values'                   => array(
				'moreinfo1'  => $order->get_id(),
				'extra_data' => array(
					// Prevent email verification screen. see plugins/woocommerce/templates/checkout/form-verify-email.php.
					'check_submission' => wp_create_nonce( 'wc_verify_email' ),
				),
			),
			'success_url'              => $order->get_checkout_order_received_url(),
			'fail_url'                 => $order->get_cancel_order_url(),
			'cancel_url'               => $order->get_cancel_order_url(),
			'ipn_enabled'              => (int) SmartPay::instance()->config()->get( 'ipn_enable' ),
			'ipn_url'                  => $this->get_ipn_url(),
			'enable_language_switcher' => (int) SmartPay::instance()->config()->is( 'language_switcher' ),
			'currency'                 => strtolower( empty( $send_currency ) ? $order->get_currency() : $send_currency ),
			'amount'                   => $this->format_amount( $order->get_total() ),
		);

		$payment_steps = $this->calculate_payment_steps( $order->get_total() );
		if ( ! empty( $payment_steps ) ) {
			$params['num_of_payments_options'] = $payment_steps;
			$params['transaction_terms']       = 'payments';
		}

		$language = SmartPay::instance()->config()->get( 'language' );
		if ( $language ) {
			$params['default_language'] = $language;
		}

		$action_type = SmartPay::instance()->config()->get( 'payment_action' );
		if ( $action_type ) {
			$params['action_type'] = $action_type;
		}

		if ( SmartPay::instance()->config()->is( 'terms_checkbox_enabled' ) ) {
			$params['terms_checkbox_enabled'] = 1;
			$terms_options                    = array(
				'terms_text',
				'terms_url_text',
				'terms_url',
			);
			foreach ( $terms_options as $option_code ) {
				$option_value = SmartPay::instance()->config()->get( $option_code );
				if ( $option_value ) {
					$params[ $option_code ] = $option_value;
				}
			}
		}

		$page_template = SmartPay::instance()->config()->get( 'page_template' );
		if ( ! empty( $page_template ) ) {
			$params['page_template'] = $page_template;
		}

		// preparing terms and conditions params.
		$callback_url = SmartPay::instance()->config()->get( 'callback_url' );
		if ( ! empty( $callback_url ) ) {
			$params['callback_url'] = $callback_url;
		}

		$params = apply_filters( 'smartpay_override_iframe_params', $params );

		$this->logger()->log_request( $order->get_id(), $params );

		$response = $this->adapter()->generate_payment_page_link( $params );

		$this->logger()->log_response( $order->get_id(), $response );

		if ( $response && 'succeeded' === $response['status'] ) {
			return array(
				'result'   => 'success',
				'redirect' => $response['url'],
			);
		}

		return array(
			'result'  => 'failure',
			'message' => $response['terminal_message'],
		);
	}

	/**
	 * Capturing order
	 *
	 * @param WC_Order $order order object.
	 * @param float    $amount amount to capture.
	 * @return array
	 */
	public function capture( $order, $amount ) {
		$cc_token = get_user_meta( $order->get_user_id(), 'cc_token', true );
		if ( ! $cc_token ) {
			$cc_token = $order->get_meta( 'cc_token' );
		}

		$params = array(
			'amount'    => $this->format_amount( $amount ),
			'source'    => 'token',
			'token'     => $cc_token,
			'moreinfo1' => $order->get_id(),
		);

		if ( $order->get_meta( 'action_param' ) === 'authorize' ) {
			unset( $params['token'] );
			$params['source']         = 'authorization';
			$params['transaction_id'] = $order->get_transaction_id();
		}

		$payment_details = $order->get_meta( 'smartpay_details' );
		if ( $payment_details ) {
			$payment_details_array = json_decode( $payment_details, true );
			$num_of_payments       = isset( $payment_details_array['details']['transaction']['num_of_payments'] )
				? $payment_details_array['details']['transaction']['num_of_payments'] : 1;

			if ( $num_of_payments > 1 ) {
				$params['num_of_payments']   = $num_of_payments;
				$params['transaction_terms'] = 'payments';
			}
		}

		$this->logger()->log_request( $order->get_id(), $params );
		$response = $this->adapter()->capture( $params );
		$this->logger()->log_response( $order->get_id(), $response );

		$response = (object) $response;

		if ( ! $response->status || 'succeeded' !== $response->status ) {
			return array(
				'success' => false,
				'msg'     => $response->terminal_message,
			);
		}

		$order->update_status( SmartPay::instance()->config()->get( 'success_capture_status' ) );
		$order->add_order_note(
			__( 'SmartPay payment successful. Capture transaction id: ', 'wc-smartpay' ) . $response->transaction_id
		);
		return array(
			'success' => true,
			'msg'     => '',
		);
	}

	/**
	 * Cancelling order
	 *
	 * @param WC_Order $order order object.
	 * @return bool
	 */
	public function cancel( $order ) {
		return $this->refund( $order, $order->get_total() );
	}

	/**
	 * Refunding
	 *
	 * @param WC_Order $order order object.
	 * @param float    $amount amount that needs to be refunded.
	 * @return bool
	 */
	public function refund( $order, $amount ) {
		$token = get_post_meta( $order->get_id(), 'cc_token', true );
		if ( ! $token ) {
			$order->add_order_note( __( 'Could not refund the order. No cc_token', 'wc-smartpay' ) );
			return false;
		}

		$request_data = array(
			'amount'           => $this->format_amount( $amount ),
			'currency'         => strtolower( $order->get_currency() ),
			'source'           => 'token',
			'token'            => $token,
			'transaction_type' => 'refund',
			'moreinfo1'        => $order->get_id(),
		);

		$this->logger()->log_request( $order->get_id(), $request_data );
		$response = $this->adapter()->refund( $request_data );
		$this->logger()->log_response( $order->get_id(), $response );

		if ( ! isset( $response['status'] ) || 'succeeded' !== $response['status'] ) {
            // phpcs:disable
			$message = isset( $response['terminal_message'] )
				? $response['terminal_message'] : var_export( array( 'response' => $response ), true );
            // phpcs:enable
			$order->add_order_note(
				sprintf(
					// translators: %1$s is replaced with "string".
					__( 'Could not refund the order. Reason: %1$s', 'wc-smartpay' ),
					esc_html( $message )
				)
			);
			return false;
		}

		$order->add_order_note(
			sprintf(
				// translators: %1$s is replaced with "string".
				__( 'Order refunded successfully. Transaction id: %1$s', 'wc-smartpay' ),
				esc_html( $response['transaction_id'] )
			)
		);
		return true;
	}

	/**
	 * Processing IPN request
	 *
	 * @return void
	 * @throws WC_Data_Exception Throwing exception.
	 */
	public function process_ipn() {
        $params = [
            'transaction',
            'transaction_id',
            'moreinfo1',
            'status',
            'terminal_message',
        ];

        $data = $this->request()->get_body( $params );
		$this->logger()->log( 'IPN', 'IPN', $this->serializer()->serialize( $data ) );
		try {
			$data_object = (object) $data;
		} catch ( Exception $e ) {
			wp_send_json(
				array(
					'status'  => 'failed',
					'message' => $e->getMessage(),
				),
				400
			);
		}

		if ( ! $data_object ) {
            // phpcs:disable
			$data_object = (object) $this->request()->get_query( $params );
            //phpcs:enable
		}

		if ( ! $data_object || ! $data_object->transaction_id || ! isset( $data_object->transaction['action_param'] ) ) {
			wp_send_json( array( 'status' => 'failed' ), 400 );
		}

		$transaction = (object) $this->adapter()
			->get_transaction( $data_object->transaction_id, $data_object->transaction['action_param'] );

		$order = wc_get_order( $transaction->moreinfo1 );

		if ( $order->is_paid() ) {
			wp_send_json( array( 'status' => 'failed' ), 400 );
		}

		if ( 'succeeded' !== $transaction->status ) {
			$order->set_status( SmartPay::instance()->config()->get( 'fail_order_status' ) );
			$order->add_order_note( $data_object->terminal_message );
			return;
		}

		$status_option_name = $this->resolve_order_status_option_name(
			$data_object->transaction['action_param']
		);
		$status             = SmartPay::instance()->config()->get( $status_option_name );
		$order->set_status( $status );
		$order->set_transaction_id( $data_object->transaction_id );
		$order->add_order_note(
			sprintf(
				// translators: %1$s is replaced with "string".
				// translators: %2$s is replaced with "string".
				__( 'Action param: %1$s. Status %2$s', 'wc-smartpay' ),
				$data_object->transaction['action_param'] ?? null,
				$status
			)
		);
		$order->add_order_note(
			__( 'Payment successful. Transaction Id: ', 'wc-smartpay' ) . $order->get_transaction_id()
		);
		$order->save();
		wp_send_json( array( 'status' => 'ok' ) );
	}

	/**
	 * Attempt to find suitable transaction for capture
	 *
	 * @param string $transaction_id smartpay transaction id.
	 * @param string $action_param action param type.
	 * @return object
	 * @throws Exception Throws exception.
	 */
	private function resolve_transaction_for_capture( $transaction_id, $action_param ) {
		// try to fetch authorization transaction.
		$response_object = (object) $this->adapter()->get_transaction( $transaction_id, $action_param ?? 'authorize' );

		if ( 'succeeded' !== $response_object->status ) {
			$response_object = (object) $this->adapter()->get_transaction( $transaction_id, 'check' );
		}

		return $response_object;
	}

	/**
	 * Process order status
	 *
	 * @param string $order_id woocommerce order id.
	 * @return void
	 * @throws Exception Throwing exception.
	 */
	public function process_capture( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order->get_transaction_id() ) {
			// charge by token (immediate capture).
			$this->capture( $order, $order->get_total() );
			return;
		}

		$this->logger()->log( $order->get_id(), 'FETCH', $order->get_transaction_id() );
		$response = $this->adapter()->get_transaction( $order->get_transaction_id(), 'capture' );
		$this->logger()->log_response( $order->get_id(), $response );
		$response_object = (object) $response;

		if ( 'succeeded' === $response_object->status ) {
			$order->add_order_note( __( 'Transaction was already captured.', 'wc-smartpay' ) );
			return;
		}

		$action_param = $order->get_meta( 'action_param' );
        // phpcs:disable
		if ( ! empty( $action_param ) && ! in_array( $action_param, array( 'authorize', 'check' ) ) ) {
            // phpcs:enable
			$order->add_order_note(
				__( 'Cannot capture transaction. Transaction action param: ', 'wc-smartpay' ) . $response_object->action_param
			);
			return;
		}

		// fallback logic starts. If action param is not set for an order we try to fetch authorized or check transaction.
		$response_object = $this->resolve_transaction_for_capture( $order->get_transaction_id(), $action_param );
		if ( 'succeeded' !== $response_object->status ) {
			$order->add_order_note(
				__( 'Cannot capture transaction. Transaction action param: ', 'wc-smartpay' ) . $response_object->action_param
			);
			return;
		}
		// fallback logic ends.

		// charge by transaction (late capture).
		$this->capture( $order, $order->get_total() );
	}


	/**
	 * Processing order
	 *
	 * @param int $order_id woocommerce order id.
	 * @return void
	 */
	public function process_order( $order_id ) {
		// exit if there was no POST data passed.
		$order = new WC_Order( $order_id );
		if ( $order->get_payment_method() !== SmartPay_Payment_Gateway::METHOD_CODE || $order->get_transaction_id() ) {
			return;
		}

        // phpcs:disable
        $params = [
            'tuid',
            'action_param',
        ];

		$response_data = ! empty( $_POST )
            ? $this->request()->get_post( $params ) : $this->request()->get_query( $params );
        // phpcs:enable
		SmartPay_Logger::instance()->log_response( $order_id, $response_data );
		$response_object = (object) $response_data;
		$response_data   = $this->adapter()->get_transaction(
			$response_object->tuid,
			$response_object->action_param
		);

		SmartPay_Logger::instance()->log_response( $order_id, $response_data );
		$response_object = (object) $response_data;

		if ( 'succeeded' === $response_object->status ) {
			$status = SmartPay::instance()->config()->get(
				$this->resolve_order_status_option_name( $response_object->action_param )
			);

			$order->update_status( $status );

			$order->add_order_note(
				sprintf(
					// translators: %1$s is replaced with "string".
					// translators: %2$s is replaced with "string".
					__( 'Action param: %1$s. Status %2$s', 'wc-smartpay' ),
					$response_object->action_param ?? null,
					$status
				)
			);
			$order->set_status( $status );
		} else {
			$order->update_status( SmartPay::instance()->config()->get( 'fail_order_status' ) );
			$order->add_order_note( 'SmartPay payment failed' );
		}

		if ( $response_object->token ) {
            // phpcs:disable
			list( $exp_year, $exp_month ) = str_split( $response_object->expirationDate, 2 );
            // phpcs:enable
			$token = new WC_Payment_Token_CC();
			$token->set_token( $response_object->token );
			$token->set_gateway_id( SmartPay::instance()->get_app_id() );
			$token->set_card_type( $this->card_type_resolver()->resolve_by_id( $response_object->brand ) );
            // phpcs:disable
			$token->set_last4( $response_object->card4digits );
            // phpcs:enable
			$token->set_expiry_month( $exp_month );
			$token->set_expiry_year( '20' . $exp_year );
			if ( $order->get_user_id() ) {
				$token->set_user_id( $order->get_user_id() );
			}

			$save_token_result = $token->save();
			$order->add_order_note( 'SmartPay saving token result: ' . ( $save_token_result ? 'True' : $save_token_result ) );
			$order->add_order_note( 'SmartPay saving token id: ' . $token->get_id() );

			// Set this token as the users new default token.
			WC_Payment_Tokens::set_users_default( $order->get_user_id(), $token->get_id() );
		}

		if ( $response_object->token ) {
			add_post_meta( $order->get_id(), 'cc_token', $response_object->token );
		}

		if ( $order->get_user_id() > 0 && $response_object->token ) {
			update_user_meta( $order->get_user_id(), 'cc_token', $response_object->token );
		}

		add_post_meta(
			$order->get_id(),
			'smartpay_details',
			wp_json_encode(
				array(
					'details' => array(
                        // phpcs:disable
						'cardname'          => base64_encode( $response_object->cardname ) ?? null,
						'card4digits'       => $response_object->card4digits ?? null,
						'expirationDate'    => $response_object->expirationDate ?? null,
                        // phpcs:enable
						'currency'          => $response_object->currency ?? null,
						'transaction_terms' => $response_object->transaction_terms,
						'transaction'       => array(
							'num_of_payments' => ( $response_object->num_of_payments ?? 1 ),
						),
					),
				)
			)
		);

		$order->add_order_note( 'SmartPay token: ' . $response_object->token );
        // phpcs:disable
		$transaction_info = array(
            // phpcs:disable
			__( 'Payment Info: ', 'wc-smartpay' ) => '',
			__( 'Credit Company', 'wc-smartpay' ) => $response_object->cardname ?? null,
			__( 'Last 4 Digits', 'wc-smartpay' )  => $response_object->card4digits ?? null,
			__( 'Exp. Date', 'wc-smartpay' )      => $response_object->expirationDate ?? null,
			__( 'Currency', 'wc-smartpay' )       => strtoupper( $response_object->currency ?? null ),
		);
        // phpcs:enable
		$number_of_payments = $response_object->transaction['num_of_payments'] ?? 1;
		if ( 1 !== (int) $number_of_payments ) {
			$transaction_info[ __( 'Payments', 'wc-smartpay' ) ]      = $number_of_payments;
			$transaction_info[ __( 'Payments type', 'wc-smartpay' ) ] = $response_object->transaction_terms;
			$transaction_info[ __( 'First Payment', 'wc-smartpay' ) ] = $response_object->amount / 100;
		}

		$transaction_info = array_map(
			function( $value, $key ) {
				return $key . ': ' . $value;
			},
			$transaction_info,
			array_keys( $transaction_info )
		);
		$order->add_meta_data( 'smartpay_payment_info', $transaction_info );
		$order->add_meta_data( 'action_param', $response_object->action_param );

		$order->set_transaction_id( $response_object->tuid );
		$order->save();

		$order->save_meta_data();
		$order->add_order_note( implode( '</br>', $transaction_info ), 1 );
		WC()->cart->empty_cart();
	}
}
