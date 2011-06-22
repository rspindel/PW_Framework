jQuery(document).ready(function($)
{
	var model = $("form.pw-form").attr('id');
	
	$("form.pw-form input[type=text], form.pw-form input[type=password], form.pw-form textarea").blur( function() {

		var field = $(this);
		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: 'action=' + model + '_form_validate&' + field.serialize(),
			success: function(response) {				
				if (response) {
					parent = field.parent().addClass('pw-error');
					errorWrapper = parent.children('.pw-error-message')
					if ( errorWrapper.size() > 0 ) {
						errorWrapper.html(response);
					} else {
						parent.append('<div class="pw-error-message">' + response + '</div>');
					}
				} else {
					field.parent().removeClass('pw-error').children('.pw-error-message').remove();					
				}
			}
		});
	})
});