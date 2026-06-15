<?php
/**
 * Shared MAH MBA CET 2026 PDF → posts import (used by CLI + admin UI).
 *
 * @package TheTopPercentile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default folder: wp-content/tools/mba-cet-pdfs/
 *
 * @return string Normalized absolute path.
 */
function tpsp_mba_cet_import_default_pdf_dir() {
    return wp_normalize_path(dirname(__FILE__) . '/mba-cet-pdfs');
}

/**
 * Validate admin-submitted folder: must exist and live under wp-content.
 *
 * @param string $posted Raw POST value (may be empty for default).
 * @return string|\WP_Error Normalized path or error.
 */
function tpsp_mba_cet_import_sanitize_admin_pdf_dir($posted) {
    $posted = is_string($posted) ? trim(wp_unslash($posted)) : '';

    $content = wp_normalize_path(WP_CONTENT_DIR);
    if ($posted === '') {
        return tpsp_mba_cet_import_default_pdf_dir();
    }

    $posted = str_replace("\0", '', $posted);
    $candidate = wp_normalize_path($posted);

    if (!file_exists($candidate) || !is_dir($candidate)) {
        return new WP_Error(
            'missing',
            __('That folder does not exist on the server. Upload PDFs first (e.g. via SFTP), then run the import again.', 'tpsp-mba-cet-import')
        );
    }

    $real = realpath($candidate);
    if ($real === false) {
        return new WP_Error('missing', __('Could not resolve that path.', 'tpsp-mba-cet-import'));
    }

    $real = wp_normalize_path($real);
    $prefix = rtrim($content, '/\\') . '/';
    if (strncmp($real, $prefix, strlen($prefix)) !== 0) {
        return new WP_Error(
            'outside',
            __('For security, the folder must be inside wp-content.', 'tpsp-mba-cet-import')
        );
    }

    return $real;
}

/**
 * Import PDFs from a directory into posts under MAH MBA CET 2026 category.
 *
 * @param string $pdf_dir Absolute normalized path to folder containing *.pdf.
 * @return array|\WP_Error {
 *   @type bool   $success
 *   @type int    $cat_id
 *   @type int    $created
 *   @type int    $skipped
 *   @type string[] $log    Progress lines (info).
 *   @type string[] $errors Warning/error lines.
 * }
 */
function tpsp_mba_cet_import_pdfs($pdf_dir) {
    $pdf_dir = wp_normalize_path($pdf_dir);

    if (!is_dir($pdf_dir)) {
        return new WP_Error('no_dir', sprintf('Folder not found: %s', $pdf_dir));
    }

    $slug_candidates = apply_filters(
        'tpsp_mba_cet_import_category_slugs',
        ['mah-mba-cet-2026', 'mah-mba-cet-2026-', 'mba-cet-2026-mah']
    );

    $cat_id = 0;
    foreach ($slug_candidates as $slug) {
        $slug = sanitize_title($slug);
        $term = get_term_by('slug', $slug, 'category');
        if ($term && !is_wp_error($term)) {
            $cat_id = (int) $term->term_id;
            break;
        }
    }

    if (!$cat_id) {
        $term = get_term_by('name', 'mah mba cet 2026', 'category');
        if ($term && !is_wp_error($term)) {
            $cat_id = (int) $term->term_id;
        }
    }

    if (!$cat_id) {
        return new WP_Error(
            'no_cat',
            'Category not found. Create category slug mah-mba-cet-2026 (or name "mah mba cet 2026") then run again.'
        );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $files = glob($pdf_dir . '/*.pdf');
    if (empty($files)) {
        return new WP_Error('no_pdfs', sprintf('No PDF files in %s', $pdf_dir));
    }

    $author_id = get_current_user_id();
    if ($author_id < 1) {
        $author_id = 1;
    }

    $log = [];
    $errors = [];
    $created = 0;
    $skipped = 0;

    $log[] = sprintf('Using category ID %d (MAH MBA CET 2026).', $cat_id);

    foreach ($files as $pdf_path) {
        $filename = basename($pdf_path);
        $title = preg_replace('/\.pdf$/i', '', $filename);
        $title = str_replace(['_', '  '], [' ', ' '], $title);
        $title = trim($title);

        global $wpdb;
        $dup_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE p.post_title = %s AND p.post_type = 'post' AND p.post_status IN ('publish','draft','pending','private')
				AND tt.taxonomy = 'category' AND tt.term_id = %d
				LIMIT 1",
                $title,
                $cat_id
            )
        );

        if ($dup_id > 0) {
            $log[] = "Skip (exists): {$title}";
            ++$skipped;
            continue;
        }

        $contents = file_get_contents($pdf_path);
        if ($contents === false) {
            $errors[] = "Read failed: {$pdf_path}";
            continue;
        }

        $upload = wp_upload_bits($filename, null, $contents);
        if (!empty($upload['error'])) {
            $errors[] = "Upload bits failed {$filename}: {$upload['error']}";
            continue;
        }

        $filetype = wp_check_filetype($filename, null);
        $attachment_post = [
            'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'application/pdf',
            'post_title'     => $title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment_post, $upload['file']);
        if (is_wp_error($attach_id)) {
            $errors[] = 'Attachment failed ' . $filename . ': ' . $attach_id->get_error_message();
            continue;
        }

        $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $meta);

        $pdf_url = wp_get_attachment_url($attach_id);
        $content = sprintf(
            '<p class="tpsp-pdf-lead"><a class="button" href="%s" target="_blank" rel="noopener">%s</a></p>' .
            '<p><iframe src="%s" width="100%" height="900" style="border:1px solid #e5e7eb;border-radius:8px;min-height:70vh;" loading="lazy" title="%s"></iframe></p>',
            esc_url($pdf_url),
            esc_html__('View / download PDF', 'default'),
            esc_url($pdf_url),
            esc_attr($title)
        );

        $post_id = wp_insert_post(
            [
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'post',
                'post_author'  => $author_id,
            ],
            true
        );

        if (is_wp_error($post_id)) {
            $errors[] = 'Post failed ' . $title . ': ' . $post_id->get_error_message();
            continue;
        }

        wp_set_post_categories($post_id, [$cat_id]);
        update_post_meta($post_id, '_tpsp_imported_pdf', $filename);

        $log[] = "Created post #{$post_id}: {$title}";
        ++$created;
    }

    $log[] = '';
    $log[] = sprintf('Done. Created %d posts, skipped %d duplicates.', $created, $skipped);

    return [
        'success' => true,
        'cat_id'  => $cat_id,
        'created' => $created,
        'skipped' => $skipped,
        'log'     => $log,
        'errors'  => $errors,
    ];
}
