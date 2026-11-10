<?php

/**
 * RollbackPage
 *
 * Builds links to the WP Rollback admin page.
 *
 * @package WpRollback\SharedCore\Rollbacks\Admin
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\Admin;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\SharedCore;

/**
 * Where the rollback page lives depends on how the plugin is activated:
 *
 * - Single site, or activated per site on a network: Tools > WP Rollback on that site.
 * - Network activated: Network Admin > Settings > WP Rollback. Subsites have no page.
 * - Network admin always has the network page while the plugin is running there.
 *
 * @unreleased
 */
class RollbackPage
{
    /**
     * URL of the rollback page, or null when there's no page to link to from here.
     *
     * Null means an admin screen on a subsite of a network where the plugin is
     * network activated. Outside admin screens (cron, REST, report emails) a
     * network-activated install gets the network page URL.
     *
     * Careful with admin-ajax.php and admin-post.php: both report is_admin() true
     * and is_network_admin() false even when the request came from Network Admin
     * (unless a screen from the network was set first, e.g. list table Ajax). So
     * on a network-activated install an Ajax request gets the network URL only
     * when the current user can open it, and null otherwise. admin-post.php
     * requests are treated like any other subsite admin request (null).
     * For links that mustn't depend on the request at all, use canonicalUrl().
     *
     * @unreleased
     *
     * @param string $fragment Route inside the page, e.g. '/pro-settings/license'. A leading '#' is optional.
     * @param string $slug     Page slug. Defaults to the running plugin's slug.
     * @return string|null
     */
    public static function url(string $fragment = '', string $slug = ''): ?string
    {
        $slug = self::slug($slug);

        if (self::usesNetworkPage()) {
            if (!is_network_admin() && is_admin()) {
                if (!wp_doing_ajax() || !current_user_can('manage_network_options')) {
                    return null;
                }
            }

            $url = network_admin_url('settings.php?page=' . $slug);
        } else {
            $url = admin_url('tools.php?page=' . $slug);
        }

        return self::withFragment($url, $fragment);
    }

    /**
     * URL of the rollback page regardless of the current request, for links that
     * leave the site (report emails).
     *
     * Network activated: always the Network Admin page. Otherwise the current
     * site's Tools page. Never null.
     *
     * @unreleased
     *
     * @param string $fragment Route inside the page, e.g. '/pro-settings/reports'. A leading '#' is optional.
     * @param string $slug     Page slug. Defaults to the running plugin's slug.
     * @return string
     */
    public static function canonicalUrl(string $fragment = '', string $slug = ''): string
    {
        $slug = self::slug($slug);

        $url = is_multisite() && self::isNetworkActivated()
            ? network_admin_url('settings.php?page=' . $slug)
            : admin_url('tools.php?page=' . $slug);

        return self::withFragment($url, $fragment);
    }

    /**
     * Whether the rollback page lives in Network Admin.
     *
     * @unreleased
     *
     * @return bool
     */
    private static function usesNetworkPage(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        if (is_network_admin()) {
            return true;
        }

        return self::isNetworkActivated();
    }

    /**
     * Whether the running plugin is network activated.
     *
     * @unreleased
     *
     * @return bool
     */
    private static function isNetworkActivated(): bool
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active_for_network(self::constants()->getBasename());
    }

    /**
     * The given slug, or the running plugin's slug when empty.
     *
     * @unreleased
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function slug(string $slug): string
    {
        return '' === $slug ? self::constants()->getSlug() : $slug;
    }

    /**
     * Append a route fragment to a URL.
     *
     * @unreleased
     *
     * @param string $url      Page URL.
     * @param string $fragment Route, with or without a leading '#'.
     * @return string
     */
    private static function withFragment(string $url, string $fragment): string
    {
        $fragment = ltrim($fragment, '#');

        return '' === $fragment ? $url : $url . '#' . $fragment;
    }

    /**
     * The running plugin's constants.
     *
     * @unreleased
     *
     * @return BaseConstants
     */
    private static function constants(): BaseConstants
    {
        return SharedCore::container()->make(BaseConstants::class);
    }
}
