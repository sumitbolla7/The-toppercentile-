<?php

namespace TTP_CRM\Admin\Pages;

use TTP_CRM\Database\ContactRepository;
use TTP_CRM\Database\CampaignRepository;

defined('ABSPATH') || exit;

class DashboardPage
{
    /**
     * @var ContactRepository
     */
    private $contacts;
    private $campaigns;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts  = $contacts;
        $this->campaigns = new CampaignRepository();
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'ttp-crm'));
        }

        $contact_count = $this->contacts->count_contacts();
        $tag_count     = $this->contacts->count_unique_tags();
        $campaigns     = $this->campaigns->count_campaigns();
        $recent_contacts = $this->contacts->count_recent_contacts(7);
        $contacts_link = admin_url('admin.php?page=ttp-crm-contacts');
        $campaign_link = admin_url('admin.php?page=ttp-crm-campaigns');
        $logo_url      = 'https://floralwhite-snake-354745.hostingersite.com/wp-content/uploads/2026/04/TTP2.jpg-scaled.jpeg';
        $stages        = $this->contacts->get_stages();
        $stage_counts  = array();
        foreach ($stages as $stage) {
            $stage_counts[$stage] = $this->contacts->count_by_stage($stage);
        }
        $max_stage_count = max(1, max($stage_counts));
        $contacts_activity = $this->contacts->contacts_created_last_days(7);
        $max_contacts_count = max(1, max($contacts_activity));
        $followup_summary = $this->contacts->followup_status_summary();
        $max_followup_count = max(1, max($followup_summary));
        $stage_conversion = $this->contacts->get_stage_conversion_rate('new', 'enrolled');
        $course_revenue   = $this->contacts->revenue_by_course(8);
        $lead_sources     = $this->contacts->lead_source_performance(8);
        $weekly_contacts  = $this->contacts->contacts_created_last_weeks(8);
        $followups       = $this->contacts->upcoming_followups(8);
        ?>
        <div class="wrap ttp-crm-wrap">
            <p>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr__('TTP CRM', 'ttp-crm'); ?>" style="max-width:280px;height:auto;" />
            </p>
            <h1><?php echo esc_html__('TTP CRM Dashboard', 'ttp-crm'); ?></h1>
            <p><?php echo esc_html__('Lead and student relationship overview.', 'ttp-crm'); ?></p>

            <div class="ttp-stats-grid">
                <div class="ttp-stat-card">
                    <h2><?php echo esc_html__('Total Contacts', 'ttp-crm'); ?></h2>
                    <p><?php echo esc_html($contact_count); ?></p>
                </div>
                <div class="ttp-stat-card">
                    <h2><?php echo esc_html__('Unique Tags', 'ttp-crm'); ?></h2>
                    <p><?php echo esc_html($tag_count); ?></p>
                </div>
                <div class="ttp-stat-card">
                    <h2><?php echo esc_html__('Campaigns', 'ttp-crm'); ?></h2>
                    <p><?php echo esc_html($campaigns); ?></p>
                </div>
                <div class="ttp-stat-card">
                    <h2><?php echo esc_html__('New Contacts (Last 7 Days)', 'ttp-crm'); ?></h2>
                    <p><?php echo esc_html($recent_contacts); ?></p>
                </div>
                <div class="ttp-stat-card">
                    <h2><?php echo esc_html__('New -> Enrolled Conversion', 'ttp-crm'); ?></h2>
                    <p><?php echo esc_html($stage_conversion); ?>%</p>
                </div>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Pipeline View', 'ttp-crm'); ?></h2>
                <div class="ttp-kanban">
                    <?php foreach ($stages as $stage) : ?>
                        <div class="ttp-kanban-col">
                            <h3><?php echo esc_html(ucfirst($stage)); ?></h3>
                            <p><?php echo esc_html($stage_counts[$stage]); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('CRM Graphs', 'ttp-crm'); ?></h2>
                <p><?php echo esc_html__('Visual summary of pipeline, contact growth, and follow-up status.', 'ttp-crm'); ?></p>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Stage Distribution', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Stage', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Contacts', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('%', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stage_counts as $stage => $count) : ?>
                                <?php $pct = $contact_count > 0 ? round(($count / $contact_count) * 100, 1) : 0; ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($stage)); ?></td>
                                    <td><?php echo esc_html((int) $count); ?></td>
                                    <td><?php echo esc_html($pct); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Daily Contact Growth (Last 7 Days)', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Day', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('New Contacts', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts_activity as $day => $count) : ?>
                                <tr>
                                    <td><?php echo esc_html($day); ?></td>
                                    <td><?php echo esc_html((int) $count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Stage Conversion (New to Enrolled)', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Metric', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Value', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo esc_html__('New → Enrolled Conversion', 'ttp-crm'); ?></td>
                                <td><?php echo esc_html($stage_conversion); ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Follow-up Status Overview', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Status', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Count', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($followup_summary as $label => $count) : ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($label)); ?></td>
                                    <td><?php echo esc_html((int) $count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Course-wise Revenue', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Course', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Contacts', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Revenue', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($course_revenue)) : ?>
                                <tr><td colspan="3"><?php echo esc_html__('No course revenue data yet.', 'ttp-crm'); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ($course_revenue as $row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($row['course_name']); ?></td>
                                        <td><?php echo esc_html((int) $row['contacts']); ?></td>
                                        <td><?php echo esc_html(number_format((float) $row['revenue'], 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Lead Source Performance', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Source', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Leads', 'ttp-crm'); ?></th>
                                <th><?php echo esc_html__('Revenue', 'ttp-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lead_sources)) : ?>
                                <tr><td colspan="3"><?php echo esc_html__('No lead source data yet.', 'ttp-crm'); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ($lead_sources as $row) : ?>
                                    <tr>
                                        <td><?php echo esc_html($row['lead_source']); ?></td>
                                        <td><?php echo esc_html((int) $row['leads']); ?></td>
                                        <td><?php echo esc_html(number_format((float) $row['revenue'], 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Daily / Weekly Reports', 'ttp-crm'); ?></h2>
                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Daily Contacts (Last 7 Days)', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead><tr><th><?php echo esc_html__('Day', 'ttp-crm'); ?></th><th><?php echo esc_html__('New Contacts', 'ttp-crm'); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($contacts_activity as $day => $count) : ?>
                            <tr><td><?php echo esc_html($day); ?></td><td><?php echo esc_html($count); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ttp-graph-block">
                    <h3><?php echo esc_html__('Weekly Contacts (Last 8 Weeks)', 'ttp-crm'); ?></h3>
                    <table class="widefat striped">
                        <thead><tr><th><?php echo esc_html__('Week', 'ttp-crm'); ?></th><th><?php echo esc_html__('New Contacts', 'ttp-crm'); ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($weekly_contacts)) : ?>
                            <tr><td colspan="2"><?php echo esc_html__('No weekly data yet.', 'ttp-crm'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($weekly_contacts as $week => $count) : ?>
                                <tr><td><?php echo esc_html($week); ?></td><td><?php echo esc_html($count); ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ttp-card">
                <h2><?php echo esc_html__('Upcoming Follow-up Reminders', 'ttp-crm'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Name', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Email', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Stage', 'ttp-crm'); ?></th>
                            <th><?php echo esc_html__('Follow-up At', 'ttp-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($followups)) : ?>
                            <tr><td colspan="4"><?php echo esc_html__('No reminders scheduled.', 'ttp-crm'); ?></td></tr>
                        <?php else : ?>
                            <?php foreach ($followups as $contact) : ?>
                                <tr>
                                    <td><?php echo esc_html($this->contacts->get_contact_display_name($contact)); ?></td>
                                    <td><?php echo esc_html($contact['email']); ?></td>
                                    <td><?php echo esc_html(ucfirst($contact['stage'])); ?></td>
                                    <td><?php echo esc_html($contact['follow_up_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="ttp-actions">
                <a class="button button-primary" href="<?php echo esc_url($contacts_link); ?>">
                    <?php echo esc_html__('Manage Contacts', 'ttp-crm'); ?>
                </a>
                <a class="button" href="<?php echo esc_url($campaign_link); ?>">
                    <?php echo esc_html__('Manage Campaigns', 'ttp-crm'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
