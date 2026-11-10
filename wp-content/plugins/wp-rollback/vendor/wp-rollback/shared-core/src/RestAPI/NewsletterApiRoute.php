<?php

/**
 * API routes for the newsletter sign-up form.
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
use WpRollback\SharedCore\Newsletter\NewsletterSignup;

/**
 * POST /wp-rollback/v1/newsletter subscribes the current user's chosen address.
 * POST /wp-rollback/v1/newsletter/dismiss hides the prompts for the current user.
 *
 * Not named /subscribe: wprollback.com runs this plugin and already serves that route.
 */
class NewsletterApiRoute extends ApiRouteV1
{
    /**
     * @var NewsletterSignup
     */
    private NewsletterSignup $_newsletter;

    /**
     * @param NewsletterSignup $newsletter Newsletter sign-up service.
     */
    public function __construct(NewsletterSignup $newsletter)
    {
        $this->_newsletter = $newsletter;
    }

    /**
     * Register the REST routes.
     */
    public function register(): void
    {
        register_rest_route(
            $this->namespace,
            '/newsletter',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'subscribe'],
                'permission_callback' => [$this, 'permissionValidation'],
                'args'                => [
                    'email'     => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                    'placement' => [
                        'type'     => 'string',
                        'required' => true,
                        'enum'     => NewsletterSignup::PLACEMENTS,
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/newsletter/dismiss',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'dismiss'],
                'permission_callback' => [$this, 'permissionValidation'],
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
     * Subscribe the submitted address.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function subscribe(WP_REST_Request $request)
    {
        $result = $this->_newsletter->subscribe(
            get_current_user_id(),
            (string) $request->get_param('email'),
            (string) $request->get_param('placement')
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Hide the sign-up prompts for the current user.
     *
     * @return WP_REST_Response
     */
    public function dismiss(): WP_REST_Response
    {
        $this->_newsletter->dismiss(get_current_user_id());

        return new WP_REST_Response(['code' => 'dismissed'], 200);
    }
}
