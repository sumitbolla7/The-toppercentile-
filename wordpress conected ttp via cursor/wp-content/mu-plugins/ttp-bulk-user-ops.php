<?php
/**
 * Plugin Name: TTP Bulk User Operations
 * Description: Admin tools to revoke unauthorized affiliate access and audit/repair TCY enrollments.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether affiliate access was explicitly granted by an admin.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function ttp_bulk_is_admin_granted_affiliate( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return false;
	}

	$granted_by = (int) get_user_meta( $user_id, 'ttpa_access_granted_by', true );
	$source     = sanitize_key( (string) get_user_meta( $user_id, 'ttpa_access_source', true ) );

	return $granted_by > 0 && in_array( $source, array( 'manual', 'influencer_role' ), true );
}

/**
 * Users who currently have referral access but were not admin-granted.
 *
 * @return array<int,array<string,mixed>>
 */
function ttp_bulk_find_unauthorized_affiliates() {
	$out = array();

	if ( ! class_exists( 'TTPA_Plugin' ) || ! TTPA_Plugin::instance()->referrals() ) {
		return $out;
	}

	$referrals = TTPA_Plugin::instance()->referrals();
	$members   = $referrals->get_enabled_affiliates( array( 'limit' => 500, 'offset' => 0 ) );

	foreach ( $members as $member ) {
		$user_id = (int) ( $member['user_id'] ?? 0 );
		if ( $user_id < 1 ) {
			continue;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			continue;
		}

		$legacy_role = in_array( 'affiliate', (array) $user->roles, true );
		$source      = (string) ( $member['access_source'] ?? '' );
		$admin_ok    = ttp_bulk_is_admin_granted_affiliate( $user_id );

		if ( $admin_ok && ! $legacy_role ) {
			continue;
		}

		$out[] = array(
			'user_id'     => $user_id,
			'email'       => $user->user_email,
			'name'        => $user->display_name,
			'source'      => $source,
			'legacy_role' => $legacy_role,
			'granted_by'  => (string) ( $member['access_granted_by'] ?? '' ),
		);
	}

	return $out;
}

/**
 * Revoke affiliate access for every user with any referral flags/roles.
 *
 * @return array<string,mixed>
 */
function ttp_bulk_revoke_all_affiliates() {
	if ( ! class_exists( 'TTPA_Plugin' ) || ! TTPA_Plugin::instance()->referrals() ) {
		return array( 'error' => 'TTPA_Plugin not available.' );
	}

	$referrals = TTPA_Plugin::instance()->referrals();
	if ( ! method_exists( $referrals, 'revoke_everyone' ) ) {
		return array( 'error' => 'revoke_everyone() not available — update ttp-affiliate plugin.' );
	}

	return $referrals->revoke_everyone();
}

/**
 * Revoke affiliate access for users not explicitly granted by admin.
 *
 * @param string[] $force_emails Always revoke these emails if they have access.
 * @return array<string,mixed>
 */
function ttp_bulk_revoke_unauthorized_affiliates( $force_emails = array() ) {
	global $wpdb;

	$result = array(
		'revoked' => array(),
		'kept'    => array(),
		'errors'  => array(),
	);

	if ( ! class_exists( 'TTPA_Plugin' ) || ! TTPA_Plugin::instance()->referrals() ) {
		$result['errors'][] = 'TTPA_Plugin not available.';
		return $result;
	}

	$referrals    = TTPA_Plugin::instance()->referrals();
	$force_emails = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $e ) {
						return strtolower( trim( sanitize_email( (string) $e ) ) );
					},
					(array) $force_emails
				)
			)
		)
	);

	$members = $referrals->get_enabled_affiliates( array( 'limit' => 500, 'offset' => 0 ) );

	foreach ( $members as $member ) {
		$user_id = (int) ( $member['user_id'] ?? 0 );
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
		if ( ! $user instanceof WP_User ) {
			continue;
		}

		$email       = strtolower( trim( (string) $user->user_email ) );
		$legacy_role = in_array( 'affiliate', (array) $user->roles, true );
		$force       = in_array( $email, $force_emails, true );
		$admin_ok    = ttp_bulk_is_admin_granted_affiliate( $user_id );

		if ( ! $force && $admin_ok && ! $legacy_role ) {
			$result['kept'][] = array(
				'user_id' => $user_id,
				'email'   => $user->user_email,
				'name'    => $user->display_name,
			);
			continue;
		}

		$referrals->revoke_all_access( $user_id );

		if ( $legacy_role ) {
			$user->remove_role( 'affiliate' );
		}

		$result['revoked'][] = array(
			'user_id' => $user_id,
			'email'   => $user->user_email,
			'name'    => $user->display_name,
			'reason'  => $force ? 'forced' : ( $legacy_role ? 'legacy_role' : (string) ( $member['access_source'] ?? 'not_admin_granted' ) ),
		);
	}

	// Users with legacy affiliate role but not in enabled list.
	$legacy_users = get_users(
		array(
			'role'   => 'affiliate',
			'number' => 500,
			'fields' => array( 'ID', 'user_email', 'display_name' ),
		)
	);
	foreach ( $legacy_users as $legacy_user ) {
		$uid = (int) $legacy_user->ID;
		if ( $uid < 1 ) {
			continue;
		}
		$already = false;
		foreach ( $result['revoked'] as $row ) {
			if ( (int) $row['user_id'] === $uid ) {
				$already = true;
				break;
			}
		}
		if ( $already ) {
			continue;
		}
		$referrals->revoke_all_access( $uid );
		$u = get_userdata( $uid );
		if ( $u instanceof WP_User && in_array( 'affiliate', (array) $u->roles, true ) ) {
			$u->remove_role( 'affiliate' );
		}
		$result['revoked'][] = array(
			'user_id' => $uid,
			'email'   => $legacy_user->user_email,
			'name'    => $legacy_user->display_name,
			'reason'  => 'legacy_role_only',
		);
	}

	return $result;
}

/**
 * Fix JBIMS mapping rows that still use legacy TCY course ids.
 *
 * @param int $wp_user_id WP user id.
 * @return int Rows updated.
 */
function ttp_bulk_repair_jbims_order_mappings( $wp_user_id = 0 ) {
	global $wpdb;

	$wp_user_id = (int) $wp_user_id;
	$updated    = 0;
	$sql        = "SELECT om.*, p.post_title
		FROM {$wpdb->prefix}ttp_order_mapping om
		LEFT JOIN {$wpdb->posts} p ON p.ID = om.product_id
		WHERE om.tcy_course_id IN ('90235','90238')";
	$params = array();
	if ( $wp_user_id > 0 ) {
		$sql     .= ' AND om.wp_user_id = %d';
		$params[] = $wp_user_id;
	}
	$rows = $params
		? $wpdb->get_results( $wpdb->prepare( $sql, $params ) )
		: $wpdb->get_results( $sql );

	foreach ( (array) $rows as $row ) {
		$title   = (string) ( $row->post_title ?? '' );
		$is_elite = (bool) preg_match( '/elite/i', $title );
		$wpdb->update(
			$wpdb->prefix . 'ttp_order_mapping',
			array(
				'tcy_course_id'   => '90334',
				'tcy_category_id' => '100000',
				'status'          => 'registered',
				'login_link'      => '',
			),
			array( 'id' => (int) $row->id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		++$updated;
	}

	return $updated;
}

/**
 * Audit and optionally repair one customer by email.
 *
 * @param string $email  Billing email.
 * @param bool   $repair Run TCY add_course repair.
 * @return array<string,mixed>
 */
function ttp_bulk_audit_customer_by_email( $email, $repair = false ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$row   = array(
		'email'   => $email,
		'status'  => 'ok',
		'issues'  => array(),
		'actions' => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$row['status'] = 'invalid_email';
		$row['issues'][] = 'Invalid email.';
		return $row;
	}

	$user = get_user_by( 'email', $email );
	$uid  = $user instanceof WP_User ? (int) $user->ID : 0;

	if ( $uid < 1 ) {
		$row['status'] = 'no_wp_user';
		$row['issues'][] = 'No WordPress account for this email.';
	}

	if ( class_exists( 'TTPA_Plugin' ) && TTPA_Plugin::instance()->referrals() && $uid > 0 ) {
		$ref = TTPA_Plugin::instance()->referrals();
		if ( $ref->is_affiliate_enabled( $uid ) && ! ttp_bulk_is_admin_granted_affiliate( $uid ) ) {
			$row['issues'][] = 'Unauthorized affiliate access (not admin-granted).';
		}
	}

	if ( function_exists( 'ttp_tcy_diagnose_customer_enrollment' ) ) {
		$row['diagnosis'] = ttp_tcy_diagnose_customer_enrollment( $uid, $email );
		$diag             = $row['diagnosis'];

		if ( empty( $diag['canonical_tcy'] ) ) {
			$row['issues'][] = 'No TCY user id linked.';
		}
		if ( ! empty( $diag['invalid_course_ids'] ) ) {
			$row['issues'][] = 'Invalid/stale course ids: ' . implode( ', ', (array) $diag['invalid_course_ids'] );
		}
		if ( empty( $diag['orders'] ) && $uid > 0 ) {
			$row['issues'][] = 'No qualifying WooCommerce orders found.';
		}

		$jbims_found = false;
		foreach ( (array) ( $diag['orders'] ?? array() ) as $order_row ) {
			foreach ( (array) ( $order_row['lines'] ?? array() ) as $line ) {
				$name = (string) ( $line['name'] ?? '' );
				$cid  = (string) ( $line['course_id'] ?? '' );
				if ( preg_match( '/jbims|mfin|mhrd|bootcamp/i', $name ) || in_array( $cid, array( '90334', '90235', '90238' ), true ) ) {
					$jbims_found = true;
					if ( in_array( $cid, array( '90235', '90238', '' ), true ) ) {
						$row['issues'][] = 'JBIMS order uses legacy/missing course_id (' . ( $cid !== '' ? $cid : 'empty' ) . ') for ' . $name;
					}
				}
			}
		}
		if ( $jbims_found ) {
			$row['has_jbims'] = true;
		}
	}

	if ( ! empty( $row['issues'] ) && 'invalid_email' !== $row['status'] && 'no_wp_user' !== $row['status'] ) {
		$row['status'] = 'needs_attention';
	} elseif ( ! empty( $row['issues'] ) ) {
		$row['status'] = 'issues';
	}

	if ( $repair && $uid > 0 && function_exists( 'ttp_tcy_repair_customer_enrollments' ) ) {
		$maps = ttp_bulk_repair_jbims_order_mappings( $uid );
		if ( $maps > 0 ) {
			$row['actions'][] = 'Fixed ' . $maps . ' JBIMS order mapping row(s).';
		}

		if ( class_exists( 'TTP_Catalog_Seed' ) ) {
			TTP_Catalog_Seed::repair_all_tcy_meta();
		}

		$sync = ttp_tcy_repair_customer_enrollments( $uid, $email );
		$row['repair'] = $sync;

		if ( ! empty( $sync['failed'] ) ) {
			$row['status']   = 'repair_partial';
			$row['issues'][] = (int) $sync['failed'] . ' add_course call(s) failed.';
		} elseif ( ! empty( $sync['error'] ) ) {
			$row['status']   = 'repair_error';
			$row['issues'][] = (string) $sync['error'];
		} elseif ( ! empty( $row['issues'] ) ) {
			$row['status'] = 'repaired';
		}

		if ( function_exists( 'ttp_get_qualifying_orders_for_user' ) && function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
			foreach ( ttp_get_qualifying_orders_for_user( $uid ) as $order ) {
				if ( $order instanceof WC_Order ) {
					ttp_sync_tcy_courses_for_order( $order );
				}
			}
			$row['actions'][] = 'Re-synced qualifying orders.';
		}
	}

	return $row;
}

/**
 * Audit/repair many emails.
 *
 * @param string[] $emails Email list.
 * @param bool     $repair Run repairs.
 * @return array<string,mixed>
 */
function ttp_bulk_audit_customers( $emails, $repair = false ) {
	$summary = array(
		'total'    => 0,
		'ok'       => 0,
		'issues'   => 0,
		'repaired' => 0,
		'rows'     => array(),
	);

	$emails = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $e ) {
						return strtolower( trim( sanitize_email( (string) $e ) ) );
					},
					(array) $emails
				)
			)
		)
	);

	foreach ( $emails as $email ) {
		$row = ttp_bulk_audit_customer_by_email( $email, $repair );
		$summary['rows'][] = $row;
		++$summary['total'];

		if ( in_array( $row['status'], array( 'repaired', 'ok' ), true ) && empty( $row['issues'] ) ) {
			++$summary['ok'];
		} elseif ( in_array( $row['status'], array( 'repaired', 'repair_partial' ), true ) ) {
			++$summary['repaired'];
			++$summary['issues'];
		} else {
			++$summary['issues'];
		}
	}

	return $summary;
}

