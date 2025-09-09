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

// Canonical and robots via Yoast if present, else output fallbacks
$slug = isset($landing['slug']) ? sanitize_title($landing['slug']) : sanitize_title($keywords);
$canonical = trailingslashit(home_url('/' . $slug));
add_filter('wpseo_canonical', function($url) use ($canonical) { return $canonical; }, 20);
add_filter('wpseo_robots', function($robots) { return 'index,follow'; }, 20);

// Open Graph/Twitter filters for Yoast
add_filter('wpseo_opengraph_title', function($t) use ($content, $keywords){
	$k = $keywords ? ' | ' . sanitize_text_field($keywords) : '';
	return $content['heading'] . $k;
}, 20);
add_filter('wpseo_opengraph_desc', function($d) use ($content){
	return wp_strip_all_tags($content['explanation']);
}, 20);
add_filter('wpseo_twitter_title', function($t) use ($content){ return $content['heading']; }, 20);
add_filter('wpseo_twitter_description', function($d) use ($content){ return wp_strip_all_tags($content['explanation']); }, 20);

// Fallback meta tags and JSON-LD if Yoast not active
add_action('wp_head', function() use ($content, $canonical) {
	if (defined('WPSEO_VERSION')) { return; }
	$site_name = get_bloginfo('name');
	$title = $content['heading'];
	$desc = wp_strip_all_tags($content['explanation']);
	$img = get_site_icon_url(512);
	echo '<meta name="robots" content="index,follow" />';
	echo '<link rel="canonical" href="' . esc_url($canonical) . '" />';
	echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />';
	echo '<meta property="og:type" content="website" />';
	echo '<meta property="og:url" content="' . esc_url($canonical) . '" />';
	echo '<meta property="og:title" content="' . esc_attr($title) . '" />';
	echo '<meta property="og:description" content="' . esc_attr($desc) . '" />';
	if ($img) { echo '<meta property="og:image" content="' . esc_url($img) . '" />'; }
	echo '<meta name="twitter:card" content="summary_large_image" />';
	echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />';
	echo '<meta name="twitter:description" content="' . esc_attr($desc) . '" />';
	if ($img) { echo '<meta name="twitter:image" content="' . esc_url($img) . '" />'; }

	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'WebPage',
		'url' => $canonical,
		'name' => $title,
		'description' => $desc,
		'inLanguage' => get_bloginfo('language'),
		'isPartOf' => array(
			'@type' => 'WebSite',
			'name' => $site_name,
			'url' => home_url('/'),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}, 5);

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

