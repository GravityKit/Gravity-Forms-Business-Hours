<?php
/*
Plugin Name: Gravity Forms Business Hours by GravityView
Plugin URI: https://gravityview.co
Description: Add a Business Hours field to your Gravity Forms form. Brought to you by <a href="https://gravityview.co">GravityView</a>, the best plugin for displaying Gravity Forms entries.
Version: 2.1.3
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