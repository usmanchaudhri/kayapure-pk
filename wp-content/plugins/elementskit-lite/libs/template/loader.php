<?php
namespace ElementsKit_Lite\Libs\Template;

defined( 'ABSPATH' ) || exit;

class Loader {

	private $warnings = array();

	/**
	 * Compile {{ placeholders }} in a widget template into context-escaped PHP.
	 */
	public function replace_tags( $string, $prefix, $force_lower = false ) {
		$compiler = new Compiler();
		$markup   = $compiler->compile( $string, $prefix );

		$this->warnings = $compiler->get_warnings();

		return $markup;
	}

	/**
	 * Placeholders dropped by the last replace_tags() call.
	 */
	public function get_warnings() {
		return $this->warnings;
	}




	/**
	 * Get the instance.
	 */
	private static $instance = null;

	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
