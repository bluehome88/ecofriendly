<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * WooCommerce Subscriptions Autoloader.
 *
 * @class WCS_Autoloader
 */
class WCS_Autoloader {

	/**
	 * The base path for autoloading.
	 *
	 * @var string
	 */
	protected $base_path = '';

	/**
	 * Whether to use the legacy API classes.
	 *
	 * @var bool
	 */
	protected $legacy_api = false;

	/**
	 * WCS_Autoloader constructor.
	 *
	 * @param string $base_path
	 */
	public function __construct( $base_path ) {
		$this->base_path = untrailingslashit( $base_path );
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		$this->unregister();
	}

	/**
	 * Register the autoloader.
	 *
	 * @author Jeremy Pry
	 */
	public function register() {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Unregister the autoloader.
	 */
	public function unregister() {
		spl_autoload_unregister( array( $this, 'autoload' ) );
	}

	/**
	 * Autoload a class.
	 *
	 * @author Jeremy Pry
	 *
	 * @param string $class The class name to autoload.
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );

		if ( ! $this->should_autoload( $class ) ) {
			return;
		}

		$full_path = $this->base_path . $this->get_relative_class_path( $class ) . $this->get_file_name( $class );
		if ( is_readable( $full_path ) ) {
			require_once( $full_path );
		}
	}

	/**
	 * Determine whether we should autoload a given class.
	 *
	 * @author Jeremy Pry
	 *
	 * @param string $class The class name.
	 *
	 * @return bool
	 */
	protected function should_autoload( $class ) {
		// We're not using namespaces, so if the class has namespace separators, skip.
		if ( false !== strpos( $class, '\\' ) ) {
			return false;
		}

		// There are some legacy classes without WCS or Subscription in the name.
		static $legacy = array(
			'wc_order_item_pending_switch'         => 1,
			'wc_report_retention_rate'             => 1,
			'wc_report_upcoming_recurring_revenue' => 1,
		);
		if ( isset( $legacy[ $class ] ) ) {
			return true;
		}

		return false !== strpos( $class, 'wcs_' ) || 0 === strpos( $class, 'wc_subscription' ) || ( false !== strpos( $class, 'wc_' ) && false !== strpos( $class, 'subscription' ) );
	}

	/**
	 * Convert the class name into an appropriate file name.
	 *
	 * @author Jeremy Pry
	 *
	 * @param string $class The class name.
	 *
	 * @return string The file name.
	 */
	protected function get_file_name( $class ) {
		$file_prefix = 'class-';

		if ( $this->is_class_abstract( $class ) ) {
			$file_prefix = 'abstract-';
		} elseif ( $this->is_class_interface( $class ) ) {
			$file_prefix = 'interface-';
		}

		return $file_prefix . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Determine if the class is one of our abstract classes.
	 *
	 * @author Jeremy Pry
	 *
	 * @param string $class The class name.
	 *
	 * @return bool
	 */
	protected function is_class_abstract( $class ) {
		static $abstracts = array(
			'wcs_background_repairer'      => true,
			'wcs_background_updater'       => true,
			'wcs_background_upgrader'      => true,
			'wcs_cache_manager'            => true,
			'wcs_debug_tool'               => true,
			'wcs_debug_tool_cache_updater' => true,
			'wcs_dynamic_hook_deprecator'  => true,
			'wcs_hook_deprecator'          => true,
			'wcs_retry_store'              => true,
			'wcs_scheduler'                => true,
			'wcs_sv_api_base'              => true,
			'wcs_customer_store'           => true,
			'wcs_related_order_store'      => true,
			'wcs_migrator'                 => true,
			'wcs_table_maker'              => true,
		);

		return isset( $abstracts[ $class ] );
	}

	/**
	 * Determine if the class is one of our class interfaces.
	 *
	 * @param string $class The class name.

	 * @return bool
	 */
	protected function is_class_interface( $class ) {
		static $interfaces = array(
			'wcs_cache_updater' => true,
		);

		return isset( $interfaces[ $class ] );
	}

	/**
	 * Determine if the class is one of our data stores.
	 *
	 * @param string $class The class name.

	 * @return bool
	 */
	protected function is_class_data_store( $class ) {
		static $data_stores = array(
			'wcs_related_order_store_cached_cpt'  => true,
			'wcs_related_order_store_cpt'         => true,
			'wcs_customer_store_cached_cpt'       => true,
			'wcs_customer_store_cpt'              => true,
			'wcs_product_variable_data_store_cpt' => true,
			'wcs_subscription_data_store_cpt'     => true,
		);

		return isset( $data_stores[ $class ] );
	}

	/**
	 * Get the relative path for the class location.
	 *
	 * This handles all of the special class locations and exceptions.
	 *
	 * @author Jeremy Pry
	 *
	 * @param string $class The class name.
	 *
	 * @return string The relative path (from the plugin root) to the class file.
	 */
	protected function get_relative_class_path( $class ) {
		$path     = '/includes';
		$is_admin = ( false !== strpos( $class, 'admin' ) );

		if ( $this->is_class_abstract( $class ) ) {
			if ( 'wcs_sv_api_base' === $class ) {
				$path .= '/gateways/paypal/includes/abstracts';
			} else {
				$path .= '/abstracts';
			}
		} elseif ( $this->is_class_interface( $class ) ) {
			$path .= '/interfaces';
		} elseif ( false !== strpos( $class, 'paypal' ) ) {
			$path .= '/gateways/paypal';
			if ( 'wcs_paypal' === $class ) {
				$path .= '';
			} elseif ( 'wcs_repair_suspended_paypal_subscriptions' === $class ) {
				// Deliberately avoid concatenation for this class, using the base path.
				$path = '/includes/upgrades';
			} elseif ( $is_admin ) {
				$path .= '/includes/admin';
			} elseif ( 'wc_paypal_standard_subscriptions' === $class ) {
				$path .= '/includes/deprecated';
			} else {
				$path .= '/includes';
			}
		} elseif ( 0 === strpos( $class, 'wcs_retry' ) && 'wcs_retry_manager' !== $class ) {
			$path .= '/payment-retry';
		} elseif ( $is_admin && 'wcs_change_payment_method_admin' !== $class ) {
			$path .= '/admin';
		} elseif ( false !== strpos( $class, 'meta_box' ) ) {
			$path .= '/admin/meta-boxes';
		} elseif ( false !== strpos( $class, 'wc_report' ) ) {
			$path .= '/admin/reports/deprecated';
		} elseif ( false !== strpos( $class, 'report' ) ) {
			$path .= '/admin/reports';
		} elseif ( false !== strpos( $class, 'debug_tool' ) ) {
			$path .= '/admin/debug-tools';
		} elseif ( false !== strpos( $class, 'rest' ) ) {
			$path .= $this->legacy_api ? '/api/legacy' : '/api';
		} elseif ( false !== strpos( $class, 'api' ) && 'wcs_api' !== $class ) {
			$path .= '/api/legacy';
		} elseif ( $this->is_class_data_store( $class ) ) {
			$path .= '/data-stores';
		} elseif ( false !== strpos( $class, 'deprecat' ) ) {
			$path .= '/deprecated';
		} elseif ( false !== strpos( $class, 'email' ) && 'wc_subscriptions_email' !== $class ) {
			$path .= '/emails';
		} elseif ( false !== strpos( $class, 'gateway' ) && 'wc_subscriptions_change_payment_gateway' !== $class ) {
			$path .= '/gateways';
		} elseif ( false !== strpos( $class, 'legacy' ) || 'wcs_array_property_post_meta_black_magic' === $class ) {
			$path .= '/legacy';
		} elseif ( false !== strpos( $class, 'privacy' ) ) {
			$path .= '/privacy';
		} elseif ( false !== strpos( $class, 'upgrade' ) || false !== strpos( $class, 'repair' ) ) {
			$path .= '/upgrades';
		} elseif ( false !== strpos( $class, 'early' ) ) {
			$path .= '/early-renewal';
		}

		return trailingslashit( $path );
	}

	/**
	 * Set whether the legacy API should be used.
	 *
	 * @author Jeremy Pry
	 *
	 * @param bool $use_legacy_api Whether to use the legacy API classes.
	 *
	 * @return $this
	 */
	public function use_legacy_api( $use_legacy_api ) {
		$this->legacy_api = (bool) $use_legacy_api;

		return $this;
	}
}