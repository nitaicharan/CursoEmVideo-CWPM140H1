<?php
/**
 * Server-side rendering of the `core/pages` block.
 *
 * @package WordPress
 */

/**
 * Build an array with CSS classes and inline styles defining the colors
 * which will be applied to the pages markup in the front-end when it is a descendant of navigation.
 *
 * @param  array $context Navigation block context.
 * @return array Colors CSS classes and inline styles.
 */
function gutenberg_block_core_page_list_build_css_colors( $context ) {
	$colors = array(
		'css_classes'   => array(),
		'inline_styles' => '',
	);

	// Text color.
	$has_named_text_color  = array_key_exists( 'textColor', $context );
	$has_custom_text_color = array_key_exists( 'customTextColor', $context );

	// If has text color.
	if ( $has_custom_text_color || $has_named_text_color ) {
		// Add has-text-color class.
		$colors['css_classes'][] = 'has-text-color';
	}

	if ( $has_named_text_color ) {
		// Add the color class.
		$colors['css_classes'][] = sprintf( 'has-%s-color', $context['textColor'] );
	} elseif ( $has_custom_text_color ) {
		// Add the custom color inline style.
		$colors['inline_styles'] .= sprintf( 'color: %s;', $context['customTextColor'] );
	}

	// Background color.
	$has_named_background_color  = array_key_exists( 'backgroundColor', $context );
	$has_custom_background_color = array_key_exists( 'customBackgroundColor', $context );

	// If has background color.
	if ( $has_custom_background_color || $has_named_background_color ) {
		// Add has-background class.
		$colors['css_classes'][] = 'has-background';
	}

	if ( $has_named_background_color ) {
		// Add the background-color class.
		$colors['css_classes'][] = sprintf( 'has-%s-background-color', $context['backgroundColor'] );
	} elseif ( $has_custom_background_color ) {
		// Add the custom background-color inline style.
		$colors['inline_styles'] .= sprintf( 'background-color: %s;', $context['customBackgroundColor'] );
	}

	return $colors;
}

/**
 * Build an array with CSS classes and inline styles defining the font sizes
 * which will be applied to the pages markup in the front-end when it is a descendant of navigation.
 *
 * @param  array $context Navigation block context.
 * @return array Font size CSS classes and inline styles.
 */
function gutenberg_block_core_page_list_build_css_font_sizes( $context ) {
	// CSS classes.
	$font_sizes = array(
		'css_classes'   => array(),
		'inline_styles' => '',
	);

	$has_named_font_size  = array_key_exists( 'fontSize', $context );
	$has_custom_font_size = array_key_exists( 'customFontSize', $context );

	if ( $has_named_font_size ) {
		// Add the font size class.
		$font_sizes['css_classes'][] = sprintf( 'has-%s-font-size', $context['fontSize'] );
	} elseif ( $has_custom_font_size ) {
		// Add the custom font size inline style.
		$font_sizes['inline_styles'] = sprintf( 'font-size: %spx;', $context['customFontSize'] );
	}

	return $font_sizes;
}

/**
 * Outputs Page list markup from an array of pages with nested children.
 *
 * @param array $nested_pages The array of nested pages.
 *
 * @return string List markup.
 */
function gutenberg_render_nested_page_list( $nested_pages ) {
	if ( empty( $nested_pages ) ) {
		return;
	}
	$markup = '';
	foreach ( (array) $nested_pages as $page ) {
		$css_class = 'wp-block-pages-list__item';
		if ( isset( $page['children'] ) ) {
			$css_class .= ' has-child';
		}
		$markup .= '<li class="' . $css_class . '">';
		$markup .= '<a class="wp-block-pages-list__item__link" href="' . esc_url( $page['link'] ) . '">' . wp_kses(
			$page['title'],
			wp_kses_allowed_html( 'post' )
		) . '</a>';
		if ( isset( $page['children'] ) ) {
			$markup .= '<span class="wp-block-page-list__submenu-icon"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" transform="rotate(90)"><path d="M8 5v14l11-7z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg></span>';
			$markup .= '<ul class="submenu-container">' . gutenberg_render_nested_page_list( $page['children'] ) . '</ul>';
		}
		$markup .= '</li>';
	}
	return $markup;
}

/**
 * Outputs nested array of pages
 *
 * @param array $current_level The level being iterated through.
 * @param array $children The children grouped by parent post ID.
 *
 * @return array The nested array of pages.
 */
function gutenberg_nest_pages( $current_level, $children ) {
	if ( empty( $current_level ) ) {
		return;
	}
	foreach ( (array) $current_level as $key => $current ) {
		if ( isset( $children[ $key ] ) ) {
			$current_level[ $key ]['children'] = gutenberg_nest_pages( $children[ $key ], $children );
		}
	}
	return $current_level;
}

/**
 * Renders the `core/page-list` block on server.
 *
 * @param array $attributes The block attributes.
 * @param array $content The saved content.
 * @param array $block The parsed block.
 *
 * @return string Returns the page list markup.
 */
function gutenberg_render_block_core_page_list( $attributes, $content, $block ) {
	static $block_id = 0;
	$block_id++;

	$all_pages = get_pages( array( 'sort_column' => 'menu_order' ) );

	$top_level_pages = array();

	$pages_with_children = array();

	foreach ( (array) $all_pages as $page ) {
		if ( $page->post_parent ) {
			$pages_with_children[ $page->post_parent ][ $page->ID ] = array(
				'title' => $page->post_title,
				'link'  => get_permalink( $page->ID ),
			);
		} else {
			$top_level_pages[ $page->ID ] = array(
				'title' => $page->post_title,
				'link'  => get_permalink( $page->ID ),
			);

		}
	}

	$nested_pages = gutenberg_nest_pages( $top_level_pages, $pages_with_children );

	$wrapper_markup = '<ul %1$s>%2$s</ul>';

	$items_markup = gutenberg_render_nested_page_list( $nested_pages );

	$colors          = gutenberg_block_core_page_list_build_css_colors( $block->context );
	$font_sizes      = gutenberg_block_core_page_list_build_css_font_sizes( $block->context );
	$classes         = array_merge(
		$colors['css_classes'],
		$font_sizes['css_classes']
	);
	$style_attribute = ( $colors['inline_styles'] || $font_sizes['inline_styles'] );
	$css_classes     = trim( implode( ' ', $classes ) );

	if ( $block->context && $block->context['showSubmenuIcon'] ) {
		$css_classes .= ' show-submenu-icons';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => $css_classes,
			'style' => $style_attribute,
		)
	);

	return sprintf(
		$wrapper_markup,
		$wrapper_attributes,
		$items_markup
	);
}

	/**
	 * Registers the `core/pages` block on server.
	 */
function gutenberg_register_block_core_page_list() {
	register_block_type_from_metadata(
		__DIR__ . '/page-list',
		array(
			'render_callback' => 'gutenberg_render_block_core_page_list',
		)
	);
}
	add_action( 'init', 'gutenberg_register_block_core_page_list', 20 );
