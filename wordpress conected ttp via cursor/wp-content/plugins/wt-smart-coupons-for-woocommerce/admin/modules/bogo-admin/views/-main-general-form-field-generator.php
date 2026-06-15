<?php
/**
 * BOGO main general settings form field generator
 *
 * @since 2.2.5
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$ds_obj = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );

if ( is_array( $args ) ) {
	foreach ( $args as $key => $value ) {
		$label       = isset( $value['label'] ) ? sanitize_text_field( $value['label'] ) : '';
		$field_type  = isset( $value['type'] ) ? $value['type'] : 'text';
		$option_name = isset( $value['option_name'] ) ? sanitize_text_field( $value['option_name'] ) : '';
		$vl          = Wbte_Smart_Coupon_Bogo_Common::get_general_settings_value( $option_name );
		$css_class   = isset( $value['css_class'] ) ? esc_attr( $value['css_class'] ) : '';
		$radio_type  = isset( $value['radio_type'] ) ? $value['radio_type'] : 'inline';

		$form_toggler_p_class  = '';
		$form_toggler_register = '';
		$form_toggler_child    = '';
		$data_attr             = array();
		if ( isset( $value['form_toggler'] ) ) {
			if ( 'parent' === $value['form_toggler']['type'] ) {
				$form_toggler_p_class                  = 'wt_sc_form_toggle';
				$form_toggler_register                 = ' wt_sc_form_toggle-target="' . esc_attr( $value['form_toggler']['target'] ) . '"';
				$data_attr['wt_sc_form_toggle-target'] = $value['form_toggler']['target'];
			} elseif ( 'child' === $value['form_toggler']['type'] ) {
				$form_toggler_child = ' wt_sc_form_toggle-id="' . esc_attr( $value['form_toggler']['id'] ) . '" wt_sc_form_toggle-val="' . esc_attr( $value['form_toggler']['val'] ) . '" ' . ( isset( $value['form_toggler']['check'] ) ? 'wt_sc_form_toggle-check="' . esc_attr( $value['form_toggler']['check'] ) . '"' : '' ) . ( isset( $value['form_toggler']['level'] ) ? ' wt_sc_form_toggle-level="' . esc_attr( $value['form_toggler']['level'] ) . '"' : '' );
			} else {
				$form_toggler_child                    = ' wt_sc_form_toggle-id="' . esc_attr( $value['form_toggler']['id'] ) . '" wt_sc_form_toggle-val="' . esc_attr( $value['form_toggler']['val'] ) . '" ' . ( isset( $value['form_toggler']['check'] ) ? 'wt_sc_form_toggle-check="' . esc_attr( $value['form_toggler']['check'] ) . '"' : '' ) . ( isset( $value['form_toggler']['level'] ) ? ' wt_sc_form_toggle-level="' . esc_attr( $value['form_toggler']['level'] ) . '"' : '' );
				$form_toggler_p_class                  = 'wt_sc_form_toggle';
				$form_toggler_register                 = ' wt_sc_form_toggle-target="' . esc_attr( $value['form_toggler']['target'] ) . '"';
				$data_attr['wt_sc_form_toggle-target'] = $value['form_toggler']['target'];
			}
		}

		echo '<div ' . wp_kses_post( $form_toggler_child ) . '>';
		if ( '' !== $label ) {
			echo '<label class="wbte_sc_bogo_input_title" for="' . esc_attr( $option_name ) . '">' . esc_html( $label ) . '</label>';
		}

		if ( 'radio' === $field_type ) {
			$radio_fields       = isset( $value['radio_fields'] ) ? $value['radio_fields'] : array();
			$radio_fields_array = array();

			foreach ( $radio_fields as $rad_vl => $rad_label ) {

				$radio_fields_array[] = array(
					'label'       => wp_kses_post( $rad_label ),
					'value'       => esc_attr( $rad_vl ),
					'is_checked'  => esc_attr( $rad_vl === $vl ),
					'is_disabled' => isset( $value['disabled_items'] ) && in_array( $rad_vl, $value['disabled_items'], true ),
				);
			}

			echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'radio-group ' . esc_attr( $radio_type ),
				array(
					'values' => array(
						'name'  => esc_attr( $option_name ),
						'items' => $radio_fields_array, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					),
					'class'  => array( esc_attr( $css_class ), esc_attr( $form_toggler_p_class ) ),
					'attr'   => $data_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				)
			);
		} elseif ( 'color' === $field_type ) {
			?>
			<div class="wbte_sc_color_picker_container wbte_sc_color_picker_field">
				<input class="wt_sc_color_picker_field"
					id="<?php echo esc_attr( $option_name ); ?>"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="<?php echo esc_attr( $vl ); ?>"
				>
				<span class="wbte_sc_color_picker_container_value_span"><?php echo esc_html( $vl ); ?></span>
			</div>
			<?php
		} else {
			?>
			<input type="text" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" class="wbte_sc_bogo_text_input" <?php echo wp_kses_post( $value['attr'] ); ?> value="<?php echo esc_attr( $vl ); ?>"> <!-- //NOSONAR -->
			<?php
			if ( in_array( $option_name, array_keys( Wbte_Smart_Coupon_Bogo_Admin::get_general_settings_placeholders() ), true ) ) {
				echo '<div class="wbte_sc_bogo_help_text">';
				Wbte_Smart_Coupon_Bogo_Admin::render_general_settings_placeholders( $option_name );
				echo '</div>';
			}
		}
		echo '</div>';
	}
}
