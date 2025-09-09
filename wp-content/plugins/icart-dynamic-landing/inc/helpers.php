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
	return ICART_DL_TRANSIENT_PREFIX . $prefix . '_' . $hash;
}

?>

