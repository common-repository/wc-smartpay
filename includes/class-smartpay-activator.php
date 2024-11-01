<?php
/**
 * Fired during plugin activation
 *
 * @package    smartpay
 * @subpackage smartpay/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    smartpay
 * @subpackage smartpay/includes
 */
class SmartPay_Activator {

	/**
	 * Activate Plugin.
	 */
	public static function activate() {
		global $wpdb;
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$sql = "
			CREATE TABLE `{$wpdb->prefix}smartpay_log` (
			  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
			  `type` varchar(255) NOT NULL COMMENT 'Type',
			  `message` text NOT NULL COMMENT 'Message',
			  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'date',
			  `ref_id` varchar(255) DEFAULT NULL COMMENT 'Reference id for orders & etc.',
			  PRIMARY KEY (`id`),
			  KEY `IDX_SMARTPAY_LOG_REF_ID` (`ref_id`)
			) {$wpdb->get_charset_collate()} COMMENT='Smartpay Logs'";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( str_replace( PHP_EOL, '', $sql ) );
		add_option( 'smartpay_option_db_version', SMARTPAY_VERSION );
		update_option( 'smartpay_option', array() );
	}
}
