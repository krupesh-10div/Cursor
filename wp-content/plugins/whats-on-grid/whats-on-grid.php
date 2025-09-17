<?php
/**
 * Plugin Name: What's On Grid
 * Description: Custom Gutenberg block that renders a reusable grid of posts filtered by categories.
 * Version: 1.0.0
 * Author: Your Name
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: whats-on-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the block using the metadata loaded from the `block.json` file.
 */
function whats_on_grid_register_block() {
	// Register the editor script with required dependencies so it loads in Gutenberg
	wp_register_script(
		'whats-on-grid-editor',
		plugins_url( 'index.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-editor' ),
		'1.0.0',
		true
	);

	register_block_type( __DIR__, array(
		'render_callback' => 'whats_on_grid_render',
	) );
}
add_action( 'init', 'whats_on_grid_register_block' );

/**
 * Server-render the block so we can output Next pagination link.
 */
function whats_on_grid_render( $attributes, $content, $block ) {
	// Extract attributes with defaults
	$per_page = isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 30;
	$ids_string = isset( $attributes['idsString'] ) ? (string) $attributes['idsString'] : '429,615,450,614,434,511';
	$include_children = ! empty( $attributes['includeChildren'] );

	// Determine current page from query var "page" (fallback to paged)
	$current_page = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] ) : get_query_var( 'paged', 1 );

	// Build tax query
	$ids = array_filter( array_map( 'intval', preg_split( '/\s*,\s*/', $ids_string ) ) );
	$tax_query = array();
	if ( ! empty( $ids ) ) {
		$tax_query[] = array(
			'taxonomy' => 'category',
			'field' => 'term_id',
			'terms' => $ids,
			'include_children' => $include_children,
			'operator' => 'IN',
		);
	}

	$q = new WP_Query( array(
		'post_type' => 'post',
		'posts_per_page' => $per_page,
		'paged' => $current_page,
		'ignore_sticky_posts' => true,
		'tax_query' => $tax_query,
	) );

	// Let the InnerBlocks (core/query) render as authored in editor
	$inner = $content;

	// Build Next link if there are more pages
	$next_link = '';
	if ( $q->max_num_pages > $current_page ) {
		$next_page = $current_page + 1;
		$base_url = get_permalink();
		if ( ! $base_url ) {
			$base_url = home_url( add_query_arg( null, null ) );
		}
		$href = add_query_arg( array( 'page' => $next_page ), $base_url );
		$next_link = '<a class="gb-button gb-button-e4720bfe gb-button-text" href="' . esc_url( $href ) . '">Next</a>';
	}

	wp_reset_postdata();

	return '<div class="whats-on-grid-wrapper">' . $inner . $next_link . '</div>';
}
