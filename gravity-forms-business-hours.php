<?php
/*
Plugin Name: Gravity Forms Business Hours by GravityView
Plugin URI: https://gravityview.co
Description: Add a Business Hours field to your Gravity Forms form.
Version: 1.2
Author: GravityView
Author URI: https://gravityview.co
Text Domain: gravity-forms-business-hours
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2014 Katz Web Services, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

//------------------------------------------
if ( class_exists("GFForms") ) {

	GFForms::include_addon_framework();

	class GFBusinessHours extends GFAddOn {

		protected $_version = "1.2";
		protected $_min_gravityforms_version = "1.7.9999";
		protected $_slug = 'gravity-forms-business-hours';
		protected $_path = 'gravity-forms-business-hours/gravity-forms-business-hours.php';
		protected $_full_path = __FILE__;
		protected $_title = 'Gravity Forms Business Hours by GravityView';
		protected $_short_title = 'Business Hours';

		/**
		 * Register functions to be called on the frontend
		 * @return void
		 */
		public function init_frontend() {
			parent::init_frontend();

			add_action('gform_entry_field_value', array($this, 'display_entry_field_value'), 10, 4);

			add_filter('gform_save_field_value', array($this, 'save_field_value'), 10, 4);

			add_filter('gform_field_validation', array( $this, 'validate'), 10, 4 );

			add_filter('gform_field_input', array( $this, 'business_hours_field'), 10, 5 );
		}

		/**
		 * Validate the field value.
		 *
		 * @param  array $status Status array with `is_valid` and `message` keys
		 * @param  mixed $value  Field value
		 * @param  array $form   Gravity Forms form array
		 * @param  array $field  Gravity Forms field array
		 * @return array         Status array
		 */
		function validate( $status, $value, $form, $field ) {

			if( $field['type'] !== 'business_hours' ) {
				return $status;
			}

			$return = $status;

			$json_value = json_decode( $value );

			if( !empty( $field['isRequired'] ) && empty( $value ) || $value === 'null' || is_null( $json_value ) ) {

				$return = array(
					'is_valid'	=> false,
					'message'	=> __('This field is required.', 'gravity-forms-business-hours'),
				);

			}

			return $return;
		}

		/**
		 * Register functions to be called when DOING_AJAX
		 * @return void
		 */
		public function init_ajax() {
			parent::init_ajax();

			add_filter('gform_field_content', array($this, 'business_hours_field_admin'), 10, 5);
		}

		/**
		 * Register functions to be called in the admin
		 * @return void
		 */
		public function init_admin() {
			parent::init_admin();

			add_action('gform_entries_field_value', array($this, 'business_hours_entries'), 10, 3);

			add_action('gform_entry_field_value', array($this, 'display_entry_field_value'), 10, 4);
			add_filter('gform_add_field_buttons', array($this, 'add_field_button'));

			add_action('gform_editor_js', array($this, 'editor_script'));

			add_action('gform_editor_js_set_default_values', array($this, 'set_defaults'));

			add_filter('gform_field_content', array($this, 'business_hours_field_admin'), 10, 5);

			add_filter('gform_field_type_title', array($this, 'field_type_title'), 10 );
		}

		/**
		 * Modify the name of the field type in the Form Editor
		 * @param  string $type Field type string
		 * @return string       Field type string
		 */
		public function field_type_title( $type = '' ) {

			if( $type === 'business_hours' ) {
				return __('Business Hours', 'gravity-forms-business-hours');
			}

			return $type;

		}

		/**
		 * If on Edit Entry screen, show default editing fields. In Edit Form, show placeholder content.
		 * @param  [type] $content [description]
		 * @param  [type] $field   [description]
		 * @param  [type] $value   [description]
		 * @param  [type] $lead_id [description]
		 * @param  [type] $form_id [description]
		 * @return [type]          [description]
		 */
		public function business_hours_field_admin($content, $field, $value, $lead_id, $form_id) {

			if( $field['type'] !== 'business_hours' ) {
				return $content;
			}

			$alternative_content = '';

			$edit_form_page = ( rgget('page') === 'gf_edit_forms' && !empty( $_GET['id'] ) );

			$add_field_ajax = ( defined('DOING_AJAX') && DOING_AJAX ) && (rgpost('action') === 'rg_add_field');

			// If on Edit Entry screen, show default editing fields
			if ( rgget('page') === 'gf_entries' && rgget('view') === 'entry' && rgpost('screen_mode') === 'edit' ) {

				$alternative_content = $this->business_hours_field('', $field, $value, $lead_id, $form_id );

			}
			// A field is already saved, or the field is being added
			else if( $edit_form_page || $add_field_ajax ) {

				$alternative_content = "<div class='gf-html-container gf-business-hours-container'><span class='gf_blockheader'><i class='dashicons dashicons-clock'></i> " . __('Business Hours', 'gravity-forms-business-hours') . '</span><span>' . __('This is a content placeholder. Preview this form to view the Business Hours field.', 'gravity-forms-business-hours') . "</span></div>";
			}

			return str_replace('</label>', '</label>' . $alternative_content, $content);
		}

		/**
		 * Get an array of days
		 * @filter gravityforms_business_hours_days Modify the days array
		 * @return array Array of days of the week (displayed using PHP "D" formatting)
		 */
		private static function get_days() {

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
		 * Set the field value on the Entries page
		 * @param  [type] $value    [description]
		 * @param  [type] $form_id  [description]
		 * @param  [type] $field_id [description]
		 * @return [type]           [description]
		 */
		public function business_hours_entries($value, $form_id, $field_id) {

			$form = GFAPI::get_form($form_id);
			$field = RGFormsModel::get_field($form, $field_id);

			return self::display_entry_field_value( $value, $field, array(), $form );
		}

		/**
		 * Populate value for business hours field on entry page
		 * @param  [type] $value [description]
		 * @param  [type] $field [description]
		 * @param  array  $lead  [description]
		 * @param  array  $form  [description]
		 * @return [type]        [description]
		 */
		public static function display_entry_field_value($value, $field, $lead = array(), $form = array() ) {

			$return = $value;

			if ($field['type'] === 'business_hours') {

				$content = '';

				$days = self::get_days();

				$filled_days = array();

				$list = self::get_list_from_value( $value );

				if (!empty($list) && is_array($list)) {

					/**
					 * @link http://schema.org/LocalBusiness
					 */
					$content = '<div class="business_hours_list_item" itemscope itemtype="http://schema.org/LocalBusiness">';

					foreach ($list as $value) {

						$filled_days[] = $value['day'];

						/**
						 * Generate schema.org markup
						 * @link http://schema.org/openingHours
						 */
						$datetime = sprintf( '%s %s-%s', substr($value['day'], 0, 2), $value['fromtime'], str_replace('+', '', $value['totime'] ) );
						$content .= '
						<div class="opening">
							<time itemprop="openingHoursSpecification" itemscope itemtype="http://schema.org/OpeningHoursSpecification" datetime="'.$datetime.'">
							<strong itemprop="dayOfWeek" itemscope itemtype="http://schema.org/DayOfWeek" rel="' . $value['daylabel'] . '"><span itemprop="name" content="'. $value['day'] .'">' . $value['daylabel'] . '</span></strong> <span itemprop="opens" content="'.$value['fromtime'].'">' . $value['fromtimelabel'] . '</span> - <span itemprop="closes" content="'.$value['totime'].'">' . $value['totimelabel'] . '</span>
							</time>
						</div>';
					}

					// Array of days that are set
					$filled_days = array_unique( $filled_days );

					// Find what days aren't entered
					$empty_days = array_diff( array_keys($days), $filled_days );

					if( !empty( $empty_days ) ) {

						// And set them as closed
						foreach( $empty_days as $value ) {
							$content .= '<div><strong>' . $value . '</strong> <span>' . __('Closed', 'gravity-forms-business-hours') . '</span></div>';
						}
					}

					$content .= "</div>";
				}

				$return = $content;
			}

			return $return;
		}

		/**
		 * Add a Business Hours field to the Advanced Fields group
		 * @param [type] $field_groups [description]
		 */
		public function add_field_button($field_groups) {

			foreach ($field_groups as &$group) {

				if ($group['name'] === 'advanced_fields' ) {

					$group['fields'][] = array(
						'class' => 'button',
						'value' => __('Business Hours', 'gravity-forms-business-hours'),
						'onclick' => "StartAddField('business_hours');"
					);

					break;
				}
			}

			return $field_groups;
		}

		/**
		 * Change the default field label
		 */
		public function set_defaults() {
			?>
			//this hook is fired in the middle of a switch statement,
			//so we need to add a case for our new field type
			case "business_hours" :
				field.inputs = null;
				field.label = "<?php echo esc_js( __('Business Hours', 'gravity-forms-business-hours') ); ?>"; //setting the default field label
			break;
		<?php
		}

		/**
		 * Set the inputs for the field type
		 * @return void
		 */
		public function editor_script() {
			?>
			<script type='text/javascript'>
				fieldSettings['business_hours'] = ".label_setting, .visibility_setting, .admin_label_setting, .rules_setting, .description_setting, .conditional_logic_field_setting, .css_class_setting";
			</script>
		<?php
		}

		/**
		 * Convert the field value into an array of days
		 * @param  string $value Value of the field
		 * @return array|NULL        NULL if not valid or empty; array if exists and is JSON
		 */
		public static function get_list_from_value( $value ) {

			$list = json_decode( html_entity_decode( $value ), true );

			// Sometimes it's double-encoded
			if( is_string( $list ) ) {
				$list = json_decode( $list, true );
			}

			if( empty( $list ) ) {
				return NULL;
			}

			// Sort the days of the week
			usort( $list, array('GFBusinessHours', 'sort_days') );

			return $list;
		}

		/**
		 * Sort the list by the day and times entered
		 * @param  array $a Item 1 to be compared
		 * @param  array $b Item 2 to be compared
		 * @return int    0 no change; -1 move down; +1 move up
		 */
		static function sort_days( $a, $b ) {

			// Generate a timestamp for the different options
			$a_time = self::get_timestamp_from_time_span( $a );
			$b_time = self::get_timestamp_from_time_span( $b );

			// If same time, don't up/down sort
			if( $a_time === $b_time ) {
				return 0;
			}

			// If A > B, move down the list (+1). Otherwise, move up (-1).
			return ( $a_time > $b_time ) ? +1 : -1;
		}

		/**
		 * Convert a timespan item into a timestamp for the blog's timezone
		 * @param  array $time_span Timespan array with at least day, fromtime keys
		 * @return float            Timestamp in float format, since that's what WP's `current_time()` returns
		 */
		public static function get_timestamp_from_time_span( $time_span, $from_or_to = 'from' ) {

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
		 * Is the business open now for the passed time span?
		 * @param  array  $time_span Time span with `day` `fromtime` and `totime`
		 * @return boolean            True: open; False: not open
		 */
		public static function is_open_now( $time_span ) {

			// Blog timestamp
			$current_time = current_time( 'timestamp' );

			$from_time = self::get_timestamp_from_time_span( $time_span, 'from' );
			$to_time = self::get_timestamp_from_time_span( $time_span, 'to' );

			if( $current_time < $from_time ) {
				return false;
			}

			if( $current_time > $to_time ) {
				return false;
			}

			return true;
		}

		/**
		 * Generate the Open Now lael
		 * @param  array $time_span Time span array
		 * @return string            HTML output, if open. Empty string if not.
		 */
		public static function open_label( $time_span ) {

			$output = '';

			if( self::is_open_now( $time_span ) ) {

				$output = '<span class="open_label">';
				$output .= esc_html__('Open Now', 'gravity-forms-business-hours');
				$output .= '</span>';
			}

			/**
			 * Modify the label for Open now
			 * @var string
			 */
			$output = apply_filters('gravityforms_business_hours_open_label', $output, $time_span );

			return $output;
		}

		/**
		 * Display field to be shown in Form and when editing entry
		 * @param  [type] $content [description]
		 * @param  [type] $field   [description]
		 * @param  [type] $value   [description]
		 * @param  [type] $lead_id [description]
		 * @param  [type] $form_id [description]
		 * @return [type]          [description]
		 */
		function business_hours_field($content, $field, $value, $lead_id, $form_id) {

			if ($field['type'] !== 'business_hours') {
				return $content;
			}

			$return = $content;

			$list = self::get_list_from_value( $value );

			$list_string = '';

			// Populate existing list items
			if (!empty($list)) {
				foreach ($list as $list_value) {
					$list_string .= '<div class="business_hours_list_item"><strong>' . $list_value['daylabel'] . '</strong>  <span>' . $list_value['fromtimelabel'] . '</span> - <span>' . $list_value['totimelabel'] . '</span><a href="" class="business_hours_remove_button"><i class="dashicons dashicons-dismiss"></i></a></div>';
				}
			}
			$return .= '
				<div rel="business_hours_' . $field['id'] . '" class="business_hours field_setting business_hours_item" >
					<input type="hidden" name="input_' . $field['id'] . '" value=\'' . json_encode( $list ) . '\' />
						<div class="business_hours_list">' . $list_string . '</div>
					   <div class="business_hours_add_form">
							';
			$return .= self::get_day_select();

			/**
			 * Change the default start time.
			 * @param string $time Time in 'H:i' format
			 */
			$default_start_time = apply_filters( 'gravityforms_business_hours_default_start_time', '09:00' );

			/**
			 * Change the default end time
			 * @param string $time Time in 'H:i' format
			 */
			$default_end_time = apply_filters( 'gravityforms_business_hours_default_end_time', '17:00' );

			$return .= self::get_times_select('item_fromtime', $default_start_time, false);
			$return .= self::get_times_select('item_totime', $default_end_time, true);

			$return .= '<a href="#" class="button gform_button business_hours_add_button"><i class="dashicons dashicons-plus-alt"></i> ' . __('Add Hours', 'gravity-forms-business-hours') . '</a>
					   </div>
				</div>
			';

			return $return;
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
		static function get_times_select( $class = 'item_fromtime', $default = '', $with_after_midnight = false ) {

			$output_times = self::get_times( $with_after_midnight );

			$output = '<select class="'.sanitize_html_class( $class ).'">';

			foreach( $output_times as $value => $label ) {
				$selected = selected( $default, $value, false );

				$output .= '<option value="' . esc_attr( $value ) .'"'.$selected.'>' . $label . '</option>';
			}

			$output .= '</select>';

			return $output;
		}

		/**
		 * Generate array of times with key in `H:i` format and after-midnight in `+H:i` format
		 *
		 * @param  boolean $with_after_midnight Include times for next day
		 * @return [type]                       [description]
		 */
		static function get_times( $with_after_midnight = false ) {


			$key_format = 'H:i';

			/**
			 * Modify the time format for the displayed value
			 * @param string
			 */
			$value_format = apply_filters( 'gravityforms_business_hours_time_format', 'g:i a' );

			$starttime = '00:00';
			$time = new DateTime( $starttime );

			/**
			 * Time interval for the dropdown options
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
		 * Build a select field with the full name of the day as the value and abreviation as the label
		 * @return string HTML <select> field
		 */
		static function get_day_select() {

			$output = '<select class="item_day">';

			$days = self::get_days();

			foreach ($days as $key => $value) {
				$output .= '<option value="'.esc_attr( $key ).'">'.esc_html( $value ).'</option>';
			}

			$output .= '</select>';

			return $output;
		}

		/**
		 * Encode the field value
		 * @param  [type] $value [description]
		 * @param  [type] $lead  [description]
		 * @param  [type] $field [description]
		 * @param  [type] $form  [description]
		 * @return [type]        [description]
		 */
		public function save_field_value($value, $lead, $field, $form) {

			if (!empty($field['type']) && $field['type'] === 'business_hours') {

				$is_already_json = json_decode( $value );

				// Don't double-encode
				if( is_null( $is_already_json ) ) {
					return json_encode( $value );
				}
			}

			return $value;
		}

		/**
		 * Enqueue scripts
		 * @return [type] [description]
		 */
		public function scripts() {
			$scripts = array(
				array(
					"handle" => "business_hours_app",
					"src" => $this->get_base_url() . "/assets/js/public.js",
					"version" => $this->_version,
					"deps" => array("jquery"),
					'callback' => array($this, 'localize_scripts'),
					"enqueue" => array(
						array(
							"field_types" => array("business_hours"),
						),
						array(
							"admin_page" => array(
								"entry_edit",
								"entry_detail"
							),
						),
					),
				)
			);

			return array_merge(parent::scripts(), $scripts);
		}

		/**
		 * Provide translation for the scripts
		 * @return void
		 */
		public function localize_scripts() {

			$days = self::get_days();

			$strings = array(
				'already_exists' => __('This combination already exists', 'gravity-forms-business-hours'),
			);

			wp_localize_script('business_hours_app', 'GFBusinessHours', $strings);

			wp_localize_script('business_hours_app_admin', 'GFBusinessHours', $strings);

		}

		/**
		 * Enqueue styles using the Gravity Forms Addons framework
		 *
		 * @see GFAddOn::styles()
		 * @return void
		 */
		public function styles() {
			$styles = array(
				array("handle" => "business_hours_admin_style",
					"src" => $this->get_base_url() . "/assets/css/admin.css",
					"version" => $this->_version,
					"enqueue" => array(
						array(
							"admin_page" => array(
								"entry_view",
								"entry_detail",
								"form_editor",
							)
						),
					),
				),
				array("handle" => "business_hours_frontend_style",
					"src" => $this->get_base_url() . "/assets/css/public.css",
					"version" => $this->_version,
					"deps" => array("dashicons"),
					"enqueue" => array(
						array("field_types" => array("business_hours")),
						array(
							"admin_page" => array(
								"entry_edit",
								"entry_detail"
							),
						),
					),
				),
			);
			return array_merge(parent::styles(), $styles);
		}
	}

	new GFBusinessHours;
}