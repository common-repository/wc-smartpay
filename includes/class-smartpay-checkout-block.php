<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class SmartPay_Checkout_Block extends AbstractPaymentMethodType
{
    /** @var SmartPay_Payment_Gateway */
    private $gateway;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->name = Smartpay::instance()->get_app_id();
        $this->settings = get_option('woocommerce' . Smartpay::instance()->get_app_id() . '_settings');
        $this->gateway = new SmartPay_Payment_Gateway();
    }

    /**
     * Check if payment method is active
     *
     * @return bool
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Add script handle
     *
     * @return string[]
     */
    public function get_payment_method_script_handles()
    {
        $handle = $this->name . '-blocks-integration';
        wp_register_script(
            $handle,
            SmartPay::instance()->get_url('assets/js/checkout.js'),
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n'
            ),
            null,
            true
        );

        return array( $handle );
    }

    /**
     * Retrieve payment method data
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return array(
            'title' => $this->gateway->get_title(),
            'description' => $this->gateway->get_description(),
            'supports' => $this->gateway->supports
        );
    }
}
