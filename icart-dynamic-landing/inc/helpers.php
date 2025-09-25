<?php
if (!defined('ABSPATH')) {
	exit;
}

function icart_dl_get_settings() {
	return get_option('icart_dl_settings', array());
}

function icart_dl_get_landing_map() {
	$opts = icart_dl_get_settings();
	$map = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
	// Normalize by slug key for quick lookup
	$by_slug = array();
	foreach ($map as $row) {
		$slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
		if ($slug !== '') {
			$by_slug[$slug] = $row;
		}
	}
	return $by_slug;
}

function icart_dl_get_landing_entry() {
	$slug = get_query_var('icart_slug');
	if (!$slug) {
		return null;
	}
	$map = icart_dl_get_landing_map();
	$slug = sanitize_title($slug);
	return isset($map[$slug]) ? $map[$slug] : null;
}

function icart_dl_get_search_keywords() {
	$keywords = '';
	if (!empty($_GET['s'])) {
		$keywords = sanitize_text_field(wp_unslash($_GET['s']));
	} elseif (!empty($_GET['q'])) {
		$keywords = sanitize_text_field(wp_unslash($_GET['q']));
	} elseif (!empty($_GET['keywords'])) {
		$keywords = sanitize_text_field(wp_unslash($_GET['keywords']));
	} elseif (get_query_var('icart_keywords')) {
		$keywords = sanitize_text_field(get_query_var('icart_keywords'));
	} elseif (get_query_var('icart_slug')) {
		$entry = icart_dl_get_landing_entry();
		if ($entry && !empty($entry['keywords'])) {
			$keywords = sanitize_text_field($entry['keywords']);
		} else {
			$keywords = sanitize_text_field(get_query_var('icart_slug'));
		}
	}
	return trim($keywords);
}

function icart_dl_normalize_keywords($keywords) {
	$normalized = strtolower(wp_strip_all_tags($keywords));
	$normalized = preg_replace('/\s+/', ' ', $normalized);
	return trim($normalized);
}

function icart_dl_build_transient_key($prefix, $keywords) {
	$hash = substr(md5(icart_dl_normalize_keywords($keywords)), 0, 12);
	return DL_TRANSIENT_PREFIX . $prefix . '_' . $hash;
}

function icart_dl_scan_sample_keywords() {
	$base_dir = DL_PLUGIN_DIR . 'sample/keywords/';
	$entries = array();
	if (!is_dir($base_dir)) {
		return $entries;
	}
	$files = glob($base_dir . '*.csv');
	foreach ($files as $file) {
		$product_key = sanitize_title(pathinfo($file, PATHINFO_FILENAME));
		if (($handle = fopen($file, 'r')) !== false) {
			$line = 0;
			$headers = array();
			while (($data = fgetcsv($handle)) !== false) {
				$line++;
				if ($line === 1) {
					$headers = array_map('strtolower', $data);
				}
				$kw = '';
				if (!empty($data[0])) {
					$kw = trim($data[0]);
				}
				if ($kw === '' || strtolower($kw) === 'keyword' || strtolower($kw) === 'keywords') {
					continue;
				}
				$slug = sanitize_title($kw);
				$entries[] = array(
					'slug' => $slug,
					'keywords' => $kw,
					'product_key' => $product_key,
					'title' => '',
					'description' => '',
				);
			}
			fclose($handle);
		}
	}
	return $entries;
}

function dl_sync_landing_map_from_samples() {
	$entries = icart_dl_scan_sample_keywords();
	$stored = icart_dl_get_settings();
	$current = isset($stored['landing_map']) && is_array($stored['landing_map']) ? $stored['landing_map'] : array();
	$hash_new = md5(wp_json_encode($entries));
	$hash_old = isset($stored['landing_map_hash']) ? $stored['landing_map_hash'] : '';
	if ($hash_new !== $hash_old) {
		$stored['landing_map'] = $entries;
		$stored['landing_map_hash'] = $hash_new;
		update_option('icart_dl_settings', $stored);
		set_transient('dl_flush_rewrite', 1, 60);
	}
}

/**
 * JSON Content Store helpers (per product JSON, e.g. icart.json, steller.json)
 */
function icart_dl_content_json_dir() {
	return DL_PLUGIN_DIR . 'sample/content/';
}

