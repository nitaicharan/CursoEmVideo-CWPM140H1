<?php
/**
 * Block template functions.
 *
 * @package gutenberg
 */

/**
 * Returns all block template file path of the current theme and its parent theme.
 * Includes demo block template files if demo experiment is enabled.
 *
 * @return array $block_template_files A list of paths to all template files.
 */
function gutenberg_get_template_paths() {
	$block_template_files = glob( get_stylesheet_directory() . '/block-templates/*.html' );
	$block_template_files = is_array( $block_template_files ) ? $block_template_files : array();

	if ( is_child_theme() ) {
		$child_block_template_files = glob( get_template_directory() . '/block-templates/*.html' );
		$child_block_template_files = is_array( $child_block_template_files ) ? $child_block_template_files : array();
		$block_template_files       = array_merge( $block_template_files, $child_block_template_files );
	}

	return $block_template_files;
}

/**
 * Registers block editor 'wp_template' post type.
 */
function gutenberg_register_template_post_type() {
	if ( ! gutenberg_is_fse_theme() ) {
		return;
	}

	$labels = array(
		'name'                  => __( 'Templates', 'gutenberg' ),
		'singular_name'         => __( 'Template', 'gutenberg' ),
		'menu_name'             => _x( 'Templates', 'Admin Menu text', 'gutenberg' ),
		'add_new'               => _x( 'Add New', 'Template', 'gutenberg' ),
		'add_new_item'          => __( 'Add New Template', 'gutenberg' ),
		'new_item'              => __( 'New Template', 'gutenberg' ),
		'edit_item'             => __( 'Edit Template', 'gutenberg' ),
		'view_item'             => __( 'View Template', 'gutenberg' ),
		'all_items'             => __( 'All Templates', 'gutenberg' ),
		'search_items'          => __( 'Search Templates', 'gutenberg' ),
		'parent_item_colon'     => __( 'Parent Template:', 'gutenberg' ),
		'not_found'             => __( 'No templates found.', 'gutenberg' ),
		'not_found_in_trash'    => __( 'No templates found in Trash.', 'gutenberg' ),
		'archives'              => __( 'Template archives', 'gutenberg' ),
		'insert_into_item'      => __( 'Insert into template', 'gutenberg' ),
		'uploaded_to_this_item' => __( 'Uploaded to this template', 'gutenberg' ),
		'filter_items_list'     => __( 'Filter templates list', 'gutenberg' ),
		'items_list_navigation' => __( 'Templates list navigation', 'gutenberg' ),
		'items_list'            => __( 'Templates list', 'gutenberg' ),
	);

	$args = array(
		'labels'                => $labels,
		'description'           => __( 'Templates to include in your theme.', 'gutenberg' ),
		'public'                => false,
		'has_archive'           => false,
		'show_ui'               => true,
		'show_in_menu'          => 'themes.php',
		'show_in_admin_bar'     => false,
		'show_in_rest'          => true,
		'rest_base'             => 'templates',
		'rest_controller_class' => 'WP_REST_Templates_Controller',
		'capability_type'       => array( 'template', 'templates' ),
		'map_meta_cap'          => true,
		'supports'              => array(
			'title',
			'slug',
			'excerpt',
			'editor',
			'revisions',
		),
	);

	register_post_type( 'wp_template', $args );
}
add_action( 'init', 'gutenberg_register_template_post_type' );

/**
 * Registers block editor 'wp_theme' taxonomy.
 */
function gutenberg_register_wp_theme_taxonomy() {
	if ( ! gutenberg_is_fse_theme() ) {
		return;
	}

	register_taxonomy(
		'wp_theme',
		array( 'wp_template', 'wp_template_part' ),
		array(
			'public'            => false,
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => __( 'Themes', 'gutenberg' ),
				'singular_name' => __( 'Theme', 'gutenberg' ),
			),
			'query_var'         => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'_builtin'          => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'gutenberg_register_wp_theme_taxonomy' );

/**
 * Filters the capabilities of a user to conditionally grant them capabilities for managing 'wp_template' posts.
 *
 * Any user who can 'edit_theme_options' will have access.
 *
 * @param array $allcaps A user's capabilities.
 * @return array Filtered $allcaps.
 */
function gutenberg_grant_template_caps( array $allcaps ) {
	if ( isset( $allcaps['edit_theme_options'] ) ) {
		$allcaps['edit_templates']             = $allcaps['edit_theme_options'];
		$allcaps['edit_others_templates']      = $allcaps['edit_theme_options'];
		$allcaps['edit_published_templates']   = $allcaps['edit_theme_options'];
		$allcaps['edit_private_templates']     = $allcaps['edit_theme_options'];
		$allcaps['delete_templates']           = $allcaps['edit_theme_options'];
		$allcaps['delete_others_templates']    = $allcaps['edit_theme_options'];
		$allcaps['delete_published_templates'] = $allcaps['edit_theme_options'];
		$allcaps['delete_private_templates']   = $allcaps['edit_theme_options'];
		$allcaps['publish_templates']          = $allcaps['edit_theme_options'];
		$allcaps['read_private_templates']     = $allcaps['edit_theme_options'];
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'gutenberg_grant_template_caps' );

/**
 * Fixes the label of the 'wp_template' admin menu entry.
 */
function gutenberg_fix_template_admin_menu_entry() {
	if ( ! gutenberg_is_fse_theme() ) {
		return;
	}
	global $submenu;
	if ( ! isset( $submenu['themes.php'] ) ) {
		return;
	}
	$post_type = get_post_type_object( 'wp_template' );
	if ( ! $post_type ) {
		return;
	}
	foreach ( $submenu['themes.php'] as $key => $submenu_entry ) {
		if ( $post_type->labels->all_items === $submenu['themes.php'][ $key ][0] ) {
			$submenu['themes.php'][ $key ][0] = $post_type->labels->menu_name; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			break;
		}
	}
}
add_action( 'admin_menu', 'gutenberg_fix_template_admin_menu_entry' );

// Customize the `wp_template` admin list.
add_filter( 'manage_wp_template_posts_columns', 'gutenberg_templates_lists_custom_columns' );
add_action( 'manage_wp_template_posts_custom_column', 'gutenberg_render_templates_lists_custom_column', 10, 2 );
add_filter( 'views_edit-wp_template', 'gutenberg_filter_templates_edit_views' );

/**
 * Sets a custom slug when creating auto-draft templates.
 * This is only needed for auto-drafts created by the regular WP editor.
 * If this page is to be removed, this won't be necessary.
 *
 * @param int $post_id Post ID.
 */
function set_unique_slug_on_create_template( $post_id ) {
	$post = get_post( $post_id );
	if ( 'auto-draft' !== $post->post_status ) {
		return;
	}

	if ( ! $post->post_name ) {
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => 'custom_slug_' . uniqid(),
			)
		);
	}

	$terms = get_the_terms( $post_id, 'wp_theme' );
	if ( ! $terms || ! count( $terms ) ) {
		wp_set_post_terms( $post_id, wp_get_theme()->get_stylesheet(), 'wp_theme' );
	}
}
add_action( 'save_post_wp_template', 'set_unique_slug_on_create_template' );
