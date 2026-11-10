<?php
namespace ElementsKit_Lite\Modules\Copy_Paste_Cross_Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Register cross-domain copy-paste promotions in the Elementor editor.
 */
class Init {

	private $dir;
	private $url;

	public function __construct() {
		$this->dir = dirname( __FILE__ ) . '/';
		$this->url = \ElementsKit_Lite::plugin_url() . 'modules/copy-paste-cross-domain/';

		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'editor_scripts' ) );
	}

	/**
	 * Enqueue the editor promotion script.
	 *
	 * @return void
	 */
	public function editor_scripts() {
		$handle = 'ekit-lite-xd-copy-paste-editor';

		wp_enqueue_script(
			$handle,
			$this->url . 'assets/js/ekit-copy-paste.js',
			array( 'elementor-editor' ),
			filemtime( $this->dir . 'assets/js/ekit-copy-paste.js' ),
			true
		);

		wp_localize_script(
			$handle,
			'ekit_cp_xd_lite',
			array(
				'promotion_url' => 'https://wpmet.com/elementskit-pricing/',
				'message'       => array(
					'copy_title'  => esc_html__( 'ElementsKit Copy', 'elementskit-lite' ),
					'paste_title' => esc_html__( 'ElementsKit Paste', 'elementskit-lite' ),
				),
			)
		);
	}
}
