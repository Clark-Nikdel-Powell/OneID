<?php
/*
Plugin Name: OneID
Plugin URI: http://clarknikdelpowell.com/wordpress
Description: Login to Wordpress using OneID (oneid.com)
Version: 0.1
Author: Glenn Welser [Clark/Nikdel/Powell]
Author URI: http://clarknikdelpowell.com
License:
	
	Copyright 2012
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
*/


require_once plugin_dir_path(__FILE__).'oneid_api.php';


class OneID {

	/**
	 * Initializes the plugin.
	 */
	function __construct() {
	
		// Register login scripts.
		add_action( 'login_enqueue_scripts', array( &$this, 'register_login_scripts' ) );
		
		// Display login form.
		add_action( 'login_form', array ( &$this, 'login_form' ) );
		
		// Create OneID settings page.
		add_action( 'admin_menu', array( &$this, 'oneid_options_menu' ) );

		register_activation_hook( __FILE, array( &$this, 'activate') );
		register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );
		
	} // end constructor
	
	/**
	 * Actions taken when plugin is activated.
	 */
	function activate() { }
	
	/**
	 * Actions taken when plugin is deactivated.
	 */
	function deactivate() { }
	
	/**
	 * Register and enqueue login scripts.
	 */
	function register_login_scripts() {
		wp_register_script( 'oneid', 'https://my.oneid.com/api/js/includeexternal.js', null, null, true );
		wp_enqueue_script( 'oneid' );
	} // end register_login_scripts

	/**
	 * Create OneID options menu.
	 */
	function oneid_options_menu() {
		add_options_page( 'OneID', 'OneID', 'manage_options', 'oneid', array( $this, 'oneid_options_page' ) );
		
		// Register settings
		add_action( 'admin_init', array( &$this, 'register_oneid_options' ) );
	} // end oneid_options_menu

	/**
	 * Register OneID settings fields.
	 */
	function register_oneid_options() {
		register_setting( 'oneid_options', 'oneid_options' );
	} // end register_oneid_options

	/**
	 * Display OneID settings page in admin.
	 */
	function oneid_options_page() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permission to access this page.' ) );
		}
		
		if ( $options = get_option( 'oneid_options' ) ) {
			extract($options);
		}
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2>OneID Settings</h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'oneid_options' ); ?>
				<?php do_settings_sections( 'oneid_options' ); ?>
				<p><label for="oneid_api_id">API ID</label><br />
					<input name="oneid_options[oneid_api_id]" id="oneid_api_id" value="<?php echo $oneid_api_id;?>" /></p>
				<p><label for="oneid_api_key">API Key</label><br />
					<input name="oneid_options[oneid_api_key]" id="oneid_api_key" value="<?php echo $oneid_api_key;?>" /></p>
				<?php submit_button( null, 'primary', 'submit', true ); ?>
			</form>
		</div>
		<?php
	} // end oneid_options_page
	
	/**
	 * Display OneID Button on WP login form.
	 */
	function login_form() {
		$redirect = 'process.php';
		if ( $_GET['redirect_to'] ) $redirect .= "?redirect_to={$_GET['redirect_to']}";
		
		echo '<hr style="clear: both; margin-bottom: 1.0em; border: 0; border-top: 1px solid #999; height: 1px;" />';
		echo '
		<p style="margin-bottom: 8px;">
			<label style="display: block; margin-bottom: 5px;">Or login with <a href="http://www.oneid.com">OneID</a>
		</p>
		<p style="margin-bottom: 8px;">
			'. OneIDAPI::OneID_Button( 'personal_info[email] personal_info[first_name] personal_info[last_name]', plugins_url( $redirect, __FILE__ ) ) .'
		</p>';
	}
}

new OneID();

?>