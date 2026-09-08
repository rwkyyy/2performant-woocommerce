<?php
/**
 * Twoo Performant @ Uprise
 *
 * @package       twoo-performant-uprise
 * @author        Eduard V. Doloc
 * @license       gplv3-or-later
 *
 * @wordpress-plugin
 * Plugin Name:   2Performant / Business League for WooCommerce
 * Description:   Full integration with 2Performant /Business League for WooCommerce, supports 3rd party tracking (iframe), 1st party tracking (big bear), basic feed generation and hiding elements for network generated traffic!
 * Version:       2026.9.8
 * Author:        Eduard V. Doloc
 * Author URI:    https://rwky.ro
 * Text Domain:   twoo-performant-uprise
 * Domain Path:   /languages
 * License:       GPLv3 or later
 * License URI:   https://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 4.2
 * WC tested up to: 10.6
 */

if (!defined('ABSPATH')) {
	exit;
}


include_once 'includes/twoo-vat.php';
include_once 'includes/twoo-iframe.php';
include_once 'includes/twoo-big-bear.php';
include_once 'includes/twoo-feed.php';
include_once 'includes/twoo-postmessage.php';
include_once 'includes/twoo-updater.php';

function twoo_add_settings_tab($settings_tabs)
{
	$settings_tabs['twoo_performant_uprise'] = __('2Performant', 'twoo-performant-uprise');

	return $settings_tabs;
}

add_filter('woocommerce_settings_tabs_array', 'twoo_add_settings_tab', 50);

function twoo_settings_tab()
{
	woocommerce_admin_fields(get_twoo_performant_uprise_settings());
}

add_action('woocommerce_settings_tabs_twoo_performant_uprise', 'twoo_settings_tab');

function get_twoo_performant_uprise_settings()
{
	$feed_url = home_url('/twoo-feed/');

	$settings = array(
		'section_title' => array(
			'name' => __('2Performant Settings', 'twoo-performant-uprise'),
			'type' => 'title',
			'desc' => 'Here you can set all your essential settings and get your feed url! The feed url is <a href="' . esc_url($feed_url) . '" target="_blank">' . esc_html($feed_url) . '</a>.',
			'id' => 'twoo_section_title',
		),
		'campaign_unique' => array(
			'name' => __('Campaign Unique', 'twoo-performant-uprise'),
			'type' => 'text',
			'desc' => 'You can find the values <a href="https://businessleague.2performant.com/advertiser/attribution/iframe_tracking#installCode" target="_blank">here</a>; input only the value after campaign_unique=',
			'id' => 'twoo_campaign_unique',
		),
		'confirm' => array(
			'name' => __('Confirm', 'twoo-performant-uprise'),
			'type' => 'text',
			'desc' => 'You can find the values <a href="https://businessleague.2performant.com/advertiser/attribution/iframe_tracking#installCode" target="_blank">here</a>; input only the value after confirm=',
			'id' => 'twoo_confirm',
		),
		'big_bear' => array(
			'name' => __('Big Bear Attribution', 'twoo-performant-uprise'),
			'type' => 'text',
			'desc' => 'You can find the value <a href="https://businessleague.2performant.com/advertiser/attribution/big_bear_attribution#section_0" target="_blank">here</a>; usually it is the segment after attr-2p.com/THIS_ID/clc/1.js',
			'id' => 'twoo_big_bear',
		),
		'css_classes_to_hide' => array(
			'name' => __('CSS Classes to Hide (optional)', 'twoo-performant-uprise'),
			'type' => 'text',
			'desc' => __('Enter CSS selectors separated by commas, for example: .class-1,.class-2', 'twoo-performant-uprise'),
			'id' => 'twoo_css_classes_to_hide',
		),
		'section_end' => array(
			'type' => 'sectionend',
			'id' => 'twoo_section_end',
		),

		'feed_filter_title' => array(
			'name' => __('Feed Product Filtering', 'twoo-performant-uprise'),
			'type' => 'title',
			'desc' => __('Restrict which products appear in the feed. Leave this disabled (or every list empty) to include all published products. When enabled, a product is included if it matches ANY selected category, brand, or tag.', 'twoo-performant-uprise'),
			'id' => 'twoo_feed_filter_title',
		),
		'feed_filter_enabled' => array(
			'name' => __('Enable feed filtering', 'twoo-performant-uprise'),
			'type' => 'checkbox',
			'desc' => __('When unchecked, the feed includes every published product (default behaviour).', 'twoo-performant-uprise'),
			'id' => 'twoo_feed_filter_enabled',
			'default' => 'no',
		),
		'feed_filter_categories' => array(
			'name' => __('Include categories', 'twoo-performant-uprise'),
			'type' => 'multiselect',
			'class' => 'wc-enhanced-select',
			'css' => 'min-width:350px;',
			'options' => twoo_get_term_options('product_cat'),
			'desc_tip' => __('Products in any selected category (and its sub-categories) are included.', 'twoo-performant-uprise'),
			'id' => 'twoo_feed_filter_categories',
			'default' => array(),
		),
		'feed_filter_brands' => array(
			'name' => __('Include brands', 'twoo-performant-uprise'),
			'type' => 'multiselect',
			'class' => 'wc-enhanced-select',
			'css' => 'min-width:350px;',
			'options' => twoo_get_term_options('product_brand'),
			'desc_tip' => __('Products assigned to any selected brand are included.', 'twoo-performant-uprise'),
			'id' => 'twoo_feed_filter_brands',
			'default' => array(),
		),
		'feed_filter_tags' => array(
			'name' => __('Include tags', 'twoo-performant-uprise'),
			'type' => 'multiselect',
			'class' => 'wc-enhanced-select',
			'css' => 'min-width:350px;',
			'options' => twoo_get_term_options('product_tag'),
			'desc_tip' => __('Products with any selected tag are included.', 'twoo-performant-uprise'),
			'id' => 'twoo_feed_filter_tags',
			'default' => array(),
		),
		'feed_filter_end' => array(
			'type' => 'sectionend',
			'id' => 'twoo_feed_filter_end',
		),

		'vat_title' => array(
			'name' => __('VAT Override', 'twoo-performant-uprise'),
			'type' => 'title',
			'desc' => __('Only used when WooCommerce taxes are disabled. If your store runs without WooCommerce tax settings, prices still include VAT that WooCommerce cannot strip. Set your VAT rate here and it will be removed from the net sale values reported to 2Performant (iframe + Big Bear). Leave at 0 to disable. Automatically ignored whenever WooCommerce taxes are enabled.', 'twoo-performant-uprise'),
			'id' => 'twoo_vat_title',
		),
		'vat_override_rate' => array(
			'name' => __('VAT rate (%)', 'twoo-performant-uprise'),
			'type' => 'number',
			'desc' => __('For example 19 for 19%. Set to 0 to disable.', 'twoo-performant-uprise'),
			'id' => 'twoo_vat_override_rate',
			'default' => '0',
			'custom_attributes' => array(
				'min' => '0',
				'max' => '100',
				'step' => '0.01',
			),
		),
		'vat_end' => array(
			'type' => 'sectionend',
			'id' => 'twoo_vat_end',
		),
	);

	return apply_filters('twoo_settings', $settings);
}

function twoo_save_settings()
{
	woocommerce_update_options(get_twoo_performant_uprise_settings());
}

add_action('woocommerce_update_options_twoo_performant_uprise', 'twoo_save_settings');

function twoo_activate_plugin()
{
	twoo_register_csv_download_endpoint();
	flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'twoo_activate_plugin');

function twoo_deactivate_plugin()
{
	flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'twoo_deactivate_plugin');

add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);