/**
 * JBIMS Bootcamp / Elite WooCommerce product IDs.
 *
 * @return int[]
 */
function ttp_bulk_jbims_product_ids() {
	$ids = array();
	if ( ! class_exists( 'TTP_Catalog_Seed' ) ) {
		return $ids;
	}
	foreach ( array( 'jbims-mfin-mhrd-bootcamp', 'jbims-mfin-mhrd-bootcamp-elite' ) as $slug ) {
		$pid = (int) TTP_Catalog_Seed::get_product_id_by_slug( $slug );
		if ( $pid > 0 ) {
			$ids[] = $pid;
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Whether an order line is JBIMS Bootcamp / Elite.
 *
 * @param WC_Order_Item_Product $item Line item.
 * @return bool
 */
function ttp_bulk_order_item_is_jbims( $item ) {
	if ( ! $item instanceof WC_Order_Item_Product ) {
		return false;
	}
	$name = (string) $item->get_name();
	if ( preg_match( '/jbims|mfin|mhrd|bootcamp/i', $name ) ) {
		return true;
	}
	$pid = (int) $item->get_product_id();
	return $pid > 0 && in_array( $pid, ttp_bulk_jbims_product_ids(), true );
}

/**
 * Resolve JBIMS pack sub_cat for a line item.
 *
 * @param WC_Order_Item_Product $item Line item.
 * @return string
 */
function ttp_bulk_jbims_sub_cat_for_item( $item ) {
	$name = $item instanceof WC_Order_Item_Product ? (string) $item->get_name() : '';
	if ( preg_match( '/elite/i', $name ) ) {
		return '38033';
	}
	$product_id = $item instanceof WC_Order_Item_Product ? (int) $item->get_product_id() : 0;
	if ( $product_id > 0 && class_exists( 'TTP_Catalog_Seed' ) ) {
		$def = TTP_Catalog_Seed::get_definition_for_product( $product_id );
		if ( $def && ! empty( $def['tcy_product_pack_id'] ) ) {
			return sanitize_text_field( (string) $def['tcy_product_pack_id'] );
		}
	}
	return '38081';
}

/**
 * Find paid JBIMS orders in the last N days (+ optional email filter).
 *
 * @param int      $days         Lookback days.
 * @param string[] $extra_emails Always include orders for these billing emails.
 * @return WC_Order[]
 */
function ttp_bulk_find_recent_jbims_orders( $days = 5, $extra_emails = array() ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$days       = max( 1, (int) $days );
	$statuses   = apply_filters( 'ttp_tcy_qualifying_order_statuses', array( 'processing', 'completed', 'on-hold' ) );
	$after      = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
	$jbims_pids = ttp_bulk_jbims_product_ids();
	$by_id      = array();

	$recent = wc_get_orders(
		array(
			'limit'        => 500,
			'status'       => $statuses,
			'date_created' => '>' . $after,
			'orderby'      => 'date',
			'order'        => 'DESC',
		)
	);

	foreach ( (array) $recent as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			if ( ttp_bulk_order_item_is_jbims( $item ) ) {
				$by_id[ (int) $order->get_id() ] = $order;
				break;
			}
		}
	}

	$extra_emails = array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $e ) {
						return strtolower( trim( sanitize_email( (string) $e ) ) );
					},
					(array) $extra_emails
				)
			)
		)
	);

	foreach ( $extra_emails as $email ) {
		if ( $email === '' ) {
			continue;
		}
		$email_orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => 20,
				'status'        => $statuses,
				'orderby'       => 'date',
				'order'         => 'DESC',
			)
		);
		foreach ( (array) $email_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( ttp_bulk_order_item_is_jbims( $item ) ) {
					$by_id[ (int) $order->get_id() ] = $order;
					break;
				}
			}
		}
	}

	return array_values( $by_id );
}

/**
 * Force TCY add_course for JBIMS lines on one order (bypass register_covers skip).
 *
 * @param WC_Order $order Order.
 * @return array<string,mixed>
 */
function ttp_bulk_force_renew_jbims_order( $order ) {
	global $wpdb;

	$result = array(
		'order_id'     => 0,
		'email'        => '',
		'status'       => 'ok',
		'actions'      => array(),
		'add_course'   => array(),
		'issues'       => array(),
	);

	if ( ! $order instanceof WC_Order ) {
		$result['status'] = 'error';
		$result['issues'][] = 'Invalid order.';
		return $result;
	}

	$order_id = (int) $order->get_id();
	$result['order_id'] = $order_id;
	$result['email']    = (string) $order->get_billing_email();

	if ( ! function_exists( 'ttp_order_qualifies_for_tcy_actions' ) || ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
		$result['status'] = 'skipped';
		$result['issues'][] = 'Order not paid/qualifying (status: ' . $order->get_status() . ').';
		return $result;
	}

	$jbims_lines = array();
	foreach ( $order->get_items() as $item ) {
		if ( ttp_bulk_order_item_is_jbims( $item ) ) {
			$jbims_lines[] = $item;
		}
	}
	if ( empty( $jbims_lines ) ) {
		$result['status'] = 'skipped';
		$result['issues'][] = 'No JBIMS line items on this order.';
		return $result;
	}

	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		TTP_Catalog_Seed::repair_all_tcy_meta();
		$result['actions'][] = 'Catalog TCY meta repaired.';
	}

	$maps = ttp_bulk_repair_jbims_order_mappings( (int) $order->get_user_id() );
	if ( $maps > 0 ) {
		$result['actions'][] = 'Fixed ' . $maps . ' legacy JBIMS mapping row(s).';
	}

	$checkout = function_exists( 'ttp_get_checkout_instance' ) ? ttp_get_checkout_instance() : null;
	$tcy_id   = function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '';

	if ( $tcy_id === '' && function_exists( 'ttp_lookup_tcy_user_id_by_email' ) ) {
		$tcy_id = ttp_lookup_tcy_user_id_by_email( (string) $order->get_billing_email() );
	}

	if ( $tcy_id === '' && $checkout ) {
		$order->delete_meta_data( '_ttp_tcy_registered' );
		$order->delete_meta_data( '_tcy_enrolled' );
		$order->save();
		$checkout->trigger_tcy_registration( $order_id );
		$result['actions'][] = 'Triggered TCY registration.';
		$tcy_id = function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '';
	}

	if ( $tcy_id === '' ) {
		$result['status'] = 'error';
		$result['issues'][] = 'No TCY user id after registration attempt.';
		return $result;
	}

	$result['tcy_user_id'] = $tcy_id;
	$wp_uid                = (int) $order->get_user_id();
	if ( $wp_uid > 0 ) {
		update_user_meta( $wp_uid, '_ttp_tcy_user_id', $tcy_id );
	}

	foreach ( $jbims_lines as $item ) {
		$product_id  = (int) $item->get_product_id();
		$line_name   = (string) $item->get_name();
		$ids         = function_exists( 'ttp_get_tcy_ids_for_line_item' )
			? ttp_get_tcy_ids_for_line_item( $item, true )
			: ( function_exists( 'ttp_get_tcy_ids_for_product' ) ? ttp_get_tcy_ids_for_product( $product_id, true ) : array() );
		$course_id   = function_exists( 'ttp_tcy_canonical_course_id' )
			? ttp_tcy_canonical_course_id( (string) ( $ids['course_id'] ?? '90334' ) )
			: '90334';
		$category_id = function_exists( 'ttp_tcy_normalize_api_category_id' )
			? ttp_tcy_normalize_api_category_id( (string) ( $ids['category_id'] ?? '100000' ) )
			: '100000';
		$sub_cat     = ttp_bulk_jbims_sub_cat_for_item( $item );

		$attempt = function_exists( 'ttp_tcy_call_add_course_with_retries' )
			? ttp_tcy_call_add_course_with_retries( $tcy_id, $course_id, $category_id, $order_id, $product_id, $sub_cat )
			: array( 'outcome' => 'failed', 'response' => array( 'error' => 'add_course helper missing' ) );

		$outcome  = isset( $attempt['outcome'] ) ? (string) $attempt['outcome'] : 'failed';
		$response = isset( $attempt['response'] ) && is_array( $attempt['response'] ) ? $attempt['response'] : array();
		$row      = array(
			'line'      => $line_name,
			'course_id' => $course_id,
			'sub_cat'   => $sub_cat,
			'outcome'   => $outcome,
			'error'     => ( 'failed' === $outcome && function_exists( 'ttp_tcy_response_error_message' ) )
				? ttp_tcy_response_error_message( $response )
				: '',
		);
		$result['add_course'][] = $row;

		if ( 'failed' === $outcome ) {
			$result['status'] = 'partial';
			$result['issues'][] = $line_name . ': ' . $row['error'];
		}

		$wpdb->replace(
			$wpdb->prefix . 'ttp_order_mapping',
			array(
				'order_id'        => $order_id,
				'wp_user_id'      => $wp_uid,
				'tcy_user_id'     => $tcy_id,
				'tcy_course_id'   => $course_id,
				'tcy_category_id' => $category_id,
				'status'          => 'registered',
				'login_link'      => '',
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	$order->update_meta_data( '_tcy_enrolled', 'yes' );
	$order->update_meta_data( '_ttp_tcy_registered', true );
	$order->update_meta_data( '_ttp_tcy_register_course_id', '90334' );
	$order->save();
	$result['actions'][] = 'Order marked enrolled.';

	if ( function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
		ttp_sync_tcy_courses_for_order( $order );
		$result['actions'][] = 'Order course sync completed.';
	}

	if ( $wp_uid > 0 && function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		ttp_sync_portal_enrollments_for_user( $wp_uid, (string) $order->get_billing_email() );
		$result['actions'][] = 'Portal enrollment synced for login dashboard.';
	}

	return $result;
}

/**
 * Renew all JBIMS orders from the last N days (+ priority emails).
 *
 * @param int      $days         Lookback days.
 * @param string[] $extra_emails Always renew JBIMS for these emails.
 * @return array<string,mixed>
 */
function ttp_bulk_renew_recent_jbims_orders( $days = 5, $extra_emails = array() ) {
	$orders  = ttp_bulk_find_recent_jbims_orders( $days, $extra_emails );
	$summary = array(
		'days'         => (int) $days,
		'orders_found' => count( $orders ),
		'ok'           => 0,
		'partial'      => 0,
		'errors'       => 0,
		'skipped'      => 0,
		'rows'         => array(),
	);

	foreach ( $orders as $order ) {
		$row = ttp_bulk_force_renew_jbims_order( $order );
		$summary['rows'][] = $row;
		switch ( $row['status'] ?? '' ) {
			case 'ok':
				++$summary['ok'];
				break;
			case 'partial':
				++$summary['partial'];
				break;
			case 'skipped':
				++$summary['skipped'];
				break;
			default:
				++$summary['errors'];
		}
	}

	return $summary;
}

/**
 * Collect all WooCommerce orders for a customer (email, user id, phone, mapping).
 *
 * @param string $email  Billing email.
 * @param int    $wp_uid WordPress user ID.
 * @param string $phone  Phone digits.
 * @return WC_Order[]
 */
function ttp_bulk_collect_customer_orders( $email, $wp_uid = 0, $phone = '' ) {
	global $wpdb;

	$email  = strtolower( trim( sanitize_email( (string) $email ) ) );
	$wp_uid = (int) $wp_uid;
	$phone  = preg_replace( '/\D+/', '', (string) $phone );
	$by_id  = array();

	$push = static function ( $order ) use ( &$by_id ) {
		if ( $order instanceof WC_Order ) {
			$by_id[ (int) $order->get_id() ] = $order;
		}
	};

	if ( function_exists( 'wc_get_orders' ) ) {
		if ( $email !== '' && is_email( $email ) ) {
			foreach ( (array) wc_get_orders(
				array(
					'billing_email' => $email,
					'limit'         => 30,
					'status'        => 'any',
					'orderby'       => 'date',
					'order'         => 'DESC',
				)
			) as $order ) {
				$push( $order );
			}
		}
		if ( $wp_uid > 0 ) {
			foreach ( (array) wc_get_orders(
				array(
					'customer_id' => $wp_uid,
					'limit'       => 30,
					'status'      => 'any',
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			) as $order ) {
				$push( $order );
			}
		}
	}

	if ( $phone !== '' ) {
		$like = '%' . $wpdb->esc_like( $phone ) . '%';
		$ids  = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key IN ('_billing_phone','_shipping_phone') AND meta_value LIKE %s ORDER BY order_id DESC LIMIT 30",
				$like
			)
		);
		if ( empty( $ids ) ) {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_billing_phone','_shipping_phone') AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 30",
					$like
				)
			);
		}
		foreach ( (array) $ids as $oid ) {
			$push( wc_get_order( (int) $oid ) );
		}
	}

	if ( $wp_uid > 0 ) {
		$map_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT order_id FROM {$wpdb->prefix}ttp_order_mapping WHERE wp_user_id = %d ORDER BY order_id DESC LIMIT 30",
				$wp_uid
			)
		);
		foreach ( (array) $map_ids as $oid ) {
			$push( wc_get_order( (int) $oid ) );
		}
	}

	uasort(
		$by_id,
		static function ( $a, $b ) {
			return (int) $b->get_id() - (int) $a->get_id();
		}
	);

	return array_values( $by_id );
}

