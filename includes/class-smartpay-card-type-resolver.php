<?php
/**
 * Smartpay Plugin SmartPay_Card_Type_Resolver
 *
 * @package wc-smartpay
 */

/**
 * Smartpay Card Type Resolve
 */
class SmartPay_Card_Type_Resolver {
	/*
	 * Mastercard
	 */
	const TYPE_MASTERCARD = 'mastercard';

	/*
	 * Visa
	 */
	const TYPE_VISA = 'visa';

	/*
	 * Discover
	 */
	const TYPE_DISCOVER = 'discover';

	/*
	 * American Express
	 */
	const TYPE_AMEX = 'american express';

	/*
	 * Diners
	 */
	const TYPE_DINERS = 'diners';

	/*
	 * JCB
	 */
	const TYPE_JCB = 'jcb';

	/*
	 * Mastercard id
	 */
	const TYPE_MASTERCARD_ID = 1;

	/*
	 * Visa id
	 */
	const TYPE_VISA_ID = 2;

	/*
	 * Diners id
	 */
	const TYPE_DINERS_ID = 3;

	/*
	 * American Express id
	 */
	const TYPE_AMEX_ID = 4;

	/*
	 * JCB Id
	 */
	const TYPE_JCB_ID = 5;

	/*
	 * Discover ID
	 */
	const TYPE_DISCOVER_ID = 7;

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
	 * @return SmartPay_Card_Type_Resolver
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Retrieve mapping
	 *
	 * @return string[]
	 */
	protected function get_mapping() {
		return array(
			self::TYPE_MASTERCARD_ID => self::TYPE_MASTERCARD,
			self::TYPE_VISA_ID       => self::TYPE_VISA,
			self::TYPE_DISCOVER_ID   => self::TYPE_DISCOVER,
			self::TYPE_AMEX_ID       => self::TYPE_AMEX,
			self::TYPE_DINERS_ID     => self::TYPE_DINERS,
			self::TYPE_JCB_ID        => self::TYPE_JCB,
		);
	}

	/**
	 * Resolve card code by id
	 *
	 * @param integer $id card type id.
	 * @return string
	 */
	public function resolve_by_id( $id ) {
		$mapping = $this->get_mapping();
		return isset( $mapping[ $id ] ) ? $mapping[ $id ] : self::TYPE_JCB;
	}
}
