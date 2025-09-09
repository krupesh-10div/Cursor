<?php
if (!defined('ABSPATH')) {
	exit;
}

class ICartDL_Keyword_Mapper {
	private $mapping_rows;

	public function __construct() {
		$settings = icart_dl_get_settings();
		$this->mapping_rows = isset($settings['mapping']) && is_array($settings['mapping']) ? $settings['mapping'] : array();
	}

	private function tokenize($keywords) {
		$k = icart_dl_normalize_keywords($keywords);
		$parts = preg_split('/[\s,|]+/', $k);
		$parts = array_filter(array_map('trim', $parts));
		return array_unique($parts);
	}

	private function parse_products_from_row($row) {
		$titles = isset($row['product_titles']) ? $row['product_titles'] : '';
		$urls = isset($row['product_urls']) ? $row['product_urls'] : '';
		$images = isset($row['product_images']) ? $row['product_images'] : '';
		$prices = isset($row['product_prices']) ? $row['product_prices'] : '';

		$title_list = array_filter(array_map('trim', explode('|', $titles)));
		$url_list = array_filter(array_map('trim', explode('|', $urls)));
		$image_list = array_filter(array_map('trim', explode('|', $images)));
		$price_list = array_map('trim', explode('|', $prices));

		$max = max(count($title_list), count($url_list), count($image_list), count($price_list));
		$items = array();
		for ($i = 0; $i < $max; $i++) {
			$title = isset($title_list[$i]) ? $title_list[$i] : '';
			$url = isset($url_list[$i]) ? $url_list[$i] : '';
			$image = isset($image_list[$i]) ? $image_list[$i] : '';
			$price = isset($price_list[$i]) ? $price_list[$i] : '';
			if ($url === '') {
				continue;
			}
			$items[] = array(
				'title' => $title,
				'url' => $url,
				'image' => $image,
				'price' => $price,
			);
		}
		return $items;
	}

	public function match_products($keywords, $limit = 6) {
		$tokens = $this->tokenize($keywords);
		$products = array();
		foreach ($this->mapping_rows as $row) {
			$kw = isset($row['keywords']) ? strtolower($row['keywords']) : '';
			if ($kw === '') {
				continue;
			}
			$required = preg_split('/[\s,|]+/', $kw);
			$required = array_filter(array_map('trim', $required));
			if (empty($required)) {
				continue;
			}
			$all_present = true;
			foreach ($required as $r) {
				if (!in_array(strtolower($r), $tokens, true)) {
					$all_present = false;
					break;
				}
			}
			if (!$all_present) {
				continue;
			}
			$items = $this->parse_products_from_row($row);
			$products = array_merge($products, $items);
			if (count($products) >= $limit) {
				break;
			}
		}
		// Dedupe by URL
		$seen = array();
		$unique = array();
		foreach ($products as $item) {
			$key = md5($item['url']);
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$unique[] = $item;
			if (count($unique) >= $limit) {
				break;
			}
		}
		return $unique;
	}

	public function get_static_products() {
		$settings = icart_dl_get_settings();
		$static_raw = isset($settings['static_products']) ? $settings['static_products'] : '';
		$lines = preg_split('/\r?\n/', $static_raw);
		$items = array();
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '') { continue; }
			$parts = explode('|', $line);
			$title = isset($parts[0]) ? trim($parts[0]) : '';
			$url = isset($parts[1]) ? trim($parts[1]) : '';
			$image = isset($parts[2]) ? trim($parts[2]) : '';
			$price = isset($parts[3]) ? trim($parts[3]) : '';
			if ($url === '') { continue; }
			$items[] = array(
				'title' => $title,
				'url' => $url,
				'image' => $image,
				'price' => $price,
			);
		}
		return $items;
	}
}

?>

