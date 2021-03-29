<?php
/**
 * Handle integration with All Products for WooCommerce Subscriptions Developed by SomewhereWarm.
 *
 * @see https://woocommerce.com/products/all-products-for-woocommerce-subscriptions/
 * @since 1.8.15
 * @package WCPBC/Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCPBC_WCS_ATT' ) ) :

	/**
	 * * WCPBC_WCS_ATT Class
	 */
	class WCPBC_WCS_ATT {

		/**
		 * Flag to control the subscription option wrapper.
		 *
		 * @var bool
		 */
		private static $wrapper_start = false;

		/**
		 * Hook actions and filters
		 */
		public static function init() {
			add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'adjust_product_price' ), -100 );
			if ( is_callable( array( 'WCPBC_Ajax_Geolocation', 'is_enabled' ) ) && WCPBC_Ajax_Geolocation::is_enabled() ) {
				add_action( 'woocommerce_before_template_part', array( __CLASS__, 'subscription_options_wrapper_start' ), 0, 4 );
				add_action( 'woocommerce_after_template_part', array( __CLASS__, 'subscription_options_wrapper_end' ), 0 );
				add_filter( 'wc_price_based_country_ajax_geolocation_wcsatt_content', array( __CLASS__, 'ajax_geolocation_wcsatt_content' ), 10, 2 );
			}
		}

		/**
		 * Set the product price to the pricing zone price for the product in the single page.
		 */
		public static function adjust_product_price() {
			global $product;
			WCPBC_Frontend_Pricing::adjust_product_price( $product );
		}

		/**
		 * Adds a wrapper for ajax geolocation.
		 *
		 * @since 2.1.13
		 * @param string $template_name Template name.
		 * @param string $template_path Template path. (default: '').
		 * @param string $located       Path. (default: '').
		 * @param array  $args          Arguments. (default: array).
		 */
		public static function subscription_options_wrapper_start( $template_name, $template_path, $located, $args ) {
			self::$wrapper_start = (
				'single-product/product-subscription-options.php' === $template_name
				&& false !== strpos( $located, 'woocommerce-all-products-for-subscriptions' )
				&& ! empty( $args['product'] )
				&& is_callable( array( $args['product'], 'is_type' ) )
				&& ! $args['product']->is_type( 'variation' )
			);

			if ( self::$wrapper_start ) {
				$product_id = $args['product']->get_id();

				self::$wrapper_start = true;
				printf( '<div class="wc-price-based-country-refresh-area" data-area="wcsatt" data-id="%s" data-options="%s">', esc_attr( $product_id ), esc_attr( wp_json_encode( array( 'product_id' => $product_id ) ) ) );
			}
		}

		/**
		 * Wrapper close.
		 *
		 * @since 2.1.13
		 * */
		public static function subscription_options_wrapper_end() {
			if ( self::$wrapper_start ) {
				echo '</div><!-- .wc-price-based-country-refresh-area -->';

				self::$wrapper_start = false;

				remove_action( 'woocommerce_before_template_part', array( __CLASS__, 'subscription_options_wrapper_start' ), 0, 4 );
				remove_action( 'woocommerce_after_template_part', array( __CLASS__, 'subscription_options_wrapper_end' ), 0 );
			}
		}

		/**
		 * Return the subscriptions options.
		 *
		 * @since 2.1.13
		 * @param string $content HTML content to return.
		 * @param array  $data Addon data.
		 * @return string
		 */
		public static function ajax_geolocation_wcsatt_content( $content, $data ) {

			$product_id = ! empty( $data['product_id'] ) ? absint( $data['product_id'] ) : false;

			if ( $product_id && is_callable( array( 'WCS_ATT_Display_Product', 'get_subscription_options_content' ) ) ) {
				$_product = wc_get_product( $product_id );
				if ( $_product ) {
					$content = WCS_ATT_Display_Product::get_subscription_options_content( $_product );
				}
			}

			return $content;
		}
	}

	add_action( 'wc_price_based_country_frontend_princing_init', array( 'WCPBC_WCS_ATT', 'init' ), 100 );

endif;
