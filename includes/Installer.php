<?php

namespace SaltShaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {
	/**
	 * Run installation tasks
	 *
	 * @return void
	 */
	public static function install(): void {
		self::create_audit_table();
		self::set_default_audit_options();
		self::maybe_upgrade();
	}

	/**
	 * Create the audit log table
	 *
	 * @return void
	 */
	public static function create_audit_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'salt_shaker_audit_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			rotation_time DATETIME NOT NULL,
			triggered_by BIGINT UNSIGNED DEFAULT 0,
			trigger_username VARCHAR(60) DEFAULT '',
			trigger_method VARCHAR(20) NOT NULL,
			status VARCHAR(20) NOT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent TEXT DEFAULT NULL,
			affected_users INT UNSIGNED DEFAULT 0,
			error_message TEXT DEFAULT NULL,
			duration_ms INT UNSIGNED DEFAULT 0,
			salt_source VARCHAR(20) DEFAULT 'wordpress_api',
			old_salt_hash VARCHAR(64) DEFAULT NULL,
			new_salt_hash VARCHAR(64) DEFAULT NULL,
			config_file_path VARCHAR(255) DEFAULT NULL,
			wp_version VARCHAR(20) DEFAULT NULL,
			plugin_version VARCHAR(20) DEFAULT NULL,
			schedule_interval VARCHAR(20) DEFAULT NULL,
			next_scheduled DATETIME DEFAULT NULL,
			metadata LONGTEXT DEFAULT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_rotation_time (rotation_time),
			INDEX idx_triggered_by (triggered_by),
			INDEX idx_status (status),
			INDEX idx_method (trigger_method)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Store the database version
		update_option( 'salt_shaker_db_version', '1.0' );
	}

	/**
	 * Set default audit options
	 *
	 * @return void
	 */
	private static function set_default_audit_options(): void {
		$default_options = [
			'retention_days'         => 90,
			'failed_retention_days'  => 180,
			'log_user_agents'        => true,
			'auto_cleanup_enabled'   => true,
		];

		// Only set if not already exists
		if ( ! get_option( 'salt_shaker_audit_options' ) ) {
			add_option( 'salt_shaker_audit_options', $default_options );
		}
	}

	/**
	 * Handle upgrades between versions
	 *
	 * @return void
	 */
	private static function maybe_upgrade(): void {
		$current_db_version = get_option( 'salt_shaker_db_version', '0' );

		// If we need to upgrade in the future, add logic here
		if ( version_compare( $current_db_version, '1.0', '<' ) ) {
			self::create_audit_table();
		}
	}

	/**
	 * Cleanup on plugin deactivation (optional - keeps data by default)
	 *
	 * @param bool $delete_data Whether to delete all data
	 *
	 * @return void
	 */
	public static function uninstall( bool $delete_data = false ): void {
		if ( ! $delete_data ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';

		// Drop the table
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table_name uses $wpdb->prefix which is safe
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// Delete options
		delete_option( 'salt_shaker_audit_options' );
		delete_option( 'salt_shaker_db_version' );
	}
}
