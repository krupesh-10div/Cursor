<?php
/*
Plugin Name: iCart Dynamic Landing
Description: Dynamic landing page that adapts to user search keywords with GPT-generated content and WooCommerce product recommendations.
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
		'api_key' => '',
		'model' => 'gpt-4o-mini',
		'brand_tone' => 'Clear, helpful, confident, conversion-focused. Keep it concise and benefit-led.',
		'figma_url' => '',
		'cache_ttl' => 3600,
		'static_product_ids' => '',
		'mapping' => array(),
	);
	$options = get_option('icart_dl_settings', array());
	update_option('icart_dl_settings', wp_parse_args($options, $defaults));
}
register_activation_hook(__FILE__, 'icart_dl_activate');

?>

