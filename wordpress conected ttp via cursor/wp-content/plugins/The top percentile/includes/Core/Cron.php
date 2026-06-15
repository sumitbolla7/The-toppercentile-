<?php

namespace TTP_CRM\Core;

use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class Cron
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    public function register_hooks()
    {
        add_action('ttp_crm_run_reminders', array($this, 'run_reminders'));

        if (!wp_next_scheduled('ttp_crm_run_reminders')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', 'ttp_crm_run_reminders');
        }
    }

    public function run_reminders()
    {
        $due_contacts = $this->contacts->due_followups_for_cron(50);
        if (empty($due_contacts)) {
            return;
        }

        $to = get_option('admin_email');
        if (!$to) {
            return;
        }

        foreach ($due_contacts as $contact) {
            $subject = sprintf(__('Follow-up Reminder: %s', 'ttp-crm'), trim($contact['first_name'] . ' ' . $contact['last_name']));
            $message = sprintf(
                "Reminder for contact:\nName: %s %s\nEmail: %s\nPhone: %s\nStage: %s\nFollow-up Time: %s\n\nNotes:\n%s",
                $contact['first_name'],
                $contact['last_name'],
                $contact['email'],
                $contact['phone'],
                $contact['stage'],
                $contact['follow_up_at'],
                $contact['internal_notes']
            );

            wp_mail($to, $subject, $message);
            $this->contacts->mark_reminder_sent($contact['id']);
        }
    }
}
