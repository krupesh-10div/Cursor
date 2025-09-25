<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Settings {
	private $option_key = 'icart_dl_settings';

	public function __construct() {
		add_action('admin_menu', array($this, 'add_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
		add_action('wp_ajax_icart_dl_upload_keywords', array($this, 'ajax_upload_keywords'));
		add_action('wp_ajax_icart_dl_process_keywords', array($this, 'ajax_process_keywords'));
		add_action('wp_ajax_icart_dl_cancel_job', array($this, 'ajax_cancel_job'));
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

		// Routing: landing page slug no longer required (direct template routing)
	}

	public function sanitize_settings($input) {
		$output = icart_dl_get_settings();
		$output['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';
		$output['openai_model'] = isset($input['openai_model']) ? sanitize_text_field($input['openai_model']) : 'gpt-5';
		$output['brand_tone'] = isset($input['brand_tone']) ? wp_kses_post($input['brand_tone']) : '';
		$output['cache_ttl'] = isset($input['cache_ttl']) ? max(60, intval($input['cache_ttl'])) : 3600;
		// remove disable_api option (no API usage at runtime)
		// landing_page_slug removed


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
			<div id="icart-dl-ajax-upload">
				<h2><?php echo esc_html__('AJAX CSV Upload & Generate', 'icart-dl'); ?></h2>
				<input type="file" id="icart-dl-ajax-file" accept=".csv" />
				<input type="text" id="icart-dl-ajax-filename" class="regular-text" placeholder="<?php echo esc_attr__('Optional filename, e.g., icart.csv', 'icart-dl'); ?>" />
				<label style="display:block;margin-top:6px;">
					<input type="checkbox" id="icart-dl-ajax-build-json" value="1" checked />
					<?php echo esc_html__('Generate JSON file after upload', 'icart-dl'); ?>
				</label>
				<p>
					<button class="button button-primary" id="icart-dl-ajax-start"><?php echo esc_html__('Upload via AJAX & Generate', 'icart-dl'); ?></button>
				</p>
		<div id="icart-dl-progress" style="display:none;max-width:600px;position:relative;">
			<button type="button" id="icart-dl-cancel" title="Cancel" style="position:absolute;right:0;top:-6px;border:none;background:transparent;color:#666;font-size:18px;line-height:1;cursor:pointer;">×</button>
					<div id="icart-dl-progress-bar" style="height:16px;background:#e2e8f0;border-radius:8px;overflow:hidden;">
						<div id="icart-dl-progress-fill" style="height:100%;width:0;background:#2271b1;"></div>
					</div>
					<p id="icart-dl-progress-text" style="margin-top:6px;"></p>
				</div>
			</div>
		</div>
		<?php
	}

	public function enqueue_admin_assets($hook) {
		if ($hook !== 'settings_page_icart-dl-settings') { return; }
		wp_register_script('icart-dl-admin', DL_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DL_VERSION, true);
		wp_localize_script('icart-dl-admin', 'ICartDLAdmin', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('icart_dl_ajax'),
		));
		wp_enqueue_script('icart-dl-admin');
	}

	private function build_job_from_file($file_path, $product_key) {
		$rows = array();
		if (($handle = fopen($file_path, 'r')) !== false) {
			$line = 0;
			while (($data = fgetcsv($handle)) !== false) {
				$line++;
				if ($line === 1) { continue; }
				$kw = isset($data[0]) ? trim($data[0]) : '';
				if ($kw === '' || strtolower($kw) === 'keyword' || strtolower($kw) === 'keywords') { continue; }
				$rows[] = array(
					'slug' => sanitize_title($kw),
					'keywords' => $kw,
				);
			}
			fclose($handle);
		}
		$job_id = 'job_' . substr(md5(uniqid('', true) . '|' . get_current_user_id()), 0, 12);
		$job = array(
			'id' => $job_id,
			'product_key' => sanitize_title($product_key),
			'rows' => $rows,
			'total' => count($rows),
			'index' => 0,
			'created_at' => time(),
			'user_id' => get_current_user_id(),
		);
		set_transient('icart_dl_' . $job_id, $job, 2 * HOUR_IN_SECONDS);
		return $job;
	}

	public function ajax_upload_keywords() {
		check_ajax_referer('icart_dl_ajax', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Unauthorized'), 403);
		}
		if (empty($_FILES['file']['name'])) {
			wp_send_json_error(array('message' => 'No file provided'), 400);
		}
		$uploaded = wp_handle_upload($_FILES['file'], array('test_form' => false));
		if (isset($uploaded['error'])) {
			wp_send_json_error(array('message' => $uploaded['error']), 400);
		}
		$dest_dir = DL_PLUGIN_DIR . 'sample/keywords/';
		if (!is_dir($dest_dir)) { wp_mkdir_p($dest_dir); }
		$filename = isset($_POST['filename']) ? sanitize_file_name(wp_unslash($_POST['filename'])) : '';
		if ($filename === '') { $filename = basename($uploaded['file']); }
		if (substr(strtolower($filename), -4) !== '.csv') { $filename .= '.csv'; }
		$dest = $dest_dir . $filename;
		copy($uploaded['file'], $dest);
		$product_key = sanitize_title(pathinfo($filename, PATHINFO_FILENAME));
		// Make upload fast: defer global landing map sync to later processes
		if (function_exists('set_time_limit')) { @set_time_limit(15); }
		$job = $this->build_job_from_file($dest, $product_key);
		wp_send_json_success(array(
			'job_id' => $job['id'],
			'product_key' => $product_key,
			'total' => $job['total'],
		));
	}

	public function ajax_cancel_job() {
		check_ajax_referer('icart_dl_ajax', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Unauthorized'), 403);
		}
		$job_id = isset($_POST['job_id']) ? sanitize_text_field(wp_unslash($_POST['job_id'])) : '';
		if ($job_id === '') { wp_send_json_error(array('message' => 'Missing job_id'), 400); }
		delete_transient('icart_dl_' . $job_id);
		wp_send_json_success(array('message' => 'Job cancelled'));
	}

	public function ajax_process_keywords() {
		check_ajax_referer('icart_dl_ajax', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Unauthorized'), 403);
		}
		if (function_exists('ignore_user_abort')) { @ignore_user_abort(true); }
		if (function_exists('set_time_limit')) { @set_time_limit(20); }
		$job_id = isset($_POST['job_id']) ? sanitize_text_field(wp_unslash($_POST['job_id'])) : '';
		$batch = isset($_POST['batch']) ? intval($_POST['batch']) : 1;
		if ($batch < 1) { $batch = 1; }
		if ($batch > 2) { $batch = 2; }
		// Require OpenAI API key for generation
		$settings = icart_dl_get_settings();
		$api_key = isset($settings['openai_api_key']) ? trim($settings['openai_api_key']) : '';
		if ($api_key === '') {
			wp_send_json_error(array('message' => 'OpenAI API key is not configured in settings.'), 400);
		}
		$job = get_transient('icart_dl_' . $job_id);
		if (!$job) { wp_send_json_error(array('message' => 'Job not found or expired'), 404); }
		if (!isset($job['rows']) || !is_array($job['rows'])) { wp_send_json_error(array('message' => 'Invalid job'), 400); }
		$product_key = $job['product_key'];
		$map = icart_dl_load_content_map_for_product($product_key);
		$processed = 0;
		for ($i = 0; $i < $batch; $i++) {
			if ($job['index'] >= $job['total']) { break; }
			$row = $job['rows'][$job['index']];
			$slug = $row['slug'];
			$keywords = $row['keywords'];
			// Use ChatGPT API for generation per requirement
			list($title, $short) = icart_dl_generate_title_short_openai($keywords, array('slug' => $slug, 'product_key' => $product_key));
			$map[$slug] = array(
				'slug' => $slug,
				'url' => trailingslashit(home_url('/' . $slug)),
				'keywords' => $keywords,
				'title' => $title,
				'short_description' => $short,
			);
			$job['index']++;
			$processed++;
		}
		if ($processed > 0) {
			icart_dl_write_content_map_for_product($product_key, $map);
		}
		set_transient('icart_dl_' . $job_id, $job, 2 * HOUR_IN_SECONDS);
		$done = ($job['index'] >= $job['total']);
		$percent = $job['total'] > 0 ? floor(($job['index'] / $job['total']) * 100) : 100;
		wp_send_json_success(array(
			'processed' => $processed,
			'completed' => $job['index'],
			'total' => $job['total'],
			'percent' => $percent,
			'done' => $done,
		));
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
		$model = $opts['openai_model'] ?? 'gpt-5';
		?>
		<input type="text" name="<?php echo esc_attr($this->option_key); ?>[openai_model]" value="<?php echo esc_attr($model); ?>" class="regular-text" placeholder="gpt-5" />
		<p class="description">Enter your OpenAI model ID, e.g., gpt-5</p>
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



	// field_landing_page_slug removed
}

?>
