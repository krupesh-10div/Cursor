<?php
if (!defined('ABSPATH')) { exit; }

$keywords = icart_dl_get_search_keywords();
$content = ICartDL_Content_Generator::generate($keywords);
$landing = icart_dl_get_landing_entry();
$product_key = $landing['product_key'] ?? '';

// Yoast SEO dynamic filters
add_filter('pre_get_document_title', function($title) use ($content, $keywords) {
	$k = $keywords ? ' | ' . sanitize_text_field($keywords) : '';
	return $content['heading'] . $k;
}, 20);
add_filter('wpseo_title', function($title) use ($content, $keywords){
	$k = $keywords ? ' | ' . sanitize_text_field($keywords) : '';
	return $content['heading'] . $k;
}, 20);
add_filter('wpseo_metadesc', function($desc) use ($content){
	return wp_strip_all_tags($content['explanation']);
}, 20);

get_header();
?>
<main class="icart-dl">
	<div class="icart-dl__container">
		<header class="icart-dl__header">
			<h1 class="icart-dl__title"><?php echo esc_html($content['heading']); ?></h1>
			<p class="icart-dl__subtitle"><?php echo esc_html($content['subheading']); ?></p>
			<?php if ($product_key): ?>
				<div class="icart-dl__badge">For: <?php echo esc_html($product_key); ?></div>
			<?php endif; ?>
		</header>

		<div class="icart-dl__explanation">
			<p><?php echo wp_kses_post($content['explanation']); ?></p>
			<?php if (!empty($content['cta'])): ?>
				<a href="#icart-dl-dynamic" class="icart-dl__cta button"><?php echo esc_html($content['cta']); ?></a>
			<?php endif; ?>
		</div>

		<?php
		$partial = ICART_DL_PLUGIN_DIR . 'templates/product-sections/' . sanitize_title($product_key) . '.php';
		if ($product_key && file_exists($partial)) {
			include $partial;
		}
		?>
	</div>
</main>
<?php get_footer(); ?>

