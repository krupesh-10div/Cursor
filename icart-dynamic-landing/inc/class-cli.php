<?php
if (!defined('ABSPATH')) {
	exit;
}

if (defined('WP_CLI') && WP_CLI) {
	/**
	 * WP-CLI commands for iCart Dynamic Landing.
	 */
	class ICartDL_CLI_Command {
		/**
		 * Build sample/content/landing-content.json from current landing map (no API calls).
		 *
		 * ## EXAMPLES
		 *
		 * wp icart-dl build-json
		 */
		public function build_json($args, $assoc_args) {
			icart_dl_build_json_from_landing_map();
			\WP_CLI::success('Built per-product JSON files in sample/content/ from landing_map.');
		}

		/**
		 * Enrich JSON titles and descriptions using Perplexity API.
		 * Respects settings: model and brand_tone. Ignores Disable API toggle.
		 *
		 * ## OPTIONS
		 *
		 * [--force]
		 * : Overwrite existing non-empty title/description.
		 *
		 * ## EXAMPLES
		 *
		 * wp icart-dl enrich-json
		 * wp icart-dl enrich-json --force
		 */
		public function enrich_json($args, $assoc_args) {
			$opts = icart_dl_get_settings();
			$api_key = isset($opts['perplexity_api_key']) ? $opts['perplexity_api_key'] : '';
			$model = isset($opts['perplexity_model']) ? $opts['perplexity_model'] : 'sonar-pro';
			$brand_tone = isset($opts['brand_tone']) ? $opts['brand_tone'] : '';
			$force = !empty($assoc_args['force']);

			if (empty($api_key)) {
				\WP_CLI::error('Perplexity API key not set in settings.');
				return;
			}

			$map = icart_dl_load_content_map();
			if (empty($map)) {
				// Try building from landing map first
				icart_dl_build_json_from_landing_map();
				$map = icart_dl_load_content_map();
			}
			if (empty($map)) {
				\WP_CLI::warning('No entries found to enrich.');
				return;
			}

			$system = 'You are a senior marketing copywriter. Use flawless American English with correct grammar and spelling. ' .
				'Write concise, benefit-led, keyword-aware copy. Do not include brand names unless present in keywords. ' .
				'Output strict JSON ONLY with keys: title, short_description.';

			$updated = 0;
			foreach ($map as $slug => $row) {
				$kw = isset($row['keywords']) ? $row['keywords'] : $slug;
				$current_title = isset($row['title']) ? trim($row['title']) : '';
				$current_short = isset($row['short_description']) ? trim($row['short_description']) : '';
				if (!$force && $current_title !== '' && $current_short !== '') {
					continue;
				}

				$user = wp_json_encode(array(
					'instructions' => 'Generate a compelling H1 title exactly 8 words long, and a short description between 22 and 25 words. Return only JSON.',
					'brand_tone' => $brand_tone,
					'keywords' => $kw,
					'constraints' => array(
						'title_min_words' => 8,
						'title_max_words' => 8,
						'short_description_min_words' => 22,
						'short_description_max_words' => 25,
					),
				));

				$body = array(
					'model' => $model,
					'messages' => array(
						array('role' => 'system', 'content' => $system),
						array('role' => 'user', 'content' => 'Return JSON only. No prefixes, no markdown. Payload: ' . $user),
					),
					'temperature' => 0.4,
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
				if (is_wp_error($response)) {
					\WP_CLI::warning("Failed for {$slug}: " . $response->get_error_message());
					continue;
				}
				$code = wp_remote_retrieve_response_code($response);
				$raw = wp_remote_retrieve_body($response);
				if ($code < 200 || $code >= 300 || empty($raw)) {
					\WP_CLI::warning("Bad response for {$slug}: HTTP {$code}");
					continue;
				}
				$data = json_decode($raw, true);
				if (!isset($data['choices'][0]['message']['content'])) {
					\WP_CLI::warning("Malformed response for {$slug}");
					continue;
				}
				$txt = trim($data['choices'][0]['message']['content']);
				$decoded = json_decode($txt, true);
				if (!is_array($decoded)) {
					\WP_CLI::warning("Non-JSON content for {$slug}");
					continue;
				}
				$title = sanitize_text_field(isset($decoded['title']) ? $decoded['title'] : '');
				$short = sanitize_text_field(isset($decoded['short_description']) ? $decoded['short_description'] : '');
				if ($title !== '' || $short !== '') {
					$map[$slug]['title'] = $title !== '' ? $title : $current_title;
					$map[$slug]['short_description'] = $short !== '' ? $short : $current_short;
					$updated++;
					\WP_CLI::log("Updated {$slug}");
				}
			}

			if ($updated > 0) {
				icart_dl_write_content_map($map);
				\WP_CLI::success("Enriched {$updated} entries and wrote JSON.");
			} else {
				\WP_CLI::log('No entries updated.');
			}
		}
	}

	\WP_CLI::add_command('icart-dl', 'ICartDL_CLI_Command');
}

?>

