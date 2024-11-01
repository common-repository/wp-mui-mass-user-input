<?php
function wp_mui_mass_email() {
	global $wpdb, $current_user;
	//check_admin_referer('wp_mui_mass_email');

	if($_REQUEST['special'] == 'self_test') {
		get_currentuserinfo();

		$subject = stripslashes($_REQUEST['message_subject']);
		$message = stripslashes($_REQUEST['message_content']);
		$message = str_replace('%username%', $current_user->user_login, $message);
		$message = str_replace('%first_name%', $current_user->first_name, $message);
		$message = str_replace('%last_name%', $current_user->last_name, $message);
		$message = str_replace('%password%', get_usermeta($current_user->ID, 'wp_mui_initial_pass'), $message);

		if(wp_mail($current_user->user_email, $subject, $message))
			echo "An email has been sent to {$current_user->user_email}.";
		else
			echo "An error occured when trying to send an email.";
		die();
	}

	$unconfirmed_users = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key ='wp_mui_initial_pass'");



	$count = 0;
	$error = 0;
	foreach($unconfirmed_users as $user) {

		$user_info = get_userdata($user);

		$subject = stripslashes($_REQUEST['message_subject']);
		$message = stripslashes($_REQUEST['message_content']);
		$message = str_replace('%username%', $user_info->user_login, $message);
		$message = str_replace('%first_name%', $user_info->first_name, $message);
		$message = str_replace('%last_name%', $user_info->last_name, $message);
		$message = str_replace('%password%', get_usermeta($user, 'wp_mui_initial_pass'), $message);

		if(wp_mail($user_info->user_email, $subject, $message))
			$count++;
		else
			$error++;
	}
	echo "$count messages were sent.";
	if($error)
		echo "There were ($error) errors with sending emails.";

	die();
}

