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
		$dynamic_products = $mapper->match_products($keywords, intval($atts['limit']));
		$static_products = $mapper->get_static_products();

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

				<?php if (!empty($dynamic_products)): ?>
				<section id="icart-dl-dynamic" class="icart-dl__products">
					<h2 class="icart-dl__section-title">Recommended for you</h2>
					<div class="icart-dl__grid">
						<?php foreach ($dynamic_products as $item): ?>
							<article class="icart-dl__card">
								<a href="<?php echo esc_url($item['url']); ?>" class="icart-dl__image">
									<?php if (!empty($item['image'])): ?>
										<img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
									<?php endif; ?>
								</a>
								<div class="icart-dl__card-content">
									<h3 class="icart-dl__product-title">
										<a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
									</h3>
									<?php if (!empty($item['price'])): ?>
										<div class="icart-dl__price"><?php echo esc_html($item['price']); ?></div>
									<?php endif; ?>
									<a class="button" href="<?php echo esc_url($item['url']); ?>">View</a>
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
						<?php foreach ($static_products as $item): ?>
							<article class="icart-dl__card">
								<a href="<?php echo esc_url($item['url']); ?>" class="icart-dl__image">
									<?php if (!empty($item['image'])): ?>
										<img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
									<?php endif; ?>
								</a>
								<div class="icart-dl__card-content">
									<h3 class="icart-dl__product-title">
										<a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
									</h3>
									<?php if (!empty($item['price'])): ?>
										<div class="icart-dl__price"><?php echo esc_html($item['price']); ?></div>
									<?php endif; ?>
									<a class="button" href="<?php echo esc_url($item['url']); ?>">View</a>
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

