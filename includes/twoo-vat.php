<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function twoo_vat_override_rate() {
	return (float) get_option( 'twoo_vat_override_rate', 0 );
}

// Active only when the store runs without WooCommerce taxes; with WC taxes on,
// tracking already strips tax natively and the override would double-strip.
function twoo_vat_override_active() {
	if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() ) {
		return false;
	}

	return twoo_vat_override_rate() > 0;
}

function twoo_strip_vat_override( $gross ) {
	$gross = (float) $gross;

	if ( ! twoo_vat_override_active() ) {
		return $gross;
	}

	$divisor = 1 + ( twoo_vat_override_rate() / 100 );
	if ( $divisor <= 0 ) {
		return $gross;
	}

	return $gross / $divisor;
}
