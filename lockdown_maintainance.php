<?php
/*
Plugin Name: Lockdown Maintainance
Plugin URI: https://github.com/klandestino/lockdown-maintainance.git
Description: Plugin to notify and logout users from admin.
Version: 0.1
Author: Tom Bergman
Author URI: http://klandestino.se
Text Domain: lockdown-maintainance
*/

if ( !defined( 'ABSPATH' ) ) exit();

/*###########################################
* Configuration options
*///##########################################


class Maintainance_Lockdown_Core {

	private static $instance;

	function __construct() {
		define( 'LOCKDOWN_ENABLED', 0 == get_option( 'maintainance_lockdown_enabled' ) ? false : true );
		// WP is loaded
		add_action( 'init', array( $this, 'init' ) );

		// Show message when saving settings
		$hook = 'settings_page_maintainance_lockdown_admin_menu';
		add_action( 'load-' . $hook, array( $this, 'maintainance_lockdown_settings_save' ) );

		// Hook up the messaging-system to notify all users of their impending doom
		add_action( 'admin_notices', array( $this, 'showAdminMessages' ) );
		add_action( 'network_admin_notices', array( $this, 'showAdminMessages' ) );


		// Prevent users from login in if in maintainance mode
		add_action( 'wp_login', array( $this, 'lockdown_prevent_login' ) );

		// Wp Heartbeat API
		add_action( 'admin_enqueue_scripts', array( $this, 'lockdown_heartbeat_enqueue' ) );
		add_filter( 'heartbeat_send', array( $this, 'send_data_to_heartbeat' ), 10, 2 );

		require_once dirname( __FILE__ ) . '/settings-page.php';
		$this->settings = new Maintainance_Lockdown_Settings();
	}

	/**
	 * Initialize the singleton
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Maintainance_Lockdown_Core;
		}
		return self::$instance;
	}


	/**
	 * Prevent cloning
	 */
	function __clone() {

	}


	/**
	 * Prevent unserializing
	 */
	function __wakeup() {

	}


	/**
	 * Run during Wordpress action "init".
	 */
	public function init() {

		$this->settings->init();

		// Use GET variables to switch on/off maintainance lockdown
		if ( is_admin() && isset( $_GET[ 'enable-maintainance' ] ) ) {
			if ( 'true' === $_GET[ 'enable-maintainance' ] ) {
				$this->lockdown_maintainance_timer_activate();
			} else {
				$this->lockdown_maintainance_timer_deactivate();
			}
		}
			

		$this->lockdown_maintainance_timer_processOnPageLoad();
	}

	function maintainance_lockdown_settings_save() {
		if ( is_admin() && isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			//plugin settings have been saved.
			if ( get_option( 'maintainance_lockdown_enabled' ) ) {
				$this->lockdown_maintainance_timer_activate();
			} else {
				$this->lockdown_maintainance_timer_deactivate();
			}
		}
	}

	/*###########################################
	* Functions
	*///##########################################

	// Define the logout interval in seconds and set the marker containing the current time on activation
	function lockdown_maintainance_timer_activate() {
		update_option( 'maintainance_lockdown_enabled', 1 );
		$logoutinterval_seconds = get_option( 'maintainance_lockdown_time', 300 );
		update_option( 'lockdown_maintainance_timer_logoutinterval', $logoutinterval_seconds );
		$this->lockdown_maintainance_timer_setmarker();
	}

	// Simple deactivation hook to unset the logout interval
	function lockdown_maintainance_timer_deactivate() {
		update_option( 'maintainance_lockdown_enabled', 0);
		delete_option( 'lockdown_maintainance_timer_logoutinterval' );
	}

	// Sets the current time in a marker upon activation
	function lockdown_maintainance_timer_setmarker() {
		if ( is_user_logged_in() ) {

			// Set maintainance marker for all users
			$users = $this->get_clients();
			foreach ( $users as $user ) {
				update_user_meta( $user->ID, 'lockdown_maintainance_timer_marker', time() );
			}

		}
	}

