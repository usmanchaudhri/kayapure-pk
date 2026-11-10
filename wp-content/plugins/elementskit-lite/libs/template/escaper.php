<?php
namespace ElementsKit_Lite\Libs\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Runtime escapers for widget-builder output contexts WordPress core has no
 * helper for. Called from compiled widget files in uploads/elementskit/custom_widgets.
 */
class Escaper {

	const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

	/**
	 * A complete JavaScript value (placeholder in code position inside <script>).
	 * A string that is itself valid JSON (`25`, `true`, `{"a":1}`) is emitted
	 * as that value, matching what the template author wrote; it is re-encoded,
	 * so it still cannot break out of the <script>.
	 */
	public static function js( $value ) {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			$decoded = json_decode( $value );

			if ( JSON_ERROR_NONE === json_last_error() ) {
				$value = $decoded;
			}
		}

		return self::json( $value );
	}

	/**
	 * A whole JS string literal ("{{ x }}" in <script>): always a string, as
	 * the template quoted it.
	 */
	public static function js_quoted( $value ) {
		return self::json( is_scalar( $value ) ? (string) $value : $value );
	}

	private static function json( $value ) {
		$json = wp_json_encode( $value, self::JSON_FLAGS );

		return false === $json ? 'null' : $json;
	}

	/**
	 * The inside of a JS string literal ('...', "..." or `...`). Everything except
	 * [A-Za-z0-9_] becomes a \uXXXX escape, so the value can neither close the
	 * string, start a ${} substitution, nor close the surrounding <script>.
	 */
	public static function js_string( $value ) {
		if ( ! is_scalar( $value ) ) {
			$value = self::js( $value );
		}

		$value = wp_check_invalid_utf8( (string) $value );
		$chars = preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY );

		if ( false === $chars ) {
			return '';
		}

		$out = '';
		foreach ( $chars as $char ) {
			if ( preg_match( '/^[A-Za-z0-9_]$/', $char ) ) {
				$out .= $char;
			} elseif ( strlen( $char ) === 1 ) {
				$out .= sprintf( '\\u%04X', ord( $char ) );
			} else {
				// json_encode() emits \uXXXX (or a surrogate pair) for multibyte chars.
				$out .= substr( wp_json_encode( $char ), 1, -1 );
			}
		}

		return $out;
	}

	/**
	 * A value inside a <style> element. Strips the characters that can close the
	 * element or the current rule/block.
	 */
	public static function css( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return str_replace( array( '<', '>', '{', '}' ), '', wp_check_invalid_utf8( (string) $value ) );
	}

	/**
	 * A dynamic HTML tag name, e.g. <{{ title_tag }}>.
	 */
	public static function tag( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return 'div';
		}

		if ( method_exists( '\Elementor\Utils', 'validate_html_tag' ) ) {
			return \Elementor\Utils::validate_html_tag( $value );
		}

		$allowed = array( 'a', 'article', 'aside', 'button', 'div', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'main', 'nav', 'p', 'section', 'span' );

		return in_array( strtolower( $value ), $allowed, true ) ? $value : 'div';
	}
}
