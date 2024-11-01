<?php
/**
 * Iframe template
 *
 * @package wc-smartpay
 * @var SmartPay_Iframe_Renderer $this
 */

?>
<?php defined( 'ABSPATH' ) || exit; ?>
<div id="smartpayiframe-wrapper">
	<iframe src="<?php echo esc_url( $this->iframe_url ); ?>"
			name="smartpayiframe"
			id="smartpayiframe"
			width="<?php echo esc_attr( $this->iframe_width ); ?>"
			height="<?php echo esc_attr( $this->iframe_height ); ?>" frameBorder="0">

	</iframe>
</div>
