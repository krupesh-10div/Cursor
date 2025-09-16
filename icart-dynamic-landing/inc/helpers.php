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

?>

