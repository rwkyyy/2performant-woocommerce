<?php
/**
 * Twoo Performant @ Uprise
 *
 * @package       twoo-performant-uprise
 * @author        Eduard V. Doloc
 * @license       gplv3-or-later
 *
 * @wordpress-plugin
 * Plugin Name:   2Performant for WooCommerce
 * Description:  Full integration with 2Performant for WooCommerce, supports 3rd party tracking, 1st party tracking, basic feed generation and hiding elements for network generated traffic!
 * Version:       1.0.3
 * Author:        Eduard V. Doloc
 * Author URI:    https://rwky.ro
 * Text Domain:   twoo-performant-uprise
 * Domain Path:   /languages
 * License:       GPLv3 or later
 * License URI:   https://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 4.2
 * WC tested up to: 8.8
 * Tags: 2performant woocommerce, 2performant, 2 performant
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//feed
include_once 'includes/twoo-iframe.php';
include_once 'includes/twoo-big-bear.php';
include_once 'includes/twoo-feed.php';
include_once 'includes/twoo-postmessage.php';
include_once 'includes/twoo-updater.php';
function twoo_add_settings_tab( $settings_tabs ) {
	$settings_tabs['twoo_performant_uprise'] = __( '2Performant', 'twoo-performant-uprise' );

	return $settings_tabs;
}

add_filter( 'woocommerce_settings_tabs_array', 'twoo_add_settings_tab', 50 );

function twoo_settings_tab() {
	woocommerce_admin_fields( get_twoo_performant_uprise_settings() );
}

add_action( 'woocommerce_settings_tabs_twoo_performant_uprise', 'twoo_settings_tab' );

function get_twoo_performant_uprise_settings() {
	$feed_url = home_url( '/twoo-feed/' );
	$settings = array(
		'section_title'       => array(
			'name' => __( '2Performant Settings', 'twoo-performant-uprise' ),
			'type' => 'title',
			'desc' => 'Here you can set all your essential settings and get your feed url! The feed url is <a href="' . $feed_url . '" target="_blank">' . $feed_url . '</a>.',
			'id'   => 'twoo_section_title'
		),
		'campaign_unique'     => array(
			'name' => __( 'Campaign Unique', 'twoo-performant-uprise' ),
			'type' => 'text',
			'desc' => 'You can find the values <a href="https://businessleague.2performant.com/advertiser/attribution/iframe_tracking#installCode" target="_blank">here</a>; It is something like campaign_unique=abc1234, please input the value after =',
			'id'   => 'twoo_campaign_unique'
		),
		'confirm'             => array(
			'name' => __( 'Confirm', 'twoo-performant-uprise' ),
			'type' => 'text',
			'desc' => 'You can find the values <a href="https://businessleague.2performant.com/advertiser/attribution/iframe_tracking#installCode" target="_blank">here</a>; It is something like conform=abc1234, please input the value after =',
			'id'   => 'twoo_confirm'
		),
		'big_bear'            => array(
			'name' => __( 'Big Bear Attribution', 'twoo-performant-uprise' ),
			'type' => 'text',
			'desc' => 'You can find the value <a href="https://businessleague.2performant.com/advertiser/attribution/big_bear_attribution#section_0" target="_blank">here</a>; It is usually right after attr-2p.com/THIS_IS_THE_ID/clc/1.js',
			'id'   => 'twoo_big_bear'
		),
		'css_classes_to_hide' => array(
			'name' => __( 'CSS Classes to Hide (optional)', 'twoo-performant-uprise' ),
			'type' => 'text',
			'desc' => __( 'Enter the CSS classes to hide elements for network traffic; CSS elements need to be separated by commas (e.g., .class-1, .class-2).', 'twoo-performant-uprise' ),
			'id'   => 'twoo_css_classes_to_hide'
		),
		'section_end'         => array(
			'type' => 'sectionend',
			'id'   => 'twoo_section_end'
		)
	);

	return apply_filters( 'twoo_settings', $settings );
}

function twoo_save_settings() {
	woocommerce_update_options( get_twoo_performant_uprise_settings() );
}

add_action( 'woocommerce_update_options_twoo_performant_uprise', 'twoo_save_settings' );


function twoo_activate_plugin() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'twoo_activate_plugin' );