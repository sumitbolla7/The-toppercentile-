<?php
/**
 * One-time bulk operations runner. Delete this file after use.
 *
 * Usage: https://thetoppercentile.co.in/ttp-bulk-run-once.php?key=CHANGE_ME&action=all
 */

define( 'TTP_BULK_RUN_KEY', 'ttp_bulk_jun12_2026_x7k9' );

$expected = TTP_BULK_RUN_KEY;
$provided = isset( $_GET['key'] ) ? (string) $_GET['key'] : '';

if ( $provided === '' || ! hash_equals( $expected, $provided ) ) {
	http_response_code( 403 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

$wp_load = __DIR__ . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	http_response_code( 500 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'wp-load.php not found' ) );
	exit;
}

require_once $wp_load;

if ( ! function_exists( 'ttp_bulk_revoke_unauthorized_affiliates' ) || ! function_exists( 'ttp_bulk_audit_customers' ) ) {
	http_response_code( 500 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'ttp-bulk-user-ops mu-plugin not loaded' ) );
	exit;
}

$emails = array(
	'muskan.cet28@gmail.com',
	'dinukatke@gmail.com',
	'rushikeshkahat4398@gmail.com',
	'manvimalunjkar04@gmail.com',
	'samikshameshram2001@gmail.com',
	'amolakevadkar@gmail.com',
	'koshtitejaswini25@gmail.com',
	'adityaajmi99@gmail.com',
	'chaitanyawankhade8@gmail.com',
	'dr.roshni1singh@gmail.com',
	'omkarwaghamare001@gmail.com',
	'sekapo3692@preparmy.com',
	'leads0219@gmail.com',
	'harsh18tandekar@gmail.com',
	'sanghaviharsha189@gmail.com',
	'sagargadhari27@gmail.com',
	'pragatilawand1@gmail.com',
	'shivamghule1997@gmail.com',
	'bhaktipawar1309@gmail.com',
	'pranitaw514@gmail.com',
	'namannavle13@gmail.com',
	'ruturajvshedge@gmail.com',
	'enthusiasticcitizen898@gmail.com',
	'manejane9o8adi2@gmail.com',
	'agarwadkarshreyas7@gmail.com',
	'shahjinay495@gmail.com',
	'karadshreyas0@gmail.com',
	'katkadetejas01@gmail.com',
	'shubhrath78@gmail.com',
	'sourabhon2401@gmail.com',
	'kalokaraditya39@gmail.com',
	'prithvivinayakp@gmail.com',
	'atharvailawar00@gmail.com',
	'htokalwar@gmail.com',
	'samikshajambhulkar9716@gmail.com',
	'sanskrutiburkule05@gmail.com',
	'janhvikamat9@gmail.com',
	'shreyashmeshram80@gmail.com',
	'2025anshitripathi04@gmail.com',
	'mahrinsayed@gmail.com',
	'anushkadgupta@gmail.com',
	'devkhetan001@gmail.com',
	'aditya.saharkar1503@gmail.com',
	'parth.mokal.ai@gmail.com',
	'yassh480@gmail.com',
	'piyushbawane8@gmail.com',
	'vaishukakad1708@gmail.com',
	'tejeshwarsingh01971@gmail.com',
	'komalshastri23@gmail.com',
	'tejaskpatil2003@gmail.com',
	'ankitaanvi2002@gmail.com',
	'mitanshukolhe@gmail.com',
	'sarralitian@gmail.com',
	'shririshi30@gmail.com',
	'akshatagawli26@gmail.com',
	'ashervedant@gmail.com',
	'aditipandey2313@gmail.com',
	'pushkarshete22@gmail.com',
	'vedantkkonde@gmail.com',
	'anujaauti2@gmail.com',
	'geetarenge@gmail.com',
	'parthraut46@gmail.com',
	'berdediya@gmail.com',
	'vanitanutri55@gmail.com',
	'aishwaryakarle17082006@gmail.com',
	'ttabhishektiwari@gmail.com',
	'thisisravankar@gmail.com',
	'mikhilnda4@gmail.com',
	'manish.chatpalliwar03@gmail.com',
	'shrunad18@gmail.com',
	'manedigvijay6@gmail.com',
	'karanpavankumar@gmail.com',
	'choicecollectionkothruddata@gmail.com',
	'ayushsiddhi4@gmail.com',
	'mrunalrandive16@gmail.com',
	'kakuldeaarya@gmail.com',
	'krutikabalapure@gmail.com',
	'ankitsinha816@gmail.com',
	'jainaj2017@gmail.com',
	'gallantgs@gmail.com',
	'b2b.tcy11@gmail.com',
	'manpreetkaur2283@gmail.com',
	'gs@tcyonline.com',
	'divyamenghrajani@gmail.com',
	'shreekanthm17@gmail.com',
	'manpreet.kaur@tcyonline.com',
	'vidhanagrawal22@jbims.edu',
	'adityasridhar22@jbims.edu',
	'adityasridhar17@GMAIL.COM',
	'leads9527@gmail.com',
	'sumitdigitalpartner@gmail.com',
	'sumitb9527@gmail.com',
	'utkarshghatol42@gmail.com',
);

