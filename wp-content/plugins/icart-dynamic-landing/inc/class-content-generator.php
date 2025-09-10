<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Content_Generator {
	public static function generate($keywords) {
		$settings = icart_dl_get_settings();
		$cache_ttl = isset($settings['cache_ttl']) ? intval($settings['cache_ttl']) : 3600;
		$transient_key = icart_dl_build_transient_key('perplexity', $keywords);
		$cached = get_transient($transient_key);
		if ($cached) {
			return $cached;
		}

		$api_key = $settings['perplexity_api_key'] ?? '';
		$model = $settings['perplexity_model'] ?? 'sonar-pro';
		$brand_tone = $settings['brand_tone'] ?? '';

		if (empty($api_key)) {
			$result = self::fallback($keywords);
			set_transient($transient_key, $result, $cache_ttl);
			return $result;
		}

		$system = 'You are a senior marketing copywriter writing high-converting landing page copy. ' .
			'Write in the brand voice provided. Keep it concise, benefit-led, and keyword-aware. ' .
			'Output strict JSON ONLY with keys: title, short_description.';

		$user = wp_json_encode(array(
			'instructions' => 'Generate a compelling H1 title and a short description for a landing page. Return JSON only.',
			'brand_tone' => $brand_tone,
			'keywords' => $keywords,
			'constraints' => array(
				'title_max_chars' => 50,
				'short_description_max_chars' => 170,
			),
		));

		$body = array(
			'model' => $model,
			'messages' => array(
				array('role' => 'system', 'content' => $system),
				array('role' => 'user', 'content' => 'Return JSON only. No prefixes, no markdown. Payload: ' . $user),
			),
			'temperature' => 0.7,
			'max_tokens' => 400,
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode($body),
			'timeout' => 20,
		);

		$response = wp_remote_post('https://api.perplexity.ai/chat/completions', $args);
		$result = self::fallback($keywords);
		if (!is_wp_error($response)) {
			$code = wp_remote_retrieve_response_code($response);
			$raw = wp_remote_retrieve_body($response);
			if ($code >= 200 && $code < 300 && $raw) {
				$data = json_decode($raw, true);
				if (isset($data['choices'][0]['message']['content'])) {
					$txt = trim($data['choices'][0]['message']['content']);
					$decoded = json_decode($txt, true);
					if (is_array($decoded)) {
						$title = sanitize_text_field($decoded['title'] ?? '');
						$short = sanitize_text_field($decoded['short_description'] ?? '');
						if ($title !== '' || $short !== '') {
							$result['title'] = $title !== '' ? $title : $result['title'];
							$result['short_description'] = $short !== '' ? $short : $result['short_description'];
							// Backward-compatible fields
							$result['heading'] = $result['title'];
							$result['subheading'] = '';
							$result['explanation'] = $result['short_description'];
							$result['cta'] = '';
						}
					}
				}
			}
		}

		// Special-case override when keyword contains "icart"
		if (stripos($keywords, 'icart') !== false) {
			$override_title = 'Boost AOV with iCart Drawer Cart';
			$override_desc = 'Increase average order value with targeted upsells, smart recommendations, and a beautiful slide cart that converts.';
			$result['title'] = $override_title;
			$result['short_description'] = $override_desc;
			$result['heading'] = $override_title;
			$result['subheading'] = '';
			$result['explanation'] = $override_desc;
			$result['cta'] = '';
		}

		set_transient($transient_key, $result, $cache_ttl);
		return $result;
	}

	private static function fallback($keywords) {
		$k = esc_html($keywords);
		$title = $k ? sprintf('Ideas for "%s"', $k) : 'Ideas just for you';
		$short = 'Discover relevant insights tailored to your search. Concise, helpful guidance to help you decide quickly and confidently.';
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
}

?>

