<?php
/**
 * Smartpay Plugin Admin Menu
 *
 * @package wc-smartpay
 */

/**
 * SmartPay Admin Menu class
 */
class SmartPay_Admin_Menu {
	/**
	 * Define menu/submenu items
	 *
	 * @return void
	 */
	public function menu_items() {
		add_submenu_page(
			'woocommerce',
			__( 'SmartPay Logs', 'wc-smartpay' ),
			__( 'SmartPay Logs', 'wc-smartpay' ),
			'read',
			'smartpay-log-list',
			array( new SmartPay_Log_List(), 'display' ),
			100
		);
	}
}