	// Upon activity by the user (page loads within WP) we check if the logout time has arrived & kick the user accordingly
	function lockdown_maintainance_timer_processOnPageLoad() {
		if ( is_user_logged_in() && LOCKDOWN_ENABLED ) {
			$marker = $this->lockdown_maintainance_timer_getmarker();
			$logoutinterval = get_option( 'lockdown_maintainance_timer_logoutinterval' );

			if ( is_admin() && $marker + $logoutinterval < time() && !current_user_can( 'administrator' ) ) {
				$this->lockdown_logout_user();
			}
		}
	}


	// Read the set time-marker
	function lockdown_maintainance_timer_getmarker() {
		if ( is_user_logged_in() ) {
			return (int) get_user_meta( get_current_user_id(), 'lockdown_maintainance_timer_marker', true );
		} else {
			return 0;
		}
	}

	// Simple function to show messages to logged-in users
	function showMessage( $message, $errormsg = false ) {
		if ( $errormsg ) {
			echo '<div id="message" class="error">';
		} else {
			echo '<div id="message" class="updated fade">';
		}
		echo "<p><strong>$message</strong></p></div>";
	}

	// Define the message to show. 2nd boalean parameter defines regular or error message style
	function showAdminMessages() {
		if ( LOCKDOWN_ENABLED ) {
			$this->showMessage( "Snart börjar underhållsarbete av systemet!<br>Spara eventuella ändringar och <a href=\"".wp_logout_url()."\">logga ut</a>.", true );
		}
	}

	// Prevent login if maintainance is in process
	function lockdown_prevent_login() {
		//TODO: Always Log in admins?
		if ( LOCKDOWN_ENABLED && is_admin() ) {
			//$this->lockdown_logout_user();
		}
	}

	function lockdown_logout_user() {
		wp_logout();
		wp_die( '<div id="message" class="updated"><p><b>Underhållsarbete</b><br>Vi uppdaterar systemet. Vänligen försök om ett tag igen.</p></div>' );
	}

	// Get all users by roles
	function get_clients() {

		$users = array();
		$roles = array( 'administrator', 'editor' );

		foreach ( $roles as $role ) :
			$users_query = new WP_User_Query( array(
					'fields' => 'all_with_meta',
					'role' => $role,
					'orderby' => 'display_name'
				) );
		$results = $users_query->get_results();
		if ( $results ) $users = array_merge( $users, $results );
		endforeach;

		return $users;
	}


	// ********** HEARTBEAT functions ***********/
	function lockdown_heartbeat_enqueue() {

		$dependency = array( 'jquery', 'heartbeat' );

		wp_enqueue_script( 'lockdown_maintainance_js', plugin_dir_url( __FILE__ ) . '/lockdown-maintainance.js', $dependency, 'lockdown', true );
		wp_enqueue_style( 'lockdown_maintainance_css', plugin_dir_url( __FILE__ ) . '/lockdown-maintainance.css' );
	}

	/**
	 * Add data to the heartbeat ajax call (filter)
	 *
	 * @arg Array $data Original data to be sent to Hearbeat
	 * @arg String $screen_id Admin Screen ID
	 *
	 * @return Array Modified array of data to be sent to Hearbeat
	 */
	function send_data_to_heartbeat( $data, $screen_id ) {
		if ( LOCKDOWN_ENABLED ) {
			$data['message'] = array(
				'title' => 'Underhållsarbete',
				'content' => 'Snart börjar underhållsarbete av systemet!<br>Spara eventuella ändringar och <a href="' . wp_logout_url() . '">logga ut</a>.'
			);
		}

		return $data;
	}
}


Maintainance_Lockdown();


/**
 * Allow direct access to Maintainance Lockdown classes
 */
function Maintainance_Lockdown() {
	return Maintainance_Lockdown_Core::instance();
}
