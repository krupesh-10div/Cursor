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

	public function match_products($keywords) {
		$tokens = $this->tokenize($keywords);
		$matched = array(
			'product_ids' => array(),
			'product_skus' => array(),
			'product_tags' => array(),
			'product_cats' => array(),
		);

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
			// Check if all required tokens are present in tokens
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

			if (!empty($row['product_ids'])) {
				$ids = array_filter(array_map('absint', preg_split('/[\s,|]+/', $row['product_ids'])));
				$matched['product_ids'] = array_merge($matched['product_ids'], $ids);
			}
			if (!empty($row['product_skus'])) {
				$skus = array_filter(array_map('trim', preg_split('/[\s,|]+/', $row['product_skus'])));
				$matched['product_skus'] = array_merge($matched['product_skus'], $skus);
			}
			if (!empty($row['product_tags'])) {
				$tags = array_filter(array_map('trim', preg_split('/[\s,|]+/', $row['product_tags'])));
				$matched['product_tags'] = array_merge($matched['product_tags'], $tags);
			}
			if (!empty($row['product_cats'])) {
				$cats = array_filter(array_map('trim', preg_split('/[\s,|]+/', $row['product_cats'])));
				$matched['product_cats'] = array_merge($matched['product_cats'], $cats);
			}
		}

		// Deduplicate
		$matched['product_ids'] = array_values(array_unique($matched['product_ids']));
		$matched['product_skus'] = array_values(array_unique($matched['product_skus']));
		$matched['product_tags'] = array_values(array_unique($matched['product_tags']));
		$matched['product_cats'] = array_values(array_unique($matched['product_cats']));

		return $matched;
	}

	public function fetch_wc_products($match, $limit = 6) {
		if (!class_exists('WC_Product') || !function_exists('wc_get_products')) {
			return array();
		}

		$args = array(
			'limit' => $limit,
			'orderby' => 'date',
			'order' => 'DESC',
			'status' => 'publish',
		);

		$products = array();

		// Fetch by IDs first
		if (!empty($match['product_ids'])) {
			$args_by_id = $args;
			$args_by_id['include'] = $match['product_ids'];
			$products = wc_get_products($args_by_id);
		}

		// Expand by SKU
		if (count($products) < $limit && !empty($match['product_skus'])) {
			$skus = $match['product_skus'];
			$sku_products = array();
			foreach ($skus as $sku) {
				$id = wc_get_product_id_by_sku($sku);
				if ($id) {
					$p = wc_get_product($id);
					if ($p) {
						$sku_products[] = $p;
					}
				}
			}
			$products = array_merge($products, $sku_products);
		}

		// Expand by tags
		if (count($products) < $limit && !empty($match['product_tags'])) {
			$args_by_tag = $args;
			$args_by_tag['tag'] = $match['product_tags'];
			$tag_products = wc_get_products($args_by_tag);
			$products = array_merge($products, $tag_products);
		}

		// Expand by categories
		if (count($products) < $limit && !empty($match['product_cats'])) {
			$args_by_cat = $args;
			$args_by_cat['category'] = $match['product_cats'];
			$cat_products = wc_get_products($args_by_cat);
			$products = array_merge($products, $cat_products);
		}

		// Trim to limit and dedupe by ID
		$by_id = array();
		$unique = array();
		foreach ($products as $p) {
			$pid = $p->get_id();
			if (isset($by_id[$pid])) {
				continue;
			}
			$by_id[$pid] = true;
			$unique[] = $p;
			if (count($unique) >= $limit) {
				break;
			}
		}

		return $unique;
	}
}

?>

