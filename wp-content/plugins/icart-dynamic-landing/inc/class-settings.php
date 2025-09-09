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

		add_settings_section('icart_dl_brand', __('Branding & Behavior', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('brand_tone', __('Brand Tone', 'icart-dl'), array($this, 'field_brand_tone'), $this->option_key, 'icart_dl_brand');
		add_settings_field('figma_url', __('Figma Link', 'icart-dl'), array($this, 'field_figma'), $this->option_key, 'icart_dl_brand');
		add_settings_field('cache_ttl', __('Cache TTL (seconds)', 'icart-dl'), array($this, 'field_cache_ttl'), $this->option_key, 'icart_dl_brand');
		add_settings_field('base_path', __('Landing Base Path', 'icart-dl'), array($this, 'field_base_path'), $this->option_key, 'icart_dl_brand');

		add_settings_section('icart_dl_products', __('Products & Mapping', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('static_products', __('Static Products (one per line)', 'icart-dl'), array($this, 'field_static_products'), $this->option_key, 'icart_dl_products');
		add_settings_field('mapping_upload', __('Upload Keyword Mapping CSV', 'icart-dl'), array($this, 'field_mapping_upload'), $this->option_key, 'icart_dl_products');

		add_settings_field('landing_upload', __('Upload Landing Map CSV', 'icart-dl'), array($this, 'field_landing_upload'), $this->option_key, 'icart_dl_products');

		add_settings_section('icart_dl_routing', __('Routing', 'icart-dl'), '__return_false', $this->option_key);
		add_settings_field('landing_page_slug', __('Landing Page Slug', 'icart-dl'), array($this, 'field_landing_page_slug'), $this->option_key, 'icart_dl_routing');
	}

	public function sanitize_settings($input) {
		$output = icart_dl_get_settings();
		$output['perplexity_api_key'] = isset($input['perplexity_api_key']) ? sanitize_text_field($input['perplexity_api_key']) : '';
		$output['perplexity_model'] = isset($input['perplexity_model']) ? sanitize_text_field($input['perplexity_model']) : 'sonar-pro';
		$output['brand_tone'] = isset($input['brand_tone']) ? wp_kses_post($input['brand_tone']) : '';
		$output['figma_url'] = isset($input['figma_url']) ? esc_url_raw($input['figma_url']) : '';
		$output['cache_ttl'] = isset($input['cache_ttl']) ? max(60, intval($input['cache_ttl'])) : 3600;
		$output['static_products'] = isset($input['static_products']) ? wp_kses_post($input['static_products']) : '';
		$output['base_path'] = isset($input['base_path']) ? sanitize_title_with_dashes($input['base_path']) : 'solutions';
		$output['landing_page_slug'] = isset($input['landing_page_slug']) ? sanitize_title_with_dashes($input['landing_page_slug']) : 'dynamic-landing';

		// Handle CSV upload (product mapping)
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

		// Handle CSV upload (landing map)
		if (!empty($_FILES['icart_dl_landing_csv']['name'])) {
			check_admin_referer($this->option_key . '-options');
			$uploaded = wp_handle_upload($_FILES['icart_dl_landing_csv'], array('test_form' => false));
			if (!isset($uploaded['error'])) {
				$parsed = $this->parse_csv($uploaded['file']);
				if (is_array($parsed)) {
					$output['landing_map'] = $parsed;
					set_transient('icart_dl_flush_rewrite', 1, 60);
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
				// Expected headers (non-WooCommerce): keywords, product_titles, product_urls, product_images, product_prices
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

	public function field_base_path() {
		$opts = icart_dl_get_settings();
		?>
		<input type="text" name="<?php echo esc_attr($this->option_key); ?>[base_path]" value="<?php echo esc_attr($opts['base_path'] ?? 'solutions'); ?>" class="regular-text" />
		<p class="description">Base slug for pretty URLs, e.g., solutions/best-boost-average-order-value-shopify-2025. Save permalinks after changing.</p>
		<?php
	}

	public function field_static_products() {
		$opts = icart_dl_get_settings();
		?>
		<textarea name="<?php echo esc_attr($this->option_key); ?>[static_products]" rows="6" class="large-text" placeholder="Title|https://example.com|https://example.com/image.jpg|$19.00\n..."><?php echo esc_textarea($opts['static_products'] ?? ''); ?></textarea>
		<p class="description">One product per line: Title|URL|ImageURL|Price (Price optional).</p>
		<?php
	}

	public function field_mapping_upload() {
		?>
		<input type="file" name="icart_dl_mapping_csv" accept=".csv" />
		<p class="description">Upload CSV with columns: keywords, product_titles, product_urls, product_images, product_prices. Use | as item delimiter in each column.</p>
		<?php
	}

	public function field_landing_upload() {
		?>
		<input type="file" name="icart_dl_landing_csv" accept=".csv" />
		<p class="description">Upload Landing Map CSV with columns: slug, keywords, product_key, title, description. Each row becomes a root-level SEO URL.</p>
		<?php
	}

	public function field_landing_page_slug() {
		$opts = icart_dl_get_settings();
		?>
		<input type="text" name="<?php echo esc_attr($this->option_key); ?>[landing_page_slug]" value="<?php echo esc_attr($opts['landing_page_slug'] ?? 'dynamic-landing'); ?>" class="regular-text" />
		<p class="description">Create a page with this slug and add the shortcode [icart_dynamic_page]. All SEO URLs route here.</p>
		<?php
	}
}

?>

