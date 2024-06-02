<?php

/** 
 * Taken from Listeo 1.9.60
 */

// get user email
if (is_user_logged_in()) {

	$current_user = wp_get_current_user();
	$email = $current_user->user_email;
	$first_name =  $current_user->first_name;
	$last_name =  $current_user->last_name;
	$phone =  get_user_meta($current_user->ID, 'billing_phone', true);
	$billing_address_1 =  get_user_meta($current_user->ID, 'billing_address_1', true);
	$billing_address_2 =  get_user_meta($current_user->ID, 'billing_address_2', true);
	$billing_postcode =  get_user_meta($current_user->ID, 'billing_postcode', true);
	$billing_company =  get_user_meta($current_user->ID, 'billing_company', true);
	$billing_state =  get_user_meta($current_user->ID, 'billing_state', true);
	$billing_city =  get_user_meta($current_user->ID, 'billing_city', true);
	$message =  false;
} else {

	$email  = (isset($_POST['email'])) ? $_POST['email'] : false;
	$first_name =  (isset($_POST['firstname'])) ? $_POST['firstname'] : false;
	$last_name =  (isset($_POST['lastname'])) ? $_POST['lastname'] : false;
	$phone =  (isset($_POST['phone'])) ? $_POST['phone'] : false;
	$message =  (isset($_POST['message'])) ? $_POST['message'] : false;
	$billing_address_1 =  (isset($_POST['billing_address_1'])) ? $_POST['billing_address_1'] : false;
	$billing_address_2 =  (isset($_POST['billing_address_2'])) ? $_POST['billing_address_1'] : false;
	$billing_company =  (isset($_POST['billing_company'])) ? $_POST['billing_company'] : false;
	$billing_postcode =  (isset($_POST['billing_postcode'])) ? $_POST['billing_postcode'] : false;
	$billing_state =  (isset($_POST['billing_state'])) ? $_POST['billing_state'] : false;
	$billing_city =  (isset($_POST['billing_city'])) ? $_POST['billing_city'] : false;
}



// get meta of listing

// get first images
$gallery = get_post_meta($data->listing_id, '_gallery', true);
$instant_booking = get_post_meta($data->listing_id, '_instant_booking', true);
$listing_type = get_post_meta($data->listing_id, '_listing_type', true);
$payment_option = get_post_meta($data->listing_id, '_payment_option', true);

foreach ((array) $gallery as $attachment_id => $attachment_url) {
	$image = wp_get_attachment_image_src($attachment_id, 'listeo-gallery');
	break;
}

if (!$image) {
	$image = wp_get_attachment_image_src(get_post_thumbnail_id($data->listing_id), 'listeo-gallery', false);
}
$email_required = get_option('listeo_booking_email_required');

