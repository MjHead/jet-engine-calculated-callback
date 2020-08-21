<?php
/**
 * Plugin Name: JetEngine - Calculated callback
 * Plugin URI:  #
 * Description: Adds new callback to Dynamic Field widget, which allows to make calculations by formulas registered from theme.
 * Version:     1.0.0
 * Author:      Crocoblock
 * Author URI:  https://crocoblock.com/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

class Jet_Engine_Calculated_Callback_Addon {

	public $config = array();

	public function __construct() {
		add_filter( 'jet-engine/listings/allowed-callbacks', 'jet_add_calc_callback', 10, 2 );
		add_filter( 'jet-engine/listing/dynamic-field/callback-args', array( $this, 'add_field_to_args' ), 10, 3 );
	}

	public function add_calc_callback( $callbacks ) {
		$callbacks['jet_engine_calculated_field'] = 'Calculated field';
		return $callbacks;
	}

	public function calculated_field( $field_value, $calc_key = null ) {

		if ( ! $calc_key ) {
			return $field_value;
		}

		$config = $this->get_config();

		if ( empty( $config ) ) {
			return $field_value;
		}

		return $field_1_value * get_post_meta( get_the_ID(), 'field_2' );

	}

	public function get_config() {
		return apply_filters( 'jet-engine-calculated-callback/config', array() );
	}

	public function add_field_to_args( $args, $callback, $settings = array() ) {

		if ( 'jet_engine_calculated_field' === $callback ) {
			$args[] = isset( $settings['dynamic_field_post_meta_custom'] ) ? $settings['dynamic_field_post_meta_custom'] : false;
		}

		return $args;
	}

}

$jet_engine_calculated = new Jet_Engine_Calculated_Callback_Addon( $field_value, $calc_key );

function jet_engine_calculated_field() {
	return $jet_engine_calculated->calculated_field( $field_value, $calc_key );
}
