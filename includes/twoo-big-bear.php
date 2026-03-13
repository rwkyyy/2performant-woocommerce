<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//big bear
function twoo_add_big_bear_click_script() {
	if ( ! empty( get_option( 'twoo_big_bear' ) ) ) {
		echo "<script defer src='https://attr-2p.com/" . esc_attr( get_option( 'twoo_big_bear' ) ) . "/clc/1.js'></script>";
	}
}

add_action( 'wp_footer', 'twoo_add_big_bear_click_script' );


function twoo_robots_update( $output, $public ) {
	if ( ! empty( get_option( 'twoo_big_bear' ) ) ) {
		$marker = "@ 2Performant @";
		if ( strpos( $output, $marker ) === false ) {
			$text   = "\n# " . $marker . "\n";
			$text   .= "Disallow: /*2pau\n";
			$text   .= "Disallow: /*2ptt\n";
			$text   .= "Disallow: /*2ptu\n";
			$text   .= "Disallow: /*2prp\n";
			$text   .= "Disallow: /*2pdlst\n";
			$output .= $text;
		}
	}

	return $output;
}

add_filter( 'robots_txt', 'twoo_robots_update', 10, 2 );


function twoo_noindex_bigbear() {
	if ( ! empty( get_option( 'twoo_big_bear' ) ) &&
	     ( isset( $_GET['2pau'] ) ||
	       isset( $_GET['2ptt'] ) ||
	       isset( $_GET['2ptu'] ) ||
	       isset( $_GET['2prp'] ) ||
	       isset( $_GET['2pdlst'] ) ) ) {
		echo '<meta name="robots" content="noindex">';
	}
}

add_action( 'wp_head', 'twoo_noindex_bigbear' );

function tp_add_order_script_to_thank_you_page( $order_id ) {
	if ( ! empty( get_option( 'twoo_big_bear' ) ) ) {
		$order   = wc_get_order( $order_id );
		$jsItems = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			$product       = $item->get_product();
			$categories    = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
			$categories_js = "['" . implode( "', '", $categories ) . "']";

			$net_item_price_per_unit = $item->get_total() / $item->get_quantity();

			$brand = ! empty( get_option( 'twoo_big_bear_brand' ) ) ? $product->get_attribute( 'brand' ) : get_bloginfo( 'name' );


			$jsItems[] = "{quantity: '{$item->get_quantity()}', product_id: '{$product->get_id()}', value: '" . number_format( $net_item_price_per_unit, 2, '.', '' ) . "', name: '{$product->get_name()}', category: $categories_js, brand: '$brand'}";
		}

		$itemsString = implode( ", ", $jsItems );

		$timestamp = $order->get_date_created()->getTimestamp();

		$script = "<script>
var tpOrder = {
    id: '{$order->get_id()}',
    placed_at: $timestamp,
    currency_code: '" . get_woocommerce_currency() . "',
    items: [$itemsString]
};
</script>
<script defer src='https://attr-2p.com/" . esc_attr( get_option( 'twoo_big_bear' ) ) . "/sls/1.js'></script>";

		echo $script;
	}
}

add_action( 'woocommerce_thankyou', 'tp_add_order_script_to_thank_you_page' );
