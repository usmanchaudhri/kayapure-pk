<?php

/**
 * Plugin Scripts
 *
 * @package WpRollback\Free\PluginSetup
 */

declare(strict_types=1);

namespace WpRollback\Free\PluginSetup;

use WpRollback\Free\Core\Constants;
use WpRollback\SharedCore\Core\Assets\AssetsManager;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;
use WpRollback\SharedCore\Newsletter\NewsletterSignup;
use WpRollback\SharedCore\Onboarding\Onboarding;
use WpRollback\SharedCore\Rollbacks\Admin\RollbackPage;
use WpRollback\SharedCore\Rollbacks\Registry\RollbackStepRegisterer;
/**
 * Handles script and style registration for the free plugin.
 *
 */
class PluginScripts
{
    /**
     * Initialize scripts.
     *
     * @return void
     */
    public function initialize(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueCommandPaletteScript']);
    }

    /**
     * Enqueue plugin assets.
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        $assetsManager = SharedCore::container()->make(AssetsManager::class);

        // The data below is only used on WP Rollback's screen, so don't build it anywhere else.
        if (!$assetsManager->isAllowedOnCurrentScreen('tools')) {
            return;
        }
        
        // This script only loads on the rollback page itself, so there's always a URL.
        $adminUrl = (string) RollbackPage::url('', 'wp-rollback');
        
        $assetsManager->enqueueScript('tools', [
            'rollback_nonce' => wp_create_nonce('wpr_rollback_nonce'),
            'restApiNonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => $adminUrl,
            'pluginsUrl' => admin_url('plugins.php'),
            'themesUrl' => admin_url('themes.php'),
            'restUrl' => esc_url_raw(rest_url()),
            'rollbackSteps' => $this->getRollbackSteps(),
            'dateFormat' => get_option('date_format', 'F j, Y'),
            'timeFormat' => get_option('time_format', 'g:i a'),
            'newsletter' => SharedCore::container()->make(NewsletterSignup::class)->getScriptData(),
            'onboarding' => SharedCore::container()->make(Onboarding::class)->getScriptData(),
        ]);
    }

    /**
     * Enqueue the command palette script on all admin pages.
     *
     * Bypasses AssetsManager's screen restriction intentionally — the command
     * palette must be available everywhere in wp-admin, not just the Tools page.
     *
     * @return void
     */
    public function enqueueCommandPaletteScript(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Graceful degradation: command palette requires WP 6.3+
        if (!wp_script_is('wp-commands', 'registered')) {
            return;
        }

        // Every command opens the rollback page, so skip the palette where there isn't one.
        $adminUrl = RollbackPage::url('', 'wp-rollback');

        if (null === $adminUrl) {
            return;
        }

        $constants = SharedCore::container()->make(Constants::class);
        $assetFile = $constants->getPluginDir() . '/build/commandPalette.asset.php';
        $assetData = file_exists($assetFile)
            ? require $assetFile
            : ['dependencies' => [], 'version' => $constants->getVersion()];

        $handle = $constants->getSlug() . '-command-palette';

        wp_enqueue_script(
            $handle,
            $constants->getPluginUrl() . '/build/commandPalette.js',
            $assetData['dependencies'],
            $assetData['version'],
            true
        );

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = [];
        foreach (get_plugins() as $pluginFile => $pluginData) {
            $plugins[] = [
                'name' => $pluginData['Name'],
                'slug' => PluginUtility::getPluginSlug((string) $pluginFile),
            ];
        }

        $themes = [];
        foreach (wp_get_themes() as $themeSlug => $themeObject) {
            $themes[] = [
                'name' => $themeObject->get('Name'),
                'slug' => $themeSlug,
            ];
        }

        wp_localize_script(
            $handle,
            'wprCommandPaletteData',
            [
                'plugins'  => $plugins,
                'themes'   => $themes,
                'adminUrl' => $adminUrl,
            ]
        );

        wp_set_script_translations($handle, $constants->getTextDomain(), $constants->getPluginDir() . '/languages');
    }

    /**
     * Get rollback steps data for script localization.
     *
     * @return array
     */
    protected function getRollbackSteps(): array
    {
        $stepRegisterer = SharedCore::container()->make(RollbackStepRegisterer::class);
        $steps = [];
        
        foreach ($stepRegisterer->getAllRollbackSteps() as $stepClass) {
            $steps[] = [
                'id' => $stepClass::id(),
                'rollbackProcessingMessage' => $stepClass::rollbackProcessingMessage()
            ];
        }
        
        return $steps;
    }
} 