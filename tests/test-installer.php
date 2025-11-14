<?php
/**
 * Tests for Installer class
 */

use SaltShaker\Installer;

class Test_Installer extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// Remove audit table and options before each test
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		delete_option( 'salt_shaker_audit_options' );
		delete_option( 'salt_shaker_db_version' );
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test plugin installation
	 */
	public function test_install() {
		Installer::install();

		// Check that audit table was created
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		$this->assertEquals( $table_name, $table_exists );
	}

	/**
	 * Test create_audit_table() method
	 */
	public function test_create_audit_table() {
		Installer::create_audit_table();

		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';

		// Verify table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		$this->assertEquals( $table_name, $table_exists );

		// Verify table structure
		$columns = $wpdb->get_results( "DESCRIBE {$table_name}" );
		$column_names = wp_list_pluck( $columns, 'Field' );

		$expected_columns = [
			'id',
			'rotation_time',
			'triggered_by',
			'trigger_username',
			'trigger_method',
			'status',
			'ip_address',
			'user_agent',
			'affected_users',
			'error_message',
			'duration_ms',
			'salt_source',
			'old_salt_hash',
			'new_salt_hash',
			'config_file_path',
			'wp_version',
			'plugin_version',
			'schedule_interval',
			'next_scheduled',
			'metadata',
			'created_at',
		];

		foreach ( $expected_columns as $column ) {
			$this->assertContains( $column, $column_names, "Column {$column} should exist" );
		}

		// Verify indexes
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
		$index_names = wp_list_pluck( $indexes, 'Key_name' );

		$this->assertContains( 'idx_rotation_time', $index_names );
		$this->assertContains( 'idx_triggered_by', $index_names );
		$this->assertContains( 'idx_status', $index_names );
		$this->assertContains( 'idx_method', $index_names );
	}

	/**
	 * Test default audit options are set
	 */
	public function test_default_audit_options() {
		Installer::install();

		$options = get_option( 'salt_shaker_audit_options' );

		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'retention_days', $options );
		$this->assertArrayHasKey( 'failed_retention_days', $options );
		$this->assertArrayHasKey( 'log_ip_addresses', $options );
		$this->assertArrayHasKey( 'log_user_agents', $options );
		$this->assertArrayHasKey( 'auto_cleanup_enabled', $options );

		$this->assertEquals( 90, $options['retention_days'] );
		$this->assertEquals( 180, $options['failed_retention_days'] );
		$this->assertTrue( $options['log_ip_addresses'] );
		$this->assertTrue( $options['log_user_agents'] );
		$this->assertTrue( $options['auto_cleanup_enabled'] );
	}

	/**
	 * Test database version is set
	 */
	public function test_database_version_set() {
		Installer::install();

		$db_version = get_option( 'salt_shaker_db_version' );

		$this->assertNotEmpty( $db_version );
		$this->assertEquals( '1.0', $db_version );
	}

	/**
	 * Test uninstall with delete_data = false
	 */
	public function test_uninstall_without_delete() {
		Installer::install();

		Installer::uninstall( false );

		// Table and options should still exist
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		$this->assertEquals( $table_name, $table_exists );
		$this->assertNotFalse( get_option( 'salt_shaker_audit_options' ) );
	}

	/**
	 * Test uninstall with delete_data = true
	 */
	public function test_uninstall_with_delete() {
		Installer::install();

		Installer::uninstall( true );

		// Table and options should be removed
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		$this->assertNull( $table_exists );
		$this->assertFalse( get_option( 'salt_shaker_audit_options' ) );
		$this->assertFalse( get_option( 'salt_shaker_db_version' ) );
	}

	/**
	 * Test that install() doesn't overwrite existing options
	 */
	public function test_install_preserves_existing_options() {
		// Set custom options
		add_option( 'salt_shaker_audit_options', [
			'retention_days' => 60,
			'failed_retention_days' => 120,
		] );

		Installer::install();

		$options = get_option( 'salt_shaker_audit_options' );

		// Custom values should be preserved
		$this->assertEquals( 60, $options['retention_days'] );
		$this->assertEquals( 120, $options['failed_retention_days'] );
	}
}
