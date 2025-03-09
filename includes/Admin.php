<?php

namespace SaltShaker;

use Exception;

class Admin {
	private Core $core;
	private Options $options;

	public function __construct( Core $core, Options $options ) {
		$this->core    = $core;
		$this->options = $options;
	}

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'display_permission_notices' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( SALT_SHAKER_PLUGIN_FILE ), [
			$this,
			'add_settings_link'
		] );

		add_action( 'wp_ajax_salt_shaker_get_settings', [ $this, 'ajax_get_settings' ] );
		add_action( 'wp_ajax_salt_shaker_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_salt_shaker_change_salts', [ $this, 'ajax_change_salts' ] );
	}

	/**
	 * Add a settings link to the plugin action links on the Plugins page.
	 *
	 * @param array $actions An array of plugin action links.
	 *
	 * @return array Modified array of plugin action links.
	 */
	public function add_settings_link( array $actions ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'tools.php?page=salt_shaker' ) ) . '">' . __( 'Settings', 'salt-shaker' ) . '</a>';
		array_unshift( $actions, $settings_link );

		return $actions;
	}

    /**
     * Add a menu item to the WordPress admin → Tools menu.
     */
	public function add_menu_item(): void {
		add_submenu_page(
			'tools.php',
			__( 'Salt Shaker Settings', 'salt-shaker' ),
			__( 'Salt Shaker', 'salt-shaker' ),
			'manage_options',
			'salt_shaker',
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page(): void {
		$permissions = $this->core->checkConfigFilePermissions();
		if ( ! $permissions['writable'] ) {
			?>
            <div class="notice notice-error">
                <p>
					<?php
					printf(
					/* translators: 1: https://codex.wordpress.org/Changing_File_Permissions */
						wp_kses(
							__( 'The file which contains your salt keys is not writable. First, make sure it exists then read how to setup the correct permissions on <a href="%1$s">WordPress codex</a>.', 'salt-shaker' ),
							array(
								'code' => array(),
								'a'    => array(
									'href' => array()
								)
							)
						),
						'https://wordpress.org/support/article/changing-file-permissions/'
					);
					?>
                </p>
            </div>
			<?php
		}

		echo '<div id="salt-shaker-settings"></div>';
	}

    public function enqueue_admin_scripts( $hook ): void {
		if ( $hook !== 'tools_page_salt_shaker' ) {
			return;
		}

		wp_enqueue_style( 'wp-components' );

		wp_register_script(
			'salt-shaker-admin',
			SALT_SHAKER_PLUGIN_URL . 'assets/build/admin.js',
			[ 'wp-element', 'wp-components', 'wp-i18n', 'jquery' ],
			SALT_SHAKER_VERSION,
			true
		);

		wp_set_script_translations( 'salt-shaker-admin', 'salt-shaker', SALT_SHAKER_PATH . '/languages' );

		wp_localize_script( 'salt-shaker-admin', 'saltShakerData', [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'salt-shaker-nonce' ),
			'loginUrl' => wp_login_url(),
			'adminUrl' => admin_url( 'tools.php?page=salt_shaker' )
		] );

		wp_enqueue_script( 'salt-shaker-admin' );
		wp_enqueue_style(
			'salt-shaker-admin',
			SALT_SHAKER_PLUGIN_URL . 'assets/css/admin.css',
			[ 'wp-components' ],
			SALT_SHAKER_VERSION
		);
	}

	public function ajax_get_settings(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$next_scheduled = wp_next_scheduled( 'salt_shaker_change_salts' );
		$next_date      = $next_scheduled ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) : null;

		$permissions = $this->core->checkConfigFilePermissions();

		wp_send_json_success( [
			'autoUpdateEnabled' => $this->options->is_auto_update_enabled(),
			'updateInterval'    => $this->options->get_update_interval(),
			'currentSalts'      => $this->core->getSaltsArray(),
			'nextScheduledDate' => $next_date,
			'isConfigWritable'  => $permissions['writable']
		] );
	}

	public function ajax_save_settings(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$permissions = $this->core->checkConfigFilePermissions();
		if ( ! $permissions['writable'] ) {
			wp_send_json_error( __( 'Cannot save settings: wp-config.php is not writable.', 'salt-shaker' ) );
		}

		$auto_update = filter_var( wp_unslash( $_POST['autoUpdateEnabled'] ?? false ), FILTER_VALIDATE_BOOLEAN );
		$interval    = sanitize_text_field( wp_unslash( $_POST['updateInterval'] ?? 'weekly' ) );

		// Validate interval
		$valid_intervals = [ 'daily', 'weekly', 'monthly', 'quarterly', 'biannually' ];
		if ( ! in_array( $interval, $valid_intervals ) ) {
			$interval = 'weekly'; // Default to weekly if invalid
		}

		$this->options->set_auto_update_enabled( $auto_update );
		$this->options->set_update_interval( $interval );

		if ( $auto_update ) {
			wp_clear_scheduled_hook( 'salt_shaker_change_salts' );
			$schedules = wp_get_schedules();

			if ( ! isset( $schedules[ $interval ] ) ) {
				wp_send_json_error( __( 'Invalid schedule interval', 'salt-shaker' ) );
			}
			// Schedule the new hook for the next interval instead of immediately
			$next_run  = time() + $schedules[ $interval ]['interval'];
			$scheduled = wp_schedule_event( $next_run, $interval, 'salt_shaker_change_salts' );

			if ( ! $scheduled ) {
				wp_send_json_error( __( 'Failed to schedule the event', 'salt-shaker' ) );
			}

			$next_scheduled = wp_next_scheduled( 'salt_shaker_change_salts' );
			$next_date      = $next_scheduled ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) : null;
		} else {
			wp_clear_scheduled_hook( 'salt_shaker_change_salts' );
			$next_date = null;
		}

		wp_send_json_success( [
			'message'           => __( 'Settings saved successfully', 'salt-shaker' ),
			'nextScheduledDate' => $next_date
		] );
	}

	public function ajax_change_salts(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$permissions = $this->core->checkConfigFilePermissions();
		if ( ! $permissions['writable'] ) {
			wp_send_json_error( __( 'Cannot change salts: wp-config.php is not writable.', 'salt-shaker' ) );
		}

		try {
			$this->core->shuffleSalts();
			wp_send_json_success( __( 'Salt keys updated successfully', 'salt-shaker' ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Display admin notices for file permission issues
	 */
	public function display_permission_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$permissions = $this->core->checkConfigFilePermissions();

		if ( ! $permissions['writable'] ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p>
					<?php
					$url  = esc_url( admin_url( 'tools.php?page=salt_shaker' ) );
					$link = sprintf(
						wp_kses(
						/* translators: %s: URL to Salt Shaker settings page */
							__( 'Salt Shaker is not working due to a configuration error. Please visit <a href="%s">the settings page</a> to resolve this error.', 'salt-shaker' ),
							array(
								'a' => array(
									'href' => array()
								)
							)
						),
						$url
					);
					echo $link;
					?>
                </p>
            </div>
			<?php
		}
	}
}
