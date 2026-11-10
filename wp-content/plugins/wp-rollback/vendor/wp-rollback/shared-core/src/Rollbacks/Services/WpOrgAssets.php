<?php

/**
 * Which installed plugins and themes come from WordPress.org.
 *
 * @package WpRollback\SharedCore\Rollbacks\Services
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Services;

/**
 * Reads WordPress's update data (the `update_plugins` and `update_themes` site
 * transients) to tell which installed plugins and themes WordPress.org hosts.
 *
 * WordPress.org's update API lists every plugin and theme it recognizes, in
 * `response` when an update is available and in `no_update` when it's current.
 * It recognizes plugins by their headers, so a plugin whose folder was renamed
 * still gets its WordPress.org slug.
 *
 * Premium updaters (PaidCommunities, EDD and others) add their own entries to
 * the same data, often with a `slug`. So a plugin only counts as WordPress.org
 * when its entry has an id like `w.org/plugins/akismet`, which only
 * WordPress.org's API writes, and a theme only when its `url` and `package`
 * point at WordPress.org.
 *
 * Results are cached per instance, starting once WordPress's `init` action has
 * fired (before that, plugins may still be adding their update filters). The
 * cache is dropped when this request saves or deletes the transient through
 * set_site_transient() or delete_site_transient(). It doesn't see anything
 * else: a `site_transient_update_plugins` (or `_themes`) filter added after the
 * first read, or another process writing the transient during a long WP-CLI or
 * cron run. Call flush() when you need a fresh read in those cases.
 *
 * @unreleased
 */
class WpOrgAssets
{
    /**
     * The id prefix WordPress.org's update API gives plugins it hosts.
     */
    public const PLUGIN_ID_PREFIX = 'w.org/plugins/';

    /**
     * Bumped by flushShared(), so every instance drops its results.
     */
    private static int $generation = 0;

    /**
     * The generation this instance's results belong to.
     */
    private int $cachedGeneration = 0;

    /**
     * @var array{known: bool, slugs: array<string, string>}|null
     */
    private ?array $_plugins = null;

    /**
     * @var array{known: bool, stylesheets: array<string, true>}|null
     */
    private ?array $_themes = null;

    /**
     * Load WordPress's plugin functions (get_plugins(), is_plugin_active() and
     * friends), which only wp-admin loads on its own.
     */
    public static function loadPluginFunctions(): void
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Drop every instance's results, the shared one included, without
     * building an instance. Hooked to WordPress saving or deleting the update
     * transients.
     */
    public static function flushShared(): void
    {
        self::$generation++;
    }

    /**
     * Drop this instance's results so the next call reads the transients again.
     */
    public function flush(): void
    {
        $this->_plugins = null;
        $this->_themes  = null;
    }

    /**
     * Plugins hosted on WordPress.org.
     *
     * @return array<string, string> Plugin file (as keyed in get_plugins()) => WordPress.org slug.
     */
    public function pluginSlugs(): array
    {
        return $this->plugins()['slugs'];
    }

    /**
     * A plugin's WordPress.org slug, or null when WordPress.org doesn't host it.
     */
    public function pluginSlug(string $pluginFile): ?string
    {
        return $this->plugins()['slugs'][$pluginFile] ?? null;
    }

    /**
     * Whether WordPress has plugin update data at all. It doesn't until its
     * first update check runs.
     */
    public function hasPluginData(): bool
    {
        return $this->plugins()['known'];
    }

    /**
     * Whether WordPress.org hosts a theme.
     */
    public function isWpOrgTheme(string $stylesheet): bool
    {
        return isset($this->themes()['stylesheets'][$stylesheet]);
    }

    /**
     * Whether WordPress has theme update data at all.
     */
    public function hasThemeData(): bool
    {
        return $this->themes()['known'];
    }

    /**
     * @return array{known: bool, slugs: array<string, string>}
     */
    private function plugins(): array
    {
        $this->dropStaleResults();
        if (null !== $this->_plugins) {
            return $this->_plugins;
        }

        $updates = get_site_transient('update_plugins');
        $slugs   = [];

        // no_update first, so a usable `response` entry wins.
        foreach (self::buckets($updates) as $items) {
            foreach ($items as $file => $item) {
                if (!is_object($item) || !isset($item->slug) || !is_string($item->slug) || '' === $item->slug) {
                    continue;
                }

                if (isset($item->id) && is_string($item->id) && 0 === strpos($item->id, self::PLUGIN_ID_PREFIX)) {
                    $slugs[(string) $file] = $item->slug;
                }
            }
        }

        $plugins = [
            'known' => is_object($updates),
            'slugs' => $slugs,
        ];
        if (self::canCache()) {
            $this->_plugins = $plugins;
        }

        return $plugins;
    }

    /**
     * @return array{known: bool, stylesheets: array<string, true>}
     */
    private function themes(): array
    {
        $this->dropStaleResults();
        if (null !== $this->_themes) {
            return $this->_themes;
        }

        $updates     = get_site_transient('update_themes');
        $stylesheets = [];

        foreach (self::buckets($updates) as $items) {
            foreach ($items as $stylesheet => $item) {
                // WordPress stores theme entries as arrays; accept objects too.
                $item = (array) $item;
                if (
                    isset($item['url'], $item['package'])
                    && is_string($item['url'])
                    && is_string($item['package'])
                    && 0 === strpos($item['url'], 'https://wordpress.org/themes/')
                    && 0 === strpos($item['package'], 'https://downloads.wordpress.org/theme/')
                ) {
                    $stylesheets[(string) $stylesheet] = true;
                }
            }
        }

        $themes = [
            'known'       => is_object($updates),
            'stylesheets' => $stylesheets,
        ];
        if (self::canCache()) {
            $this->_themes = $themes;
        }

        return $themes;
    }

    /**
     * Forget results from before the last flushShared().
     */
    private function dropStaleResults(): void
    {
        if ($this->cachedGeneration !== self::$generation) {
            $this->flush();
            $this->cachedGeneration = self::$generation;
        }
    }

    /**
     * Only cache once `init` has fired, so update filters plugins add while
     * loading aren't missed.
     */
    private static function canCache(): bool
    {
        return function_exists('did_action') && did_action('init') > 0;
    }

    /**
     * The `no_update` and `response` lists of an update transient.
     *
     * @param mixed $updates The transient's value.
     * @return list<array<int|string, mixed>>
     */
    private static function buckets($updates): array
    {
        if (!is_object($updates)) {
            return [];
        }

        $buckets = [];
        foreach (['no_update', 'response'] as $bucket) {
            if (isset($updates->{$bucket}) && is_array($updates->{$bucket})) {
                $buckets[] = $updates->{$bucket};
            }
        }

        return $buckets;
    }
}
