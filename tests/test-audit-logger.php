<?php
/**
 * Tests for AuditLogger class
 */

use SaltShaker\AuditLogger;

class Test_AuditLogger extends WP_UnitTestCase {

	private $audit_logger;

	public function set_up() {
		parent::set_up();
		$this->audit_logger = new AuditLogger();

		// Ensure audit table exists
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	public function tear_down() {
		parent::tear_down();

		// Clean up test data
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	/**
	 * Test that audit logger can be instantiated
	 */
	public function test_audit_logger_instantiation() {
		$this->assertInstanceOf( AuditLogger::class, $this->audit_logger );
	}

	/**
	 * Test start_rotation() method
	 */
	public function test_start_rotation() {
		$this->audit_logger->start_rotation();

		// Use reflection to check private properties
		$reflection = new ReflectionClass( $this->audit_logger );
		$start_time_property = $reflection->getProperty( 'start_time' );
		$start_time_property->setAccessible( true );
		$start_time = $start_time_property->getValue( $this->audit_logger );

		$this->assertGreaterThan( 0, $start_time );
		$this->assertIsFloat( $start_time );
	}

	/**
	 * Test log_success() method
	 */
	public function test_log_success() {
		$this->audit_logger->start_rotation();

		$result = $this->audit_logger->log_success( [
			'salt_source'      => 'wordpress_api',
			'old_salt_hash'    => 'test_old_hash',
			'new_salt_hash'    => 'test_new_hash',
			'config_file_path' => '/path/to/wp-config.php',
			'affected_users'   => 5,
		] );

		$this->assertTrue( $result );

		// Verify log was inserted
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'success'" );
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test log_failure() method
	 */
	public function test_log_failure() {
		$this->audit_logger->start_rotation();

		$result = $this->audit_logger->log_failure( [
			'error_message'    => 'Test error message',
			'config_file_path' => '/path/to/wp-config.php',
			'old_salt_hash'    => 'test_old_hash',
		] );

		$this->assertTrue( $result );

		// Verify log was inserted
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$log = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE status = 'failed'" );

		$this->assertNotNull( $log );
		$this->assertEquals( 'Test error message', $log->error_message );
	}

	/**
	 * Test hash_salts() method
	 */
	public function test_hash_salts() {
		$salts = [
			'AUTH_KEY'        => 'test_key_1',
			'SECURE_AUTH_KEY' => 'test_key_2',
		];

		$hash = $this->audit_logger->hash_salts( $salts );

		$this->assertIsString( $hash );
		$this->assertEquals( 64, strlen( $hash ) ); // SHA256 hash length

		// Same input should produce same hash
		$hash2 = $this->audit_logger->hash_salts( $salts );
		$this->assertEquals( $hash, $hash2 );

		// Different input should produce different hash
		$salts['AUTH_KEY'] = 'different_key';
		$hash3 = $this->audit_logger->hash_salts( $salts );
		$this->assertNotEquals( $hash, $hash3 );
	}

	/**
	 * Test get_logs() method without filters
	 */
	public function test_get_logs_without_filters() {
		// Create some test logs
		for ( $i = 0; $i < 5; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_success( [
				'salt_source' => 'wordpress_api',
			] );
		}

		$result = $this->audit_logger->get_logs();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'logs', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 5, $result['logs'] );
		$this->assertEquals( 5, $result['total'] );
	}

	/**
	 * Test get_logs() with status filter
	 */
	public function test_get_logs_with_status_filter() {
		// Create success logs
		for ( $i = 0; $i < 3; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_success();
		}

		// Create failed logs
		for ( $i = 0; $i < 2; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_failure( [
				'error_message' => 'Test error',
			] );
		}

		$result = $this->audit_logger->get_logs( [ 'status' => 'success' ] );
		$this->assertCount( 3, $result['logs'] );

		$result = $this->audit_logger->get_logs( [ 'status' => 'failed' ] );
		$this->assertCount( 2, $result['logs'] );
	}

	/**
	 * Test get_logs() with pagination
	 */
	public function test_get_logs_with_pagination() {
		// Create 25 test logs
		for ( $i = 0; $i < 25; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_success();
		}

		// Get first page (20 items)
		$result = $this->audit_logger->get_logs( [
			'per_page' => 20,
			'page'     => 1,
		] );

		$this->assertCount( 20, $result['logs'] );
		$this->assertEquals( 25, $result['total'] );

		// Get second page (5 items)
		$result = $this->audit_logger->get_logs( [
			'per_page' => 20,
			'page'     => 2,
		] );

		$this->assertCount( 5, $result['logs'] );
	}

	/**
	 * Test get_log() method
	 */
	public function test_get_log() {
		$this->audit_logger->start_rotation();
		$this->audit_logger->log_success( [
			'salt_source' => 'local_generation',
		] );

		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';
		$log_id = $wpdb->get_var( "SELECT id FROM {$table_name} LIMIT 1" );

		$log = $this->audit_logger->get_log( $log_id );

		$this->assertIsArray( $log );
		$this->assertEquals( 'success', $log['status'] );
		$this->assertEquals( 'local_generation', $log['salt_source'] );
	}

	/**
	 * Test cleanup_old_logs() method
	 */
	public function test_cleanup_old_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'salt_shaker_audit_log';

		// Create a log 100 days old
		$wpdb->insert(
			$table_name,
			[
				'rotation_time' => date( 'Y-m-d H:i:s', strtotime( '-100 days' ) ),
				'status'        => 'success',
				'trigger_method' => 'manual',
			]
		);

		// Create a recent log
		$wpdb->insert(
			$table_name,
			[
				'rotation_time' => current_time( 'mysql' ),
				'status'        => 'success',
				'trigger_method' => 'manual',
			]
		);

		// Cleanup logs older than 90 days
		$deleted = $this->audit_logger->cleanup_old_logs( 90 );

		$this->assertEquals( 1, $deleted );

		// Verify only recent log remains
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test get_stats() method
	 */
	public function test_get_stats() {
		// Create test data
		for ( $i = 0; $i < 10; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_success();
		}

		// Create 2 failed logs
		for ( $i = 0; $i < 2; $i++ ) {
			$this->audit_logger->start_rotation();
			$this->audit_logger->log_failure( [
				'error_message' => 'Test error',
			] );
		}

		$stats = $this->audit_logger->get_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'total_rotations', $stats );
		$this->assertArrayHasKey( 'success_rate', $stats );
		$this->assertArrayHasKey( 'failed_30_days', $stats );
		$this->assertArrayHasKey( 'avg_duration_ms', $stats );
		$this->assertArrayHasKey( 'last_rotation', $stats );

		$this->assertEquals( 12, $stats['total_rotations'] );
		$this->assertEquals( 83.3, $stats['success_rate'] ); // 10/12 * 100
		$this->assertEquals( 2, $stats['failed_30_days'] );
	}
}
