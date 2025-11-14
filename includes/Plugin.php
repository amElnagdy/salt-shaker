<?php

namespace SaltShaker;

class Plugin {
	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * Main Plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	public function run(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$options = new Options();
		$core    = new Core();
		$admin   = new Admin( $core, $options );
		$admin->init();

		// Initialize audit admin
		$audit_admin = new AuditAdmin( $core );
		$audit_admin->init();

		$this->setup_audit_cleanup();
		$this->load_freemius();
		do_action( 'ss_fs_loaded' );
	}

	/**
	 * Setup automatic audit log cleanup
	 *
	 * @return void
	 */
	public function setup_audit_cleanup(): void {
		// Register the cleanup action
		add_action( 'salt_shaker_cleanup_old_logs', array( $this, 'cleanup_audit_logs' ) );

		// Schedule the cleanup if not already scheduled
		if ( ! wp_next_scheduled( 'salt_shaker_cleanup_old_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'salt_shaker_cleanup_old_logs' );
		}
	}

	/**
	 * Clean up old audit logs based on retention settings
	 *
	 * @return void
	 */
	public function cleanup_audit_logs(): void {
		$options = get_option( 'salt_shaker_audit_options', [] );

		// Check if auto cleanup is enabled
		if ( ! isset( $options['auto_cleanup_enabled'] ) || ! $options['auto_cleanup_enabled'] ) {
			return;
		}

		$audit_logger        = new AuditLogger();
		$retention_days      = $options['retention_days'] ?? 90;
		$failed_retention    = $options['failed_retention_days'] ?? 180;

		// Clean up successful rotations older than retention_days
		$audit_logger->cleanup_old_logs( $retention_days, false );

		// Note: We keep all logs (including failed) for failed_retention_days
		// So we only delete failed logs that are older than failed_retention_days
		// This is handled separately if needed
	}

	public function load_freemius() {
		global $ss_fs;
		if ( ! isset( $ss_fs ) ) {
			$ss_fs = fs_dynamic_init( array(
				'id'              => '8851',
				'slug'            => 'salt-shaker',
				'premium_slug'    => 'salt-shaker-pro',
				'type'            => 'plugin',
				'public_key'      => 'pk_f3d8cc8437a2ffddb2e1db1c8ad0e',
				'is_premium'      => false,
				'is_premium_only' => false,
				'has_addons'      => false,
				'has_paid_plans'  => true,
				'menu'            => array(
					'slug'       => 'salt_shaker',
					'first-path' => 'tools.php?page=salt_shaker',
					'contact'    => false,
					'parent'     => array(
						'slug' => 'tools.php',
					),
				),
			) );
		}

		return $ss_fs;
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'salt-shaker',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}
