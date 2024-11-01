<?php
/**
 * Redirect template
 *
 * @package wc-smartpay
 * @var SmartPay_Iframe_Redirect_Renderer $this
 * @var WC_Order $order
 */

?>
<?php defined( 'ABSPATH' ) || exit; ?>
<script>
	if(window.top != window.self) {
		window.top.location.href = "<?php echo esc_url( $order->get_checkout_order_received_url() ); ?>";
	}
</script>
