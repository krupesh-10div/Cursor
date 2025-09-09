<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Shortcode {
	public static function register() {
		add_shortcode('icart_dynamic_page', array(__CLASS__, 'render'));
	}

	public static function render($atts) {
		$atts = shortcode_atts(array(
			'limit' => 6,
		), $atts, 'icart_dynamic_page');

		$keywords = icart_dl_get_search_keywords();
		$content = ICartDL_Content_Generator::generate($keywords);

		$mapper = new ICartDL_Keyword_Mapper();
		$match = $mapper->match_products($keywords);
		$wc_products = $mapper->fetch_wc_products($match, intval($atts['limit']));

		$settings = icart_dl_get_settings();
		$static_ids = array_filter(array_map('absint', preg_split('/[\s,|]+/', $settings['static_product_ids'] ?? '')));
		$static_products = array();
		if (!empty($static_ids) && function_exists('wc_get_products')) {
			$static_products = wc_get_products(array(
				'include' => $static_ids,
				'limit' => count($static_ids),
				'orderby' => 'include',
			));
		}

		ob_start();
		?>
		<section class="icart-dl">
			<div class="icart-dl__container">
				<header class="icart-dl__header">
					<h1 class="icart-dl__title"><?php echo esc_html($content['heading']); ?></h1>
					<p class="icart-dl__subtitle"><?php echo esc_html($content['subheading']); ?></p>
				</header>

				<div class="icart-dl__explanation">
					<p><?php echo wp_kses_post($content['explanation']); ?></p>
					<?php if (!empty($content['cta'])): ?>
						<a href="#icart-dl-dynamic" class="icart-dl__cta button"><?php echo esc_html($content['cta']); ?></a>
					<?php endif; ?>
				</div>

				<?php if (!empty($wc_products)): ?>
				<section id="icart-dl-dynamic" class="icart-dl__products">
					<h2 class="icart-dl__section-title">Recommended for you</h2>
					<div class="icart-dl__grid">
						<?php foreach ($wc_products as $product): ?>
							<?php /** @var WC_Product $product */ ?>
							<article class="icart-dl__card">
								<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="icart-dl__image">
									<?php echo $product->get_image('woocommerce_thumbnail'); ?>
								</a>
								<div class="icart-dl__card-content">
									<h3 class="icart-dl__product-title">
										<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
									</h3>
									<div class="icart-dl__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
									<a class="button add_to_cart_button" href="<?php echo esc_url('?add-to-cart=' . $product->get_id()); ?>">Add to cart</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>

				<?php if (!empty($static_products)): ?>
				<section class="icart-dl__products icart-dl__products--static">
					<h2 class="icart-dl__section-title">Popular choices</h2>
					<div class="icart-dl__grid">
						<?php foreach ($static_products as $product): ?>
							<?php /** @var WC_Product $product */ ?>
							<article class="icart-dl__card">
								<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="icart-dl__image">
									<?php echo $product->get_image('woocommerce_thumbnail'); ?>
								</a>
								<div class="icart-dl__card-content">
									<h3 class="icart-dl__product-title">
										<a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
									</h3>
									<div class="icart-dl__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
									<a class="button add_to_cart_button" href="<?php echo esc_url('?add-to-cart=' . $product->get_id()); ?>">Add to cart</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}

?>

