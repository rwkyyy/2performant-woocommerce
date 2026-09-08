<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function twoo_register_csv_download_endpoint() {
	add_rewrite_rule( '^twoo-feed/?$', 'index.php?products_csv_download=1', 'top' );
}
add_action( 'init', 'twoo_register_csv_download_endpoint' );

function twoo_query_vars( $vars ) {
	$vars[] = 'products_csv_download';
	return $vars;
}
add_filter( 'query_vars', 'twoo_query_vars' );

function twoo_trigger_csv_download() {
	if ( (int) get_query_var( 'products_csv_download' ) !== 1 ) {
		return;
	}

	nocache_headers();
	twoo_generate_products_csv();
	exit;
}
add_action( 'template_redirect', 'twoo_trigger_csv_download' );

function twoo_generate_products_csv() {
	$args = array(
		'status' => 'publish',
		'limit'  => -1,
		'return' => 'objects',
	);

	$products = wc_get_products( $args );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=products.csv' );

	$output = fopen( 'php://output', 'w' );

	fputcsv(
		$output,
		array(
			'Title',
			'Description',
			'Sale price',
			'Category',
			'Product URL',
			'Images',
			'Product ID',
			'Availability',
			'Old price',
			'Brand',
			'GTIN',
			'Other data',
		)
	);

	foreach ( $products as $product ) {
		$sale_price = $product->get_price();

		if ( '' === (string) $sale_price ) {
			continue;
		}

		$old_price = $product->is_on_sale() ? $product->get_regular_price() : '';

		$image_ids = $product->get_gallery_image_ids();
		if ( $product->get_image_id() ) {
			array_unshift( $image_ids, $product->get_image_id() );
			$image_ids = array_unique( $image_ids );
		}

		$image_urls = array();
		foreach ( $image_ids as $image_id ) {
			$image_url = wp_get_attachment_url( $image_id );
			if ( $image_url ) {
				$image_urls[] = $image_url;
			}
		}

		$brands     = get_the_terms( $product->get_id(), 'product_brand' );
		$brand_name = ( ! empty( $brands ) && ! is_wp_error( $brands ) ) ? $brands[0]->name : get_bloginfo( 'name' );

		// GTIN comes from the SKU (stores the EAN sitewide); only emit it when it
		// fits 2Performant's 9–13 char GTIN validation, otherwise leave blank.
		$sku      = (string) $product->get_sku();
		$sku_len  = strlen( $sku );
		$gtin     = ( $sku_len > 8 && $sku_len < 14 ) ? $sku : '';

		$data = array(
			twoo_truncate( $product->get_name(), 255 ),
			wp_strip_all_tags( $product->get_description() ),
			$sale_price,
			twoo_build_category_tree( $product->get_id() ),
			get_permalink( $product->get_id() ),
			implode( ',', $image_urls ),
			$product->get_id(),
			$product->is_in_stock() ? '1' : '0',
			$old_price,
			$brand_name,
			$gtin,
			'',
		);

		fputcsv( $output, $data );
	}

	fclose( $output );
}

/**
 * Build the full "Parent > Child > …" category path for a product.
 *
 * Picks the deepest assigned product_cat term so the tree is as complete as
 * possible, then walks its ancestors top-down.
 */
function twoo_build_category_tree( $product_id ) {
	$categories = get_the_terms( $product_id, 'product_cat' );
	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return '';
	}

	$deepest       = null;
	$deepest_depth = -1;
	foreach ( $categories as $term ) {
		$depth = count( get_ancestors( $term->term_id, 'product_cat' ) );
		if ( $depth > $deepest_depth ) {
			$deepest_depth = $depth;
			$deepest       = $term;
		}
	}

	if ( ! $deepest ) {
		return '';
	}

	$names     = array();
	$ancestors = array_reverse( get_ancestors( $deepest->term_id, 'product_cat' ) );
	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'product_cat' );
		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			$names[] = $ancestor->name;
		}
	}
	$names[] = $deepest->name;

	return implode( ' > ', $names );
}

/**
 * Truncate a string to a max length, multibyte-safe when mbstring is available.
 */
function twoo_truncate( $text, $length ) {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $length );
	}
	return substr( $text, 0, $length );
}
