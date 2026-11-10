<?php

namespace WhatsappScoped\YayCommerce\AdminShell\Menu;

use WhatsappScoped\YayCommerce\AdminShell\Pages\LicensesPage;
use WhatsappScoped\YayCommerce\AdminShell\Pages\RecommendedPluginsPage;
use WhatsappScoped\YayCommerce\AdminShell\Pages\HelpPage;
use WhatsappScoped\YayCommerce\AdminShell\Registry\LicenseRegistry;
/**
 * Registers Licenses, Other Plugins, and Help submenus under 'yaycommerce'.
 * Ported from YayMail Pro RegisterMenu::add_submenus() — de-branded and
 * adapter-driven (no hardcoded cross-plugin class_exists() probes).
 * @internal
 */
class PagesRouter
{
    private LicenseRegistry $registry;
    public function __construct(LicenseRegistry $registry)
    {
        $this->registry = $registry;
    }
    public function init() : void
    {
        add_action('admin_menu', [$this, 'register_submenus'], 11);
        // Instantiate RecommendedPluginsPage to register its wp_ajax_* handlers.
        RecommendedPluginsPage::get_instance();
    }
    public function register_submenus() : void
    {
        $submenus = $this->get_submenus();
        foreach ($submenus as $slug => $submenu) {
            $page_id = add_submenu_page($submenu['parent'], $submenu['name'], $submenu['name'], $submenu['capability'], $slug, $submenu['render_callback'], $submenu['position'] ?? null);
            if ($submenu['load_callback']) {
                add_action('load-' . $page_id, $submenu['load_callback']);
            }
        }
    }
    private function get_submenus() : array
    {
        $submenus = [];
        // Plugin submenus use position 0-99 (via adapter).
        // Shell pages use 900+ so they always appear at the bottom.
        $submenus['yaycommerce-help'] = ['parent' => 'yaycommerce', 'name' => \__('Help', 'yaycommerce'), 'capability' => 'manage_options', 'render_callback' => [HelpPage::class, 'render'], 'load_callback' => [HelpPage::class, 'load_data'], 'position' => 900];
        // Licenses submenu — only shown if any plugins are registered.
        $has_any = !empty($this->registry->all());
        if ($has_any) {
            $submenus['yaycommerce-licenses'] = ['parent' => 'yaycommerce', 'name' => \__('Licenses', 'yaycommerce'), 'capability' => 'manage_options', 'render_callback' => [LicensesPage::class, 'render'], 'load_callback' => [LicensesPage::class, 'load_data'], 'position' => 910];
        }
        $submenus['yaycommerce-other-plugins'] = ['parent' => 'yaycommerce', 'name' => \__('Recommended Plugins', 'yaycommerce'), 'capability' => 'manage_options', 'render_callback' => [RecommendedPluginsPage::class, 'render'], 'load_callback' => [RecommendedPluginsPage::class, 'load_data'], 'position' => 920];
        return $submenus;
    }
}
