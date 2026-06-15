<?php
/**
 * Plugin Name: TTP Phone Checkout Sync
 * Description: Saves phone from User Registration to WooCommerce billing_phone and auto-fills checkout. Removes order notes section.
 * Version: 1.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UR field names forced as mobile (filterable). Example: number_box_1780417779.
 *
 * @return string[]
 */
function ttp_phone_ur_forced_field_names() {
	return array_map(
		'sanitize_key',
		(array) apply_filters(
			'ttp_phone_ur_forced_field_names',
			array( 'number_box_1780417779' )
		)
	);
}

/**
 * Whether a UR form field should be treated as mobile/WhatsApp.
 *
 * @param string $field_key  UR field_key (phone, number, …).
 * @param string $field_name UR field_name (e.g. number_box_1780417779).
 * @param string $label      Field label from the form builder.
 * @return bool
 */
function ttp_phone_is_ur_mobile_field( $field_key, $field_name, $label = '' ) {
	$field_key  = strtolower( sanitize_text_field( (string) $field_key ) );
	$field_name = strtolower( sanitize_text_field( (string) $field_name ) );
	$label      = strtolower( sanitize_text_field( (string) $label ) );

	if ( in_array( $field_key, array( 'phone', 'billing_phone', 'smart_phone' ), true ) ) {
		return true;
	}

	if ( in_array( $field_name, ttp_phone_ur_forced_field_names(), true ) ) {
		return true;
	}

	if ( preg_match( '/(?:phone|mobile|tel|whatsapp)/i', $field_name ) ) {
		return true;
	}

	if ( $label !== '' && preg_match( '/(?:phone|mobile|tel|whatsapp)/i', $label ) ) {
		return true;
	}

	// UR "Number" field renamed to Mobile/WhatsApp (field_name number_box_*).
	if ( 'number' === $field_key && preg_match( '/^number_box_\d+$/', $field_name ) ) {
		if ( $label !== '' && preg_match( '/(?:phone|mobile|tel|whatsapp)/i', $label ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'ttp_phone_is_ur_mobile_field', false, $field_key, $field_name, $label );
}

/**
 * Normalize UR phone values (plain text, smart-phone hidden JSON, arrays).
 *
 * @param mixed $value Raw field value.
 * @return string
 */
function ttp_phone_normalize_value( $value ) {
	if ( is_object( $value ) ) {
		$value = (array) $value;
	}

	if ( is_array( $value ) ) {
		$keys = array( 'phone', 'national_number', 'mobile', 'number', 'full_number', 'value', 'e164' );
		foreach ( $keys as $key ) {
			if ( ! empty( $value[ $key ] ) && is_scalar( $value[ $key ] ) ) {
				$normalized = ttp_phone_normalize_value( (string) $value[ $key ] );
				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}
		return '';
	}

	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return '';
	}

	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( ( '{' === $value[0] || '[' === $value[0] ) ) {
		$decoded = json_decode( $value, true );
		if ( is_array( $decoded ) ) {
			$from_json = ttp_phone_normalize_value( $decoded );
			if ( '' !== $from_json ) {
				return $from_json;
			}
		}
	}

	if ( preg_match( '/\d{6,}/', $value ) ) {
		$digits = preg_replace( '/[^\d+]/', '', $value );
		return is_string( $digits ) ? $digits : '';
	}

	return '';
}

/**
 * Extract phone from User Registration form submission payload.
 *
 * @param array $valid_form_data UR form data objects.
 * @return string
 */
function ttp_phone_extract_from_ur_form( $valid_form_data ) {
	if ( ! is_array( $valid_form_data ) ) {
		return '';
	}

	foreach ( $valid_form_data as $data ) {
		if ( ! is_object( $data ) ) {
			continue;
		}

		$field_key  = '';
		$field_name = '';
		$label      = '';
		$value      = '';

		if ( isset( $data->extra_params['field_key'] ) ) {
			$field_key = strtolower( (string) $data->extra_params['field_key'] );
		}
		if ( isset( $data->field_name ) ) {
			$field_name = strtolower( (string) $data->field_name );
		}
		if ( isset( $data->extra_params['label'] ) ) {
			$label = (string) $data->extra_params['label'];
		}
		if ( isset( $data->value ) ) {
			$value = ttp_phone_normalize_value( $data->value );
		}

		if ( '' === $value ) {
			continue;
		}

		if ( ttp_phone_is_ur_mobile_field( $field_key, $field_name, $label ) ) {
			return $value;
		}
	}

	return '';
}

/**
 * Read stored phone for a user from known meta keys.
 *
 * @param int $user_id User ID.
 * @return string
 */
function ttp_phone_get_user_phone( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id < 1 ) {
		return '';
	}

	// UR "Phone Number" field is usually field_name phone_number → meta user_registration_phone_number.
	$keys = array(
		'billing_phone',
		'ttp_mobile',
		'user_registration_phone_number',
		'user_registration_phone',
		'user_registration_billing_phone',
		'user_registration_mobile',
		'user_registration_mobile_number',
		'phone_number',
		'mobile_number',
		'user_registration_number_box_1780417779',
	);

	$keys = array_merge( $keys, (array) apply_filters( 'ttp_phone_ur_meta_keys', array() ) );
	foreach ( ttp_phone_ur_forced_field_names() as $forced_name ) {
		$keys[] = 'user_registration_' . $forced_name;
		$keys[] = $forced_name;
	}

	foreach ( $keys as $key ) {
		$value = ttp_phone_normalize_value( get_user_meta( $user_id, $key, true ) );
		if ( '' !== $value ) {
			return $value;
		}
	}

	global $wpdb;
	$like_keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->usermeta}
			WHERE user_id = %d
			AND (
				meta_key LIKE %s
				OR meta_key LIKE %s
				OR meta_key LIKE %s
				OR meta_key LIKE %s
				OR meta_key LIKE %s
			)
			ORDER BY meta_key ASC",
			$user_id,
			'user_registration_%phone%',
			'%phone%',
			'%mobile%',
			'%whatsapp%',
			'user_registration_number_box_%'
		)
	);

	if ( is_array( $like_keys ) ) {
		foreach ( $like_keys as $meta_key ) {
			$value = ttp_phone_normalize_value( get_user_meta( $user_id, $meta_key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}
	}

	return '';
}

/**
 * Persist phone to WooCommerce + TTP meta keys.
 *
 * @param int    $user_id User ID.
 * @param string $phone   Phone number.
 * @return void
 */
function ttp_phone_save_for_user( $user_id, $phone ) {
	$user_id = absint( $user_id );
	$phone   = preg_replace( '/\s+/', '', sanitize_text_field( (string) $phone ) );

	if ( $user_id < 1 || '' === $phone ) {
		return;
	}

	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'ttp_mobile', $phone );

	foreach ( ttp_phone_ur_forced_field_names() as $forced_name ) {
		update_user_meta( $user_id, 'user_registration_' . $forced_name, $phone );
	}
	// Do not call WC()->customer->save() during registration — it can break UR AJAX responses.
}

