<?php
/**
 * Free Shipping Notice.
 *
 * @package WPDesk\FS\TableRate\FreeShipping
 */

namespace WPDesk\FS\TableRate\FreeShipping;

use FSVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WC_Cart;
use WC_Session;
use WP;

/**
 * Can display free shipping notice.
 */
class FreeShippingNotice implements Hookable {

	const FLEXIBLE_SHIPPING_FREE_SHIPPING_NOTICE = 'flexible_shipping_free_shipping_notice';
	const NOTICE_TYPE_SUCCESS = 'success';

	/**
	 * @var WC_Cart
	 */
	private $cart;

	/**
	 * @var WC_Session
	 */
	private $session;

	/**
	 * @var WP
	 */
	private $wp;

	/**
	 * FreeShippingNotice constructor.
	 *
	 * @param WC_Cart    $cart    .
	 * @param WC_Session $session .
	 * @param WP         $wp      .
	 */
	public function __construct( WC_Cart $cart, WC_Session $session, WP $wp ) {
		$this->wp      = $wp;
		$this->cart    = $cart;
		$this->session = $session;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'add_notice_free_shipping' ) );
	}

	/**
	 * Add notice to free shipping left.
	 */
	public function add_notice_free_shipping() {
		if ( ! $this->cart->needs_shipping() ) {
			return;
		}

		$amount = (float) $this->session->get( FreeShippingNoticeGenerator::SESSION_VARIABLE, 0.0 );

		if ( $amount <= 0.0 ) {
			return;
		}

		$message_text = $this->prepare_notice_text( $amount );

		if ( wc_has_notice( $message_text, self::NOTICE_TYPE_SUCCESS ) ) {
			return;
		}

		if ( $this->should_add_to_card() || $this->should_add_to_checkout() ) {
			wc_add_notice( $message_text, self::NOTICE_TYPE_SUCCESS, array( self::FLEXIBLE_SHIPPING_FREE_SHIPPING_NOTICE => 'yes' ) );

			remove_action( 'woocommerce_after_calculate_totals', array( $this, 'add_notice_free_shipping' ) );
		}
	}

	/**
	 * @return bool
	 */
	private function should_add_to_card() {
		return is_cart() && ! wp_doing_ajax();
	}

	/**
	 * @return bool
	 */
	private function should_add_to_checkout() {
		return is_checkout() && ( ! empty( $this->wp->request ) || wp_doing_ajax() );
	}

	/**
	 * @param float $amount .
	 *
	 * @return string
	 */
	private function prepare_notice_text( $amount ) {
		$notice_text = sprintf(
		// Translators: cart value and shop link.
			__( 'You only need %1$s more to get free shipping! %2$sContinue shopping%3$s', 'flexible-shipping' ),
			wc_price( $amount ),
			'<a class="button" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">',
			'</a>'
		);

		/**
		 * Notice text for Free Shipping.
		 *
		 * @param string $notice_text Notice text.
		 * @param float  $amount      Amount left to free shipping.
		 *
		 * @return string Message text.
		 */
		return apply_filters( 'flexible_shipping_free_shipping_notice_text', $notice_text, $amount );
	}
}
