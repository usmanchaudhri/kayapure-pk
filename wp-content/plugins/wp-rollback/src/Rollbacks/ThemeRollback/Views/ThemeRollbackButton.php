<?php

/**
 * @package WpRollback\Free\Rollbacks\ThemeRollback\Views
 */

declare(strict_types=1);

namespace WpRollback\Free\Rollbacks\ThemeRollback\Views;

use WpRollback\SharedCore\Core\Assets\AssetsManager;
use WpRollback\SharedCore\Core\SharedCore;
use WpRollback\SharedCore\Rollbacks\Traits\PluginHelpers;

/**
 * Handles theme rollback button functionality and assets.
 *
 */
class ThemeRollbackButton
{
    use PluginHelpers;
    /**
     * Register and enqueue theme rollback assets.
     *
     */
    public function __invoke(): void
    {
        global $pagenow;

        if ('themes.php' !== $pagenow) {
            return;
        }

        // Not in network admin (the Themes table has its own action links), nor on
        // subsites when network activated.
        if (!$this->showsThemeRollbackUi()) {
            return;
        }

        $assetsManager = SharedCore::container()->make(AssetsManager::class);
        $assetsManager->enqueueScript('themesAdmin', [], false);
    }
} 