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
		add_settings_field('api_key', __('API Key', 'icart-dl'), array($this, 'field_api_key'), $this->option_key, 'icart_dl_api');
		add_settings_field('model', __('Model', 'icart-dl'), array($this, 'field_model'), $this->option_key, 'icart_dl_api');

		add_settings_section('icart_dl_brand', __('Branding & Behavior', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('brand_tone', __('Brand Tone', 'icart-dl'), array($this, 'field_brand_tone'), $this->option_key, 'icart_dl_brand');
		add_settings_field('figma_url', __('Figma Link', 'icart-dl'), array($this, 'field_figma'), $this->option_key, 'icart_dl_brand');
		add_settings_field('cache_ttl', __('Cache TTL (seconds)', 'icart-dl'), array($this, 'field_cache_ttl'), $this->option_key, 'icart_dl_brand');

		add_settings_section('icart_dl_products', __('Products & Mapping', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('static_product_ids', __('Static Product IDs', 'icart-dl'), array($this, 'field_static_products'), $this->option_key, 'icart_dl_products');
		add_settings_field('mapping_upload', __('Upload Keyword Mapping CSV', 'icart-dl'), array($this, 'field_mapping_upload'), $this->option_key, 'icart_dl_products');
	}

	public function sanitize_settings($input) {
		$output = icart_dl_get_settings();
		$output['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
		$output['model'] = isset($input['model']) ? sanitize_text_field($input['model']) : 'gpt-4o-mini';
		$output['brand_tone'] = isset($input['brand_tone']) ? wp_kses_post($input['brand_tone']) : '';
		$output['figma_url'] = isset($input['figma_url']) ? esc_url_raw($input['figma_url']) : '';
		$output['cache_ttl'] = isset($input['cache_ttl']) ? max(60, intval($input['cache_ttl'])) : 3600;
		$output['static_product_ids'] = isset($input['static_product_ids']) ? sanitize_text_field($input['static_product_ids']) : '';

		// Handle CSV upload
		if (!empty($_FILES['icart_dl_mapping_csv']['name'])) {
			check_admin_referer($this->option_key . '-options');
			$uploaded = wp_handle_upload($_FILES['icart_dl_mapping_csv'], array('test_form' => false));
			if (!isset($uploaded['error'])) {
				$parsed = $this->parse_csv($uploaded['file']);
				if (is_array($parsed)) {
					$output['mapping'] = $parsed;
				}
			}
		}

		return $output;
	}

	private function parse_csv($filepath) {
		if (!file_exists($filepath)) {
			return array();
		}
		$rows = array();
		if (($handle = fopen($filepath, 'r')) !== false) {
			$headers = array();
			$line = 0;
			while (($data = fgetcsv($handle)) !== false) {
				$line++;
				if ($line === 1) {
					$headers = array_map('strtolower', $data);
					continue;
				}
				$item = array();
				foreach ($headers as $i => $header) {
					$item[$header] = isset($data[$i]) ? trim($data[$i]) : '';
				}
				// Expected headers: keywords, product_ids, product_skus, product_tags, product_cats
				if (!empty($item)) {
					$rows[] = $item;
				}
			}
			fclose($handle);
		}
		return $rows;
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
		<input type="password" name="<?php echo esc_attr($this->option_key); ?>[api_key]" value="<?php echo esc_attr($opts['api_key'] ?? ''); ?>" class="regular-text" autocomplete="off" />
		<p class="description">Store your OpenAI API key securely here.</p>
		<?php
	}

	public function field_model() {
		$opts = icart_dl_get_settings();
		$model = $opts['model'] ?? 'gpt-4o-mini';
		?>
		<select name="<?php echo esc_attr($this->option_key); ?>[model]">
			<?php foreach (array('gpt-4o-mini','gpt-4o','gpt-4.1-mini','gpt-4.1') as $m): ?>
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

	public function field_figma() {
		$opts = icart_dl_get_settings();
		?>
		<input type="url" name="<?php echo esc_attr($this->option_key); ?>[figma_url]" value="<?php echo esc_attr($opts['figma_url'] ?? ''); ?>" class="regular-text" />
		<p class="description">Reference design link (Figma).</p>
		<?php
	}

	public function field_cache_ttl() {
		$opts = icart_dl_get_settings();
		?>
		<input type="number" min="60" step="60" name="<?php echo esc_attr($this->option_key); ?>[cache_ttl]" value="<?php echo esc_attr($opts['cache_ttl'] ?? 3600); ?>" />
		<p class="description">How long to cache generated content (seconds). Minimum 60.</p>
		<?php
	}

	public function field_static_products() {
		$opts = icart_dl_get_settings();
		?>
		<input type="text" name="<?php echo esc_attr($this->option_key); ?>[static_product_ids]" value="<?php echo esc_attr($opts['static_product_ids'] ?? ''); ?>" class="regular-text" />
		<p class="description">Comma-separated WooCommerce product IDs to always show in the static section.</p>
		<?php
	}

	public function field_mapping_upload() {
		?>
		<input type="file" name="icart_dl_mapping_csv" accept=".csv" />
		<p class="description">Upload CSV with columns: keywords, product_ids, product_skus, product_tags, product_cats</p>
		<?php
	}
}

?>

