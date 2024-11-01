<?php
global  $current_user;
get_currentuserinfo();
?>

<?php //print $numrows."::".$pageno."::".$lastpage; ?>
<? //This stuff is hidden, but the jQuery moves it to the top ?>
<div class="hidden hide-if-no-js screen-meta-toggle" id="screen-options-link-wrap">
	<a class="show-settings" id="show-settings-link" href="#screen-options">Screen Options</a>
</div>
<div class="hidden" id="screen-options-wrap">
	<form method="post" action="" id="adv-settings">
		<h5>Options</h5>
		<div class="metabox-prefs">
			<?php echo wp_nonce_field('wp_mui_insert_users_page')?>
			<label for="rows_per_page">Rows Per Page</label><input type="text" value="<?php echo $wp_mui->settings['rows_per_page']?>" id="rows_per_page" name="rows_per_page" /><br />
			<input type="checkbox" value="true" id="show_only_wp_mui_users" name="show_only_wp_mui_users" <?php if($wp_mui->settings['show_only_wp_mui_users']) echo "CHECKED"; ?> /> <label for="show_only_wp_mui_users">Show only users entered using WP-MUI</label>
		</div>
	</form>
</div>
<? // End hidden ?>

<div class="wrap">
	<div class="icon32" id="icon-users"><br/></div>
	<h2>Previously Added Users <a class="button add-new-h2" href="users.php?page=wp_mui_insert_users_page">Back to User Entry</a></h2>
	
	<?php if($numrows == 0 && $usersearch == ""){ ?>
	<div id="wp_mui_result" class="error">There have been no users entered at this time.</div>
	<?php } else { ?>
	
	<?php if($numrows == 0 && $usersearch != ""){ ?>
	<div id="wp_mui_result" class="error">There have been no users entered at this time with the following characters: '<?php echo $usersearch?>'. <a href="#" id="clear_filter">Clear Filter</a></div>
	<?php } else { ?>
	<div id="wp_mui_result"></div>
	
	
	<div class="tablenav">
		<div class="alignleft actions">
			
			<input type="submit" class="button-secondary" id="export_btn" name="export_btn" value="Export All to CSV"/>
			<input type="button" class="button-secondary" value="Send Email to All Unconfirmed Users" id="wp_mui_notify_all_unconfirmed" />
			
			<?php if($usersearch != ""){ ?>
				<input type="submit" class="button-secondary" id="clear_filter" name="clear_filter" value="Clear Filter"/>
			<?php } ?>
		</div>
		
		<p class="search-box">
			<input type="text" value="<?php echo $usersearch?>" name="usersearch" id="usersearch"/>
			<input type="submit" class="button" value="Filter By Username" name="usersearch_btn" id="usersearch_btn" />
		</p>
		
		<br class="clear"/>
	</div>
	
	<div id="wp_mui_email_unconfirmed" style="border:1px solid #E5E5E5;display:none;margin-bottom: 20px; padding: 10px;">
	<?php echo wp_nonce_field('wp_mui_mass_email'); ?>
	<p>This function will send a welcome email to all unconfirmed users.  An unconfirmed user is any user that has not logged in and changed their <b>temporary password</b>.</p>
	<p>Tags you can use are: %username%, %first_name%, %last_name%, and %password% - these tags will be automatically replaced with the user's information.<br />
	Note: if are user is missing a field, such as first name, blank will be inserted.</p>
	
	<table>
	<tr>
	<td>Subject</td>
	<td><input style="width: 500px;" id="wp_mui_message_subject" value="Login Information for <?php echo get_bloginfo(); ?>"/></td>
	</tr>
	<tr>
	<td>Message</td>
	<td><textarea style="width: 500px;height: 140px;"  id="wp_mui_message_content" />Your login information for <?php echo get_bloginfo(); ?>:
Username: %username%
Password: %password%
You may login here: <?php echo get_bloginfo('url');?>
	</textarea></td>
	</tr>
	<tr>
	<td></td>
	<td>
		<input type="button" id="wp_mui_process_mass_email" value="Send Mass Email" />
		<input type="button" id="wp_mui_process_mass_email_test" value="Send Test Message to <?php echo $current_user->user_email;?>" />
	</td>
	</tr>
	</table>
	</div>
	
	<?php if($numrows != 0) { ?>
	<table cellspacing="0" class="widefat" style="width:100%;">
		<thead>
			<tr class="thead">
				<th style="" class="" id="user_login" scope="col">Username</th>
				<th style="" class="manage-column" id="email_col" scope="col">Email</th>
				<th style="" class="manage-column" id="password" scope="col">Temporary Password</th>
				<?php if($wp_mui->settings['visible_fields']['first_name']){?><th style="" class="manage-column" id="first_name" scope="col">First Name</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['middle_initial']){?><th style="" class="manage-column" id="middle_initial" scope="col">MI</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['last_name']){?><th style="" class="manage-column" id="last_name" scope="col">Last Name</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['streetaddress']){?><th style="" class="manage-column" id="streetaddress" scope="col">Address</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['city']){?><th style="" class="manage-column" id="city" scope="col">City</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['state']){?><th style="" class="manage-column" id="state" scope="col">State</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['zip']){?><th style="" class="manage-column" id="zip" scope="col">Zip</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['phonenumber']){?><th style="" class="manage-column" id="phonenumber" scope="col">Phone</th><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['role']){?><th style="" class="manage-column" id="role" scope="col">Role</th><?php } ?>
			</tr>
		</thead>

		<tbody class="list:user user-list" id="users">
			<?php 
				$class = 'alternate';
				foreach($data as $row){
					$class = ($class == 'alternate' ? $class = "" : $class = "alternate");
					//Get the user info
					$user = get_userdata( $row['ID'] );
					//Determine the role info
					$roles = array_keys($user->{$wpdb->prefix.'capabilities'});
					$role = $roles[0];
			?>
			<tr class="<?php echo $class?>" id="user-<?php echo $user->ID?>">
				<td><strong><a href="user-edit.php?user_id=<?php echo $user->ID?>"><?php echo $user->user_login?></a></strong></td>
				<td><?php echo $user->user_email?></td>
				<td><?php echo $user->wp_mui_initial_pass?></td>
				<?php if($wp_mui->settings['visible_fields']['first_name']){?><td><?php echo $user->first_name?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['middle_initial']){?><td><?php echo $user->middle_initial?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['last_name']){?><td><?php echo $user->last_name?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['streetaddress']){?><td><?php echo $user->streetaddress?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['city']){?><td><?php echo $user->city?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['state']){?><td><?php echo $user->state?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['zip']){?><td><?php echo $user->zip?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['phonenumber']){?><td><?php echo $user->phonenumber?></td><?php } ?>
				<?php if($wp_mui->settings['visible_fields']['role']){?><td><?php echo $role?></td><?php } ?>
			</tr>
			<?php } //end foreach ?>
		</tbody>
	</table>
	
	<table style="width:100%;">
		<thead>
			<tr class="thead">
				<th>
				<div class="alignleft" style="color: #8F8F8F; font-weight: normal;">
				<?php if($wp_mui->settings['show_only_wp_mui_users']) { ?>
				Displaying only users that have been created with WP-MUI and have not set their own passwords. 
				<?php } else { ?>
				Displaying all users.
				<?php } ?>
				</div>
				<div class="alignright actions" style="font-weight: normal;">
					<?php if($pagination_necessary) { ?>
					Choose Page
					<select name="choose_page" id="choose_page">
						<?php
							for($x2 = 1;$x2<=$lastpage;$x2++)
								echo "<option ".($x2 == $pageno ? 'selected="selected"' : '').">$x2</option>";
						?>
					</select>
					<?php $link = ($pageno != 1 ? 'javascript:change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list&pageno='.($pageno-1).($usersearch != "" ? "&usersearch=$usersearch" : "").'")' : ''); ?>
					<input type="button" class="button-secondary action" onclick='<?php echo $link?>' value="&lt;" />
					<?php $link = ($pageno != $lastpage ? 'javascript:change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list&pageno='.($pageno+1).($usersearch != "" ? "&usersearch=$usersearch" : "").'")' : ''); ?>
					<input type="button" class="button-secondary action" onclick='<?php echo $link?>' value="&gt;" />
					<?php } ?>
				</th>
			</tr>
		</thead>
	</table>
	<?php } ?>
	<?php } ?>
	<?php } ?>