function icart_dl_content_json_path_for_product($product_key) {
	$product_key = sanitize_title($product_key);
	return icart_dl_content_json_dir() . $product_key . '.json';
}

function icart_dl_ensure_content_dir() {
	$dir = icart_dl_content_json_dir();
	if (!is_dir($dir)) {
		wp_mkdir_p($dir);
	}
}

function icart_dl_load_content_map_for_product($product_key) {
	$path = icart_dl_content_json_path_for_product($product_key);
	if (!file_exists($path)) {
		return array();
	}
	$raw = file_get_contents($path);
	$data = json_decode($raw, true);
	return is_array($data) ? $data : array();
}

function icart_dl_write_content_map_for_product($product_key, $map) {
	icart_dl_ensure_content_dir();
	$path = icart_dl_content_json_path_for_product($product_key);
	$payload = wp_json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	file_put_contents($path, $payload);
}

function icart_dl_titlecase($text) {
	$text = trim((string)$text);
	$text = mb_strtolower($text);
	$mode = defined('MB_CASE_TITLE_SIMPLE') ? MB_CASE_TITLE_SIMPLE : MB_CASE_TITLE;
	return mb_convert_case($text, $mode, 'UTF-8');
}

function icart_dl_trim_to_chars($text, $max) {
	$text = trim((string)$text);
	if ($text === '' || $max <= 0) { return ''; }
	if (mb_strlen($text) <= $max) { return $text; }
	$truncated = mb_substr($text, 0, $max);
	$space = mb_strrpos($truncated, ' ');
	if ($space !== false && $space > ($max - 20)) {
		$truncated = mb_substr($truncated, 0, $space);
	}
	return rtrim($truncated, "\s\.,;:!-—") . '…';
}

function icart_dl_normalize_short_description($text, $min_words = 25, $max_words = 30) {
	$text = trim(wp_strip_all_tags((string)$text));
	$text = preg_replace('/\s+/', ' ', $text);
	if ($text === '') { return ''; }
	$words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
	// Remove trailing stop-words that cause dangling fragments
	$bad_endings = array('a','an','and','or','to','with','for','of','in','on','at','by','from','the');
	while (!empty($words) && in_array(strtolower(end($words)), $bad_endings, true)) {
		array_pop($words);
	}
	// Enforce word bounds
	if (count($words) > $max_words) {
		$words = array_slice($words, 0, $max_words);
	}
	if (count($words) < $min_words) {
		$pad = array('clearly','simply','confidently','seamlessly','effectively');
		$pi = 0;
		while (count($words) < $min_words) {
			$words[] = $pad[$pi % count($pad)];
			$pi++;
		}
	}
	$out = implode(' ', $words);
	$out = rtrim($out, ' .,!?:;');
	return $out . '.';
}

