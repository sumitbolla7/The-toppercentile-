<?php

namespace TTP_CRM\Core;

use TTP_CRM\Admin\Menu;
use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class Plugin
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
    }

    public function boot()
    {
        $menu = new Menu($this->contacts);
        $menu->register_hooks();

        $cron = new Cron($this->contacts);
        $cron->register_hooks();

        $login_integration = new LoginIntegration($this->contacts);
        $login_integration->register_hooks();

        $purchase_integration = new PurchaseIntegration($this->contacts);
        $purchase_integration->register_hooks();

    }
}