/**
 * Encode plain TCY numeric id (8160407) to ERP token (ODE2MDQwNw==).
 *
 * @param string|int $numeric TCY id from export sheet.
 * @return string
 */
function ttp_bulk_encode_tcy_numeric_id( $numeric ) {
	$numeric = preg_replace( '/\D+/', '', (string) $numeric );
	if ( $numeric === '' ) {
		return '';
	}
	$encoded = base64_encode( $numeric );
	return function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( $encoded ) : $encoded;
}

/**
 * Link a known TCY account and force JBIMS add_course (90334 + pack).
 *
 * @param string     $email       Billing email.
 * @param string|int $tcy_numeric Plain TCY id from export.
 * @param string     $phone       Phone digits.
 * @param string     $sub_cat     JBIMS pack (38081 Bootcamp / 38033 Elite).
 * @return array<string,mixed>
 */
function ttp_bulk_link_known_tcy_jbims( $email, $tcy_numeric, $phone = '', $sub_cat = '38081' ) {
	global $wpdb;

	$email   = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone   = preg_replace( '/\D+/', '', (string) $phone );
	$sub_cat = sanitize_text_field( (string) $sub_cat );
	if ( $sub_cat === '' ) {
		$sub_cat = '38081';
	}

	$out = array(
		'email'       => $email,
		'tcy_numeric' => preg_replace( '/\D+/', '', (string) $tcy_numeric ),
		'phone'       => $phone,
		'sub_cat'     => $sub_cat,
		'actions'     => array(),
		'issues'      => array(),
	);

	$tcy_id = ttp_bulk_encode_tcy_numeric_id( $tcy_numeric );
	if ( $tcy_id === '' ) {
		$out['issues'][] = 'Invalid TCY numeric id.';
		return $out;
	}
	$out['tcy_user_id'] = $tcy_id;

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$out['wp_user_id'] = $wp_uid;

	if ( $wp_uid > 0 ) {
		update_user_meta( $wp_uid, '_ttp_tcy_user_id', $tcy_id );
		$out['actions'][] = 'Linked _ttp_tcy_user_id on WP user.';
	}

	$jbims_product_id = 0;
	$jbims_name       = 'JBIMS MFIN MHRD Bootcamp';
	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		$jbims_product_id = (int) TTP_Catalog_Seed::get_product_id_by_slug( 'jbims-mfin-mhrd-bootcamp' );
		$def              = TTP_Catalog_Seed::get_definition_by_slug( '38033' === $sub_cat ? 'jbims-mfin-mhrd-bootcamp-elite' : 'jbims-mfin-mhrd-bootcamp' );
		if ( is_array( $def ) && ! empty( $def['name'] ) ) {
			$jbims_name = (string) $def['name'];
		} elseif ( '38033' === $sub_cat ) {
			$jbims_name = 'JBIMS MFIN MHRD Bootcamp Elite';
		}
	}
	if ( $wp_uid > 0 && $jbims_product_id > 0 ) {
		update_user_meta(
			$wp_uid,
			'_ttp_portal_enrollments',
			array(
				array(
					'name'          => $jbims_name,
					'tcy_course_id' => '90334',
					'product_id'    => $jbims_product_id,
					'sub_cat'       => $sub_cat,
				),
			)
		);
		$out['actions'][] = 'Portal enrollment row set for login dashboard.';
	}

	$student = null;
	if ( $wp_uid > 0 ) {
		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d ORDER BY id DESC LIMIT 1",
				$wp_uid
			)
		);
	}
	if ( ! $student && $email !== '' ) {
		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ttp_students WHERE email = %s ORDER BY id DESC LIMIT 1",
				$email
			)
		);
	}

	$name = $user instanceof WP_User ? trim( $user->first_name . ' ' . $user->last_name ) : '';
	if ( $name === '' && $user instanceof WP_User ) {
		$name = (string) $user->display_name;
	}
	if ( $name === '' ) {
		$name = strstr( $email, '@', true ) ?: 'Student';
	}

	if ( $student ) {
		$wpdb->update(
			$wpdb->prefix . 'ttp_students',
			array(
				'tcy_user_id' => $tcy_id,
				'email'       => $email,
				'mobile'      => $phone,
			),
			array( 'id' => (int) $student->id )
		);
		$out['actions'][] = 'Updated ttp_students row #' . (int) $student->id . '.';
	} elseif ( $wp_uid > 0 ) {
		$wpdb->insert(
			$wpdb->prefix . 'ttp_students',
			array(
				'wp_user_id'  => $wp_uid,
				'full_name'   => $name,
				'email'       => $email,
				'mobile'      => $phone,
				'username'    => $email,
				'tcy_user_id' => $tcy_id,
			)
		);
		$out['actions'][] = 'Created ttp_students row.';
	}

	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		TTP_Catalog_Seed::repair_all_tcy_meta();
	}
	$maps = ttp_bulk_repair_jbims_order_mappings( $wp_uid );
	if ( $maps > 0 ) {
		$out['actions'][] = 'Fixed ' . $maps . ' legacy JBIMS mapping row(s).';
	}

	if ( function_exists( 'ttp_tcy_unify_customer_to_canonical_id' ) && $wp_uid > 0 ) {
		ttp_tcy_unify_customer_to_canonical_id( $tcy_id, $wp_uid, $email );
	}

	if ( class_exists( 'TTP_TCY_API' ) ) {
		$api = new TTP_TCY_API();
		foreach ( array( '90235', '90238' ) as $legacy_cid ) {
			$removed = $api->remove_course( $tcy_id, $legacy_cid, '100000' );
			$out['remove_legacy'][] = array(
				'course_id' => $legacy_cid,
				'success'   => function_exists( 'ttp_tcy_api_is_success' ) ? ttp_tcy_api_is_success( $removed ) : ! empty( $removed['success'] ),
			);
		}
	}

	if ( function_exists( 'ttp_tcy_call_add_course_with_retries' ) ) {
		$attempt = ttp_tcy_call_add_course_with_retries( $tcy_id, '90334', '100000', 0, 0, $sub_cat );
		$outcome = function_exists( 'ttp_tcy_add_course_outcome' )
			? ttp_tcy_add_course_outcome( $attempt['response'] ?? array() )
			: ( ! empty( $attempt['ok'] ) ? 'success' : 'failed' );
		if ( 'failed' === $outcome && class_exists( 'TTP_TCY_API' ) && function_exists( 'ttp_tcy_response_is_pack_conflict' )
			&& ttp_tcy_response_is_pack_conflict( $attempt['response'] ?? array() ) ) {
			$api = new TTP_TCY_API();
			$api->remove_course( $tcy_id, '90334', '100000' );
			$attempt = ttp_tcy_call_add_course_with_retries( $tcy_id, '90334', '100000', 0, 0, $sub_cat );
			$outcome = function_exists( 'ttp_tcy_add_course_outcome' )
				? ttp_tcy_add_course_outcome( $attempt['response'] ?? array() )
				: ( ! empty( $attempt['ok'] ) ? 'success' : 'failed' );
			$out['actions'][] = 'Retried add_course after removing stale 90334 pack row.';
		}
		$err_msg = function_exists( 'ttp_tcy_response_error_message' )
			? ttp_tcy_response_error_message( $attempt['response'] ?? array() )
			: '';
		if ( 'failed' === $outcome && stripos( $err_msg, 'Course added successfully' ) !== false ) {
			$outcome = 'success';
		}
		$out['add_course'] = array(
			'course_id' => '90334',
			'sub_cat'   => $sub_cat,
			'outcome'   => $outcome,
			'error'     => $err_msg,
		);
		$out['actions'][] = 'add_course 90334 + pack ' . $sub_cat . ' → ' . $outcome;
		if ( $wp_uid > 0 && in_array( $outcome, array( 'success', 'pack_conflict' ), true ) && function_exists( 'ttp_grant_jbims_entitlement' ) ) {
			ttp_grant_jbims_entitlement( $wp_uid, $sub_cat );
			$out['actions'][] = 'JBIMS entitlement granted on WP user.';
		}
	}

	if ( class_exists( 'TTP_TCY_API' ) ) {
		$login = ( new TTP_TCY_API() )->login_student( $tcy_id );
		$out['login_test'] = array(
			'success' => function_exists( 'ttp_tcy_api_is_success' ) ? ttp_tcy_api_is_success( $login ) : ! empty( $login['success'] ),
			'has_url' => function_exists( 'ttp_extract_login_url_from_tcy_response' )
				? ( ttp_extract_login_url_from_tcy_response( $login ) !== '' )
				: false,
		);
	}

	$deep = ttp_bulk_deep_fix_jbims_student( $email, $phone );
	$out['order_scan'] = array(
		'orders'   => $deep['orders'] ?? array(),
		'renewals' => $deep['renewals'] ?? array(),
	);
	if ( ! empty( $deep['renewals'] ) ) {
		$out['actions'][] = 'Also renewed ' . count( $deep['renewals'] ) . ' JBIMS order(s) from WooCommerce.';
	}

	if ( function_exists( 'ttp_tcy_diagnose_customer_enrollment' ) ) {
		$out['diagnosis'] = ttp_tcy_diagnose_customer_enrollment( $wp_uid, $email );
	}

	$out['status'] = ( ! empty( $out['add_course']['outcome'] ) && in_array( $out['add_course']['outcome'], array( 'success', 'already', 'pack_conflict' ), true ) )
		? 'ok'
		: 'partial';

	return $out;
}

