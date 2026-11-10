<?php

namespace Elementor;

use ElementsKit_Lite\Utils;

class ElementsKit_Extend_Onepage_Scroll {

	public function __construct() {
		/**
		 * Page Controls
		 */
		add_action( 'elementor/documents/register_controls', array( $this, 'register_page_controls' ) );

		/**
		 * Section Controls
		 */
		add_action( 'elementor/element/section/section_advanced/after_section_end', array( $this, 'register_section_controls' ) );

		/**
		 * Flexbox Container Support
		 */
		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'register_section_controls' ) );

		if ( 'free' === \ElementsKit_Lite::package_type() ) {
			add_action( 'elementor/element/wp-page/ekit_page_settings/before_section_end', array( $this, 'pro_panel_notice' ), 99 );
			add_action( 'elementor/element/section/ekit_onepagescroll_section/before_section_end', array( $this, 'pro_panel_notice' ), 99 );
			add_action( 'elementor/element/container/ekit_onepagescroll_section/before_section_end', array( $this, 'pro_panel_notice' ), 99 );
		}
	}

	/**
	 * Promote navigation functionality implemented by the Pro plugin.
	 *
	 * @param Controls_Stack $element Elementor control stack.
	 */
	public function pro_panel_notice( Controls_Stack $element ) {
		$element->add_control(
			'ekit_control_get_pro',
			array(
				'label'       => esc_html__( 'Unlock more possibilities', 'elementskit-lite' ),
				'type'        => Controls_Manager::CHOOSE,
				'options'     => array(
					'1' => array( 'icon' => 'eicon-lock' ),
				),
				'default'     => '1',
				'toggle'      => false,
				'separator'   => 'before',
				'description' => sprintf(
					'<span class="ekit-widget-pro-feature">%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></span>',
					esc_html__( 'One Page Scroll navigation is available in', 'elementskit-lite' ),
					esc_url( 'https://wpmet.com/elementskit-pricing' ),
					esc_html__( 'ElementsKit Pro.', 'elementskit-lite' )
				),
			)
		);
	}


	/**
	 * Page Controls
	 */
	public function register_page_controls( Controls_Stack $element ) {
		$element->start_controls_section(
			'ekit_page_settings',
			array(
				'label' => esc_html__('ElementsKit Settings', 'elementskit-lite'),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			)
		);

		$element->add_control(
			'ekit_onepagescroll',
			array(
				'label'              => esc_html__( 'Enable Onepage Scroll', 'elementskit-lite' ),
				'type'               => Controls_Manager::SWITCHER,
				'return_value'       => 'block',
				'frontend_available' => true,
				'selectors'          => array(
					'div.onepage_scroll_nav' => 'display: {{VALUE}};',
				),
			)
		);

		$element->end_controls_section();
	}


	/**
	 * Section Controls
	 */
	public function register_section_controls( Controls_Stack $element ) {
		$element->start_controls_section(
			'ekit_onepagescroll_section',
			array(
				'label'         => Utils::get_promo_icon() . esc_html__( 'Onepage Scroll', 'elementskit-lite' ),
				'tab'           => Controls_Manager::TAB_ADVANCED,
				'hide_in_inner' => true,
			)
		);

		$element->add_control(
			'ekit_has_onepagescroll',
			array(
				'label'              => esc_html__( 'Enable Section', 'elementskit-lite' ),
				'type'               => Controls_Manager::SWITCHER,
				'frontend_available' => true,
				'return_value'       => 'section',
				'prefix_class'       => 'ops-',
			)
		);

		$element->end_controls_section();
	}

}
