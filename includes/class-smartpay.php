<?php
/**
 * Smartpay Plugin Smartpay god-object
 *
 * @package wc-smartpay
 */

/**
 * Smartpay god-object
 */
final class SmartPay {

	/**
	 * Version.
	 *
	 * @var string
	 */
	private $version = '1.0.9';

	/**
	 * The single instance of the class.
	 *
	 * @var $instance
	 */
	protected static $instance = null;

	/**
	 * Application id
	 *
	 * @var string
	 */
	protected $app_id = 'wc-smartpay';

	/**
	 * Plugin directory
	 *
	 * @var string $plugin_dir
	 */
	protected $plugin_dir;

	/**
	 * Base name
	 *
	 * @var string $basename
	 */
	protected $basename;

	/**
	 * Config class
	 *
	 * @var SmartPay_Config $config
	 */
	private $config;

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
	 * Initializing class
	 */
	private function __construct() {
        add_action( 'woocommerce_blocks_loaded', array( $this, 'enable_blocks_support') );
		add_action( 'woocommerce_init', array( $this, 'init_hooks' ) );
		add_filter( 'auto_update_plugin', array( $this, 'enable_auto_updates' ), 10, 2 );
		add_filter( 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
	}

	/**
	 * Retrieving instance of the class
	 *
	 * @return SmartPay|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Running logic
	 *
	 * @param array $config configurations.
	 */
	public function run( $config = array() ) {
		$this->plugin_dir = isset( $config['plugin_dir'] ) ? $config['plugin_dir'] : null;
		$this->basename   = isset( $config['base_name'] ) ? $config['base_name'] : null;
	}

	/**
	 * Retrieving app id
	 *
	 * @return string
	 */
	public function get_app_id() {
		return $this->app_id;
	}

	/**
	 * Retrieving current version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieving path inside plugins folder
	 *
	 * @param array|string $relative_path relative file path.
	 * @return string
	 */
	public function get_file_path( $relative_path ) {
		if ( is_array( $relative_path ) ) {
			$relative_path = implode( DIRECTORY_SEPARATOR, $relative_path );
		}
		return $this->get_plugin_dir() . DIRECTORY_SEPARATOR . $relative_path;
	}

	/**
	 * Retrieving plugin directory
	 *
	 * @return mixed
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Retrieving plugin url
	 *
	 * @param string $path path.
	 * @return string
	 */
	public function get_url( $path ) {
		return plugins_url( $path, $this->basename );
	}

	/**
	 * Registering hooks
	 */
	public function init_hooks() {

		add_action( 'smartpay_payment_gateway_init_after', array( $this, 'init_config' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_payment_method' ) );
		add_action( 'woocommerce_receipt_' . $this->get_app_id(), array( new SmartPay_Iframe_Renderer(), 'render' ) );
		add_filter( 'billi_can_sync_invoice', array( SmartPay_Billi_Observer::instance(), 'can_sync_invoice' ), 10, 2 );
		add_action( 'wp_footer', array( new SmartPay_Iframe_Return_Type_Top_Footer_Renderer(), 'render' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( new SmartPay_Admin_Menu(), 'menu_items' ), 100 );
			add_action(
				'woocommerce_admin_order_data_after_billing_address',
				array(
					new SmartPay_Admin_Method_Renderer(),
					'render',
				)
			);
			add_filter( 'woocommerce_order_actions', array( $this, 'add_capture_action' ), 10, 2 );
		}
	}

    /**
     * @return void
     */
    public function enable_blocks_support()
    {
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( $payment_method_registry ) {
                    $payment_method_registry->register( new SmartPay_Checkout_Block() );
                }
            );
        }
    }

	/**
	 * Enable auto updates
	 *
	 * @param bool $value
	 * @param object $item
	 * @return bool
	 */
	public function enable_auto_updates( $value, $item ) {
		return $item->slug === $this->get_app_id() ? true : $value;
	}

	/**
	 * Check for plugin updates
	 *
	 * @param object $transient
	 * @return object
	 */
	public function check_for_update( $transient ) {

		if (empty($transient->checked)) {
			return $transient;
		}

		$response = wp_remote_get( sprintf(
			'https://api.wordpress.org/plugins/info/1.0/%s.json',
			$this->get_app_id()
		));

		if (is_wp_error($response)) {
			return $transient;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( $data && version_compare( $data->version, $this->get_version(), '>' ) ) {
			$transient->response[$this->get_plugin_dir()] = (object) array(
				'new_version' => $data->version,
				'package' => $data->download_link,
				'url' => $data->homepage,
				'slug' => $this->get_app_id()
			);
		}

		return $transient;
	}

	/**
	 * Retrieving payment gateway
	 *
	 * @return SmartPay_Payment_Gateway|null
	 */
	public function get_payment_gateway() {
		$payment_gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		foreach ( $payment_gateways  as $payment_gateway ) {
			if ( $payment_gateway instanceof SmartPay_Payment_Gateway ) {
				return $payment_gateway;
			}
		}
		return null;
	}

	/**
	 * Initializing config
	 *
	 * @param WC_Payment_Gateway $payment_gateway payment gateway class name.
	 */
	public function init_config( $payment_gateway ) {
		// no need to re-initialize configuration.
		if ( $this->config ) {
			return;
		}

		if ( $payment_gateway instanceof SmartPay_Payment_Gateway ) {
			$this->config				= new SmartPay_Config( $payment_gateway->settings );
			$payment_gateway->title	  = $this->config()->get( 'title' );
			$payment_gateway->has_fields = $this->config()->is( 'embed_enabled' );
		}
	}

	/**
	 * Retrieving configurations
	 *
	 * @return SmartPay_Config
	 */
	public function config() {
		if ( ! $this->config ) {

			// @codingStandardsIgnoreStart
			_doing_it_wrong(
				__FUNCTION__,
				__( 'Config is not initialized yet.', 'wc-smartpay' ),
				$this->version
			);
			// @codingStandardsIgnoreEnd
		}

		return $this->config;
	}

	/**
	 * Registering new payment method
	 *
	 * @param array $methods payment methods list.
	 * @return mixed
	 */
	public function register_payment_method( $methods ) {
		array_push( $methods, new SmartPay_Payment_Gateway() );
		return $methods;
	}

	/**
	 * Adding capture action
	 *
	 * @param array	$actions actions array.
	 * @param WC_Order $order woocommerce order object.
	 * @return array
	 */
	public function add_capture_action( $actions, $order ) {

		if ( $order->get_payment_method() !== SmartPay_Payment_Gateway::METHOD_CODE
			|| $order->get_meta( 'action_param' ) === 'capture'
		) {
			return $actions;
		}

		$actions['smartpay_capture'] = __( 'Smartpay Capture', 'wc-smartpay' );
		return $actions;
	}
}
