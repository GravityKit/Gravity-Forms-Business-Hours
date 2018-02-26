=== Gravity Forms Business Hours by GravityView ===
Tags: gravityview,gravity forms, gravity,gravity form,business, hours, time, field, form
Requires at least: 3.3
Tested up to: 4.9.4
Stable tag: trunk
Contributors: katzwebdesign,katzwebservices,gravityview
License: GPL 3 or higher
Donate link: https://gravityview.co

Add a Business Hours field to Gravity Forms.

== Description ==

Add a Business Hours field to your Gravity Forms form.

__This plugin supports:__

* Setting closing times after midnight
* Multiple open times per day
* Displaying when a business is currently open
* Fully localized - works great in languages other than English
* Edit existing values when editing an entry
* Works with [GravityView](https://gravityview.co) and the [Gravity Forms Directory](https://wordpress.org/plugins/gravity-forms-addons/) plugins

#### Note: this plugin is actively updated, but customer support is only available to [GravityView](https://gravityview.co) license holders.

### Available Filters

These filters are available for code writers to modify the output:

* `gravityforms_business_hours_output_template` - Change template for open days. Modify the output of the open days. Data inside {{brackets}} will be replaced with the appropriate values.
* `gravityforms_business_hours_output_closed_template` - Closed days template.  Data inside {{brackets}} will be replaced with the appropriate values.
* `gravityforms_business_hours_open_label` - "Open Now" label
* `gravityforms_business_hours_default_start_time` - Default start time in `H:i` format (default: `09:00`)
* `gravityforms_business_hours_default_end_time` - Default end time in `H:i` format (default: `18:00`)
* `gravityforms_business_hours_time_format` - Modify the time format for the displayed value (default: `g:i a`)
* `gravityforms_business_hours_interval` - Time interval for the time dropdown options (default: `30`)
* `gravityforms_business_hours_day_format` - Modify the date format for how the days appear, in [PHP Date formatting](http://codex.wordpress.org/Formatting_Date_and_Time).
* `gravityforms_business_hours_days` - Array of day values used to display dropdowns and Hours output __Only modify the day values. Don't change the keys!__

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. In Edit Form, under Advanced Fields, click "Business Hours" to add the field to your form

== Screenshots ==

1. Business Hours in the Gravity Forms View Entry screen
2. The Business Hours field
3. The Business Hours button in the Form Editor

== Changelog ==

= 2.0.1 on February 26, 2018 =

* Fixed: Network Activation on Multisite
* Moved `GFBusinessHours` class to its own file

= 2.0 on November 8, 2017 =

* Email notifications now show a list of hours instead of code
* Improved output in GravityView by stripping extra whitespace
* Major code rewrite for a better structure (using Gravity Forms `GF_Field` class)
* Developers: All public methods have been removed. This is a breaking change, if you're building on top of Version 1.x

= 1.2.1 on March 10, 2015 =
* Fixed: Business Hours field would be shown as Required in GravityView Edit Entry mode
* Fixed: PHP notices

= 1.2 on December 18 =

* Liftoff!