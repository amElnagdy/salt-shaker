<?php

namespace SaltShaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AuditLogger {
	private const TABLE_NAME = 'salt_shaker_audit_log';

	/**
	 * Start time for rotation timing
	 *
	 * @var float
	 */
	private float $start_time = 0;

	/**
	 * Data collected during rotation
	 *
	 * @var array
	 */
	private array $rotation_data = [];

	/**
	 * Start tracking a rotation event
	 *
	 * @return void
	 */
	public function start_rotation(): void {
		$this->start_time    = microtime( true );
		$this->rotation_data = [
			'rotation_time'     => current_time( 'mysql' ),
			'triggered_by'      => get_current_user_id(),
			'trigger_username'  => $this->get_trigger_username(),
			'trigger_method'    => $this->get_trigger_method(),
			'ip_address'        => $this->get_client_ip(),
			'user_agent'        => $this->get_user_agent(),
			'wp_version'        => get_bloginfo( 'version' ),
			'plugin_version'    => SALT_SHAKER_VERSION,
			'schedule_interval' => get_option( 'salt_shaker_options' )['salt_shaker_update_interval'] ?? null,
		];
	}

	/**
	 * Log a successful rotation
	 *
	 * @param array $additional_data Additional data to log
	 *
	 * @return bool
	 */
	public function log_success( array $additional_data = [] ): bool {
		$duration_ms = $this->calculate_duration();

		$data = array_merge( $this->rotation_data, [
			'status'      => 'success',
			'duration_ms' => $duration_ms,
		], $additional_data );

		// Get next scheduled time if applicable
		$next_scheduled = wp_next_scheduled( 'salt_shaker_change_salts' );
		if ( $next_scheduled ) {
			$data['next_scheduled'] = date( 'Y-m-d H:i:s', $next_scheduled );
		}

		return $this->insert_log( $data );
	}

	/**
	 * Log a failed rotation
	 *
	 * @param array $additional_data Additional data including error_message
	 *
	 * @return bool
	 */
	public function log_failure( array $additional_data = [] ): bool {
		$duration_ms = $this->calculate_duration();

		$data = array_merge( $this->rotation_data, [
			'status'      => 'failed',
			'duration_ms' => $duration_ms,
		], $additional_data );

		return $this->insert_log( $data );
	}

	/**
	 * Insert a log entry into the database
	 *
	 * @param array $data Log data
	 *
	 * @return bool
	 */
	private function insert_log( array $data ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Ensure all expected fields have defaults
		$defaults = [
			'rotation_time'      => current_time( 'mysql' ),
			'triggered_by'       => 0,
			'trigger_username'   => '',
			'trigger_method'     => 'manual',
			'status'             => 'success',
			'ip_address'         => null,
			'user_agent'         => null,
			'affected_users'     => 0,
			'error_message'      => null,
			'duration_ms'        => 0,
			'salt_source'        => 'wordpress_api',
			'old_salt_hash'      => null,
			'new_salt_hash'      => null,
			'config_file_path'   => null,
			'wp_version'         => get_bloginfo( 'version' ),
			'plugin_version'     => SALT_SHAKER_VERSION,
			'schedule_interval'  => null,
			'next_scheduled'     => null,
			'metadata'           => null,
		];

		$data = wp_parse_args( $data, $defaults );

		// Convert metadata array to JSON if needed
		if ( is_array( $data['metadata'] ) ) {
			$data['metadata'] = wp_json_encode( $data['metadata'] );
		}

		$result = $wpdb->insert(
			$table_name,
			$data,
			[
				'%s', // rotation_time
				'%d', // triggered_by
				'%s', // trigger_username
				'%s', // trigger_method
				'%s', // status
				'%s', // ip_address
				'%s', // user_agent
				'%d', // affected_users
				'%s', // error_message
				'%d', // duration_ms
				'%s', // salt_source
				'%s', // old_salt_hash
				'%s', // new_salt_hash
				'%s', // config_file_path
				'%s', // wp_version
				'%s', // plugin_version
				'%s', // schedule_interval
				'%s', // next_scheduled
				'%s', // metadata
			]
		);

		return $result !== false;
	}

	/**
	 * Calculate duration since start_rotation() was called
	 *
	 * @return int Duration in milliseconds
	 */
	private function calculate_duration(): int {
		if ( $this->start_time === 0 ) {
			return 0;
		}

		return (int) round( ( microtime( true ) - $this->start_time ) * 1000 );
	}

	/**
	 * Get the username of who triggered the rotation
	 *
	 * @return string
	 */
	private function get_trigger_username(): string {
		if ( wp_doing_cron() ) {
			return 'System (Cron)';
		}

		$user = wp_get_current_user();

		return $user->exists() ? $user->user_login : 'Unknown';
	}

	/**
	 * Get the trigger method
	 *
	 * @return string
	 */
	private function get_trigger_method(): string {
		if ( wp_doing_cron() ) {
			return 'scheduled';
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'cli';
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'api';
		}

		return 'manual';
	}

