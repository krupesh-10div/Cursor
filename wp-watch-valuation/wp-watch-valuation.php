<?php
/*
Plugin Name: WP Watch Valuation
Description: A watch valuation tool using WatchAnalytics-like API (WPForms)
Version: 1.2
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// ================================
// Enqueue front-end assets
// ================================
add_action('wp_enqueue_scripts', function() {
	// Only enqueue on frontend
	if (is_admin()) return;

	$handle = 'wpwv-estimate';
	$src    = plugins_url('assets/estimate.js', __FILE__);
 	$ver    = '1.2';

 	wp_enqueue_script($handle, $src, array('jquery'), $ver, true);
	wp_localize_script($handle, 'WPWV', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('wpwv_nonce'),
		'formId'   => 765,
	));
});

// ================================
// AJAX: Pre-submit estimation
// ================================
add_action('wp_ajax_wpwv_estimate_valuation', 'wpwv_estimate_valuation');
add_action('wp_ajax_nopriv_wpwv_estimate_valuation', 'wpwv_estimate_valuation');
function wpwv_estimate_valuation() {
	check_ajax_referer('wpwv_nonce', 'nonce');

	$get_post = function($key) {
		return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
	};

	$normalize_select = function($v) {
		$v = trim((string) $v);
		return ($v === 'Select' || $v === '--- Select Choice ---') ? '' : $v;
	};

	$brand     = $normalize_select($get_post('brand'));
	$model     = $get_post('model');
	$reference = $get_post('reference');
	$year      = $normalize_select($get_post('year'));
	$box       = $get_post('box');
	$papers    = $get_post('papers');
	$age       = $normalize_select($get_post('age'));
	$condition = $normalize_select($get_post('condition'));
	$source    = $normalize_select($get_post('source'));

	$series = '';

	$prompt = "
	Estimate the market value of this watch based on Chrono24 data:
	Brand: {$brand}
	Model: {$model}
	Series: {$series}
	Reference Number: {$reference}
	Purchase Year: {$year}
	Box: {$box}
	Papers: {$papers}
	Age: {$age}
	Condition (1-10): {$condition}
	Return only the approximate resale value in price range (e.g., $10,500 – $12,000). Do not include explanations, descriptions, references, or any other text.";

	$perplexity_api_key = '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';
	$api_url = 'https://api.perplexity.ai/chat/completions';

	$response = wp_remote_post($api_url, array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $perplexity_api_key,
			'Content-Type'  => 'application/json',
		),
		'body' => wp_json_encode(array(
			'model'     => 'sonar-pro',
			'messages'  => array(
				array('role' => 'system', 'content' => 'You are a watch valuation assistant using Chrono24 market prices.'),
				array('role' => 'user',   'content' => $prompt),
			),
			'temperature' => 0.4,
		)),
		'timeout' => 60,
	));

	if (is_wp_error($response)) {
		wp_send_json_error(array('message' => 'Unable to fetch estimate.'));
	}

	$body = json_decode(wp_remote_retrieve_body($response), true);
	$valuation = isset($body['choices'][0]['message']['content']) ? (string) $body['choices'][0]['message']['content'] : '';

	if ($valuation === '') {
		wp_send_json_error(array('message' => 'No valuation found.'));
	}

	wp_send_json_success(array(
		'valuation' => $valuation,
	));
}

