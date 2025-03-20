/**
 * MailCleaner AntiSpam
 * @copyright 2015 Fastnet SA ; 2023, John Mertz
 * @license Mailcleaner Public License http://www.mailcleaner.net/open/licence_en.html
 */

/**
 * Newsletter's user interface
 * @author jpgrossglauser
 */
$(function() {
	$.ajax({
		async: true,
		type: "get",
		url: 'users/preferences/',
		dataType: 'json',
		success: function(data) {	
			if (1 == data.preferences.allow_newsletters) {
				$('#allowNewsletters').attr('checked', 'checked');
			} else {
				$('#allowNewsletters').removeAttr('checked');
			}
			$('#allowNewsletters').attr('disabled', false);		
		}
	});	
	
	/**
	 * Allow or deny new newsletters by default 
	 */
	$('.switch').on('click', function(event) {		
		$.ajax({
			async: true,
			type: "patch",
			url: 'users/preferences/newsletters',
			dataType: "json",
			data: {action:'switch'},
			success : function(data) {
				
			}
		});
	});	
	
	/**
	 * Allow selected elements
	 * @todo Refactor
	 */
	$('.allow').on('click', function(event) {
		event.preventDefault();
		
		$.ajax({
			async: true,
			type: "patch",
			url: 'users/newsletters',
			dataType: "json",
			data: $(this).closest('form').serialize(),
			success : function(data) {		
				
			}
		});
	});
	
	/**
	 * Deny selected elements
	 * @todo Refactor
	 */
	$('.deny').on('click', function(event) {
		event.preventDefault();
		
		$.ajax({
			async: true,
			type: "patch",
			url:'users/newsletters/',
			dataType: "json",
			data: $(this).closest('form').serialize(),
			success: function(data) {
				
			}
		});
	});	
});

