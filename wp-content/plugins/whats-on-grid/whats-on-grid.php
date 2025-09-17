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
	// Register editor script (no build) with dependencies
	wp_register_script(
		'whats-on-grid-editor',
		plugins_url( 'index.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),
		'1.0.0',
		true
	);

	register_block_type( __DIR__, array(
		'render_callback' => 'whats_on_grid_render',
	) );
}
add_action( 'init', 'whats_on_grid_register_block' );

/**
 * Server-render the grid and pagination link.
 */
function whats_on_grid_render( $attributes, $content, $block ) {
	$defaults = array(
		'perPage' => 30,
		'idsString' => '429,615,450,614,434,511',
		'includeChildren' => true,
		'columns' => 3,
		'baseUrl' => '/whats-on/',
		'queryVar' => 'page',
	);
	$attributes = wp_parse_args( $attributes, $defaults );

	$per_page = (int) $attributes['perPage'];
	$ids_string = (string) $attributes['idsString'];
	$include_children = ! empty( $attributes['includeChildren'] );
	$columns = max( 1, (int) $attributes['columns'] );
	$base_url = trim( (string) $attributes['baseUrl'] );
	$query_var = preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $attributes['queryVar'] );

	// Current page from custom query var fallback to paged
	$current_page = isset( $_GET[ $query_var ] ) ? max( 1, (int) $_GET[ $query_var ] ) : get_query_var( 'paged', 1 );

	// Build taxonomy filter
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

	// Build base URL for Next link. If relative path provided, resolve to site URL
	if ( empty( $base_url ) ) {
		$base_url = get_permalink();
	}
	if ( 0 === strpos( $base_url, '/' ) ) {
		$base_url = home_url( $base_url );
	}

	ob_start();
	?>
	<div class="whats-on-grid-wrapper">
		<div class="whats-on-grid" style="display:grid;grid-template-columns:repeat(<?php echo (int) $columns; ?>,1fr);gap:24px;">
			<?php if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post(); ?>
				<article class="whats-on-grid__item">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="whats-on-grid__thumb"><?php the_post_thumbnail( 'large' ); ?></a>
					<?php endif; ?>
					<h3 class="whats-on-grid__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				</article>
			<?php endwhile; endif; ?>
		</div>
		<?php if ( $q->max_num_pages > $current_page ) : ?>
			<?php $next_page = $current_page + 1; $href = add_query_arg( array( $query_var => $next_page ), $base_url ); ?>
			<a class="gb-button gb-button-e4720bfe gb-button-text" href="<?php echo esc_url( $href ); ?>">Next</a>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
