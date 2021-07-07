<?php
/**
 * @var string $settings_field_id
 * @var string $settings_field_name
 * @var string $settings_field_title
 * @var array  $rules_settings
 * @var array  $translations
 * @var array  $available_conditions
 * @var array  $cost_settings_fields
 * @var array  $additional_cost_fields
 * @var array  $special_action_fields
 * @var array  $rules_table_settings
 * @var array  $preconfigured_scenarios
 *
 * @package Flexible Shipping
 */

?>
<tr valign="top" class="flexible_shipping_method_rules">
	<th class="forminp" colspan="2">
		<label for="<?php echo esc_attr( $settings_field_name ); ?>"><?php echo wp_kses_post( $settings_field_title ); ?></label>

		<?php
		$fs_pro_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/flexible-shipping-pro-woocommerce/' : 'https://flexibleshipping.com/table-rate/';

		if ( ! in_array( 'flexible-shipping-pro/flexible-shipping-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ):
			?>
			<p><?php printf( __( 'Check %sFlexible Shipping PRO%s to add advanced rules based on shipment classes, product/item count or additional handling fees/insurance.', 'flexible-shipping' ), '<a href="' . $fs_pro_link . '?utm_campaign=flexible-shipping&utm_source=user-site&utm_medium=link&utm_term=flexible-shipping-pro&utm_content=fs-shippingzone-addnew-rules" target="_blank">', '</a>' ); ?></p>
		<?php endif; ?>

	</th>
</tr>
<tr valign="top" class="flexible-shipping-method-rules-settings">
	<td colspan="2" style="padding:0;">
		<?php do_action( 'flexible-shipping/method-rules-settings/table/before' ); ?>

		<div class="flexible-shipping-rules-instruction" style="margin-bottom: 15px;">
			<p><?php echo wp_kses_post( __( 'Please mind that the ranges you define must not overlap each other and make sure there are no gaps between them.', 'flexible-shipping' ) ); ?></p>
			<p><?php echo wp_kses_post( sprintf( __( '%1$sExample%2$s: If your rules are based on %1$sprice%2$s and the first range covers $0-$100, the next one should start from %1$s$100.01%2$s, not from %1$s$101%2$s, etc.', 'flexible-shipping' ), '<strong>', '</strong>' ) ); ?></p>
		</div>

		<script type="text/javascript">
			var <?php echo esc_attr( $settings_field_id ); ?> = <?php echo json_encode( array(
				'rules_settings'          => $rules_settings,
				'table_settings'          => $rules_table_settings,
				'translations'            => $translations,
				'available_conditions'    => $available_conditions,
				'cost_settings_fields'    => $cost_settings_fields,
				'special_action_fields'   => $special_action_fields,
				'additional_cost_fields'  => $additional_cost_fields,
				'preconfigured_scenarios' => $preconfigured_scenarios,
			), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>;

			document.addEventListener( "DOMContentLoaded", function ( event ) {
				document.querySelector( '#mainform button[name="save"]' ).addEventListener( "click", function ( event ) {
					if ( null === document.querySelector( '#<?php echo esc_attr( $settings_field_id ); ?>_control_field' ) ) {
						event.preventDefault();
						alert( '<?php echo esc_attr( __( 'Missing rules table - settings cannot be saved!', 'flexible-shipping' ) ); ?>' );
					}
				} );
			} );
		</script>

		<div class="flexible-shipping-rules-settings" id="<?php echo esc_attr( $settings_field_id ); ?>"
			 data-settings-field-name="<?php echo esc_attr( $settings_field_name ); ?>">
			<div class="notice notice-error inline">
				<?php echo wpautop( wp_kses_post( __( 'This is where the rules table should be displayed. If it\'s not, it is usually caused by the conflict with the other plugins you are currently using, JavaScript error or the caching issue. Clear your browser\'s cache or deactivate the plugins which may be interfering.', 'flexible-shipping' ) ) ); ?>
			</div>
		</div>

		<?php do_action( 'flexible-shipping/method-rules-settings/table/after' ); ?>
	</td>
</tr>
