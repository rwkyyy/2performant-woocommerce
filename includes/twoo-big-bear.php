<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debugger
if ( ! defined( 'TWOO_BIG_BEAR_DEBUG' ) ) {
	define( 'TWOO_BIG_BEAR_DEBUG', false );
}

function twoo_get_net_item_unit_value( WC_Order_Item_Product $item, WC_Order $order ) {

	$quantity = max( 1, (int) $item->get_quantity() );

	if ( wc_prices_include_tax() ) {

		// Store prices include VAT → remove VAT
		$subtotal = (float) $item->get_subtotal();
		$tax      = (float) $item->get_subtotal_tax();

		$line_total_ex_vat = $subtotal - $tax;

	} else {

		// Store prices already exclude VAT
		$line_total_ex_vat = (float) $item->get_total();

	}

	$unit_value = $line_total_ex_vat / $quantity;

	return round( $unit_value, 2 );
}

function twoo_get_product_category_path( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array( 'Uncategorized' );
	}

	$deepest_term = null;
	$max_depth    = - 1;

	foreach ( $terms as $term ) {
		$ancestors = get_ancestors( $term->term_id, 'product_cat' );
		$depth     = count( $ancestors );
		if ( $depth > $max_depth ) {
			$max_depth    = $depth;
			$deepest_term = $term;
		}
	}

	if ( ! $deepest_term ) {
		return array_values( wp_list_pluck( $terms, 'name' ) );
	}

	$path_ids   = array_reverse( get_ancestors( $deepest_term->term_id, 'product_cat' ) );
	$path_ids[] = $deepest_term->term_id;

	$path = array();
	foreach ( $path_ids as $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$path[] = $term->name;
		}
	}

	return array_slice( $path, 0, 10 );
}

function twoo_add_big_bear_click_script() {
	$big_bear_id = trim( (string) get_option( 'twoo_big_bear' ) );
	if ( '' === $big_bear_id ) {
		return;
	}

	echo "<script async src='https://attr-2p.com/" . esc_attr( $big_bear_id ) . "/clc/1.js'></script>\n";
}

add_action( 'wp_head', 'twoo_add_big_bear_click_script', 1 );

function twoo_robots_update( $output, $public ) {
	if ( empty( get_option( 'twoo_big_bear' ) ) ) {
		return $output;
	}

	$marker = '@ 2Performant @';
	if ( false === strpos( $output, $marker ) ) {
		$output .= "\n# {$marker}\n";
		$output .= "Disallow: /*2pau\n";
		$output .= "Disallow: /*2ptt\n";
		$output .= "Disallow: /*2ptu\n";
		$output .= "Disallow: /*2prp\n";
		$output .= "Disallow: /*2pdlst\n";
	}

	return $output;
}

add_filter( 'robots_txt', 'twoo_robots_update', 10, 2 );

function twoo_noindex_bigbear() {
	if (
		empty( get_option( 'twoo_big_bear' ) ) ||
		( ! isset( $_GET['2pau'] ) && ! isset( $_GET['2ptt'] ) && ! isset( $_GET['2ptu'] ) && ! isset( $_GET['2prp'] ) && ! isset( $_GET['2pdlst'] ) )
	) {
		return;
	}

	echo "<meta name='robots' content='noindex'>\n";
}

add_action( 'wp_head', 'twoo_noindex_bigbear', 0 );

function twoo_render_tp_order_script( $order_id ) {
	$big_bear_id = trim( (string) get_option( 'twoo_big_bear' ) );
	if ( '' === $big_bear_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$items = array();

	foreach ( $order->get_items( 'line_item' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}

		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}

		$brand = get_bloginfo( 'name' );

		//native WC brand
		$brand_terms = get_the_terms( $product->get_id(), 'product_brand' );
		if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
			$brand = reset( $brand_terms )->name;
		}

		$items[] = array(
			'quantity'   => max( 1, (int) $item->get_quantity() ),
			'product_id' => (string) ( $product->get_sku() ? $product->get_sku() : $product->get_id() ),
			'value'      => twoo_get_net_item_unit_value( $item, $order ),
			'name'       => mb_substr( wp_strip_all_tags( $item->get_name() ), 0, 250 ),
			'category'   => twoo_get_product_category_path( $product->get_id() ),
			'brand'      => mb_substr( wp_strip_all_tags( $brand ), 0, 250 ),
		);
	}

	if ( empty( $items ) ) {
		return;
	}

	$tp_order = array(
		'id'            => (string) $order->get_id(),
		'placed_at'     => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time(),
		'currency_code' => (string) $order->get_currency(),
		'items'         => $items,
	);

	if ( TWOO_BIG_BEAR_DEBUG ) {
		$debug_items = array();
		foreach ( $order->get_items( 'line_item' ) as $debug_item ) {
			if ( ! $debug_item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$debug_items[] = array(
				'name'           => $debug_item->get_name(),
				'quantity'       => $debug_item->get_quantity(),
				'subtotal'       => $debug_item->get_subtotal(),
				'subtotal_tax'   => $debug_item->get_subtotal_tax(),
				'total'          => $debug_item->get_total(),
				'total_tax'      => $debug_item->get_total_tax(),
				'computed_value' => twoo_get_net_item_unit_value( $debug_item, $order ),
			);
		}

		$debug_payload = array(
			'order_id' => $order->get_id(),
			'items'    => $debug_items,
			'tpOrder'  => $tp_order,
		);

		echo "<script>window.tpOrder = " . wp_json_encode( $tp_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "; console.log('2Performant BigBear payload', " . wp_json_encode( $debug_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ");</script>\n";
	} else {
		echo "<script>window.tpOrder = " . wp_json_encode( $tp_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";</script>\n";
	}

	echo "<script defer src='https://attr-2p.com/" . esc_attr( $big_bear_id ) . "/sls/1.js'></script>\n";
}

add_action( 'woocommerce_thankyou', 'twoo_render_tp_order_script', 20 );
