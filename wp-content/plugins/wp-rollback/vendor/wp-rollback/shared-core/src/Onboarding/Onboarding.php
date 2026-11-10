<?php

/**
 * Onboarding for fresh installs: the "What do you want to do?" chooser.
 *
 * @package WpRollback\SharedCore\Onboarding
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Onboarding;

use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;

/**
 * Decides when a user sees the onboarding chooser and remembers their answer.
 *
 * A fresh install (never an upgrade) records its install time when it's
 * activated. For 30 days after that, every user who can roll back sees the
 * chooser until they pick something. Answers are stored per plugin, so
 * answering in Free doesn't hide the chooser after an upgrade to Pro.
 */
class Onboarding
{
    /**
     * User meta key holding ['plugin' => slug, 'choice' => string, 'popin_seen' => bool].
     */
    public const USER_META_KEY = 'wpr_onboarding';

    /**
     * Answers the chooser accepts. The first three start a tour.
     */
    public const CHOICES = ['rollback-plugin', 'rollback-theme', 'tour', 'dismissed'];

    /**
     * How long after a fresh install the chooser is offered.
     */
    public const WINDOW_DAYS = 30;

    /**
     * @var BaseConstants
     */
    private BaseConstants $_constants;

    /**
     * @param BaseConstants $constants Constants of the plugin that's running.
     */
    public function __construct(BaseConstants $constants)
    {
        $this->_constants = $constants;
    }

    /**
     * Whether onboarding is offered at all.
     *
     * Agencies can turn it off on client sites with the `wpr_onboarding_enabled` filter.
     */
    public function isEnabled(): bool
    {
        /**
         * Filters whether WP Rollback offers its onboarding chooser and tours.
         *
         * @param bool $enabled Whether onboarding is offered. Default true.
         */
        return (bool) apply_filters('wpr_onboarding_enabled', true);
    }

    /**
     * Option holding a plugin's fresh-install timestamp, e.g. "wp-rollback_onboarding".
     *
     * @param string $slug Plugin slug.
     */
    public static function optionNameFor(string $slug): string
    {
        return $slug . '_onboarding';
    }

    /**
     * Record a fresh install. Called when the plugin is activated.
     *
     * Only an activation with no stored version counts. Updates never run
     * activation, so an old site updated to this release can't get here, even
     * though releases before Free 3.2.0 and Pro 2.0.0 didn't record the version
     * on update and so look fresh to `wp_rollback_installed`. Reactivating a
     * site that already tracks its version doesn't restart onboarding either.
     *
     * @param BaseConstants $constants The activating plugin's constants.
     */
    public static function recordActivation(BaseConstants $constants): void
    {
        $slug = $constants->getSlug();

        if (false !== get_option($slug . '_current_version', false)) {
            return;
        }

        update_option(self::optionNameFor($slug), time(), false);
    }

    /**
     * Whether this plugin was freshly installed in the last 30 days.
     */
    public function isWithinWindow(): bool
    {
        $installedAt = (int) get_option(self::optionNameFor($this->_constants->getSlug()), 0);

        return $installedAt > 0 && (time() - $installedAt) < self::WINDOW_DAYS * DAY_IN_SECONDS;
    }

    /**
     * The user's stored answer for this plugin.
     *
     * @param int $userId User ID.
     * @return array{choice: string, popin_seen: bool}
     */
    public function getAnswer(int $userId): array
    {
        $meta = get_user_meta($userId, self::USER_META_KEY, true);

        if (! is_array($meta) || ($meta['plugin'] ?? '') !== $this->_constants->getSlug()) {
            return ['choice' => '', 'popin_seen' => false];
        }

        $choice = $meta['choice'] ?? '';

        return [
            'choice'     => in_array($choice, self::CHOICES, true) ? $choice : '',
            'popin_seen' => ! empty($meta['popin_seen']),
        ];
    }

    /**
     * Whether the current user should see the chooser.
     */
    public function isDue(): bool
    {
        $userId = get_current_user_id();

        // The install time isn't autoloaded, so it's read last, once the cached checks pass.
        return $userId > 0
            && $this->isEnabled()
            && PluginUtility::currentUserCanRollback()
            && '' === $this->getAnswer($userId)['choice']
            && $this->isWithinWindow();
    }

    /**
     * Whether the current user should see the pop-in on the Plugins screen. It shows once.
     */
    public function isPopinDue(): bool
    {
        return $this->isDue() && ! $this->getAnswer(get_current_user_id())['popin_seen'];
    }

    /**
     * Remember that the user has seen the pop-in. The dashboard card stays until they answer.
     *
     * @param int $userId User ID.
     */
    public function markPopinSeen(int $userId): void
    {
        $this->saveAnswer($userId, $this->getAnswer($userId)['choice'], true);
    }

    /**
     * Save the user's answer to the chooser.
     *
     * @param int    $userId User ID.
     * @param string $choice One of self::CHOICES.
     * @return bool False for an unknown choice.
     */
    public function saveChoice(int $userId, string $choice): bool
    {
        if (! in_array($choice, self::CHOICES, true)) {
            return false;
        }

        $this->saveAnswer($userId, $choice, $this->getAnswer($userId)['popin_seen']);

        return true;
    }

    /**
     * Name shown in the chooser's title. Brand names, so not translated.
     */
    public function getProductName(): string
    {
        return 'wp-rollback-pro' === $this->_constants->getSlug() ? 'WP Rollback Pro' : 'WP Rollback';
    }

    /**
     * Data for the admin app.
     *
     * @return array{enabled: bool, due: bool, productName: string, isNetwork: bool}
     */
    public function getScriptData(): array
    {
        return [
            'enabled'     => $this->isEnabled(),
            'due'         => $this->isDue(),
            'productName' => $this->getProductName(),
            'isNetwork'   => is_network_admin(),
        ];
    }

    /**
     * Store the user's answer for this plugin.
     *
     * @param int    $userId    User ID.
     * @param string $choice    One of self::CHOICES, or "" before they answer.
     * @param bool   $popinSeen Whether they've seen the pop-in.
     */
    private function saveAnswer(int $userId, string $choice, bool $popinSeen): void
    {
        update_user_meta(
            $userId,
            self::USER_META_KEY,
            [
                'plugin'     => $this->_constants->getSlug(),
                'choice'     => $choice,
                'popin_seen' => $popinSeen,
            ]
        );
    }
}