/**
 * Sync UR registration phone into WooCommerce billing phone.
 *
 * @param array $valid_form_data Form data.
 * @param int   $form_id         Form ID.
 * @param int   $user_id         User ID.
 * @return void
 */
function ttp_phone_on_ur_register( $valid_form_data, $form_id, $user_id ) {
	unset( $form_id );
	$phone = ttp_phone_extract_from_ur_form( $valid_form_data );
	if ( '' !== $phone ) {
		ttp_phone_save_for_user( $user_id, $phone );
	}
}
/**
 * Turn a registration "name" value into a real name (reject raw emails).
 *
 * @param string $value Raw field value.
 * @return string
 */
function ttp_sanitize_person_name_value( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( is_email( $value ) ) {
		$local = strstr( $value, '@', true );
		if ( is_string( $local ) && '' !== $local ) {
			$local = str_replace( array( '.', '_', '-' ), ' ', $local );
			$local = preg_replace( '/\s+/', ' ', trim( $local ) );
			return sanitize_text_field( ucwords( $local ) );
		}
		return '';
	}
	return sanitize_text_field( $value );
}

/**
 * TCY register API requires full_name with letters and spaces only (no digits/symbols).
 *
 * @param string $value Raw or partially cleaned name.
 * @return string
 */
function ttp_sanitize_tcy_full_name( $value ) {
	if ( function_exists( 'ttp_sanitize_person_name_value' ) ) {
		$value = ttp_sanitize_person_name_value( $value );
	} else {
		$value = trim( (string) $value );
	}
	$value = preg_replace( '/[^A-Za-z\s]/', '', (string) $value );
	$value = preg_replace( '/\s+/', ' ', trim( $value ) );
	if ( '' === $value ) {
		return 'Student';
	}
	return $value;
}

