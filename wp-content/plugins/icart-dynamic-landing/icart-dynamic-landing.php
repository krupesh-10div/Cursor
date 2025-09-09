<?php
/*
Plugin Name: iCart Dynamic Landing
Description: Dynamic landing page that adapts to user search keywords with Perplexity-generated content and CSV-based product recommendations.
Version: 1.0.0
Author: Identixweb
*/

if (!defined('ABSPATH')) {
	exit;
}

define('ICART_DL_VERSION', '1.0.0');
define('ICART_DL_PLUGIN_FILE', __FILE__);
define('ICART_DL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ICART_DL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes (simple loader)
spl_autoload_register(function ($class) {
	if (strpos($class, 'ICartDL_') === 0) {
		$short = str_replace('ICartDL_', '', $class);
		$short = str_replace('_', '-', $short);
		$file = ICART_DL_PLUGIN_DIR . 'inc/' . 'class-' . strtolower($short) . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
});

require_once ICART_DL_PLUGIN_DIR . 'inc/helpers.php';

function icart_dl_init() {
	// Ensure transients are namespaced
	if (!defined('ICART_DL_TRANSIENT_PREFIX')) {
		define('ICART_DL_TRANSIENT_PREFIX', 'icart_dl_');
	}

	// Register shortcode
	ICartDL_Shortcode::register();

	// Admin settings page
	if (is_admin()) {
		new ICartDL_Settings();
	}

	// Sync landing map from sample keywords folder
	icart_dl_sync_landing_map_from_samples();

	// Register rewrite rules and query vars
	add_filter('query_vars', function($vars){
		$vars[] = 'icart_keywords';
		$vars[] = 'icart_slug';
		$vars[] = 'icart_product_key';
		return $vars;
	});

	add_action('init', function(){
		$opts = icart_dl_get_settings();
		$landing_slug = isset($opts['landing_page_slug']) && $opts['landing_page_slug'] !== '' ? sanitize_title($opts['landing_page_slug']) : 'dynamic-landing';
		$landing_map = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
		foreach ($landing_map as $row) {
			if (empty($row['slug'])) { continue; }
			$slug = sanitize_title($row['slug']);
			add_rewrite_rule('^' . $slug . '/?$', 'index.php?pagename=' . $landing_slug . '&icart_slug=' . $slug, 'top');
		}
	});

	// Flush rewrite rules if requested by settings update
	add_action('init', function(){
		if (get_transient('icart_dl_flush_rewrite')) {
			flush_rewrite_rules();
			delete_transient('icart_dl_flush_rewrite');
		}
	}, 99);
}
add_action('plugins_loaded', 'icart_dl_init');

function icart_dl_enqueue_assets() {
	wp_register_style('icart-dl-style', ICART_DL_PLUGIN_URL . 'assets/css/style.css', array(), ICART_DL_VERSION);
	wp_enqueue_style('icart-dl-style');
}
add_action('wp_enqueue_scripts', 'icart_dl_enqueue_assets');

// Activation hook to ensure default options
function icart_dl_activate() {
	$defaults = array(
		'perplexity_api_key' => '',
		'perplexity_model' => 'sonar-pro',
		'brand_tone' => 'Clear, helpful, confident, conversion-focused. Keep it concise and benefit-led.',
		'figma_url' => '',
		'cache_ttl' => 3600,
		'static_products' => '',
		'mapping' => array(),
		'landing_map' => array(),
		'landing_page_slug' => 'dynamic-landing',
		'base_path' => 'solutions',
	);
	$options = get_option('icart_dl_settings', array());
	update_option('icart_dl_settings', wp_parse_args($options, $defaults));
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'icart_dl_activate');

function icart_dl_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'icart_dl_deactivate');

?>

