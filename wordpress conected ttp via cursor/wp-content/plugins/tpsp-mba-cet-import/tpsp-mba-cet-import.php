<?php
/**
 * Plugin Name: TPSP MBA CET PDF Import
 * Description: One-time tool: import PDFs from a folder into posts under the MAH MBA CET 2026 category. Deactivate and delete after use.
 * Version: 1.0.0
 * Author: The Top Percentile
 * Text Domain: tpsp-mba-cet-import
 *
 * @package TheTopPercentile
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TPSP_MBA_CET_IMPORT_PLUGIN_FILE', __FILE__);

/**
 * Load shared library from wp-content/tools/.
 */
function tpsp_mba_cet_import_load_lib() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $lib = WP_CONTENT_DIR . '/tools/import-mba-cet-2026-lib.php';
    if (is_readable($lib)) {
        require_once $lib;
        $loaded = true;
    }
}

add_action('admin_menu', function () {
    add_management_page(
        __('MBA CET PDF Import', 'tpsp-mba-cet-import'),
        __('MBA CET PDF Import', 'tpsp-mba-cet-import'),
        'manage_options',
        'tpsp-mba-cet-import',
        'tpsp_mba_cet_import_render_admin_page'
    );
});

/**
 * @return void
 */
function tpsp_mba_cet_import_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to run this import.', 'tpsp-mba-cet-import'));
    }

    tpsp_mba_cet_import_load_lib();
    if (!function_exists('tpsp_mba_cet_import_pdfs')) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Missing wp-content/tools/import-mba-cet-2026-lib.php. Upload that file from your project, then refresh.',
            'tpsp-mba-cet-import'
        );
        echo '</p></div>';
        return;
    }

    $default_dir = function_exists('tpsp_mba_cet_import_default_pdf_dir')
        ? tpsp_mba_cet_import_default_pdf_dir()
        : wp_normalize_path(WP_CONTENT_DIR . '/tools/mba-cet-pdfs');

    $result = null;
    $fatal = null;

    if (isset($_POST['tpsp_mba_cet_import_run']) && isset($_POST['_wpnonce'])) {
        check_admin_referer('tpsp_mba_cet_import_run', '_wpnonce');

        $pdf_dir = tpsp_mba_cet_import_sanitize_admin_pdf_dir($_POST['pdf_dir'] ?? '');
        if (is_wp_error($pdf_dir)) {
            $fatal = $pdf_dir;
        } else {
            $result = tpsp_mba_cet_import_pdfs($pdf_dir);
            if (is_wp_error($result)) {
                $fatal = $result;
                $result = null;
            }
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <p>
            <?php
            esc_html_e(
                'Upload PDFs to the server first (SFTP or File Manager), then run this once. Posts are created in the MAH MBA CET 2026 category. Duplicate titles in that category are skipped.',
                'tpsp-mba-cet-import'
            );
            ?>
        </p>

        <ol>
            <li><?php esc_html_e('Recommended folder:', 'tpsp-mba-cet-import'); ?> <code>wp-content/tools/mba-cet-pdfs/</code></li>
            <li><?php esc_html_e('Leave the path empty to use that default folder.', 'tpsp-mba-cet-import'); ?></li>
            <li><?php esc_html_e('After a successful import, deactivate and delete this plugin and remove any leftover PDFs from tools if you want.', 'tpsp-mba-cet-import'); ?></li>
        </ol>

        <?php if ($fatal instanceof WP_Error) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($fatal->get_error_message()); ?></p></div>
        <?php endif; ?>

        <?php if (is_array($result) && !empty($result['success'])) : ?>
            <div class="notice notice-success">
                <p>
                    <?php
                    printf(
                        /* translators: 1: created count, 2: skipped count */
                        esc_html__('Import finished. Created %1$d posts, skipped %2$d duplicates.', 'tpsp-mba-cet-import'),
                        (int) $result['created'],
                        (int) $result['skipped']
                    );
                    ?>
                </p>
            </div>
            <?php if (!empty($result['errors'])) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Some files failed (see log below).', 'tpsp-mba-cet-import'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('tpsp_mba_cet_import_run'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="tpsp_pdf_dir"><?php esc_html_e('PDF folder (absolute path on server)', 'tpsp-mba-cet-import'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="large-text code" id="tpsp_pdf_dir" name="pdf_dir" value=""
                               placeholder="<?php echo esc_attr($default_dir); ?>" autocomplete="off"/>
                        <p class="description">
                            <?php esc_html_e('Must be inside wp-content. Example:', 'tpsp-mba-cet-import'); ?>
                            <code><?php echo esc_html($default_dir); ?></code>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Run import', 'tpsp-mba-cet-import'), 'primary', 'tpsp_mba_cet_import_run'); ?>
        </form>

        <?php if (is_array($result) && !empty($result['log'])) : ?>
            <h2><?php esc_html_e('Log', 'tpsp-mba-cet-import'); ?></h2>
            <textarea readonly rows="16" class="large-text code" style="width:100%;font-family:monospace;"><?php
                $lines = $result['log'];
                if (!empty($result['errors'])) {
                    $lines[] = '';
                    $lines[] = '--- ' . __('Errors', 'tpsp-mba-cet-import') . ' ---';
                    foreach ($result['errors'] as $err) {
                        $lines[] = $err;
                    }
                }
                echo esc_textarea(implode("\n", $lines));
            ?></textarea>
        <?php endif; ?>
    </div>
    <?php
}
