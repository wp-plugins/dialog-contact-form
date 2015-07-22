<?php
/*
 * Plugin Name: 	Dialog Contact Form
 * Plugin URI: 		http://wordpress.org/plugins/dialog-contact-form/
 * Description: 	This is a very simple contact form with Captcha validation.
 * Version: 		1.1.0
 * Author: 			Sayful Islam, Sayful-IT
 * Author URI: 		http://www.sayfulit.com
 * Text Domain: 	dialogcf
 * Domain Path: 	/languages/
 * License: 		GPL2
*/
if ( !class_exists('Dialog_Contact_Form')):

class Dialog_Contact_Form {

	protected static $instance = null;

	public function __construct(){
		add_action( 'admin_init', array( $this, 'settings_init') );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_style') );
		add_shortcode( 'dialog_contact_form', array( $this, 'shortcode') );
		
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

		$this->options = self::get_options();

		if ( 'show' == $this->options['display_dialog'] ){
			add_action('wp_footer', array( $this, 'dialog') );
		}

		if ( self::is_session_started() === FALSE ) session_start();
	}

	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function enqueue_scripts() {
		global $post;
		$this->options = self::get_options();

		if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dialog_contact_form') || 'show' == $this->options['display_dialog'] ) {
			
			//wp_enqueue_script('jquery-validate',plugins_url( '/js/jquery.validate.min.js', __FILE__ ),array( 'jquery' ));
			//wp_enqueue_script('dialog_contact_main',plugins_url( '/js/main.js' , __FILE__ ),array( 'jquery', 'jquery-validate' ));

			wp_enqueue_style('dialog_contact_modal',plugins_url( '/css/style.css' , __FILE__ ));

			if ( 'show' == $this->options['display_dialog'] ){

				wp_enqueue_style('jquery-ui',plugins_url( '/css/jquery-ui.min.css' , __FILE__ ));
				wp_enqueue_script( 'jquery-ui-dialog' );
			}
		}
	}

	public function admin_style($hook_suffix){

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'dialog_contact_form_color', plugins_url('/js/admin.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
		wp_enqueue_style('dialog_contact_form_admin',plugins_url( '/css/admin.css' , __FILE__ ));
	}

	public function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'options-general.php?page=dialogcf_options_page' ) . '">' . __( 'Settings', 'dialogcf' ) . '</a>'
		);

		return array_merge( $plugin_links, $links );
	}

	public static function get_options(){
		$options_array = array(
	      	'email' 			=> get_option( 'admin_email' ),
	      	'field_web' 		=> '',
	      	'field_phone' 		=> '',
	      	'field_sub' 		=> '',
	      	'field_captcha' 	=> '',
	      	'display_dialog' 	=> 'show',
	      	'label_name' 		=> __('Name *', 'dialogcf'),
	      	'label_email' 		=> __('Email *', 'dialogcf'),
	      	'label_url' 		=> __('Website', 'dialogcf'),
	      	'label_phone' 		=> __('Phone', 'dialogcf'),
	      	'label_sub' 		=> __('Subject', 'dialogcf'),
	      	'label_msg' 		=> __('Message *', 'dialogcf'),
	      	'label_capt' 		=> __('Enter captcha code *', 'dialogcf'),
	      	'label_submit' 		=> __('Send Message', 'dialogcf'),
	      	'place_name' 		=> __('Name', 'dialogcf'),
	      	'place_email' 		=> __('mail@example.com', 'dialogcf'),
	      	'place_url' 		=> __('http://example.com', 'dialogcf'),
	      	'place_phone' 		=> __('xxx-xxxx-xxxx', 'dialogcf'),
	      	'place_sub' 		=> __('Please enter the subject of your message here.', 'dialogcf'),
	      	'place_msg' 		=> __('Please enter your message here.', 'dialogcf'),
	      	'place_capt' 		=> __('Enter captcha code', 'dialogcf'),
	      	'err_name' 			=> __('Please enter at least 3 characters.', 'dialogcf' ),
	      	'err_email' 		=> __('Email address seems invalid.', 'dialogcf' ),
	      	'err_url' 			=> __('URL seems invalid.', 'dialogcf' ),
	      	'err_message' 		=> __('Please enter at least 15 characters.', 'dialogcf' ),
	      	'err_captcha' 		=> __('Your entered code is incorrect.', 'dialogcf' ),
	      	'msg_success' 		=> __('Your message was sent successfully. Thanks.', 'dialogcf' ),
	      	'msg_fail' 			=> __('Please check the error below.', 'dialogcf' ),
	      	'msg_subject' 		=> __('Someone sent you a message from ', 'dialogcf' ).get_bloginfo('name'),
	      	'msg_body' 			=> __('This mail is sent via contact form ', 'dialogcf' ).get_bloginfo('name'),
	      	'dialog_button' 	=> __('Leave a message', 'dialogcf' ),
	      	'dialog_title' 		=> __('Contact us', 'dialogcf' ),
	      	'dialog_width' 		=> 600,
	      	'dialog_color' 		=> '#ea632d',
	    );
		$options = wp_parse_args(get_option( 'dialogcf_options' ), $options_array);
	   	return $options;
	}

	public function settings_init(){
	    register_setting( 'dialogcf_options', 'dialogcf_options' );
	}

	function admin_menu () {
		add_options_page( __('Dialog Contact Form', 'dialogcf'), __('Dialog Contact Form', 'dialogcf'),'manage_options','dialogcf_options_page', array( $this, 'settings_page' ) );
	}
	function  settings_page () {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'dialogcf' ) );
		}
		?>
		<div class="wrap">
		<h2><?php _e('Dialog Contact Form', 'dialogcf'); ?></h2>
		<hr><p><?php printf( __('To use contact form as dialog, just choose "Show Dialog" from option. If you want to use contact form for page, just copy this shortcode %s and paste where you want to show it.', 'dialogcf'), '<code>[dialog_contact_form]</code>') ?></p><hr>

		<form method="post" action="options.php">

			<?php 
				$options = self::get_options();
				settings_fields( 'dialogcf_options' );
			?>
		    <table class="form-table">
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Mail Receiver Email', 'dialogcf'); ?></label>
		        	</th>
		        	<td>
		        		<input type="email" class="regular-text ltr" name="dialogcf_options[email]" value="<?php esc_attr_e($options['email']); ?>">
		        	</td>
		        </tr>
		         
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Show Form Fields', 'dialogcf'); ?></label>
		        	</th>
			        <td>
			        	<label for="field_web">
			        		<input type="checkbox" id="field_web" name="dialogcf_options[field_web]" value="on" <?php checked( $options['field_web'], 'on' ); ?>><?php _e('Website', 'dialogcf'); ?>
			        	</label>
			        	<label for="field_phone">
			        		<input type="checkbox" id="field_phone" name="dialogcf_options[field_phone]" value="on" <?php checked( $options['field_phone'], 'on' ); ?>><?php _e('Phone', 'dialogcf'); ?>
			        	</label>
			        	<label for="field_sub">
			        		<input type="checkbox" id="field_sub" name="dialogcf_options[field_sub]" value="on" <?php checked( $options['field_sub'], 'on' ); ?>><?php _e('Subject', 'dialogcf'); ?>
			        	</label>
			        	<label for="field_captcha">
			        		<input type="checkbox" id="field_captcha" name="dialogcf_options[field_captcha]" value="on" <?php checked( $options['field_captcha'], 'on' ); ?>><?php _e('Captcha', 'dialogcf'); ?>
			        	</label>
			        </td>
		        </tr>
		         
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Show or Hide Dialog Form', 'dialogcf'); ?></label>
		        	</th>
			        <td>
			        	<input type="radio" name="dialogcf_options[display_dialog]" value="show" <?php checked( $options['display_dialog'], 'show' ); ?> ><?php _e('Show Dialog', 'dialogcf'); ?><br>
			        	<input type="radio" name="dialogcf_options[display_dialog]" value="hide" <?php checked( $options['display_dialog'], 'hide' ); ?> ><?php _e('Hide Dialog', 'dialogcf'); ?><br>
			        </td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Form Label Text', 'dialogcf'); ?></label>
		        	</th>
			        <td class="dcf_label">
			        	<label for="label_name"><?php _e('Label for Name', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_name]" id="label_name" value="<?php if(isset($options['label_name'])) echo $options['label_name']; ?>" class="regular-text"><br>
			        	
			        	<label for="label_email"><?php _e('Label for Email', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_email]" id="label_email" value="<?php if(isset($options['label_email'])) echo $options['label_email']; ?>" class="regular-text"><br>
			        	
			        	<label for="label_url"><?php _e('Label for Website', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_url]" id="label_url" value="<?php if(isset($options['label_url'])) echo $options['label_url']; ?>" class="regular-text"><br>
			        	
			        	<label for="label_phone"><?php _e('Label for Phone', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_phone]" id="label_phone" value="<?php if(isset($options['label_phone'])) echo $options['label_phone']; ?>" class="regular-text"><br>

			        	<label for="label_sub"><?php _e('Label for Subject', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_sub]" id="label_sub" value="<?php if(isset($options['label_sub'])) echo $options['label_sub']; ?>" class="regular-text"><br>

			        	<label for="label_msg"><?php _e('Label for Message', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_msg]" id="label_msg" value="<?php if(isset($options['label_msg'])) echo $options['label_msg']; ?>" class="regular-text"><br>

			        	<label for="label_capt"><?php _e('Label for Captcha', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_capt]" id="label_capt" value="<?php if(isset($options['label_capt'])) echo $options['label_capt']; ?>" class="regular-text"><br>

			        	<label for="label_submit"><?php _e('Label for Submit button', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[label_submit]" id="label_submit" value="<?php if(isset($options['label_submit'])) echo $options['label_submit']; ?>" class="regular-text"><br>
			        </td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Form Placeholder Text', 'dialogcf'); ?></label>
		        	</th>
		        	<td class="dcf_label">
			        	<label for="place_name"><?php _e('Placeholder text for Name', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_name]" id="place_name" value="<?php if(isset($options['place_name'])) echo $options['place_name']; ?>" class="regular-text"><br>
			        	
			        	<label for="place_email"><?php _e('Placeholder text for Email', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_email]" id="place_email" value="<?php if(isset($options['place_email'])) echo $options['place_email']; ?>" class="regular-text"><br>
			        	
			        	<label for="place_url"><?php _e('Placeholder text for Website', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_url]" id="place_url" value="<?php if(isset($options['place_url'])) echo $options['place_url']; ?>" class="regular-text"><br>
			        	
			        	<label for="place_phone"><?php _e('Placeholder text for Phone', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_phone]" id="place_phone" value="<?php if(isset($options['place_phone'])) echo $options['place_phone']; ?>" class="regular-text"><br>

			        	<label for="place_sub"><?php _e('Placeholder text for Subject', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_sub]" id="place_sub" value="<?php if(isset($options['place_sub'])) echo $options['place_sub']; ?>" class="regular-text"><br>

			        	<label for="place_msg"><?php _e('Placeholder text for Message', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_msg]" id="place_msg" value="<?php if(isset($options['place_msg'])) echo $options['place_msg']; ?>" class="regular-text"><br>

			        	<label for="place_capt"><?php _e('Placeholder text for Captcha', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[place_capt]" id="place_capt" value="<?php if(isset($options['place_capt'])) echo $options['place_capt']; ?>" class="regular-text"><br>
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Dialog', 'dialogcf'); ?></label>
		        	</th>
		        	<td class="dcf_label">
			        	<label for="dialog_button"><?php _e('Dialog button text', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[dialog_button]" id="dialog_button" value="<?php if(isset($options['dialog_button'])) echo $options['dialog_button']; ?>" class="regular-text"><br>

			        	<label for="dialog_title"><?php _e('Dialog title text', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[dialog_title]" id="dialog_title" value="<?php if(isset($options['dialog_title'])) echo $options['dialog_title']; ?>" class="regular-text"><br>

			        	<label for="dialog_width"><?php _e('Dialog width', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[dialog_width]" id="dialog_width" value="<?php if(isset($options['dialog_width'])) echo $options['dialog_width']; ?>" class="regular-text"><br>

			        	<label for="dialog_color"><?php _e('Dialog button color', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[dialog_color]" id="dialog_color" value="<?php if(isset($options['dialog_color'])) echo $options['dialog_color']; ?>" class="colorpicker" data-default-color="#ea632d"><br>
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row">
		        		<label><?php _e('Messages', 'dialogcf'); ?></label>
		        	</th>
			        <td class="dcf_label_more">
			        	<label for="err_name"><?php _e('Validation errors occurred for name validation', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[err_name]" id="err_name" value="<?php if(isset($options['err_name'])) echo $options['err_name']; ?>" class="regular-text"><br>

			        	<label for="err_email"><?php _e('Validation errors occurred for email validation', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[err_email]" id="err_email" value="<?php if(isset($options['err_email'])) echo $options['err_email']; ?>" class="regular-text"><br>

			        	<label for="err_url"><?php _e('Validation errors occurred for URL validation', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[err_url]" id="err_url" value="<?php if(isset($options['err_url'])) echo $options['err_url']; ?>" class="regular-text"><br>

			        	<label for="err_message"><?php _e('Validation errors occurred for Message validation', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[err_message]" id="err_message" value="<?php if(isset($options['err_message'])) echo $options['err_message']; ?>" class="regular-text"><br>

			        	<label for="err_captcha"><?php _e('Validation errors occurred for captcha validation', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[err_captcha]" id="err_captcha" value="<?php if(isset($options['err_captcha'])) echo $options['err_captcha']; ?>" class="regular-text"><br>

			        	<label for="msg_success"><?php _e('Sender\'s message was sent successfully', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[msg_success]" id="msg_success" value="<?php if(isset($options['msg_success'])) echo $options['msg_success']; ?>" class="regular-text"><br>

			        	<label for="msg_fail"><?php _e('Sender\'s message was failed to send', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[msg_fail]" id="msg_fail" value="<?php if(isset($options['msg_fail'])) echo $options['msg_fail']; ?>" class="regular-text"><br>

			        	<label for="msg_subject"><?php _e('Message subject text.', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[msg_subject]" id="msg_subject" value="<?php if(isset($options['msg_subject'])) echo $options['msg_subject']; ?>" class="regular-text"><br>

			        	<label for="msg_body"><?php _e('Message body text.', 'dialogcf'); ?></label>
			        	<input type="text" name="dialogcf_options[msg_body]" id="msg_body" value="<?php if(isset($options['msg_body'])) echo $options['msg_body']; ?>" class="regular-text"><br>
			        </td>
		        </tr>
		    </table>
		    
		    <?php submit_button(); ?>

		</form>
		</div>
		<?php
	}

	public function shortcode(){

		$options = self::get_options();

		if ( isset( $_POST['dialog_contact_form'] ) && wp_verify_nonce( $_POST['dialog_contact_form'], 'dialog_contact_form' )) {
			
			$phone		= (isset($_POST['phone'])) ? sanitize_text_field($_POST['phone']) : '';
			$website    = (isset($_POST['website'])) ? esc_url($_POST['website']) : '';
			$msgsubject = (isset($_POST['subject'])) ? sanitize_text_field($_POST['subject']) : '';

			// Validate fullname with PHP
			if ( strlen($_POST['fullname']) < 3 ) {
				$nameErr = $options['err_name'];
		        $hasError = true;
			}else {
				$fullname		= sanitize_text_field($_POST['fullname']);
			}

			// Validate email address with PHP
			if(!is_email($_POST['email'])){
				$emailErr = $options['err_email'];
		        $hasError = true;
			} else {
				$email = sanitize_email($_POST['email']);
			}

			// Validate website with PHP
			if(isset($options['field_web']) && $options['field_web'] == 'on' ){
				if(!empty($website)){
					if (!filter_var($_POST['website'], FILTER_VALIDATE_URL)) {
						$websiteErr = $options['err_url'];
			        	$hasError = true;
					}
				}
			}

			// Validate message with PHP
			if ( strlen($_POST['message']) < 15 ) {
				$messageErr = $options['err_message'];
		        $hasError = true;
			} else {
				$message = esc_textarea($_POST['message']);
			}

			// Validate Captcha code with PHP
			if(isset($options['field_captcha']) && $options['field_captcha'] == 'on' ){

				$captcha = sanitize_text_field($_POST['captcha']);

				if($captcha != $_SESSION['dialog_contact_form']){
					$captchaErr = $options['err_captcha'];
			        $hasError = true;
				}
			}

			// If all validation are true than send mail
			if ( !isset($hasError) ) {

				$to = (isset($options['email'])) ? $options['email'] : get_option( 'admin_email' );

		        $subject 	= (isset($options['msg_subject'])) ? $options['msg_subject'] : '';
		        $website 	= (isset($website)) ? $website : '';
		        $phone 		= (isset($phone)) ? $phone : '';
		        $msgsubject = (isset($msgsubject)) ? $msgsubject : '';

		        $body  = "Name: $fullname \nEmail: $email \nWebsite: $website \nPhone: $phone \n\nSubject: $msgsubject \n\nMessage: $message \n\n";
		        $body .= "--\n";
		        $body .= $options['msg_body']."\n";
		        $body .= home_url();

				$headers = 'From: '.$fullname.' <'.$email.'>' . "\r\n" . 'Reply-To: ' . $email;

				wp_mail($to, $subject, $body, $headers);
				$emailSent = true;
			}

			// Show message to user
			if ( isset($emailSent) && $emailSent == true ) {
				$sendMailSucc = $options['msg_success'];
			} else {
				$sendMailFail = $options['msg_fail'];
			}

		}

		ob_start();
		
		if( isset($_POST['send_mail']) && isset($sendMailSucc)): ?>

			<div class="shapla-alert shapla-alert--green">
				<?php if(isset($sendMailSucc)) echo $sendMailSucc; ?>
			</div>

		<?php else: ?>
			<form id="dialog_contact" action="<?php the_permalink(); ?>" method="post">

				<?php if(isset($sendMailFail)){ ?>
					<div class="shapla-alert shapla-alert--red">
				 		<?php echo $sendMailFail; ?>
					</div>
				<?php } ?>

				<p class="fields fullname">
					<label for="fullname">
						<?php echo (isset($options['label_name'])) ? $options['label_name'] : ''; ?>
					</label>
					<input type="text" id="fullname" name="fullname" value="<?php if(isset($fullname)) echo $fullname;?>" placeholder="<?php if(isset($options['place_name'])) echo $options['place_name']; ?>" required>
					<?php if(isset($nameErr)) { ?>
						<span class="error"><?php echo $nameErr; ?></span>
					<?php } ?>
				</p>

				<p class="fields email">
					<label for="email">
						<?php echo (isset($options['label_email'])) ? $options['label_email'] : ''; ?>
					</label>
					<input type="email" id="email" name="email" value="<?php if(isset($_POST['email'])) echo $_POST['email'];?>" placeholder="<?php if(isset($options['place_email'])) echo $options['place_email']; ?>" required>
					<?php if(isset($emailErr)) { ?>
						<span class="error"><?php echo $emailErr; ?></span>
					<?php } ?>
				</p>

				<?php if(isset($options['field_web']) && $options['field_web'] == 'on' ): ?>
				<p class="fields website">
					<label for="website">
						<?php echo (isset($options['label_url'])) ? $options['label_url'] : ''; ?>
					</label>
					<input type="url" id="website" name="website" value="<?php if(isset($_POST['website'])) echo $_POST['website'];?>" placeholder="<?php if(isset($options['place_url'])) echo $options['place_url']; ?>" >
					<?php if(isset($websiteErr)) { ?>
						<span class="error"><?php echo $websiteErr; ?></span>
					<?php } ?>
				</p>
				<?php endif; ?>

				<?php if(isset($options['field_phone']) && $options['field_phone'] == 'on' ): ?>
				<p class="fields phone">
					<label for="phone">
						<?php echo (isset($options['label_phone'])) ? $options['label_phone'] : ''; ?>
					</label>
					<input type="text" id="phone" name="phone" value="<?php if(isset($_POST['phone'])) echo $_POST['phone'];?>" placeholder="<?php if(isset($options['place_phone'])) echo $options['place_phone']; ?>" >
				</p>
				<?php endif; ?>

				<?php if(isset($options['field_sub']) && $options['field_sub'] == 'on' ): ?>
				<p class="fields subject">
					<label for="subject">
						<?php echo (isset($options['label_sub'])) ? $options['label_sub'] : ''; ?>
					</label>
					<input type="text" id="subject" name="subject" value="<?php if(isset($_POST['subject'])) echo $_POST['subject'];?>" placeholder="<?php if(isset($options['place_sub'])) echo $options['place_sub']; ?>" >
				</p>
				<?php endif; ?>

				<p class="fields message">
					<label for="message">
						<?php echo (isset($options['label_msg'])) ? $options['label_msg'] : ''; ?>
					</label>
					<textarea name="message" id="message" rows="5" placeholder="<?php if(isset($options['place_msg'])) echo $options['place_msg']; ?>" required><?php if(isset($message)) echo $message; ?></textarea>
					<?php if(isset($messageErr)) { ?>
						<span class="error"><?php echo $messageErr; ?></span>
					<?php } ?>
				</p>

				<?php if(isset($options['field_captcha']) && $options['field_captcha'] == 'on' ): ?>
				<p class="fields captcha">
					<label for="captcha">
						<?php echo (isset($options['label_capt'])) ? $options['label_capt'] : ''; ?>
					</label>
					<input type="text" name="captcha" id="captcha" placeholder="<?php if(isset($options['place_capt'])) echo $options['place_capt']; ?>" autocomplete="off" required>
					<?php if(isset($captchaErr)) { ?>
						<span class="error"><?php echo $captchaErr; ?></span>
					<?php } ?>
				</p>

				<p class="fields captcha-img">
				  	<img src="<?php echo plugins_url( 'captcha.php', __FILE__ ); ?>">
				</p>
				<?php endif; ?>

				<p class="fields submit">
				  	<input type="submit" name="send_mail" id="send_mail" value="<?php echo (isset($options['label_submit'])) ? $options['label_submit'] : 'Submit'; ?>">
				  	<?php wp_nonce_field( 'dialog_contact_form', 'dialog_contact_form' ); ?>
				</p>
			</form>
		<?php endif;
		return ob_get_clean();

	}

	function dialog(){
		$options = self::get_options();
		?>
		<div id="dialog" title="<?php echo $options['dialog_title']; ?>">
			<?php echo do_shortcode( '[dialog_contact_form]' ); ?>
		</div>
		<p><a href="#" id="dialog-link">
			<img width="25" src="<?php echo plugins_url( 'img/message.png', __FILE__ ); ?>">
			<?php echo $options['dialog_button']; ?>
		</a></p>
		<!-- Modal End -->
		<style type="text/css">
			#dialog-link {
				background-color: <?php echo (isset($options['dialog_color'])) ? $options['dialog_color'] : '#ea632d'; ?>;
			}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$( "#dialog" ).dialog({
					autoOpen: <?php echo (isset($_POST['send_mail'])) ? 'true' : 'false' ; ?>,
					width: <?php echo (isset($options['dialog_width'])) ? $options['dialog_width'] : '600' ; ?>
				});

				// Link to open the dialog
				$( "#dialog-link" ).click(function( event ) {
					$( "#dialog" ).dialog( "open" );
					event.preventDefault();
				});
			});
		</script>
		<?php
	}

	private static function is_session_started(){
	    if ( php_sapi_name() !== 'cli' ) {
	        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
	            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
	        } else {
	            return session_id() === '' ? FALSE : TRUE;
	        }
	    }
	    return FALSE;
	}
}
add_action( 'plugins_loaded', array( 'Dialog_Contact_Form', 'get_instance' ) );
endif;

function dialog_contact_form_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'options-general.php?page=dialogcf_options_page' ) ) );
    }
}
add_action( 'activated_plugin', 'dialog_contact_form_activation_redirect' );