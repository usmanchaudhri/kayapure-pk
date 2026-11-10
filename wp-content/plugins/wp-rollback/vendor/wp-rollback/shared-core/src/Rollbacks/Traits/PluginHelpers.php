<?php

/**
 * Plugin Helper Traits
 * 
 * @package WpRollback\SharedCore\Rollbacks\Traits
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Traits;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\Exceptions\BindingResolutionException;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\Services\WpOrgAssets;

trait PluginHelpers
{
    /**
     * Default plugin paths of WP Rollback (Free) and WP Rollback Pro.
     *
     * Not used by this code any more: see activePluginFiles(). Kept because
     * Free and Pro share this trait's namespace and whichever plugin loads
     * first provides it, so WP Rollback Pro 2.0.0, which reads this property,
     * must still find it when a newer Free is loaded.
     *
     * @deprecated 3.3.0 Use activePluginFiles().
     *
     * @var array<string, string[]>
     */
    protected static $pluginPaths = [
        'free' => [
            'wp-rollback/wp-rollback.php',
            'wp-rollback-monorepo/packages/free-plugin/wp-rollback.php',
        ],
        'pro' => [
            'wp-rollback-pro/wp-rollback-pro.php',
            'wp-rollback-monorepo/packages/pro-plugin/wp-rollback-pro.php',
        ],
    ];

    /**
     * Main file names of WP Rollback (Free) and WP Rollback Pro. Their folders
     * can be renamed (wp-rollback-master/, a Composer path, the monorepo), so
     * active installs are found by file name, not by full path.
     *
     * @var array<string, string>
     */
    protected static $pluginFileNames = [
        'free' => 'wp-rollback.php',
        'pro' => 'wp-rollback-pro.php',
    ];

    /**
     * Active WP Rollback plugin files for one edition, in whatever folder they live.
     *
     * Reads the active_plugins option and, on multisite, active_sitewide_plugins,
     * so it costs no disk reads or extra queries.
     *
     * @unreleased
     *
     * @param string $edition 'free' or 'pro'.
     * @return array<string, bool> Plugin file => whether it's network activated. Network activations come first.
     */
    protected static function activePluginFiles(string $edition): array
    {
        $fileName = self::$pluginFileNames[$edition] ?? '';
        if ($fileName === '') {
            return [];
        }

        $files = [];

        if (is_multisite()) {
            foreach (array_keys((array) get_site_option('active_sitewide_plugins', [])) as $file) {
                $files[(string) $file] = true;
            }
        }

        foreach ((array) get_option('active_plugins', []) as $file) {
            $files[(string) $file] = $files[(string) $file] ?? false;
        }

        return array_filter(
            $files,
            static function (string $file) use ($fileName): bool {
                return basename($file) === $fileName;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Ensure WordPress plugin functions are loaded
     *
     * @return void
     */
    private function loadPluginFunctions(): void
    {
        WpOrgAssets::loadPluginFunctions();
    }

    /**
     * Get plugin file path by slug
     *
     * @param string $pluginSlug The plugin slug
     * @return string The plugin file path
     */
    private function getPluginFileBySlug(string $pluginSlug): string
    {
        $this->loadPluginFunctions();
        $plugins = get_plugins();
        
        foreach (array_keys($plugins) as $path) {
            if (strpos((string) $path, $pluginSlug . '/') === 0) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Find an installed plugin from the slug a rollback request names it by.
     *
     * That's the plugin's folder, or its file name for a single-file plugin. A
     * WordPress.org plugin whose folder was renamed also answers to its
     * WordPress.org slug. getPluginFileBySlug() only matches the folder, which
     * is enough for the rollback steps but not for a request's slug.
     *
     * @param string $slug The slug from the rollback request
     * @return string The plugin file as keyed in get_plugins(), or '' if no installed plugin matches
     */
    protected function findPluginFileForRollbackSlug(string $slug): string
    {
        $this->loadPluginFunctions();

        $renamedMatch = '';

        foreach (array_keys(get_plugins()) as $pluginFile) {
            $pluginFile = (string) $pluginFile;
            $folder     = strstr($pluginFile, '/', true);

            if (($folder ?: $pluginFile) === $slug) {
                return $pluginFile;
            }

            if ('' === $renamedMatch && $this->resolveWpOrgSlug($pluginFile) === $slug) {
                $renamedMatch = $pluginFile;
            }
        }

        return $renamedMatch;
    }

    /**
     * Check if the plugin is network activated
     * 
     * This method is primarily used to determine if operations should be skipped
     * on individual subsites when the plugin is network activated. It returns false
     * in network admin context to ensure network-level operations proceed normally.
     *
     * @return bool True if network activated (and not in network admin), false otherwise
     */
    protected function isNetworkActivated(): bool
    {
        // Only check in multisite environments
        if (!is_multisite()) {
            return false;
        }
        
        // In network admin context, operations should proceed normally
        // regardless of network activation status
        if (is_network_admin()) {
            return false;
        }
        
        // Load plugin functions if not already loaded
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        try {
            // Try to get constants from container to determine the actual plugin file
            $constants = SharedCore::container()->make(BaseConstants::class);
            if ($constants && method_exists($constants, 'getBasename')) {
                $pluginBasename = $constants->getBasename();
                if (is_plugin_active_for_network($pluginBasename)) {
                    return true;
                }
            }
        } catch (BindingResolutionException $e) {
            // Fall through to check common plugin paths
        }
        
        // Fallback: any network-activated Free or Pro install, whatever its folder
        return in_array(true, self::activePluginFiles('free') + self::activePluginFiles('pro'), true);
    }

    /**
     * Whether plugin rollback UI shows on this screen: the Rollback links and
     * "Roll Back Plugins" button on the Plugins screen, and plugin dates.
     *
     * On a single site it shows everywhere. On a network, plugins are managed
     * in the network admin, so it shows there and not on subsites.
     *
     * @unreleased
     *
     * @return bool
     */
    protected function showsPluginRollbackUi(): bool
    {
        return !is_multisite() || is_network_admin();
    }

    /**
     * Whether theme rollback UI shows on the Themes screen in this context:
     * the Rollback button in the theme details, the "Roll Back Themes" button,
     * and theme dates in the details.
     *
     * Not in the network admin, whose Themes table gets per-row Rollback links
     * instead. Not on a subsite when WP Rollback is network activated, since
     * the subsite has no rollback page of its own (see BaseRegisterAdminMenu).
     * Yes on a subsite when it's activated on that site.
     *
     * @unreleased
     *
     * @return bool
     */
    protected function showsThemeRollbackUi(): bool
    {
        return !is_network_admin() && !$this->isNetworkActivated();
    }

    /**
     * Check if Pro plugin is active
     *
     * @return bool Whether Pro plugin is active
     */
    protected function isProPluginActive(): bool
    {
        // Check if instance has isProVersion property set
        if (property_exists($this, 'isProVersion') && $this->isProVersion) {
            return true;
        }
        
        return self::activePluginFiles('pro') !== [];
    }

    /**
     * Check if Free plugin is active
     *
     * @return bool Whether Free plugin is active
     */
    protected function isFreePluginActive(): bool
    {
        return self::activePluginFiles('free') !== [];
    }

    /**
     * Resolve the canonical WordPress.org slug for an installed plugin.
     *
     * Reads the `update_plugins` site transient that WP core maintains via
     * its twice-daily update check. WordPress.org's update API identifies
     * plugins server-side by header fingerprint (Name, TextDomain, PluginURI,
     * Author, UpdateURI) rather than by directory name, so the canonical
     * slug is available even when a user has renamed the local directory
     * (e.g. wp-rollback/ -> wp-rollback-disabled/ to disable during a conflict).
     *
     * Returns null when the plugin has no WordPress.org entry in either bucket
     * of the transient: a premium plugin, a site whose update check has never
     * run, or an entry a premium updater added. Only entries with an id like
     * `w.org/plugins/akismet` count, so an updater entry that borrows a
     * WordPress.org slug can't send a rollback to an unrelated plugin's
     * package. See WpOrgAssets.
     *
     * @param string $pluginFile Plugin file path as keyed in get_plugins()
     *                           (e.g. 'wp-rollback-disabled/wp-rollback.php').
     * @return string|null Canonical wp.org slug or null when not a wp.org plugin.
     */
    protected function resolveWpOrgSlug(string $pluginFile): ?string
    {
        return SharedCore::container()->make(WpOrgAssets::class)->pluginSlug($pluginFile);
    }
}
