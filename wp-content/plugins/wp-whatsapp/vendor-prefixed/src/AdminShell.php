<?php

namespace WhatsappScoped\YayCommerce\AdminShell;

use WhatsappScoped\YayCommerce\AdminShell\Contracts\AddonHostAdapter;
use WhatsappScoped\YayCommerce\AdminShell\Contracts\PluginMenuAdapter;
use WhatsappScoped\YayCommerce\AdminShell\License\Contracts\LicenseConfigAdapter;
use WhatsappScoped\YayCommerce\AdminShell\License\LicenseHandler;
use WhatsappScoped\YayCommerce\AdminShell\License\PluginInfoFactory;
use WhatsappScoped\YayCommerce\AdminShell\Menu\ExternalPluginMenuAdapter;
use WhatsappScoped\YayCommerce\AdminShell\Menu\PluginSubmenu;
use WhatsappScoped\YayCommerce\AdminShell\Menu\MenuSuppressor;
use WhatsappScoped\YayCommerce\AdminShell\Menu\PagesRouter;
use WhatsappScoped\YayCommerce\AdminShell\Menu\TopLevelMenu;
use WhatsappScoped\YayCommerce\AdminShell\Pages\RecommendedPluginsPage;
use WhatsappScoped\YayCommerce\AdminShell\Registry\AddonBridge;
use WhatsappScoped\YayCommerce\AdminShell\Registry\LegacyBridge;
use WhatsappScoped\YayCommerce\AdminShell\Registry\LicenseRegistry;
use WhatsappScoped\YayCommerce\AdminShell\Support\Constants;
\defined('ABSPATH') || exit;
/**
 * Public facade — entry point for consuming plugins.
 *
 * Version election: when multiple scoped copies coexist, the highest
 * VERSION wins and registers menus/pages. All copies' register_plugin()
 * and enable_license() still run — they hook global WP actions.
 * @internal
 */
