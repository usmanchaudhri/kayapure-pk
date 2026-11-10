<?php

namespace WhatsappScoped\YayCommerce\AdminShell\Contracts;

/**
 * Minimal per-plugin config for menu registration.
 * All plugins (lite + pro) implement this.
 * Pro plugins implement LicenseConfigAdapter which extends this.
 * @internal
 */
interface PluginMenuAdapter
{
    /**
     * Short name for the admin sidebar submenu item, e.g. 'YayMail'.
     */
    public function get_menu_title() : string;
    /**
     * Browser tab title for the settings page, e.g. 'YayMail Pro - Settings'.
     */
    public function get_page_title() : string;
    /**
     * Menu slug under YayCommerce, e.g. 'yaymail-settings'.
     * Return empty string if plugin has no settings page.
     */
    public function get_menu_slug() : string;
    /**
     * Render callback for the settings page.
     * Return null to redirect to the Licenses page (pro) or show nothing (lite).
     */
    public function get_settings_page_callback() : ?callable;
    /**
     * Position of the plugin submenu. Return null for WP default ordering.
     */
    public function get_settings_page_position() : ?int;
    /**
     * WP capability required. Default: 'manage_options'.
     */
    public function get_capability() : string;
    /**
     * WP plugin basename, e.g. 'yaymail-pro/yaymail.php'.
     */
    public function get_plugin_basename() : string;
    /**
     * Label for the settings action link on the Plugins page.
     */
    public function get_settings_label() : string;
    /**
     * Documentation URL. Return empty string to hide the link.
     */
    public function get_docs_url() : string;
    /**
     * "Go Pro" upgrade URL (for lite plugins). Return empty string to hide.
     */
    public function get_pro_url() : string;
}