$action = isset( $_GET['action'] ) ? sanitize_key( (string) $_GET['action'] ) : 'all';
$batch  = isset( $_GET['batch'] ) ? max( 0, (int) $_GET['batch'] ) : 0;
$size   = isset( $_GET['batch_size'] ) ? max( 1, min( 25, (int) $_GET['batch_size'] ) ) : 10;
$repair = ! isset( $_GET['repair'] ) || '0' !== (string) $_GET['repair'];

$jbims_emails = array_values(
	array_filter(
		$emails,
		static function ( $email ) {
			return is_string( $email ) && $email !== '';
		}
	)
);

$output = array(
	'time'        => gmdate( 'c' ),
	'action'      => $action,
	'batch'       => $batch,
	'batch_size'  => $size,
	'repair'      => $repair,
	'total_users' => count( $jbims_emails ),
);

if ( in_array( $action, array( 'all', 'revoke' ), true ) ) {
	$output['revoke'] = ttp_bulk_revoke_unauthorized_affiliates(
		array(
			'2025anshitripathi04@gmail.com',
			'tripathianshika2000@gmail.com',
			'kalokaraditya39@gmail.com',
		)
	);
}

if ( in_array( $action, array( 'all', 'audit', 'audit_batch' ), true ) ) {
	$offset      = $batch * $size;
	$batch_emails = array_slice( $jbims_emails, $offset, $size );
	$output['batch_from'] = $offset;
	$output['batch_count'] = count( $batch_emails );
	$do_repair = $repair && in_array( $action, array( 'all', 'audit', 'audit_batch' ), true );
	$output['audit'] = ttp_bulk_audit_customers( $batch_emails, $do_repair );
	$output['next_batch_url'] = ( $offset + $size < count( $jbims_emails ) )
		? home_url( '/ttp-bulk-run-once.php?key=' . rawurlencode( $expected ) . '&action=audit_batch&batch=' . ( $batch + 1 ) . '&batch_size=' . $size . '&repair=' . ( $repair ? '1' : '0' ) )
		: '';
}

if ( in_array( $action, array( 'jbims_renew', 'jbims_users' ), true ) ) {
	$priority = array(
		'utkarshghatol42@gmail.com',
		'mahrinsayed@gmail.com',
	);
	$days = isset( $_GET['days'] ) ? max( 1, (int) $_GET['days'] ) : 5;
	$output['jbims_renew'] = ttp_bulk_renew_recent_jbims_orders( $days, $priority );
}

if ( 'jbims_users' === $action ) {
	$output['utkarsh'] = ttp_bulk_renew_customer_by_email( 'utkarshghatol42@gmail.com' );
	$output['mahrin']  = ttp_bulk_deep_fix_jbims_student( 'mahrinsayed@gmail.com', '7208757443' );
	$output['anshika'] = ttp_bulk_deep_fix_jbims_student( '2025anshitripathi04@gmail.com' );
	$output['mahrin_lookup'] = ttp_bulk_find_customer_orders_any_status( 'mahrinsayed@gmail.com', '7208757443' );
	$output['anshika_lookup'] = ttp_bulk_find_customer_orders_any_status( '2025anshitripathi04@gmail.com' );
}

