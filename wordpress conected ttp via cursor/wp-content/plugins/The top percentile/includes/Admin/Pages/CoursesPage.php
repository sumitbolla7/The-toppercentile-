<?php

namespace TTP_CRM\Admin\Pages;

defined('ABSPATH') || exit;

class CoursesPage
{
    private const OPTION_KEY = 'ttp_crm_course_map';

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ttp-crm'));
        }

        $action_url = admin_url('admin-post.php');
        $raw_map    = get_option(self::OPTION_KEY, array());
        $map        = is_array($raw_map) ? $raw_map : array();
        ?>
        <div class="wrap ttp-crm-wrap">
            <h1><?php echo esc_html__('TTP CRM Courses', 'ttp-crm'); ?></h1>
            <p><?php echo esc_html__('Store your TCY courses here. When you later add matching WooCommerce products, purchases will auto-link to these courses by Product ID.', 'ttp-crm'); ?></p>

            <?php $this->render_notices(); ?>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Import / Update Course Mapping (JSON)', 'ttp-crm'); ?></h2>
                <p class="description"><?php echo esc_html__('Paste the JSON you shared (data[] list). We will store a mapping of Product ID → Course/Category.', 'ttp-crm'); ?></p>
                <form method="post" action="<?php echo esc_url($action_url); ?>">
                    <?php wp_nonce_field('ttp_crm_save_courses'); ?>
                    <input type="hidden" name="action" value="ttp_crm_save_courses" />
                    <textarea name="courses_json" rows="14" class="large-text code" placeholder="{ &quot;data&quot;: [ ... ] }"></textarea>
                    <?php submit_button(__('Save Course Mapping', 'ttp-crm')); ?>
                </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Current Mapping', 'ttp-crm'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Product ID', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Product Name', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Course ID', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Course Name', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Category', 'ttp-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($map)) : ?>
                            <tr><td colspan="5"><?php echo esc_html__('No course mapping saved yet.', 'ttp-crm'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($map as $product_id => $row) : ?>
                                <tr>
                                    <td><?php echo esc_html((string) $product_id); ?></td>
                                    <td><?php echo esc_html((string) ($row['product_name'] ?? '')); ?></td>
                                    <td><?php echo esc_html((string) ($row['course_id'] ?? '')); ?></td>
                                    <td><?php echo esc_html((string) ($row['course_name'] ?? '')); ?></td>
                                    <td><?php echo esc_html((string) ($row['category_name'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function handle_save()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }

        check_admin_referer('ttp_crm_save_courses');

        $json = isset($_POST['courses_json']) ? wp_unslash($_POST['courses_json']) : '';
        $json = is_string($json) ? trim($json) : '';
        if ($json === '') {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-courses&status=invalid_json'));
            exit;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-courses&status=invalid_json'));
            exit;
        }

        $data = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : array();
        $map  = array();

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $product_id   = isset($item['Product_id']) ? (string) $item['Product_id'] : '';
            $product_name = isset($item['Product_name']) ? (string) $item['Product_name'] : '';
            if ($product_id === '') {
                continue;
            }

            $course_id = '';
            $course_name = '';
            $category_name = '';
            $category_id = '';

            $course_details = isset($item['Course_details']) && is_array($item['Course_details']) ? $item['Course_details'] : array();
            if (!empty($course_details) && is_array($course_details[0])) {
                $course_id   = isset($course_details[0]['course_id']) ? (string) $course_details[0]['course_id'] : '';
                $course_name = isset($course_details[0]['course_name']) ? (string) $course_details[0]['course_name'] : '';
                $category_details = isset($course_details[0]['Category_details']) && is_array($course_details[0]['Category_details']) ? $course_details[0]['Category_details'] : array();
                if (!empty($category_details) && is_array($category_details[0])) {
                    $category_id   = isset($category_details[0]['category_id']) ? (string) $category_details[0]['category_id'] : '';
                    $category_name = isset($category_details[0]['categoy_name']) ? (string) $category_details[0]['categoy_name'] : '';
                }
            }

            $map[$product_id] = array(
                'product_name'  => sanitize_text_field($product_name),
                'course_id'     => sanitize_text_field($course_id),
                'course_name'   => sanitize_text_field($course_name),
                'category_id'   => sanitize_text_field($category_id),
                'category_name' => sanitize_text_field($category_name),
            );
        }

        update_option(self::OPTION_KEY, $map, false);

        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-courses&status=saved&count=' . absint(count($map))));
        exit;
    }

    private function render_notices()
    {
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!in_array($status, array('saved', 'invalid_json'), true)) {
            return;
        }

        $messages = array(
            'saved'        => sprintf(__('Course mapping saved. Products mapped: %1$d', 'ttp-crm'), isset($_GET['count']) ? absint($_GET['count']) : 0), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'invalid_json' => __('Invalid JSON provided. Please paste the full JSON response.', 'ttp-crm'),
        );
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($messages[$status]); ?></p>
        </div>
        <?php
    }
}

