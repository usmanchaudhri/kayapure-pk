import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import TourChip from '@wp-rollback/shared-core/onboarding/TourChip';
import { finishStep, rollbackSteps } from '@wp-rollback/shared-core/onboarding/tourSteps';

/**
 * The shortcuts step's text. The command palette is only loaded for users who
 * can manage options, so its sentence only shows for them.
 *
 * @return {Object} The text element.
 */
const shortcutsBody = () => {
    const elements = {
        rollback: <TourChip variant="link">{ __( 'Rollback', 'wp-rollback' ) }</TourChip>,
        mac: (
            <span className="wpr-tour-keys">
                <kbd className="wpr-tour-key">⌘</kbd> <kbd className="wpr-tour-key">K</kbd>
            </span>
        ),
        pc: (
            <span className="wpr-tour-keys">
                <kbd className="wpr-tour-key">Ctrl</kbd> <kbd className="wpr-tour-key">K</kbd>
            </span>
        ),
    };

    return window.wprCommandPaletteData
        ? createInterpolateElement(
              /* translators: <rollback /> is the Rollback link on the Plugins screen. <mac /> and <pc /> are the keyboard shortcuts ⌘K and Ctrl+K. */
              __(
                  'Every plugin on the Plugins screen has a <rollback /> link. From anywhere in wp-admin, press <mac /> or <pc /> and type “rollback”.',
                  'wp-rollback'
              ),
              elements
          )
        : createInterpolateElement(
              /* translators: <rollback /> is the Rollback link on the Plugins screen. */
              __( 'Every plugin on the Plugins screen has a <rollback /> link.', 'wp-rollback' ),
              elements
          );
};

/**
 * The Free plugin's tours, keyed by the chooser option that starts them.
 *
 * Built when the app mounts, so localized data such as the command palette's
 * is already on the page.
 *
 * @return {Object} Steps keyed by tour id.
 */
export const getTours = () => ( {
    'rollback-plugin': rollbackSteps( 'plugin' ),
    'rollback-theme': rollbackSteps( 'theme' ),
    tour: [
        {
            id: 'start',
            route: '/',
            anchor: 'dashboard-start',
            placement: 'bottom',
            title: __( 'Start here', 'wp-rollback' ),
            body: createInterpolateElement(
                /* translators: <plugin /> and <theme /> are the "Roll back a plugin" and "Roll back a theme" buttons. */
                __( 'Click <plugin /> or <theme /> to see every version you can roll back to.', 'wp-rollback' ),
                {
                    plugin: <TourChip variant="primary">{ __( 'Roll back a plugin', 'wp-rollback' ) }</TourChip>,
                    theme: <TourChip variant="primary">{ __( 'Roll back a theme', 'wp-rollback' ) }</TourChip>,
                }
            ),
            advance: 'next',
        },
        {
            id: 'shortcuts',
            route: '/',
            title: __( 'Two shortcuts', 'wp-rollback' ),
            body: shortcutsBody(),
            advance: 'next',
        },
        {
            id: 'pro',
            route: '/',
            anchor: 'pro-comparison',
            placement: 'top',
            title: __( 'Need more?', 'wp-rollback' ),
            body: __(
                'Pro adds premium plugins and themes, automatic backups, rollback notes, and an activity log.',
                'wp-rollback'
            ),
            advance: 'next',
        },
        finishStep(),
    ],
} );
