<?php
/**
 * Class TaxableMethodSettings
 *
 * @package WPDesk\FS\TableRate\ShippingMethod
 */

namespace WPDesk\FS\TableRate\ShippingMethod;

/**
 * Settings fields with taxable option.
 */
class SingleMethodSettings implements MethodSettings {

	/**
	 * @param array $method_settings           .
	 * @param bool  $with_integration_settings Append integration settings.
	 *
	 * @return array
	 */
	public function get_settings_fields( array $method_settings, $with_integration_settings ) {
		$settings_fields = $this->append_taxable_settings(
			( new CommonMethodSettings() )->get_settings_fields( $method_settings, $with_integration_settings )
		);
		unset( $settings_fields['method_enabled'] );

		return $settings_fields;
	}

	/**
	 * @param array $settings_fields .
	 *
	 * @return array
	 */
	private function append_taxable_settings( array $settings_fields ) {
		$new_settings_fields = array();
		foreach ( $settings_fields as $key => $settings_field ) {
			$new_settings_fields[ $key ] = $settings_field;
			if ( CommonMethodSettings::METHOD_DESCRIPTION === $key ) {
				$new_settings_fields['tax_status'] = array(
					'title'    => __( 'Tax Status', 'flexible-shipping' ),
					'type'     => 'select',
					'default'  => 'taxable',
					'options'  => array(
						'taxable' => __( 'Taxable', 'flexible-shipping' ),
						'none'    => _x( 'None', 'Tax status', 'flexible-shipping' ),
					),
					'desc_tip' => __( 'If you select to apply the tax, the plugin will use the tax rates defined in the WooCommerce settings at <strong>WooCommerce → Settings → Tax</strong>.', 'flexible-shipping' ),
				);
			}
		}

		return $new_settings_fields;
	}
}
