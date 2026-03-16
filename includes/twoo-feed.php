<?php

if (!defined('ABSPATH')) {
	exit;
}

function twoo_register_csv_download_endpoint()
{
	add_rewrite_rule('^twoo-feed/?$', 'index.php?products_csv_download=1', 'top');
}

add_action('init', 'twoo_register_csv_download_endpoint');

function twoo_query_vars($vars)
{
	$vars[] = 'products_csv_download';
	return $vars;
}

add_filter('query_vars', 'twoo_query_vars');

function twoo_trigger_csv_download()
{
	if ((int)get_query_var('products_csv_download') !== 1) {
		return;
	}

	nocache_headers();
	twoo_generate_products_csv();
	exit;
}

add_action('template_redirect', 'twoo_trigger_csv_download');

function twoo_generate_products_csv()
{
//	@todo: selector for taxonomy
//	$allowed_brand_ids = array( 13218, 13083, 13319 );

	$args = array(
		'status' => 'publish',
		'limit' => -1,
		'return' => 'objects',
//		'tax_query' => array(
//			array(
//				'taxonomy' => 'product_brand',
//				'field'    => 'term_id',
//				'terms'    => $allowed_brand_ids,
//				'operator' => 'IN',
//			),
//		),
	);

	$products = wc_get_products($args);

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=products.csv');

	$output = fopen('php://output', 'w');

	fputcsv(
		$output,
		array(
			'title',
			'description',
			'short message',
			'price',
			'category',
			'subcategory',
			'url',
			'image urls',
			'product id',
			'generate text link',
			'brand',
			'active',
			'other data',
		)
	);

	foreach ($products as $product) {
		$regular_price = $product->get_regular_price();
		$sale_price = $product->get_sale_price();

		if ('' === (string)$regular_price && '' === (string)$sale_price) {
			continue;
		}

		$price = ('' !== (string)$sale_price && $sale_price !== $regular_price) ? $regular_price . '/' . $sale_price : $regular_price;

		$category_names = array();
		$categories = get_the_terms($product->get_id(), 'product_cat');
		if (!empty($categories) && !is_wp_error($categories)) {
			foreach ($categories as $category) {
				$category_names[] = $category->name;
			}
		}

		$category = !empty($category_names) ? $category_names[0] : '';
		$subcategory = count($category_names) > 1 ? $category_names[1] : '';

		$image_ids = $product->get_gallery_image_ids();
		if ($product->get_image_id()) {
			array_unshift($image_ids, $product->get_image_id());
			$image_ids = array_unique($image_ids);
		}

		$image_urls = array();
		foreach ($image_ids as $image_id) {
			$image_url = wp_get_attachment_url($image_id);
			if ($image_url) {
				$image_urls[] = $image_url;
			}
		}

		$brands = get_the_terms($product->get_id(), 'product_brand');
		$brand_name = (!empty($brands) && !is_wp_error($brands)) ? $brands[0]->name : get_bloginfo('name');

		$data = array(
			$product->get_name(),
			wp_strip_all_tags($product->get_description()),
			'',
			$price,
			$category,
			$subcategory,
			get_permalink($product->get_id()),
			implode(',', $image_urls),
			$product->get_id(),
			'0',
			$brand_name,
			$product->is_in_stock() ? '1' : '0',
			'',
		);

		fputcsv($output, $data);
	}

	fclose($output);
}
