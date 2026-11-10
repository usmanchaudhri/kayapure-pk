<?php

namespace ElementsKit_Lite\Widgets\Mail_Chimp;

defined('ABSPATH') || exit;

use Elementor\ElementsKit_Widget_Mail_Chimp_Handler;
use ElementsKit_Lite\Core\Handler_Api;

class Mail_Chimp_Api extends Handler_Api {

	const LISTS_CACHE_PREFIX = 'ekit_mailchimp_lists_';
	const LISTS_CACHE_TTL = WEEK_IN_SECONDS;
	const LISTS_REFRESH_LOCK = 30;
	const LISTS_FAILURE_BACKOFF = 5 * MINUTE_IN_SECONDS;

	public function config() {
		$this->prefix = 'widget/mailchimp';
		$this->param  = '';
	}

	/**
	 * Get Mailchimp audiences, using the last known good response while it is stale.
	 *
	 * Remote requests are only made when explicitly allowed by the caller. This prevents
	 * Elementor frontend rendering from generating Mailchimp API traffic.
	 *
	 * @param string $token         Mailchimp API key.
	 * @param bool   $allow_refresh Whether a stale cache may be refreshed.
	 *
	 * @return array Audience IDs keyed to audience names.
	 */
	public static function get_lists($token, $allow_refresh = false) {
		$server_prefix = self::get_server_prefix($token);

		if ('' === $server_prefix) {
			return [];
		}

		$cache_key = self::get_lists_cache_key($token);
		$cached = get_transient($cache_key);
		$lists = (is_array($cached) && isset($cached['lists']) && is_array($cached['lists'])) ? $cached['lists'] : [];
		$is_fresh = is_array($cached) && !empty($cached['fresh_until']) && $cached['fresh_until'] >= time();

		if (!$is_fresh && $allow_refresh && false === get_transient($cache_key . '_backoff')) {
			// Avoid duplicate Mailchimp requests when several editor requests arrive together.
			set_transient($cache_key . '_backoff', 1, self::LISTS_REFRESH_LOCK);
			$fetched = self::fetch_lists($token, $server_prefix, $cache_key);

			if (is_array($fetched)) {
				$lists = $fetched;
			}
		}

		return $lists;
	}

	private static function get_server_prefix($token) {
		$separator = strrpos($token, '-');

		if (false === $separator) {
			return '';
		}

		$server_prefix = substr($token, $separator + 1);

		return preg_match('/^[a-z0-9]+$/i', $server_prefix) ? strtolower($server_prefix) : '';
	}

	private static function get_lists_cache_key($token) {
		return self::LISTS_CACHE_PREFIX . md5($token);
	}

	/**
	 * Build the full Mailchimp API request (URL + headers) for a given path.
	 *
	 * @param string $server_prefix Mailchimp datacenter prefix (e.g. "us21").
	 * @param string $token         Mailchimp API key.
	 * @param string $path          API path relative to the /3.0/ base.
	 *
	 * @return array{url: string, headers: array}
	 */
	private static function get_api_request($server_prefix, $token, $path) {
		return [
			'url' => 'https://' . $server_prefix . '.api.mailchimp.com/3.0/' . ltrim($path, '/'),
			'headers' => [
				'Authorization' => 'apikey ' . $token,
				'Content-Type'  => 'application/json; charset=utf-8',
			],
		];
	}

	private static function fetch_lists($token, $server_prefix, $cache_key) {
		$request = self::get_api_request($server_prefix, $token, 'lists?count=1000&fields=lists.id,lists.name');

		$response = wp_remote_get(
			$request['url'],
			[
				'timeout' => 10,
				'headers' => $request['headers'],
			]
		);

		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			set_transient($cache_key . '_backoff', 1, self::LISTS_FAILURE_BACKOFF);
			return null;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		$lists = [];

		if (!empty($body['lists']) && is_array($body['lists'])) {
			foreach ($body['lists'] as $list) {
				if (isset($list['id'], $list['name'])) {
					$lists[$list['id']] = $list['name'];
				}
			}
		}

		$fresh_for = max(1, (int) apply_filters('ekit_mailchimp_lists_cache_expiration', 6 * HOUR_IN_SECONDS));

		set_transient(
			$cache_key,
			[
				'lists' => $lists,
				'fresh_until' => time() + $fresh_for,
			],
			max(self::LISTS_CACHE_TTL, $fresh_for)
		);
		delete_transient($cache_key . '_backoff');

		return $lists;
	}

