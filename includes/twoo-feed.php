<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function twoo_register_csv_download_endpoint() {
	add_rewrite_rule( '^twoo-feed/?', 'index.php?products_csv_download=1', 'top' );
	flush_rewrite_rules();
}

function twoo_query_vars( $vars ) {
	$vars[] = 'products_csv_download';

	return $vars;
}

add_action( 'init', 'twoo_register_csv_download_endpoint' );
add_filter( 'query_vars', 'twoo_query_vars' );

function twoo_trigger_csv_download() {
	if ( get_query_var( 'products_csv_download' ) ) {
		twoo_generate_products_csv();
		exit;
	}
}

add_action( 'template_redirect', 'twoo_trigger_csv_download' );
function twoo_generate_products_csv() {
	$args     = array(
		'status' => 'publish',
		'limit'  => - 1,
		'return' => 'objects'
	);
	$products = wc_get_products( $args );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="products.csv"' );

	$output = fopen( 'php://output', 'w' );

	fputcsv( $output, array(
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
		'other data'
	) );

	foreach ( $products as $product ) {
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();

		if ( empty( $regular_price ) && empty( $sale_price ) ) {
			continue;
		}

		$price = $sale_price && $sale_price != $regular_price ? "$regular_price/$sale_price" : $regular_price;

		$category_names = array();
		$categories     = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$category_names[] = $category->name;
			}
		}
		$category    = ! empty( $category_names ) ? $category_names[0] : '';
		$subcategory = count( $category_names ) > 1 ? $category_names[1] : '';

		$images     = $product->get_gallery_image_ids();
		$image_urls = array();
		foreach ( $images as $image_id ) {
			$image_urls[] = wp_get_attachment_url( $image_id );
		}
		$image_urls_string = implode( ',', $image_urls );

		$brand = ! empty( $product->get_attribute( 'brand' ) ) ? $product->get_attribute( 'brand' ) : get_bloginfo( 'name' );

		//clean html
		$description = wp_strip_all_tags( $product->get_description() );

		$data = array(
			$product->get_name(),
			$description,
			'', // @todo: short description? maybe?
			$price,
			$category,
			$subcategory,
			get_permalink( $product->get_id() ),
			$image_urls_string,
			$product->get_id(),
			'0', // idk why, but ok
			$brand,
			$product->is_in_stock() ? '1' : '0',
			'' // idk why, but ok
		);


		fputcsv( $output, $data );
	}

	fclose( $output );
}
