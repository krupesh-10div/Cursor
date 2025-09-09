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
			'Output strict JSON with keys: heading, subheading, explanation, cta.';

		$user = wp_json_encode(array(
			'instructions' => 'Create keyword-driven copy for a dynamic landing section. Return JSON only.',
			'brand_tone' => $brand_tone,
			'keywords' => $keywords,
			'constraints' => array(
				'heading_max_chars' => 70,
				'subheading_max_chars' => 120,
				'explanation_max_words' => 80,
				'cta_max_chars' => 40,
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
						$result = array(
							'heading' => sanitize_text_field($decoded['heading'] ?? $result['heading']),
							'subheading' => sanitize_text_field($decoded['subheading'] ?? $result['subheading']),
							'explanation' => wp_kses_post($decoded['explanation'] ?? $result['explanation']),
							'cta' => sanitize_text_field($decoded['cta'] ?? $result['cta']),
						);
					}
				}
			}
		}

		set_transient($transient_key, $result, $cache_ttl);
		return $result;
	}

	private static function fallback($keywords) {
		$k = esc_html($keywords);
		return array(
			'heading' => $k ? sprintf('Top Picks for "%s"', $k) : 'Top Picks Tailored for You',
			'subheading' => $k ? sprintf('Curated recommendations for %s', $k) : 'Curated recommendations based on your interests',
			'explanation' => 'Discover relevant products and insights. We personalize this page based on your search keywords to help you act faster with confidence.',
			'cta' => 'Shop Recommended Picks',
		);
	}
}

?>