	/**
	 * Sign the widget's subscription policy, independently of sessions for page caches.
	 */
	public static function get_form_config($settings) {
		$fields = [];
		foreach (($settings['ekit_mail_chimp_fields_repeater'] ?? []) as $field) {
			if (!empty($field['ekit_mail_chimp_field_name'])) {
				$fields[] = $field['ekit_mail_chimp_field_name'];
			}
		}
		$config = base64_encode(wp_json_encode([
			'expires' => time() + 2 * DAY_IN_SECONDS,
			'list' => (string) ($settings['ekit_mail_chimp_select_listed_id'] ?? ''),
			'opt_in' => 'yes' === ($settings['ekit_mail_chimp_double_opt_in'] ?? '') ? 'yes' : 'no',
			'fields' => $fields,
		]));
		$data = ElementsKit_Widget_Mail_Chimp_Handler::get_data();
		return $config . '.' . hash_hmac('sha256', $config, wp_salt('auth') . ($data['token'] ?? ''));
	}

	private static function verify_form_config($value, $token) {
		if (!is_string($value) || strlen($value) > 16384) {
			return false;
		}
		$parts = explode('.', $value);
		if (2 !== count($parts) || !hash_equals(hash_hmac('sha256', $parts[0], wp_salt('auth') . $token), $parts[1])) {
			return false;
		}
		$config = json_decode(base64_decode($parts[0], true), true);
		if (!is_array($config) || !isset($config['list'], $config['opt_in'], $config['fields'], $config['expires'])
			|| !is_int($config['expires']) || $config['expires'] < time()
			|| !is_string($config['list']) || !in_array($config['opt_in'], ['yes', 'no'], true) || !is_array($config['fields'])) {
			return false;
		}
		return $config;
	}

	/**
	 * Handle a Mailchimp subscribe request from the frontend form.
	 *
	 * Validates the request (nonce, API key, list, email) before ever building
	 * the payload or contacting Mailchimp, so invalid requests fail fast without
	 * doing unnecessary work.
	 *
	 * @return array{success: array, error: array|string}
	 */
    public function get_sendmail(){
		// Get only the GET parameters
        $params = isset($this->request['GET']) ? $this->request['GET'] : $this->request->get_params();

		$return = ['success' => [], 'error' => [] ];

		$nonce = $this->request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			$return['error'] = esc_html__( 'Security check failed. Please refresh the page and try again.', 'elementskit-lite' );
			return $return;
		}

		$dataApi = ElementsKit_Widget_Mail_Chimp_Handler::get_data();

		$token  = isset($dataApi['token']) ? $dataApi['token'] : '';
		$config = self::verify_form_config($params['form_config'] ?? null, $token);
		if (false === $config) {
			$return['error'] = esc_html__( 'Invalid form configuration. Please refresh the page and try again.', 'elementskit-lite' );
			return $return;
		}
		$listed = $config['list'];
		$email = isset($params['email']) && is_string($params['email']) ? sanitize_email($params['email']) : '';

		$server_prefix = self::get_server_prefix($token);
		if ('' === $server_prefix) {
			$return['error'] = esc_html__( 'Please set API Key into Dashboard User Data. ', 'elementskit-lite' );
			return $return;
		}

		if ('' === $listed) {
			$return['error'] = esc_html__( 'Please select a MailChimp list.', 'elementskit-lite' );
			return $return;
		}

		if ('' === $email || ! is_email($email)) {
			$return['error'] = esc_html__( 'Please provide a valid email address.', 'elementskit-lite' );
			return $return;
		}

		// Only fields authorized by the rendered widget may become merge tags.
		static $reserved_fields = ['listed', 'double_opt_in', 'email', 'action', 'form_config', '_wpnonce'];
		$merge_fields = [];

		foreach ( array_diff_key( $params, array_flip( $reserved_fields ) ) as $key => $value ) {
			if ( is_string($value) && $value !== '' && in_array($key, $config['fields'], true) ) {
				// Convert field name to uppercase Mailchimp merge tag.
				$merge_fields[ strtoupper( $key ) ] = sanitize_text_field( $value );
			}
		}

		$data = [
			'email_address' => $email,
		];

		if ( ! empty( $merge_fields ) ) {
			$data['merge_fields'] = $merge_fields;
		}

		$data['status'] = ( 'yes' === $config['opt_in'] )
			? 'pending'
			: 'subscribed';

		$request = self::get_api_request($server_prefix, $token, 'lists/' . rawurlencode($listed) . '/members/');

		$response = wp_remote_post( $request['url'], [
			'method' => 'POST',
			'data_format' => 'body',
			'timeout' => 45,
			'headers' => $request['headers'],
			'body' => wp_json_encode($data)
			]
		);

		/* handle Mailchimp response */
		if ( is_wp_error( $response ) ) {
			$return['error'] = 'Something went wrong: ' . $response->get_error_message();
			return $return;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( $code >= 400 ) {
			if ( is_array( $decoded ) && ! empty( $decoded['title'] ) ) {
				$return['error'] = $decoded['title'];
			} else {
				// Avoid echoing raw/unstructured response bodies back to the client.
				$return['error'] = esc_html__( 'Mailchimp request failed. Please try again later.', 'elementskit-lite' );
			}
		} else {
			// keep the original wp_remote_post response so JS can parse response.success.body
			$return['success'] = $response;
		}

		return $return;
    }
}
