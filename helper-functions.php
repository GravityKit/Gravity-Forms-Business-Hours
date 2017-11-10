<?php

/**
 * Generate array of times with key in `H:i` format and after-midnight in `+H:i` format
 *
 * @since 2.0
 *
 * @param  boolean $with_after_midnight Include times for next day
 *
 * @return array Array of times, with keys as `H:i`-formatted time, and values as `g:i a` formatted time
 */
function gf_business_hours_get_times( $with_after_midnight = false ) {

	$key_format = 'H:i';

	/**
	 * Modify the time format for the displayed value
	 * @param string
	 */
	$value_format = apply_filters( 'gravityforms_business_hours_time_format', 'g:i a' );

	$starttime = '00:00';
	$time = new DateTime( $starttime );

	/**
	 * Time interval for the time dropdown options
	 * @var int
	 */
	$interval_minutes = apply_filters( 'gravityforms_business_hours_interval', 30 );
	$interval_minutes = intval( $interval_minutes );
	$interval = new DateInterval('PT'.$interval_minutes.'M');

	$temptime = '';

	$times = array();

	do {

		$key = $time->format( $key_format );

		// 12:30 am
		$value = $time->format( $value_format );

		$times[ $key ] = $value;

		// Increase by 30 minute intervals
		$time->add($interval);

		$temptime = $time->format( $key_format );

	} while( $temptime !== $starttime );

	// Build additional times for the next day closing times
	if( $with_after_midnight ) {

		$next_day = __('next day', 'gravity-forms-business-hours');

		foreach( $times as $key => $time ) {

			$times[ '+'.$key ] = sprintf( '%s (%s)', $time, $next_day );

			// Only show "Next day" times until 7am
			if( $key === '07:00' ) {
				break;
			}
		}

	}

	return $times;
}

/**
 * Sort the list by the day and times entered
 *
 * @since 2.0
 *
 * @param  array $a Item 1 to be compared
 * @param  array $b Item 2 to be compared
 *
 * @return int    0 no change; -1 move down; +1 move up
 */
function gf_business_hours_sort_days( $a, $b ) {

	// Generate a timestamp for the different options
	$a_time = gf_business_hours_get_timestamp_from_time_span( $a );
	$b_time = gf_business_hours_get_timestamp_from_time_span( $b );

	// If same time, don't up/down sort
	if( $a_time === $b_time ) {
		return 0;
	}

	// If A > B, move down the list (+1). Otherwise, move up (-1).
	return ( $a_time > $b_time ) ? +1 : -1;
}

/**
 * Convert a timespan item into a timestamp for the blog's timezone
 *
 * @since 2.0
 *
 * @param  array $time_span Timespan array with at least day, fromtime keys
 *
 * @return float            Timestamp in float format, since that's what WP's `current_time()` returns
 */
function gf_business_hours_get_timestamp_from_time_span( $time_span, $from_or_to = 'from' ) {

	// Only allow from or to
	if( $from_or_to !== 'from' ) {
		$from_or_to = 'to';
	}

	// `fromtime` or `totime`
	$time_value = $time_span[$from_or_to.'time'];

	// Full weekday in English
	$day_value = $time_span['day'];

	// After midnight!
	// We add a day to the strtotime value
	// And strip the + from the time to use the standard `H:i` value
	if( substr( $time_value, 0, 1 ) === '+' ) {
		$day_value .= ' +1 day';
		$time_value = str_replace('+', '', $time_value);
	}

	// strtotime sentence
	$str_to_time_string = $day_value .' this week '.$time_value;

	// Blog timestamp
	$current_time = current_time( 'timestamp' );

	$timestamp = strtotime($str_to_time_string , $current_time );

	return floatval( $timestamp );
}

/**
 * Get an array of days
 *
 * @since 2.0
 *
 * @filter gravityforms_business_hours_days Modify the days array
 *
 * @return array Array of days of the week (displayed using PHP "D" formatting)
 */
function gf_business_hours_get_days() {

	/**
	 * Modify the date format for how the days appear, in PHP Date formatting
	 * @param string $date PHP Date format
	 */
	$day_format = apply_filters( 'gravityforms_business_hours_day_format', 'D' );

	$days = array(
		'Monday' => date_i18n($day_format, strtotime('Monday this week')),
		'Tuesday' => date_i18n($day_format, strtotime('Tuesday this week')),
		'Wednesday' => date_i18n($day_format, strtotime('Wednesday this week')),
		'Thursday' => date_i18n($day_format, strtotime('Thursday this week')),
		'Friday' => date_i18n($day_format, strtotime('Friday this week')),
		'Saturday' => date_i18n($day_format, strtotime('Saturday this week')),
		'Sunday' => date_i18n($day_format, strtotime('Sunday this week')),
	);

	/**
	 * Modify the day values. Don't change the keys!
	 * @var array
	 */
	$days = apply_filters( 'gravityforms_business_hours_days', $days );

	return $days;
}

/**
 * Is the business open now for the passed time span?
 *
 * @since 2.0
 *
 * @param  array  $time_span Time span with `day` `fromtime` and `totime`
 *
 * @return boolean            True: open; False: not open
 */
function gf_business_hours_is_open_now( $time_span ) {

	// Blog timestamp
	$current_time = current_time( 'timestamp' );

	$from_time = gf_business_hours_get_timestamp_from_time_span( $time_span, 'from' );
	$to_time = gf_business_hours_get_timestamp_from_time_span( $time_span, 'to' );

	if( $current_time < $from_time ) {
		return false;
	}

	if( $current_time > $to_time ) {
		return false;
	}

	return true;
}

/**
 * Generate the HTML for the times select
 *
 * @param string $class Class for the select input
 * @param string $default Default value
 * @param boolean $with_after_midnight Include the hours after midnight of the next day?
 *
 * @return string HTML <select> of time options
 */
function gf_business_hours_get_times_select( $class = 'item_fromtime', $default = '', $with_after_midnight = false ) {

	$output_times = gf_business_hours_get_times( $with_after_midnight );

	$output = '<select class="'.sanitize_html_class( $class ).'">';

	foreach( $output_times as $value => $label ) {
		$selected = selected( $default, $value, false );

		$output .= '<option value="' . esc_attr( $value ) .'"'.$selected.'>' . $label . '</option>';
	}

	$output .= '</select>';

	return $output;
}

/**
 * Convert the field value into an array of days
 * @param  string $value Value of the field
 * @return array|NULL        NULL if not valid or empty; array if exists and is JSON
 */
function gf_business_hours_get_list_from_value( $value ) {

	$list = json_decode( html_entity_decode( $value ), true );

	// Sometimes it's double-encoded
	if( is_string( $list ) ) {
		$list = json_decode( $list, true );
	}

	if( empty( $list ) ) {
		return NULL;
	}

	// Sort the days of the week
	usort( $list, 'gf_business_hours_sort_days' );

	return $list;
}