<?php
if (!defined('ABSPATH')) exit();

class Maintainance_Lockdown_Settings {

	/**
	 * Run during Wordpress action "init".
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		//add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );		

	}

	/**
	 * Init admin menu.
	 */
	public function admin_menu() {
		$hook = add_options_page( 'Maintainance Lockdown', 'Maintainance Lockdown', 'manage_options', 'maintainance_lockdown_admin_menu', array( $this, 'submenu_page' ) );
	}

	/**
	 * Init network admin menu.
	 */
	//public function network_admin_menu() {
	//	add_submenu_page( 'settings.php', 'Maintainance Lockdown', 'Maintainance Lockdown', 'manage_options', 'maintainance_lockdown_admin_menu', array( $this, 'submenu_page' ) );
	//}

	/**
	 * Register settings.
	 */

	public function register_settings() {
		register_setting( 'maintainance-lockdown-settings-group', 'maintainance_lockdown_time' );
		register_setting( 'maintainance-lockdown-settings-group', 'maintainance_lockdown_enabled' );
	}

	/**
	 * The submenu page.
	 */

	public function submenu_page() {
		?>
			<div class="wrap">
				<h2>Maintainance Lockdown</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'maintainance-lockdown-settings-group' ); ?>
					<?php do_settings_sections( 'maintainance-lockdown-settings-group' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Maintainance mode enabled</th>
							<td>No <input name="maintainance_lockdown_enabled" type="radio" value="0" <?php checked( '0', get_option( 'maintainance_lockdown_enabled' ) ); ?> />
								Yes <input name="maintainance_lockdown_enabled" type="radio" value="1" <?php checked( '1', get_option( 'maintainance_lockdown_enabled' ) ); ?> /></td>
						</tr>
						<tr valign="top">
							<th scope="row">Countdown time (in seconds)</th>
							<td><input type="text" name="maintainance_lockdown_time" value="<?php echo esc_attr( get_option( 'maintainance_lockdown_time' ) ); ?>" /></td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}
}