/**
 * Diagnose + renew a customer by email (any recent paid order, not only JBIMS).
 *
 * @param string $email Billing email.
 * @return array<string,mixed>
 */
function ttp_bulk_renew_customer_by_email( $email ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$out   = array(
		'email'  => $email,
		'orders' => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['error'] = 'Invalid email.';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$orders = ttp_bulk_collect_customer_orders( $email, $wp_uid );

	$jbims_done = false;
	foreach ( (array) $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$has_jbims = false;
		foreach ( $order->get_items() as $item ) {
			if ( ttp_bulk_order_item_is_jbims( $item ) ) {
				$has_jbims = true;
				break;
			}
		}
		if ( $has_jbims ) {
			$out['orders'][] = ttp_bulk_force_renew_jbims_order( $order );
			$jbims_done      = true;
			continue;
		}
	}

	if ( ! $jbims_done && function_exists( 'ttp_tcy_repair_customer_enrollments' ) ) {
		$out['tcy_repair'] = ttp_tcy_repair_customer_enrollments( $wp_uid, $email );
		if ( function_exists( 'ttp_get_checkout_instance' ) ) {
			$checkout = ttp_get_checkout_instance();
			foreach ( (array) $orders as $order ) {
				if ( $order instanceof WC_Order && $checkout && function_exists( 'ttp_get_tcy_user_id_for_order' ) && ttp_get_tcy_user_id_for_order( $order ) === '' ) {
					$checkout->trigger_tcy_registration( (int) $order->get_id() );
					$out['orders'][] = array(
						'order_id' => (int) $order->get_id(),
						'action'   => 'trigger_tcy_registration',
					);
				}
			}
		}
	}

	return $out;
}

/**
 * Deep JBIMS fix: locate every order/mapping, link TCY id, force add_course with 90334.
 *
 * @param string $email Billing email.
 * @param string $phone Optional phone digits.
 * @return array<string,mixed>
 */
function ttp_bulk_deep_fix_jbims_student( $email, $phone = '' ) {
	global $wpdb;

	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone = preg_replace( '/\D+/', '', (string) $phone );
	$out   = array(
		'email'    => $email,
		'phone'    => $phone,
		'actions'  => array(),
		'issues'   => array(),
		'orders'   => array(),
		'renewals' => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['issues'][] = 'Invalid email.';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$out['wp_user_id'] = $wp_uid;

	if ( $phone === '' && $wp_uid > 0 ) {
		$phone = preg_replace( '/\D+/', '', (string) get_user_meta( $wp_uid, 'billing_phone', true ) );
		if ( $phone === '' ) {
			$phone = preg_replace( '/\D+/', '', (string) get_user_meta( $wp_uid, 'mobile', true ) );
		}
		$out['phone'] = $phone;
	}

	$student_row = null;
	if ( $wp_uid > 0 ) {
		$student_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d ORDER BY id DESC LIMIT 1",
				$wp_uid
			)
		);
	} elseif ( $email !== '' ) {
		$student_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ttp_students WHERE email = %s ORDER BY id DESC LIMIT 1",
				$email
			)
		);
	}
	if ( $student_row && ! empty( $student_row->tcy_user_id ) ) {
		$out['student_tcy_id'] = (string) $student_row->tcy_user_id;
		if ( $wp_uid > 0 ) {
			update_user_meta( $wp_uid, '_ttp_tcy_user_id', (string) $student_row->tcy_user_id );
			$out['actions'][] = 'Linked TCY id from ttp_students.';
		}
	}

	$all_orders = ttp_bulk_collect_customer_orders( $email, $wp_uid, $phone );
	foreach ( $all_orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$lines  = array();
		$jbims  = false;
		foreach ( $order->get_items() as $item ) {
			$name = (string) $item->get_name();
			$lines[] = $name;
			if ( ttp_bulk_order_item_is_jbims( $item ) ) {
				$jbims = true;
			}
		}
		$out['orders'][] = array(
			'order_id'      => (int) $order->get_id(),
			'status'        => $order->get_status(),
			'billing_email' => (string) $order->get_billing_email(),
			'qualifies'     => function_exists( 'ttp_order_qualifies_for_tcy_actions' ) ? ttp_order_qualifies_for_tcy_actions( $order ) : null,
			'has_jbims'     => $jbims,
			'lines'         => $lines,
			'tcy_user_id'   => function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '',
		);
		if ( $jbims && function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order ) ) {
			$out['renewals'][] = ttp_bulk_force_renew_jbims_order( $order );
		} elseif ( $jbims ) {
			$out['issues'][] = 'JBIMS order #' . (int) $order->get_id() . ' not qualifying (status: ' . $order->get_status() . ').';
		}
	}

	$tcy_id = '';
	if ( function_exists( 'ttp_get_canonical_tcy_user_id' ) && ( $wp_uid > 0 || $email !== '' ) ) {
		$tcy_id = ttp_get_canonical_tcy_user_id( $wp_uid, $email );
	}
	if ( $tcy_id === '' && ! empty( $out['student_tcy_id'] ) ) {
		$tcy_id = (string) $out['student_tcy_id'];
	}
	if ( $tcy_id === '' && function_exists( 'ttp_lookup_tcy_user_id_by_email' ) ) {
		$tcy_id = ttp_lookup_tcy_user_id_by_email( $email );
	}
	$out['tcy_user_id'] = $tcy_id;

	if ( empty( $out['renewals'] ) && $tcy_id !== '' && function_exists( 'ttp_tcy_call_add_course_with_retries' ) ) {
		if ( class_exists( 'TTP_Catalog_Seed' ) ) {
			TTP_Catalog_Seed::repair_all_tcy_meta();
		}
		$maps = ttp_bulk_repair_jbims_order_mappings( $wp_uid );
		if ( $maps > 0 ) {
			$out['actions'][] = 'Fixed ' . $maps . ' legacy JBIMS mapping row(s).';
		}
		$sub_cat = '38081';
		foreach ( $all_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( ttp_bulk_order_item_is_jbims( $item ) && preg_match( '/elite/i', (string) $item->get_name() ) ) {
					$sub_cat = '38033';
					break 2;
				}
			}
		}
		$attempt = ttp_tcy_call_add_course_with_retries( $tcy_id, '90334', '100000', 0, 0, $sub_cat );
		$out['direct_add_course'] = array(
			'course_id' => '90334',
			'sub_cat'   => $sub_cat,
			'outcome'   => function_exists( 'ttp_tcy_add_course_outcome' )
				? ttp_tcy_add_course_outcome( $attempt['response'] ?? array() )
				: ( ! empty( $attempt['ok'] ) ? 'success' : 'failed' ),
		);
		if ( $wp_uid > 0 ) {
			update_user_meta( $wp_uid, '_ttp_tcy_user_id', $tcy_id );
		}
		$out['actions'][] = 'Direct add_course 90334 + pack ' . $sub_cat . ' for linked TCY account.';
	} elseif ( empty( $out['renewals'] ) && $tcy_id === '' ) {
		$checkout = function_exists( 'ttp_get_checkout_instance' ) ? ttp_get_checkout_instance() : null;
		foreach ( $all_orders as $order ) {
			if ( ! $order instanceof WC_Order || ! $checkout ) {
				continue;
			}
			if ( ! function_exists( 'ttp_order_qualifies_for_tcy_actions' ) || ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
				continue;
			}
			$order->delete_meta_data( '_ttp_tcy_registered' );
			$order->delete_meta_data( '_tcy_enrolled' );
			$order->save();
			$checkout->trigger_tcy_registration( (int) $order->get_id() );
			$out['actions'][] = 'Triggered TCY registration on order #' . (int) $order->get_id() . '.';
			$tcy_id = function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '';
			if ( $tcy_id !== '' ) {
				break;
			}
		}
		$out['tcy_user_id'] = $tcy_id;
		if ( $tcy_id !== '' ) {
			foreach ( $all_orders as $order ) {
				if ( $order instanceof WC_Order && function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order ) ) {
					$has_jbims = false;
					foreach ( $order->get_items() as $item ) {
						if ( ttp_bulk_order_item_is_jbims( $item ) ) {
							$has_jbims = true;
							break;
						}
					}
					if ( $has_jbims ) {
						$out['renewals'][] = ttp_bulk_force_renew_jbims_order( $order );
					}
				}
			}
		}
	}

	if ( function_exists( 'ttp_tcy_diagnose_customer_enrollment' ) ) {
		$out['diagnosis'] = ttp_tcy_diagnose_customer_enrollment( $wp_uid, $email );
	}

	if ( function_exists( 'ttp_sync_portal_enrollments_for_user' ) && $wp_uid > 0 ) {
		$out['portal_enrollments'] = ttp_sync_portal_enrollments_for_user( $wp_uid, $email );
		$out['actions'][]          = 'Synced portal enrollment rows for login dashboard.';
	}

	if ( function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) && $wp_uid > 0 ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
	}

	$out['status'] = ( ! empty( $out['renewals'] ) || ! empty( $out['direct_add_course'] ) || ! empty( $out['enrolled_panel'] ) ) ? 'ok' : 'needs_review';
	if ( empty( $out['renewals'] ) && empty( $out['direct_add_course'] ) && $tcy_id === '' && empty( $out['enrolled_panel'] ) ) {
		$out['issues'][] = 'No TCY account linked and no qualifying JBIMS order found.';
	}

	return $out;
}

