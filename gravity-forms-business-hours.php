<?php
/*
Plugin Name: Gravity Forms Business Hours by GravityView
Plugin URI: https://gravityview.co
Description: Add a Business Hours field to your Gravity Forms form. Brought to you by <a href="https://gravityview.co">GravityView</a>, the best plugin for displaying Gravity Forms entries.
Version: 2.0
Author: GravityView
Author URI: https://gravityview.co
Text Domain: gravity-forms-business-hours
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2017 Katz Web Services, Inc.

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

	include_once plugin_dir_path( __FILE__ ) . 'class-gf-field-business-hours.php';
	include_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';

	class GFBusinessHours extends GFAddOn {

		protected $_version = "2.0";
		protected $_min_gravityforms_version = "2.0";
		protected $_slug = 'gravity-forms-business-hours';
		protected $_path = 'gravity-forms-business-hours/gravity-forms-business-hours.php';
		protected $_full_path = __FILE__;
		protected $_title = 'Gravity Forms Business Hours by GravityView';
		protected $_short_title = 'Business Hours';

		/**
		 * Enqueue scripts
		 * @return [type] [description]
		 */
		public function scripts() {

			$script_debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			$scripts = array(
				array(
					"handle" => "business_hours_app",
					"src" => $this->get_base_url() . "/assets/js/public{$script_debug}.js",
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

			$days = gf_business_hours_get_days();

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