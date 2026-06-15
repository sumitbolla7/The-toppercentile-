<?php

namespace TTP_CRM\Admin\Pages;

use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class ContactsPage
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ttp-crm'));
        }

        if (!get_transient('ttp_crm_backfill_names_v2')) {
            $this->contacts->backfill_missing_contact_names(500);
            $this->contacts->backfill_names_from_wp_users(500);
            set_transient('ttp_crm_backfill_names_v2', 1, WEEK_IN_SECONDS);
        }

        $edit_id       = isset($_GET['edit']) ? absint($_GET['edit']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search        = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $filter_stage  = isset($_GET['stage']) ? sanitize_key(wp_unslash($_GET['stage'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $filter_tag    = isset($_GET['tag']) ? sanitize_text_field(wp_unslash($_GET['tag'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $stages        = $this->contacts->get_stages();
        $editing       = $edit_id > 0 ? $this->contacts->find($edit_id) : null;
        $paged         = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per_page      = 20;
        $filters       = array(
            'search' => $search,
            'stage'  => $filter_stage,
            'tag'    => $filter_tag,
        );
        $contacts      = $this->contacts->all(
            $filters,
            array(
                'page'     => $paged,
                'per_page' => $per_page,
            )
        );
        $total_contacts = $this->contacts->count_filtered($filters);
        $total_pages    = max(1, (int) ceil($total_contacts / $per_page));
        // Pipeline board is meant as a visual overview; pull more rows than the table.
        $pipeline_items = $this->contacts->all($filters, array('page' => 1, 'per_page' => 2000));
        $action_url    = admin_url('admin-post.php');
        $redirect_page = admin_url('admin.php?page=ttp-crm-contacts');
        ?>
        <div class="wrap ttp-crm-wrap">
            <h1><?php echo esc_html__('TTP CRM Contacts', 'ttp-crm'); ?></h1>
            <p style="margin: 6px 0 0; color: #50575e;">
                <?php echo esc_html(sprintf(__('Plugin v%1$s • Total contacts: %2$d', 'ttp-crm'), defined('TTP_CRM_VERSION') ? TTP_CRM_VERSION : 'unknown', (int) $this->contacts->count_contacts())); ?>
            </p>

            <?php $this->render_notices(); ?>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Search and Filters', 'ttp-crm'); ?></h2>
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="ttp-inline-form">
                    <input type="hidden" name="page" value="ttp-crm-contacts" />
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Search name, email, phone', 'ttp-crm'); ?>" />
                    <select name="stage">
                        <option value=""><?php echo esc_html__('All Stages', 'ttp-crm'); ?></option>
                        <?php foreach ($stages as $stage) : ?>
                            <option value="<?php echo esc_attr($stage); ?>" <?php selected($filter_stage, $stage); ?>>
                                <?php echo esc_html(ucfirst($stage)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="tag" value="<?php echo esc_attr($filter_tag); ?>" placeholder="<?php echo esc_attr__('Filter by tag', 'ttp-crm'); ?>" />
                    <?php submit_button(__('Apply', 'ttp-crm'), 'secondary', 'submit', false); ?>
                    <a class="button" href="<?php echo esc_url($redirect_page); ?>"><?php echo esc_html__('Reset', 'ttp-crm'); ?></a>
                </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('CSV Import and Export', 'ttp-crm'); ?></h2>
                <form method="post" action="<?php echo esc_url($action_url); ?>" class="ttp-inline-form">
                    <?php wp_nonce_field('ttp_crm_export_contacts'); ?>
                    <input type="hidden" name="action" value="ttp_crm_export_contacts" />
                    <?php submit_button(__('Export CSV', 'ttp-crm'), 'secondary', 'submit', false); ?>
                </form>
                <form method="post" action="<?php echo esc_url($action_url); ?>" enctype="multipart/form-data" class="ttp-inline-form">
                    <?php wp_nonce_field('ttp_crm_import_contacts'); ?>
                    <input type="hidden" name="action" value="ttp_crm_import_contacts" />
                    <input type="file" name="csv_file" accept=".csv" required />
                    <?php submit_button(__('Import CSV', 'ttp-crm'), 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Demo Data', 'ttp-crm'); ?></h2>
                <p><?php echo esc_html__('Need to preview the pipeline at scale? Generate 1000 dummy contacts (admin only).', 'ttp-crm'); ?></p>
                <form method="post" action="<?php echo esc_url($action_url); ?>" class="ttp-inline-form" onsubmit="return confirm('<?php echo esc_js(__('This will add 1000 dummy contacts. Continue?', 'ttp-crm')); ?>');">
                    <?php wp_nonce_field('ttp_crm_seed_dummy_contacts'); ?>
                    <input type="hidden" name="action" value="ttp_crm_seed_dummy_contacts" />
                    <?php submit_button(__('Generate 1000 Dummy Contacts', 'ttp-crm'), 'secondary', 'submit', false); ?>
                </form>
                <form method="post" action="<?php echo esc_url($action_url); ?>" class="ttp-inline-form" onsubmit="return confirm('<?php echo esc_js(__('This will delete seeded dummy contacts. Continue?', 'ttp-crm')); ?>');">
                    <?php wp_nonce_field('ttp_crm_purge_dummy_contacts'); ?>
                    <input type="hidden" name="action" value="ttp_crm_purge_dummy_contacts" />
                    <?php submit_button(__('Remove Dummy Contacts', 'ttp-crm'), 'delete', 'submit', false); ?>
                </form>
            </div>

            <div class="ttp-card">
            <h2><?php echo esc_html($editing ? __('Edit Contact', 'ttp-crm') : __('Add Contact', 'ttp-crm')); ?></h2>
            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <?php wp_nonce_field('ttp_crm_save_contact'); ?>
                <input type="hidden" name="action" value="ttp_crm_save_contact" />
                <input type="hidden" name="contact_id" value="<?php echo esc_attr($editing['id'] ?? 0); ?>" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="ttp_crm_first_name"><?php echo esc_html__('First Name', 'ttp-crm'); ?></label></th>
                            <td><input name="first_name" type="text" id="ttp_crm_first_name" class="regular-text" value="<?php echo esc_attr($editing['first_name'] ?? ''); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_last_name"><?php echo esc_html__('Last Name', 'ttp-crm'); ?></label></th>
                            <td><input name="last_name" type="text" id="ttp_crm_last_name" class="regular-text" value="<?php echo esc_attr($editing['last_name'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_email"><?php echo esc_html__('Email', 'ttp-crm'); ?></label></th>
                            <td><input name="email" type="email" id="ttp_crm_email" class="regular-text" value="<?php echo esc_attr($editing['email'] ?? ''); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_phone"><?php echo esc_html__('Phone', 'ttp-crm'); ?></label></th>
                            <td><input name="phone" type="text" id="ttp_crm_phone" class="regular-text" value="<?php echo esc_attr($editing['phone'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_stage"><?php echo esc_html__('Pipeline Stage', 'ttp-crm'); ?></label></th>
                            <td>
                                <select name="stage" id="ttp_crm_stage">
                                    <?php foreach ($stages as $stage) : ?>
                                        <option value="<?php echo esc_attr($stage); ?>" <?php selected($editing['stage'] ?? 'new', $stage); ?>>
                                            <?php echo esc_html(ucfirst($stage)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_tags"><?php echo esc_html__('Tags', 'ttp-crm'); ?></label></th>
                            <td>
                                <input name="tags" type="text" id="ttp_crm_tags" class="regular-text" value="<?php echo esc_attr($editing['tags'] ?? ''); ?>" />
                                <p class="description"><?php echo esc_html__('Comma separated tags. Example: warm lead, vip, referral', 'ttp-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_course_name"><?php echo esc_html__('Course', 'ttp-crm'); ?></label></th>
                            <td><input name="course_name" type="text" id="ttp_crm_course_name" class="regular-text" value="<?php echo esc_attr($editing['course_name'] ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_lead_source"><?php echo esc_html__('Lead Source', 'ttp-crm'); ?></label></th>
                            <td><input name="lead_source" type="text" id="ttp_crm_lead_source" class="regular-text" value="<?php echo esc_attr($editing['lead_source'] ?? ''); ?>" placeholder="Meta Ads / Google / Referral" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_revenue_amount"><?php echo esc_html__('Revenue Amount', 'ttp-crm'); ?></label></th>
                            <td><input name="revenue_amount" type="number" step="0.01" min="0" id="ttp_crm_revenue_amount" class="regular-text" value="<?php echo esc_attr($editing['revenue_amount'] ?? '0'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_student_profile"><?php echo esc_html__('Student Profile', 'ttp-crm'); ?></label></th>
                            <td><textarea name="student_profile" id="ttp_crm_student_profile" rows="3" class="large-text"><?php echo esc_textarea($editing['student_profile'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_purchase_summary"><?php echo esc_html__('Purchase Summary', 'ttp-crm'); ?></label></th>
                            <td><textarea name="purchase_summary" id="ttp_crm_purchase_summary" rows="3" class="large-text"><?php echo esc_textarea($editing['purchase_summary'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_progress_notes"><?php echo esc_html__('Progress', 'ttp-crm'); ?></label></th>
                            <td><textarea name="progress_notes" id="ttp_crm_progress_notes" rows="3" class="large-text"><?php echo esc_textarea($editing['progress_notes'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_follow_up"><?php echo esc_html__('Follow-up Reminder', 'ttp-crm'); ?></label></th>
                            <td><input type="datetime-local" name="follow_up_at" id="ttp_crm_follow_up" value="<?php echo esc_attr($this->to_datetime_local($editing['follow_up_at'] ?? '')); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_notes"><?php echo esc_html__('Internal Notes', 'ttp-crm'); ?></label></th>
                            <td><textarea name="internal_notes" id="ttp_crm_notes" rows="4" class="large-text"><?php echo esc_textarea($editing['internal_notes'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ttp_crm_history"><?php echo esc_html__('Communication History', 'ttp-crm'); ?></label></th>
                            <td><textarea name="communication_history" id="ttp_crm_history" rows="5" class="large-text"><?php echo esc_textarea($editing['communication_history'] ?? ''); ?></textarea></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($editing ? __('Update Contact', 'ttp-crm') : __('Add Contact', 'ttp-crm')); ?>
            </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Pipeline Board (Drag and Drop)', 'ttp-crm'); ?></h2>
                <p><?php echo esc_html__('Drag contact cards between stages to quickly update lead status.', 'ttp-crm'); ?></p>
                <div class="ttp-pipeline-board">
                    <?php foreach ($stages as $stage) : ?>
                        <div class="ttp-pipeline-column" data-stage="<?php echo esc_attr($stage); ?>">
                            <h3><?php echo esc_html(ucfirst($stage)); ?></h3>
                            <?php
                            foreach ($pipeline_items as $pipeline_contact) :
                                if ($pipeline_contact['stage'] !== $stage) {
                                    continue;
                                }
                                ?>
                                <div class="ttp-pipeline-card" draggable="true" data-contact-id="<?php echo esc_attr($pipeline_contact['id']); ?>">
                                    <strong><?php echo esc_html(trim($pipeline_contact['first_name'] . ' ' . $pipeline_contact['last_name'])); ?></strong>
                                    <small><?php echo esc_html($pipeline_contact['email']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ttp-card">
            <h2><?php echo esc_html__('All Contacts', 'ttp-crm'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Name', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Email', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Phone', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Stage', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Course', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Source', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Revenue', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Tags', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Follow-up', 'ttp-crm'); ?></th>
                        <th><?php echo esc_html__('Actions', 'ttp-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contacts)) : ?>
                        <tr>
                            <td colspan="10"><?php echo esc_html__('No contacts found.', 'ttp-crm'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($contacts as $contact) : ?>
                            <?php
                            $full_name   = $this->contacts->get_contact_display_name($contact);
                            $edit_url    = add_query_arg(
                                array(
                                    'page' => 'ttp-crm-contacts',
                                    'edit' => absint($contact['id']),
                                ),
                                admin_url('admin.php')
                            );
                            $delete_url  = wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action'     => 'ttp_crm_delete_contact',
                                        'contact_id' => absint($contact['id']),
                                    ),
                                    admin_url('admin-post.php')
                                ),
                                'ttp_crm_delete_contact_' . absint($contact['id'])
                            );
                            ?>
                            <tr>
                                <td><?php echo esc_html($full_name !== '' ? $full_name : __('(No Name)', 'ttp-crm')); ?><?php if ($full_name !== '' && trim($contact['first_name'] . ' ' . $contact['last_name']) === '') : ?><span class="description"> <?php echo esc_html__('(from email)', 'ttp-crm'); ?></span><?php endif; ?></td>
                                <td><?php echo esc_html($contact['email']); ?></td>
                                <td><?php echo esc_html($contact['phone']); ?></td>
                                <td><span class="ttp-chip"><?php echo esc_html(ucfirst($contact['stage'])); ?></span></td>
                                <td><?php echo esc_html($contact['course_name']); ?></td>
                                <td><?php echo esc_html($contact['lead_source']); ?></td>
                                <td><?php echo esc_html(number_format((float) $contact['revenue_amount'], 2)); ?></td>
                                <td><?php echo esc_html($contact['tags']); ?></td>
                                <td><?php echo esc_html($contact['follow_up_at'] ?: '-'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Edit', 'ttp-crm'); ?></a>
                                    |
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this contact?', 'ttp-crm')); ?>');">
                                        <?php echo esc_html__('Delete', 'ttp-crm'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php $this->render_pagination($paged, $total_pages); ?>
            </div>

            <p style="margin-top:14px;">
                <a class="button" href="<?php echo esc_url($redirect_page); ?>"><?php echo esc_html__('Reset Form', 'ttp-crm'); ?></a>
            </p>
        </div>
        <?php
    }

    public function handle_save()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }

        check_admin_referer('ttp_crm_save_contact');

        $contact_id = isset($_POST['contact_id']) ? absint($_POST['contact_id']) : 0;
        $data       = $this->sanitize_contact_data($_POST);

        if ($contact_id > 0) {
            $result = $this->contacts->update($contact_id, $data);
            $status = is_wp_error($result) ? 'duplicate_email' : 'updated';
        } else {
            $result = $this->contacts->insert($data);
            $status = is_wp_error($result) ? 'duplicate_email' : 'created';
        }

        $redirect_url = add_query_arg(
            array(
                'page'   => 'ttp-crm-contacts',
                'status' => $status,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_update_stage()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ttp-crm')), 403);
        }

        check_ajax_referer('ttp_crm_stage_update', 'nonce');

        $contact_id = isset($_POST['contact_id']) ? absint($_POST['contact_id']) : 0;
        $stage      = isset($_POST['stage']) ? sanitize_key(wp_unslash($_POST['stage'])) : '';

        if ($contact_id < 1 || empty($stage)) {
            wp_send_json_error(array('message' => __('Invalid request.', 'ttp-crm')), 400);
        }

        $result = $this->contacts->update_stage($contact_id, $stage);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 400);
        }

        wp_send_json_success(array('message' => __('Stage updated.', 'ttp-crm')));
    }

    public function handle_delete()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }

        $contact_id = isset($_GET['contact_id']) ? absint($_GET['contact_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($contact_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts'));
            exit;
        }

        check_admin_referer('ttp_crm_delete_contact_' . $contact_id);
        $this->contacts->delete($contact_id);

        $redirect_url = add_query_arg(
            array(
                'page'   => 'ttp-crm-contacts',
                'status' => 'deleted',
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_export()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }
        check_admin_referer('ttp_crm_export_contacts');

        $contacts = $this->contacts->all();
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ttp-crm-contacts.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, array('first_name', 'last_name', 'email', 'phone', 'stage', 'tags', 'course_name', 'lead_source', 'revenue_amount', 'student_profile', 'purchase_summary', 'progress_notes', 'follow_up_at', 'internal_notes', 'communication_history'));
        foreach ($contacts as $contact) {
            fputcsv(
                $out,
                array(
                    $contact['first_name'],
                    $contact['last_name'],
                    $contact['email'],
                    $contact['phone'],
                    $contact['stage'],
                    $contact['tags'],
                    $contact['course_name'],
                    $contact['lead_source'],
                    $contact['revenue_amount'],
                    $contact['student_profile'],
                    $contact['purchase_summary'],
                    $contact['progress_notes'],
                    $contact['follow_up_at'],
                    $contact['internal_notes'],
                    $contact['communication_history'],
                )
            );
        }
        fclose($out);
        exit;
    }

    public function handle_import()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }
        check_admin_referer('ttp_crm_import_contacts');

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=import_failed'));
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r'); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!$file) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=import_failed'));
            exit;
        }

        $header = fgetcsv($file);
        if (!$header) {
            fclose($file);
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=import_failed'));
            exit;
        }

        $created = 0;
        $skipped = 0;
        while (($row = fgetcsv($file)) !== false) {
            $mapped = $this->map_csv_row($header, $row);
            if (empty($mapped['email']) || empty($mapped['first_name'])) {
                $skipped++;
                continue;
            }
            $result = $this->contacts->insert($mapped);
            if (is_wp_error($result)) {
                $skipped++;
                continue;
            }
            $created++;
        }

        fclose($file);
        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=imported&count=' . $created . '&skipped=' . $skipped));
        exit;
    }

    public function handle_seed_dummy_contacts()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }

        check_admin_referer('ttp_crm_seed_dummy_contacts');

        $stages = $this->contacts->get_stages();
        $stage_pool = array();

        // Weighted distribution for a realistic-looking board.
        foreach ($stages as $stage) {
            $weight = 1;
            if ($stage === 'new') {
                $weight = 4;
            } elseif ($stage === 'contacted') {
                $weight = 3;
            } elseif ($stage === 'interested') {
                $weight = 4;
            } elseif ($stage === 'enrolled') {
                $weight = 3;
            } elseif ($stage === 'inactive') {
                $weight = 2;
            }
            for ($i = 0; $i < $weight; $i++) {
                $stage_pool[] = $stage;
            }
        }

        $courses = array('TTP Web Dev', 'TTP Data Analytics', 'TTP AI Bootcamp', 'TTP Placement Prep');
        $sources = array('Meta Ads', 'Google', 'Referral', 'Website Login', 'Instagram', 'YouTube');
        $tags    = array('warm', 'vip', 'referral', 'newsletter', 'website-login', 'website-purchase');

        $created = 0;
        $skipped = 0;
        $seed    = (int) current_time('timestamp');

        for ($i = 1; $i <= 1000; $i++) {
            $num   = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $email = sprintf('dummy.%d.%s@example.com', $seed, $num);
            $stage = $stage_pool[array_rand($stage_pool)];

            $course = $courses[array_rand($courses)];
            $source = $sources[array_rand($sources)];

            $tag_a = $tags[array_rand($tags)];
            $tag_b = $tags[array_rand($tags)];
            $tag_csv = implode(', ', array_unique(array($tag_a, $tag_b)));

            $revenue = 0;
            if ($stage === 'enrolled') {
                $revenue = (float) (4999 + (array_rand(array(0, 1, 2, 3, 4)) * 1000));
            }

            $now = current_time('mysql');
            $result = $this->contacts->insert(
                array(
                    'first_name'            => 'Dummy',
                    'last_name'             => 'User ' . $num,
                    'email'                 => $email,
                    'phone'                 => '99999' . str_pad((string) ((int) $seed + $i) % 10000, 4, '0', STR_PAD_LEFT),
                    'stage'                 => $stage,
                    'tags'                  => $tag_csv,
                    'course_name'           => $course,
                    'lead_source'           => $source,
                    'revenue_amount'        => $revenue,
                    'student_profile'       => 'Dummy profile for preview/testing.',
                    'purchase_summary'      => $stage === 'enrolled' ? ('[' . $now . '] Dummy purchase - ' . $course) : '',
                    'progress_notes'        => '',
                    'follow_up_at'          => null,
                    'reminder_sent_at'      => null,
                    'internal_notes'        => 'Seeded dummy contact for preview.',
                    'communication_history' => '[' . $now . '] Seeded for preview.',
                )
            );

            if (is_wp_error($result)) {
                $skipped++;
                continue;
            }
            $created++;
        }

        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=seeded&count=' . $created . '&skipped=' . $skipped));
        exit;
    }

    public function handle_purge_dummy_contacts()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }

        check_admin_referer('ttp_crm_purge_dummy_contacts');

        $deleted = $this->contacts->delete_dummy_contacts();

        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-contacts&status=purged&count=' . absint($deleted)));
        exit;
    }

    private function sanitize_contact_data($request)
    {
        $first_name = isset($request['first_name']) ? sanitize_text_field(wp_unslash($request['first_name'])) : '';
        $last_name  = isset($request['last_name']) ? sanitize_text_field(wp_unslash($request['last_name'])) : '';
        $email      = isset($request['email']) ? sanitize_email(wp_unslash($request['email'])) : '';
        $phone      = isset($request['phone']) ? sanitize_text_field(wp_unslash($request['phone'])) : '';
        $stage      = isset($request['stage']) ? sanitize_key(wp_unslash($request['stage'])) : 'new';
        $tags_raw   = isset($request['tags']) ? sanitize_text_field(wp_unslash($request['tags'])) : '';
        $tags       = implode(', ', $this->contacts->parse_tags($tags_raw));
        $course_name           = isset($request['course_name']) ? sanitize_text_field(wp_unslash($request['course_name'])) : '';
        $lead_source           = isset($request['lead_source']) ? sanitize_text_field(wp_unslash($request['lead_source'])) : '';
        $revenue_amount        = isset($request['revenue_amount']) ? (float) wp_unslash($request['revenue_amount']) : 0;
        $student_profile       = isset($request['student_profile']) ? sanitize_textarea_field(wp_unslash($request['student_profile'])) : '';
        $purchase_summary      = isset($request['purchase_summary']) ? sanitize_textarea_field(wp_unslash($request['purchase_summary'])) : '';
        $progress_notes        = isset($request['progress_notes']) ? sanitize_textarea_field(wp_unslash($request['progress_notes'])) : '';
        $follow_up_at          = isset($request['follow_up_at']) ? sanitize_text_field(wp_unslash($request['follow_up_at'])) : '';
        $internal_notes        = isset($request['internal_notes']) ? sanitize_textarea_field(wp_unslash($request['internal_notes'])) : '';
        $communication_history = isset($request['communication_history']) ? sanitize_textarea_field(wp_unslash($request['communication_history'])) : '';

        if (!in_array($stage, $this->contacts->get_stages(), true)) {
            $stage = 'new';
        }

        $follow_up_at = $this->normalize_datetime($follow_up_at);

        return array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $phone,
            'stage'      => $stage,
            'tags'       => $tags,
            'course_name' => $course_name,
            'lead_source' => $lead_source,
            'revenue_amount' => max(0, $revenue_amount),
            'student_profile'       => $student_profile,
            'purchase_summary'      => $purchase_summary,
            'progress_notes'        => $progress_notes,
            'follow_up_at'          => $follow_up_at,
            'reminder_sent_at'      => null,
            'internal_notes'        => $internal_notes,
            'communication_history' => $communication_history,
        );
    }

    private function render_notices()
    {
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if (!in_array($status, array('created', 'updated', 'deleted', 'imported', 'import_failed', 'duplicate_email', 'seeded', 'purged'), true)) {
            return;
        }

        $messages = array(
            'created'       => __('Contact added.', 'ttp-crm'),
            'updated'       => __('Contact updated.', 'ttp-crm'),
            'deleted'       => __('Contact deleted.', 'ttp-crm'),
            'imported'      => sprintf(__('Contacts imported: %1$d | Skipped: %2$d', 'ttp-crm'), isset($_GET['count']) ? absint($_GET['count']) : 0, isset($_GET['skipped']) ? absint($_GET['skipped']) : 0), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'seeded'        => sprintf(__('Dummy contacts created: %1$d | Skipped: %2$d', 'ttp-crm'), isset($_GET['count']) ? absint($_GET['count']) : 0, isset($_GET['skipped']) ? absint($_GET['skipped']) : 0), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'purged'        => sprintf(__('Dummy contacts deleted: %1$d', 'ttp-crm'), isset($_GET['count']) ? absint($_GET['count']) : 0), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'import_failed' => __('CSV import failed.', 'ttp-crm'),
            'duplicate_email' => __('Duplicate email found. Contact not saved.', 'ttp-crm'),
        );
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($messages[$status]); ?></p>
        </div>
        <?php
    }

    private function normalize_datetime($value)
    {
        if (empty($value)) {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        $ts    = strtotime($value);
        if (!$ts) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $ts + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS));
    }

    private function to_datetime_local($value)
    {
        if (empty($value)) {
            return '';
        }

        $ts = strtotime($value);
        if (!$ts) {
            return '';
        }

        return gmdate('Y-m-d\TH:i', $ts + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS));
    }

    private function map_csv_row($header, $row)
    {
        $record = array();
        foreach ($header as $index => $key) {
            $record[sanitize_key($key)] = isset($row[$index]) ? $row[$index] : '';
        }

        return $this->sanitize_contact_data($record);
    }

    private function render_pagination($current_page, $total_pages)
    {
        if ($total_pages < 2) {
            return;
        }

        $base_url = add_query_arg(
            array(
                'page'  => 'ttp-crm-contacts',
                's'     => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'stage' => isset($_GET['stage']) ? sanitize_key(wp_unslash($_GET['stage'])) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'tag'   => isset($_GET['tag']) ? sanitize_text_field(wp_unslash($_GET['tag'])) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ),
            admin_url('admin.php')
        );
        ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo wp_kses_post(paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%', $base_url),
                    'format'    => '',
                    'current'   => max(1, $current_page),
                    'total'     => max(1, $total_pages),
                    'prev_text' => __('&laquo;', 'ttp-crm'),
                    'next_text' => __('&raquo;', 'ttp-crm'),
                ))); ?>
            </div>
        </div>
        <?php
    }
}