function icart_dl_generate_25_word_description_from_keywords($keywords) {
	$kw = trim((string)$keywords);
	if ($kw === '') { $kw = 'iCart for Shopify'; }

	// Allowed benefits per spec
	$benefits = array(
		'Upselling & Cross-selling',
		'Product Bundles & Volume Discounts',
		'Progress Bars & Free Gifts',
		'Sticky/Slide Cart Drawer & Cart Popups',
		'In-cart Offers to Boost AOV',
	);
	// Natural paraphrases for friendlier copy while staying within allowed scope
	$benefit_synonyms = array(
		'Upselling & Cross-selling' => 'smart upselling and cross‑selling',
		'Product Bundles & Volume Discounts' => 'flexible product bundles and volume discounts',
		'Progress Bars & Free Gifts' => 'engaging progress bars with free gifts',
		'Sticky/Slide Cart Drawer & Cart Popups' => 'a seamless sticky cart drawer and eye‑catching cart popups',
		'In-cart Offers to Boost AOV' => 'intuitive in‑cart offers to boost AOV',
	);

	// Deterministic selection to ensure uniqueness per keyword while avoiding repetition across keywords
	$seed = hexdec(substr(md5(mb_strtolower($kw)), 0, 8));
	$idx = range(0, count($benefits) - 1);
	usort($idx, function($a, $b) use ($benefits, $seed){
		$ha = crc32($benefits[$a] . '|' . $seed);
		$hb = crc32($benefits[$b] . '|' . $seed);
		if ($ha === $hb) { return 0; }
		return ($ha < $hb) ? -1 : 1;
	});
	// Pick 3–4 benefits
	$count = 3 + ($seed % 2); // 3 or 4
	$chosen = array();
	for ($i = 0; $i < $count; $i++) { $chosen[] = $benefits[$idx[$i]]; }

	// Build a natural, friendly description (~25–30 words). No questions. Mention iCart once and Shopify merchants once.
	$verbs = array('helps','supports','empowers','equips','enables');
	$verb = $verbs[$seed % count($verbs)];
	$chosen_syn = array();
	foreach ($chosen as $label) { $chosen_syn[] = isset($benefit_synonyms[$label]) ? $benefit_synonyms[$label] : strtolower($label); }
	$closers = array('increase average order value','lift AOV','boost average order value','raise AOV');
	$closer = $closers[$seed % count($closers)];

	// Template variants to reduce repetition across keywords
	$templates = array(
		'iCart {verb} Shopify merchants drive more revenue with {b1}, {b2}, and {b3}, encouraging easy add‑ons with {b4}.',
		'Boost conversions with iCart’s {b1}, {b2}, and {b3} for Shopify merchants, designed to {closer}.',
		'Create seamless shopping experiences using iCart’s {b1}, {b2}, and {b3} so Shopify merchants can {closer}.',
		'Raise order value with iCart: {b1}, {b2}, and {b3} tailored for Shopify merchants to {closer}.',
		'With iCart, Shopify merchants get {b1}, {b2}, and {b3}, plus {b4} that supports smooth customer engagement to {closer}.',
	);
	$tpl = $templates[$seed % count($templates)];
	$b1 = $chosen_syn[0];
	$b2 = $chosen_syn[1];
	$b3 = isset($chosen_syn[2]) ? $chosen_syn[2] : $chosen_syn[0];
	$b4 = isset($chosen_syn[3]) ? $chosen_syn[3] : $chosen_syn[1];
	$text = str_replace(
		array('{verb}','{b1}','{b2}','{b3}','{b4}','{closer}'),
		array($verb, $b1, $b2, $b3, $b4, $closer),
		$tpl
	);
	return icart_dl_normalize_short_description($text, 25, 30);
}

// Simple wrapper for title-based fallback generation
function icart_dl_generate_25_word_description_from_title($title) {
	return icart_dl_generate_25_word_description_from_keywords($title);
}

function icart_dl_generate_title_short_from_keywords($keywords) {
	$k = wp_strip_all_tags($keywords);
	$title = icart_dl_titlecase($k);
	$title = icart_dl_trim_to_chars($title, 60);
	// Generate a 25-word short description from the original keywords (no fixed prefixes)
	$short = icart_dl_generate_25_word_description_from_keywords($keywords);
	return array($title, $short);
}

// Local-only generator for use during CSV->JSON builds (avoids any network calls)
function icart_dl_generate_title_short_local($keywords) {
	$k = wp_strip_all_tags($keywords);
	$title = icart_dl_titlecase($k);
	$title = icart_dl_trim_to_chars($title, 60);
	$short = icart_dl_generate_25_word_description_from_keywords($keywords);
	$short = icart_dl_normalize_short_description($short, 25, 30);
	return array($title, $short);
}

/**
 * Call OpenAI Chat Completions API and return the assistant message content or WP_Error.
 */
function icart_dl_openai_chat($messages, $model = null, $max_tokens = 400, $temperature = 0.4) {
	$settings = icart_dl_get_settings();
	$api_key = isset($settings['openai_api_key']) ? trim($settings['openai_api_key']) : '';
	// Back-compat: allow legacy key to be used if present
	if ($api_key === '' && isset($settings['perplexity_api_key'])) {
		$api_key = trim($settings['perplexity_api_key']);
	}
	if ($api_key === '') {
		return new \WP_Error('missing_api_key', 'OpenAI API key is not configured.');
	}
	$model = $model ? $model : (isset($settings['openai_model']) ? $settings['openai_model'] : 'gpt-5');

	$body = array(
		'model' => $model,
		'messages' => $messages,
		'temperature' => $temperature,
		'max_tokens' => $max_tokens,
	);

	$args = array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type' => 'application/json',
		),
		'body' => wp_json_encode($body),
		'timeout' => 30,
	);

	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
	if (is_wp_error($response)) { return $response; }
	$code = wp_remote_retrieve_response_code($response);
	$raw = wp_remote_retrieve_body($response);
	if ($code < 200 || $code >= 300 || empty($raw)) {
		return new \WP_Error('bad_http_status', 'OpenAI API HTTP ' . intval($code));
	}
	$data = json_decode($raw, true);
	if (!isset($data['choices'][0]['message']['content'])) {
		return new \WP_Error('bad_response', 'OpenAI API returned malformed response.');
	}
	return $data['choices'][0]['message']['content'];
}

