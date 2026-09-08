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

// Empty when the taxonomy is not registered (e.g. product_brand without WooCommerce Brands).
function twoo_get_term_options( $taxonomy ) {
	$options = array();

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return $options;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) ) {
		return $options;
	}

	foreach ( $terms as $term ) {
		$options[ $term->term_id ] = $term->name;
	}

	return $options;
}

function twoo_feed_filter_enabled() {
	return 'yes' === get_option( 'twoo_feed_filter_enabled' );
}

function twoo_get_feed_filter_terms( $option ) {
	$value = get_option( $option, array() );

	if ( ! is_array( $value ) ) {
		$value = array();
	}

	return array_values( array_filter( array_map( 'absint', $value ) ) );
}

// Returns null for "no restriction" (all products), or an array of matching IDs
// (possibly empty) using OR across the selected categories, brands and tags.
function twoo_get_feed_filtered_product_ids() {
	if ( ! twoo_feed_filter_enabled() ) {
		return null;
	}

	$taxonomy_terms = array(
		'product_cat'   => twoo_get_feed_filter_terms( 'twoo_feed_filter_categories' ),
		'product_brand' => twoo_get_feed_filter_terms( 'twoo_feed_filter_brands' ),
		'product_tag'   => twoo_get_feed_filter_terms( 'twoo_feed_filter_tags' ),
	);

	$tax_query = array( 'relation' => 'OR' );

	foreach ( $taxonomy_terms as $taxonomy => $term_ids ) {
		if ( empty( $term_ids ) ) {
			continue;
		}

		$tax_query[] = array(
			'taxonomy'         => $taxonomy,
			'field'            => 'term_id',
			'terms'            => $term_ids,
			'include_children' => true,
		);
	}

	// Only the relation key present means nothing was selected.
	if ( 1 === count( $tax_query ) ) {
		return null;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => $tax_query,
		)
	);

	return array_map( 'intval', $ids );
}

function twoo_generate_products_csv() {
	$args = array(
		'status' => 'publish',
		'limit'  => -1,
		'return' => 'objects',
	);

	$filtered_ids = twoo_get_feed_filtered_product_ids();

	if ( is_array( $filtered_ids ) ) {
		if ( empty( $filtered_ids ) ) {
			$products = array();
		} else {
			$args['include'] = $filtered_ids;
			$products        = wc_get_products( $args );
		}
	} else {
		$products = wc_get_products( $args );
	}

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

// Picks the deepest assigned category, then walks its ancestors into a
// "Parent > Child > …" path so the tree is as complete as possible.
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

function twoo_truncate( $text, $length ) {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $length );
	}
	return substr( $text, 0, $length );
}
