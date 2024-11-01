<?php
/**
 * Plugin Name: SmartPay for WooCommerce
 * Plugin URI: https://smartpay.co.il/
 * Description: SmartPay payment plugin for WooCommerce
 * Version:     1.0.9
 * Author:      SmartPay
 * License:     GPLv2 or later
 * Text Domain: wc-smartpay
 * Domain Path: /languages
 *
 * @package /wc-smartpay
 */

defined( 'ABSPATH' ) || exit;

define( 'SMARTPAY_VERSION', '1.0.9' );

if ( ! defined( 'SMARTPAY_PLUGIN_FILE' ) ) {
	define( 'SMARTPAY_PLUGIN_FILE', __FILE__ );
}

spl_autoload_register( 'smartpay_autoloader' );

/**
 * SmartPay class autoloader
 *
 * @param string $class_name class name.
 */
function smartpay_autoloader( $class_name ) {
	if ( strpos( $class_name, 'SmartPay' ) !== false ) {
		$class_dir  = realpath( plugin_dir_path( __FILE__ ) )
			. DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-';
		$class_file = str_replace( array( '_' ), '-', strtolower( $class_name ) ) . '.php';
		require_once $class_dir . $class_file;
	}
}

register_activation_hook( __FILE__, array( 'SmartPay_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SmartPay_Deactivator', 'deactivate' ) );

SmartPay::instance()->run(
	array(
		'plugin_dir' => plugin_dir_path( __FILE__ ),
		'base_name'  => plugin_basename( __FILE__ ),
	)
);

// @codingStandardsIgnoreStart
/**
 * Global function to retrieve smartpay object
 *
 * @return SmartPay|null
 */
function smartpay() {
	return SmartPay::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce-smartpay'] = smartpay();

// @codingStandardsIgnoreEnd
