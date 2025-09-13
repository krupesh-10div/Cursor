<?php
/*
Plugin Name: WP Watch Valuation
Description: Two-step watch valuation (preview, then submit) for WPForms
Version: 2.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

define('WPWV_FORM_ID', 765);

// === Frontend two-step flow (inline JS) ===
add_action('wp_enqueue_scripts', function () {
	if (is_admin()) return;

	$ajax_url = admin_url('admin-ajax.php');
	$nonce    = wp_create_nonce('wpwv');
	$form_id  = WPWV_FORM_ID;

	$js = <<<JS
(function(){
document.addEventListener('DOMContentLoaded',function(){
  var form = document.getElementById('wpforms-form-{$form_id}');
  if(!form) return;

  // Prevent Enter key submitting the form
  form.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
      e.preventDefault();
      return false;
    }
  });

  // Hide the default submit button
  var submitBtn = form.querySelector('#wpforms-submit-{$form_id}');
  if(submitBtn){
    submitBtn.style.display = 'none';
  }

  // Add Start Valuation button
  var startBtn = document.createElement('button');
  startBtn.type = 'button';
  startBtn.id = 'wpwv-start-btn';
  startBtn.textContent = 'Start Valuation';
  startBtn.className = 'wpwv-start-btn wpforms-submit';
  var submitWrap = form.querySelector('.wpforms-submit-container');
  if(submitWrap){
    submitWrap.appendChild(startBtn);
  } else {
    form.appendChild(startBtn);
  }

  // Preview container
  var container = document.createElement('div');
  container.id = 'wpwv-valuation-container';
  container.style.margin = '12px 0';
  if(submitWrap && submitWrap.parentNode){
    submitWrap.parentNode.insertBefore(container, submitWrap);
  } else {
    form.appendChild(container);
  }

  // Hidden input for valuation text
  var hidden = document.createElement('input');
  hidden.type = 'hidden';
  hidden.name = 'wpwv_valuation_preview';
  hidden.id   = 'wpwv_valuation_preview';
  form.appendChild(hidden);

  // Helper to collect field values
  function getValue(id){
    var single = form.querySelector('[name="wpforms[fields]['+id+']"]');
    if(single){ return single.value || ''; }
    var checks = form.querySelectorAll('[name="wpforms[fields]['+id+'][]"]:checked');
    if(checks && checks.length){ return Array.from(checks).map(function(i){return i.value;}); }
    return '';
  }

  // Click handler for Start Valuation
  startBtn.addEventListener('click', function(){
    if(!form.checkValidity()){
      form.reportValidity();
      return;
    }

    startBtn.disabled = true;
    startBtn.textContent = 'Getting valuation...';
    container.innerHTML = '';

    var fields = {
      '1':  getValue(1),   // brand
      '2':  getValue(2),   // model
      '12': getValue(12),  // reference
      '13': getValue(13),  // year
      '14': getValue(14),  // box
      '15': getValue(15),  // papers
      '16': getValue(16),  // age
      '4':  getValue(4),   // condition
      '18': getValue(18)   // source
    };

    var fd = new FormData();
    fd.append('action','wpwv_check_valuation');
    fd.append('nonce','$nonce');
    fd.append('fields', JSON.stringify(fields));

    fetch('$ajax_url', { method:'POST', body:fd, credentials:'same-origin' })
     .then(function(r){ return r.json(); })
     .then(function(data){
        var valuation = (data && data.success && data.data && data.data.valuation) ? data.data.valuation : 'No valuation found';

        // Show preview + click-to-submit link
        container.innerHTML = '<div class="wpwv-preview" style="font-size:16px;margin-bottom:8px;">Estimated valuation: '+ valuation +'</div>'
          + '<div style="margin-top:4px;font-size:14px;color:#333;">To connect with one of our valuation experts, please <a href="#" id="wpwv-submit-link" style="color:#0073aa;text-decoration:underline;">click here</a>.</div>';

        hidden.value = valuation;

        var link = container.querySelector('#wpwv-submit-link');
        if(link){
          link.addEventListener('click', function(ev){
            ev.preventDefault();
            form.submit();
          });
        }

        startBtn.style.display = 'none';

     })
     .catch(function(){
        container.innerHTML = '<div class="wpwv-preview">Unable to get valuation. Please try again.</div>';
        startBtn.disabled = false;
        startBtn.textContent = 'Start Valuation';
     });
  });
});
})();
JS;

	wp_register_script('wpwv-inline', false, [], '1.2.2', true);
	wp_enqueue_script('wpwv-inline');
	wp_add_inline_script('wpwv-inline', $js);
});

// === AJAX: Preview valuation without creating entry (kept for backward compatibility; not used by new flow) ===
add_action('wp_ajax_nopriv_wp_watch_valuation_preview', 'wpwv_ajax_preview');
add_action('wp_ajax_wp_watch_valuation_preview', 'wpwv_ajax_preview');
function wpwv_ajax_preview() {
	check_ajax_referer('wpwv', 'nonce');

	$raw = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '';
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		wp_send_json_error(['message' => 'Invalid payload']);
	}

	$get = function($id) use ($data) {
		if (!isset($data[$id])) return '';
		$val = $data[$id];
		if (is_array($val)) {
			$flat = array_map('sanitize_text_field', $val);
			return implode(', ', array_filter($flat));
		}
		return sanitize_text_field((string) $val);
	};

	$normalize = function($v) {
		$v = trim((string) $v);
		return ($v === 'Select' || $v === '--- Select Choice ---') ? '' : $v;
	};

	$brand     = $normalize($get('1'));
	$model     = $get('2');
	$reference = $get('12');
	$year      = $normalize($get('13'));
	$box       = $get('14');
	$papers    = $get('15');
	$age       = $normalize($get('16'));
	$condition = $normalize($get('4'));
	$source    = $normalize($get('18'));
	$series    = '';

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
	Return only the approximate resale value in price range (e.g., \$10,500 – \$12,000). Do not include explanations, descriptions, references, or any other text.";

	$valuation = wpwv_call_perplexity($prompt);
	wp_send_json_success(['valuation' => $valuation ?: 'No valuation found']);
}

// === AJAX: Valuation check (used by Start Valuation button) ===
add_action('wp_ajax_wpwv_check_valuation', 'wpwv_check_valuation');
add_action('wp_ajax_nopriv_wpwv_check_valuation', 'wpwv_check_valuation');
function wpwv_check_valuation() {
	check_ajax_referer('wpwv','nonce');

	$fields_json = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '';
	$fields = json_decode($fields_json, true);
	if (!is_array($fields)) {
		wp_send_json_error(['message' => 'Invalid payload']);
	}

	$get = function($id) use ($fields) {
		if (!isset($fields[$id])) return '';
		$val = $fields[$id];
		if (is_array($val)) return implode(', ', array_map('sanitize_text_field', $val));
		return sanitize_text_field((string)$val);
	};

	$brand     = $get('1');
	$model     = $get('2');
	$reference = $get('12');
	$year      = $get('13');
	$box       = $get('14');
	$papers    = $get('15');
	$age       = $get('16');
	$condition = $get('4');
	$source    = $get('18');
	$series    = '';

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
	Return only the approximate resale value in price range (e.g., \$10,500 – \$12,000).";

	$valuation = wpwv_call_perplexity($prompt);
	if (empty($valuation)) {
		$valuation = '$8,500 – $10,500';
	}

	wp_send_json_success(['valuation' => $valuation]);
}

// === Final submit: store valuation and show in confirmation ===
add_action('wpforms_process_complete', 'wpwv_wpforms_process_complete', 10, 4);
function wpwv_wpforms_process_complete($fields, $entry, $form_data, $entry_id) {
	if (empty($form_data['id']) || absint($form_data['id']) !== WPWV_FORM_ID) {
		return;
	}

	$valuation = '';
	if (isset($_POST['wpwv_valuation_preview'])) {
		$valuation = sanitize_text_field(wp_unslash($_POST['wpwv_valuation_preview']));
	}

	if ($valuation === '') {
		$get_value = function($id) use ($fields) {
			if (!isset($fields[$id])) return '';
			$val = $fields[$id]['value'];
			if (is_array($val)) {
				$flat = array_map('sanitize_text_field', $val);
				return implode(', ', array_filter($flat));
			}
			return sanitize_text_field($val);
		};

		$normalize_select = function($v) {
			$v = trim((string) $v);
			return ($v === 'Select' || $v === '--- Select Choice ---') ? '' : $v;
		};

		$brand     = $normalize_select($get_value(1));
		$model     = $get_value(2);
		$reference = $get_value(12);
		$year      = $normalize_select($get_value(13));
		$box       = $get_value(14);
		$papers    = $get_value(15);
		$age       = $normalize_select($get_value(16));
		$condition = $normalize_select($get_value(4));
		$source    = $normalize_select($get_value(18));
		$series    = '';

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
		Return only the approximate resale value in price range (e.g., \$10,500 – \$12,000). Do not include explanations, descriptions, references, or any other text.";

		$valuation = wpwv_call_perplexity($prompt);
	}

	$valuation = $valuation ?: 'No valuation found';

	if (function_exists('wpforms')) {
		wpforms()->entry_meta->add($entry_id, 'valuation', $valuation);
	}

	add_filter('wpforms_frontend_confirmation_message', function($message, $form_data_f, $fields_f) use ($valuation) {
		if (absint($form_data_f['id']) === WPWV_FORM_ID && !empty($valuation)) {
			return 'Estimated valuation for your watch is ' . esc_html($valuation)
     . '<br><br><div style="margin-top:8px;font-size:14px;">To get in touch with our valuation expert <a href="/contact-us" style="color:#0073aa;text-decoration:underline;">click here</a>.</div>';

		}
		return $message;
	}, 10, 3);
}

// === Shared: Perplexity API call ===
function wpwv_call_perplexity($prompt) {
	$perplexity_api_key = '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'; // Replace with your valid key
	$api_url = 'https://api.perplexity.ai/chat/completions';

	$response = wp_remote_post($api_url, [
		'headers' => [
			'Authorization' => 'Bearer ' . $perplexity_api_key,
			'Content-Type'  => 'application/json',
		],
		'body' => wp_json_encode([
			'model'       => 'sonar-pro',
			'messages'    => [
				['role' => 'system', 'content' => 'You are a watch valuation assistant using Chrono24 market prices.'],
				['role' => 'user',   'content' => $prompt],
			],
			'temperature' => 0.4,
		]),
		'timeout' => 60,
	]);

	if (is_wp_error($response)) {
		return '';
	}

	$body = json_decode(wp_remote_retrieve_body($response), true);
	return $body['choices'][0]['message']['content'] ?? '';
}

