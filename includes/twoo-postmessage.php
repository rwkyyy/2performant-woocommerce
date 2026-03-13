<?php

function twoo_enqueue_hide_elements_script() {
	wp_enqueue_script( 'twoo_postmessage', 'https://event.2performant.com/javascripts/postmessage.js', array( 'jquery' ), null, true );


	$css_classes_to_hide = get_option( 'twoo_css_classes_to_hide', '' );
	$css_classes_to_hide = str_replace( ' ', '', $css_classes_to_hide );


	$inline_script = "
	                 <script type='text/javascript'>
	                              jQuery(document).ready(function($) {
		                              window.dp_network_url = 'event.2performant.com';
		                              window.dp_campaign_unique = '" . esc_js(get_option('twoo_campaign_unique', '')) . "';
        window.dp_cookie_result = function(data){
	        if(data && data.indexOf(':click:') !== -1) {
		        $('" . esc_js($css_classes_to_hide) . "').hide();
	        } else {
		        $('" . esc_js($css_classes_to_hide) . "').show();
	        }
        };
        xtd_receive_cookie(); 
    });
    </script>";



	echo $inline_script;
}

add_action( 'wp_footer', 'twoo_enqueue_hide_elements_script' );