	/**
	 * Get client IP address
	 *
	 * @return string|null
	 */
	private function get_client_ip(): ?string {
		// Check if IP logging is enabled
		$options = get_option( 'salt_shaker_audit_options', [] );
		if ( isset( $options['log_ip_addresses'] ) && ! $options['log_ip_addresses'] ) {
			return null;
		}

		$ip_keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle multiple IPs (X-Forwarded-For can contain multiple)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return null;
	}

	/**
	 * Get user agent string
	 *
	 * @return string|null
	 */
	private function get_user_agent(): ?string {
		// Check if user agent logging is enabled
		$options = get_option( 'salt_shaker_audit_options', [] );
		if ( isset( $options['log_user_agents'] ) && ! $options['log_user_agents'] ) {
			return null;
		}

		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return null;
	}

	/**
	 * Generate SHA256 hash of salt array for verification
	 *
	 * @param array $salts Array of salt values
	 *
	 * @return string
	 */
	public function hash_salts( array $salts ): string {
		return hash( 'sha256', wp_json_encode( $salts ) );
	}

	/**
	 * Get audit logs with optional filters and pagination
	 *
	 * @param array $args Query arguments
	 *
	 * @return array
	 */
	public function get_logs( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'status'   => '',
			'method'   => '',
			'user_id'  => '',
			'orderby'  => 'rotation_time',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$where      = [ '1=1' ];
		$where_args = [];

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$where[]      = 'status = %s';
			$where_args[] = $args['status'];
		}

		// Method filter
		if ( ! empty( $args['method'] ) ) {
			$where[]      = 'trigger_method = %s';
			$where_args[] = $args['method'];
		}

		// User filter
		if ( ! empty( $args['user_id'] ) ) {
			$where[]      = 'triggered_by = %d';
			$where_args[] = $args['user_id'];
		}

		$where_clause = implode( ' AND ', $where );

		// Build query
		$query = "SELECT * FROM {$table_name} WHERE {$where_clause}";

		// Add ORDER BY
		$allowed_orderby = [ 'rotation_time', 'status', 'trigger_method', 'duration_ms' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'rotation_time';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$query           .= " ORDER BY {$orderby} {$order}";

		// Add pagination
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$query  .= $wpdb->prepare( " LIMIT %d OFFSET %d", $args['per_page'], $offset );

		// Prepare full query
		if ( ! empty( $where_args ) ) {
			$query = $wpdb->prepare( $query, ...$where_args );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		// Get total count for pagination
		$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
		if ( ! empty( $where_args ) ) {
			$count_query = $wpdb->prepare( $count_query, ...$where_args );
		}
		$total = $wpdb->get_var( $count_query );

		return [
			'logs'  => $results,
			'total' => (int) $total,
		];
	}

	/**
	 * Get a single log entry by ID
	 *
	 * @param int $log_id Log ID
	 *
	 * @return array|null
	 */
	public function get_log( int $log_id ): ?array {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $log_id ),
			ARRAY_A
		);

		return $log ?: null;
	}

	/**
	 * Delete logs older than specified days
	 *
	 * @param int  $days        Number of days to keep
	 * @param bool $failed_only Only delete failed logs
	 *
	 * @return int Number of deleted rows
	 */
	public function cleanup_old_logs( int $days = 90, bool $failed_only = false ): int {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$date       = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		if ( $failed_only ) {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_name} WHERE rotation_time < %s AND status = %s",
					$date,
					'failed'
				)
			);
		} else {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_name} WHERE rotation_time < %s",
					$date
				)
			);
		}

		return (int) $deleted;
	}

	/**
	 * Get statistics for dashboard
	 *
	 * @return array
	 */
	public function get_stats(): array {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Total rotations
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		// Success count
		$success_count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'success' )
		);

		// Failed in last 30 days
		$failed_30_days = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE status = %s AND rotation_time > %s",
				'failed',
				date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
			)
		);

		// Average duration
		$avg_duration = (int) $wpdb->get_var( "SELECT AVG(duration_ms) FROM {$table_name}" );

		// Last rotation
		$last_rotation = $wpdb->get_row(
			"SELECT * FROM {$table_name} ORDER BY rotation_time DESC LIMIT 1",
			ARRAY_A
		);

		// Next scheduled
		$next_scheduled = wp_next_scheduled( 'salt_shaker_change_salts' );
		$next_date      = $next_scheduled ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) : null;

		return [
			'total_rotations'  => $total,
			'success_rate'     => $total > 0 ? round( ( $success_count / $total ) * 100, 1 ) : 0,
			'failed_30_days'   => $failed_30_days,
			'avg_duration_ms'  => $avg_duration,
			'last_rotation'    => $last_rotation,
			'next_scheduled'   => $next_date,
			'next_scheduled_timestamp' => $next_scheduled,
		];
	}
}
