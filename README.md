## Description
This plugin allows you to use predefined calculated callbacks or register any callback you want to perform any calculations with data outputed by Dynamic Field widget.

Default callbacks:
- increase_value_by_percentage - increase default value of Dynamic Fild widget to spcified number of percents.
- decrease_value_by_percentage - decrease default value of Dynamic Fild widget to spcified number of percents.
- sum_fields - retruns sum of default field and any additional fields you want.
- fields_diff - retruns difference of default field and any additional fields you want.
- mupltiple_fields - retruns the result of multiplying of default field and any additional fields you want.

## Config example
This code should be added into functions.php file of active theme:

```php
add_filter( 'jet-engine-calculated-callback/config', function( $callbacks = array() ) {

	/**
	 * Dynamic total price depending on guests number
	 * $field_value - is default price per guest for example
	 */
	$callbacks['custom_callback'] = function( $field_value ) {

		$additional_field       = 'guests-number';
		$additional_field_value = jet_engine()->listings->data->get_meta( $additional_field );
		$result                 = 0;

		if ( $additional_field_value >= 10 ) {
			$result = $field_value * $additional_field_value - $field_value * $additional_field_value * 0.2;
		} elseif ( 5 <= $additional_field_value && $additional_field_value < 10 ) {
			$result = $field_value * $additional_field_value - $field_value * $additional_field_value * 0.1;
		} else {
			$result = $field_value * $additional_field_value;
		}

		return $result;

	};

	return $callbacks;

} );
```

Where:

 - *$field_value* - is value of the field selected in Dynamic Filed widget settings.
 - *$callbacks* - is all registered custom callbacks list.
 - body of custom_callback funtion - is any calculations you want to perform.
