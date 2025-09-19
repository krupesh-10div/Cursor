<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Settings {
	private $option_key = 'icart_dl_settings';

	public function __construct() {
		add_action('admin_menu', array($this, 'add_menu'));
		add_action('admin_init', array($this, 'register_settings'));
	}

	public function add_menu() {
		add_options_page(
			'iCart Dynamic Landing',
			'iCart Dynamic Landing',
			'manage_options',
			'icart-dl-settings',
			array($this, 'render_settings_page')
		);
	}

	public function register_settings() {
		register_setting($this->option_key, $this->option_key, array($this, 'sanitize_settings'));

		add_settings_section('icart_dl_api', __('OpenAI Settings', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('openai_api_key', __('API Key', 'icart-dl'), array($this, 'field_api_key'), $this->option_key, 'icart_dl_api');
		add_settings_field('openai_model', __('Model', 'icart-dl'), array($this, 'field_model'), $this->option_key, 'icart_dl_api');

		add_settings_section('icart_dl_brand', __('Branding & Behavior', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('brand_tone', __('Brand Tone', 'icart-dl'), array($this, 'field_brand_tone'), $this->option_key, 'icart_dl_brand');
		add_settings_field('cache_ttl', __('Cache TTL (seconds)', 'icart-dl'), array($this, 'field_cache_ttl'), $this->option_key, 'icart_dl_brand');

		add_settings_section('icart_dl_products', __('Keywords & Landing', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('keywords_file_upload', __('Upload Keywords CSV to sample/keywords/', 'icart-dl'), array($this, 'field_keywords_file_upload'), $this->option_key, 'icart_dl_products');

		// Routing: landing page slug no longer required (direct template routing)
	}

	public function sanitize_settings($input) {
		$output = icart_dl_get_settings();
		$output['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';
		$output['openai_model'] = isset($input['openai_model']) ? sanitize_text_field($input['openai_model']) : 'gpt-4o-mini';
		$output['brand_tone'] = isset($input['brand_tone']) ? wp_kses_post($input['brand_tone']) : '';
		$output['cache_ttl'] = isset($input['cache_ttl']) ? max(60, intval($input['cache_ttl'])) : 3600;
		// remove disable_api option (no API usage at runtime)
		// landing_page_slug removed

		// Upload keywords CSV into sample/keywords/
		if (!empty($_FILES['icart_dl_keywords_file']['name'])) {
			check_admin_referer($this->option_key . '-options');
			$uploaded = wp_handle_upload($_FILES['icart_dl_keywords_file'], array('test_form' => false));
			if (!isset($uploaded['error'])) {
				$dest_dir = DL_PLUGIN_DIR . 'sample/keywords/';
				if (!is_dir($dest_dir)) {
					wp_mkdir_p($dest_dir);
				}
				$filename = isset($input['keywords_filename']) ? sanitize_file_name($input['keywords_filename']) : '';
				if ($filename === '') {
					$filename = basename($uploaded['file']);
				}
				if (substr(strtolower($filename), -4) !== '.csv') {
					$filename .= '.csv';
				}
				$dest = $dest_dir . $filename;
				copy($uploaded['file'], $dest);
				// Derive product key from filename (without extension)
				$product_key = sanitize_title(pathinfo($filename, PATHINFO_FILENAME));
				// Trigger rescan to rebuild landing_map and refresh $output in memory
				dl_sync_landing_map_from_samples();
				$refreshed = icart_dl_get_settings();
				if (isset($refreshed['landing_map'])) {
					$output['landing_map'] = $refreshed['landing_map'];
				}
				// Optionally (re)build JSON only for this product, replacing existing file
				if (!empty($input['build_json_after_upload'])) {
					// Build in shutdown to avoid delaying the admin response if IO is slow
					$product_key_for_build = $product_key;
					add_action('shutdown', function() use ($product_key_for_build) {
						if (function_exists('icart_dl_build_json_for_product_auto')) {
							icart_dl_build_json_for_product_auto($product_key_for_build);
						} else {
							icart_dl_build_json_for_product($product_key_for_build);
						}
					});
				}
				set_transient('dl_flush_rewrite', 1, 60);
			}
		}

		// Landing map upload removed; landing map is built from sample keywords

		return $output;
	}


	public function render_settings_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('iCart Dynamic Landing', 'icart-dl'); ?></h1>
			<form method="post" action="options.php" enctype="multipart/form-data">
				<?php
				settings_fields($this->option_key);
				do_settings_sections($this->option_key);
				submit_button();
				?>
			</form>
			<p>
				<?php echo esc_html__('Shortcode:', 'icart-dl'); ?>
				<code>[icart_dynamic_page]</code>
			</p>
		</div>
		<?php
	}

	public function field_api_key() {
		$opts = icart_dl_get_settings();
		?>
		<input type="password" name="<?php echo esc_attr($this->option_key); ?>[openai_api_key]" value="<?php echo esc_attr($opts['openai_api_key'] ?? ''); ?>" class="regular-text" autocomplete="off" />
		<p class="description">Store your OpenAI API key securely here.</p>
		<?php
	}

	public function field_model() {
		$opts = icart_dl_get_settings();
		$model = $opts['openai_model'] ?? 'gpt-4o-mini';
		?>
		<select name="<?php echo esc_attr($this->option_key); ?>[openai_model]">
			<?php foreach (array('gpt-4o-mini','gpt-4o') as $m): ?>
				<option value="<?php echo esc_attr($m); ?>" <?php selected($model, $m); ?>><?php echo esc_html($m); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function field_brand_tone() {
		$opts = icart_dl_get_settings();
		?>
		<textarea name="<?php echo esc_attr($this->option_key); ?>[brand_tone]" rows="4" class="large-text"><?php echo esc_textarea($opts['brand_tone'] ?? ''); ?></textarea>
		<p class="description">Describe your brand voice. E.g., Clear, helpful, confident, conversion-focused.</p>
		<?php
	}


	public function field_cache_ttl() {
		$opts = icart_dl_get_settings();
		?>
		<input type="number" min="60" step="60" name="<?php echo esc_attr($this->option_key); ?>[cache_ttl]" value="<?php echo esc_attr($opts['cache_ttl'] ?? 3600); ?>" />
		<p class="description">How long to cache generated content (seconds). Minimum 60.</p>
		<?php
	}


	public function field_keywords_file_upload() {
		?>
		<input type="file" name="icart_dl_keywords_file" accept=".csv" />
		<p class="description">Upload a CSV with a single column header (e.g., "keyword"). It will be saved to sample/keywords/ and auto-scanned.</p>
		<br />
		<input type="text" name="<?php echo esc_attr($this->option_key); ?>[keywords_filename]" value="" class="regular-text" placeholder="Optional filename, e.g., icart.csv" />
		<p class="description">Provide a filename to save under sample/keywords/. Leave blank to keep original name.</p>
		<label>
			<input type="checkbox" name="<?php echo esc_attr($this->option_key); ?>[build_json_after_upload]" value="1" />
			<?php echo esc_html__('Generate JSON file after upload', 'icart-dl'); ?>
		</label>
		<?php
	}


	// field_landing_page_slug removed
}

?>
