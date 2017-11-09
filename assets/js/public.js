/**
 * Part of Gravity Forms Business Hours plugin. This script is enqueued when in
 * admin page.
 *
 * globals jQuery, GFBusinessHours
 */
(function($){

	"use strict";

	var self = $.extend({

		/**
		 * Array of list arrays stored using the field ID as a key.
		 * @type {Array}
		 */
		lists: [],

		/**
		 * The current list being worked on. Populated by self.get_list()
		 * @type {Array}
		 */
		business_hours_list: []
	});

	self.init = function() {

		self.build_lists();

		self.setup_bindings();

		// Set the initial disabled items in the time picker
		$('.item_fromtime').trigger('change');

	};

	/**
	 * Bind clicks and changes for the field
	 * @return {[type]} [description]
	 */
	self.setup_bindings = function() {

		$(document).on('click', '.business_hours_remove_button', self.click_remove_button );

		$(document).on('click', '.business_hours_add_button', self.click_add_button );

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
			daylabel: $parent.find('.item_day option:selected').text(),
			fromtime: $parent.find('.item_fromtime').val(),
			fromtimelabel: $parent.find('.item_fromtime option:selected').text(),
			totime: $parent.find('.item_totime').val(),
			totimelabel: $parent.find('.item_totime option:selected').text(),
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

		// Currently selected option
		var $currently_selected_option = $day_field.find('option:selected');

		// Get the next option in the select
		var $next_option = $currently_selected_option.next('option');

		// If it doesn't exist, we're at the end of the week...cycle back around
		if( $next_option.length === 0 ) {
			$next_option = $day_field.find('option:first-child');
		}

		// Update the value
		$day_field.val( $next_option.val() );
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
			items.push('<div class="business_hours_list_item"><strong>' + self.business_hours_list[i].daylabel + '</strong>  <span>' + self.business_hours_list[i].fromtimelabel + '</span> - <span>' + self.business_hours_list[i].totimelabel + '</span><a href="#" class="business_hours_remove_button"><i class="dashicons dashicons-dismiss"></i></a></div>');
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