/**
 * Generate title and 22-25 word short description from keywords using OpenAI. Fallback to local if API fails.
 */
function icart_dl_generate_title_short_openai($keywords, $options = array()) {
	$settings = icart_dl_get_settings();
	$brand_tone = isset($settings['brand_tone']) ? $settings['brand_tone'] : '';
	$slug = isset($options['slug']) ? sanitize_title($options['slug']) : '';
	$product_key = isset($options['product_key']) ? sanitize_title($options['product_key']) : '';
	$uniqueness_seed = md5($slug . '|' . $product_key);
	$system = 'You are a senior marketing copywriter. Use flawless American English with correct grammar and spelling. ' .
		'Write concise, benefit-led, keyword-aware copy. Do not include brand names unless present in keywords. ' .
		'Ensure titles follow rules and descriptions are varied and engaging. ' .
		'Output strict JSON ONLY with keys: title, short_description.';
	$user = wp_json_encode(array(
		'instructions' => 'Title rules: (1) If the corrected keyword contains 8 or more words, set the title EQUAL to the corrected keyword EXACTLY (no extra words). (2) If fewer than 8 words, write a new H1 title of 8–12 words that preserves the core meaning of the keyword. Correct obvious spelling errors. Description rules: Write ONE unique description of 25–30 words about iCart for Shopify merchants. Use only these benefit areas (paraphrase labels but add no new features): Upselling & Cross-selling; Product Bundles & Volume Discounts; Progress Bars & Free Gifts; Sticky/Slide Cart Drawer & Cart Popups; In-cart Offers to Boost AOV. Select 3–4 benefits and vary order and sentence structure. Start with varied natural openings like “iCart helps…”, “Boost… with iCart…”, or “Create… using iCart…”. Do NOT ask questions. Ban repetitive phrases such as “Add … to”, “Use smart offers”, “naturally”, and “Enjoy”. Mention “iCart” once and “Shopify merchants” once. End with a complete sentence. Use uniqueness_seed to diversify phrasing across keywords. Return ONLY JSON with keys: title, short_description.',
		'brand_tone' => $brand_tone,
		'keywords' => (string) $keywords,
		'specific_keyword' => (string) $keywords,
		'slug' => $slug,
		'uniqueness_seed' => $uniqueness_seed,
		'constraints' => array(
			'title_generated_min_words' => 8,
			'title_generated_max_words' => 12,
			'short_description_min_words' => 25,
			'short_description_max_words' => 30,
		),
	));
	$result = icart_dl_openai_chat(array(
		array('role' => 'system', 'content' => $system),
		array('role' => 'user', 'content' => 'Return JSON only. No prefixes, no markdown. Payload: ' . $user),
	), null, 500, 0.4);
	if (is_wp_error($result)) {
		return icart_dl_generate_title_short_local($keywords);
	}
	$txt = trim((string)$result);
	$decoded = json_decode($txt, true);
	if (!is_array($decoded)) {
		return icart_dl_generate_title_short_local($keywords);
	}
	$title = isset($decoded['title']) ? sanitize_text_field($decoded['title']) : '';
	$short = isset($decoded['short_description']) ? sanitize_text_field($decoded['short_description']) : '';
	if ($title === '' && $short === '') {
		return icart_dl_generate_title_short_local($keywords);
	}
	$title = $title !== '' ? icart_dl_trim_to_chars($title, 60) : icart_dl_generate_title_short_local($keywords)[0];
	$short = $short !== '' ? icart_dl_normalize_short_description($short, 25, 30) : icart_dl_generate_title_short_local($keywords)[1];
	return array($title, $short);
}

