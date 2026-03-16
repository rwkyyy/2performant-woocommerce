<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function twoo_enqueue_hide_elements_script() {
	$campaign_unique     = trim( (string) get_option( 'twoo_campaign_unique', '' ) );
	$css_classes_to_hide = trim( (string) get_option( 'twoo_css_classes_to_hide', '' ) );

	if ( '' === $campaign_unique || '' === $css_classes_to_hide ) {
		return;
	}

	wp_enqueue_script( 'twoo-postmessage', 'https://event.2performant.com/javascripts/postmessage.js', array( 'jquery' ), null, true );

	$selectors = array_filter( array_map( 'trim', explode( ',', $css_classes_to_hide ) ) );
	if ( empty( $selectors ) ) {
		return;
	}

	$inline_js = "
window.dp_network_url = 'event.2performant.com';
window.dp_campaign_unique = " . wp_json_encode( $campaign_unique ) . ";
window.dp_cookie_result = function(data) {
    var selectors = " . wp_json_encode( implode( ',', $selectors ) ) . ";
    if (!selectors) {
        return;
    }

    if (data && data.indexOf(':click:') !== -1) {
        jQuery(selectors).hide();
    } else {
        jQuery(selectors).show();
    }
};
if (typeof xtd_receive_cookie === 'function') {
    xtd_receive_cookie();
}
";

	wp_add_inline_script( 'twoo-postmessage', $inline_js, 'after' );
}

add_action( 'wp_enqueue_scripts', 'twoo_enqueue_hide_elements_script' );