/**
 * TCY client_id must be numeric digits only.
 *
 * @param string $raw Raw option value.
 * @return string
 */
function ttp_normalize_tcy_client_id( $raw ) {
	$digits = preg_replace( '/\D+/', '', (string) $raw );
	return '' !== $digits ? $digits : sanitize_text_field( (string) $raw );
}

/**
 * Extract first/last name from UR form submission.
 *
 * @param array $valid_form_data UR form data objects.
 * @return array{first_name: string, last_name: string}
 */
function ttp_ur_extract_name_from_form( $valid_form_data ) {
	$first = '';
	$last  = '';
	$full  = '';

	if ( ! is_array( $valid_form_data ) ) {
		return array( 'first_name' => '', 'last_name' => '' );
	}

	foreach ( $valid_form_data as $data ) {
		if ( ! is_object( $data ) ) {
			continue;
		}

		$field_key  = isset( $data->extra_params['field_key'] ) ? strtolower( (string) $data->extra_params['field_key'] ) : '';
		$field_name = isset( $data->field_name ) ? strtolower( (string) $data->field_name ) : '';
		$label      = isset( $data->extra_params['label'] ) ? strtolower( (string) $data->extra_params['label'] ) : '';
		$value      = isset( $data->value ) && is_scalar( $data->value ) ? trim( (string) $data->value ) : '';

		if ( '' === $value ) {
			continue;
		}
		$value = ttp_sanitize_person_name_value( $value );
		if ( '' === $value ) {
			continue;
		}

		if ( in_array( $field_key, array( 'first_name', 'last_name', 'nickname', 'display_name' ), true ) ) {
			if ( 'first_name' === $field_key ) {
				$first = $value;
			} elseif ( 'last_name' === $field_key ) {
				$last = $value;
			} elseif ( 'nickname' === $field_key && '' === $first ) {
				$first = $value;
			} elseif ( 'display_name' === $field_key ) {
				$full = $value;
			}
			continue;
		}

		if ( in_array( $field_name, array( 'first_name', 'last_name', 'nickname', 'display_name' ), true ) ) {
			if ( 'first_name' === $field_name ) {
				$first = $value;
			} elseif ( 'last_name' === $field_name ) {
				$last = $value;
			} elseif ( 'nickname' === $field_name && '' === $first ) {
				$first = $value;
			} elseif ( 'display_name' === $field_name ) {
				$full = $value;
			}
			continue;
		}

		if ( preg_match( '/full\s*name|your\s*name|^name$/i', $label ) || preg_match( '/^full_?name$/i', $field_name ) ) {
			$full = $value;
		}
	}

	if ( '' === $first && '' === $last && '' !== $full ) {
		$parts = preg_split( '/\s+/', $full, 2 );
		$first = isset( $parts[0] ) ? trim( (string) $parts[0] ) : '';
		$last  = isset( $parts[1] ) ? trim( (string) $parts[1] ) : '';
	}

	return array(
		'first_name' => sanitize_text_field( $first ),
		'last_name'  => sanitize_text_field( $last ),
	);
}

