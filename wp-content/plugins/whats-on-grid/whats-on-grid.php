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

	register_block_type( __DIR__ );
}
add_action( 'init', 'whats_on_grid_register_block' );
