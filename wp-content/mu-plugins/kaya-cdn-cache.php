<?php
/**
 * Public cache header for anonymous storefront responses.
 *
 * Loaded on its own from advanced-cache.php so a WP Rocket cache hit still
 * tells the CDN it may store the page. Cart, checkout, account, and any
 * logged-in or WooCommerce session cookie are left uncacheable.
 */

if ( defined( 'KAYA_CDN_CACHE_HEADER' ) ) {
	return;
}

define( 'KAYA_CDN_CACHE_HEADER', true );

/**
 * Whether this request is safe for a shared cache to store.
 */
function kaya_request_is_cdn_cacheable() {
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return false;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
	$path = (string) parse_url( $uri, PHP_URL_PATH );
	if ( preg_match( '#/(?:cart|checkout|my-account|wc-api|wp-admin|wp-login\.php|wp-json|kp-xpk-7h2x)(?:/|$)#i', $path ) ) {
		return false;
	}

	$query = array();
	$raw_query = parse_url( $uri, PHP_URL_QUERY );
	if ( is_string( $raw_query ) ) {
		parse_str( $raw_query, $query );
	}
	foreach ( array( 'add-to-cart', 'remove_item', 'undo_item', 'wc-ajax', 'elementor-preview', 'preview', 'customize_changeset_uuid' ) as $blocked ) {
		if ( isset( $query[ $blocked ] ) ) {
			return false;
		}
	}

	$cookies = isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : array();
	foreach ( array_keys( $cookies ) as $name ) {
		if (
			0 === strpos( $name, 'wordpress_logged_in_' )
			|| 0 === strpos( $name, 'wordpress_sec_' )
			|| 0 === strpos( $name, 'wp_woocommerce_session_' )
			|| 0 === strpos( $name, 'comment_author' )
			|| 0 === strpos( $name, 'wp-postpass_' )
			|| 'woocommerce_items_in_cart' === $name
			|| 'woocommerce_cart_hash' === $name
		) {
			return false;
		}
	}

	$flag = dirname( __DIR__ ) . '/uploads/.kaya-webp-ready';
	if ( ! is_file( $flag ) ) {
		return false;
	}

	return true;
}

if ( kaya_request_is_cdn_cacheable() && ! headers_sent() ) {
	header( 'Cache-Control: public, max-age=0, s-maxage=900, stale-while-revalidate=60' );
}
