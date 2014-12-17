/**
 * Part of GravityView_Ratings_Reviews plugin. This script is enqueued when in
 * admin page.
 *
 * globals jQuery, GFBusinessHours
 */
(function($){

	"use strict";

	var self = $.extend({

		/**
		 * Lists stored using the field ID as a key
		 * @type {Array}
		 */
		lists: [],
		days: [
			GFBusinessHours.day_1,
			GFBusinessHours.day_2,
			GFBusinessHours.day_3,
			GFBusinessHours.day_4,
			GFBusinessHours.day_5,
			GFBusinessHours.day_6,
			GFBusinessHours.day_7
		],
		business_hours_list: []
	});

	self.init = function() {

		self.build_dropdowns();

		self.build_lists();

		self.setup_bindings();

	};

	/**
	 * Bind clicks and changes for the field
	 * @return {[type]} [description]
	 */
	self.setup_bindings = function() {

		$(document).on('click', '.business_hours_remove_button', self.click_remove_button );

		$(document).on('click', '.business_hours_add_button', self.click_add_button );

		/**
		 * detecting change on start time list & disabling ealier end times
		 */
		$(document).on('change', '.item_fromtime', self.time_change );
	};

	/**
	 * detecting change on start time list & disabling ealier end times
	 */
	self.time_change = function( e ) {

		$(this)
			.parents('.business_hours_item')
			.find('.item_totime option[value="' + $(this).val() + '"]')
				.attr('disabled', 'disabled')
				.prevAll('option')
				.attr('disabled', 'disabled');
	};

	/**
	 * Get a list from the stored lists based on the ID.
	 * @param  {[type]} id Field ID `ref` attribute
	 * @return {array}   Returns empty array if not set yet.
	 */
	self.get_list = function( id ) {
		return self.lists[ id ] = self.lists[ id ] || [];
	};

	/**
	 * attach event to add hours button
	 */
	self.click_add_button = function( e ) {
		e.preventDefault();

		var field_id = $(this).parents('.business_hours_item').attr('rel');

		var $parent = $(this).parents('.business_hours_item');

		var item = {
			day: $parent.find('.item_day').val(),
			fromtime: $parent.find('.item_fromtime').val(),
			totime: $parent.find('.item_totime').val()
		};

		self.business_hours_list = self.get_list( field_id );

		// The exact date combination doesn't exist yet
		if ( !self.does_exact_day_exist(item, self.business_hours_list) ) {

			// Add the day to the list
			self.business_hours_list.push(item);

			// Update the input value
			self.update_input_value( this )

			// Rebuild the HTML list
			self.populate_business_hours_list( this );

			// Process the day select field
			self.move_day_forward( $parent, item );

		} else {

			alert( GFBusinessHours.already_exists );

			return false;
		}
	};

	/**
	 * When a day is added, set the day picker to the next day
	 * @param  {[type]} $parent [description]
	 * @param  {[type]} item    [description]
	 * @return {[type]}         [description]
	 */
	self.move_day_forward = function( $parent, item ) {

		var $day_field = $parent.find('.item_day');

		var day_index = self.days.indexOf( item.day );

		// If the day is Sunday, set the day picker to Monday
		if( day_index === 6 ) {
			day_index = 0;
		}
		// Otherwise, progress to next day in list
		else {
			day_index++
		}

		$day_field.val( self.days[ day_index ] );
	};

	/**
	 * When Remove button is clicked
	 */
	self.click_remove_button = function( e ) {
		e.preventDefault();

		var field_id = $(this).parents('.business_hours_item').attr('rel');
		var index = $(this).parents('.business_hours_list_item').index();
		var list_string_value = self.get_list( field_id );

		self.business_hours_list = list_string_value;

		// If there's more than one item
		if ( index >= 0 ) {

			// Remove the item from the business list
			self.business_hours_list.splice(index, 1);

			self.update_input_value( this );

			$(this).parents('.business_hours_list_item').remove();
		}
	};

	/**
	 * Populate self.times with array of time values lists
	 */
	self.get_times_array = function( with_after_midnight ) {

		var times = [
			"12:00 " + GFBusinessHours.am + " " + GFBusinessHours.midnight,
			"12:30 " + GFBusinessHours.am,
		];

		for (var i = 1; i < 12; i++) {
			times.push(i + ":00 " + GFBusinessHours.am);
			times.push(i + ":30 " + GFBusinessHours.am);
		}

		// Add noon
		times.push("12:00 " + GFBusinessHours.pm + " " + GFBusinessHours.noon);
		times.push("12:30 " + GFBusinessHours.pm);

		for (var i = 1; i < 12; i++) {
			times.push(i + ":00 " + GFBusinessHours.pm);
			times.push(i + ":30 " + GFBusinessHours.pm);
		}

		if( true === with_after_midnight ) {

			times.push("12:00 " + GFBusinessHours.am + ' (' + GFBusinessHours.midnight + " " + GFBusinessHours.nextDay + ')');
			times.push("12:30 " + GFBusinessHours.am + ' (' + GFBusinessHours.nextDay + ')' );

			// Add another 7 hours after midnight
			for (var i = 1; i < 7; i++) {
				times.push(i + ":00 " + GFBusinessHours.am + ' (' + GFBusinessHours.nextDay + ')' );
				times.push(i + ":30 " + GFBusinessHours.am + ' (' + GFBusinessHours.nextDay + ')' );
			}
		}

		return times;
	};

	/**
	 * Generate the HTML for the days select
	 * @return {string} HTML of day options
	 */
	self.get_days_select = function() {

		var output = [];

		for (var i in self.days) {
			output.push('<option value="' + self.days[i] + '">' + self.days[i] + '</option>');
		}

		return output.join('');
	};

	/**
	 * Generate the HTML for the times select
	 * @return {string} HTML of time options
	 */
	self.get_times_select = function( with_after_midnight ) {

		var output_times = self.get_times_array( with_after_midnight );

		var output = [];

		for (var i in output_times ) {
			output.push('<option value="' + output_times[i] + '">' + output_times[i] + '</option>');
		}

		return output.join('');

	};

	/**
	 * Generate the <select>s for each field
	 * @return {[type]} [description]
	 */
	self.build_dropdowns = function() {

		$('.business_hours_item').each(function() {

			$(this).find('.item_fromtime,.item_totime,.item_day').empty();

			$(this).find('.item_day').append( self.get_days_select );

			// Without after midnight
			$(this).find('.item_fromtime').append( self.get_times_select( false ) );

			// With after midnight
			$(this).find('.item_totime').append( self.get_times_select( true ) );

			// Set defaults
			$('.item_fromtime').val('9:00 ' + GFBusinessHours.am);
			$('.item_totime').val('5:00 ' + GFBusinessHours.pm);

		});

	};

	/**
	 * Build an array of existing list values
	 * @return {[type]} [description]
	 */
	self.build_lists = function() {

		// Cycle through the Business Hours fields
		$('.business_hours_list_item').each(function() {

			var $item = $(this).parents('.business_hours_item:first');
			var field_id = $item.attr('rel');
			var value = $item.find('input:first').val();

			// Populate the list with the stored values
			self.lists[ field_id ] = $.parseJSON( value );
		});

	};

	/**
	 * Populating business hours list
	 * @return {void}
	 */
	self.populate_business_hours_list = function( obj ) {

		// List container HTML
		var $container = jQuery(obj).parents('.business_hours_item').find('.business_hours_list');

		// Clear it out
		$container.empty();

		// Build array of content
		var items = [];

		for (var i in self.business_hours_list) {
			items.push('<div class="business_hours_list_item"><strong>' + self.business_hours_list[i].day + '</strong>  <span>' + self.business_hours_list[i].fromtime + '</span> - <span>' + self.business_hours_list[i].totime + '</span><a href="#" class="business_hours_remove_button"><i class="dashicons dashicons-dismiss"></i></a></div>');
		}

		// Then append it to the list
		$container.append( items.join('') );

	};

	/**
	 * Update the hidden field value
	 *
	 * @param  {jQuery DOM Object} obj The item that was clicked to trigger the update
	 * @return {void}
	 */
	self.update_input_value = function( obj ) {

		var json_string = JSON.stringify( self.business_hours_list );

		$(obj)
			.parents('.business_hours_item')
			.find('input:first')
			.val( json_string );
	};

	/**
	 * check if business hours array contain an object
	 * @param  {object}
	 * @return {boolean}
	 */
	self.does_exact_day_exist = function(obj, list) {
		var i;
		for (i = 0; i < list.length; i++) {
			if (list[i].day === obj.day && list[i].fromtime === obj.fromtime && list[i].totime === obj.totime) {
				return true;
			}
		}
		return false;
	};

	$(self.init);

}(jQuery));