/**
 * Fresh TCY register + JBIMS add_course when no paid order / TCY link exists.
 *
 * @param string $email Billing email.
 * @param string $phone Phone digits.
 * @param bool   $elite Elite pack (38033) vs Bootcamp (38081).
 * @return array<string,mixed>
 */
function ttp_bulk_fresh_register_jbims_student( $email, $phone = '', $elite = false ) {
	global $wpdb;

	$email   = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone   = preg_replace( '/\D+/', '', (string) $phone );
	$sub_cat = $elite ? '38033' : '38081';
	$out     = array(
		'email'   => $email,
		'phone'   => $phone,
		'sub_cat' => $sub_cat,
		'actions' => array(),
		'issues'  => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['issues'][] = 'Invalid email.';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$out['wp_user_id'] = $wp_uid;

	$name = function_exists( 'ttp_resolve_customer_full_name' )
		? ttp_resolve_customer_full_name( $wp_uid, null )
		: ( $user instanceof WP_User ? (string) $user->display_name : '' );
	if ( $name === '' || is_email( $name ) ) {
		$name = strstr( $email, '@', true ) ?: 'Student';
	}

	if ( $phone === '' && $wp_uid > 0 ) {
		$phone = preg_replace( '/\D+/', '', (string) get_user_meta( $wp_uid, 'billing_phone', true ) );
		$out['phone'] = $phone;
	}

	$tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
		? ttp_get_canonical_tcy_user_id( $wp_uid, $email )
		: '';
	if ( $tcy_id === '' && function_exists( 'ttp_lookup_tcy_user_id_by_email' ) ) {
		$tcy_id = ttp_lookup_tcy_user_id_by_email( $email );
	}

	if ( $tcy_id === '' && class_exists( 'TTP_TCY_API' ) ) {
		$api      = new TTP_TCY_API();
		$register = $api->register_student(
			array(
				'full_name'   => $name,
				'email'       => $email,
				'mobile'      => $phone,
				'course_id'   => '90334',
				'category_id' => '100000',
				'order_id'    => 0,
			)
		);
		$tcy_id = function_exists( 'ttp_tcy_extract_register_user_id' )
			? ttp_tcy_extract_register_user_id( $register )
			: '';
		if ( $tcy_id !== '' ) {
			$out['actions'][] = 'Fresh TCY register completed.';
		} else {
			$out['register_response'] = $register;
			$out['issues'][]          = function_exists( 'ttp_tcy_response_error_message' )
				? ttp_tcy_response_error_message( $register )
				: 'TCY register did not return user_id.';
		}
	}

	if ( $tcy_id === '' ) {
		$out['status'] = 'error';
		return $out;
	}

	$tcy_id = function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( $tcy_id ) : $tcy_id;
	$out['tcy_user_id'] = $tcy_id;

	if ( $wp_uid > 0 ) {
		update_user_meta( $wp_uid, '_ttp_tcy_user_id', $tcy_id );
	}

	$student = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d OR email = %s ORDER BY id DESC LIMIT 1",
			$wp_uid,
			$email
		)
	);
	if ( $student ) {
		$wpdb->update(
			$wpdb->prefix . 'ttp_students',
			array(
				'tcy_user_id' => $tcy_id,
				'email'       => $email,
				'mobile'      => $phone,
			),
			array( 'id' => (int) $student->id )
		);
	} elseif ( $wp_uid > 0 ) {
		$wpdb->insert(
			$wpdb->prefix . 'ttp_students',
			array(
				'wp_user_id'  => $wp_uid,
				'full_name'   => $name,
				'email'       => $email,
				'mobile'      => $phone,
				'username'    => $email,
				'tcy_user_id' => $tcy_id,
			)
		);
	}

	if ( class_exists( 'TTP_TCY_API' ) ) {
		$api = new TTP_TCY_API();
		foreach ( array( '90235', '90238' ) as $legacy_cid ) {
			$api->remove_course( $tcy_id, $legacy_cid, '100000' );
		}
	}

	if ( function_exists( 'ttp_tcy_call_add_course_with_retries' ) ) {
		$attempt = ttp_tcy_call_add_course_with_retries( $tcy_id, '90334', '100000', 0, 0, $sub_cat );
		$outcome = function_exists( 'ttp_tcy_add_course_outcome' )
			? ttp_tcy_add_course_outcome( $attempt['response'] ?? array() )
			: 'failed';
		$out['add_course'] = array(
			'course_id' => '90334',
			'sub_cat'   => $sub_cat,
			'outcome'   => $outcome,
		);
		$out['actions'][] = 'add_course → ' . $outcome;
		if ( $wp_uid > 0 && in_array( $outcome, array( 'success', 'pack_conflict' ), true ) && function_exists( 'ttp_grant_jbims_entitlement' ) ) {
			ttp_grant_jbims_entitlement( $wp_uid, $sub_cat );
		}
	}

	if ( $wp_uid > 0 && function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		$out['portal_enrollments'] = ttp_sync_portal_enrollments_for_user( $wp_uid, $email );
		$out['actions'][]          = 'Portal enrollment synced.';
	}

	if ( function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) && $wp_uid > 0 ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
	}

	$out['status'] = ! empty( $out['enrolled_panel'] ) ? 'ok' : 'partial';
	return $out;
}

/**
 * Promote gateway-paid orders stuck in cancelled/failed for one customer.
 *
 * @param string $email Billing email.
 * @param string $phone Optional phone digits.
 * @param int[]  $force_order_ids Order IDs confirmed paid (gateway screenshot / admin).
 * @return array<string,mixed>
 */
function ttp_bulk_promote_gateway_paid_orders_for_customer( $email, $phone = '', $force_order_ids = array() ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone = preg_replace( '/\D+/', '', (string) $phone );
	$out   = array(
		'email'    => $email,
		'phone'    => $phone,
		'promoted' => array(),
		'skipped'  => array(),
		'issues'   => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['issues'][] = 'Invalid email.';
		return $out;
	}

	$force_order_ids = array_map( 'intval', (array) $force_order_ids );
	$force_order_ids = array_values( array_filter( $force_order_ids ) );

	$orders = ttp_bulk_collect_customer_orders( $email, 0, $phone );
	$by_id  = array();
	foreach ( $orders as $order ) {
		if ( $order instanceof WC_Order ) {
			$by_id[ (int) $order->get_id() ] = $order;
		}
	}
	foreach ( $force_order_ids as $force_id ) {
		if ( isset( $by_id[ $force_id ] ) || ! function_exists( 'wc_get_order' ) ) {
			continue;
		}
		$forced_order = wc_get_order( $force_id );
		if ( $forced_order instanceof WC_Order ) {
			$by_id[ $force_id ] = $forced_order;
		}
	}
	$orders = array_values( $by_id );
	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$oid    = (int) $order->get_id();
		$forced = in_array( $oid, $force_order_ids, true );
		if ( ! in_array( $order->get_status(), array( 'cancelled', 'failed' ), true ) ) {
			$out['skipped'][] = array(
				'order_id' => $oid,
				'status'   => $order->get_status(),
				'reason'   => 'not_cancelled_or_failed',
			);
			continue;
		}
		$has_payment = function_exists( 'ttp_order_has_gateway_payment' ) && ttp_order_has_gateway_payment( $order );
		if ( $forced && ! $has_payment ) {
			$order->update_meta_data( '_ttp_gateway_paid', 'yes' );
			if ( ! $order->get_date_paid() ) {
				$order->set_date_paid( time() );
			}
			$order->save();
			$has_payment = true;
			$out['promoted'][] = array(
				'order_id' => $oid,
				'action'   => 'forced_gateway_paid_meta',
			);
		}
		if ( ! $has_payment ) {
			$out['skipped'][] = array(
				'order_id' => $oid,
				'status'   => $order->get_status(),
				'reason'   => 'no_gateway_payment',
			);
			continue;
		}
		if ( function_exists( 'ttp_maybe_promote_gateway_paid_order' ) && ttp_maybe_promote_gateway_paid_order(
			$order,
			'Bulk repair: gateway payment confirmed; promoted for TCY CRM sync.'
		) ) {
			$out['promoted'][] = array(
				'order_id' => $oid,
				'action'   => 'status_promoted_to_processing',
			);
		}
	}

	$out['status'] = ! empty( $out['promoted'] ) ? 'ok' : ( empty( $out['issues'] ) ? 'no_action' : 'error' );
	return $out;
}

/**
 * Full JBIMS repair: TCY link, add_course, portal row, and login-panel verification.
 *
 * @param string     $email       Billing email.
 * @param string|int $tcy_numeric Optional plain TCY id from export.
 * @param string     $phone       Optional phone digits.
 * @param int[]      $force_order_ids Confirmed-paid order IDs to promote.
 * @return array<string,mixed>
 */
