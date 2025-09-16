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
	return mb_convert_case($text, MB_CASE_TITLE_SIMPLE, 'UTF-8');
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

function icart_dl_generate_title_short_from_keywords($keywords) {
	$k = wp_strip_all_tags($keywords);
	$title = icart_dl_titlecase($k);
	$title = icart_dl_trim_to_chars($title, 60);
	$short = icart_dl_trim_to_chars(sprintf('Summary of %s.', $title), 170);
	return array($title, $short);
}

/**
 * Generate title and short description via Perplexity API.
 * Returns array(title, short) or null on failure/misconfiguration.
 */
// Removed Perplexity API generation from helpers to avoid network calls during uploads or page loads

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
		list($gen_title, $gen_short) = icart_dl_generate_title_short_from_keywords($keywords);
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
		list($gen_title, $gen_short) = icart_dl_generate_title_short_from_keywords($keywords);
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

?>

