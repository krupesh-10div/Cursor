<?php
/**
 * Plugin Name: What's On Grid
 * Description: Custom Gutenberg block that renders a reusable grid of posts filtered by categories with pagination.
 * Version: 1.0.0
 * Author: Sydney Travel Guide
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
	wp_register_script(
		'whats-on-grid-editor',
		plugins_url( 'index.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),
		'1.0.0',
		true
	);

	register_block_type( __DIR__, array(
		'render_callback' => 'whats_on_grid_render',
	) );
	wp_enqueue_style(
        'whats-on-grid-editor-style',
        plugins_url( 'style.css', __FILE__ ),
        array( 'wp-edit-blocks' ),
        '1.0.0'
    );
}
add_action( 'init', 'whats_on_grid_register_block' );

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'whats-on-grid-style',
        plugins_url( 'style.css', __FILE__ ),
        array(),
        '1.0.0'
    );
});
function whats_on_grid_normalize_base_url( $base_url ) {
	$base_url = trim( (string) $base_url );
	if ( $base_url === '' ) {
		$base_url = get_permalink();
	}
	if ( 0 === strpos( $base_url, '/' ) ) {
		$base_url = home_url( $base_url );
	}
	return remove_query_arg( array( 'page', 'paged' ), $base_url );
}

/**
 * Server-render grid and pagination (query-string ?page=N).
 */
function whats_on_grid_render( $attributes ) {
	$defaults = array(
		'perPage' => 30,
		'postType' => 'post',
		'taxonomy' => 'category',
		'termIds' => array( 429, 615, 450, 614, 434, 511 ),
		'includeChildren' => true,
		'columns' => 3,
		'baseUrl' => '/whats-on/',
	);
	$attributes = wp_parse_args( (array) $attributes, $defaults );

	$per_page = max( 1, (int) $attributes['perPage'] );
	$post_type = sanitize_key( $attributes['postType'] );
	$taxonomy = sanitize_key( $attributes['taxonomy'] );
	$term_ids = array_filter( array_map( 'intval', (array) $attributes['termIds'] ) );
	$include_children = ! empty( $attributes['includeChildren'] );
	$columns = max( 1, (int) $attributes['columns'] );
	$base_url = whats_on_grid_normalize_base_url( $attributes['baseUrl'] );

	// Read current page from ?page=N (fallback to paged)
	$paged = isset( $_GET['page'] ) ? max( 1, (int) $_GET['page'] ) : max( 1, (int) get_query_var( 'paged', 1 ) );

	$tax_query = array();
	if ( ! empty( $taxonomy ) && ! empty( $term_ids ) ) {
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field' => 'term_id',
			'terms' => $term_ids,
			'include_children' => $include_children,
			'operator' => 'IN',
		);
	}

	$query = new WP_Query( array(
		'post_type' => $post_type,
		'posts_per_page' => $per_page,
		'paged' => $paged,
		'ignore_sticky_posts' => true,
		'tax_query' => $tax_query,
	) );

	ob_start();
	?>
	<div class="whats-on-grid-wrapper">
		<div class="whats-on-grid" style="display:grid;grid-template-columns:repeat(<?php echo (int) $columns; ?>,1fr);gap:24px;">
			<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
				<article class="whats-on-grid__item">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="whats-on-grid__thumb gb-block-image-7f8874c3"><?php the_post_thumbnail( array('373', '210') ); ?></a>
					<?php endif; ?>
					<h2 class="whats-on-grid__title gb-headline-795bb9ef"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				</article>
			<?php endwhile; endif; ?>
		</div>
		<?php
		$total_pages = (int) $query->max_num_pages;
		if ( $total_pages > 1 ) {
			$base = add_query_arg( array( 'page' => '%#%' ), $base_url );
			$pagination_links = paginate_links( array(
				'base'      => $base,
				'format'    => '',
				'current'   => $paged,
				'total'     => $total_pages,
				'prev_text' => '« Prev',
				'next_text' => 'Next »',
				'type'      => 'array',
			) );

			if ( ! empty( $pagination_links ) ) {
				echo '<nav class="whats-on-grid__pagination" aria-label="Pagination">';

				foreach ( $pagination_links as $link ) {
					// Add base button classes
					$link = preg_replace(
						'/<a\s+/',
						'<a class="gb-button gb-button-e4720bfe gb-button-text page-numbers" ',
						$link,
						1
					);

					// Add specific classes for previous/next
					if ( strpos( $link, 'prev' ) !== false ) {
						$link = preg_replace(
							'/class="/',
							'class="whats-on-grid__prev ',
							$link,
							1
						);
					} elseif ( strpos( $link, 'next' ) !== false ) {
						$link = preg_replace(
							'/class="/',
							'class="whats-on-grid__next ',
							$link,
							1
						);
					}

					echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				echo '</nav>';
			}

		}
		?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
