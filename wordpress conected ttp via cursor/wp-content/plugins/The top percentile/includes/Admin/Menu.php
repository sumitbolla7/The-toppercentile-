<?php

namespace TTP_CRM\Admin;

use TTP_CRM\Database\ContactRepository;
use TTP_CRM\Database\CampaignRepository;

defined('ABSPATH') || exit;

class Menu
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    /**
     * @var Pages\DashboardPage
     */
    private $dashboard_page;

    /**
     * @var Pages\ContactsPage
     */
    private $contacts_page;
    private $campaigns_page;
    private $courses_page;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts       = $contacts;
        $this->dashboard_page = new Pages\DashboardPage($contacts);
        $this->contacts_page  = new Pages\ContactsPage($contacts);
        $this->campaigns_page = new Pages\CampaignsPage(new CampaignRepository(), $contacts);
        $this->courses_page   = new Pages\CoursesPage();
    }

    public function register_hooks()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_ttp_crm_save_contact', array($this->contacts_page, 'handle_save'));
        add_action('admin_post_ttp_crm_delete_contact', array($this->contacts_page, 'handle_delete'));
        add_action('admin_post_ttp_crm_export_contacts', array($this->contacts_page, 'handle_export'));
        add_action('admin_post_ttp_crm_import_contacts', array($this->contacts_page, 'handle_import'));
        add_action('admin_post_ttp_crm_seed_dummy_contacts', array($this->contacts_page, 'handle_seed_dummy_contacts'));
        add_action('admin_post_ttp_crm_purge_dummy_contacts', array($this->contacts_page, 'handle_purge_dummy_contacts'));
        add_action('admin_post_ttp_crm_save_courses', array($this->courses_page, 'handle_save'));
        add_action('wp_ajax_ttp_crm_update_stage', array($this->contacts_page, 'handle_update_stage'));
        add_action('admin_post_ttp_crm_save_campaign', array($this->campaigns_page, 'handle_save'));
        add_action('admin_post_ttp_crm_send_campaign', array($this->campaigns_page, 'handle_send'));
    }

    public function register_menu()
    {
        $capability = 'manage_options';

        add_menu_page(
            __('TTP CRM', 'ttp-crm'),
            __('TTP CRM', 'ttp-crm'),
            $capability,
            'ttp-crm',
            array($this->dashboard_page, 'render'),
            'dashicons-groups',
            56
        );

        add_submenu_page(
            'ttp-crm',
            __('Dashboard', 'ttp-crm'),
            __('Dashboard', 'ttp-crm'),
            $capability,
            'ttp-crm',
            array($this->dashboard_page, 'render')
        );

        add_submenu_page(
            'ttp-crm',
            __('Contacts', 'ttp-crm'),
            __('Contacts', 'ttp-crm'),
            $capability,
            'ttp-crm-contacts',
            array($this->contacts_page, 'render')
        );

        add_submenu_page(
            'ttp-crm',
            __('Campaigns', 'ttp-crm'),
            __('Campaigns', 'ttp-crm'),
            $capability,
            'ttp-crm-campaigns',
            array($this->campaigns_page, 'render')
        );

        add_submenu_page(
            'ttp-crm',
            __('Courses', 'ttp-crm'),
            __('Courses', 'ttp-crm'),
            $capability,
            'ttp-crm-courses',
            array($this->courses_page, 'render')
        );
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'ttp-crm') === false) {
            return;
        }

        wp_enqueue_style(
            'ttp-crm-admin',
            TTP_CRM_URL . 'includes/Admin/assets/admin.css',
            array(),
            TTP_CRM_VERSION
        );

        wp_enqueue_script(
            'ttp-crm-admin',
            TTP_CRM_URL . 'includes/Admin/assets/admin.js',
            array(),
            TTP_CRM_VERSION,
            true
        );

        wp_localize_script(
            'ttp-crm-admin',
            'TtpCrmAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ttp_crm_stage_update'),
            )
        );
    }
}
