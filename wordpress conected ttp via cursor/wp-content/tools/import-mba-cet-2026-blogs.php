<?php
/**
 * Import PDF “blogs” from a folder into WordPress posts under category MAH MBA CET 2026.
 *
 * Usage (SSH at WordPress root):
 *   php wp-content/tools/import-mba-cet-2026-blogs.php "/full/path/to/folder/with/pdfs"
 *
 * If no path is passed, uses: wp-content/tools/mba-cet-pdfs/ (copy PDFs there first).
 *
 * Or use WP Admin → Tools → MBA CET PDF Import (no SSH).
 *
 * Run once; delete this file after success if you only use the admin UI.
 *
 * @package TheTopPercentile
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Run from command line only.\n");
    exit(1);
}

$wp_load = dirname(__DIR__, 2) . '/wp-load.php';
if (!is_readable($wp_load)) {
    $wp_load = dirname(__DIR__) . '/../wp-load.php';
}
if (!is_readable($wp_load)) {
    fwrite(STDERR, "Could not find wp-load.php. Place this script in wp-content/tools/ inside a WordPress install.\n");
    exit(1);
}

require_once $wp_load;

if (!function_exists('wp_upload_bits')) {
    fwrite(STDERR, "WordPress did not load correctly.\n");
    exit(1);
}

require_once dirname(__FILE__) . '/import-mba-cet-2026-lib.php';

$pdf_dir = isset($argv[1]) ? $argv[1] : dirname(__FILE__) . '/mba-cet-pdfs';
$pdf_dir = wp_normalize_path($pdf_dir);

$result = tpsp_mba_cet_import_pdfs($pdf_dir);

if (is_wp_error($result)) {
    fwrite(STDERR, $result->get_error_message() . "\n");
    exit(1);
}

foreach ($result['log'] as $line) {
    echo $line . "\n";
}
foreach ($result['errors'] as $line) {
    fwrite(STDERR, $line . "\n");
}

exit(0);
