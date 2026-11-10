<?php

/**
 * Newsletter sign-ups from inside the plugin.
 *
 * @package WpRollback\SharedCore\Newsletter
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Newsletter;

use WP_Error;
use WpRollback\SharedCore\Core\BaseConstants;

/**
 * Sends a sign-up to the WP Rollback newsletter on wprollback.com and remembers,
 * per user, whether they subscribed or dismissed the prompt.
 *
 * Nothing is sent until the user clicks Subscribe. wprollback.com stores the
 * address in FluentCRM with double opt-in.
 */
class NewsletterSignup
{
    /**
     * User meta key holding ['status' => 'subscribed'|'dismissed', 'time' => int].
     */
    public const USER_META_KEY = 'wpr_newsletter';

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Where the form can appear. Sent to wprollback.com as part of the source.
     */
    public const PLACEMENTS = ['rollback-complete', 'dashboard', 'settings', 'onboarding'];

    /**
     * The sign-up endpoint on wprollback.com.
     */
    private const ENDPOINT = 'https://wprollback.com/wp-json/wp-rollback/v1/subscribe';

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
     * Whether the sign-up form is available at all.
     *
     * Agencies can hide it on client sites with the `wpr_newsletter_signup_enabled` filter.
     */
    public function isEnabled(): bool
    {
        /**
         * Filters whether WP Rollback shows its newsletter sign-up form.
         *
         * @param bool $enabled Whether the form is shown. Default true.
         */
        return (bool) apply_filters('wpr_newsletter_signup_enabled', true);
    }

    /**
     * The user's sign-up status: "subscribed", "dismissed", or "" when they haven't decided.
     *
     * @param int $userId User ID.
     */
    public function getStatus(int $userId): string
    {
        $meta = get_user_meta($userId, self::USER_META_KEY, true);

        if (! is_array($meta) || empty($meta['status'])) {
            return '';
        }

        return in_array($meta['status'], [self::STATUS_SUBSCRIBED, self::STATUS_DISMISSED], true)
            ? $meta['status']
            : '';
    }

    /**
     * Data for the sign-up form in the admin app.
     *
     * @return array{enabled: bool, show: bool, status: string, email: string}
     */
    public function getScriptData(): array
    {
        $user    = wp_get_current_user();
        $enabled = $this->isEnabled();
        $status  = $user->ID ? $this->getStatus((int) $user->ID) : '';

        return [
            'enabled' => $enabled,
            'show'    => $enabled && '' === $status,
            'status'  => $status,
            'email'   => (string) $user->user_email,
        ];
    }

    /**
     * Subscribe an email address and remember it for the user.
     *
     * @param int    $userId    User who submitted the form.
     * @param string $email     Email address.
     * @param string $placement Where the form was, one of self::PLACEMENTS.
     * @return array{code: string, message: string}|WP_Error
     */
    public function subscribe(int $userId, string $email, string $placement)
    {
        $email = sanitize_email($email);
        if ('' === $email || ! is_email($email)) {
            return $this->error('wpr_invalid_email', 400);
        }

        if (! in_array($placement, self::PLACEMENTS, true)) {
            return $this->error('wpr_invalid_placement', 400);
        }

        $response = wp_remote_post(
            $this->getEndpointUrl(),
            [
                'timeout' => 10,
                'headers' => ['Accept' => 'application/json'],
                'body'    => [
                    'email'   => $email,
                    'source'  => $this->getSource($placement),
                    'website' => '',
                ],
            ]
        );

        if (is_wp_error($response)) {
            return $this->error('wpr_newsletter_unavailable', 503);
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $body       = json_decode((string) wp_remote_retrieve_body($response), true);
        $remoteCode = is_array($body) && isset($body['code']) && is_string($body['code']) ? $body['code'] : '';

        if (200 !== $statusCode) {
            if ('wpr_invalid_email' === $remoteCode) {
                return $this->error('wpr_invalid_email', 400);
            }

            if ('wpr_rate_limited' === $remoteCode) {
                return $this->error('wpr_rate_limited', 429);
            }

            return $this->error('wpr_newsletter_unavailable', 503);
        }

        $code = 'already_subscribed' === $remoteCode ? 'already_subscribed' : 'success';

        $this->setStatus($userId, self::STATUS_SUBSCRIBED);

        return [
            'code'    => $code,
            'message' => self::getMessage($code),
        ];
    }

    /**
     * Hide the sign-up prompts for a user.
     *
     * @param int $userId User ID.
     */
    public function dismiss(int $userId): void
    {
        $this->setStatus($userId, self::STATUS_DISMISSED);
    }

    /**
     * Source recorded on wprollback.com, e.g. "free-plugin-rollback-complete".
     *
     * @param string $placement Where the form was.
     */
    public function getSource(string $placement): string
    {
        $plugin = 'wp-rollback-pro' === $this->_constants->getSlug() ? 'pro-plugin' : 'free-plugin';

        return $plugin . '-' . $placement;
    }

    /**
     * The sign-up endpoint. Override with the WP_ROLLBACK_NEWSLETTER_URL constant for local testing.
     */
    public function getEndpointUrl(): string
    {
        if (defined('WP_ROLLBACK_NEWSLETTER_URL') && is_string(WP_ROLLBACK_NEWSLETTER_URL) && '' !== WP_ROLLBACK_NEWSLETTER_URL) {
            return WP_ROLLBACK_NEWSLETTER_URL;
        }

        return self::ENDPOINT;
    }

    /**
     * Message shown for a result code. Unknown codes get a generic error.
     *
     * @param string $code Result or error code.
     */
    public static function getMessage(string $code): string
    {
        switch ($code) {
            case 'success':
                return __('Almost done. Check your inbox to confirm your subscription.', 'wp-rollback');
            case 'already_subscribed':
                return __('You’re already subscribed. Thanks for reading!', 'wp-rollback');
            case 'wpr_invalid_email':
                return __('Please enter a valid email address.', 'wp-rollback');
            case 'wpr_rate_limited':
                return __('Too many attempts. Please try again in a few minutes.', 'wp-rollback');
            default:
                return __('We couldn’t subscribe you right now. Please try again later.', 'wp-rollback');
        }
    }

    /**
     * Save the user's sign-up status.
     *
     * @param int    $userId User ID.
     * @param string $status One of the STATUS_* constants.
     */
    private function setStatus(int $userId, string $status): void
    {
        update_user_meta(
            $userId,
            self::USER_META_KEY,
            [
                'status' => $status,
                'time'   => time(),
            ]
        );
    }

    /**
     * Error with its user-facing message.
     *
     * @param string $code       Error code.
     * @param int    $httpStatus HTTP status.
     */
    private function error(string $code, int $httpStatus): WP_Error
    {
        return new WP_Error($code, self::getMessage($code), ['status' => $httpStatus]);
    }
}
