<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Content_Generator {
	public static function generate($keywords) {
		$settings = icart_dl_get_settings();
		$cache_ttl = isset($settings['cache_ttl']) ? intval($settings['cache_ttl']) : 3600;
		// Include JSON file mtime salt in cache key so updates invalidate cache
		$json_salt = function_exists('icart_dl_get_json_cache_salt_for_keywords') ? icart_dl_get_json_cache_salt_for_keywords($keywords) : '';
		$transient_key = icart_dl_build_transient_key('content_' . $json_salt, $keywords);
		$cached = get_transient($transient_key);
		if ($cached) {
			if (function_exists('icart_dl_log')) { icart_dl_log('Cache hit for content: ' . $transient_key); }
			return $cached;
		}

		// Attempt to source from local JSON first for fastest results
		$json_content = icart_dl_lookup_content_for_keywords($keywords);
		if (is_array($json_content)) {
			if (function_exists('icart_dl_log')) { icart_dl_log('Using JSON content for keywords: ' . $keywords); }
			set_transient($transient_key, $json_content, $cache_ttl);
			return $json_content;
		}

		// No API calls: return fallback if not found in JSON
		$result = self::generate_with_openai($keywords);
		set_transient($transient_key, $result, $cache_ttl);
		return $result;
	}

	private static function fallback($keywords) {
		$k = esc_html($keywords);
		$title = $k ? sprintf('Ideas for "%s"', $k) : 'Ideas just for you';
		$short = 'Discover relevant insights tailored to your search. Concise, helpful guidance to help you decide quickly and confidently.';
		$title = self::trim_to_chars($title, 50);
		$short = self::trim_to_chars($short, 170);
		return array(
			// New fields
			'title' => $title,
			'short_description' => $short,
			// Backward-compatible fields
			'heading' => $title,
			'subheading' => '',
			'explanation' => $short,
			'cta' => '',
		);
	}

	private static function generate_with_openai($keywords) {
		$keywords = trim((string)$keywords);
		if ($keywords === '') {
			return self::fallback($keywords);
		}
		if (!function_exists('icart_dl_generate_title_short_openai')) {
			if (function_exists('icart_dl_log')) { icart_dl_log('OpenAI function missing; using local fallback'); }
			return self::fallback($keywords);
		}
		list($title, $short) = icart_dl_generate_title_short_openai($keywords, array());
		if (function_exists('icart_dl_log')) { icart_dl_log('Generated content via OpenAI for keywords: ' . $keywords); }
		$title = self::trim_to_chars($title, 50);
		$short = self::trim_to_chars($short, 170);
		return array(
			'title' => $title,
			'short_description' => $short,
			'heading' => $title,
			'subheading' => '',
			'explanation' => $short,
			'cta' => '',
		);
	}

	private static function trim_to_chars($text, $max) {
		$text = trim((string)$text);
		if ($text === '' || $max <= 0) { return ''; }
		if (mb_strlen($text) <= $max) { return $text; }
		$truncated = mb_substr($text, 0, $max);
		// avoid cutting last word: backtrack to last space if present
		$space = mb_strrpos($truncated, ' ');
		if ($space !== false && $space > ($max - 20)) { // only backtrack if near the end to avoid over-shortening
			$truncated = mb_substr($truncated, 0, $space);
		}
		return rtrim($truncated, "\s\.,;:!-—") . '…';
	}
}

?>