if ( 'fix_two_students' === $action ) {
	$output['mahrin']  = ttp_bulk_link_known_tcy_jbims( 'mahrinsayed@gmail.com', '8160070', '7208757443', '38081' );
	$output['anshika'] = ttp_bulk_link_known_tcy_jbims( '2025anshitripathi04@gmail.com', '8160407', '6387154017', '38081' );
}

if ( 'verify_panel' === $action ) {
	foreach ( array( 308 => 'mahrinsayed@gmail.com', 354 => '2025anshitripathi04@gmail.com' ) as $uid => $email ) {
		$output['users'][ $email ] = array(
			'wp_user_id'        => $uid,
			'tcy_user_id'       => get_user_meta( $uid, '_ttp_tcy_user_id', true ),
			'portal_enrollments' => get_user_meta( $uid, '_ttp_portal_enrollments', true ),
			'enrolled_panel'    => function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' )
				? ttp_get_user_enrolled_courses_for_login_panel( $uid )
				: array(),
		);
	}
}

if ( 'fix_jbims_students' === $action ) {
	$students = array(
		array( 'email' => 'katkadetejas01@gmail.com', 'tcy' => '8161300', 'phone' => '7498405960', 'orders' => array() ),
		array( 'email' => 'manejane9o8adi2@gmail.com', 'tcy' => '8161575', 'phone' => '9172114082', 'orders' => array() ),
		array( 'email' => 'hanbarmansi777@gmail.com', 'tcy' => '', 'phone' => '9370697227', 'orders' => array( 18441 ) ),
		array( 'email' => 'srushti0627@gmail.com', 'tcy' => '', 'phone' => '9773237243', 'orders' => array( 18432 ) ),
		array( 'email' => 'mahrinsayed@gmail.com', 'tcy' => '8160070', 'phone' => '7208757443', 'orders' => array() ),
		array( 'email' => '2025anshitripathi04@gmail.com', 'tcy' => '8160407', 'phone' => '6387154017', 'orders' => array() ),
	);
	$days = isset( $_GET['days'] ) ? max( 1, (int) $_GET['days'] ) : 5;
	$output['jbims_renew_recent'] = ttp_bulk_renew_recent_jbims_orders(
		$days,
		array_column( $students, 'email' )
	);
	foreach ( $students as $student ) {
		$output['students'][ $student['email'] ] = ttp_bulk_complete_jbims_student_fix(
			$student['email'],
			$student['tcy'],
			$student['phone'],
			isset( $student['orders'] ) ? $student['orders'] : array()
		);
	}
}

if ( 'fix_paid_jbims' === $action ) {
	$paid_students = array(
		array( 'email' => 'srushti0627@gmail.com', 'phone' => '9773237243', 'orders' => array( 18432 ) ),
		array( 'email' => 'hanbarmansi777@gmail.com', 'phone' => '9370697227', 'orders' => array( 18441 ) ),
	);
	foreach ( $paid_students as $student ) {
		$output['students'][ $student['email'] ] = ttp_bulk_complete_jbims_student_fix(
			$student['email'],
			'',
			$student['phone'],
			$student['orders']
		);
	}
	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		$output['catalog_repair'] = TTP_Catalog_Seed::repair_all_tcy_meta();
	}
}

if ( 'revoke_false_jbims' === $action ) {
	$target = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( (string) $_GET['email'] ) ) : '';
	$emails = $target !== '' ? array( $target ) : array( 'kriaditi24dec@gmail.com' );
	foreach ( $emails as $email ) {
		$output['students'][ $email ] = ttp_bulk_revoke_false_jbims_for_customer( $email );
	}
}

