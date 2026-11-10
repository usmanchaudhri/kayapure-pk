<?php

namespace ElementsKit_Lite\Modules\Widget_Builder;

use ElementsKit_Lite\Modules\Widget_Builder\Controls\Widget_Writer;

defined( 'ABSPATH' ) || exit;


class Widget_File {

	private static $instance;

	private $warnings = array();


	public function get_file_path() {

		$uploads    = wp_upload_dir();
		$upload_dir = $uploads['basedir'];
		$upload_dir = $upload_dir . '/elementskit/custom_widgets';

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		return $upload_dir;
	}


	public static function load_filesystem() {

		require_once ABSPATH . 'wp-admin/includes/file.php';

		return WP_Filesystem();
	}


	/**
	 * @return bool Whether widget.php was (re)written by the current compiler.
	 */
	public function create( $wObj, $id ) {

		$this->warnings = array();

		if ( ! self::load_filesystem() ) {
			return false;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return false;
		}

		$writer = new Widget_Writer( $wObj, $id, 'elementskit-lite' );

		$writer->start_backing( $wp_filesystem );

		$this->warnings = $writer->get_warnings();

		return $writer->finish_backing( $wp_filesystem );
	}


	/**
	 * Template placeholders dropped by the last create() call.
	 *
	 * @return string[]
	 */
	public function get_warnings() {
		return $this->warnings;
	}


	public static function get_wp_filesystem_pointer() {

		self::load_filesystem();

		global $wp_filesystem;

		return $wp_filesystem;
	}

	public static function instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
