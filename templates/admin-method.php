<?php
/**
 * Admin method template file
 *
 * @package wc-smartpay
 * @var SmartPay_Admin_Method_Renderer $this
 * @var $order WC_Order
 */

?>
<?php defined( 'ABSPATH' ) || exit; ?>
<?php // @codingStandardsIgnoreStart ?>
<?php if ( $order->get_payment_method() !== SmartPay_Payment_Gateway::METHOD_CODE ) : ?>
	<?php return; ?>
<?php endif; ?>
<?php // @codingStandardsIgnoreEnd ?>
<div>
	<?php foreach ( (array) $order->get_meta( 'smartpay_payment_info' ) as $value ) : ?>
	<p>
		<?php echo esc_html( $value ); ?>
	</p>
	<?php endforeach; ?>
	<?php $token = $order->get_meta( 'cc_token', true ); ?>
	<?php if ( $token ) : ?>
	<p>
		<?php esc_html_e( 'Token: ', 'wc-smartpay' ); ?>
		<?php echo esc_html( $token ); ?>
	</p>
	<?php endif; ?>
</div>
