<?php
/**
 * Class DefaultRulesSettings
 *
 * @package WPDesk\FS\TableRate
 */

namespace WPDesk\FS\TableRate;

/**
 * Can provide default settings for rules.
 */
class DefaultRulesSettings {

	/**
	 * @return array
	 */
	public function get_normalized_settings() {
		return apply_filters( 'flexible-shipping/shipping-method/default-rules-settings', $this->get_default_settings() );
	}

	/**
	 * @return array
	 */
	private function get_default_settings() {
		return array();
	}
}
