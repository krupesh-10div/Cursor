<?php
/*
Plugin Name: WP Watch Valuation
Description: A watch valuation tool using WatchAnalytics-like API (WPForms) + Text→HTML viewer
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Optional: define your API key via wp-config.php or environment
if (!defined('WPWV_PERPLEXITY_API_KEY')) {
	define('WPWV_PERPLEXITY_API_KEY', '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');
}

add_action('wp_enqueue_scripts', function() {
	if (is_admin()) return;

	$ver = '1.3';

	// Existing estimation script (stubbed if not used)
	$handle_estimate = 'wpwv-estimate';
	$src_estimate    = plugins_url('assets/estimate.js', __FILE__);
	wp_enqueue_script($handle_estimate, $src_estimate, array('jquery'), $ver, true);
	wp_localize_script($handle_estimate, 'WPWV', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('wpwv_nonce'),
		'formId'   => 765,
		// Field mapping (adjust to your WPForms field IDs)
		'fields'   => array(
			'brand'           => 1,
			'model'           => 2,
			'condition'       => 4,
			'reference'       => 12,
			'year'            => 13,
			'box'             => 14,
			'papers'          => 15,
			'age'             => 16,
			'source'          => 18,
			'valuationHidden' => 20,
		),
	));

	// Text→HTML viewer script
	$handle_viewer = 'wpwv-viewer';
	$src_viewer    = plugins_url('assets/viewer.js', __FILE__);
	wp_register_script($handle_viewer, $src_viewer, array('jquery'), $ver, true);
	wp_localize_script($handle_viewer, 'WPWV_Viewer', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('wpwv_nonce'),
	));
});

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
    Apply a 12% reduction to the equivalent AUD price range.
    Return the result ONLY in this exact format:
    'Estimated valuation for your watch is <strong>AUD \$X – \$Y</strong>.'
    Do not include explanations, descriptions, or any other text.";


	$perplexity_api_key = defined('WPWV_PERPLEXITY_API_KEY') && WPWV_PERPLEXITY_API_KEY ? WPWV_PERPLEXITY_API_KEY : getenv('PPLX_API_KEY');
	if (empty($perplexity_api_key)) {
		wp_send_json_error(array('message' => 'API key not configured.'));
	}

	$api_url = 'https://api.perplexity.ai/chat/completions';

	$response = wp_remote_post($api_url, array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $perplexity_api_key,
			'Content-Type'  => 'application/json',
		),
		'body' => wp_json_encode(array(
			'model'       => 'sonar-pro',
			'messages'    => array(
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

	$decoded = json_decode(wp_remote_retrieve_body($response), true);
	$valuation = '';
	if (is_array($decoded) && isset($decoded['choices'][0]['message']['content'])) {
		$valuation = (string) $decoded['choices'][0]['message']['content'];
	}

	$valuation = trim(wp_kses_post($valuation));

	// If <strong> is missing, wrap the AUD price range
	if (!preg_match('/<strong>.*<\/strong>/', $valuation)) {
		$valuation = preg_replace(
			'/(AUD\s?\$[0-9,]+(?:\s?[–-]\s?\$?[0-9,]+)?)/',
			'<strong>$1<\/strong>',
			$valuation
		);
	}

	if ($valuation === '') {
		wp_send_json_error(array('message' => 'No valuation found.'));
	}

	preg_match('/Estimated valuation for your watch is .*\.<\/strong>\./', $valuation);

\twp_send_json_success(array(
		'valuation' => $valuation,
	));
}

function make_bold_text($matches) {
	$map = [
		'a'=>'𝗮','b'=>'𝗯','c'=>'𝗰','d'=>'𝗱','e'=>'𝗲','f'=>'𝗳','g'=>'𝗴',
		'h'=>'𝗵','i'=>'𝗶','j'=>'𝗷','k'=>'𝗸','l'=>'𝗹','m'=>'𝗺','n'=>'𝗻',
		'o'=>'𝗼','p'=>'𝗽','q'=>'𝗾','r'=>'𝗿','s'=>'𝘀','t'=>'𝘁','u'=>'𝘂',
		'v'=>'𝘃','w'=>'𝘄','x'=>'𝘅','y'=>'𝘆','z'=>'𝘇',
		'A'=>'𝗔','B'=>'𝗕','C'=>'𝗖','D'=>'𝗗','E'=>'𝗘','F'=>'𝗙','G'=>'𝗚',
		'H'=>'𝗛','I'=>'𝗜','J'=>'𝗝','K'=>'𝗞','L'=>'𝗟','M'=>'𝗠','N'=>'𝗡',
		'O'=>'𝗢','P'=>'𝗣','Q'=>'𝗤','R'=>'𝗥','S'=>'𝗦','T'=>'𝗧','U'=>'𝗨',
		'V'=>'𝗩','W'=>'𝗪','X'=>'𝗫','Y'=>'𝗬','Z'=>'𝗭',
		'0'=>'𝟬','1'=>'𝟭','2'=>'𝟮','3'=>'𝟯','4'=>'𝟰','5'=>'𝟱','6'=>'𝟲','7'=>'𝟳','8'=>'𝟴','9'=>'𝟵'
	];
	return strtr($matches[1], $map);
}

// Text → HTML Viewer: AJAX endpoint reusing the same range-strong-wrapping logic
add_action('wp_ajax_wpwv_text_to_html', 'wpwv_text_to_html');
add_action('wp_ajax_nopriv_wpwv_text_to_html', 'wpwv_text_to_html');
function wpwv_text_to_html() {
	check_ajax_referer('wpwv_nonce', 'nonce');
	$raw = isset($_POST['text']) ? wp_unslash($_POST['text']) : '';
	$raw = is_string($raw) ? $raw : '';
	$raw = trim($raw);

	// Basic sanitization while allowing simple HTML if user passes any
	$html = wp_kses_post($raw);

	// Ensure AUD price range appears bold if not already wrapped
	if (!preg_match('/<strong>.*<\/strong>/', $html)) {
		$html = preg_replace(
			'/(AUD\s?\$[0-9,]+(?:\s?[–-]\s?\$?[0-9,]+)?)/',
			'<strong>$1<\/strong>',
			$html
		);
	}

	if ($html === '') {
		wp_send_json_error(array('message' => 'No content provided.'));
	}

	wp_send_json_success(array('html' => $html));
}

// Shortcode: [wpwv_text_viewer]Initial text here[/wpwv_text_viewer]
add_shortcode('wpwv_text_viewer', function($atts, $content = null) {
	wp_enqueue_script('wpwv-viewer');

	$initial = $content ? $content : '';
	$initial = esc_textarea($initial);

	ob_start();
	?>
	<div class="wpwv-viewer" data-nonce="<?php echo esc_attr(wp_create_nonce('wpwv_nonce')); ?>">
		<textarea class="wpwv-input" rows="6" style="width:100%;"><?php echo $initial; ?></textarea>
		<p><button type="button" class="button button-primary wpwv-run">Preview</button></p>
		<div class="wpwv-output"></div>
	</div>
	<?php
	return (string) ob_get_clean();
});