//This function checks the database for the username typed in
function wp_mui_ajax_validate_user() {
	global $wpdb;
	$current_user = wp_get_current_user();	if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_mui_' . $current_user->ID) ) die('Security check');
	//Check to make sure the username is valid
	if(!validate_username($_REQUEST['user_login']))
	{
		echo json_encode('This username is invalid. Please try another.');
		die();
	}
	//Do the query to get the number of rows
	$user_count = $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM $wpdb->users WHERE user_login = %s;",
		$_REQUEST['user_login']
	));
	echo ($user_count == 0 ? json_encode(true) : json_encode("This username is already taken. Please try another."));
	die();
}
//This function uses ajax to add the user to the DB
function wp_mui_add_user()
{
	global $wpdb, $wp_mui;
	$current_user = wp_get_current_user();
 		if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_mui_' . $current_user->ID) ) die('Security check');

	//First, check to make sure the user doesn't exist
	if(!$wp_mui->settings['autocreate_user'] && username_exists($_REQUEST['user_login']))
	{
		$res['result'] = 'nonunique_username';
		echo json_encode($res);
		die();
	}
	//Now, check to make sure the username is valid
	if(!$wp_mui->settings['autocreate_user'] && !validate_username($_REQUEST['user_login']))
	{
		$res['result'] = 'invalid_username';
		echo json_encode($res);
		die();
	}

	//Here we generate the username
	if($wp_mui->settings['autocreate_user'])
	{
		//We do this until there is a unique name
		$cont = true;
		$x = 0;
		while($cont)
		{
			//Select from the DB to determine the number of users
			$user_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->users WHERE user_login LIKE %s;",
				$_REQUEST['first_name'].".".$_REQUEST['last_name']."%"
			));

			if($user_count)
			{
				$user_count = $user_count + $x + 1;
				$user_login = $_REQUEST['first_name'].".".$_REQUEST['last_name'].$user_count;
			}
			else
			{
				$user_login = $_REQUEST['first_name'].".".$_REQUEST['last_name'];
			}

			//Recheck validity
			if(!username_exists($user_login)) $cont = false;
			else $x++;
		}
	}
	else
		$user_login = $_REQUEST['user_login'];
	//Generate the password if we need to
	if($wp_mui->settings['autocreate_pass'])
		$password = substr(md5(md5(time()) + md5(rand())), 0, 10);
	else
		$password = $_REQUEST['pass1'];

	// Prepare email address if set and passed
 		$email = (!empty($_REQUEST['email']) ? $_REQUEST['email'] : false);
		if(empty($email)) {
			$res['result'] = 'warning';
			$res['message'] = "Error! An email must be specified.";
			echo json_encode($res);
			die();
		}

		if(email_exists($email)) {
			$res['result'] = 'warning';
			$res['message'] = "Error! Email address '$email' already exists, can not have users with duplicate email addresses.";
			echo json_encode($res);
			die();
		}


	if(empty($user_login)) {
		$res['result'] = 'invalid_username';
		echo json_encode($res);
		die();

	}

	//Now add the user to the DB
	if($user_id = wp_create_user($user_login, $password, $email))
	{

		// Add BB fields
		$bp_fields =  wp_mui::get_user_fields();

		if(is_array($bp_fields)):
			foreach($bp_fields as $bp_data):

				if(!empty($_REQUEST['bp_' . $bp_data->id])) {
					$wpdb->insert($wpdb->prefix . 'bp_xprofile_data', array('field_id' => $bp_data->id, 'value' => $_REQUEST['bp_' . $bp_data->id], 'user_id' => $user_id));




 				}
			endforeach;
		endif;


		// Send email to user if selected

 		if($_REQUEST['send_user_notification'] == 'on') {

			wp_new_user_notification($user_id, $password);
		}

		//Add to our final output, and then add all the user meta information
		$res['user_id'] = $user_id;
		if($wp_mui->settings['visible_fields']['role'])
			wp_update_user(array('ID'=>$user_id,'role'=>$_REQUEST['role']));
		if($wp_mui->settings['visible_fields']['first_name'])
			update_usermeta($user_id, 'first_name', $_REQUEST['first_name']);
		if($wp_mui->settings['visible_fields']['last_name'])
			update_usermeta($user_id, 'last_name', $_REQUEST['last_name']);
		if($wp_mui->settings['visible_fields']['streetaddress'])
			update_usermeta($user_id, 'streetaddress', $_REQUEST['streetaddress']);
		if($wp_mui->settings['visible_fields']['city'])
			update_usermeta($user_id, 'city', $_REQUEST['city']);
		if($wp_mui->settings['visible_fields']['state'])
			update_usermeta($user_id, 'state', $_REQUEST['state']);
		if($wp_mui->settings['visible_fields']['zip'])
			update_usermeta($user_id, 'zip', $_REQUEST['zip']);
		if($wp_mui->settings['visible_fields']['phonenumber'])
			update_usermeta($user_id, 'phonenumber', $_REQUEST['phonenumber']);
		if($wp_mui->settings['visible_fields']['middle_initial'])
			update_usermeta($user_id, 'middle_initial', $_REQUEST['middle_initial']);
		update_usermeta($user_id, 'wp_mui_initial_pass', $password);
		$res['user_login'] = $user_login;
		$res['result'] = 'success';
	}
	else
	{
		//An error occurred
		$res['result'] = 'error';
	}

	//Return the json encoded message
	echo json_encode($res);
	die();
}
//This function updates our option being sent
function wp_mui_update_option()
{
	global $wp_mui;

	$current_user = wp_get_current_user();	if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_mui_' . $current_user->ID) ) die('Security check');

	$is_visible_fields = true;
	$option_value = (isset($_REQUEST['option_value']) ? $_REQUEST['option_value'] : "");
	if($_REQUEST['option_name'] == "show_only_wp_mui_users" || $_REQUEST['option_name'] == "autocreate_user" || $_REQUEST['option_name'] == "autocreate_pass" || $_REQUEST['option_name'] == "rows_per_page") $is_visible_fields = false;

	$res['new_value'] = $wp_mui->toggle_setting($_REQUEST['option_name'], $is_visible_fields, $option_value);
	$res['result'] = "success";
	$res['option_name'] = $_REQUEST['option_name'];

	echo json_encode($res);

	die();
}
//Handles the export for the CSV
function wp_mui_export()
{
	global $wpdb, $wp_mui;
	//Add the headers to the output
	$output = '"Username","Password",';
	if($wp_mui->settings['visible_fields']['first_name']) $output .= '"First Name",';
	if($wp_mui->settings['visible_fields']['middle_initial']) $output .= '"MI",';
	if($wp_mui->settings['visible_fields']['last_name']) $output .= '"Last Name",';
	if($wp_mui->settings['visible_fields']['email']) $output .= '"Email",';
	if($wp_mui->settings['visible_fields']['streetaddress']) $output .= '"Street Address",';
	if($wp_mui->settings['visible_fields']['city']) $output .= '"City",';
	if($wp_mui->settings['visible_fields']['state']) $output .= '"State",';
	if($wp_mui->settings['visible_fields']['zip']) $output .= '"Zip",';
	if($wp_mui->settings['visible_fields']['phonenumber']) $output .= '"Phone Number",';
	if($wp_mui->settings['visible_fields']['role']) $output .= '"Role",';
	//Subtract the final comma and add the return char
	$output = substr($output, 0, strlen($output)-1)."\r\n";

	if($wp_mui->settings['show_only_wp_mui_users']) {
		$wp_mui_initial_pass_filter = "WHERE meta_key = 'wp_mui_initial_pass'";
	}

	$data = $wpdb->get_results("SELECT u.ID FROM $wpdb->users AS u WHERE u.ID IN (SELECT user_id FROM $wpdb->usermeta $wp_mui_initial_pass_filter) ORDER BY u.user_login ASC;", ARRAY_A);
	//Update the data with the user informatin we need
	foreach($data as $row)
	{
		$x = array();
		//Get the user info
		$user = get_userdata( $row['ID'] );

		//Determine the role info
		$roles = array_keys($user->{$wpdb->prefix.'capabilities'});
		$role = $roles[0];
		//Save the array to $x
		$x['user_login'] = $user->user_login;
		$x['wp_mui_initial_pass'] = $user->wp_mui_initial_pass;
		if($wp_mui->settings['visible_fields']['first_name']) $x['first_name'] = $user->first_name;
		if($wp_mui->settings['visible_fields']['middle_initial']) $x['middle_initial'] = $user->middle_initial;
		if($wp_mui->settings['visible_fields']['last_name']) $x['last_name'] = $user->last_name;
		if($wp_mui->settings['visible_fields']['email']) $x['email'] = $user->user_email;
		if($wp_mui->settings['visible_fields']['streetaddress']) $x['streetaddress'] = $user->streetaddress;
		if($wp_mui->settings['visible_fields']['city']) $x['city'] = $user->city;
		if($wp_mui->settings['visible_fields']['state']) $x['state'] = $user->state;
		if($wp_mui->settings['visible_fields']['zip']) $x['zip'] = $user->zip;
		if($wp_mui->settings['visible_fields']['phonenumber']) $x['phonenumber'] = $user->phonenumber;
		if($wp_mui->settings['visible_fields']['role']) $x['role'] = $role;
		//Add to the CSV output
		$output .= arr_to_csv(array(0=>$x))."\r\n";
	}
	//Print the output with headers
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"wp_mui_export.csv\"");
	print $output;
	die();
}
?>