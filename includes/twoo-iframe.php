<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function twoo_get_iframe_sale_value( WC_Order $order ) {
	$amount = 0.0;

	foreach ( $order->get_items( 'line_item' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}

		$quantity = max( 1, (int) $item->get_quantity() );

		// Primary value: WooCommerce line total excluding tax and including coupon distribution.
		$line_total_ex_tax = (float) $item->get_total();

		// Defensive fallback for unusual WooCommerce configurations.
		if ( $line_total_ex_tax <= 0 ) {
			$subtotal_ex_tax = (float) $item->get_subtotal() - (float) $item->get_subtotal_tax();
			if ( $subtotal_ex_tax > 0 ) {
				$line_total_ex_tax = $subtotal_ex_tax;
			}
		}

		$unit_value = round( (float) ( $line_total_ex_tax / $quantity ), 2 );
		$amount     += $unit_value * $quantity;
	}

	return round( (float) $amount, 2 );
}

function twoo_add_iframe( $order_id ) {
	$campaign_unique = trim( (string) get_option( 'twoo_campaign_unique' ) );
	$confirm         = trim( (string) get_option( 'twoo_confirm' ) );

	if ( '' === $campaign_unique || '' === $confirm ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$description = array();

	foreach ( $order->get_items( 'line_item' ) as $item ) {
		if ( ! $item instanceof WC_Order_Item_Product ) {
			continue;
		}

		$product = $item->get_product();
		$sku     = $product ? $product->get_sku() : '';
		$label   = $item->get_name();

		if ( '' !== $sku ) {
			$label .= ' (SKU: ' . $sku . ')';
		}

		$description[] = $label;
	}

	$params = array(
		'campaign_unique' => $campaign_unique,
		'confirm'         => $confirm,
		'transaction_id'  => (string) $order->get_id(),
		'description'     => implode( ', ', $description ),
		'amount'          => number_format( twoo_get_iframe_sale_value( $order ), 2, '.', '' ),
	);

	echo '<iframe height="1" width="1" scrolling="no" marginheight="0" marginwidth="0" frameborder="0" src="' . esc_url( 'https://event.2performant.com/events/salecheck?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) ) . '"></iframe>';
}

add_action( 'woocommerce_thankyou', 'twoo_add_iframe', 10 );
