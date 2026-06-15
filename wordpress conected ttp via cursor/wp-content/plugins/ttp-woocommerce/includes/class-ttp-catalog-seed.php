<?php
/**
 * MBA CET 2027 catalog: WooCommerce products + TCY course/product ID mapping.
 *
 * TCY API (per TCY ERP sheet May 2026):
 *   course_id     → _ttp_tcy_course_id     (e.g. 90069 CET Elite)
 *   category_id   → _ttp_tcy_category_id   (MBA Entrance 100000 — sent in register/add_course API)
 *   entrance ref  → _ttp_tcy_entrance_category_id (same as category_id in API)
 *   product pack  → _ttp_tcy_product_pack_id (reference only: 33599 CET, 33605 NMAT — not sent in API)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_Catalog_Seed {

	const CATALOG_VERSION = '10';

	/** TCY MBA Entrance (reference — not sent as API category_id). */
	const TCY_CATEGORY_MBA_ENTRANCE = '100000';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions() {
		$nmat_pack = 'NON- CAT/MBA - All in one Combo Pack with SNAP/NMAT eBooks (12 Months)';
		$cet_pack  = 'CET- MH Test Series with MBA Topic wise Tests (12 Months)';

		$bullets_nmat_elite = [
			'Live Basics to Advanced Classes by JBIMS Alumni',
			'Dedicated WhatsApp Community',
			'Doubt Solving WhatsApp Group',
			'35 CET Mocks + 50 Sectional Tests for Maximum Practice',
			'10 NMAT, 20 SNAP, 12 CMAT & 5 MAT Mock Tests Included',
			'320+ Topic-wise Tests for QA, LR, DI, RC & English',
			'Dedicated E-Books & Preparation Material for NMAT/SNAP',
			'Learn Anytime on Web + Mobile App Access',
		];

		$bullets_nmat_mentorship = array_merge(
			[
				'Personalized 1:1 Mentorship with JBIMS Students',
				'Weekly Strategy & Motivation Mentorship Sessions',
			],
			$bullets_nmat_elite
		);

		$bullets_cet_live_mentorship = [
			'Live Basics to Advanced Classes by JBIMS Alumni',
			'Personalized 1:1 Mentorship with JBIMS Students',
			'Weekly Strategy & Motivation Mentorship Sessions',
			'Dedicated WhatsApp Community',
			'Doubt Solving WhatsApp Group',
			'35 CET Mocks + 50 Sectional Tests for Maximum Practice',
			'320+ Topic-wise Tests for QA, LR, DI, RC & English',
			'Learn Anytime on Web + Mobile App Access',
		];

		$bullets_cet_elite = [
			'Live Basics to Advanced Classes by JBIMS Alumni',
			'Dedicated WhatsApp Community',
			'Doubt Solving WhatsApp Group',
			'35 CET Mocks + 50 Sectional Tests for Maximum Practice',
			'320+ Topic-wise Tests for QA, LR, DI, RC & English',
			'Learn Anytime on Web + Mobile App Access',
		];

		$bullets_cet_solo = [
			'Recorded Classes by JBIMS Alumni',
			'Dedicated WhatsApp Community',
			'Doubt Solving WhatsApp Group',
			'23 CET Mocks + 14 Sectional Tests for Maximum Practice',
			'320+ Topic-wise Tests for QA, LR, DI, RC & English',
			'Learn Anytime on Web + Mobile App Access',
		];

		$jbims_capsules = [ 'Live Sessions', 'Mock GD and PI' ];

		$bullets_jbims_bootcamp = [
			'Live GD-WAT-PI Masterclasses by JBIMS MSc Finance & MHRD Alumni',
			'Orientation Session on Admission Process, Timelines & Panel Expectations',
			'GD Masterclasses — Abstract GDs, Fish-market Handling & Leadership Techniques',
			'WAT Masterclasses — SOP Writing, Complex & Controversial Topics',
			'PI Masterclasses — TMAY, Domain Questions, Stress Interviews & Curveballs',
			'Finance & HR Domain Prep — Core Concepts, Key Terms & Interview Questions',
			'Current Affairs Strategy — Sources, Topics & Usage in GD-PI',
			'CV & Application Form Deep Dive — Evaluation Criteria & Profile Optimisation',
			'2 Mock GDs with Detailed Feedback',
			'2 Customised Mock PIs with Personalised Feedback',
			'Networking Sessions with JBIMS MSc Finance & MHRD Seniors',
			'Final Revision & Open Q&A — GD, WAT & PI Drill before D-Day',
		];

		$bullets_jbims_elite = [
			'Live GD-WAT-PI Masterclasses by JBIMS MSc Finance & MHRD Alumni',
			'Orientation Session on Admission Process, Timelines & Panel Expectations',
			'GD Masterclasses — Abstract GDs, Fish-market Handling & Leadership Techniques',
			'WAT Masterclasses — SOP Writing, Complex & Controversial Topics',
			'PI Masterclasses — TMAY, Domain Questions, Stress Interviews & Curveballs',
			'Finance & HR Domain Prep — Core Concepts, Key Terms & Interview Questions',
			'Current Affairs Strategy — Sources, Topics & Usage in GD-PI',
			'CV & Application Form Deep Dive — Evaluation Criteria & Profile Optimisation',
			'4 Mock GDs with Detailed Feedback',
			'4 Customised Mock PIs with Personalised Feedback',
			'Networking Sessions with JBIMS MSc Finance & MHRD Seniors',
			'Final Revision & Open Q&A — GD, WAT & PI Drill before D-Day',
		];

		return apply_filters( 'ttp_catalog_mba_cet_2027_definitions', [
			[
				'slug'                 => 'cet-nmat-snap-elite-with-1-on-1-mentorship',
				'name'                 => 'CET NMAT SNAP Elite (with 1 on 1 Mentorship)',
				'sku'                  => 'TTP-MBA-CET-2027-NMAT-SNAP-ELITE-M',
				'tcy_course_id'             => '90073',
				'tcy_product_pack_id'     => '33605',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '19999',
				'sale_price'           => '17999',
				'pack_line'            => $nmat_pack,
				'feature_bullets'      => $bullets_nmat_mentorship,
			],
			[
				'slug'                 => 'cet-nmat-snap-elite',
				'name'                 => 'CET NMAT SNAP Elite',
				'sku'                  => 'TTP-MBA-CET-2027-NMAT-SNAP-ELITE',
				'tcy_course_id'             => '90071',
				'tcy_product_pack_id'     => '33605',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '19999',
				'sale_price'           => '17999',
				'pack_line'            => $nmat_pack,
				'feature_bullets'      => $bullets_nmat_elite,
			],
			[
				'slug'                 => 'cet-elite-with-1-on-1-mentorship',
				'name'                 => 'CET Elite (with 1 on 1 Mentorship)',
				'sku'                  => 'TTP-MBA-CET-2027-ELITE-M',
				'tcy_course_id'             => '90072',
				'tcy_product_pack_id'     => '33599',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '17999',
				'sale_price'           => '14999',
				'pack_line'            => $cet_pack,
				'feature_bullets'      => $bullets_cet_live_mentorship,
			],
			[
				'slug'                 => 'cet-elite',
				'name'                 => 'CET Elite',
				'sku'                  => 'TTP-MBA-CET-2027-ELITE',
				'tcy_course_id'             => '90069',
				'tcy_product_pack_id'     => '33599',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '17999',
				'sale_price'           => '14999',
				'pack_line'            => $cet_pack,
				'feature_bullets'      => $bullets_cet_elite,
			],
			[
				'slug'                 => 'cet-solo-self-study',
				'name'                 => 'CET Solo (Self Study)',
				'sku'                  => 'TTP-MBA-CET-2027-SOLO',
				'tcy_course_id'             => '90070',
				'tcy_product_pack_id'     => '33599',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '8999',
				'sale_price'           => '',
				'pack_line'            => $cet_pack,
				'feature_bullets'      => $bullets_cet_solo,
			],
			[
				'slug'                 => 'jbims-mfin-mhrd-bootcamp',
				'name'                 => 'JBIMS MFIN MHRD Bootcamp',
				'sku'                  => 'TTP-JBIMS-BOOTCAMP',
				'tcy_course_id'        => '90334',
				'tcy_product_pack_id'  => '38081',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '3499',
				'sale_price'           => '2499',
				'pack_line'            => 'JBIMS MFin / MHRD GD-PI Bootcamp',
				'enroll_capsules'      => $jbims_capsules,
				'feature_bullets'      => $bullets_jbims_bootcamp,
			],
			[
				'slug'                 => 'jbims-mfin-mhrd-bootcamp-elite',
				'name'                 => 'JBIMS MFIN MHRD Bootcamp Elite',
				'sku'                  => 'TTP-JBIMS-BOOTCAMP-ELITE',
				'tcy_course_id'        => '90334',
				'tcy_product_pack_id'  => '38033',
				'tcy_entrance_category_id' => self::TCY_CATEGORY_MBA_ENTRANCE,
				'regular_price'        => '4999',
				'sale_price'           => '3999',
				'pack_line'            => 'JBIMS MFin / MHRD GD-PI Bootcamp Elite',
				'enroll_capsules'      => $jbims_capsules,
				'feature_bullets'      => $bullets_jbims_elite,
			],
		] );
	}

	/**
	 * Full catalog row by slug / plan key.
	 *
	 * @param string $slug Catalog slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}
		foreach ( self::get_definitions() as $def ) {
			if ( isset( $def['slug'] ) && sanitize_title( (string) $def['slug'] ) === $slug ) {
				return $def;
			}
		}
		return null;
	}

	/**
	 * Fix Woo meta that stored TCY pack / Product_id as course_id (TCY add_course error 006).
	 *
	 * JBIMS Bootcamp: pack Product_id 38081, course_id 90334 (per TCY get_courses).
	 *
	 * @param string $stored_id Value from _ttp_tcy_course_id.
	 * @param string $label     Product or order line title.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition_for_misstored_course_id( $stored_id, $label = '' ) {
		$stored_id = sanitize_text_field( (string) $stored_id );
		if ( '' === $stored_id ) {
			return null;
		}

		// Legacy JBIMS ids (invalid on TCY register — use get_courses row 90334 + pack sub_cat).
		$legacy_jbims_course_ids = array( '90235', '90238' );
		if ( in_array( $stored_id, $legacy_jbims_course_ids, true ) ) {
			$label = trim( (string) $label );
			if ( $label !== '' && preg_match( '/elite/i', $label ) ) {
				$elite = self::get_definition_by_slug( 'jbims-mfin-mhrd-bootcamp-elite' );
				if ( $elite ) {
					return $elite;
				}
			}
			$bootcamp = self::get_definition_by_slug( 'jbims-mfin-mhrd-bootcamp' );
			if ( $bootcamp ) {
				return $bootcamp;
			}
		}

		$pack_matches = [];
		foreach ( self::get_definitions() as $def ) {
			$course_id = isset( $def['tcy_course_id'] ) ? sanitize_text_field( (string) $def['tcy_course_id'] ) : '';
			$pack_id   = isset( $def['tcy_product_pack_id'] ) ? sanitize_text_field( (string) $def['tcy_product_pack_id'] ) : '';
			if ( $course_id !== '' && $course_id === $stored_id ) {
				return $def;
			}
			if ( $pack_id !== '' && $pack_id === $stored_id ) {
				$pack_matches[] = $def;
			}
		}

		if ( empty( $pack_matches ) ) {
			return null;
		}
		if ( 1 === count( $pack_matches ) ) {
			return $pack_matches[0];
		}

		$label = trim( (string) $label );
		if ( '' !== $label ) {
			$from_title = self::get_definition_from_title_heuristic( $label );
			if ( $from_title && ! empty( $from_title['slug'] ) ) {
				$want = sanitize_title( (string) $from_title['slug'] );
				foreach ( $pack_matches as $def ) {
					if ( isset( $def['slug'] ) && sanitize_title( (string) $def['slug'] ) === $want ) {
						return $def;
					}
				}
			}
		}

		return $pack_matches[0];
	}

	/**
	 * Guess catalog row from product title (live titles often differ from seed names).
	 *
	 * @param string $product_name WooCommerce product title.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition_from_title_heuristic( $product_name ) {
		$n = strtolower( trim( (string) $product_name ) );
		if ( '' === $n ) {
			return null;
		}
		// Legacy Woo / TCY admin titles (e.g. "Non CAT / MBA All-in-One Combo Pack", "CET-MH Test Series…").
		if ( preg_match( '/non\s*cat|all[\s-]*in[\s-]*one|combo\s*pack|snap\/nmat|nmat\s*ebook/i', $n ) ) {
			if ( preg_match( '/mentorship|1\s*on\s*1/i', $n ) ) {
				return self::get_definition_by_slug( 'cet-nmat-snap-elite-with-1-on-1-mentorship' );
			}
			return self::get_definition_by_slug( 'cet-nmat-snap-elite' );
		}
		if ( preg_match( '/cet[\s-]*mh|test\s*series|topic\s*wise/i', $n ) ) {
			if ( preg_match( '/mentorship|1\s*on\s*1/i', $n ) ) {
				return self::get_definition_by_slug( 'cet-elite-with-1-on-1-mentorship' );
			}
			if ( preg_match( '/\bsolo\b|self\s*study/i', $n ) ) {
				return self::get_definition_by_slug( 'cet-solo-self-study' );
			}
			return self::get_definition_by_slug( 'cet-solo-self-study' );
		}
		if ( preg_match( '/jbims|mfin|mhrd|bootcamp/i', $n ) ) {
			if ( preg_match( '/elite/i', $n ) ) {
				return self::get_definition_by_slug( 'jbims-mfin-mhrd-bootcamp-elite' );
			}
			return self::get_definition_by_slug( 'jbims-mfin-mhrd-bootcamp' );
		}
		if ( preg_match( '/\bsolo\b|self\s*study/i', $n ) ) {
			return self::get_definition_by_slug( 'cet-solo-self-study' );
		}
		if ( preg_match( '/\bnmat\b|\bsnap\b/i', $n ) ) {
			if ( preg_match( '/mentorship|1\s*on\s*1/i', $n ) ) {
				return self::get_definition_by_slug( 'cet-nmat-snap-elite-with-1-on-1-mentorship' );
			}
			return self::get_definition_by_slug( 'cet-nmat-snap-elite' );
		}
		if ( preg_match( '/mentorship|1\s*on\s*1/i', $n ) ) {
			return self::get_definition_by_slug( 'cet-elite-with-1-on-1-mentorship' );
		}
		if ( preg_match( '/\belite\b/i', $n ) ) {
			return self::get_definition_by_slug( 'cet-elite' );
		}
		return null;
	}

	/**
	 * WooCommerce product ID for a catalog row (slug, then SKU).
	 *
	 * @param array<string, mixed> $def Catalog definition.
	 * @return int
	 */
	public static function get_product_id_for_definition( array $def ) {
		$slug = isset( $def['slug'] ) ? sanitize_title( (string) $def['slug'] ) : '';
		if ( $slug ) {
			$id = self::get_product_id_by_slug( $slug );
			if ( $id > 0 ) {
				return $id;
			}
		}
		$sku = isset( $def['sku'] ) ? sanitize_text_field( (string) $def['sku'] ) : '';
		if ( $sku !== '' && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$by_sku = (int) wc_get_product_id_by_sku( $sku );
			if ( $by_sku > 0 ) {
				return $by_sku;
			}
		}
		return 0;
	}

	/**
	 * Write TCY IDs + plan key onto a product.
	 *
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $def        Catalog definition.
	 * @return void
	 */
	/**
	 * category_id in TCY register/add_course API = MBA Entrance (100000), per TCY Postman register.
	 *
	 * @param array<string, mixed> $def Catalog row.
	 * @return string
	 */
	public static function resolve_api_category_id( array $def ) {
		if ( ! empty( $def['tcy_entrance_category_id'] ) ) {
			return sanitize_text_field( (string) $def['tcy_entrance_category_id'] );
		}
		if ( ! empty( $def['tcy_category_id'] ) ) {
			return sanitize_text_field( (string) $def['tcy_category_id'] );
		}
		return self::TCY_CATEGORY_MBA_ENTRANCE;
	}

	/**
	 * @param array<string, mixed> $def Catalog row.
	 * @return array{course_id: string, category_id: string, product_pack_id: string, entrance_category_id: string}
	 */
	public static function resolve_tcy_api_ids( array $def ) {
		$pack_ref = ! empty( $def['tcy_product_pack_id'] )
			? sanitize_text_field( (string) $def['tcy_product_pack_id'] )
			: '';
		return [
			'course_id'             => sanitize_text_field( (string) ( $def['tcy_course_id'] ?? '' ) ),
			'category_id'           => self::resolve_api_category_id( $def ),
			'product_pack_id'       => $pack_ref,
			'entrance_category_id'  => sanitize_text_field( (string) ( $def['tcy_entrance_category_id'] ?? self::TCY_CATEGORY_MBA_ENTRANCE ) ),
		];
	}

	public static function apply_tcy_meta_from_definition( $product_id, array $def ) {
		$product_id = (int) $product_id;
		if ( $product_id < 1 || empty( $def['tcy_course_id'] ) ) {
			return;
		}
		$src_id = function_exists( 'ttp_tcy_meta_source_product_id' ) ? ttp_tcy_meta_source_product_id( $product_id ) : $product_id;
		$ids    = self::resolve_tcy_api_ids( $def );
		$plan   = isset( $def['slug'] ) ? sanitize_title( (string) $def['slug'] ) : '';

		update_post_meta( $src_id, '_ttp_tcy_course_id', $ids['course_id'] );
		update_post_meta( $src_id, '_ttp_tcy_category_id', $ids['category_id'] );
		update_post_meta( $src_id, '_ttp_tcy_product_pack_id', $ids['product_pack_id'] );
		if ( $ids['entrance_category_id'] !== '' ) {
			update_post_meta( $src_id, '_ttp_tcy_entrance_category_id', $ids['entrance_category_id'] );
		}
		if ( $plan !== '' ) {
			update_post_meta( $src_id, '_ttp_tcy_plan_key', $plan );
		}
		if ( $src_id !== $product_id ) {
			update_post_meta( $product_id, '_ttp_tcy_course_id', $ids['course_id'] );
			update_post_meta( $product_id, '_ttp_tcy_category_id', $ids['category_id'] );
			update_post_meta( $product_id, '_ttp_tcy_product_pack_id', $ids['product_pack_id'] );
			if ( $ids['entrance_category_id'] !== '' ) {
				update_post_meta( $product_id, '_ttp_tcy_entrance_category_id', $ids['entrance_category_id'] );
			}
			if ( $plan !== '' ) {
				update_post_meta( $product_id, '_ttp_tcy_plan_key', $plan );
			}
		}
	}

	/**
	 * Canonical TCY IDs for a catalog slug.
	 *
	 * @param string $slug Product post_name.
	 * @return array{course_id: string, product_id: string}|null
	 */
	public static function get_tcy_ids_for_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}
		foreach ( self::get_definitions() as $def ) {
			if ( isset( $def['slug'] ) && sanitize_title( (string) $def['slug'] ) === $slug ) {
				$ids = self::resolve_tcy_api_ids( $def );
				return [
					'course_id'  => $ids['course_id'],
					'product_id' => $ids['category_id'],
				];
			}
		}
		return null;
	}

	/**
	 * Canonical TCY IDs for a catalog SKU.
	 *
	 * @param string $sku WooCommerce SKU.
	 * @return array{course_id: string, product_id: string}|null
	 */
	public static function get_tcy_ids_for_sku( $sku ) {
		$sku = sanitize_text_field( (string) $sku );
		if ( '' === $sku ) {
			return null;
		}
		foreach ( self::get_definitions() as $def ) {
			if ( isset( $def['sku'] ) && (string) $def['sku'] === $sku ) {
				$ids = self::resolve_tcy_api_ids( $def );
				return [
					'course_id'  => $ids['course_id'],
					'product_id' => $ids['category_id'],
				];
			}
		}
		return null;
	}

	/**
	 * Fix wrong _ttp_tcy_* meta on catalog products (e.g. CET Solo copied from NMAT).
	 *
	 * @return array<int, array{product_id: int, slug: string, was: array, now: array}>
	 */
	public static function repair_all_tcy_meta() {
		$fixed    = [];
		$seen_ids = [];

		foreach ( self::get_definitions() as $def ) {
			$product_id = self::get_product_id_for_definition( $def );
			if ( $product_id < 1 ) {
				continue;
			}
			$seen_ids[ $product_id ] = true;
			$result                  = self::repair_tcy_meta_for_product( $product_id );
			if ( $result ) {
				$fixed[] = $result;
			}
		}

		$term = get_term_by( 'slug', 'mba-cet-2027', 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$q = new WP_Query(
				[
					'post_type'      => 'product',
					'post_status'    => [ 'publish', 'draft', 'pending' ],
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => [
						[
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => [ (int) $term->term_id ],
						],
					],
				]
			);
			foreach ( $q->posts as $pid ) {
				$pid = (int) $pid;
				if ( $pid < 1 || isset( $seen_ids[ $pid ] ) ) {
					continue;
				}
				$result = self::repair_tcy_meta_for_product( $pid );
				if ( $result ) {
					$fixed[]        = $result;
					$seen_ids[ $pid ] = true;
				}
			}
		}

		return $fixed;
	}

	/**
	 * @param int $product_id WooCommerce product ID.
	 * @return array|null Change record when meta was corrected.
	 */
	public static function repair_tcy_meta_for_product( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}
		$def = self::get_definition_for_product( $product );
		if ( ! $def ) {
			return null;
		}
		$src_id     = function_exists( 'ttp_tcy_meta_source_product_id' ) ? ttp_tcy_meta_source_product_id( $product_id ) : $product_id;
		$old_course = (string) get_post_meta( $src_id, '_ttp_tcy_course_id', true );
		$old_cat    = (string) get_post_meta( $src_id, '_ttp_tcy_category_id', true );
		$ids        = self::resolve_tcy_api_ids( $def );
		$new_course = $ids['course_id'];
		$new_cat    = $ids['category_id'];
		$changed    = ( $old_course !== $new_course || $old_cat !== $new_cat );
		self::apply_tcy_meta_from_definition( $product_id, $def );
		if ( ! $changed ) {
			return null;
		}
		return [
			'product_id' => $product_id,
			'slug'       => sanitize_title( $product->get_slug() ),
			'plan'       => isset( $def['slug'] ) ? (string) $def['slug'] : '',
			'was'        => [ 'course_id' => $old_course, 'product_id' => $old_cat ],
			'now'        => [ 'course_id' => $new_course, 'product_id' => $new_cat ],
		];
	}

	/**
	 * Build HTML description: pack line + bullet list.
	 *
	 * @param string   $pack_line Pack / TCY description line (12 Months at end).
	 * @param string[] $bullets   Feature bullets.
	 * @return string
	 */
	/**
	 * Four two-column rows for the single-product course card (pipe-separated CSV in admin).
	 *
	 * @param string[] $bullets Feature bullets from catalog definitions.
	 * @return string
	 */
	public static function build_feature_grid_from_bullets( array $bullets ) {
		$lines = [];
		for ( $r = 0; $r < 4; $r++ ) {
			$i  = $r * 2;
			$lt = isset( $bullets[ $i ] ) ? sanitize_text_field( $bullets[ $i ] ) : '';
			$rt = isset( $bullets[ $i + 1 ] ) ? sanitize_text_field( $bullets[ $i + 1 ] ) : '';

			if ( '' === $lt && '' === $rt ) {
				continue;
			}

			$lines[] = $lt . ' | ' . self::feature_grid_subtext_for_bullet( $lt ) . ' | ' . $rt . ' | ' . self::feature_grid_subtext_for_bullet( $rt );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Short sub-line under each feature (unique per bullet, not one generic line for every course).
	 *
	 * @param string $bullet Feature title.
	 * @return string
	 */
	public static function feature_grid_subtext_for_bullet( $bullet ) {
		$b = strtolower( (string) $bullet );
		if ( '' === $b ) {
			return '';
		}
		if ( false !== strpos( $b, '1:1' ) || false !== strpos( $b, 'mentorship' ) ) {
			return __( 'Personal mentor check-ins and strategy', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'live' ) ) {
			return __( 'Live classes with JBIMS alumni', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'recorded' ) ) {
			return __( 'Self-paced video lessons on demand', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'whatsapp' ) ) {
			return __( 'Peer community and updates', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'doubt' ) ) {
			return __( 'Quick doubt resolution support', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'nmat' ) || false !== strpos( $b, 'snap' ) || false !== strpos( $b, 'cmat' ) || false !== strpos( $b, 'mat ' ) ) {
			return __( 'OMET mock tests included', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'mock' ) || false !== strpos( $b, 'sectional' ) ) {
			return __( 'Timed mocks with performance analytics', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'topic' ) || false !== strpos( $b, 'topic-wise' ) ) {
			return __( 'Chapter-wise practice across all sections', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'e-book' ) || false !== strpos( $b, 'material' ) ) {
			return __( 'Digital study material on the portal', 'ttp-woocommerce' );
		}
		if ( false !== strpos( $b, 'mobile' ) || false !== strpos( $b, 'app' ) || false !== strpos( $b, 'web' ) ) {
			return __( 'Study on web and mobile app', 'ttp-woocommerce' );
		}

		return __( 'Included in your program', 'ttp-woocommerce' );
	}

	/**
	 * Match a WooCommerce product to a catalog definition (slug, SKU, then title).
	 *
	 * @param WC_Product|int|null $product Product or ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition_for_product( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}
		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$slug = sanitize_title( $product->get_slug() );
		$sku  = sanitize_text_field( (string) $product->get_sku() );
		$name = strtolower( trim( (string) $product->get_name() ) );

		$aliases = apply_filters(
			'ttp_catalog_slug_aliases',
			[
				'cet-solo'              => 'cet-solo-self-study',
				'cet-solo-self-study-2' => 'cet-solo-self-study',
				'nmat-snap-elite'       => 'cet-nmat-snap-elite',
				'jbims-mfin-mhrd-bootcamp-006' => 'jbims-mfin-mhrd-bootcamp',
				'jbims-bootcamp'        => 'jbims-mfin-mhrd-bootcamp',
				'jbims-bootcamp-elite'  => 'jbims-mfin-mhrd-bootcamp-elite',
			]
		);
		if ( isset( $aliases[ $slug ] ) ) {
			$slug = sanitize_title( (string) $aliases[ $slug ] );
		}

		foreach ( self::get_definitions() as $def ) {
			$dslug = isset( $def['slug'] ) ? sanitize_title( (string) $def['slug'] ) : '';
			if ( $dslug !== '' && $dslug === $slug ) {
				return $def;
			}
		}
		if ( $sku !== '' ) {
			foreach ( self::get_definitions() as $def ) {
				if ( isset( $def['sku'] ) && (string) $def['sku'] === $sku ) {
					return $def;
				}
			}
		}
		foreach ( self::get_definitions() as $def ) {
			$dname = isset( $def['name'] ) ? strtolower( trim( (string) $def['name'] ) ) : '';
			if ( $dname !== '' && $dname === $name ) {
				return $def;
			}
		}

		$heuristic = self::get_definition_from_title_heuristic( $product->get_name() );
		if ( $heuristic ) {
			return $heuristic;
		}

		// Stale _ttp_tcy_plan_key must not override slug/SKU/title (was forcing CET Elite on NMAT products).
		$plan_key = sanitize_title( (string) get_post_meta( $product->get_id(), '_ttp_tcy_plan_key', true ) );
		if ( $plan_key !== '' ) {
			$by_plan = self::get_definition_by_slug( $plan_key );
			if ( $by_plan ) {
				return $by_plan;
			}
		}

		return null;
	}

	/**
	 * Match catalog row from order line item title (what the customer purchased).
	 *
	 * @param WC_Order_Item_Product|object $item Order line item.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition_for_order_line_item( $item ) {
		if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
			return null;
		}
		$line_name = trim( (string) $item->get_name() );
		if ( $line_name !== '' ) {
			$from_line = self::get_definition_from_title_heuristic( $line_name );
			if ( $from_line ) {
				return $from_line;
			}
			$line_lower = strtolower( $line_name );
			foreach ( self::get_definitions() as $def ) {
				$dname = isset( $def['name'] ) ? strtolower( trim( (string) $def['name'] ) ) : '';
				if ( $dname !== '' && $dname === $line_lower ) {
					return $def;
				}
			}
		}
		$product_id = (int) $item->get_product_id();
		if ( $product_id > 0 ) {
			return self::get_definition_for_product( $product_id );
		}
		return null;
	}

	/**
	 * TCY course + product IDs for a WooCommerce product (catalog is source of truth).
	 *
	 * @param WC_Product|int $product Product or ID.
	 * @return array{course_id: string, product_id: string}
	 */
	public static function get_tcy_ids_for_product( $product ) {
		$def = self::get_definition_for_product( $product );
		if ( $def ) {
			$ids = self::resolve_tcy_api_ids( $def );
			return [
				'course_id'  => $ids['course_id'],
				'product_id' => $ids['category_id'],
			];
		}
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}
		if ( ! $product instanceof WC_Product ) {
			return [ 'course_id' => '', 'product_id' => '' ];
		}
		$src = function_exists( 'ttp_tcy_meta_source_product_id' ) ? ttp_tcy_meta_source_product_id( $product->get_id() ) : $product->get_id();

		return [
			'course_id'  => (string) get_post_meta( $src, '_ttp_tcy_course_id', true ),
			'product_id' => (string) get_post_meta( $src, '_ttp_tcy_category_id', true ),
		];
	}

	/**
	 * Feature list HTML for exam cards (always from catalog when matched).
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function get_features_html_for_product( $product ) {
		if ( $product instanceof WC_Product ) {
			$src   = function_exists( 'ttp_tcy_meta_source_product_id' ) ? ttp_tcy_meta_source_product_id( $product->get_id() ) : $product->get_id();
			$lines = function_exists( 'ttp_enroll_parse_lines_meta' )
				? ttp_enroll_parse_lines_meta( (string) get_post_meta( $src, '_ttp_enroll_card_bullets', true ) )
				: [];
			if ( ! empty( $lines ) ) {
				return self::build_short_description_html( $lines );
			}
		}
		$def = self::get_definition_for_product( $product );
		if ( $def && ! empty( $def['feature_bullets'] ) && is_array( $def['feature_bullets'] ) ) {
			return self::build_short_description_html( $def['feature_bullets'] );
		}
		if ( $product instanceof WC_Product ) {
			return (string) $product->get_short_description();
		}

		return '';
	}

	/**
	 * Feature grid CSV for single-product layout (catalog first, then stored meta).
	 *
	 * @param WC_Product|int $product Product or ID.
	 * @return string
	 */
	public static function get_feature_grid_for_product( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}
		$def = self::get_definition_for_product( $product );
		if ( $def && ! empty( $def['feature_bullets'] ) && is_array( $def['feature_bullets'] ) ) {
			return self::build_feature_grid_from_bullets( $def['feature_bullets'] );
		}
		if ( $product instanceof WC_Product ) {
			return (string) get_post_meta( $product->get_id(), '_ttp_course_feature_grid', true );
		}

		return '';
	}

	/**
	 * Push catalog bullets/descriptions onto the WooCommerce product (fixes “all courses look the same”).
	 *
	 * @param int                  $product_id Product ID.
	 * @param array<string, mixed> $def        Catalog row.
	 * @return bool
	 */
	public static function apply_definition_display_to_product( $product_id, array $def ) {
		$product_id = (int) $product_id;
		if ( $product_id < 1 || empty( $def['feature_bullets'] ) || ! is_array( $def['feature_bullets'] ) ) {
			return false;
		}

		$pack = isset( $def['pack_line'] ) ? (string) $def['pack_line'] : '';

		update_post_meta( $product_id, '_ttp_course_feature_grid', self::build_feature_grid_from_bullets( $def['feature_bullets'] ) );
		update_post_meta( $product_id, '_ttp_enroll_card_bullets', implode( "\n", $def['feature_bullets'] ) );
		if ( ! empty( $def['enroll_capsules'] ) && is_array( $def['enroll_capsules'] ) ) {
			update_post_meta( $product_id, '_ttp_enroll_capsules', implode( "\n", $def['enroll_capsules'] ) );
		}

		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			$product->set_short_description( self::build_short_description_html( $def['feature_bullets'] ) );
			$product->set_description( self::build_description_html( $pack, $def['feature_bullets'] ) );
			$product->save();
		}

		return true;
	}

	/**
	 * Refresh bullets + descriptions for all five catalog products.
	 *
	 * @return int Number of products updated.
	 */
	public static function repair_all_product_display_content() {
		$count = 0;
		foreach ( self::get_definitions() as $def ) {
			$product_id = self::get_product_id_for_definition( $def );
			if ( $product_id < 1 ) {
				$product_id = self::get_product_id_by_slug( $def['slug'] );
			}
			if ( $product_id < 1 && ! empty( $def['name'] ) ) {
				$by_title = get_page_by_title( (string) $def['name'], OBJECT, 'product' );
				if ( $by_title instanceof WP_Post ) {
					$product_id = (int) $by_title->ID;
				}
			}
			if ( $product_id > 0 ) {
				self::apply_tcy_meta_from_definition( $product_id, $def );
				if ( self::apply_definition_display_to_product( $product_id, $def ) ) {
					++$count;
				}
			}
		}

		return $count;
	}

	public static function build_description_html( $pack_line, array $bullets ) {
		$items = '';
		foreach ( $bullets as $b ) {
			$items .= '<li>' . esc_html( $b ) . '</li>';
		}

		return '<p><strong>' . esc_html( $pack_line ) . '</strong></p><ul>' . $items . '</ul>';
	}

	/**
	 * Short description: bullets only.
	 *
	 * @param string[] $bullets Feature bullets.
	 * @return string
	 */
	public static function build_short_description_html( array $bullets ) {
		$items = '';
		foreach ( $bullets as $b ) {
			$items .= '<li>' . esc_html( $b ) . '</li>';
		}

		return '<ul>' . $items . '</ul>';
	}

	/**
	 * Ensure product category exists.
	 *
	 * @return int Term ID.
	 */
	public static function ensure_category_term_id() {
		$slug = 'mba-cet-2027';
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		$insert = wp_insert_term(
			'MBA CET 2027',
			'product_cat',
			[
				'slug'        => $slug,
				'description' => 'MBA CET 2027 programs linked to TCY.',
			]
		);

		if ( is_wp_error( $insert ) ) {
			return 0;
		}

		return (int) $insert['term_id'];
	}

	/**
	 * Find product post ID by slug.
	 *
	 * @param string $slug Post name.
	 * @return int
	 */
	public static function get_product_id_by_slug( $slug ) {
		$posts = get_posts(
			[
				'post_type'      => 'product',
				'name'           => sanitize_title( $slug ),
				'post_status'    => [ 'publish', 'draft', 'pending' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		return ! empty( $posts[0] ) ? (int) $posts[0] : 0;
	}

	/**
	 * Create or update catalog products.
	 *
	 * @param bool $full_sync When true, overwrite titles, prices, descriptions, SKU from definitions.
	 * @return array{created:int,updated:int,ids:int[]}
	 */
	public static function seed( $full_sync = false ) {
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
			return [ 'created' => 0, 'updated' => 0, 'ids' => [] ];
		}

		$cat_id = self::ensure_category_term_id();
		$created = 0;
		$updated = 0;
		$ids     = [];

		foreach ( self::get_definitions() as $def ) {
			$product_id    = self::get_product_id_by_slug( $def['slug'] );
			$is_new        = ( $product_id <= 0 );
			$apply_content = $full_sync || $is_new;

			if ( $is_new ) {
				$product = new WC_Product_Simple();
				$product->set_status( 'publish' );
				$product->set_catalog_visibility( 'visible' );
				$product->set_virtual( true );
				$product->set_manage_stock( false );
				$product->set_slug( $def['slug'] );
			} else {
				$product = wc_get_product( $product_id );
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
			}

			if ( $apply_content ) {
				$product->set_name( $def['name'] );
				$product->set_sku( $def['sku'] );
				$product->set_regular_price( $def['regular_price'] );
				if ( $def['sale_price'] !== '' && $def['sale_price'] !== null ) {
					$product->set_sale_price( $def['sale_price'] );
				} else {
					$product->set_sale_price( '' );
				}
			}

			// Always refresh per-plan bullets (safe sync); stops every course showing identical copy.
			$product->set_description( self::build_description_html( $def['pack_line'], $def['feature_bullets'] ) );
			$product->set_short_description( self::build_short_description_html( $def['feature_bullets'] ) );

			$menu_order_map = [
				'cet-nmat-snap-elite-with-1-on-1-mentorship' => 10,
				'cet-nmat-snap-elite'                        => 20,
				'cet-elite-with-1-on-1-mentorship'           => 30,
				'cet-elite'                                  => 40,
				'cet-solo-self-study'                        => 50,
			];
			$product->set_menu_order( isset( $menu_order_map[ $def['slug'] ] ) ? (int) $menu_order_map[ $def['slug'] ] : 0 );

			$new_id = $product->save();
			if ( ! $new_id ) {
				continue;
			}

			$ids[] = (int) $new_id;

			self::apply_tcy_meta_from_definition( (int) $new_id, $def );
			update_post_meta( $new_id, '_ttp_validity', '12 Months' );
			update_post_meta( $new_id, '_ttp_course_badge_label', __( 'MBA CET 2027', 'ttp-woocommerce' ) );

			update_post_meta( $new_id, '_ttp_course_feature_grid', self::build_feature_grid_from_bullets( $def['feature_bullets'] ) );

			if ( $cat_id ) {
				wp_set_object_terms( $new_id, [ (int) $cat_id ], 'product_cat', true );
			}

			if ( $is_new ) {
				++$created;
			} else {
				++$updated;
			}
		}

		update_option( 'ttp_catalog_mba_cet_2027_version', self::CATALOG_VERSION );

		return [ 'created' => $created, 'updated' => $updated, 'ids' => $ids ];
	}
}
