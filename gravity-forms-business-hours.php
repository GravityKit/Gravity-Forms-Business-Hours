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

			$days = array(
				date_i18n("D", strtotime('Monday this week')),
				date_i18n("D", strtotime('Tuesday this week')),
				date_i18n("D", strtotime('Wednesday this week')),
				date_i18n("D", strtotime('Thursday this week')),
				date_i18n("D", strtotime('Friday this week')),
				date_i18n("D", strtotime('Saturday this week')),
				date_i18n("D", strtotime('Sunday this week')),
			);

			return apply_filters( 'gravityforms_business_hours_days', $days );
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

					$content = '<div class="business_hours_list_item">';

					foreach ($list as $value) {

						$filled_days[] = $value['day'];

						$content .= '<div class="opening"><strong rel="' . $value['day'] . '">' . $value['day'] . '</strong> <span>' . $value['fromtime'] . '</span> - <span>' . $value['totime'] . '</span></div>';
					}

					// Array of days that are set
					$filled_days = array_unique( $filled_days );

					// Find what days aren't entered
					$empty_days = array_diff( $days, $filled_days );

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
			$a_time = strtotime( $a['day'] .' this week '.$a['fromtime']);
			$b_time = strtotime( $b['day'] .' this week '.$b['fromtime']);

			// If same time, don't up/down sort
			if( $a_time === $b_time ) {
				return 0;
			}

			// If A > B, move down the list (+1). Otherwise, move up (-1).
			return ( $a_time > $b_time ) ? +1 : -1;
		}

		/**
		 * Populate value for business hours field on frontend
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
					$list_string .= '<div class="business_hours_list_item"><strong>' . $list_value['day'] . '</strong>  <span>' . $list_value['fromtime'] . '</span> - <span>' . $list_value['totime'] . '</span><a href="" class="business_hours_remove_button"><i class="dashicons dashicons-dismiss"></i></a></div>';
				}
			}
			$return .= '
				<div rel="business_hours_' . $field['id'] . '" class="business_hours field_setting business_hours_item" >
					<input type="hidden" name="input_' . $field['id'] . '" value=\'' . json_encode( $list ) . '\' />
					    <div class="business_hours_list">' . $list_string . '</div>
					   <div class="business_hours_add_form">
					       <select class="item_day"></select>
					       <select class="item_fromtime"></select>
					       <select class="item_totime"></select>
					       <a href="#" class="button gform_button business_hours_add_button"><i class="dashicons dashicons-plus-alt"></i> ' . __('Add Hours', 'gravity-forms-business-hours') . '</a>
					   </div>
                </div>
			';

			return $return;
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
				),
				array(
					"handle" => "business_hours_app_admin",
					"src" => $this->get_base_url() . "/assets/js/admin.js",
					"version" => $this->_version,
					"deps" => array("jquery"),
					'callback' => array($this, 'localize_scripts'),
					"enqueue" => array(
						array(
							"admin_page" => array(
								"entry_view",
								"entry_detail"
							),
						),
					),
				),

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
				'am' => __('am', 'gravity-forms-business-hours'),
				'pm' => __('pm', 'gravity-forms-business-hours'),
				'midnight' => __('midnight', 'gravity-forms-business-hours'),
				'nextDay' => __('next day', 'gravity-forms-business-hours'),
				'open' => __('open', 'gravity-forms-business-hours'),
				'noon' => __('noon', 'gravity-forms-business-hours'),
				'day_1' => $days[0],
				'day_2' => $days[1],
				'day_3' => $days[2],
				'day_4' => $days[3],
				'day_5' => $days[4],
				'day_6' => $days[5],
				'day_7' => $days[6],
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