/**
 * Name parts for a WP user (UR stores first_name without user_registration_ prefix).
 *
 * @param int $user_id User ID.
 * @return array{first_name: string, last_name: string}
 */
function ttp_user_get_name_parts( $user_id ) {
	$user_id = absint( $user_id );
	$first   = '';
	$last    = '';

	if ( $user_id < 1 ) {
		return array( 'first_name' => '', 'last_name' => '' );
	}

	$user = get_user_by( 'id', $user_id );
	if ( $user instanceof WP_User ) {
		$first = trim( (string) $user->first_name );
		$last  = trim( (string) $user->last_name );
		if ( '' === $first && '' !== trim( (string) $user->display_name ) && ! is_email( $user->display_name ) ) {
			$parts = preg_split( '/\s+/', trim( (string) $user->display_name ), 2 );
			$first = isset( $parts[0] ) ? trim( (string) $parts[0] ) : '';
			$last  = isset( $parts[1] ) ? trim( (string) $parts[1] ) : '';
		}
		if ( '' === $first && '' !== trim( (string) $user->user_login ) && ! is_email( $user->user_login ) ) {
			$first = sanitize_text_field( (string) $user->user_login );
		}
	}

	$meta_keys = array(
		'first_name',
		'last_name',
		'user_registration_first_name',
		'user_registration_last_name',
		'nickname',
		'billing_first_name',
		'billing_last_name',
	);

	foreach ( $meta_keys as $key ) {
		$val = trim( (string) get_user_meta( $user_id, $key, true ) );
		if ( '' === $val ) {
			continue;
		}
		if ( false !== strpos( $key, 'last' ) ) {
			if ( '' === $last ) {
				$last = $val;
			}
		} elseif ( '' === $first ) {
			$first = $val;
		}
	}

	if ( '' === $first && '' === $last && $user instanceof WP_User ) {
		$email = sanitize_email( (string) $user->user_email );
		if ( $email !== '' && is_email( $email ) ) {
			$local = strstr( $email, '@', true );
			if ( is_string( $local ) && '' !== $local ) {
				$local = str_replace( array( '.', '_', '-' ), ' ', $local );
				$local = preg_replace( '/\s+/', ' ', trim( $local ) );
				$first = sanitize_text_field( ucwords( $local ) );
			}
		}
	}

	return array(
		'first_name' => sanitize_text_field( $first ),
		'last_name'  => sanitize_text_field( $last ),
	);
}

/**
 * Save UR name fields onto the user record for CRM / WooCommerce.
 *
 * @param int   $user_id         User ID.
 * @param array $valid_form_data UR form data.
 * @return void
 */
function ttp_ur_save_name_for_user( $user_id, $valid_form_data ) {
	$user_id = absint( $user_id );
	if ( $user_id < 1 || ! is_array( $valid_form_data ) ) {
		return;
	}

	$name = ttp_ur_extract_name_from_form( $valid_form_data );
	if ( '' === $name['first_name'] && '' === $name['last_name'] ) {
		$name = ttp_user_get_name_parts( $user_id );
	}
	if ( '' === $name['first_name'] && '' === $name['last_name'] ) {
		return;
	}

	$update = array( 'ID' => $user_id );
	$name['first_name'] = ttp_sanitize_person_name_value( $name['first_name'] );
	$name['last_name']  = ttp_sanitize_person_name_value( $name['last_name'] );
	if ( '' === $name['first_name'] && '' === $name['last_name'] ) {
		return;
	}

	if ( '' !== $name['first_name'] ) {
		$update['first_name']   = $name['first_name'];
		$update['display_name'] = trim( $name['first_name'] . ' ' . $name['last_name'] );
		update_user_meta( $user_id, 'first_name', $name['first_name'] );
		update_user_meta( $user_id, 'billing_first_name', $name['first_name'] );
	}
	if ( '' !== $name['last_name'] ) {
		$update['last_name'] = $name['last_name'];
		update_user_meta( $user_id, 'last_name', $name['last_name'] );
		update_user_meta( $user_id, 'billing_last_name', $name['last_name'] );
	}
	if ( count( $update ) > 1 ) {
		wp_update_user( $update );
	}
}

