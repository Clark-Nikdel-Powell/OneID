<?php

/*
 * This page is called by OneID and logs the user in.
 */


define( "ONEID_UID", "oneid_uid" ); // Usermeta field name


include_once $_SERVER['DOCUMENT_ROOT'].'/wp-load.php';
require_once plugin_dir_path(__FILE__).'oneid_api.php';


/*
 * Call OneID's server to validate the user
 */
$attrs = OneIDAPI::OneID_Response();

/*
 * If the user is validated, we log the user into Wordpress
 */
if ( $attrs ) {
	
	// Get user info from OneID response
	$oneid_user_id = $attrs["UID"];
	$oneid_email = $attrs["ATTR"]["personal_info"]["email"];
	$oneid_first_name = $attrs["ATTR"]["personal_info"]["first_name"];
	$oneid_last_name = $attrs["ATTR"]["personal_info"]["last_name"];

	
	// Check to see if there is a Wordpress user attached to the the OneID UID
	if ( !isset($wp_user) ) {
		if ( $wp_user = get_user_by_meta( ONEID_UID, $oneid_user_id ) ) {
			// We found a user for this OneID UID
			// Now grab some user data
			$user_data = get_user_by( 'id', $wp_user->ID );
			$user_login_id = $wp_user->ID;
			$user_login_name = $user_data->user_login;
		}
	}

	
	// If we didn't find a user, lookp by email address
	if ( !$user_login_id ) {
		if ( $wp_user = get_user_by( 'email', $oneid_email ) ) {
			// We found a user by email address
			// Now grab some user data
			$user_data = get_user_by( 'id', $wp_user->ID );
			$user_login_id = $wp_user->ID;
			$user_login_name = $user_data->user_login;
			
			// Save the user's OneID UID value so we can find them next time
			update_user_meta( $user_login_id, ONEID_UID, $oneid_user_id );
		}
	}

	
	// If we didn't find a user, the user has never logged in before
	// Let's create a new user
	if ( !$user_login_id ) {
		// Build user data using OneID response
		$user_data = array();
		// Generate username using OneID UID
		$user_data['user_login'] = "ONEID_".$oneid_user_id;
		// Generate a radnom password
		$user_data['user_pass'] = wp_generate_password();
		// Record user's name
		$user_data['user_nicename'] = $oneid_first_name.' '.$oneid_last_name;
		$user_data['first_name'] = $oneid_first_name;
		$user_data['last_name'] = $oneid_last_name;
		$user_data['display_name'] = $oneid_first_name.' '.substr($oneid_last_name, 0, 1);
		// Record user's email address
		$user_data['user_email'] = $oneid_email;
		// Assign this new user to the subscriber role
		$user_data['role'] = 'subscriber'; // TODO allow this to default to Setting > General > New User Default Role
		
		// Insert the user into the database
		$user_login_id = wp_insert_user($user_data);
		$user_login_name = $user_data['user_login'];
		// Send new user notification
		wp_new_user_notification($user_login_name);
		
		// Save the user's OneID UID value so we can find them next time
		update_user_meta( $user_login_id, ONEID_UID, $oneid_user_id );
	}
	
	if ( $user_login_id ) {
		// Log the user into Wordpress
		wp_set_auth_cookie( $user_login_id, false );
	}
	
	// Get the redirect URL
	if ( isset($_GET['redirect_to']) || $_GET['redirect_to'] ) {
		$redirectTo = $_GET['redirect_to'];
	} else {
		$redirectTo = admin_url();
	}
	
	// Display response data so OneID knows where to redirect the user
	echo $oneid->OneID_Redirect($redirectTo);

}



/*
 * Find user by meta data
 *
 * @param string $meta_key
 * @param string $meta_value
 * @return array
 */
function get_user_by_meta( $meta_key, $meta_value ) {
	global $wpdb, $blog_id;
	if ( empty($id) ) $id = (int)$blog_id;
	$blog_prefix = $wpdb->get_blog_prefix($id);
	$sql = "SELECT user_id, user_id AS ID,  user_login, display_name, user_email, meta_value ".
		"FROM $wpdb->users, $wpdb->usermeta ".
		"WHERE {$wpdb->users}.ID = {$wpdb->usermeta}.user_id ".
		"AND {$wpdb->users}.ID IN (".
			"SELECT user_id FROM {$wpdb->usermeta} ".
			"WHERE meta_key = '$meta_key' AND meta_value = '$meta_value')";
	return $wpdb->get_results( $sql );
}

?>