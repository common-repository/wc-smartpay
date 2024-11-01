<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's
 * deactivation.
 *
 * @package    smartpay
 * @subpackage smartpay/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    wc-smartpay
 */
class SmartPay_Deactivator {

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		// Unregister plugin settings on deactivation.
		unregister_setting(
			'smartpay',
			'smartpay_option'
		);
	}
}