/**
 * @param array $valid_form_data Form data.
 * @param int   $form_id         Form ID.
 * @param int   $user_id         User ID.
 * @return void
 */
function ttp_ur_on_register_sync_profile( $valid_form_data, $form_id, $user_id ) {
	unset( $form_id );
	ttp_phone_on_ur_register( $valid_form_data, 0, $user_id );
	ttp_ur_save_name_for_user( $user_id, $valid_form_data );
}

// Right after UR writes usermeta (phone + name are in DB).
add_action( 'user_registration_after_user_meta_update', 'ttp_ur_on_register_sync_profile', 20, 3 );
// Final pass before success JSON (membership / email-confirm flows).
add_action( 'user_registration_after_register_user_action', 'ttp_ur_on_register_sync_profile', 99, 3 );

/**
 * Sanitize name fields before UR creates the account (stops gmail-in-name registrations).
 *
 * @param array $valid_form_data UR form data.
 * @param int   $form_id         Form ID.
 * @return array
 */
function ttp_ur_sanitize_register_name_fields( $valid_form_data, $form_id ) {
	unset( $form_id );
	if ( ! is_array( $valid_form_data ) ) {
		return $valid_form_data;
	}
	foreach ( $valid_form_data as $data ) {
		if ( ! is_object( $data ) || ! isset( $data->value ) || ! is_scalar( $data->value ) ) {
			continue;
		}
		$field_key  = isset( $data->extra_params['field_key'] ) ? strtolower( (string) $data->extra_params['field_key'] ) : '';
		$field_name = isset( $data->field_name ) ? strtolower( (string) $data->field_name ) : '';
		$label      = isset( $data->extra_params['label'] ) ? strtolower( (string) $data->extra_params['label'] ) : '';
		$is_name    = in_array( $field_key, array( 'first_name', 'last_name', 'nickname', 'display_name' ), true )
			|| in_array( $field_name, array( 'first_name', 'last_name', 'nickname', 'display_name', 'full_name' ), true )
			|| preg_match( '/full\s*name|your\s*name|^name$/i', $label );
		if ( ! $is_name ) {
			continue;
		}
		$data->value = ttp_sanitize_person_name_value( (string) $data->value );
	}
	return $valid_form_data;
}
add_filter( 'user_registration_before_register_user_filter', 'ttp_ur_sanitize_register_name_fields', 20, 2 );

/**
 * Repair accounts that saved an email address as their display name (breaks TCY course access).
 *
 * @param int $user_id User ID.
 * @return bool True when a repair was applied.
 */
function ttp_repair_user_names_if_email_for_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id < 1 ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( ! $user instanceof WP_User ) {
		return false;
	}
	$bad = is_email( $user->display_name ) || is_email( $user->first_name );
	if ( ! $bad ) {
		return false;
	}
	ttp_ur_save_name_for_user( $user->ID, array() );
	if ( function_exists( 'ttp_resolve_customer_full_name' ) ) {
		global $wpdb;
		$full_name = ttp_resolve_customer_full_name( $user->ID );
		if ( '' !== $full_name ) {
			$wpdb->update(
				$wpdb->prefix . 'ttp_students',
				array( 'full_name' => $full_name ),
				array( 'wp_user_id' => $user->ID )
			);
		}
	}
	return true;
}

/**
 * @param string  $user_login Username.
 * @param WP_User $user       User.
 * @return void
 */
function ttp_repair_user_names_if_email( $user_login, $user ) {
	unset( $user_login );
	if ( ! $user instanceof WP_User ) {
		return;
	}
	ttp_repair_user_names_if_email_for_user( $user->ID );
}
add_action( 'wp_login', 'ttp_repair_user_names_if_email', 20, 2 );

