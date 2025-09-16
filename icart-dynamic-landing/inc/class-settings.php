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

		add_settings_section('icart_dl_api', __('Perplexity Settings', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('perplexity_api_key', __('API Key', 'icart-dl'), array($this, 'field_api_key'), $this->option_key, 'icart_dl_api');
		add_settings_field('perplexity_model', __('Model', 'icart-dl'), array($this, 'field_model'), $this->option_key, 'icart_dl_api');
		add_settings_field('disable_api', __('Disable API Calls', 'icart-dl'), array($this, 'field_disable_api'), $this->option_key, 'icart_dl_api');

		add_settings_section('icart_dl_brand', __('Branding & Behavior', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('brand_tone', __('Brand Tone', 'icart-dl'), array($this, 'field_brand_tone'), $this->option_key, 'icart_dl_brand');
		add_settings_field('cache_ttl', __('Cache TTL (seconds)', 'icart-dl'), array($this, 'field_cache_ttl'), $this->option_key, 'icart_dl_brand');

		add_settings_section('icart_dl_products', __('Keywords & Landing', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('keywords_file_upload', __('Upload Keywords CSV to sample/keywords/', 'icart-dl'), array($this, 'field_keywords_file_upload'), $this->option_key, 'icart_dl_products');

		add_settings_section('icart_dl_content_json', __('Content JSON', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('build_content_json', __('Build JSON from CSV', 'icart-dl'), array($this, 'field_build_content_json'), $this->option_key, 'icart_dl_content_json');

		// Routing: landing page slug no longer required (direct template routing)
	}

	public function sanitize_settings($input) {
		$output = icart_dl_get_settings();
		$output['perplexity_api_key'] = isset($input['perplexity_api_key']) ? sanitize_text_field($input['perplexity_api_key']) : '';
		$output['perplexity_model'] = isset($input['perplexity_model']) ? sanitize_text_field($input['perplexity_model']) : 'sonar-pro';
		$output['brand_tone'] = isset($input['brand_tone']) ? wp_kses_post($input['brand_tone']) : '';
		$output['cache_ttl'] = isset($input['cache_ttl']) ? max(60, intval($input['cache_ttl'])) : 3600;
		$output['disable_api'] = !empty($input['disable_api']) ? 1 : 0;
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
				// Trigger rescan to rebuild landing_map
				dl_sync_landing_map_from_samples();
				// Build content JSON immediately for faster usage
				icart_dl_build_json_from_landing_map();
				set_transient('dl_flush_rewrite', 1, 60);
			}
		}

		// If user clicked the build JSON action, build from current landing map
		if (!empty($input['build_content_json'])) {
			icart_dl_build_json_from_landing_map();
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
		<input type="password" name="<?php echo esc_attr($this->option_key); ?>[perplexity_api_key]" value="<?php echo esc_attr($opts['perplexity_api_key'] ?? ''); ?>" class="regular-text" autocomplete="off" />
		<p class="description">Store your Perplexity API key securely here.</p>
		<?php
	}

	public function field_model() {
		$opts = icart_dl_get_settings();
		$model = $opts['perplexity_model'] ?? 'sonar-pro';
		?>
		<select name="<?php echo esc_attr($this->option_key); ?>[perplexity_model]">
			<?php foreach (array('sonar-pro','sonar-medium','sonar-small') as $m): ?>
				<option value="<?php echo esc_attr($m); ?>" <?php selected($model, $m); ?>><?php echo esc_html($m); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function field_disable_api() {
		$opts = icart_dl_get_settings();
		$checked = !empty($opts['disable_api']);
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr($this->option_key); ?>[disable_api]" value="1" <?php checked($checked, true); ?> />
			<?php echo esc_html__('Do not call Perplexity; use JSON/fallback only.', 'icart-dl'); ?>
		</label>
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
		<?php
	}

	public function field_build_content_json() {
		$path = icart_dl_content_json_path();
		$exists = file_exists($path);
		?>
		<button type="submit" class="button button-secondary" name="<?php echo esc_attr($this->option_key); ?>[build_content_json]" value="1">Build landing-content.json</button>
		<?php if ($exists): ?>
			<p class="description">Current file: <code><?php echo esc_html(basename($path)); ?></code>. Location: <code>sample/content/</code></p>
		<?php else: ?>
			<p class="description">This will create <code>sample/content/landing-content.json</code> with title, short_description and URL per slug.</p>
		<?php endif; ?>
		<?php
	}


	// field_landing_page_slug removed
}

?>
