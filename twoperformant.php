<?php

add_action( 'plugins_loaded', 'twoperformant_load_textdomain' );
function twoperformant_load_textdomain(){
	load_plugin_textdomain(
		'twoperforman',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}

add_action( 'plugins_loaded', 'twoperformant_integration' );
function twoperformant_integration(){

	if ( class_exists( 'WC_Integration' ) ) {
		// Include our integration class.
		include_once 'includes/class-wc-integration-twoperformant.php';
		// Register the integration.
		add_filter( 'woocommerce_integrations', 'twoperformant_add_integration' );
	}

	add_action( 'woocommerce_thankyou', 'twoperformant_add_code' );
	add_action( 'woocommerce_receipt_euplatesc', 'twoperformant_add_code' );

}

/**
 * Add a new integration to WooCommerce.
 */
function twoperformant_add_integration( $integrations ) {
	$integrations[] = 'WC_Integration_Twoperformant';
	return $integrations;
}

function twoperformant_add_code( $order_id ){

	$options = get_option( 'twoperformant' );
	$options = wp_parse_args( $options, array( 'campaign' => '', 'confirm' => '' ) );

	$order = wc_get_order( $order_id );
	$total = number_format( (float) $order->get_total() - $order->get_total_tax() - $order->get_total_shipping() - $order->get_shipping_tax(), wc_get_price_decimals(), '.', '' );
	$products = '';
	$total = 0;

	$order_items = $order->get_items();
	 foreach ( $order_items as $item_id => $item_data ) {

	 	$product = $item_data->get_product();
   	 	$product_name = $product->get_name();

   	 	$item_quantity = $item_data->get_quantity();

	 	if ( '' == $products ) {
	 		$products .= $product_name . 'x' . $item_quantity;
	 	}else{
	 		$products .= ' / ' . $product_name . 'x' . $item_quantity;
	 	}

	 	$item_total = floatval( number_format( $item_data->get_total(), 2 ) );
	 	$total += $item_total;
	           
	}

	$twoperf_url = "<iframe height='1' width='1' scrolling='no' marginheight='0' marginwidth='0' frameborder='0' src='https://event.2performant.com/events/salecheck?amount=%s&campaign_unique=" . esc_attr( $options['campaign'] ) . "&confirm=" . esc_attr( $options['confirm'] ) . "&description=%s&transaction_id=%s'></iframe>";

	printf( $twoperf_url, $total, $products, $order_id );

}

add_action('wp_enqueue_scripts', 'twoperformant_hide_phone');
function twoperformant_hide_phone(){

	$options = get_option( 'twoperformant' );
	$options = wp_parse_args( $options, array( 'selector' => '', 'campaign' => '' ) );

	if ( empty( $options['campaign'] ) ) {
		return;
	}

	if ( empty( $options['selector'] ) ) {
		return;
	}

	$script = "window.dp_network_url='event.2performant.com';window.dp_campaign_unique='" . esc_attr( $options['campaign'] ) . "';window.dp_cookie_result=function(data){if(data && data.indexOf(':click:')) {jQuery('" . esc_attr( $options['selector'] ) . "').hide();}else{jQuery('" . esc_attr( $options['selector'] ) . "').show();}};xtd_receive_cookie();";

	wp_enqueue_script( 'twoperformant_hide_phone', 'https://event.2performant.com/javascripts/postmessage.js', array( 'jquery' ), '1.0.0', true );
	wp_add_inline_script( 'twoperformant_hide_phone', $script );

}