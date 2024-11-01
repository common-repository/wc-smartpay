<?php
/**
 * Smartpay Plugin SmartPay_Payment_Gateway
 *
 * @package wc-smartpay
 */

/**
 * SmartPay SmartPay_Payment_Gateway
 */
class SmartPay_Payment_Gateway extends WC_Payment_Gateway {
	/*
	 * Method code
	 */
	const METHOD_CODE = 'wc-smartpay';

	/**
	 * Payment processor
	 *
	 * @var SmartPay_Payment_Processor
	 */
	private $payment_processor;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->id                 = SmartPay::instance()->get_app_id();
		$this->has_fields         = false;
		$this->method_title       = __( 'WC SmartPay', 'wc-smartpay' );
		$this->method_description = __( 'Redirect customers to Smartpay hosted page.', 'wc-smartpay' );
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array(
			'pre-orders',
			'products',
			'refunds',			
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_admin',			
			'subscription_payment_method_change_customer',
		);

		do_action( 'smartpay_payment_gateway_init_after', $this );

		// This action hook saves the settings.
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);

		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'process_capture' ), 51 );

		add_action( 'woocommerce_api_smartpay_ipn', array( $this->payment_processor(), 'process_ipn' ) );
		add_action( 'woocommerce_thankyou_' . self::METHOD_CODE, array( $this->payment_processor(), 'process_order' ), 1, 1 );
		add_action( 'woocommerce_thankyou_' . self::METHOD_CODE, array( SmartPay_Iframe_Redirect_Renderer::class, 'render' ), 1, 1 );

		if ( class_exists( 'WC_Subscriptions_Order' )
			&& !defined( 'SMARTPAY_SUBSCRIPTION_HANDLER_DEFINED' )
		) {
			add_action(
				'woocommerce_scheduled_subscription_payment_' . $this->id,
				array( $this, 'process_subscription' ),
				10,
				2
			);
			define( 'SMARTPAY_SUBSCRIPTION_HANDLER_DEFINED', true );
		}
	}

	/**
	 * Plugin options.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                        => array(
				'title'   => __( 'Enable/Disable', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable SmartPay Payment', 'wc-smartpay' ),
				'default' => 'yes',
			),
			'title'                          => array(
				'title'       => __( 'Title', 'wc-smartpay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-smartpay' ),
				'default'     => __( 'SmartPay', 'wc-smartpay' ),
				'desc_tip'    => true,
			),
			'description'                    => array(
				'title'   => __( 'Description:', 'wc-smartpay' ),
				'type'    => 'textarea',
				'default' => 'Pay securely by Credit Card through SmartPay',
			),
			'sandbox'                        => array(
				'title'    => __( 'Sandbox', 'wc-smartpay' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enabled', 'wc-smartpay' ),
				'default'  => 'no',
				'desc_tip' => true,
			),
			'main_api_params'                => array(
				'title'       => __( 'SmartPay API Parameters', 'wc-smartpay' ),
				'type'        => 'title',
				'description' => '',
			),
			'api_key'                        => array(
				'title'    => __( 'API Key', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'api_secret'                     => array(
				'title'    => __( 'Secret Key', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'page_uuid'                      => array(
				'title'    => __( 'Page ID', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'num_of_payments_options'        => array(
				'title'       => __( 'Payment Steps', 'wc-smartpay' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => '1:1|999999999999:3',
			),

			// user interface settings.
			'user_interface'                 => array(
				'title'       => __( 'USER INTERFACE', 'wc-smartpay' ),
				'type'        => 'title',
				'description' => '',
			),
			'use_iframe'                     => array(
				'title'   => __( 'Use Iframe', 'wc-smartpay' ),
				'label'   => ' ',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'iframe_height'                  => array(
				'title'       => __( 'Iframe Height', 'wc-smartpay' ),
				'type'        => 'text',
				'description' => '',
				'default'     => __( '550px', 'wc-smartpay' ),
				'desc_tip'    => true,
			),
			'iframe_width'                   => array(
				'title'       => __( 'Iframe Width', 'wc-smartpay' ),
				'type'        => 'text',
				'description' => '',
				'default'     => __( '100%', 'wc-smartpay' ),
				'desc_tip'    => true,
			),
			'payment_action'                 => array(
				'title'       => __( 'Payment Action', 'wc-smartpay' ),
				'type'        => 'select',
				'default'     => '',
				'description' => __( 'Select status', 'wc-smartpay' ),
				'options'     => array(
					''                       => 'Payment Page Default',
					'charge_create_token'    => 'Charge Create Token',
					'check_create_token'     => 'Check Create Token',
					'authorize_create_token' => 'Authorize Create Token',
				),
			),
			'jump_to_top_enabled'            => array(
				'title'   => __( 'Jump to Top Enabled', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'language'                       => array(
				'title'       => __( 'Default Language', 'wc-smartpay' ),
				'type'        => 'select',
				'description' => __( 'Select language', 'wc-smartpay' ),
				'options'     => array(
					'he' => 'Hebrew',
					'en' => 'English',
				),
			),
			'language_switcher'              => array(
				'title'   => __( 'Enable Language Switcher', 'wc-smartpay' ),
				'label'   => ' ',
				'type'    => 'checkbox',
				'default' => 'no',
			),

			'ipn_enable'                     => array(
				'title'   => __( 'Use SmartPay IPN', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'After return site, Verify the transaction with SmartPay IPN', 'wc-smartpay' ),
				'default' => 'yes',
			),

			'currency'                       => array(
				'title'   => __( 'Currency', 'wc-smartpay' ),
				'label'   => ' ',
				'type'    => 'select',
				'options' => array(
					''    => __( 'Use WooCommerce settings', 'wc-smartpay' ),
					'ils' => 'ILS',
					'usd' => 'USD',
					'eur' => 'EUR',
					'gbp' => 'GBP',
					'cad' => 'CAD',
				),
			),
			'use_separate_terminal'          => array(
				'title'   => __( 'Use Separate Terminal', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'label'   => ' ',
				'default' => 'no',
			),

			'alternative_api_key'            => array(
				'title'    => __( 'Separate API Key', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'alternative_api_secret'         => array(
				'title'    => __( 'Separate Secret Key', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),

			'save_credit_cards'              => array(
				'title'   => __( 'Saved Credit Cards', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'label'   => ' ',
				'default' => 'yes',
			),
			'log_enabled'                    => array(
				'title'   => __( 'Log enabled', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'label'   => ' ',
				'default' => 'yes',
			),
			'success_capture_status'         => array(
				'title'   => __( 'Success Capture Status', 'wc-smartpay' ),
				'type'    => 'select',
				'default' => 'wc-processing',
				'options' => array(
					'wc-processing' => _x( 'Processing', 'wc-smartpay' ),
					'wc-on-hold'    => _x( 'On hold', 'wc-smartpay' ),
				),
			),
			'success_check_authorize_status' => array(
				'title'   => __( 'Success Check / Authorize Status', 'wc-smartpay' ),
				'type'    => 'select',
				'default' => 'wc-processing',
				'options' => array(
					'wc-pending'    => _x( 'Pending', 'wc-smartpay' ),
					'wc-on-hold'    => _x( 'On hold', 'woocommerce' ),
					'wc-processing' => _x( 'Processing', 'woocommerce' ),
				),

			),
			'fail_order_status'              => array(
				'title'   => __( 'Failed Order Status', 'wc-smartpay' ),
				'type'    => 'select',
				'options' => array(
					'wc-failed'  => _x( 'Failed', 'woocommerce' ),
					'wc-on-hold' => _x( 'On hold', 'woocommerce' ),
				),
				'default' => 'wc-failed',
			),
			'page_template'                  => array(
				'title' => __( 'Payment Page Template', 'wc-smartpay' ),
				'type'  => 'text',
			),

			// terms and conditions options.
			'terms_checkbox_enabled'         => array(
				'title'   => __( 'Terms & Conditions Enable', 'wc-smartpay' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),

			'terms_text'                     => array(
				'title'    => __( 'Terms & Conditions Text', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'terms_url_text'                 => array(
				'title'    => __( 'Terms & Conditions URL Text', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'terms_url'                      => array(
				'title'    => __( 'Terms & Conditions URL', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			'callback_url'                   => array(
				'title'    => __( 'Webhook URL', 'wc-smartpay' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Admin options
	 *
	 * @return void
	 */
	public function admin_options() {
		echo '<h3>' . esc_html_e( 'SmartPay', 'wc-smartpay' ) . '</h3>';
		echo '<p>';
        // phpcs:disable
		printf(
			esc_html( __( 'For more information %s', 'wc-smartpay' ) ),
			esc_url( 'http://www.smartpay.co.il' )
		);
        // phpcs:enable
		echo '</p>';
		echo '<table class="form-table">';
		// Generate the HTML For the settings form.
		$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 *  There are no payment fields for smartpay, but we want to show the description if set.
	 **/
	public function payment_fields() {
		if ( $this->description ) {
			echo esc_html( wpautop( wptexturize( $this->description ) ) );
		}
	}

	/**
	 * Retrieve payment processor class
	 *
	 * @return SmartPay_Payment_Processor
	 */
	private function payment_processor() {
		if ( ! $this->payment_processor ) {
			$this->payment_processor = new SmartPay_Payment_Processor( $this );
		}
		return $this->payment_processor;
	}

	/**
	 * Processing payment
	 *
	 * @param int $order_id order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( SmartPay::instance()->config()->is( 'use_iframe' ) ) {
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		}
		return $this->payment_processor()->execute( $order );
	}

	/**
	 * Processing subscription
	 *
	 * @param integer  $amount_to_charge amount to charge.
	 * @param WC_Order $renewal_order flag to identify renewal order.
	 * @return void
	 */
	public function process_subscription( $amount_to_charge, $renewal_order ) {

		SmartPay_Logger::instance()->log(
			$renewal_order->get_id(),
			'RENEWAL START',
			'Starting renewal creation'
		);

		$this->payment_processor()->capture( $renewal_order, $amount_to_charge );
	}

	/**
	 * Processing refund
	 *
	 * @param int    $order_id order id.
	 * @param null   $amount amount to be refunded.
	 * @param string $reason refund reason.
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->payment_processor()->refund( wc_get_order( $order_id ), $amount );
	}

	/**
	 * Processing order capture
	 *
	 * @param integer $order_id woocommerce order id.
	 * @return void
	 * @throws Exception Throwing exception.
	 */
	public function process_capture( $order_id ) {

		// @codingStandardsIgnoreStart
		if ( ! isset( $_POST['wc_order_action'] ) || 'smartpay_capture' !== $_POST['wc_order_action'] ) {
			return;
		}
		// @codingStandardsIgnoreEnd

		$this->payment_processor()->process_capture( $order_id );
	}
}