if (isset($data->registration_errors) && !empty($data->registration_errors)) {
	$registration_errors = $data->registration_errors;
} else {
	$registration_errors = array();
}
?>
<div class="row">

	<!-- Content
		================================================== -->
	<div class="col-lg-8 col-md-8 padding-right-30">

		<h3 class="margin-top-0 margin-bottom-30"><?php esc_html_e('Personal Details', 'listeo_core'); ?></h3>

		<form id="booking-confirmation" action="" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="confirmed" value="yessir" />
			<input type="hidden" name="value" value="<?php echo esc_html($data->submitteddata); ?>" />
			<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $data->listing_id; ?>">
			<input type="hidden" name="coupon_code" class="input-text" id="coupon_code" value="<?php if (isset($data->coupon)) echo $data->coupon; ?>" placeholder="<?php esc_html_e('Coupon code', 'listeo_core'); ?>">
			<div class="row">
				<?php
				if (!is_user_logged_in()) :
					$email_required = true;
					$booking_without_login = get_option('listeo_booking_without_login', 'off');

					$popup_login = get_option('listeo_popup_login', 'ajax');

					if ($booking_without_login == 'on') { ?>
						<?php if ($registration_errors) {
							foreach ($registration_errors as $key => $error) {
								switch ($error) {
									case 'email':
										$errors[] = esc_html__('The email address you entered is not valid.', 'listeo_core');
										break;
									case 'email_exists':
										$errors[] = esc_html__('An account exists with this email address.', 'listeo_core');
										break;
									case 'closed':
										$errors[] = esc_html__('Registering new users is currently not allowed.', 'listeo_core');
										break;
									case 'captcha-no':
										$errors[] = esc_html__('Please check reCAPTCHA checbox to register.', 'listeo_core');
										break;
									case 'username_exists':
										$errors[] =  esc_html__('This username already exists.', 'listeo_core');
										break;
									case 'captcha-fail':
										$errors[] = esc_html__("You're a bot, aren't you?.", 'listeo_core');
										break;
									case 'policy-fail':
										$errors[] = esc_html__("Please accept the Privacy Policy to register account.", 'listeo_core');
										break;
									case 'terms-fail':
										$errors[] = esc_html__("Please accept the Terms and Conditions to register account.", 'listeo_core');
										break;
									case 'otp-fail':
										$errors[] = esc_html__("Your one time verification code was not correct, please try again.", 'listeo_core');
										break;
									case 'first_name':
										$errors[] = esc_html__("Please provide your first name", 'listeo_core');
										break;
									case 'last_name':
										$errors[] = esc_html__("Please provide your last name", 'listeo_core');
										break;
									case 'empty_user_login':
										$errors[] = esc_html__("Please provide your user login", 'listeo_core');
										break;
									case 'password-no':
										$errors[] = esc_html__("You have forgot about password.", 'listeo_core');
										break;
									case 'registration_closed':
										$errors[] = esc_html__("Registration is closed.", 'listeo_core');
										break;
									case 'incorrect_password':
										$err = __(
											"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?",
											'listeo_core'
										);
										$errors[] =  sprintf($err, wp_lostpassword_url());
										break;

									default:
										# code...
										break;
								}
							}
						} ?>
						<?php if (isset($errors) && is_array($errors) && count($errors) > 0) : ?>
							<?php foreach ($errors  as $error) : ?>
								<div class="col-md-12">
									<div class="notification error closeable">
										<p><?php echo ($error); ?></p>
										<a class="close"></a>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
						<div class="col-md-12">

							<div class="woocommerce-info margin-bottom-30">
								<?php _e('Your account will be created automatically based on data you provide below. <br> If you already have an account, please', 'listeo_core'); ?>
								<?php if ($popup_login == 'ajax') { ?>

									<a href="#sign-in-dialog" class="popup-with-zoom-anim">
										<?php esc_html_e('login', 'listeo_core') ?></span>.
									</a>

								<?php } else {

									$login_page = get_option('listeo_profile_page'); ?>
									<a href="<?php echo esc_url(get_permalink($login_page)); ?>"><?php esc_html_e('login', 'listeo_core') ?></span>.
									</a>
								<?php } ?>
							</div>
						</div>
						<input type="hidden" name="user_role" value="guest" checked />
						<?php if (!get_option('listeo_registration_hide_username')) : ?>
							<div class="col-md-6 booking-registration-field">
								<div class="input-with-icon medium-icons">
									<label><?php esc_html_e('Username', 'listeo_core'); ?> <i class="fas fa-asterisk"></i></label>
									<input required type="text" class="input-text" name="username" id="username2" value="<?php if (isset($_POST['username']) && !empty($_POST['username'])) {
																																echo $_POST['username'];
																															} ?>" />
									<i class="sl sl-icon-user"></i>
								</div>
							</div>
						<?php endif; ?>

						<?php if (get_option('listeo_display_password_field')) : ?>
							<div class="col-md-6 booking-registration-field">
								<div class="">
									<label for="password1"><?php esc_html_e('Password', 'listeo_core'); ?></label>
									<input required class="input-text" type="password" name="password" id="password1" />
									<span class="pwstrength_viewport_progress"></span>
								</div>
							</div>
						<?php endif; ?>


						<?php $recaptcha = get_option('listeo_recaptcha');
						$recaptcha_version = get_option('listeo_recaptcha_version', 'v2');


						if ($recaptcha && $recaptcha_version == 'v3') { ?>
							<input type="hidden" id="rc_action" name="rc_action" value="ws_register">
							<input type="hidden" id="token" name="token">
						<?php } ?>
				<?php }
				endif; ?>
				<div class="col-md-6">
					<label><?php esc_html_e('First Name', 'listeo_core'); ?></label>
					<input type="text" id="firstname" name="firstname" value="<?php esc_html_e($first_name); ?>">
				</div>

				<div class="col-md-6">
					<label><?php esc_html_e('Last Name', 'listeo_core'); ?></label>
					<input type="text" name="lastname" id="lastname" value="<?php esc_html_e($last_name); ?>">
				</div>

				<?php ?>
				<div class="col-md-6">
					<div class="input-with-icon medium-icons">
						<label><?php esc_html_e('E-Mail Address', 'listeo_core'); ?><?php if ($email_required) {
																						echo '<i class="fas fa-asterisk"></i>';
																					} ?></label>
						<input type="text" <?php if ($email_required) {
												echo "required";
											} ?> name="email" id="email" value="<?php esc_html_e($email); ?>">
						<i class="sl sl-icon-envelope-open"></i>
					</div>
				</div>

				<?php $phone_required = get_option('listeo_booking_phone_required'); ?>
				<div class="col-md-6">
					<div class="input-with-icon medium-icons">
						<label><?php esc_html_e('Phone', 'listeo_core'); ?><?php if ($phone_required) {
																				echo '<i class="fas fa-asterisk"></i>';
																			} ?> </label>
						<input type="text" <?php if ($phone_required) {
												echo "required";
											} ?> name="phone" id="phone" value="<?php esc_html_e($phone); ?>">
						<i class="sl sl-icon-phone"></i>
					</div>
				</div>
				<!-- /// -->

				<?php if (get_option('listeo_add_address_fields_booking_form')) :
					$address_fields = get_option('listeo_booking_address_displayed', array('billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_city', 'billing_country', 'billing_state'));
					$address_fields_requirements = get_option('listeo_booking_address_required', array('billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_city', 'billing_country', 'billing_state')); ?>

					<?php if (in_array('billing_company', $address_fields)) {
						$billing_company_required = in_array('billing_company', $address_fields_requirements);
					?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Company Name', 'listeo_core'); ?><?php if ($billing_company_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<input <?php if ($billing_company_required) { ?>required<?php } ?> type="text"  id="billing_company" name="billing_company" value="<?php esc_html_e($billing_company); ?>">

							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_address_1', $address_fields)) {
						$billing_address_1_required = in_array('billing_address_1', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Street Address', 'listeo_core'); ?><?php if ($billing_address_1_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<input <?php if ($billing_address_1_required) { ?>required<?php } ?> type="text"  id="billing_address_1" name="billing_address_1" value="<?php esc_html_e($billing_address_1); ?>">

							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_address_2', $address_fields)) {
						$billing_address_2_required = in_array('billing_address_2', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Apartment, suite, unit etc. (optional)', 'listeo_core'); ?><?php if ($billing_address_2_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<input <?php if ($billing_address_2_required) { ?>required<?php } ?> type="text" name="billing_address_2" value="<?php esc_html_e($billing_address_2); ?>">

							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_postcode', $address_fields)) {
						$billing_postcode_required = in_array('billing_postcode', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Postcode/ZIP', 'listeo_core'); ?><?php if ($billing_postcode_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<input type="text" <?php if ($billing_postcode_required) { ?>required<?php } ?> name="billing_postcode" id="billing_postcode" value=" <?php esc_html_e($billing_postcode); ?>">

							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_city', $address_fields)) {
						$billing_city_required = in_array('billing_city', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Town', 'listeo_core'); ?><?php if ($billing_city_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<input type="text" <?php if ($billing_city_required) { ?>required<?php } ?> name="billing_city" value="<?php esc_html_e($billing_city); ?>">

							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_country', $address_fields)) {
						$billing_country_required = in_array('billing_country', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('Country', 'listeo_core'); ?><?php if ($billing_country_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<?php
								global $woocommerce;
								// get user meta billing_country
								if (is_user_logged_in()) {
									$billing_country = get_user_meta($current_user->ID, 'billing_country', true);
								} else {
									$billing_country = '';
								}

								$billing_country_args = array(
									'type' => 'country',
									
									'class' => ['address-field'],
									'validate' => ['country'],
									'default' => $billing_country,
									'return' => false
								);
								if($billing_country_required){
									$billing_country_args['required'] = true;
								}
								$billing_country_field = woocommerce_form_field('billing_country', $billing_country_args);
								
								// parse the field to add required attribute
								if ($billing_country_required) {
									$billing_country_field = str_replace('<select ', '<select required ', $billing_country_field);
								}
								echo $billing_country_field; ?>
							</div>
						</div>
					<?php } ?>
					<?php if (in_array('billing_state', $address_fields)) {
						$billing_state_required = in_array('billing_state', $address_fields_requirements); ?>
						<div class="col-md-6">
							<div class="input-without-icon">
								<label><?php esc_html_e('State', 'listeo_core'); ?><?php if ($billing_state_required) { ?><i class="fas fa-asterisk"></i> <?php } ?></label>
								<?php
								global $woocommerce;
								// get user meta billing_country
								if (is_user_logged_in()) {
									$billing_state = get_user_meta($current_user->ID, 'billing_state', true);
								} else {
									$billing_state = '';
								}

								$field = [
									'type' => 'state',
									'required' => 1,
									'class' => ['address-field'],
									'validate' => ['state'],
									'return' => true
								];
								if($billing_state_required){
									$field['required'] = true;
								}
								$billing_state_field = woocommerce_form_field('billing_state', $field, '');
								// add required attribute to select field inside the $billing_state_field

								// parse the field to add required attribute
								if ($billing_state_required) {
									$billing_state_field = str_replace('<select ', '<select required ', $billing_state_field);
								}
								echo $billing_state_field;
								?>
								

							</div>
						</div>

					<?php } ?>
				<?php endif; ?>

				<!-- Custom fields for booking form -->
				<div class="listeo-custom-booking-fields-wrapper">
					<?php echo listeo_get_extra_booking_fields($listing_type); ?>

				</div>
				<!-- /// -->
				<div class="col-md-12 margin-top-15">
					<label><?php esc_html_e('Message', 'listeo_core'); ?></label>
					<textarea maxlength="200" name="message" placeholder="<?php esc_html_e('Your short message to the listing owner (optional)', 'listeo_core'); ?>" id="booking_message" cols="20" rows="3"><?php echo $message; ?></textarea>
				</div>

				<?php if (!is_user_logged_in()) :

					$booking_without_login = get_option('listeo_booking_without_login', 'off');
					if ($booking_without_login == 'on') { ?>
						<?php if ($recaptcha && $recaptcha_version == 'v2') { ?>

							<div class="col-md-6 checkboxes margin-bottom-15" style="padding: 0px 20px">
								<div class="g-recaptcha" data-sitekey="<?php echo get_option('listeo_recaptcha_sitekey'); ?>"></div>
							</div>
						<?php }


						$privacy_policy_status = get_option('listeo_privacy_policy');

						if ($privacy_policy_status && function_exists('the_privacy_policy_link')) { ?>
							<div class="col-md-6 booking-registration-field">
								<div class="margin-top-10 checkboxes margin-bottom-10">
									<input type="checkbox" id="privacy_policy_booking" name="privacy_policy">
									<label for="privacy_policy_booking"><?php esc_html_e('I agree to the', 'listeo_core'); ?> <a target="_blank" href="<?php echo get_privacy_policy_url(); ?>"><?php esc_html_e('Privacy Policy', 'listeo_core'); ?></a> </label>

								</div>
							</div>
						<?php } ?>

						<?php
						$terms_and_condition_status = get_option('listeo_terms_and_conditions_req');
						$terms_and_condition_status_page = get_option('listeo_terms_and_conditions_page');

						if ($terms_and_condition_status) { ?>
							<div class="col-md-6 booking-registration-field">
								<div class="margin-top-10 checkboxes margin-bottom-10">
									<input type="checkbox" id="terms_and_conditions_booking" name="terms_and_conditions">
									<label for="terms_and_conditions_booking"><?php esc_html_e('I agree to the', 'listeo_core'); ?> <a target="_blank" href="<?php echo get_permalink($terms_and_condition_status_page); ?>"><?php esc_html_e('Terms and Conditions', 'listeo_core'); ?></a> </label>

								</div>
							</div>
				<?php }
					}
				endif;
				?>
		</form>
	</div>


	<a href="#" class="button booking-confirmation-btn margin-top-20">
		<div class="loadingspinner"></div><span class="book-now-text">
			<?php
			if (get_option('listeo_disable_payments') || $payment_option ==  'pay_cash' || $payment_option ==  'pay_maybe') {
				($instant_booking == 'on') ? esc_html_e('Confirm', 'listeo_core') : esc_html_e('Confirm', 'listeo_core');
			} else {
				($instant_booking == 'on') ? esc_html_e('Confirm and Pay', 'listeo_core') : esc_html_e('Confirm and Book', 'listeo_core');
			}
			?></span>
	</a>

</div>


<!-- Sidebar
		================================================== -->
<div class="col-lg-4 col-md-4 margin-top-0 margin-bottom-60">

	<!-- Booking Summary -->
	<div class="listing-item-container compact order-summary-widget">
		<div class="listing-item">
			<?php if (isset($image[0])) { ?>
				<img src="<?php echo $image[0]; ?>" alt="">
			<?php } ?>


			<div class="listing-item-content">
				<?php $rating = get_post_meta($data->listing_id, 'listeo-avg-rating', true);
				if (isset($rating) && $rating > 0) : ?>
					<div class="numerical-rating" data-rating="<?php $rating_value = esc_attr(round($rating, 1));
																printf("%0.1f", $rating_value); ?>"></div>
				<?php endif; ?>
				<h3><?php echo get_the_title($data->listing_id); ?></h3>
				<?php if (get_the_listing_address($data->listing_id)) { ?><span><?php the_listing_address($data->listing_id); ?></span><?php } ?>
			</div>
		</div>
	</div>
	<div class="boxed-widget opening-hours summary margin-top-0">
		<h3><i class="fa fa-calendar-check"></i> <?php esc_html_e('Booking Summary', 'listeo_core'); ?></h3>
		<?php
		$currency_abbr = get_option('listeo_currency');
		$currency_postion = get_option('listeo_currency_postion');
		$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);

		?>
		<ul id="booking-confirmation-summary">

			<?php if ($listing_type == 'event') { ?>
				<li id='booking-confirmation-summary-date'>
					<?php esc_html_e('Date Start', 'listeo_core'); ?>
					<span>
						<?php
						$meta_value = get_post_meta($data->listing_id, '_event_date', true);
						$meta_value_timestamp = get_post_meta($data->listing_id, '_event_date_timestamp', true);

						if (!empty($meta_value_timestamp)) {
							echo date_i18n(get_option('date_format'), $meta_value_timestamp);
							$meta_value_date = explode(' ', $meta_value, 2);
							$meta_value_date[0] = str_replace('/', '-', $meta_value_date[0]);
							if (isset($meta_value_date[1])) {
								$time = str_replace('-', '', $meta_value_date[1]);
								$meta_value = esc_html__(' at ', 'listeo_core');
								$meta_value .= date_i18n(get_option('time_format'), strtotime($time));
							}
							echo $meta_value;
						} else {
							$meta_value_date = explode(' ', $meta_value, 2);
							$meta_value_date[0] = str_replace('/', '-', $meta_value_date[0]);
							$meta_value = date_i18n(listeo_date_time_wp_format_php(), strtotime($meta_value_date[0]));

							if (isset($meta_value_date[1])) {
								$time = str_replace('-', '', $meta_value_date[1]);
								$meta_value .= esc_html__(' at ', 'listeo_core');
								$meta_value .= date_i18n(get_option('time_format'), strtotime($time));
							}
							echo $meta_value;
						}

						?>

					</span>
				</li>
				<?php
				$meta_value = get_post_meta($data->listing_id, '_event_date_end', true);

				if (isset($meta_value) && !empty($meta_value)) : ?>
					<li id='booking-confirmation-summary-date'>
						<?php esc_html_e('Date End', 'listeo_core'); ?>
						<span>
							<?php
							$meta_value = get_post_meta($data->listing_id, '_event_date_end', true);
							$meta_value_end_timestamp = get_post_meta($data->listing_id, '_event_date_end_timestamp', true);
							if (!empty($meta_value_end_timestamp)) {
								echo date_i18n(get_option('date_format'), $meta_value_end_timestamp);
								$meta_value_date = explode(' ', $meta_value, 2);

								$meta_value_date[0] = str_replace('/', '-', $meta_value_date[0]);
								if (isset($meta_value_date[1])) {
									$time = str_replace('-', '', $meta_value_date[1]);
									$meta_value = esc_html__(' at ', 'listeo_core');
									$meta_value .= date_i18n(get_option('time_format'), strtotime($time));
								}
								echo $meta_value;
							} else {
								$meta_value_date = explode(' ', $meta_value, 2);

								$meta_value_date[0] = str_replace('/', '-', $meta_value_date[0]);
								$meta_value = date_i18n(get_option('date_format'), strtotime($meta_value_date[0]));


								//echo strtotime(end($meta_value_date));
								//echo date( get_option( 'time_format' ), strtotime(end($meta_value_date)));
								if (isset($meta_value_date[1])) {
									$time = str_replace('-', '', $meta_value_date[1]);
									$meta_value .= esc_html__(' at ', 'listeo_core');
									$meta_value .= date_i18n(get_option('time_format'), strtotime($time));
								}
								echo $meta_value;
							}
							?>
						</span>
					</li>
				<?php endif; ?>
			<?php } else { ?>

				<li id='booking-confirmation-summary-date'>
					<?php esc_html_e('Date', 'listeo_core'); ?>
					<span><?php
							// $data->date_start is in this format 2024-03-01
							// make sure it is displayed in the format set in the settings
							echo date_i18n(get_option('date_format'), strtotime($data->date_start));
							//	echo $data->date_start; 
							?>
						<?php if (isset($data->date_end) && $data->date_start != $data->date_end) {
							echo '<b> - </b>' . $data->date_end; ?>
						<?php	} ?>

					</span>
				</li>
				<?php if (isset($data->_hour)) { ?>
					<li id='booking-confirmation-summary-time'>
						<?php esc_html_e('Time', 'listeo_core'); ?> <span><?php echo $data->_hour;
																			if (isset($data->_hour_end)) {
																				echo ' - ';
																				echo $data->_hour_end;
																			}; ?></span>
					</li>
				<?php } ?>
				<?php if ($listing_type == 'event') { ?>
					<li id='booking-confirmation-summary-time'>
						<?php

						$event_start = get_post_meta($data->listing_id, '_event_date', true);

						$event_start_date = explode(' ', $event_start, 2);

						if (isset($event_start_date[1])) {
							$time = str_replace('-', '', $event_start_date[1]);
							$event_hour_start = date_i18n(get_option('time_format'), strtotime($time));
						}

						$event_end  = get_post_meta($data->listing_id, '_event_date_end', true);

						$event_start_end = explode(' ', $event_end, 2);

						if (isset($event_start_end[1])) {
							$time = str_replace('-', '', $event_start_end[1]);
							$event_hour_end = date_i18n(get_option('time_format'), strtotime($time));
						}
						?>
						<?php esc_html_e('Time', 'listeo_core'); ?>
						<span><?php echo $event_hour_start; ?> <?php if (isset($event_hour_end) && $event_hour_start != $event_hour_end) echo '<b> - </b>' . $event_hour_end; ?></span>
					</li>
				<?php } ?>
			<?php } ?>
			<?php $max_guests = get_post_meta($data->listing_id, "_max_guests", true);
			if (get_option('listeo_remove_guests')) {
				$max_guests = 1;
			}
			if (!get_option('listeo_remove_guests')) : ?>

				<?php if (isset($data->adults) || isset($data->childrens)) { ?>
					<li id='booking-confirmation-summary-guests'>
						<?php esc_html_e('Guests', 'listeo_core'); ?> <span><?php if (isset($data->adults)) echo $data->adults;
																			if (isset($data->childrens)) echo $data->childrens . ' Childrens ';
																			?></span>
					</li>
				<?php }

			endif;

			if (isset($data->tickets)) { ?>
				<li id='booking-confirmation-summary-tickets'>
					<?php esc_html_e('Tickets', 'listeo_core'); ?> <span><?php if (isset($data->tickets)) echo $data->tickets; ?></span>
				</li>
			<?php } ?>

			<?php if (isset($data->services) && !empty($data->services)) { ?>
				<li id='booking-confirmation-summary-services'>
					<h5 id="summary-services"><?php esc_html_e('Additional Services', 'listeo_core'); ?></h5>
					<ul>
						<?php
						$bookable_services = listeo_get_bookable_services($data->listing_id);
						$i = 0;
						if ($listing_type == 'rental') {
							if (isset($data->date_start) && !empty($data->date_start) && isset($data->date_end) && !empty($data->date_end)) {

								$firstDay = new DateTime($data->date_start);
								$lastDay = new DateTime($data->date_end . '23:59:59');

								$days = $lastDay->diff($firstDay)->format("%a");
								if (get_option('listeo_count_last_day_booking')) {
									$days += 1;
								}
							} else {
								$days = 1;
							}
						} else {
							$days = 1;
						}
						if (isset($data->adults)) {
							$guests = $data->adults;
						} else {
							$guests = $data->tickets;
						}


						foreach ($bookable_services as $key => $service) {

							// $data->date_start
							// $data->date_end;
							// days

							$countable = array_column($data->services, 'value');

							if (in_array(sanitize_title($service['name']), array_column($data->services, 'service'))) {
						?>
								<li>
									<span><?php
											if (empty($service['price']) || $service['price'] == 0) {
												esc_html_e('Free', 'listeo_core');
											} else {
												if ($currency_postion == 'before') {
													echo $currency_symbol . ' ';
												}
												$service_price = listeo_calculate_service_price($service, $guests, $days, $countable[$i]);
												$decimals = get_option('listeo_number_decimals', 2);
												echo number_format_i18n($service_price, $decimals);
												if ($currency_postion == 'after') {
													echo ' ' . $currency_symbol;
												}
											}
											?></span>
									<?php echo esc_html($service['name']);
									if (isset($countable[$i]) && $countable[$i] > 1) { ?>
										<em>(*<?php echo $countable[$i]; ?>)</em>
									<?php } ?>
								</li>
						<?php $i++;
							}
						}  ?>
					</ul>
				</li>
			<?php }
			$decimals = get_option('listeo_number_decimals', 2); ?>

			<?php if (!get_option('listeo_remove_coupons')) : ?>
				<li class="booking-confirmation-coupons">
					<div class="coupon-booking-widget-wrapper">
						<a id="listeo-coupon-link" href="#"><?php esc_html_e('Have a coupon?', 'listeo_core'); ?></a>
						<div class="coupon-form">

							<input type="text" name="apply_new_coupon" class="input-text" id="apply_new_coupon" value="" placeholder="<?php esc_html_e('Coupon code', 'listeo_core'); ?>">
							<a href="#" class="button listeo-booking-widget-apply_new_coupon" name="apply_new_coupon"><?php esc_html_e('Apply', 'listeo_core'); ?></a>
						</div>
						<div id="coupon-widget-wrapper-output">
							<div class="notification error closeable"></div>
							<div class="notification success closeable" id="coupon_added"><?php esc_html_e('This coupon was added', 'listeo_core'); ?></div>
						</div>
						<div id="coupon-widget-wrapper-applied-coupons">
							<?php
							if (isset($data->coupon) && !empty($data->coupon)) {
								$coupons = explode(',', $data->coupon);
								foreach ($coupons as $key => $value) {
									echo "<span data-coupon='{$value}'>{$value} <i class=\"fa fa-times\"></i></span>";
								}
							}
							?>
						</div>
					</div>


				</li>
			<?php endif; ?>
			<?php
			$decimals = get_option('listeo_number_decimals', 2);

			if ($data->price > 0) : ?>
				<?php

				$mandatory_fees = get_post_meta($data->listing_id, "_mandatory_fees", true);
				if (!isset($mandatory_fees) || !is_array($mandatory_fees)) {
					$mandatory_fees = [];
				}

				$options = get_option( 'mrjgcc_plugin_options', array( "title" => "Booking fee", "percentage" => 5 ));
				$pctcommission = (int) $options['percentage'];
				$starting_price = ($data->price / (1 + $pctcommission / 100));
				$commission_amount = $data->price - $starting_price;

				// MRJ - Generates the commission line on the booking widget
				$mandatory_fees[] = ["title" => $options['title'], "price" => $commission_amount ];

				// MRJ
				// This outputs on the booking confimation where confirm and pay is clicked
				if (is_array($mandatory_fees) && !empty($mandatory_fees)) {
					$currency_abbr = get_option('listeo_currency');
					$currency_postion = get_option('listeo_currency_postion');
					$currency_symbol = Listeo_Core_Listing::get_currency_symbol($currency_abbr);

					echo "<ul id='booking-mandatory-fees'>";
					foreach ($mandatory_fees as $key => $fee) {
						if ($fee['price']) { ?>
							<li>
								<p><?php echo $fee['title']; ?></p>
								<strong><?php if ($currency_postion == 'before') {
											echo $currency_symbol . ' ';
										}
										$decimals = get_option('listeo_number_decimals', 2);
										if (is_numeric($fee['price'])) {
											echo number_format_i18n($fee['price'], $decimals);
										}
										if ($currency_postion == 'after') {
											echo ' ' . $currency_symbol;
										} ?></strong>
							</li>
				<?php }
					}
					echo "</ul>";
				};

				?>
				<li class="total-costs <?php if (isset($data->price_sale)) : ?> estimated-with-discount<?php endif; ?>" data-price="<?php echo esc_attr($data->price); ?>"><?php esc_html_e('Total Cost', 'listeo_core'); ?><span>
						<?php if ($currency_postion == 'before') {
							echo $currency_symbol . ' ';
						}
						echo number_format_i18n($data->price, $decimals);
						if ($currency_postion == 'after') {
							echo ' ' . $currency_symbol;
						} ?></span></li>
			<?php endif; ?>
			<?php if (isset($data->price_sale)) : ?>

				<?php $decimals = get_option('listeo_number_decimals', 2); ?>
				<li class="total-discounted_costs"><?php esc_html_e('Final Cost', 'listeo_core'); ?><span>
						<?php if ($currency_postion == 'before') {
							echo $currency_symbol . ' ';
						}
						echo number_format_i18n($data->price_sale, $decimals);
						if ($currency_postion == 'after') {
							echo ' ' . $currency_symbol;
						} ?></span></li>

			<?php else : ?>
				<li style="display:none;" class="total-discounted_costs"><?php esc_html_e('Final Cost', 'listeo_core'); ?><span> </span></li>
			<?php endif; ?>
		</ul>

	</div>
	<!-- Booking Summary / End -->

</div>
</div>