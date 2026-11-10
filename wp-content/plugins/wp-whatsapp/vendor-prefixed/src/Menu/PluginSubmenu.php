<?php

namespace WhatsappScoped\YayCommerce\AdminShell\Menu;

use WhatsappScoped\YayCommerce\AdminShell\Contracts\PluginMenuAdapter;
use WhatsappScoped\YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;
use WhatsappScoped\YayCommerce\AdminShell\License\License;
/**
 * Registers a plugin-named submenu under YayCommerce.
 * Works with any PluginMenuAdapter (lite or pro).
 *
 * For pro plugins: automatically redirects to Licenses page when license
 * is not active — no developer action needed.
 * @internal
 */
class PluginSubmenu
{
    private PluginMenuAdapter $adapter;
    public function __construct(PluginMenuAdapter $adapter)
    {
        $this->adapter = $adapter;
    }
    public function init() : void
    {
        add_action('admin_menu', [$this, 'register'], 10);
    }
    public function register() : void
    {
        $menu_slug = $this->adapter->get_menu_slug();
        if (empty($menu_slug)) {
            return;
        }
        global $submenu;
        $has_menu = \false;
        $is_override = \false;
        if (isset($submenu['yaycommerce'])) {
            $yaycommerce_menu = $submenu['yaycommerce'];
            foreach ($yaycommerce_menu as $key => $value) {
                if ($value[2] === $menu_slug) {
                    if (\method_exists($this->adapter, 'is_licensed') && $this->adapter->is_licensed() || !\method_exists($this->adapter, 'is_licensed')) {
                        remove_submenu_page('yaycommerce', $menu_slug);
                        $is_override = \true;
                    } else {
                        $has_menu = \true;
                    }
                    break;
                }
            }
        }
        if ($has_menu) {
            return;
        }
        $callback = $this->adapter->get_settings_page_callback();
        // Guard: ensure callback is actually callable
        if (null !== $callback && !\is_callable($callback)) {
            $callback = null;
        }
        // Pro plugins without active license → override callback to redirect
        $needs_redirect = \false;
        if ($this->adapter instanceof LicenseConfigAdapter) {
            $license = new License($this->adapter);
            if (!$license->is_active() || $license->is_expired()) {
                $needs_redirect = \true;
                $callback = null;
            }
        }
        $page_id = add_submenu_page('yaycommerce', $this->adapter->get_page_title(), $this->adapter->get_menu_title(), $this->adapter->get_capability(), $menu_slug, $callback ?? '__return_false', $this->adapter->get_settings_page_position());
        if ($is_override) {
            \remove_all_actions('load-' . $page_id);
        }
        if ($needs_redirect) {
            add_action('load-' . $page_id, [__CLASS__, 'redirect_to_licenses']);
        }
    }
    public static function redirect_to_licenses() : void
    {
        wp_safe_redirect(\admin_url('admin.php?page=yaycommerce-licenses'));
        exit;
    }
}
