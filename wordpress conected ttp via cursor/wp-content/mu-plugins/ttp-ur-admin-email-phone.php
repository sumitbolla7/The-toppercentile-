<?php
/**
 * Plugin Name: TTP UR Admin Email Phone Tag
 * Description: Adds {{phone}} and {{mobile}} smart tags to User Registration emails (admin + user).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve mobile number from UR field meta or billing_phone.
 *
 * @param int   $user_id    User ID.
 * @param array $name_value Parsed form smart-tag values.
 * @return string
 */
function ttp_ur_resolve_phone_for_email( $user_id, $name_value = array() ) {
	$user_id = (int) $user_id;

	if ( function_exists( 'ttp_phone_get_user_phone' ) && $user_id > 0 ) {
		$phone = ttp_phone_get_user_phone( $user_id );
		if ( '' !== $phone ) {
			return $phone;
		}
	}

	if ( is_array( $name_value ) ) {
		foreach ( $name_value as $key => $val ) {
			if ( ! is_scalar( $val ) || '' === (string) $val ) {
				continue;
			}
			$key_l = strtolower( (string) $key );
			if ( preg_match( '/phone|mobile|whatsapp|number_box/', $key_l ) ) {
				return sanitize_text_field( (string) $val );
			}
		}
	}

	if ( $user_id > 0 ) {
		$billing = get_user_meta( $user_id, 'billing_phone', true );
		if ( is_scalar( $billing ) && '' !== trim( (string) $billing ) ) {
			return sanitize_text_field( (string) $billing );
		}
	}

	return '';
}

add_filter(
	'user_registration_authenticated_smart_tags',
	static function ( $tags ) {
		$tags['{{phone}}']  = __( 'Mobile / Phone', 'ttp-ur' );
		$tags['{{mobile}}'] = __( 'Mobile Number', 'ttp-ur' );

		return $tags;
	}
);

add_filter(
	'user_registration_process_smart_tag',
	static function ( $name_value, $valid_form_data, $form_id, $user_id ) {
		unset( $valid_form_data, $form_id );

		if ( ! is_array( $name_value ) ) {
			$name_value = array();
		}

		$phone = ttp_ur_resolve_phone_for_email( (int) $user_id, $name_value );
		if ( '' !== $phone ) {
			$name_value['phone']  = $phone;
			$name_value['mobile'] = $phone;
		}

		return $name_value;
	},
	10,
	4
);

add_filter(
	'user_registration_add_smart_tags',
	static function ( $defaults, $email ) {
		$user = is_email( $email ) ? get_user_by( 'email', $email ) : false;
		if ( ! $user instanceof WP_User ) {
			return $defaults;
		}

		$phone = ttp_ur_resolve_phone_for_email( (int) $user->ID );
		if ( '' === $phone ) {
			return $defaults;
		}

		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		$defaults['phone']  = $phone;
		$defaults['mobile'] = $phone;

		return $defaults;
	},
	10,
	2
);
