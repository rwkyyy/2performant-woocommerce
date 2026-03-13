<?php

function twoo_add_iframe( $order_id ) {
	$order = wc_get_order( $order_id );
	$items = $order->get_items();


	$description = array();
	foreach ( $items as $item ) {
		$product       = $item->get_product();
		$product_name  = $product->get_name();
		$sku           = $product->get_sku();
		$description[] = "{$product_name} (SKU: {$sku})";
	}
	$description_string = implode( ', ', $description );
	$sale_value         = $order->get_total() - $order->get_total_tax() - $order->get_shipping_total();
	$transaction_id     = $order->get_id();

	// Echo the iframe with dynamic values
	echo "<iframe height='1' width='1' scrolling='no' marginheight='0' marginwidth='0' frameborder='0' src='https://event.2performant.com/events/salecheck?campaign_unique=" . get_option( 'tp_campaign_unique' ) . "&confirm=" . get_option( 'tp_confirm' ) . "&transaction_id={$transaction_id}&description=" . urlencode( $description_string ) . "&amount={$sale_value}'></iframe>";
}

add_action( 'woocommerce_thankyou', 'twoo_add_iframe' );
