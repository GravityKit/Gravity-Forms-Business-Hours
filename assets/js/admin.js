jQuery(function(){
	//updating current status ofr the business [opening or closing]
	var weekdayNames = [
		GFBusinessHours.day_1,
		GFBusinessHours.day_2,
		GFBusinessHours.day_3,
		GFBusinessHours.day_4,
		GFBusinessHours.day_5,
		GFBusinessHours.day_6,
		GFBusinessHours.day_7
	];
	var now= new Date();
	var weekday = weekdayNames[now.getDay()];
	var date = new Date().toISOString().split('T')[0];

	jQuery('strong[rel="'+weekday+'"]').each(function(){

		if(jQuery(this).next('span:first').next('span:first').text().indexOf('(')==-1){

			var fromdate_time =new Date(date+" "+jQuery(this).next('span:first').text());
			var todate_time =new Date(date+" "+jQuery(this).next('span:first').next('span:first').text());
			if(now>=fromdate_time && now<=todate_time){
				jQuery(this).parents('div:first').append('<span class="open_label"> '+GFBusinessHours.open+' </span>');
			}
		}else{

			jQuery(this).parents('div:first').append('<span class="open_label"> '+GFBusinessHours.open+' </span>');
		}
	});
});