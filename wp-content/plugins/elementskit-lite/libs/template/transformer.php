<?php
namespace ElementsKit_Lite\Libs\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a single {{ placeholder }} into an escaped PHP echo for a given output
 * context. Never produces a raw echo: an unknown context, an invalid expression,
 * or an expression not allowed in that context returns ''.
 */
class Transformer {

	const CONTEXT_TEXT      = 'text';      // HTML text node.
	const CONTEXT_HTML      = 'html';      // Plain-text-only spots: <textarea>, <title>, comments.
	const CONTEXT_ATTR      = 'attr';      // Quoted attribute value.
	const CONTEXT_URL       = 'url';       // Start of a URL attribute value (href, src, ...).
	const CONTEXT_TAG       = 'tag';       // Tag name: <{{ tag }}>.
	const CONTEXT_CSS       = 'css';       // Inside <style>.
	const CONTEXT_JS        = 'js';        // JS value inside <script>.
	const CONTEXT_JS_STRING = 'js_string'; // Inside a JS string literal in <script>.
	const CONTEXT_JS_QUOTED = 'js_quoted'; // A whole JS string literal: "{{ x }}" in <script>.

	const ESCAPER = '\ElementsKit_Lite\Libs\Template\Escaper';

	private static $wp_escapers = array(
		self::CONTEXT_TEXT => 'wp_kses_post',
		self::CONTEXT_HTML => 'esc_html',
		self::CONTEXT_ATTR => 'esc_attr',
		self::CONTEXT_URL  => 'esc_url',
	);

	private static $runtime_escapers = array(
		self::CONTEXT_TAG       => 'tag',
		self::CONTEXT_CSS       => 'css',
		self::CONTEXT_JS        => 'js',
		self::CONTEXT_JS_STRING => 'js_string',
		self::CONTEXT_JS_QUOTED => 'js_quoted',
	);

	private $prefix;

	public function render( $str, $prefix, $context = self::CONTEXT_TEXT ) {
		$str          = trim( $str );
		$this->prefix = $prefix;

		if ( preg_match( '/^([A-Za-z_]\w*)\s*\((.*)\)$/s', $str, $fn ) ) {
			// icon() prints markup, so it is only valid as a text node.
			if ( 'icon' === $fn[1] && self::CONTEXT_TEXT === $context ) {
				return $this->icon( $fn[2] );
			}

			return '';
		}

		return $this->variable( $str, $context );
	}

	private function variable( $str, $context ) {
		$path = $this->settings_path( $str );

		if ( '' === $path ) {
			return '';
		}

		// WP escapers fatal on arrays (e.g. {{ image }} instead of {{ image.url }}).
		if ( isset( self::$wp_escapers[ $context ] ) ) {
			return '<?php echo isset(' . $path . ') && is_scalar(' . $path . ') ? ' . self::$wp_escapers[ $context ] . '(' . $path . ') : ""; ?>';
		}

		if ( isset( self::$runtime_escapers[ $context ] ) ) {
			return '<?php echo ' . self::ESCAPER . '::' . self::$runtime_escapers[ $context ] . '(isset(' . $path . ') ? ' . $path . ' : ""); ?>';
		}

		return '';
	}

	private function icon( $str ) {
		$path = $this->settings_path( $str );

		// Only a top-level icons control can be rendered.
		if ( '' === $path || substr_count( $path, '[' ) !== 1 ) {
			return '';
		}

		return '<?php Icons_Manager::render_icon(' . $path . '); ?>';
	}

	/**
	 * `field.sub` => `$settings["<prefix>field"]["sub"]`, or '' when any segment
	 * is not a plain control key.
	 */
	private function settings_path( $str ) {
		$path = '$settings';

		foreach ( explode( '.', trim( $str ) ) as $i => $key ) {
			$key = trim( $key );

			if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $key ) ) {
				return '';
			}

			$path .= '["' . ( $i > 0 ? '' : $this->prefix ) . $key . '"]';
		}

		return $path;
	}

	/**
	 * Whether a PHP block has exactly one of the shapes render() generates.
	 * Used as the final gate before compiled markup is written to disk.
	 */
	public static function is_safe_block( $php_block ) {
		$path = '\$settings(?:\["[A-Za-z0-9_\-]+"\])+';

		$patterns = array(
			'/^<\?php echo isset\((' . $path . ')\) && is_scalar\(\1\) \? (?:' . implode( '|', self::$wp_escapers ) . ')\(\1\) : ""; \?>$/',
			'/^<\?php echo ' . preg_quote( self::ESCAPER, '/' ) . '::(?:' . implode( '|', self::$runtime_escapers ) . ')\(isset\((' . $path . ')\) \? \1 : ""\); \?>$/',
			'/^<\?php Icons_Manager::render_icon\(\$settings\["[A-Za-z0-9_\-]+"\]\); \?>$/',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $php_block ) ) {
				return true;
			}
		}

		return false;
	}
}
