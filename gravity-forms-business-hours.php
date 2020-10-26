<?php
/**
 * Plugin Name: Gravity Forms Business Hours by GravityView
 * Plugin URI:  https://wordpress.org/plugins/gravity-forms-business-hours/
 * Description: Add a Business Hours field to your Gravity Forms form. Brought to you by <a href="https://gravityview.co">GravityView</a>, the best plugin for displaying Gravity Forms entries.
 * Version:     2.1.3
 * Author:      GravityView
 * Author URI:  https://gravityview.co
 * Text Domain: gravity-forms-business-hours
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 */

add_action( 'gform_loaded', 'load_gf_business_hours' );

function load_gf_business_hours() {

	//------------------------------------------
	if ( class_exists("GFForms") ) {

		GFForms::include_addon_framework();

		include_once plugin_dir_path( __FILE__ ) . 'class-gf-field-business-hours.php';
		include_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';
		include_once plugin_dir_path( __FILE__ ) . 'class-gf-business-hours.php';

		new GFBusinessHours;
	}
}
