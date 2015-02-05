<?php
/*
 * Plugin Name: Dialog Contact Form
 * Plugin URI: http://wordpress.org/plugins/dialog-contact-form/
 * Description: This is a very simple contact form. Emails will be sent to the site admin email by default.
 * Version: 1.0.0
 * Author: Sayful Islam
 * Author URI: http://www.sayful.net
 * Text Domain: dialogcf
 * Domain Path: /languages/
 * License: GPL2
*/

/* Adding Latest jQuery for Wordpress plugin */
function dialog_contact_form_scripts() {
	if ( ! is_admin() ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('dialog_contact_validate','https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.13.1/jquery.validate.min.js',array( 'jquery' ));
		wp_enqueue_script('dialog_contact_modal',plugins_url( '/js/modal.js' , __FILE__ ),array( 'jquery' ));
		wp_enqueue_script('dialog_contact_main',plugins_url( '/js/main.js' , __FILE__ ),array( 'jquery' ));

		wp_enqueue_style('dialog_contact_modal',plugins_url( '/css/modal.css' , __FILE__ ));
	}
}
add_action('init', 'dialog_contact_form_scripts');

session_start();

function dialog_contact_form_shortcode(){

if ( isset($_POST['send_mail'])) {

	$fullname		= sanitize_text_field($_POST['fullname']);
	$website 		= esc_url($_POST['website']);
	$email			= sanitize_email($_POST['email']);
	$phone			= sanitize_text_field($_POST['phone']);
	$message 		= esc_textarea($_POST['message']);
	$msgsubject 	= sanitize_text_field($_POST['subject']);
	$captcha 		= sanitize_text_field($_POST['captcha']);

	// Validate fullname with PHP
	if ( strlen($fullname) < 3 ) {
		$nameErr = __( 'Please enter at least 3 characters.', 'dialogcf' );
        $hasError = true;
	}

	// Validate email address with PHP
	if(!is_email($email)){
		$emailErr = __( 'Please specify a valid email address.', 'dialogcf' );
        $hasError = true;
	}

	// Validate website with PHP
	if(!empty($website)){
		if (!filter_var($website, FILTER_VALIDATE_URL)) {
			$websiteErr = __( 'Please specify a valid URL.', 'dialogcf' );
        	$hasError = true;
		}
	}

	// Validate phone with PHP
	if(!empty($phone)){
		if ( strlen($phone) < 7 ) {
			$phoneErr = __( 'Please specify a valid phone number.', 'dialogcf' );
        	$hasError = true;
		}
	}

	// Validate message with PHP
	if ( strlen($message) < 15 ) {
		$messageErr = __( 'Please enter at least 15 characters.', 'dialogcf' );
        $hasError = true;
	}

	// 
	if($captcha != $_SESSION['security_code']){
		$captchaErr = __( 'Please verify that you typed in the correct code.', 'dialogcf' );
        $hasError = true;
	}

	// If all validation are true than send mail
	if ( !isset($hasError) ) {

		$admin_email 	= get_option( 'admin_email' );

        $subject = 'Someone sent you a message from '.get_bloginfo('name');

        $body = "Name: $fullname \nEmail: $email \nWebsite: $website \nPhone: $phone \n\nSubject: $msgsubject \n\nMessage: $message \n\n";
        $body .= "--\n";
        $body .= "This mail is sent via contact form on ".get_bloginfo('name')."\n";
        $body .= home_url();

		$headers = 'From: '.$fullname.' <'.$email.'>' . "\r\n" . 'Reply-To: ' . $email;

		$emailSent 		= wp_mail($admin_email, $subject, $body, $headers);
	}

	// Show message to user
	if ( $emailSent ) {
		$sendMailSucc = __( 'Your email has been sent successful. Thank you for contact with us.', 'dialogcf' );
	} else {
		$sendMailFail = __('Something is wrong. Please check the error bellow.', 'dialogcf' );
	}

}

ob_start();
?>
<?php if( isset($_POST['send_mail']) && isset($sendMailSucc)): ?>
	<div class="confirmation-success text-center">
		<?php if(isset($sendMailSucc)){ echo $sendMailSucc; } ?>
	</div>
	<?php else: ?>
	<div class="confirmation-fail text-center">
		<?php if(isset($sendMailFail)){ echo $sendMailFail; } ?>
	</div>
	<form id="dialog_contact" action="<?php the_permalink(); ?>" method="post">
		<div class="text-center">
		    <p><?php _e('Your email address will not be published. Required fields are marked *', 'dialogcf'); ?></p>
		</div>
		<div class="row">
			<div class="col-2">
				<label for="fullname">
					<?php _e('Your Name *', 'dialogcf'); ?>
				</label>
				<input type="text" class="form-control" name="fullname" id="fullname" placeholder="<?php _e('Full Name'); ?>" value="<?php if(isset($fullname)){ echo $fullname; } ?>">
				<span class="error"><?php if(isset($nameErr)){ echo $nameErr; } ?></span>
			</div>
			<div class="col-2">
				<label for="email"><?php _e('Your Email *', 'dialogcf'); ?></label>
				<input type="email" class="form-control" name="email" id="email" placeholder="<?php _e('mail@example.com'); ?>" value="<?php if(isset($email)){ echo $email; } ?>">
				<span class="error"><?php if(isset($emailErr)){ echo $emailErr; } ?></span>
			</div>
		</div>
		<div class="row">
			<div class="col-2">
				<label for="website"><?php _e('Website', 'dialogcf'); ?></label>
				<input type="text" class="form-control" name="website" id="website" placeholder="<?php _e('http://example.com/'); ?>" value="<?php if(isset($website)){ echo $website; } ?>">
				<span class="error"><?php if(isset($websiteErr)){ echo $websiteErr; } ?></span>
			</div>
			<div class="col-2">
				<label for="phone"><?php _e('Phone', 'dialogcf'); ?></label>
				<input type="text" class="form-control" name="phone" id="phone" placeholder="xxx-xxxx-xxxx" value="<?php if(isset($phone)){ echo $phone; } ?>">
				<span class="error"><?php if(isset($phoneErr)){ echo $phoneErr; } ?></span>
			</div>
		</div>
		<div class="row">
			<div class="col-1">
				<label for="subject"><?php _e('Subject', 'dialogcf'); ?></label>
				<input type="text" class="form-control" name="subject" id="subject" placeholder="<?php _e('What would you like to contact us?'); ?>" value="<?php if(isset($msgsubject)){ echo $msgsubject; } ?>"  autocomplete="off">
			</div>
		</div>
		<div class="row">
			<div class="col-1">
				<label for="message"><?php _e('Your Message *', 'dialogcf'); ?></label>
				<textarea class="form-control" name="message" id="message" rows="5" placeholder="<?php _e('Tell us detail about your contact ...', 'dialogcf'); ?>"><?php if(isset($message)){ echo $message; } ?></textarea>
				<span class="error"><?php if(isset($messageErr)){ echo $messageErr; } ?></span>
			</div>
		</div>
		<div class="row">
			<div class="col-2">
				<label for="fullname">
					<?php _e('Enter captcha code *', 'dialogcf'); ?>
				</label>
				<input type="text" class="form-control" name="captcha" id="captcha" autocomplete="off">
				<span class="error"><?php if(isset($captchaErr)){ echo $captchaErr; } ?></span>
			</div>
			<div class="col-2">
		  		<img src="<?php echo plugins_url( 'captcha.php?width=275&height=50&characters=6', __FILE__ ); ?>" />
			</div>
		</div>
		<div class="row"><div class="col-1">
			<input type="submit" class="modal-btn" name="send_mail" id="send_mail" value="<?php _e('Send', 'dialogcf'); ?>">
		</div></div>
	</form>
	<?php endif; ?>

<?php
ob_end_flush();

}
add_shortcode( 'dialog_contact_form', 'dialog_contact_form_shortcode' );

