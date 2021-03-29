<?php
/**
 * Mini cart template
 *
 * @author  YITH
 * @package YITH WooCommerce Added to Cart Popup
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WACP' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div id="yith-wacp-mini-cart" class="<?php echo empty( $items ) ? 'empty' : ''; ?>">
	<?php
	if ( $show_counter ) :
		?>
		<div class="yith-wacp-mini-cart-count"><?php echo esc_html( $items ); ?></div>
		<?php
	endif;
	?>
	<div class="yith-wacp-mini-cart-icon">
		<?php if( ! empty( $icon ) ) : ?>
			<img src="<?php echo esc_url( $icon ); ?>" width="35px" height="35px">
		<?php endif; ?>
	</div>
</div>
