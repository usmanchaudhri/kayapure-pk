/**
 * WP Rollback onboarding pop-in, loaded on the Plugins screen after a fresh install.
 */
import domReady from '@wordpress/dom-ready';
import { renderActivationPopin } from '@wp-rollback/shared-core/onboarding/ActivationPopin';
import '@wp-rollback/shared-core/onboarding/onboarding.scss';

domReady( renderActivationPopin );
