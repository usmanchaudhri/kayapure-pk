<?php

/**
 * API route for the onboarding chooser.
 *
 * @package WpRollback\SharedCore\RestAPI
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\RestAPI;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WpRollback\SharedCore\Core\Contracts\ApiRouteV1;
use WpRollback\SharedCore\Core\Utilities\PluginUtility;
use WpRollback\SharedCore\Onboarding\Onboarding;

/**
 * POST /wp-rollback/v1/onboarding saves the current user's chooser answer, so
 * the chooser doesn't come back on the Plugins screen or the dashboard.
 */
class OnboardingApiRoute extends ApiRouteV1
{
    /**
     * @var Onboarding
     */
    private Onboarding $_onboarding;

    /**
     * @param Onboarding $onboarding Onboarding service.
     */
    public function __construct(Onboarding $onboarding)
    {
        $this->_onboarding = $onboarding;
    }

    /**
     * Register the REST route.
     */
    public function register(): void
    {
        register_rest_route(
            $this->namespace,
            '/onboarding',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'saveChoice'],
                'permission_callback' => [$this, 'permissionValidation'],
                'args'                => [
                    'choice' => [
                        'type'     => 'string',
                        'required' => true,
                        'enum'     => Onboarding::CHOICES,
                    ],
                ],
            ]
        );
    }

    /**
     * Nonce check plus the rollback capability, like the other admin routes.
     *
     * @param WP_REST_Request $request Request.
     * @return bool|WP_Error
     */
    public function permissionValidation(WP_REST_Request $request)
    {
        $parentCheck = parent::permissionValidation($request);
        if (is_wp_error($parentCheck)) {
            return $parentCheck;
        }

        return PluginUtility::currentUserCanRollback();
    }

    /**
     * Save the current user's answer.
     *
     * The route's enum has already refused unknown choices.
     *
     * @param WP_REST_Request $request Request.
     */
    public function saveChoice(WP_REST_Request $request): WP_REST_Response
    {
        $choice = (string) $request->get_param('choice');

        $this->_onboarding->saveChoice(get_current_user_id(), $choice);

        return new WP_REST_Response(['choice' => $choice], 200);
    }
}