if ( 'audit_customer' === $action ) {
	$target = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( (string) $_GET['email'] ) ) : 'kriaditi24dec@gmail.com';
	$output['audit'] = ttp_bulk_audit_customer_by_email( $target, false );
	if ( function_exists( 'ttp_get_user_enrolled_courses_for_login_panel' ) ) {
		$user = get_user_by( 'email', $target );
		if ( $user instanceof WP_User ) {
			$output['enrolled_panel'] = ttp_get_user_enrolled_courses_for_login_panel( (int) $user->ID );
		}
	}
}

if ( 'repair_panel' === $action ) {
	$target = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( (string) $_GET['email'] ) ) : 'katkadetejas01@gmail.com';
	$output['student'] = ttp_bulk_repair_customer_panel( $target, true );
}

if ( in_array( $action, array( 'repair_panels', 'repair_panels_batch' ), true ) ) {
	$offset       = $batch * $size;
	$batch_emails = array_slice( $jbims_emails, $offset, $size );
	$output['batch_from']  = $offset;
	$output['batch_count'] = count( $batch_emails );
	$output['repair']      = ttp_bulk_repair_customer_panels( $batch_emails, true );
	$output['next_batch_url'] = ( $offset + $size < count( $jbims_emails ) )
		? home_url( '/ttp-bulk-run-once.php?key=' . rawurlencode( $expected ) . '&action=repair_panels_batch&batch=' . ( $batch + 1 ) . '&batch_size=' . $size )
		: '';
}

if ( 'revoke_all_false_jbims' === $action ) {
	$output['revoke_all'] = ttp_bulk_revoke_all_false_jbims( isset( $_GET['limit'] ) ? (int) $_GET['limit'] : 500 );
}

if ( 'fix_jbims_jun15' === $action ) {
	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		$output['catalog_repair'] = TTP_Catalog_Seed::repair_all_tcy_meta();
	}
	$priority_students = array(
		array( 'email' => 'agarwadkarshreyas7@gmail.com' ),
		array( 'email' => 'ruturajvshedge@gmail.com', 'username' => 'RuturajShedge' ),
		array( 'email' => 'enthusiasticcitizen898@gmail.com' ),
		array( 'email' => 'pranitaw514@gmail.com' ),
		array( 'email' => 'vaishnavk1708@gmail.com' ),
		array( 'email' => 'vaishukakad1708@gmail.com' ),
		array( 'email' => 'htokalwar@gmail.com' ),
		array( 'email' => 'samikshajambhulkar9716@gmail.com' ),
		array( 'email' => 'janhvikamat9@gmail.com' ),
	);
	foreach ( $priority_students as $student ) {
		$email = strtolower( trim( sanitize_email( (string) ( $student['email'] ?? '' ) ) ) );
		if ( $email === '' || ! is_email( $email ) ) {
			continue;
		}
		$row = array(
			'lookup' => ttp_bulk_find_customer_orders_any_status( $email, isset( $student['phone'] ) ? (string) $student['phone'] : '' ),
		);
		if ( function_exists( 'ttp_bulk_ensure_wp_user_for_customer' ) ) {
			$row['ensure_wp'] = ttp_bulk_ensure_wp_user_for_customer(
				$email,
				isset( $student['phone'] ) ? (string) $student['phone'] : '',
				isset( $student['username'] ) ? (string) $student['username'] : ''
			);
		}
		$row['complete'] = ttp_bulk_complete_jbims_student_fix(
			$email,
			isset( $student['tcy'] ) ? (string) $student['tcy'] : '',
			isset( $student['phone'] ) ? (string) $student['phone'] : '',
			isset( $student['orders'] ) ? (array) $student['orders'] : array()
		);
		$row['panel'] = ttp_bulk_repair_customer_panel( $email, true );
		$output['students'][ $email ] = $row;
	}
}

if ( 'find_user' === $action ) {
	$fragment = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : 'hrishiraj';
	$output['find'] = ttp_bulk_find_users_by_email_fragment( $fragment );
}

header( 'Content-Type: application/json; charset=utf-8' );
echo wp_json_encode( $output, JSON_PRETTY_PRINT );

// Self-delete after successful all run.
if ( 'all' === $action && isset( $_GET['self_delete'] ) && '1' === (string) $_GET['self_delete'] ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	@unlink( __FILE__ );
}