</div>

<script type="text/javascript" language="javascript">
	//Functino to change the link
	function change_page(url){ window.location = url; }
	jQuery(document).ready(function($) 
	{	
		
		$("#wp_mui_notify_all_unconfirmed").click(function() {
 			$("#wp_mui_email_unconfirmed").show();		
		});
		
		
		// The next two functions can be combined at some point
		$("#wp_mui_process_mass_email").click(function() {
		
			// Verify user is sure about this
			
			
			if(confirm("You are about to send a mass email to all your unconfirmed users, proceed?")) {
			jQuery.get(
				ajaxurl, 
				{
					action: 'wp_mui_mass_email',
					message_subject: jQuery("#wp_mui_message_subject").val(),
					message_content: jQuery("#wp_mui_message_content").val(),
					_wpnonce: function(){return '<?php echo wp_create_nonce('wp_mui_' . $current_user->ID); ?>'}
				},
				function(data){
					$("#wp_mui_result").show();
					$("#wp_mui_result").removeClass('error').addClass('updated').html("<p>"+data+"</p>");
					$("#wp_mui_email_unconfirmed").hide();
				})
			}
			
			
		});		
		
		$("#wp_mui_process_mass_email_test").click(function() {
		
			jQuery.get(
				ajaxurl, 
				{
					action: 'wp_mui_mass_email',
					special: 'self_test',
					message_subject: jQuery("#wp_mui_message_subject").val(),
					message_content: jQuery("#wp_mui_message_content").val(),
					_wpnonce: function(){return '<?php echo wp_create_nonce('wp_mui_' . $current_user->ID); ?>'}
				},
				function(data){					
					$("#wp_mui_result").show();
					$("#wp_mui_result").removeClass('error').addClass('updated').html("<p>"+data+"</p>");
				})
			
			
		});
		
		
		//Attach our button to the clear filter
		$("#clear_filter").click(function(){
			change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list");
		});
		//Attach our button to the 'export'
		$("#export_btn").click(function(){
			window.location = ajaxurl + "?action=wp_mui_export";
		});
		//Attach our event to the drop box for the pages
		$("#choose_page").change(function(){
			change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list<?php echo ($usersearch != "" ? "&usersearch=$usersearch" : "")?>&pageno=" + $(this).val());
		});
		//Attach our event to the usersearch for the pages and textbox
		$("#usersearch_btn").click(function(){
			change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list&usersearch=" + $("#usersearch").val());
		});
		$('#usersearch').keyup(function(e) { 
			if(e.keyCode == 13)
				change_page("users.php?page=wp_mui_insert_users_page&wp_mui_page=users_list&usersearch=" + $("#usersearch").val());
		});
		//Prepend our help areas
		$("#screen-options-link-wrap").appendTo("#screen-meta-links").removeClass("hidden");
		$("#screen-options-wrap").prependTo("#screen-meta");
 	
		
		$("#adv-settings input").change(function(){
 
			var value = 'unknown';
			
			if($(this).is(":text")) {
				var value = $(this).val();
			}
		
			if($(this).is(":checkbox")) {
				if($(this).is(":checked")) {
					var value = 'on';
				} else {
					var value = 'off';
				}
			}
				
			
			jQuery.get(
				ajaxurl, 
				{
					action: 'wp_mui_update_option', 
					option_name: $(this).attr('name'),
					option_value: value,
					rand: Math.random(),
					_wpnonce: function(){return '<?php echo wp_create_nonce('wp_mui_' . $current_user->ID); ?>'}
				},
 
				function(data){
					if(data['result'] == "success")	
						$("#wp_mui_result").removeClass('error').addClass('updated').html("<p>Display settings updated, please <a href='javascript:window.location.reload()'>refresh</a> the page.</p>");
					else
						$("#wp_mui_result").removeClass('updated').addClass('error').html("<p>Please contact your administrator. An unknown error has occured.</p>");
				},'json'
			)
		});
	});
</script>