function ttp_bulk_complete_jbims_student_fix( $email, $tcy_numeric = '', $phone = '', $force_order_ids = array() ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$out   = array(
		'email'  => $email,
		'steps'  => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['error'] = 'Invalid email.';
		return $out;
	}

	$tcy_numeric = preg_replace( '/\D+/', '', (string) $tcy_numeric );
	if ( $tcy_numeric !== '' ) {
		$out['link'] = ttp_bulk_link_known_tcy_jbims( $email, $tcy_numeric, $phone );
		$out['steps'][] = 'link_known_tcy';
	}

	$out['promote'] = ttp_bulk_promote_gateway_paid_orders_for_customer( $email, $phone, $force_order_ids );
	if ( ! empty( $out['promote']['promoted'] ) ) {
		$out['steps'][] = 'promote_gateway_paid';
	}

	$out['deep'] = ttp_bulk_deep_fix_jbims_student( $email, $phone );
	$out['steps'][] = 'deep_fix';

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	if ( $wp_uid > 0 && function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		$out['portal_enrollments'] = ttp_sync_portal_enrollments_for_user( $wp_uid, $email );
	}
	if ( $wp_uid > 0 && function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
	}

	$panel_ok = ! empty( $out['enrolled_panel'] );
	$tcy_ok   = ! empty( $out['deep']['tcy_user_id'] ) || ! empty( $out['link']['tcy_user_id'] );
	$needs_fresh = ! $panel_ok || ! $tcy_ok;
	if ( $panel_ok && ! empty( $out['deep']['orders'] ) ) {
		$elite_order = false;
		foreach ( (array) $out['deep']['orders'] as $ord ) {
			if ( empty( $ord['has_jbims'] ) ) {
				continue;
			}
			foreach ( (array) ( $ord['lines'] ?? array() ) as $line ) {
				if ( preg_match( '/elite/i', (string) $line ) ) {
					$elite_order = true;
					break 2;
				}
			}
		}
		if ( $elite_order ) {
			foreach ( (array) $out['enrolled_panel'] as $panel_row ) {
				$pid = (int) ( $panel_row['product_id'] ?? 0 );
				if ( class_exists( 'TTP_Catalog_Seed' ) ) {
					$elite_pid = (int) TTP_Catalog_Seed::get_product_id_by_slug( 'jbims-mfin-mhrd-bootcamp-elite' );
					if ( $elite_pid > 0 && $pid !== $elite_pid ) {
						$needs_fresh = true;
						break;
					}
				}
			}
		}
	}
	if ( $needs_fresh && ! empty( $out['deep']['orders'] ) ) {
		$elite = false;
		foreach ( (array) $out['deep']['orders'] as $ord ) {
			if ( empty( $ord['has_jbims'] ) ) {
				continue;
			}
			foreach ( (array) ( $ord['lines'] ?? array() ) as $line ) {
				if ( preg_match( '/elite/i', (string) $line ) ) {
					$elite = true;
					break 2;
				}
			}
		}
		$out['fresh_register'] = ttp_bulk_fresh_register_jbims_student( $email, $phone, $elite );
		$out['steps'][]        = 'fresh_register';
		if ( ! empty( $out['fresh_register']['enrolled_panel'] ) ) {
			$out['enrolled_panel'] = $out['fresh_register']['enrolled_panel'];
		}
		if ( ! empty( $out['fresh_register']['tcy_user_id'] ) ) {
			$out['deep']['tcy_user_id'] = $out['fresh_register']['tcy_user_id'];
		}
		$panel_ok = ! empty( $out['enrolled_panel'] );
		$tcy_ok   = ! empty( $out['deep']['tcy_user_id'] ) || ! empty( $out['link']['tcy_user_id'] );
	}

	if ( $wp_uid > 0 && function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		$out['portal_enrollments'] = ttp_sync_portal_enrollments_for_user( $wp_uid, $email );
	}
	if ( $wp_uid > 0 && function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
		$panel_ok              = ! empty( $out['enrolled_panel'] );
	}

	$out['status'] = ( $panel_ok && $tcy_ok ) ? 'ok' : ( $panel_ok ? 'panel_ok' : 'needs_review' );

	return $out;
}

/**
 * Find WooCommerce orders for a customer (any status).
 *
 * @param string $email  Email.
 * @param string $phone  Phone digits.
 * @return array<string,mixed>
 */
function ttp_bulk_find_customer_orders_any_status( $email = '', $phone = '' ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone = preg_replace( '/\D+/', '', (string) $phone );
	$out   = array(
		'email'  => $email,
		'phone'  => $phone,
		'orders' => array(),
	);

	if ( ! function_exists( 'wc_get_orders' ) ) {
		return $out;
	}

	$candidates = array();
	if ( $email !== '' && is_email( $email ) ) {
		$candidates = array_merge(
			$candidates,
			(array) wc_get_orders(
				array(
					'billing_email' => $email,
					'limit'         => 20,
					'status'        => 'any',
					'orderby'       => 'date',
					'order'         => 'DESC',
				)
			)
		);
	}

	if ( $phone !== '' ) {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $phone ) . '%';
		$ids  = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT order_id FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key IN ('_billing_phone','_shipping_phone') AND meta_value LIKE %s ORDER BY order_id DESC LIMIT 20",
				$like
			)
		);
		if ( empty( $ids ) ) {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_billing_phone','_shipping_phone') AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 20",
					$like
				)
			);
		}
		foreach ( (array) $ids as $oid ) {
			$order = wc_get_order( (int) $oid );
			if ( $order instanceof WC_Order ) {
				$candidates[] = $order;
			}
		}
	}

	$seen = array();
	foreach ( $candidates as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$oid = (int) $order->get_id();
		if ( isset( $seen[ $oid ] ) ) {
			continue;
		}
		$seen[ $oid ] = true;
		$lines        = array();
		foreach ( $order->get_items() as $item ) {
			$lines[] = (string) $item->get_name();
		}
		$out['orders'][] = array(
			'order_id'       => $oid,
			'status'         => $order->get_status(),
			'billing_email'  => (string) $order->get_billing_email(),
			'billing_phone'  => (string) $order->get_billing_phone(),
			'customer_id'    => (int) $order->get_user_id(),
			'qualifies_tcy'  => function_exists( 'ttp_order_qualifies_for_tcy_actions' ) ? ttp_order_qualifies_for_tcy_actions( $order ) : null,
			'tcy_user_id'    => function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '',
			'lines'          => $lines,
		);
	}

	return $out;
}

/**
 * Remove JBIMS portal/mapping rows for customers who never purchased JBIMS.
 *
 * @param string $email Billing email.
 * @return array<string,mixed>
 */
function ttp_bulk_revoke_false_jbims_for_customer( $email ) {
	global $wpdb;

	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$out   = array(
		'email'   => $email,
		'actions' => array(),
		'issues'  => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['issues'][] = 'Invalid email.';
		$out['status']   = 'error';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$out['wp_user_id'] = $wp_uid;

	$has_jbims = function_exists( 'ttp_user_has_jbims_entitlement' ) && ttp_user_has_jbims_entitlement( $wp_uid, $email );
	$out['has_jbims_entitlement'] = $has_jbims;

	if ( $has_jbims ) {
		$out['status'] = 'skipped';
		$out['issues'][] = 'Customer has confirmed JBIMS entitlement; no revoke needed.';
		return $out;
	}

	$tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
		? ttp_get_canonical_tcy_user_id( $wp_uid, $email )
		: '';

	if ( $wp_uid > 0 ) {
		$manual = get_user_meta( $wp_uid, '_ttp_portal_enrollments', true );
		if ( is_array( $manual ) && ! empty( $manual ) ) {
			$filtered = array();
			foreach ( $manual as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$tcy_course = (string) ( $row['tcy_course_id'] ?? '' );
				$name       = (string) ( $row['name'] ?? '' );
				if ( '90334' === $tcy_course || preg_match( '/jbims|mfin|mhrd|bootcamp/i', $name ) ) {
					continue;
				}
				$filtered[] = $row;
			}
			if ( empty( $filtered ) ) {
				delete_user_meta( $wp_uid, '_ttp_portal_enrollments' );
				$out['actions'][] = 'Deleted false JBIMS portal enrollment meta.';
			} elseif ( count( $filtered ) !== count( $manual ) ) {
				update_user_meta( $wp_uid, '_ttp_portal_enrollments', $filtered );
				$out['actions'][] = 'Stripped JBIMS rows from portal enrollment meta.';
			}
		} else {
			delete_user_meta( $wp_uid, '_ttp_portal_enrollments' );
		}
	}

	$where = array( 'tcy_course_id = %s' );
	$args  = array( '90334' );
	if ( $wp_uid > 0 ) {
		$where[] = 'wp_user_id = %d';
		$args[]  = $wp_uid;
	} elseif ( $tcy_id !== '' ) {
		$where[] = 'tcy_user_id = %s';
		$args[]  = $tcy_id;
	} else {
		$student = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ttp_students WHERE email = %s ORDER BY id DESC LIMIT 1",
				$email
			)
		);
		if ( $student && ! empty( $student->tcy_user_id ) ) {
			$where[] = 'tcy_user_id = %s';
			$args[]  = (string) $student->tcy_user_id;
		}
	}

	if ( count( $where ) > 1 ) {
		$sql  = "SELECT id, order_id FROM {$wpdb->prefix}ttp_order_mapping WHERE " . implode( ' AND ', $where );
		$maps = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		foreach ( (array) $maps as $map ) {
			$order_id = (int) $map->order_id;
			$order    = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
			if ( $order instanceof WC_Order && function_exists( 'ttp_order_has_jbims_line_item' ) && ttp_order_has_jbims_line_item( $order ) ) {
				continue;
			}
			$wpdb->delete( $wpdb->prefix . 'ttp_order_mapping', array( 'id' => (int) $map->id ), array( '%d' ) );
			$out['actions'][] = 'Removed false JBIMS mapping row #' . (int) $map->id . ' (order #' . $order_id . ').';
		}
	}

	if ( $tcy_id !== '' && class_exists( 'TTP_TCY_API' ) ) {
		$api = new TTP_TCY_API();
		$api->remove_course( $tcy_id, '90334', '100000' );
		$out['actions'][] = 'Requested TCY remove_course for unauthorized 90334.';
	}

	if ( $wp_uid > 0 ) {
		delete_user_meta( $wp_uid, '_ttp_jbims_entitlement' );
		delete_user_meta( $wp_uid, '_ttp_jbims_sub_cat' );
	}

	if ( $wp_uid > 0 && function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		ttp_sync_portal_enrollments_for_user( $wp_uid, $email );
		$out['actions'][] = 'Re-synced portal enrollments.';
	}
	if ( $wp_uid > 0 && function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
	}

	$out['status'] = 'ok';
	return $out;
}

/**
 * Probe TCY and grant JBIMS entitlement when course 90334 is already on the account.
 *
 * @param string $email Billing email.
 * @param string $sub_cat Optional pack id.
 * @return array<string,mixed>
 */