class AdminShell
{
    /** Package version — used for cross-scope version election. */
    const VERSION = '2.6.1';
    private static ?self $instance = null;
    private static bool $booted = \false;
    private static array $enabled_slugs = [];
    /** The scoped prefix for THIS copy (derived from namespace). */
    private static string $prefix = '';
    private LicenseRegistry $registry;
    private function __construct()
    {
        $this->registry = new LicenseRegistry();
    }
    /**
     * Bootstrap the shared admin shell.
     *
     * Each scoped copy calls boot(). All register their version in a shared
     * global. Actual menu/page registration is deferred to admin_menu where
     * only the highest version runs.
     */
    public static function boot() : void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = \true;
        require_once __DIR__ . '/Support/Constants.php';
        $instance = self::get_instance();
        // Derive our scoped prefix from the namespace (e.g. "YayMailScoped\YayCommerce\AdminShell" → "YayMailScoped")
        $ns = __NAMESPACE__;
        // YayCommerce\AdminShell or ScopedPrefix\YayCommerce\AdminShell
        $parts = \explode('\\', $ns);
        self::$prefix = \count($parts) > 2 ? $parts[0] : 'default';
        // Register this version in the shared global for cross-scope election
        if (!isset($GLOBALS['yaycommerce_admin_shell_versions'])) {
            $GLOBALS['yaycommerce_admin_shell_versions'] = [];
        }
        $GLOBALS['yaycommerce_admin_shell_versions'][self::$prefix] = ['version' => self::VERSION, 'registry' => $instance->registry, 'boot_cb' => [static::class, 'do_shell_registration']];
        // Register the version election — only once (first copy to call boot sets it up)
        if (1 === \count($GLOBALS['yaycommerce_admin_shell_versions'])) {
            add_action('admin_menu', [static::class, 'elect_version'], 8);
        }
        // Legacy bridge — reads yaycommerce_licensing_plugins filter.
        // Runs for ALL versions (uses global WP hooks, contributes to any winning registry).
        $legacy_bridge = new LegacyBridge($instance->registry);
        $legacy_bridge->init();
        // On AJAX requests admin_menu doesn't fire, so register AJAX handlers directly.
        // Global guard ensures only one scoped copy registers (action name is identical across copies).
        // DOING_AJAX must be in scoper.inc.php exclude-constants to avoid namespace prefixing.
        if (\defined('DOING_AJAX') && \DOING_AJAX) {
            $registered_ver = $GLOBALS['yaycommerce_ajax_handlers_registered'] ?? '0.0.0';
            if (\version_compare(self::VERSION, $registered_ver, '>')) {
                $GLOBALS['yaycommerce_ajax_handlers_registered'] = self::VERSION;
                RecommendedPluginsPage::get_instance();
            }
        }
        \do_action('yaycommerce_admin_shell_booted', $instance);
    }
    /**
     * Version election — picks the highest version and runs its shell registration.
     * Called once at admin_menu priority 8 (before TopLevelMenu at 9).
     */
    public static function elect_version() : void
    {
        $versions = $GLOBALS['yaycommerce_admin_shell_versions'] ?? [];
        if (empty($versions)) {
            return;
        }
        // Find highest version
        $winner_prefix = '';
        $winner_ver = '0.0.0';
        foreach ($versions as $prefix => $data) {
            if (\version_compare($data['version'], $winner_ver, '>')) {
                $winner_ver = $data['version'];
                $winner_prefix = $prefix;
            }
        }
        // Call the winner's registration — may be a different scoped class
        $winner = $versions[$winner_prefix];
        if (isset($winner['boot_cb']) && \is_callable($winner['boot_cb'])) {
            \call_user_func($winner['boot_cb']);
        }
    }
    /**
     * Register menus/pages — only called by the winning version.
     */
    public static function do_shell_registration() : void
    {
        $instance = self::get_instance();
        // Merge all registries into the winner's registry
        foreach ($GLOBALS['yaycommerce_admin_shell_versions'] ?? [] as $prefix => $data) {
            if ($prefix !== self::$prefix && isset($data['registry'])) {
                $other_registry = $data['registry'];
                foreach ($other_registry->all() as $info) {
                    if (!$instance->registry->get($info->slug)) {
                        $instance->registry->register($info);
                    }
                }
            }
        }
        $suppressor = new MenuSuppressor();
        $suppressor->init();
        $top_menu = new TopLevelMenu();
        $top_menu->init();
        $router = new PagesRouter($instance->registry);
        $router->init();
    }
    /**
     * Register a plugin with the admin shell.
     * Auto-detects pro vs lite via instanceof.
     * Runs for ALL versions (not version-gated).
     */
    public static function register_plugin(PluginMenuAdapter $adapter) : void
    {
        self::validate_adapter($adapter);
        // Submenu registration — per-plugin, all versions
        $submenu = new PluginSubmenu($adapter);
        $submenu->init();
        // Action links + row meta — per-plugin, all versions
        if (\is_admin()) {
            self::register_plugin_links($adapter);
        }
        // License subsystem — per-plugin, all versions
        if ($adapter instanceof LicenseConfigAdapter) {
            self::enable_license($adapter);
        }
        // Addon host — bridge plugin-specific addon filter into registry.
        if ($adapter instanceof AddonHostAdapter) {
            $filter_name = $adapter->get_addon_licensing_filter();
            if (!empty($filter_name)) {
                $bridge = new AddonBridge($filter_name, self::get_instance()->registry);
                $bridge->init();
            }
        }
    }
    /**
     * Register an "Other Plugins" submenu under an external plugin's own top-level menu.
     * Use for plugins with their own menu hierarchy (e.g. whatsapp, filester) that should
     * not appear under the shared YayCommerce menu.
     *
     * @param array{parent_menu: string, menu_title: string, menu_capability: string, menu_slug: string} $config
     */
    public static function register_external_plugin_menu(array $config) : void
    {
        $adapter = new ExternalPluginMenuAdapter($config);
        $adapter->init();
    }
    /**
     * Validate adapter values upfront.
     */
    private static function validate_adapter(PluginMenuAdapter $adapter) : void
    {
        $menu_title = $adapter->get_menu_title();
        if (empty($menu_title)) {
            \trigger_error('[YayCommerce AdminShell] get_menu_title() must return a non-empty string.', \E_USER_WARNING);
        }
        $basename = $adapter->get_plugin_basename();
        if (empty($basename)) {
            \trigger_error('[YayCommerce AdminShell] get_plugin_basename() must return a non-empty string.', \E_USER_WARNING);
        }
        $callback = $adapter->get_settings_page_callback();
        if (null !== $callback && !\is_callable($callback)) {
            \trigger_error('[YayCommerce AdminShell] get_settings_page_callback() returned a non-callable value.', \E_USER_WARNING);
        }
        if ($adapter instanceof LicenseConfigAdapter) {
            $slug = $adapter->get_plugin_slug();
            if (empty($slug)) {
                \trigger_error('[YayCommerce AdminShell] get_plugin_slug() must return a non-empty string.', \E_USER_WARNING);
            }
            $item_id = $adapter->get_item_id();
            if ($item_id <= 0) {
                \trigger_error('[YayCommerce AdminShell] get_item_id() must return a positive integer.', \E_USER_WARNING);
            }
            $store_url = $adapter->get_store_url();
            if (empty($store_url)) {
                \trigger_error('[YayCommerce AdminShell] get_store_url() must return a non-empty URL.', \E_USER_WARNING);
            }
            $plugin_file = $adapter->get_plugin_file();
            if (empty($plugin_file)) {
                \trigger_error('[YayCommerce AdminShell] get_plugin_file() must return a non-empty path.', \E_USER_WARNING);
            }
        }
    }
    /**
     * Enable the license subsystem for a plugin.
     * Runs for ALL versions (not version-gated).
     */
    public static function enable_license(LicenseConfigAdapter $adapter) : void
    {
        $slug = $adapter->get_plugin_slug();
        if (isset(self::$enabled_slugs[$slug])) {
            return;
        }
        self::$enabled_slugs[$slug] = \true;
        $instance = self::get_instance();
        new LicenseHandler($adapter);
        $info = PluginInfoFactory::from_adapter($adapter);
        $instance->registry->register($info);
        \do_action('yaycommerce_admin_shell_license_enabled', $adapter);
    }
    /**
     * Register plugin action links + row meta.
     */
    private static function register_plugin_links(PluginMenuAdapter $adapter) : void
    {
        $basename = $adapter->get_plugin_basename();
        add_filter('plugin_action_links_' . $basename, function (array $links) use($adapter) {
            $new = [];
            $menu_slug = $adapter->get_menu_slug();
            if (!empty($menu_slug)) {
                $url = \admin_url('admin.php?page=' . $menu_slug);
                $new['settings'] = '<a href="' . \esc_url($url) . '">' . \esc_html($adapter->get_settings_label()) . '</a>';
            }
            $pro_url = $adapter->get_pro_url();
            if (!empty($pro_url)) {
                $new['go-pro'] = '<a href="' . \esc_url($pro_url) . '" target="_blank" style="color:#00a32a;font-weight:700;">' . \esc_html__('Go Pro', 'yaycommerce') . '</a>';
            }
            return \array_merge($new, $links);
        });
        add_filter('plugin_row_meta', function (array $meta, string $file) use($adapter, $basename) {
            if ($file !== $basename) {
                return $meta;
            }
            $docs_url = $adapter->get_docs_url();
            if (!empty($docs_url)) {
                $meta[] = '<a href="' . \esc_url($docs_url) . '" target="_blank">' . \esc_html__('Docs', 'yaycommerce') . '</a>';
            }
            $meta[] = '<a href="https://yaycommerce.com/support" target="_blank">' . \esc_html__('Support', 'yaycommerce') . '</a>';
            return $meta;
        }, 10, 2);
    }
    /**
     * Return the shared registry.
     */
    public static function registry() : LicenseRegistry
    {
        return self::get_instance()->registry;
    }
    private static function get_instance() : self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Reset state — for unit tests only.
     */
    public static function reset() : void
    {
        self::$instance = null;
        self::$booted = \false;
        self::$enabled_slugs = [];
        self::$prefix = '';
        unset($GLOBALS['yaycommerce_admin_shell_versions']);
        unset($GLOBALS['yaycommerce_ajax_handlers_registered']);
    }
}