function dialog_contact_form_output(){
	?>
	<!-- Button trigger modal -->
	<div id="leave_message">
		<button type="button" id="leave_message_button" data-toggle="modal" data-target="#dialogContactForm">
			<span class="image">
				<img width="25" src="<?php echo plugins_url( 'img/message.png', __FILE__ ); ?>">
			</span>
			<span class="text"><?php _e('Leave a message', 'dialogcf'); ?></span>
		</button>
	</div>

	<!-- Modal Start -->
	<div class="modal fade" id="dialogContactForm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	  	<div class="modal-dialog">
	    	<div class="modal-content">
		      	<div class="modal-header">
		        	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        	<h4 class="modal-title" id="myModalLabel"><?php _e('Contact us', 'dialogcf'); ?></h4>
		      	</div><!-- .modal-header -->
		      	<div class="modal-body">
				    <?php dialog_contact_form_shortcode(); ?>
		      	</div><!-- .modal-body -->
		      	<div class="modal-footer">
		        	<button type="button" class="modal-btn" data-dismiss="modal"><?php _e('Close', 'dialogcf'); ?></button>
		      	</div><!-- .modal-footer -->
	    	</div>
	  	</div>
	</div>
	<!-- Modal End -->
	<script type="text/javascript">
		jQuery(document).ready(function($){
			<?php if (isset($_POST['send_mail'])): ?>
				$('#dialogContactForm').modal({ show: true });
			<?php else: ?>
				$('#dialogContactForm').modal({ show: false });
			<?php endif; ?>
		});
	</script>
	<?php
}
add_action('wp_footer','dialog_contact_form_output');