<?php
/**
 * Social Icons Styling Loader for Astra theme.
 *
 * @package     Astra Builder
 * @author      Brainstorm Force
 * @copyright   Copyright (c) 2020, Brainstorm Force
 * @link        https://www.brainstormforce.com
 * @since       Astra 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Social Icons Initialization
 *
 * @since 3.0.0
 */
class Astra_Footer_Social_Icons_Component_Loader {

	/**
	 * Constructor
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'customize_preview_init', array( $this, 'preview_scripts' ), 110 );
	}

	/**
	 * Customizer Preview
	 *
	 * @since 3.0.0
	 */
	public function preview_scripts() {
		/**
		 * Load unminified if SCRIPT_DEBUG is true.
		 */
		/* Directory and Extension */
		$dir_name    = ( SCRIPT_DEBUG ) ? 'unminified' : 'minified';
		$file_prefix = ( SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'astra-footer-social-icons-customizer-preview-js', ASTRA_BUILDER_FOOTER_SOCIAL_ICONS_URI . '/assets/js/customizer-preview.js', array( 'customize-preview', 'astra-customizer-preview-js' ), ASTRA_THEME_VERSION, true );

		// Localize variables for Astra Breakpoints JS.
		wp_localize_script(
			'astra-footer-social-icons-customizer-preview-js',
			'astraBuilderFooterSocial',
			array(
				'tablet_break_point'  => astra_get_tablet_breakpoint(),
				'mobile_break_point'  => astra_get_mobile_breakpoint(),
				'footer_social_count' => Astra_Builder_Helper::$num_of_footer_social_icons,
				'header_social_count' => Astra_Builder_Helper::$num_of_header_social_icons,
			)
		);
	}
}

/**
*  Kicking this off by creating the object of the class.
*/
new Astra_Footer_Social_Icons_Component_Loader();