/**
 * Sanitize name fields on UR edit-profile save.
 *
 * @param array $profile Profile fields.
 * @param int   $user_id User ID.
 * @param int   $form_id Form ID.
 * @return array
 */
function ttp_ur_sanitize_profile_name_fields( $profile, $user_id, $form_id ) {
	unset( $user_id, $form_id );
	if ( ! is_array( $profile ) ) {
		return $profile;
	}
	foreach ( $profile as $key => $field ) {
		if ( ! is_object( $field ) || ! isset( $field->value ) ) {
			continue;
		}
		$key_str = strtolower( (string) $key );
		if ( false === strpos( $key_str, 'name' ) && false === strpos( $key_str, 'nickname' ) ) {
			continue;
		}
		$field->value = ttp_sanitize_person_name_value( (string) $field->value );
	}
	return $profile;
}
add_filter( 'user_registration_before_save_profile_details', 'ttp_ur_sanitize_profile_name_fields', 5, 3 );

/**
 * After UR profile save: sync ttp_students + retry TCY for unpaid mappings.
 *
 * @param int $user_id User ID.
 * @param int $form_id Form ID.
 * @return void
 */
function ttp_ur_after_profile_save_repair_tcy( $user_id, $form_id ) {
	unset( $form_id );
	$user_id = absint( $user_id );
	if ( $user_id < 1 ) {
		return;
	}
	ttp_repair_user_names_if_email_for_user( $user_id );
	if ( function_exists( 'ttp_sync_tcy_enrollments_for_user_on_account_view' ) ) {
		delete_transient( 'ttp_acct_sync_' . $user_id );
		ttp_sync_tcy_enrollments_for_user_on_account_view( $user_id );
	}
}
add_action( 'user_registration_save_profile_details', 'ttp_ur_after_profile_save_repair_tcy', 20, 2 );

/**
 * Sanitize non-URM profile POST name fields before UR saves them.
 *
 * @param array $fields Form fields.
 * @param array $post   POST data.
 * @return array
 */
function ttp_ur_sanitize_profile_update_post_names( $fields, $post ) {
	unset( $post );
	if ( ! is_array( $fields ) ) {
		return $fields;
	}
	foreach ( $fields as $field_name => $field_obj ) {
		if ( ! is_object( $field_obj ) || ! isset( $field_obj->value ) ) {
			continue;
		}
		$name_key = strtolower( (string) $field_name );
		if ( false === strpos( $name_key, 'name' ) && false === strpos( $name_key, 'nickname' ) ) {
			continue;
		}
		$field_obj->value = ttp_sanitize_person_name_value( (string) $field_obj->value );
	}
	return $fields;
}
add_filter( 'user_registration_profile_update_data', 'ttp_ur_sanitize_profile_update_post_names', 5, 2 );

/**
 * Backfill billing phone for existing accounts (e.g. only ttp_mobile was saved earlier).
 *
 * @param int $user_id User ID.
 * @return void
 */
function ttp_phone_maybe_backfill_user( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id < 1 ) {
		return;
	}

	$billing = get_user_meta( $user_id, 'billing_phone', true );
	if ( is_string( $billing ) && '' !== trim( $billing ) ) {
		return;
	}

	$phone = ttp_phone_get_user_phone( $user_id );
	if ( '' !== $phone ) {
		ttp_phone_save_for_user( $user_id, $phone );
	}
}

/**
 * Sync phone into WC customer session on checkout/cart (all devices: desktop + mobile).
 *
 * @return void
 */
function ttp_phone_sync_customer_session() {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->customer ) {
		return;
	}
	$user_id = get_current_user_id();
	ttp_phone_maybe_backfill_user( $user_id );
	$phone = ttp_phone_get_user_phone( $user_id );
	if ( '' !== $phone ) {
		WC()->customer->set_billing_phone( $phone );
	}
}