function ttp_bulk_maybe_grant_jbims_entitlement_from_tcy( $email, $sub_cat = '38081' ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$out   = array(
		'email'   => $email,
		'actions' => array(),
		'issues'  => array(),
	);

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	if ( $wp_uid < 1 ) {
		$out['issues'][] = 'No WordPress user.';
		$out['status']   = 'error';
		return $out;
	}

	if ( function_exists( 'ttp_user_has_jbims_entitlement' ) && ttp_user_has_jbims_entitlement( $wp_uid, $email ) ) {
		$out['status'] = 'already_entitled';
		return $out;
	}

	$tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
		? ttp_get_canonical_tcy_user_id( $wp_uid, $email )
		: '';
	if ( $tcy_id === '' ) {
		$out['issues'][] = 'No TCY account linked.';
		$out['status']   = 'no_tcy';
		return $out;
	}

	if ( ! function_exists( 'ttp_tcy_call_add_course_with_retries' ) ) {
		$out['status'] = 'error';
		return $out;
	}

	$attempt = ttp_tcy_call_add_course_with_retries( $tcy_id, '90334', '100000', 0, 0, $sub_cat );
	$outcome = function_exists( 'ttp_tcy_add_course_outcome' )
		? ttp_tcy_add_course_outcome( $attempt['response'] ?? array() )
		: 'failed';

	if ( in_array( $outcome, array( 'success', 'pack_conflict' ), true ) && function_exists( 'ttp_grant_jbims_entitlement' ) ) {
		// Only grant when this helper is invoked from an explicit JBIMS link/fix — not generic repair.
		ttp_grant_jbims_entitlement( $wp_uid, $sub_cat );
		$out['actions'][] = 'Granted JBIMS entitlement from TCY (' . $outcome . ').';
		$out['status']    = 'ok';
	} else {
		$out['issues'][] = 'TCY add_course outcome: ' . $outcome;
		$out['status']   = 'no_jbims_on_tcy';
	}

	return $out;
}

/**
 * Create or locate a WordPress user from guest-checkout billing data.
 *
 * @param string $email         Billing email.
 * @param string $phone         Optional phone digits.
 * @param string $username_hint Optional preferred username.
 * @return array<string,mixed>
 */
function ttp_bulk_ensure_wp_user_for_customer( $email, $phone = '', $username_hint = '' ) {
	global $wpdb;

	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$phone = preg_replace( '/\D+/', '', (string) $phone );
	$out   = array(
		'email'   => $email,
		'phone'   => $phone,
		'actions' => array(),
		'issues'  => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['status'] = 'error';
		$out['issues'][] = 'Invalid email.';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	if ( $wp_uid > 0 ) {
		$out['wp_user_id'] = $wp_uid;
		$out['status']     = 'exists';
		return $out;
	}

	$orders = ttp_bulk_collect_customer_orders( $email, 0, $phone );
	if ( empty( $orders ) ) {
		$out['status']     = 'no_orders';
		$out['issues'][]   = 'No WooCommerce orders found for this email/phone — cannot create WP account.';
		return $out;
	}

	$source = null;
	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order ) ) {
			$source = $order;
			break;
		}
	}
	if ( ! $source ) {
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order && in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ), true ) ) {
				$source = $order;
				break;
			}
		}
	}
	if ( ! $source ) {
		$source = $orders[0];
	}

	$full_name = trim( (string) $source->get_billing_first_name() . ' ' . (string) $source->get_billing_last_name() );
	if ( $full_name === '' && function_exists( 'ttp_resolve_customer_full_name' ) ) {
		$full_name = ttp_resolve_customer_full_name( 0, $source );
	}
	if ( $full_name === '' || is_email( $full_name ) ) {
		$full_name = strstr( $email, '@', true ) ?: 'Student';
	}

	$username = sanitize_user( (string) $username_hint, true );
	if ( $username === '' || username_exists( $username ) ) {
		$base = sanitize_user( strstr( $email, '@', true ) ?: 'student', true );
		if ( $base === '' ) {
			$base = 'student';
		}
		$username = $base;
		$suffix   = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $suffix;
			++$suffix;
		}
	}

	$password = wp_generate_password( 16, true, true );
	$wp_uid   = wp_create_user( $username, $password, $email );
	if ( is_wp_error( $wp_uid ) ) {
		$out['status']   = 'error';
		$out['issues'][] = $wp_uid->get_error_message();
		return $out;
	}

	$wp_uid = (int) $wp_uid;
	wp_update_user(
		array(
			'ID'           => $wp_uid,
			'display_name' => $full_name,
			'first_name'   => (string) $source->get_billing_first_name(),
			'last_name'    => (string) $source->get_billing_last_name(),
		)
	);

	$billing_phone = preg_replace( '/\D+/', '', (string) $source->get_billing_phone() );
	if ( $billing_phone !== '' ) {
		update_user_meta( $wp_uid, 'billing_phone', $billing_phone );
		update_user_meta( $wp_uid, 'mobile', $billing_phone );
	}
	update_user_meta( $wp_uid, 'ttp_full_name', $full_name );

	$wpdb->insert(
		$wpdb->prefix . 'ttp_students',
		array(
			'wp_user_id' => $wp_uid,
			'full_name'  => $full_name,
			'email'      => $email,
			'mobile'     => $billing_phone,
			'username'   => $username,
		)
	);

	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( (int) $order->get_user_id() < 1 ) {
			$order->set_customer_id( $wp_uid );
			$order->save();
		}
	}

	if ( function_exists( 'ttp_link_guest_orders_to_user_by_email' ) ) {
		ttp_link_guest_orders_to_user_by_email( $wp_uid );
	}

	$out['wp_user_id'] = $wp_uid;
	$out['username']   = $username;
	$out['order_id']   = (int) $source->get_id();
	$out['actions'][]  = 'Created WP user #' . $wp_uid . ' from order #' . (int) $source->get_id() . '.';
	$out['status']     = 'created';
	return $out;
}

/**
 * Full login-panel repair: link orders, sync TCY, restore entitlements, verify panel.
 *
 * @param string $email  Billing email.
 * @param bool   $repair Run TCY sync repairs.
 * @return array<string,mixed>
 */
function ttp_bulk_repair_customer_panel( $email, $repair = true ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	$out   = array(
		'email'   => $email,
		'actions' => array(),
		'issues'  => array(),
	);

	if ( $email === '' || ! is_email( $email ) ) {
		$out['status'] = 'error';
		return $out;
	}

	$user   = get_user_by( 'email', $email );
	$wp_uid = $user instanceof WP_User ? (int) $user->ID : 0;
	$out['wp_user_id'] = $wp_uid;

	if ( $wp_uid < 1 && $repair ) {
		$ensure = ttp_bulk_ensure_wp_user_for_customer( $email );
		$out['ensure_wp_user'] = $ensure;
		if ( ! empty( $ensure['wp_user_id'] ) ) {
			$wp_uid            = (int) $ensure['wp_user_id'];
			$out['wp_user_id'] = $wp_uid;
			$user              = get_user_by( 'id', $wp_uid );
			if ( ! empty( $ensure['actions'] ) ) {
				$out['actions'] = array_merge( $out['actions'], (array) $ensure['actions'] );
			}
		} elseif ( ( $ensure['status'] ?? '' ) === 'no_orders' ) {
			$out['issues'][] = 'No WordPress account and no guest order found for this email.';
		}
	}

	if ( $wp_uid < 1 ) {
		$out['status'] = 'no_wp_user';
		return $out;
	}

	if ( function_exists( 'ttp_link_guest_orders_to_user_by_email' ) ) {
		$linked = ttp_link_guest_orders_to_user_by_email( $wp_uid );
		if ( $linked > 0 ) {
			$out['actions'][] = 'Linked ' . $linked . ' guest order(s) to WP user.';
		}
	}

	if ( $repair && class_exists( 'TTP_Catalog_Seed' ) ) {
		TTP_Catalog_Seed::repair_all_tcy_meta();
	}

	if ( $repair && function_exists( 'ttp_tcy_repair_customer_enrollments' ) ) {
		$out['tcy_repair'] = ttp_tcy_repair_customer_enrollments( $wp_uid, $email );
		$out['actions'][]  = 'Ran TCY enrollment repair.';
	}

	if ( $repair && function_exists( 'ttp_get_qualifying_orders_for_user' ) && function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
		foreach ( ttp_get_qualifying_orders_for_user( $wp_uid ) as $order ) {
			if ( $order instanceof WC_Order ) {
				ttp_sync_tcy_courses_for_order( $order );
			}
		}
		$out['actions'][] = 'Synced qualifying order courses.';
	}

	if ( function_exists( 'ttp_user_has_jbims_purchase' ) && ttp_user_has_jbims_purchase( $wp_uid, $email ) && function_exists( 'ttp_grant_jbims_entitlement' ) ) {
		$sub = '38081';
		foreach ( ttp_get_qualifying_orders_for_user( $wp_uid ) as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( function_exists( 'ttp_order_item_is_jbims_product' ) && ttp_order_item_is_jbims_product( $item ) ) {
					$sub = preg_match( '/elite/i', (string) $item->get_name() ) ? '38033' : '38081';
					break 2;
				}
			}
		}
		ttp_grant_jbims_entitlement( $wp_uid, $sub );
		$out['actions'][] = 'JBIMS entitlement set from WooCommerce purchase.';
	} elseif ( $repair && function_exists( 'ttp_purge_unauthorized_jbims_for_user' ) ) {
		ttp_purge_unauthorized_jbims_for_user( $wp_uid, $email );
		$out['actions'][] = 'Purged unauthorized JBIMS (no purchase/grant).';
	}

	if ( function_exists( 'ttp_sync_tcy_enrollments_for_user_on_account_view' ) ) {
		delete_transient( 'ttp_acct_sync_' . $wp_uid );
		ttp_sync_tcy_enrollments_for_user_on_account_view( $wp_uid );
		$out['actions'][] = 'Account-view TCY sync completed.';
	}

	if ( function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
		$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
	}

	if ( $repair && empty( $out['enrolled_panel'] ) && function_exists( 'ttp_bulk_complete_jbims_student_fix' ) ) {
		$out['jbims_complete'] = ttp_bulk_complete_jbims_student_fix( $email );
		$out['actions'][]        = 'Ran full JBIMS student fix (TCY register + add_course).';
		if ( function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
			$out['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( $wp_uid );
		}
	}

	$out['status'] = ! empty( $out['enrolled_panel'] ) ? 'ok' : 'empty_panel';
	return $out;
}

/**
 * Batch repair login panels for many customers.
 *
 * @param string[] $emails Email list.
 * @param bool     $repair Run repairs.
 * @return array<string,mixed>
 */
function ttp_bulk_repair_customer_panels( $emails, $repair = true ) {
	$summary = array(
		'total'  => 0,
		'ok'     => 0,
		'empty'  => 0,
		'errors' => 0,
		'rows'   => array(),
	);

	foreach ( (array) $emails as $email ) {
		$row = ttp_bulk_repair_customer_panel( (string) $email, $repair );
		$summary['rows'][] = $row;
		++$summary['total'];
		if ( ! empty( $row['enrolled_panel'] ) ) {
			++$summary['ok'];
		} elseif ( in_array( $row['status'] ?? '', array( 'error', 'no_wp_user' ), true ) ) {
			++$summary['errors'];
		} else {
			++$summary['empty'];
		}
	}

	return $summary;
}

/**
 * Revoke false JBIMS for every WP user without a JBIMS purchase/grant.
 *
 * @param int $limit Max users to scan.
 * @return array<string,mixed>
 */
