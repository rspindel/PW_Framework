jQuery(document).ready(function($)
{
	
	
	$("form.pw-form input").blur( function() {
		
		// console.log( $('form').serializeArray() );
		
		
		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: 'action=pw-ajax-validate&' + $(this).serialize(),
			success: function(response) {
				$('.error-reporting').html(response);
			}
		});
		
	})
	
	
		
});