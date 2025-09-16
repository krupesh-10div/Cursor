<?php
if (!defined('ABSPATH')) { exit; }
?>
<section class="icart-dl__product-section icart-dl__product-section--icart">
	<h1 class="icart-dl__title"><?php echo esc_html( isset( $content['title'] ) && $content['title'] !== '' ? $content['title'] : ( $content['heading'] ?? '' ) ); ?></h1>
			<?php if ( ! empty( $content['short_description'] ) || ! empty( $content['explanation'] ) ) : ?>
			<p class="icart-dl__subtitle"><?php echo esc_html( isset( $content['short_description'] ) && $content['short_description'] !== '' ? $content['short_description'] : ( $content['explanation'] ?? '' ) ); ?></p>
			<?php endif; ?>
			<!-- <?php if ( $product_key ) : ?>
				<div class="icart-dl__badge">For: <?php echo esc_html( $product_key ); ?></div>
			<?php endif; ?> -->
</section>