/**
 * Generate a short description from a title. Uses OpenAI when configured, else local fallback.
 */
function icart_dl_generate_short_from_title($title) {
	$settings = icart_dl_get_settings();
	$brand_tone = isset($settings['brand_tone']) ? $settings['brand_tone'] : '';
	if ($title === '') { return icart_dl_generate_25_word_description_from_title($title); }
	$system = 'You are a senior marketing copywriter. Use flawless American English with correct grammar and spelling. ' .
		'Write concise, benefit-led copy using ONLY the provided title for context. Do not repeat or restate the title. ' .
		'Output strict JSON ONLY with key: short_description.';
	$user = wp_json_encode(array(
		'instructions' => 'Using ONLY the provided title, write a short description between 22 and 25 words. Do not repeat the title. Return JSON only.',
		'title' => (string) $title,
		'brand_tone' => $brand_tone,
		'constraints' => array(
			'short_description_min_words' => 22,
			'short_description_max_words' => 25,
		),
	));
	$result = icart_dl_openai_chat(array(
		array('role' => 'system', 'content' => $system),
		array('role' => 'user', 'content' => 'Return JSON only. No prefixes, no markdown. Payload: ' . $user),
	), null, 250, 0.4);
	if (is_wp_error($result)) {
		return icart_dl_generate_25_word_description_from_title($title);
	}
	$txt = trim((string)$result);
	$decoded = json_decode($txt, true);
	if (!is_array($decoded)) { return icart_dl_generate_25_word_description_from_title($title); }
	$short = isset($decoded['short_description']) ? sanitize_text_field($decoded['short_description']) : '';
	return icart_dl_trim_to_chars($short !== '' ? $short : icart_dl_generate_25_word_description_from_title($title), 170);
}

function icart_dl_build_json_from_landing_map() {
	$opts = icart_dl_get_settings();
	$entries = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
	if (empty($entries)) {
		$entries = icart_dl_scan_sample_keywords();
	}
	$by_product = array();
	foreach ($entries as $row) {
		$slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
		$keywords = isset($row['keywords']) ? sanitize_text_field($row['keywords']) : '';
		$product_key = isset($row['product_key']) ? sanitize_title($row['product_key']) : 'default';
		if ($slug === '') { continue; }
		if (!isset($by_product[$product_key])) { $by_product[$product_key] = array(); }
		list($gen_title, $gen_short) = icart_dl_generate_title_short_local($keywords);
		$by_product[$product_key][$slug] = array(
			'slug' => $slug,
			'url' => trailingslashit(home_url('/' . $slug)),
			'keywords' => $keywords,
			'title' => isset($row['title']) && $row['title'] !== '' ? sanitize_text_field($row['title']) : $gen_title,
			'short_description' => isset($row['description']) && $row['description'] !== '' ? sanitize_text_field($row['description']) : $gen_short,
		);
	}
	foreach ($by_product as $product_key => $map) {
		icart_dl_write_content_map_for_product($product_key, $map);
	}
}

function icart_dl_build_json_for_product($product_key) {
	$product_key = sanitize_title($product_key);
	if ($product_key === '') { return; }
	$opts = icart_dl_get_settings();
	$entries = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
	if (empty($entries)) {
		$entries = icart_dl_scan_sample_keywords();
	}
	$map = array();
	foreach ($entries as $row) {
		if (!isset($row['product_key']) || sanitize_title($row['product_key']) !== $product_key) { continue; }
		$slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
		$keywords = isset($row['keywords']) ? sanitize_text_field($row['keywords']) : '';
		if ($slug === '') { continue; }
		list($gen_title, $gen_short) = icart_dl_generate_title_short_local($keywords);
		$map[$slug] = array(
			'slug' => $slug,
			'url' => trailingslashit(home_url('/' . $slug)),
			'keywords' => $keywords,
			'title' => isset($row['title']) && $row['title'] !== '' ? sanitize_text_field($row['title']) : $gen_title,
			'short_description' => isset($row['description']) && $row['description'] !== '' ? sanitize_text_field($row['description']) : $gen_short,
		);
	}
	icart_dl_write_content_map_for_product($product_key, $map);
}

