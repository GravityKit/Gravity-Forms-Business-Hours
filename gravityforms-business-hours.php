<?php
/*
Plugin Name: Gravity Forms Business Hours
Plugin URI: http://www.gravityforms.com
Description: adds business hours feature to your forms
Version: 1.1
Author: Fayez Qandeel
Author URI: https://www.elance.com/s/creative-next/

------------------------------------------------------------------------
Copyright 2012-2013 Rocketgenius Inc.

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
if (class_exists("GFForms")) {
	GFForms::include_addon_framework();

	class GFBusinessHours extends GFAddOn {

		protected $_version = "1.1";
		protected $_min_gravityforms_version = "1.7.9999";
		protected $_slug = "gravityforms-business-hours";
		protected $_path = "gravityformsbusinesshours/gravityforms-business-hours.php";
		protected $_full_path = __FILE__;
		protected $_title = "Gravity Forms Business Hours";
		protected $_short_title = "Business Hours";

		// Permissions
		protected $_enable_rg_autoupgrade = true;

		public function init() {
			parent::init();

		}

		public function init_admin() {
			parent::init_admin();
			add_action("gform_entries_field_value", array($this, "business_hours_entries"), 10, 3);
			add_action('gform_entry_field_value', array($this, 'display_entry_field_value'), 10, 4);
			add_filter("gform_add_field_buttons", array($this, 'add_business_hours_field'));
			add_action("gform_editor_js_set_default_values", array($this, 'set_defaults'));
			add_action("gform_editor_js", array($this, 'editor_script'));

			if (RG_CURRENT_VIEW && isset($_POST['screen_mode']) && ($_POST['screen_mode'] == 'edit')) {
				add_filter('gform_field_content', array($this, 'business_hours_field_entry_edit'), 10, 5);
			}
		}

		public function business_hours_field_entry_edit($content, $field, $value, $lead_id, $form_id) {
			if ($field['type'] == 'business_hours') {

				$list = json_decode($value);
				if (!is_array($list)) {
					$list = json_decode($list);
				}
				$list_string = '';
				if (!empty($list)) {
					foreach ($list as $list_value) {
						$list_string .= '<div class="business_hours_list_item"><strong>' . $list_value->day . '</strong>  <span>' . $list_value->fromtime . '</span> - <span>' . $list_value->totime . '</span><a href="" class="business_hours_remove_button">-</a></div>';
					}
				}

				$alternative_content .= '
        <div rel="' . $field['id'] . '" class="business_hours field_setting business_hours_item" >
            <input type="hidden" name="input_' . $field['id'] . '" value=\'' . stripslashes(trim($value, '"')) . '\' />
            <div ng-controller="business_hours_controller">
                <div class="business_hours_list">' . $list_string . '</div>
               <div class="business_hours_add_form">
                   <select class="item_day">
                   </select>
                   <select class="item_fromtime" >
                   </select>
                   <select class="item_totime">
                   </select>
                   <a href="" class="button business_hours_add_button">
                    <i class="fa fa-plus-square"></i> ' . __('Add Hours', 'gravityforms-business-hours') . '
                   </a>
               </div>
            </div>
                  </div>
        ';
				return str_replace('</label>', '</label>' . $alternative_content, $content);
			} else {
				return $content;
			}
		}

		public function init_frontend() {
			parent::init_frontend();
			add_filter('gform_field_content', array($this, 'business_hours_field'), 10, 5);
			add_filter("gform_save_field_value", array($this, "save_field_value"), 10, 4);
		}

		public function init_ajax() {
			parent::init_ajax();
		}

		public function business_hours_entries($value, $form_id, $field_id) {
			$days = array(
				date_i18n("D", strtotime('Monday this week')),
				date_i18n("D", strtotime('Tuesday this week')),
				date_i18n("D", strtotime('Wednesday this week')),
				date_i18n("D", strtotime('Thursday this week')),
				date_i18n("D", strtotime('Friday this week')),
				date_i18n("D", strtotime('Saturday this week')),
				date_i18n("D", strtotime('Sunday this week')),
			);

			$filled_days = array();
			$form_meta = RGFormsModel::get_form_meta($form_id);
			$field = RGFormsModel::get_field($form_meta, $field_id);

			if ($field['type'] == 'business_hours') {
				$content = '<div class="business_hours_admin_holder">';
				$list = json_decode(html_entity_decode($value));
				if (!is_array($list)) {
					$list = json_decode($list);
				}
				if (!empty($list) && is_array($list)) {
					foreach ($list as $list_value) {
						$filled_days[] = $list_value->day;
						$content .= '<div class="opening"><strong rel="' . $list_value->day . '">' . $list_value->day . '</strong> <span>' . $list_value->fromtime . '</span> - <span>' . $list_value->totime . '</span></div>';
					}
					$filled_days = array_unique($filled_days);
					$empty_days = array_diff($days, $filled_days);
					if (!empty($empty_days)) {
						foreach ($empty_days as $value) {
							$content .= '<div><strong>' . $value . '</strong> <span>' . __('Closed', 'gravityforms-business-hours') . '</span></div>';
						}
					}
				}

				return $content . "</div>";
			}
			return $value;
		}

		//populating value for business hours field on entry page
		public static function display_entry_field_value($value, $field, $lead, $form) {

			$days = array(
				date_i18n("D", strtotime('Monday this week')),
				date_i18n("D", strtotime('Tuesday this week')),
				date_i18n("D", strtotime('Wednesday this week')),
				date_i18n("D", strtotime('Thursday this week')),
				date_i18n("D", strtotime('Friday this week')),
				date_i18n("D", strtotime('Saturday this week')),
				date_i18n("D", strtotime('Sunday this week')),
			);

			$filled_days = array();

			if ($field['type'] == 'business_hours') {
				$content = '<div class="business_hours_admin_holder">';
				$list = json_decode($value);
				if (!is_array($list)) {
					$list = json_decode($list);
				}
				if (!empty($list) && is_array($list)) {
					foreach ($list as $value) {
						$filled_days[] = $value->day;
						$content .= '<div class="opening"><strong rel="' . $value->day . '">' . $value->day . '</strong> <span>' . $value->fromtime . '</span> - <span>' . $value->totime . '</span></div>';
					}
					$filled_days = array_unique($filled_days);
					$empty_days = array_diff($days, $filled_days);
					if (!empty($empty_days)) {
						foreach ($empty_days as $value) {
							$content .= '<div><strong>' . $value . '</strong> <span>' . __('Closed', 'gravityforms-business-hours') . '</span></div>';
						}
					}
				}
				return $content . "</div>";
			}
			return $value;
		}

		//add field button
		public function add_business_hours_field($field_groups) {
			foreach ($field_groups as &$group) {
				if ($group["name"] == "advanced_fields") {
					$group["fields"][] = array("class" => "button", "value" => __("Business Hours", "gravityforms-business-hours"), "onclick" => "StartAddField('business_hours');");
					break;
				}
			}
			return $field_groups;
		}

		//changing default field label
		public function set_defaults() {
			?>
            //this hook is fired in the middle of a switch statement,
            //so we need to add a case for our new field type
            case "business_hours" :
                field.label = "<?php echo __('Business Hours Field', 'gravityforms-business-hours')?>"; //setting the default field label
            break;
<?php
}

		//form editor script
		public function editor_script() {
			?>
<script type='text/javascript'>
                fieldSettings["business_hours"] = ".label_setting ";
            </script>
<?php
}

		//populating value for business hours field on frontend
		function business_hours_field($content, $field, $value, $lead_id, $form_id) {

			if ($field['type'] == 'business_hours') {
				$list = json_decode($value);
				$list_string = '';
				if (!empty($list)) {
					foreach ($list as $list_value) {
						$list_string .= '<div class="business_hours_list_item"><strong>' . $list_value->day . '</strong>  <span>' . $list_value->fromtime . '</span> - <span>' . $list_value->totime . '</span><a href="" class="business_hours_remove_button">-</a></div>';
					}
				}
				$content .= '
					<div rel="' . $field['id'] . '" class="business_hours field_setting business_hours_item" >
						<input type="hidden" name="input_' . $field['id'] . '" value=\'' . $value . '\' />
						<div ng-controller="business_hours_controller">
						    <div class="business_hours_list">' . $list_string . '</div>
						   <div class="business_hours_add_form">
						       <select class="item_day">
						       </select>
						       <select class="item_fromtime" >
						       </select>
						       <select class="item_totime">
						       </select>
						       <a href="" class="button business_hours_add_button">
						        <i class="fa fa-plus-square"></i> ' . __('Add Hours', 'gravityforms-business-hours') . '
						       </a>
						   </div>
						</div>
	                </div>
				';
				return $content;
			} else {
				return $content;
			}
		}

		public function save_field_value($value, $lead, $field, $form) {

			if (!empty($field['type']) && $field['type'] === 'business_hours') {
				return json_encode($value);
			}

			return $value;
		}

		// enqueue scripts
		public function scripts() {
			$scripts = array(
				array(
					"handle" => "business_hours_app",
					"src" => $this->get_base_url() . "/js/business_hours_app.js",
					"version" => $this->_version,
					"deps" => array("jquery"),
					'callback' => array($this, 'localize_scripts'),
					"enqueue" => array(
						array(
							"field_types" => array("business_hours"),
						),
						array(
							"admin_page" => array("entry_edit", "entry_detail"),
						),
					),
				),
				array(
					"handle" => "business_hours_app_admin",
					"src" => $this->get_base_url() . "/js/business_hours_app_admin.js",
					"version" => $this->_version,
					"deps" => array("jquery"),
					'callback' => array($this, 'localize_scripts'),
					"enqueue" => array(
						array(
							"admin_page" => array("entry_view", "entry_detail"),
						),
					),
				),

			);

			return array_merge(parent::scripts(), $scripts);
		}

		public function localize_scripts() {

			$strings = array(
				'am' => __('am', 'gravityforms-business-hours'),
				'pm' => __('pm', 'gravityforms-business-hours'),
				'midnight' => __('midnight', 'gravityforms-business-hours'),
				'nextDay' => __('next day', 'gravityforms-business-hours'),
				'open' => __('open', 'gravityforms-business-hours'),
				'noon' => __('noon', 'gravityforms-business-hours'),
				'day_1' => date_i18n("D", strtotime('Monday this week')),
				'day_2' => date_i18n("D", strtotime('Tuesday this week')),
				'day_3' => date_i18n("D", strtotime('Wednesday this week')),
				'day_4' => date_i18n("D", strtotime('Thursday this week')),
				'day_5' => date_i18n("D", strtotime('Friday this week')),
				'day_6' => date_i18n("D", strtotime('Saturday this week')),
				'day_7' => date_i18n("D", strtotime('Sunday this week')),
			);
			wp_localize_script('business_hours_app', 'strings', $strings);
			wp_localize_script('business_hours_app_admin', 'strings', $strings);

		}

		// enqueue styles
		public function styles() {
			$styles = array(
				array("handle" => "business_hours_admin_style",
					"src" => $this->get_base_url() . "/css/business_hours_admin_style.css",
					"version" => $this->_version,
					"enqueue" => array(
						array("admin_page" => array("entry_view", "entry_detail")),
					),
				),
				array("handle" => "business_hours_frontend_style",
					"src" => $this->get_base_url() . "/css/business_hours_frontend_style.css",
					"version" => $this->_version,
					"enqueue" => array(
						array("field_types" => array("business_hours")),
						array(
							"admin_page" => array("entry_edit", "entry_detail"),
						),
					),
				),
			);
			return array_merge(parent::styles(), $styles);
		}
	}
	new GFBusinessHours();
}