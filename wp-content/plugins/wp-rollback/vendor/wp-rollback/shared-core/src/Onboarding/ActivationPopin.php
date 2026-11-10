<?php

/**
 * The onboarding pop-in on the Plugins screen.
 *
 * @package WpRollback\SharedCore\Onboarding
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Onboarding;

use WpRollback\SharedCore\Core\Assets\AssetsManager;
use WpRollback\SharedCore\Core\BaseConstants;
use WpRollback\SharedCore\Rollbacks\Admin\RollbackPage;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Loads the chooser pop-in on the Plugins screen, once per user, after a fresh
 * install. Activating from the Plugins screen lands back on it, so this is the
 * first thing a new user sees.
 */
class ActivationPopin
{
    use PluginHelpers;

    /**
     * @var Onboarding
     */
    private Onboarding $_onboarding;

    /**
     * @var BaseConstants
     */
    private BaseConstants $_constants;

    /**
     * @var AssetsManager
     */
    private AssetsManager $_assets;

    /**
     * @param Onboarding    $onboarding Onboarding service.
     * @param BaseConstants $constants  Constants of the plugin that's running.
     * @param AssetsManager $assets     Script loader.
     */
    public function __construct(Onboarding $onboarding, BaseConstants $constants, AssetsManager $assets)
    {
        $this->_onboarding = $onboarding;
        $this->_constants  = $constants;
        $this->_assets     = $assets;
    }

    /**
     * Enqueue the pop-in when it's due. Hooked to `admin_enqueue_scripts`.
     */
    public function enqueue(): void
    {
        // The Plugins screen; AssetsManager has the list.
        if (! $this->_assets->isAllowedOnCurrentScreen('activationPopin')) {
            return;
        }

        // Network-activated, sites have no WP Rollback screen, only the network admin does.
        if ($this->isNetworkActivated()) {
            return;
        }

        if (! $this->_onboarding->isPopinDue()) {
            return;
        }

        $scriptData = $this->getScriptData();

        // No rollback page to send the user to from here.
        if (null === $scriptData) {
            return;
        }

        $this->_assets->enqueueScript('activationPopin', $scriptData, true, 'wprOnboardingPopin');
        $this->_onboarding->markPopinSeen(get_current_user_id());
    }

    /**
     * Data for the pop-in: where the app is and which admin menu item it lives under.
     *
     * Null when there's no rollback page to link to from this screen, i.e. a
     * subsite of a network where the plugin is network activated.
     *
     * @return array{productName: string, appUrl: string, menuId: string, isNetwork: bool}|null
     */
    public function getScriptData(): ?array
    {
        $appUrl = RollbackPage::url('', $this->_constants->getSlug());

        if (null === $appUrl) {
            return null;
        }

        $isNetwork = is_network_admin();

        return [
            'productName' => $this->_onboarding->getProductName(),
            'appUrl'      => $appUrl,
            'menuId'      => $isNetwork ? 'menu-settings' : 'menu-tools',
            'isNetwork'   => $isNetwork,
        ];
    }
}