function ttp_bulk_revoke_all_false_jbims( $limit = 500 ) {
	global $wpdb;

	$limit   = max( 1, min( 2000, (int) $limit ) );
	$summary = array(
		'scanned'  => 0,
		'revoked'  => 0,
		'kept'     => 0,
		'rows'     => array(),
	);

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT u.ID FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID
			WHERE m.meta_key IN ('_ttp_tcy_user_id','_ttp_jbims_entitlement','_ttp_portal_enrollments')
			ORDER BY u.ID DESC
			LIMIT %d",
			$limit
		)
	);

	foreach ( (array) $user_ids as $uid ) {
		$user = get_user_by( 'id', (int) $uid );
		if ( ! $user instanceof WP_User ) {
			continue;
		}
		++$summary['scanned'];
		$email = sanitize_email( (string) $user->user_email );
		if ( function_exists( 'ttp_user_has_jbims_entitlement' ) && ttp_user_has_jbims_entitlement( (int) $uid, $email ) ) {
			++$summary['kept'];
			continue;
		}
		$row = ttp_bulk_revoke_false_jbims_for_customer( $email );
		if ( ! empty( $row['actions'] ) ) {
			++$summary['revoked'];
		}
		$summary['rows'][] = array(
			'email'   => $email,
			'actions' => $row['actions'] ?? array(),
			'panel'   => $row['enrolled_panel'] ?? array(),
		);
	}

	return $summary;
}

/**
 * Find WP users / orders matching an email fragment.
 *
 * @param string $fragment Partial email.
 * @return array<string,mixed>
 */
function ttp_bulk_find_users_by_email_fragment( $fragment ) {
	global $wpdb;

	$fragment = strtolower( trim( sanitize_text_field( (string) $fragment ) ) );
	$out      = array(
		'fragment' => $fragment,
		'users'    => array(),
		'orders'   => array(),
	);

	if ( $fragment === '' ) {
		return $out;
	}

	$like = '%' . $wpdb->esc_like( $fragment ) . '%';
	$ids  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, user_email, display_name FROM {$wpdb->users} WHERE user_email LIKE %s OR display_name LIKE %s LIMIT 20",
			$like,
			$like
		)
	);
	foreach ( (array) $ids as $row ) {
		$uid = (int) $row->ID;
		$out['users'][] = array(
			'wp_user_id' => $uid,
			'email'      => (string) $row->user_email,
			'name'       => (string) $row->display_name,
			'panel'      => function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' )
				? ttp_get_user_enrolled_courses_for_login_panel( $uid )
				: array(),
		);
	}

	if ( function_exists( 'wc_get_orders' ) ) {
		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT order_id FROM {$wpdb->prefix}wc_orders WHERE billing_email LIKE %s ORDER BY order_id DESC LIMIT 20",
				$like
			)
		);
		if ( empty( $order_ids ) ) {
			$order_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_billing_email' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 20",
					$like
				)
			);
		}
		foreach ( (array) $order_ids as $oid ) {
			$order = wc_get_order( (int) $oid );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$lines = array();
			foreach ( $order->get_items() as $item ) {
				$lines[] = (string) $item->get_name();
			}
			$out['orders'][] = array(
				'order_id' => (int) $order->get_id(),
				'email'    => (string) $order->get_billing_email(),
				'status'   => $order->get_status(),
				'lines'    => $lines,
			);
		}
	}

	return $out;
}

add_action(
	'admin_menu',
	static function () {
		add_submenu_page(
			'ttp-dashboard',
			'Bulk User Ops',
			'Bulk User Ops',
			'manage_options',
			'ttp-bulk-user-ops',
			'ttp_bulk_user_ops_admin_page'
		);
	}
);

/**
 * Admin page renderer.
 *
 * @return void
 */
function ttp_bulk_user_ops_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden', 'ttp-woocommerce' ) );
	}

	$report = null;

	if ( isset( $_POST['ttp_bulk_revoke_all'] ) && check_admin_referer( 'ttp_bulk_user_ops' ) ) {
		$report = array(
			'type' => 'revoke_all',
			'data' => ttp_bulk_revoke_all_affiliates(),
		);
	}

	if ( isset( $_POST['ttp_bulk_revoke'] ) && check_admin_referer( 'ttp_bulk_user_ops' ) ) {
		$force = isset( $_POST['force_revoke_emails'] )
			? preg_split( '/[\s,;]+/', sanitize_textarea_field( wp_unslash( $_POST['force_revoke_emails'] ) ) )
			: array();
		$report = array(
			'type' => 'revoke',
			'data' => ttp_bulk_revoke_unauthorized_affiliates( $force ),
		);
	}

	if ( isset( $_POST['ttp_bulk_audit'] ) && check_admin_referer( 'ttp_bulk_user_ops' ) ) {
		$raw    = isset( $_POST['audit_emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['audit_emails'] ) ) : '';
		$emails = preg_split( '/[\s,;]+/', $raw );
		$repair = ! empty( $_POST['run_repair'] );
		$report = array(
			'type' => 'audit',
			'data' => ttp_bulk_audit_customers( $emails, $repair ),
		);
	}

	if ( isset( $_POST['ttp_bulk_jbims_renew'] ) && check_admin_referer( 'ttp_bulk_user_ops' ) ) {
		$days   = isset( $_POST['jbims_days'] ) ? max( 1, (int) $_POST['jbims_days'] ) : 5;
		$raw    = isset( $_POST['jbims_extra_emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jbims_extra_emails'] ) ) : '';
		$emails = preg_split( '/[\s,;]+/', $raw );
		$report = array(
			'type' => 'jbims_renew',
			'data' => ttp_bulk_renew_recent_jbims_orders( $days, $emails ),
		);
	}

	$unauthorized = ttp_bulk_find_unauthorized_affiliates();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bulk User Operations', 'ttp-woocommerce' ); ?></h1>

		<h2><?php esc_html_e( 'Unauthorized affiliate access', 'ttp-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Users below have referral access but were not explicitly enabled by an admin (no granted_by audit).', 'ttp-woocommerce' ); ?></p>
		<table class="widefat striped" style="max-width:960px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'ttp-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Email', 'ttp-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Source', 'ttp-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $unauthorized ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'None found.', 'ttp-woocommerce' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $unauthorized as $u ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $u['name'] ); ?></td>
						<td><?php echo esc_html( (string) $u['email'] ); ?></td>
						<td><?php echo esc_html( (string) $u['source'] ); ?><?php echo ! empty( $u['legacy_role'] ) ? ' (legacy affiliate role)' : ''; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<form method="post" style="margin:24px 0;padding:16px;background:#fff;border:1px solid #f5c2c7;border-radius:8px;max-width:720px;">
			<?php wp_nonce_field( 'ttp_bulk_user_ops' ); ?>
			<h3 style="color:#b91c1c;"><?php esc_html_e( 'Revoke ALL affiliate access', 'ttp-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Removes referral access from every user. Re-enable only the people you choose under Affiliate Hub → Affiliate Links.', 'ttp-woocommerce' ); ?></p>
			<p><input type="submit" name="ttp_bulk_revoke_all" class="button" style="color:#b91c1c;border-color:#b91c1c;" value="<?php esc_attr_e( 'Revoke everyone now', 'ttp-woocommerce' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Remove referral access from ALL users?', 'ttp-woocommerce' ) ); ?>');"/></p>
		</form>

		<form method="post" style="margin:24px 0;padding:16px;background:#fff;border:1px solid #ccc;border-radius:8px;max-width:720px;">
			<?php wp_nonce_field( 'ttp_bulk_user_ops' ); ?>
			<h3><?php esc_html_e( 'Revoke unauthorized affiliate access', 'ttp-woocommerce' ); ?></h3>
			<p>
				<label><?php esc_html_e( 'Always revoke these emails (one per line)', 'ttp-woocommerce' ); ?></label><br/>
				<textarea name="force_revoke_emails" rows="3" class="large-text" placeholder="2025anshitripathi04@gmail.com"></textarea>
			</p>
			<p><input type="submit" name="ttp_bulk_revoke" class="button button-primary" value="<?php esc_attr_e( 'Revoke all unauthorized + forced emails', 'ttp-woocommerce' ); ?>"/></p>
		</form>

		<form method="post" style="margin:24px 0;padding:16px;background:#fff;border:1px solid #ccc;border-radius:8px;max-width:720px;">
			<?php wp_nonce_field( 'ttp_bulk_user_ops' ); ?>
			<h3><?php esc_html_e( 'Renew JBIMS Bootcamp / Elite (last N days)', 'ttp-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Re-triggers TCY register/add_course with course_id 90334 and correct pack (38081 Bootcamp / 38033 Elite). Use when TCY API was not triggered or wrong course id was used.', 'ttp-woocommerce' ); ?></p>
			<p><label><?php esc_html_e( 'Days lookback', 'ttp-woocommerce' ); ?></label><br/>
			<input type="number" name="jbims_days" value="5" min="1" max="30" class="small-text"/></p>
			<p><label><?php esc_html_e( 'Also renew these emails (one per line)', 'ttp-woocommerce' ); ?></label><br/>
			<textarea name="jbims_extra_emails" rows="3" class="large-text" placeholder="utkarshghatol42@gmail.com&#10;mahrinsayed@gmail.com"></textarea></p>
			<p><input type="submit" name="ttp_bulk_jbims_renew" class="button button-primary" value="<?php esc_attr_e( 'Renew JBIMS enrollments', 'ttp-woocommerce' ); ?>"/></p>
		</form>

		<form method="post" style="margin:24px 0;padding:16px;background:#fff;border:1px solid #ccc;border-radius:8px;max-width:720px;">
			<?php wp_nonce_field( 'ttp_bulk_user_ops' ); ?>
			<h3><?php esc_html_e( 'Audit / repair TCY enrollments', 'ttp-woocommerce' ); ?></h3>
			<p>
				<label><?php esc_html_e( 'Customer emails (one per line)', 'ttp-woocommerce' ); ?></label><br/>
				<textarea name="audit_emails" rows="10" class="large-text" placeholder="student@example.com"></textarea>
			</p>
			<p><label><input type="checkbox" name="run_repair" value="1" checked="checked"/> <?php esc_html_e( 'Run TCY repair (add_course loop + JBIMS mapping fix)', 'ttp-woocommerce' ); ?></label></p>
			<p><input type="submit" name="ttp_bulk_audit" class="button button-primary" value="<?php esc_attr_e( 'Audit / repair', 'ttp-woocommerce' ); ?>"/></p>
		</form>

		<?php if ( is_array( $report ) ) : ?>
			<h2><?php esc_html_e( 'Last run result', 'ttp-woocommerce' ); ?></h2>
			<pre style="background:#f6f7f7;padding:16px;border-radius:8px;overflow:auto;max-height:640px;"><?php echo esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT ) ); ?></pre>
		<?php endif; ?>
	</div>
	<?php
}
