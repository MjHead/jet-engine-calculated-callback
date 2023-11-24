<?php
/**
 * Plugin Name: JetEngine - Calculated callback
 * Plugin URI:  #
 * Description: Adds new callback to Dynamic Field widget, which allows to make calculations by formulas registered from theme.
 * Version:     1.0.1
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

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	public $config = array();

	public function __construct() {
		add_filter( 'jet-engine/listings/allowed-callbacks', array( $this, 'add_calc_callback' ), 10, 2 );
		add_filter( 'jet-engine/listing/dynamic-field/callback-args', array( $this, 'add_field_to_args' ), 10, 3 );
		add_filter( 'jet-engine/listings/allowed-callbacks-args', array( $this, 'callback_controls' ) );
	}

	public function callback_controls( $args = array() ) {

		$config = $this->get_config();

		if ( empty( $config ) ) {
			return $args;
		}

		$config  = array_keys( $config );
		$options = array_combine( $config, $config );
		$options = array_merge( array( '' => esc_html__( 'Select ...', 'jet-engine' ) ), $options );

		$args['jet_calc_cbs'] = array(
			'label'       => esc_html__( 'Calculated callbacks', 'jet-engine' ),
			'type'        => 'select',
			'label_block' => true,
			'description' => esc_html__( 'Select calculated callback to apply', 'jet-engine' ),
			'default'     => '',
			'options'     => $options,
			'condition'   => array(
				'dynamic_field_filter' => 'yes',
				'filter_callback'      => array( 'jet_engine_calculated_field' ),
			),
		);

		$args['jet_calc_cb_args'] = array(
			'label'       => esc_html__( 'Additional arguments', 'jet-engine' ),
			'type'        => 'text',
			'label_block' => true,
			'description' => esc_html__( 'Pass any additional arguments for calculated callback. For example increase/dercrease percentage, additional meta field key for default callbacks. Note: for sum_fields, multiply_fields and fields_diff callbacks you can pass multiple additional fields by separating fields name with comma - field_1, field_2, field_3', 'jet-engine' ),
			'default'     => '',
			'condition'   => array(
				'dynamic_field_filter' => 'yes',
				'filter_callback'      => array( 'jet_engine_calculated_field' ),
			),
		);

		$args['jet_calc_cb_dec'] = array(
			'label'       => esc_html__( 'Decimal points', 'jet-engine' ),
			'type'        => 'number',
			'min'         => '0',
			'max'         => '99',
			'step'        => '1',
			'default'     => '0',
			'label_block' => true,
			'condition'   => array(
				'dynamic_field_filter' => 'yes',
				'filter_callback'      => array( 'jet_engine_calculated_field' ),
			),
		);

		$args['jet_calc_cb_dec_sep'] = array(
			'label'       => esc_html__( 'Decimal point', 'jet-engine' ),
			'type'        => 'text',
			'label_block' => true,
			'default'     => '.',
			'condition'   => array(
				'dynamic_field_filter' => 'yes',
				'filter_callback'      => array( 'jet_engine_calculated_field' ),
			),
		);

		$args['jet_calc_cb_th_sep'] = array(
			'label'       => esc_html__( 'Thousands separator', 'jet-engine' ),
			'type'        => 'text',
			'label_block' => true,
			'default'     => ',',
			'condition'   => array(
				'dynamic_field_filter' => 'yes',
				'filter_callback'      => array( 'jet_engine_calculated_field' ),
			),
		);


		return $args;
	}

	public function add_calc_callback( $callbacks ) {
		$callbacks['jet_engine_calculated_field'] = 'Calculated field';
		return $callbacks;
	}

	public function calculated_field( $field_value = null, $calc_key = null, $args = null ) {

		if ( ! $calc_key ) {
			return $field_value;
		}

		$config = $this->get_config();

		if ( empty( $config ) ) {
			return $field_value;
		}

		$callback = ! empty( $config[ $calc_key ] ) ? $config[ $calc_key ] : false;

		if ( ! $callback || ! is_callable( $callback ) ) {
			return $field_value;
		} else {
			return call_user_func( $callback, $field_value, $args );
		}

	}

	public function get_config() {
		return apply_filters( 'jet-engine-calculated-callback/config', array(
			'increase_value_by_percentage' => function( $field_value, $percent = 0 ) {
				if ( ! $percent ) {
					return 'Please set percentage value to calculate';
				}
				return $field_value + $field_value * $percent / 100;
			},
			'decrease_value_by_percentage' => function( $field_value, $percent = 0 ) {
				if ( ! $percent ) {
					return 'Please set percentage value to calculate';
				}
				return $field_value - $field_value * $percent / 100;
			},
			'sum_fields' => function( $field_value, $fields ) {

				if ( empty( $fields ) ) {
					return 'Please set additional fields names to calculate';
				}

				$res    = Jet_Engine_Calculated_Callback_Addon::prepare_value( $field_value );
				$fields = explode( ',', str_replace( ' ', '', $fields ) );

				foreach ( $fields as $field ) {
					$res += Jet_Engine_Calculated_Callback_Addon::prepare_value( jet_engine()->listings->data->get_meta( $field ) );
				}

				return $res;

			},
			'fields_diff' => function( $field_value, $fields ) {

				if ( empty( $fields ) ) {
					return 'Please set additional fields names to calculate';
				}

				$res    = Jet_Engine_Calculated_Callback_Addon::prepare_value( $field_value );
				$fields = explode( ',', str_replace( ' ', '', $fields ) );

				foreach ( $fields as $field ) {
					$res = $res - Jet_Engine_Calculated_Callback_Addon::prepare_value( jet_engine()->listings->data->get_meta( $field ) );
				}

				return $res;

			},
			'mupltiple_fields' => function( $field_value, $fields ) {

				if ( empty( $fields ) ) {
					return 'Please set additional fields names to calculate';
				}

				$res    = Jet_Engine_Calculated_Callback_Addon::prepare_value( $field_value );
				$fields = explode( ',', str_replace( ' ', '', $fields ) );

				foreach ( $fields as $field ) {
					$res = $res * Jet_Engine_Calculated_Callback_Addon::prepare_value( jet_engine()->listings->data->get_meta( $field ) );
				}

				return $res;

			},
			'divide_fields' => function( $field_value, $fields ) {

				if ( empty( $fields ) ) {
					return 'Please set additional fields names to calculate';
				}

				$res    = Jet_Engine_Calculated_Callback_Addon::prepare_value( $field_value );
				$fields = explode( ',', str_replace( ' ', '', $fields ) );

				foreach ( $fields as $field ) {

					$div = Jet_Engine_Calculated_Callback_Addon::prepare_value( jet_engine()->listings->data->get_meta( $field ) );

					if ( ! $div ) {

						throw new Exception( 'Division by 0' );

					}

					$res = $res / $div;

				}

				return $res;

			},
		) );
	}

	public function add_field_to_args( $args, $callback, $settings = array() ) {

		if ( 'jet_engine_calculated_field' === $callback ) {
			$args[] = isset( $settings['jet_calc_cbs'] ) ? $settings['jet_calc_cbs'] : false;
			$args[] = isset( $settings['jet_calc_cb_args'] ) ? $settings['jet_calc_cb_args'] : false;
			$args[] = isset( $settings['jet_calc_cb_dec'] ) ? $settings['jet_calc_cb_dec'] : 0;
			$args[] = isset( $settings['jet_calc_cb_dec_sep'] ) ? $settings['jet_calc_cb_dec_sep'] : '.';
			$args[] = isset( $settings['jet_calc_cb_th_sep'] ) ? $settings['jet_calc_cb_th_sep'] : ',';
		}

		return $args;
	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

	public static function prepare_value( $value ) {

		if ( ! is_numeric( $value ) ) {

			$value = 0;
			
		}

		return $value;

	}

}

Jet_Engine_Calculated_Callback_Addon::instance();

function jet_engine_calculated_field( $field_value = null, $calc_key = null, $args = null, $dec = 0, $dec_sep = '.', $th_sep = ',' ) {
	
	try {

		$result = Jet_Engine_Calculated_Callback_Addon::instance()->calculated_field( $field_value, $calc_key, $args );

	} catch( Exception $e ) {

		return $e->getMessage();

	}

	if ( is_numeric( $result ) ) {

		$result = number_format ( $result, $dec, $dec_sep, $th_sep );

	}

	return $result;

}
