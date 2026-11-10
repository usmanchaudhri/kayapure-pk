<?php

/**
 * Onboarding Service Provider
 *
 * @package WpRollback\SharedCore\Onboarding
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Onboarding;

use WpRollback\SharedCore\Core\Contracts\ServiceProvider as ServiceProviderContract;
use WpRollback\SharedCore\Core\Hooks;
use WpRollback\SharedCore\Core\SharedCore;

/**
 * Loads the onboarding pop-in. Fresh installs are recorded on activation, in
 * each plugin's PluginSetup::activatePlugin() via Onboarding::recordActivation().
 */
class ServiceProvider implements ServiceProviderContract
{
    /**
     * @inheritdoc
     */
    public function register(): void
    {
        SharedCore::container()->singleton(Onboarding::class);

        // Hooks doesn't autowire constructor arguments, so the pop-in comes from the container.
        Hooks::registerFactory(ActivationPopin::class, function () {
            return SharedCore::container()->make(ActivationPopin::class);
        });
    }

    /**
     * @inheritdoc
     */
    public function boot(): void
    {
        Hooks::addAction('admin_enqueue_scripts', ActivationPopin::class, 'enqueue');
    }
}
