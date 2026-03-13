<?php
/**
 * 2Performant Integration
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * WC Integration 2performant
 *
 * @since 1.0.0
 */
class WC_Integration_Twoperformant extends WC_Integration {

	/**
	 * Initialize the integration.
	 */
	public function __construct() {
		$this->id                 = 'twoperformant';
		$this->method_title       = __( '2Performant', 'woocommerce' );
		$this->method_description = __( 'An integration for 2Performant affiliate system.', 'woocommerce' );

		$this->init_form_fields();
		$this->init_settings();

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

		// Filters.
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

	}

	/**
	 * Initializes the settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'campaign' => array(
				'title'             => __( 'Campaign unique', 'woocommerce-integration-demo' ),
				'type'              => 'text',
				'description'       => __( 'You will get this from 2Performant email.', 'woocommerce-integration-demo' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'confirm' => array(
				'title'             => __( 'Confirm', 'woocommerce-integration-demo' ),
				'type'              => 'text',
				'description'       => __( 'You will get this from 2Performant email.', 'woocommerce-integration-demo' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'selector' => array(
				'title'             => __( 'Phone selector', 'woocommerce-integration-demo' ),
				'type'              => 'text',
				'description'       => __( 'This will hide phone when a user will come from an affilate link', 'woocommerce-integration-demo' ),
				'desc_tip'          => true,
				'default'           => ''
			),
		);
	}

	public function get_option_key() {
		return 'twoperformant';
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'no' );
	}

	/**
	 * Santize our settings
	 * @see process_admin_options()
	 */
	public function sanitize_settings( $settings ) {
		// We're just going to make the api key all upper case characters since that's how our imaginary API works
		if ( isset( $settings ) && isset( $settings['campaign'] ) ) {
			$settings['campaign'] = sanitize_text_field( $settings['campaign'] );
		}

		if ( isset( $settings ) && isset( $settings['campaign'] ) ) {
			$settings['confirm'] = sanitize_text_field( $settings['confirm'] );
		}
		return $settings;
	}

}
