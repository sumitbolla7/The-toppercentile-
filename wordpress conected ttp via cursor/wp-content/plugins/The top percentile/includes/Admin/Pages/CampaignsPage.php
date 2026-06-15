<?php

namespace TTP_CRM\Admin\Pages;

use TTP_CRM\Database\CampaignRepository;
use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class CampaignsPage
{
    /**
     * @var CampaignRepository
     */
    private $campaigns;

    /**
     * @var ContactRepository
     */
    private $contacts;

    public function __construct(CampaignRepository $campaigns, ContactRepository $contacts)
    {
        $this->campaigns = $campaigns;
        $this->contacts  = $contacts;
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ttp-crm'));
        }

        $campaigns = $this->campaigns->all();
        $stages    = $this->contacts->get_stages();
        ?>
        <div class="wrap ttp-crm-wrap">
            <h1><?php echo esc_html__('TTP CRM Campaigns', 'ttp-crm'); ?></h1>
            <?php $this->render_notices(); ?>
            <div class="ttp-card">
                <h2><?php echo esc_html__('Create Campaign', 'ttp-crm'); ?></h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('ttp_crm_save_campaign'); ?>
                    <input type="hidden" name="action" value="ttp_crm_save_campaign" />
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="ttp_campaign_channel"><?php echo esc_html__('Channel', 'ttp-crm'); ?></label></th>
                                <td>
                                    <select id="ttp_campaign_channel" name="channel">
                                        <option value="email"><?php echo esc_html__('Email', 'ttp-crm'); ?></option>
                                        <option value="whatsapp"><?php echo esc_html__('WhatsApp', 'ttp-crm'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ttp_campaign_title"><?php echo esc_html__('Title', 'ttp-crm'); ?></label></th>
                                <td><input type="text" class="regular-text" id="ttp_campaign_title" name="title" required /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ttp_campaign_tags"><?php echo esc_html__('Filter by Tags', 'ttp-crm'); ?></label></th>
                                <td><input type="text" class="regular-text" id="ttp_campaign_tags" name="tags_filter" placeholder="vip, mba" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ttp_campaign_stage"><?php echo esc_html__('Filter by Stage', 'ttp-crm'); ?></label></th>
                                <td>
                                    <select id="ttp_campaign_stage" name="stage_filter">
                                        <option value=""><?php echo esc_html__('Any Stage', 'ttp-crm'); ?></option>
                                        <?php foreach ($stages as $stage) : ?>
                                            <option value="<?php echo esc_attr($stage); ?>"><?php echo esc_html(ucfirst($stage)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ttp_campaign_message"><?php echo esc_html__('Message', 'ttp-crm'); ?></label></th>
                                <td><textarea id="ttp_campaign_message" name="message" rows="5" class="large-text" required></textarea></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button(__('Save Campaign', 'ttp-crm')); ?>
                </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Send Campaign', 'ttp-crm'); ?></h2>
                <p><?php echo esc_html__('Pick a saved campaign and send now. Email uses WordPress mail. WhatsApp is recorded in communication history for now.', 'ttp-crm'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('ttp_crm_send_campaign'); ?>
                    <input type="hidden" name="action" value="ttp_crm_send_campaign" />
                    <select name="campaign_id" required>
                        <option value=""><?php echo esc_html__('Select Campaign', 'ttp-crm'); ?></option>
                        <?php foreach ($campaigns as $campaign) : ?>
                            <option value="<?php echo esc_attr($campaign['id']); ?>"><?php echo esc_html($campaign['title'] . ' (' . ucfirst($campaign['channel']) . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('Send Now', 'ttp-crm'), 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Campaign History', 'ttp-crm'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Title', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Channel', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Filters', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Status', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Sent Count', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Sent At', 'ttp-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($campaigns)) : ?>
                            <tr><td colspan="6"><?php echo esc_html__('No campaigns yet.', 'ttp-crm'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($campaigns as $campaign) : ?>
                                <tr>
                                    <td><?php echo esc_html($campaign['title']); ?></td>
                                    <td><?php echo esc_html(ucfirst($campaign['channel'])); ?></td>
                                    <td><?php echo esc_html(trim('Tags: ' . $campaign['tags_filter'] . ' | Stage: ' . $campaign['stage_filter'], ' |')); ?></td>
                                    <td><?php echo esc_html(ucfirst($campaign['status'])); ?></td>
                                    <td><?php echo esc_html($campaign['sent_count']); ?></td>
                                    <td><?php echo esc_html($campaign['sent_at'] ?: '-'); ?></td>
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
        check_admin_referer('ttp_crm_save_campaign');

        $channel = isset($_POST['channel']) ? sanitize_key(wp_unslash($_POST['channel'])) : 'email';
        if (!in_array($channel, array('email', 'whatsapp'), true)) {
            $channel = 'email';
        }

        $data = array(
            'channel'      => $channel,
            'title'        => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'message'      => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'tags_filter'  => isset($_POST['tags_filter']) ? sanitize_text_field(wp_unslash($_POST['tags_filter'])) : '',
            'stage_filter' => isset($_POST['stage_filter']) ? sanitize_key(wp_unslash($_POST['stage_filter'])) : '',
        );

        $this->campaigns->insert($data);
        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-campaigns&status=campaign_saved'));
        exit;
    }

    public function handle_send()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ttp-crm'));
        }
        check_admin_referer('ttp_crm_send_campaign');

        $campaign_id = isset($_POST['campaign_id']) ? absint($_POST['campaign_id']) : 0;
        if ($campaign_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-campaigns'));
            exit;
        }

        $campaign = null;
        foreach ($this->campaigns->all() as $item) {
            if ((int) $item['id'] === $campaign_id) {
                $campaign = $item;
                break;
            }
        }

        if (!$campaign) {
            wp_safe_redirect(admin_url('admin.php?page=ttp-crm-campaigns'));
            exit;
        }

        $filters = array(
            'stage' => $campaign['stage_filter'],
        );

        $contacts = $this->contacts->all($filters);
        $tags     = $this->contacts->parse_tags($campaign['tags_filter']);
        $sent     = 0;

        foreach ($contacts as $contact) {
            if (!$this->passes_tag_filter($contact, $tags)) {
                continue;
            }

            if ($campaign['channel'] === 'email' && !empty($contact['email'])) {
                wp_mail($contact['email'], $campaign['title'], $campaign['message']);
                $sent++;
            } elseif ($campaign['channel'] === 'whatsapp') {
                $sent++;
            }

            $history_line = sprintf(
                '[%s][%s Campaign] %s: %s',
                current_time('mysql'),
                ucfirst($campaign['channel']),
                $campaign['title'],
                $campaign['message']
            );
            $history = trim($contact['communication_history'] . PHP_EOL . $history_line);
            $this->contacts->update(
                $contact['id'],
                array(
                    'first_name' => $contact['first_name'],
                    'last_name'  => $contact['last_name'],
                    'email'      => $contact['email'],
                    'phone'      => $contact['phone'],
                    'stage'      => $contact['stage'],
                    'tags'       => $contact['tags'],
                    'course_name' => $contact['course_name'],
                    'lead_source' => $contact['lead_source'],
                    'revenue_amount' => $contact['revenue_amount'],
                    'student_profile'       => $contact['student_profile'],
                    'purchase_summary'      => $contact['purchase_summary'],
                    'progress_notes'        => $contact['progress_notes'],
                    'follow_up_at'          => $contact['follow_up_at'],
                    'internal_notes'        => $contact['internal_notes'],
                    'communication_history' => $history,
                )
            );
        }

        $this->campaigns->mark_sent($campaign_id, $sent);
        wp_safe_redirect(admin_url('admin.php?page=ttp-crm-campaigns&status=campaign_sent'));
        exit;
    }

    private function passes_tag_filter($contact, $required_tags)
    {
        if (empty($required_tags)) {
            return true;
        }

        $contact_tags = $this->contacts->parse_tags($contact['tags']);
        foreach ($required_tags as $required_tag) {
            if (!in_array($required_tag, $contact_tags, true)) {
                return false;
            }
        }

        return true;
    }

    private function render_notices()
    {
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$status) {
            return;
        }

        $messages = array(
            'campaign_saved' => __('Campaign saved.', 'ttp-crm'),
            'campaign_sent'  => __('Campaign sent.', 'ttp-crm'),
        );

        if (!isset($messages[$status])) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($messages[$status]); ?></p></div>
        <?php
    }
}
