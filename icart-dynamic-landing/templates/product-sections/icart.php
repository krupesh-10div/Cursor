<?php
if (!defined('ABSPATH')) { exit; }

?>

<section class="icart-dl__product-section icart-dl__product-section--icart">
	<?php if ( ! empty( $content['title'] ) ) : ?>
		<h1 class="icart-dl__title"><?php echo esc_html( $content['title'] ); ?></h1>
	<?php endif; ?>
			<?php if ( ! empty( $content['short_description'] ) || ! empty( $content['explanation'] ) ) : ?>
			<p class="icart-dl__subtitle"><?php echo esc_html( $content['short_description'] ); ?></p>
			<?php endif; ?>
			<!-- <?php if ( $product_key ) : ?>
				<div class="icart-dl__badge">For: <?php echo esc_html( $product_key ); ?></div>
			<?php endif; ?> -->
</section>