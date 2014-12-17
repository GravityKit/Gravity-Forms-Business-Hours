/**
 * check if business hours array contain an object
 * @param  {object}
 * @return {boolean}
 */
function containsObject(obj, list) {
    var i;
    for (i = 0; i < list.length; i++) {
        if (list[i].day === obj.day && list[i].fromtime === obj.fromtime && list[i].totime === obj.totime) {
            return true;
        }
    }
    return false;
}

/**
 * Populating business hours list
 * @return {void}
 */
function populate_business_hours_list(obj, business_hours_list) {
    jQuery(obj).parents('.business_hours_item').find('.business_hours_list').empty();

    for (var i in business_hours_list) {
        jQuery(obj).parents('.business_hours_item').find('.business_hours_list').append('<div  class="business_hours_list_item"><strong>' + business_hours_list[i].day + '</strong>  <span>' + business_hours_list[i].fromtime + '</span> - <span>' + business_hours_list[i].totime + '</span><a href="" class="business_hours_remove_button" >-</a></div>');
    }

    /**
     * attach event to remove button
     */
    jQuery('.business_hours_remove_button').unbind('click').on('click', function(e) {
        e.preventDefault();
        var index = jQuery(this).parents('.business_hours_list_item').index();
        if (index >= 0) {
            business_hours_list.splice(index, 1);
            jQuery(this).parents('.business_hours_item').find('input:first').val(JSON.stringify(business_hours_list));
            jQuery(this).parents('.business_hours_list_item').remove();

        }
    });

    jQuery(obj).parents('.business_hours_item').find('input:first').val(JSON.stringify(business_hours_list));
}
jQuery(function() {

    var days = [
        strings.day_1,
        strings.day_2,
        strings.day_3,
        strings.day_4,
        strings.day_5,
        strings.day_6,
        strings.day_7
    ];
    var times = [];
    var date = new Date().toISOString().split('T')[0];
    var lists = {};

    /**
     * Populating times lists
     */
    times.push("12:00 " + strings.am + " " + strings.midnight);
    times.push("12:30 " + strings.am);

    for (var i = 1; i < 12; i++) {
        times.push(i + ":00 " + strings.am);
        times.push(i + ":30 " + strings.am);
    }

    times.push("12:00 " + strings.pm + " " + strings.noon);
    times.push("12:30 " + strings.pm);

    for (var i = 1; i < 12; i++) {
        times.push(i + ":00 " + strings.pm);
        times.push(i + ":30 " + strings.pm);
    }

    jQuery('.business_hours_item').each(function() {

        jQuery(this).find('.item_fromtime,.item_totime,.item_day').empty();
        for (var i in days) {
            jQuery(this).find('.item_day').append('<option value="' + days[i] + '">' + days[i] + '</option>');
        }

        for (var i in times) {
            jQuery(this).find('.item_fromtime,.item_totime').append('<option value="' + times[i] + '">' + times[i] + '</option>');
        }
        //selecting defaults
        jQuery('.item_fromtime').val('9:00 ' + strings.am);
        jQuery('.item_totime').val('5:00 ' + strings.pm);

        /**
         * adding midnight closing feature
         */
        jQuery(this).find('.item_totime').append('<option value="12:00 ' + strings.am + ' ( ' + " " + strings.midnight + " " + strings.nextDay + '  )">12:00 ' + strings.am + ' ( ' + " " + strings.midnight + " " + strings.nextDay + '  )</option>');
        jQuery(this).find('.item_totime').append('<option value="12:30 ' + strings.am + ' ( ' + strings.nextDay + '  )">12:30 ' + strings.am + ' ( ' + strings.nextDay + '  )</option>');

        for (var i = 1; i < 7; i++) {
            jQuery(this).find('.item_totime').append('<option value="' + i + ':00 ' + strings.am + ' ( ' + strings.nextDay + ' )' + '">' + i + ':00 ' + strings.am + ' ( ' + strings.nextDay + ' )' + '</option>');
            jQuery(this).find('.item_totime').append('<option value="' + i + ':30 ' + strings.am + ' ( ' + strings.nextDay + ' )' + '">' + i + ':30 ' + strings.am + ' ( ' + strings.nextDay + ' )' + '</option>');
        }

    });

    jQuery('.business_hours_list_item').each(function() {
        lists[jQuery(this).parents('.business_hours_item:first').attr('rel')] = JSON.parse(jQuery(this).parents('.business_hours_item:first').find('input:first').val());
    });

    /**
     * detecting change on start time list & disabling ealier end times
     */
    jQuery('.item_fromtime').on('change', function() {
        jQuery(this).parents('.business_hours_item').find('.item_totime option[value="' + jQuery(this).val() + '"]').attr('disabled', 'disabled').prevAll('option').attr('disabled', 'disabled');
    });

    /**
     * attach event to add hours button
     */
    jQuery('.business_hours_add_button').on('click', function(e) {

        e.preventDefault();
        lists[jQuery(this).parents('.business_hours_item').attr('rel')] = lists[jQuery(this).parents('.business_hours_item').attr('rel')] || [];
        business_hours_list = lists[jQuery(this).parents('.business_hours_item').attr('rel')];	
        var item = {};
        item.day = jQuery(this).parents('.business_hours_item').find('.item_day').val();
        item.fromtime = jQuery(this).parents('.business_hours_item').find('.item_fromtime').val();
        item.totime = jQuery(this).parents('.business_hours_item').find('.item_totime').val();
        if (!containsObject(item, business_hours_list)) {
            business_hours_list.push(item);
            if (days.indexOf(item.day) == 6) {
                jQuery(this).parents('.business_hours_item').find('.item_day').val(days[0]);
            } else {
                jQuery(this).parents('.business_hours_item').find('.item_day').val(days[days.indexOf(item.day) + 1]);
            }
            populate_business_hours_list(this, business_hours_list);
        }
    });

    /**
     * attach event to remove button
     */
    jQuery('.business_hours_remove_button').unbind('click').on('click', function(e) {
        e.preventDefault();
        business_hours_list = lists[jQuery(this).parents('.business_hours_item').attr('rel')];
        var index = jQuery(this).parents('.business_hours_list_item').index();
        if (index >= 0) {
            business_hours_list.splice(index, 1);
            jQuery(this).parents('.business_hours_item').find('input:first').val(JSON.stringify(business_hours_list));
            jQuery(this).parents('.business_hours_list_item').remove();

        }
    });

});