add_action( 'woocommerce_checkout_init', 'ttp_phone_sync_customer_session', 20 );
add_action(
	'wp',
	static function () {
		if ( ! function_exists( 'is_checkout' ) ) {
			return;
		}
		if ( is_checkout() || ( function_exists( 'is_cart' ) && is_cart() ) ) {
			ttp_phone_sync_customer_session();
		}
	},
	20
);

/**
 * Prefill checkout mobile field for logged-in users.
 *
 * @param mixed  $value Field value.
 * @param string $input Field key.
 * @return mixed
 */
function ttp_phone_checkout_prefill( $value, $input ) {
	if ( ! is_user_logged_in() ) {
		return $value;
	}

	$user = wp_get_current_user();

	if ( 'billing_email' === $input && $user instanceof WP_User && is_email( $user->user_email ) ) {
		return $user->user_email;
	}

	if ( 'billing_phone' !== $input ) {
		return $value;
	}

	if ( is_string( $value ) && '' !== trim( $value ) ) {
		return $value;
	}

	$phone = ttp_phone_get_user_phone( get_current_user_id() );
	return '' !== $phone ? $phone : $value;
}
add_filter( 'woocommerce_checkout_get_value', 'ttp_phone_checkout_prefill', 20, 2 );
add_filter( 'default_checkout_billing_phone', 'ttp_phone_default_checkout_phone', 20, 2 );

/**
 * @param string $value Current default.
 * @param int    $user_id User ID (may be 0).
 * @return string
 */
function ttp_phone_default_checkout_phone( $value, $user_id = 0 ) {
	unset( $user_id );
	if ( ! is_user_logged_in() ) {
		return $value;
	}
	if ( is_string( $value ) && '' !== trim( $value ) ) {
		return $value;
	}
	$phone = ttp_phone_get_user_phone( get_current_user_id() );
	return '' !== $phone ? $phone : $value;
}

/**
 * Checkout field tweaks: label + remove order notes / additional information.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function ttp_phone_customize_checkout_fields( $fields ) {
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['label']       = __( 'Mobile Number', 'ttp-phone' );
		$fields['billing']['billing_phone']['placeholder'] = __( '10-digit mobile number', 'ttp-phone' );
		$fields['billing']['billing_phone']['priority']  = 25;
	}

	if ( isset( $fields['order'] ) ) {
		unset( $fields['order']['order_comments'] );
		if ( empty( $fields['order'] ) ) {
			unset( $fields['order'] );
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'ttp_phone_customize_checkout_fields', 999 );

add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 99 );

add_action(
	'wp_head',
	static function () {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		echo '<style id="ttp-hide-order-notes">'
			. '.woocommerce-additional-fields,'
			. '.woocommerce-checkout .woocommerce-additional-fields,'
			. '#order_comments_field,'
			. '.woocommerce-checkout #order_comments_field,'
			. 'h3#order_comments_heading,'
			. '.woocommerce-additional-fields__field-wrapper,'
			. '.woocommerce-checkout-review-order-table + .woocommerce-additional-fields,'
			. 'div.woocommerce-additional-fields h3{display:none!important;visibility:hidden!important;height:0!important;margin:0!important;padding:0!important;overflow:hidden!important;}'
			. '</style>';
	},
	99
);

/**
 * JS fallback so billing phone prefills on desktop browsers too (theme/cache safe).
 *
 * @return void
 */
function ttp_phone_checkout_footer_script() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! is_user_logged_in() ) {
		return;
	}
	$phone = ttp_phone_get_user_phone( get_current_user_id() );
	if ( '' === $phone ) {
		return;
	}
	?>
	<script id="ttp-phone-checkout-prefill">
	(function () {
		var phone = <?php echo wp_json_encode( $phone ); ?>;
		function fill() {
			var el = document.querySelector('#billing_phone, input[name="billing_phone"]');
			if (el && !el.value) { el.value = phone; }
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fill);
		} else {
			fill();
		}
		setTimeout(fill, 400);
		setTimeout(fill, 1200);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'ttp_phone_checkout_footer_script', 99 );
