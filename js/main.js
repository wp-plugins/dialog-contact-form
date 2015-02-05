jQuery(document).ready(function($){
   	$("form#dialog_contact").validate({
		rules: {
			fullname: {
				required: true,
				minlength: 3
			},
			email: {
				required: true,
				email: true
			},
			website: {
				url: true
			},
			phone: {
				minlength: 7,
				maxlength: 15
			},
			subject: {
				maxlength: 100
			},
			message: {
				required: true,
				minlength: 15
			},
			captcha: {
				required: true
			}
		}
	});
});