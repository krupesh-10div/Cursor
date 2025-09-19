<?php
/*
Plugin Name: Dynamic Landing Page
Description: Dynamic landing page that adapts to user search keywords with ChatGPT-generated content and CSV-based product recommendations.
Version: 1.0.0
Author: Identixweb
*/

if (!defined('ABSPATH')) {
	exit;
}

define('DL_VERSION', '1.0.0');
define('DL_PLUGIN_FILE', __FILE__);
define('DL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes (simple loader)
spl_autoload_register(function ($class) {
	if (strpos($class, 'ICartDL_') === 0) {
		$short = str_replace('ICartDL_', '', $class);
		$short = str_replace('_', '-', $short);
		$file = DL_PLUGIN_DIR . 'inc/' . 'class-' . strtolower($short) . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
});

require_once DL_PLUGIN_DIR . 'inc/helpers.php';
require_once DL_PLUGIN_DIR . 'inc/class-cli.php';

function icart_dl_init() {
	// Ensure transients are namespaced
	if (!defined('DL_TRANSIENT_PREFIX')) {
		define('DL_TRANSIENT_PREFIX', 'icart_dl_');
	}

	// Register shortcodes
	if (class_exists('ICartDL_Shortcode')) {
		ICartDL_Shortcode::register();
		add_filter('the_content', array('ICartDL_Shortcode', 'maybe_inject_into_content'), 5);
	}

	// Admin settings page
	if (is_admin()) {
		new ICartDL_Settings();
	}

	// Sync landing map from sample keywords folder
	dl_sync_landing_map_from_samples();

	// Register rewrite rules and query vars
	add_filter('query_vars', function($vars){
		$vars[] = 'icart_keywords';
		$vars[] = 'icart_slug';
		$vars[] = 'icart_product_key';
		return $vars;
	});

	add_action('init', function(){
		$opts = icart_dl_get_settings();
		$landing_map = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
		foreach ($landing_map as $row) {
			if (empty($row['slug'])) { continue; }
			$slug = sanitize_title($row['slug']);
			add_rewrite_rule('^' . $slug . '/?$', 'index.php?icart_slug=' . $slug, 'top');
		}
	});

	// Route to plugin template when icart_slug is present
	add_filter('template_include', function($template){
		if (get_query_var('icart_slug')) {
			$tpl = DL_PLUGIN_DIR . 'templates/landing.php';
			if (file_exists($tpl)) {
				return $tpl;
			}
		}
		return $template;
	}, 99);

	// Flush rewrite rules if requested by settings update
	add_action('init', function(){
		if (get_transient('dl_flush_rewrite')) {
			flush_rewrite_rules();
			delete_transient('dl_flush_rewrite');
		}
	}, 99);
}
add_action('plugins_loaded', 'icart_dl_init');

function icart_dl_enqueue_assets() {
	wp_register_style('icart-dl-style', DL_PLUGIN_URL . 'assets/css/style.css', array(), DL_VERSION);
	wp_enqueue_style('icart-dl-style');
	wp_register_script('icart-dl-script', DL_PLUGIN_URL . 'assets/js/general.js', array(), DL_VERSION, true);
	wp_enqueue_script('icart-dl-script');

}
add_action('wp_enqueue_scripts', 'icart_dl_enqueue_assets');

// Activation hook to ensure default options
function icart_dl_activate() {
	$defaults = array(
		'openai_api_key' => '',
		'openai_model' => 'gpt-4o-mini',
		'brand_tone' => 'Clear, helpful, confident, conversion-focused. Keep it concise and benefit-led.',
		'cache_ttl' => 3600,
		'mapping' => array(),
		'landing_map' => array(),
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