// Build JSON for a given product key using OpenAI when configured (auto generation)
function icart_dl_build_json_for_product_auto($product_key) {
	$product_key = sanitize_title($product_key);
	if ($product_key === '') { return; }
	$opts = icart_dl_get_settings();
	$entries = isset($opts['landing_map']) && is_array($opts['landing_map']) ? $opts['landing_map'] : array();
	if (empty($entries)) {
		$entries = icart_dl_scan_sample_keywords();
	}
	$map = array();
	foreach ($entries as $row) {
		if (!isset($row['product_key']) || sanitize_title($row['product_key']) !== $product_key) { continue; }
		$slug = isset($row['slug']) ? sanitize_title($row['slug']) : '';
		$keywords = isset($row['keywords']) ? sanitize_text_field($row['keywords']) : '';
		if ($slug === '') { continue; }
		list($gen_title, $gen_short) = icart_dl_generate_title_short_openai($keywords);
		$map[$slug] = array(
			'slug' => $slug,
			'url' => trailingslashit(home_url('/' . $slug)),
			'keywords' => $keywords,
			'title' => isset($row['title']) && $row['title'] !== '' ? sanitize_text_field($row['title']) : $gen_title,
			'short_description' => isset($row['description']) && $row['description'] !== '' ? sanitize_text_field($row['description']) : $gen_short,
		);
	}
	icart_dl_write_content_map_for_product($product_key, $map);
}

function icart_dl_lookup_content_for_keywords($keywords) {
	$slug = sanitize_title($keywords);
	$entry = icart_dl_get_landing_entry();
	if ($entry && !empty($entry['slug'])) {
		$slug = sanitize_title($entry['slug']);
		$product_key = isset($entry['product_key']) ? sanitize_title($entry['product_key']) : '';
		if ($product_key !== '') {
			$map = icart_dl_load_content_map_for_product($product_key);
			if (isset($map[$slug]) && is_array($map[$slug])) {
				$row = $map[$slug];
				$title = isset($row['title']) ? sanitize_text_field($row['title']) : '';
				$short = isset($row['short_description']) ? sanitize_text_field($row['short_description']) : '';
				return array(
					'title' => $title,
					'short_description' => $short,
					'heading' => $title,
					'subheading' => '',
					'explanation' => $short,
					'cta' => '',
				);
			}
		}
	}
	// Fallback: attempt scan across all product content files
	$files = glob(icart_dl_content_json_dir() . '*.json');
	foreach ($files as $file) {
		$raw = file_get_contents($file);
		$data = json_decode($raw, true);
		if (isset($data[$slug])) {
			$row = $data[$slug];
			$title = isset($row['title']) ? sanitize_text_field($row['title']) : '';
			$short = isset($row['short_description']) ? sanitize_text_field($row['short_description']) : '';
			return array(
				'title' => $title,
				'short_description' => $short,
				'heading' => $title,
				'subheading' => '',
				'explanation' => $short,
				'cta' => '',
			);
		}
	}
	return null;
}

// Returns a cache salt that changes when the relevant per-product JSON file changes
function icart_dl_get_json_cache_salt_for_keywords($keywords) {
	$entry = icart_dl_get_landing_entry();
	$slug = $entry && !empty($entry['slug']) ? sanitize_title($entry['slug']) : sanitize_title($keywords);
	$product_key = $entry && !empty($entry['product_key']) ? sanitize_title($entry['product_key']) : '';
	if ($product_key !== '') {
		$path = icart_dl_content_json_path_for_product($product_key);
		$mtime = file_exists($path) ? filemtime($path) : 0;
		return md5($product_key . '|' . $slug . '|' . $mtime);
	}
	return 'nojson';
}

/**
 * Theme helper: is current request a Dynamic Landing page?
 */
function icart_dl_is_dynamic_landing() {
	return (bool) get_query_var('icart_slug');
}

/**
 * Theme helper: get current Dynamic Landing slug (or empty string)
 */
function icart_dl_get_current_slug() {
	$slug = get_query_var('icart_slug');
	return $slug ? sanitize_title($slug) : '';
}

/**
 * Theme helper: get current product key (icart, steller, tablepress...) if present
 */
function icart_dl_get_current_product_key() {
	$entry = icart_dl_get_landing_entry();
	return $entry && !empty($entry['product_key']) ? sanitize_title($entry['product_key']) : '';
}

?>

