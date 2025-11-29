<?php

namespace SaltShaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AuditAdmin {
	private Core $core;
	private AuditLogger $audit_logger;

	public function __construct( Core $core ) {
		$this->core         = $core;
		$this->audit_logger = new AuditLogger();
	}

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_audit_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_audit_scripts' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// AJAX endpoints
		add_action( 'wp_ajax_salt_shaker_get_audit_logs', [ $this, 'ajax_get_audit_logs' ] );
		add_action( 'wp_ajax_salt_shaker_get_log_detail', [ $this, 'ajax_get_log_detail' ] );
		add_action( 'wp_ajax_salt_shaker_get_audit_stats', [ $this, 'ajax_get_audit_stats' ] );
		add_action( 'wp_ajax_salt_shaker_export_audit_logs', [ $this, 'ajax_export_audit_logs' ] );
		add_action( 'wp_ajax_salt_shaker_save_audit_settings', [ $this, 'ajax_save_audit_settings' ] );
		add_action( 'wp_ajax_salt_shaker_cleanup_audit_logs', [ $this, 'ajax_cleanup_audit_logs' ] );
	}

	/**
	 * Add audit log submenu under Tools > Salt Shaker
	 */
	public function add_audit_menu_item(): void {
		add_submenu_page(
			'salt_shaker',
			__( 'Audit Log', 'salt-shaker' ),
			__( 'Audit Log', 'salt-shaker' ),
			'manage_options',
			'salt_shaker_audit',
			array( $this, 'render_audit_page' )
		);
	}

	/**
	 * Render the audit log page
	 */
	public function render_audit_page(): void {
		echo '<div id="salt-shaker-audit-log"></div>';
	}

	/**
	 * Enqueue scripts for audit log page
	 */
	public function enqueue_audit_scripts( $hook ): void {
		if ( $hook !== 'salt-shaker_page_salt_shaker_audit' ) {
			return;
		}

		wp_enqueue_style( 'wp-components' );

		wp_register_script(
			'salt-shaker-audit',
			SALT_SHAKER_PLUGIN_URL . 'assets/build/audit.js',
			[ 'wp-element', 'wp-components', 'wp-i18n', 'jquery' ],
			SALT_SHAKER_VERSION,
			true
		);

		wp_set_script_translations( 'salt-shaker-audit', 'salt-shaker', SALT_SHAKER_PATH . '/languages' );

		wp_localize_script( 'salt-shaker-audit', 'saltShakerAuditData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'salt-shaker-nonce' ),
		] );

		wp_enqueue_script( 'salt-shaker-audit' );
		wp_enqueue_style(
			'salt-shaker-audit',
			SALT_SHAKER_PLUGIN_URL . 'assets/css/audit.css',
			[ 'wp-components' ],
			SALT_SHAKER_VERSION
		);
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'salt_shaker_audit_widget',
			__( 'Salt Shaker Status', 'salt-shaker' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget
	 */
	public function render_dashboard_widget(): void {
		$stats = $this->audit_logger->get_stats();

		// Enqueue widget styles
		wp_enqueue_style(
			'salt-shaker-widget',
			SALT_SHAKER_PLUGIN_URL . 'assets/css/widget.css',
			[],
			SALT_SHAKER_VERSION
		);

		?>
		<div class="salt-shaker-widget">
			<?php if ( $stats['last_rotation'] ) : ?>
				<div class="widget-section">
					<h4><?php esc_html_e( 'Last Rotation', 'salt-shaker' ); ?></h4>
					<div class="widget-status <?php echo esc_attr( $stats['last_rotation']['status'] ); ?>">
						<?php if ( $stats['last_rotation']['status'] === 'success' ) : ?>
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Success', 'salt-shaker' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Failed', 'salt-shaker' ); ?>
						<?php endif; ?>
					</div>
					<p class="widget-time">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: human-readable time difference */
								__( '%s ago', 'salt-shaker' ),
								human_time_diff( strtotime( $stats['last_rotation']['rotation_time'] ), current_time( 'timestamp' ) )
							)
						);
						?>
						<br>
						<small><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $stats['last_rotation']['rotation_time'] ) ) ); ?></small>
					</p>
				</div>
			<?php else : ?>
				<div class="widget-section">
					<p><?php esc_html_e( 'No rotations have been performed yet.', 'salt-shaker' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $stats['next_scheduled'] ) : ?>
				<div class="widget-section">
					<h4><?php esc_html_e( 'Next Scheduled', 'salt-shaker' ); ?></h4>
					<p class="widget-time">
						<?php
						$next_timestamp = $stats['next_scheduled_timestamp'];
						$time_until     = human_time_diff( current_time( 'timestamp' ), $next_timestamp );
						echo esc_html(
							sprintf(
								/* translators: %s: human-readable time difference */
								__( 'in %s', 'salt-shaker' ),
								$time_until
							)
						);
						?>
						<br>
						<small><?php echo esc_html( $stats['next_scheduled'] ); ?></small>
					</p>
				</div>
			<?php endif; ?>

			<div class="widget-section widget-stats">
				<div class="stat-item">
					<span class="stat-value"><?php echo esc_html( $stats['total_rotations'] ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Rotations', 'salt-shaker' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-value stat-success"><?php echo esc_html( $stats['success_rate'] ); ?>%</span>
					<span class="stat-label"><?php esc_html_e( 'Success Rate', 'salt-shaker' ); ?></span>
				</div>
				<?php if ( $stats['failed_30_days'] > 0 ) : ?>
					<div class="stat-item">
						<span class="stat-value stat-failed"><?php echo esc_html( $stats['failed_30_days'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Failed (30d)', 'salt-shaker' ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<div class="widget-actions">
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=salt_shaker_audit' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'View Audit Log', 'salt-shaker' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=salt_shaker' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Settings', 'salt-shaker' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Get audit logs with filters and pagination
	 */
	public function ajax_get_audit_logs(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$method   = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';
		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : '';
		$orderby  = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'rotation_time';
		$order    = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';

		$args = [
			'page'     => $page,
			'per_page' => $per_page,
			'status'   => $status,
			'method'   => $method,
			'user_id'  => $user_id,
			'orderby'  => $orderby,
			'order'    => $order,
		];

		$result = $this->audit_logger->get_logs( $args );

		wp_send_json_success( [
			'logs'       => $result['logs'],
			'total'      => $result['total'],
			'total_pages' => ceil( $result['total'] / $per_page ),
		] );
	}

	/**
	 * AJAX: Get single log detail
	 */
	public function ajax_get_log_detail(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! $log_id ) {
			wp_send_json_error( __( 'Invalid log ID', 'salt-shaker' ) );
		}

		$log = $this->audit_logger->get_log( $log_id );

		if ( ! $log ) {
			wp_send_json_error( __( 'Log not found', 'salt-shaker' ) );
		}

		wp_send_json_success( $log );
	}

	/**
	 * AJAX: Get audit statistics
	 */
	public function ajax_get_audit_stats(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$stats = $this->audit_logger->get_stats();

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX: Export audit logs
	 */
	public function ajax_export_audit_logs(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$format = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : 'csv';
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$method = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';

		// Get all logs (no pagination for export)
		$args = [
			'per_page' => 9999,
			'page'     => 1,
			'status'   => $status,
			'method'   => $method,
		];

		$result = $this->audit_logger->get_logs( $args );
		$logs   = $result['logs'];

		if ( $format === 'csv' ) {
			$this->export_csv( $logs );
		} else {
			$this->export_json( $logs );
		}
	}

	/**
	 * Export logs as CSV
	 */
	private function export_csv( array $logs ): void {
		$filename = 'salt-shaker-audit-' . date( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// CSV headers
		fputcsv( $output, [
			'Date/Time',
			'Triggered By',
			'Method',
			'Status',
			'Duration (ms)',
			'Affected Users',
			'Salt Source',
			'IP Address',
			'Error Message',
		] );

		// CSV rows
		foreach ( $logs as $log ) {
			fputcsv( $output, [
				$log['rotation_time'],
				$log['trigger_username'],
				$log['trigger_method'],
				$log['status'],
				$log['duration_ms'],
				$log['affected_users'],
				$log['salt_source'],
				$log['ip_address'] ?? '',
				$log['error_message'] ?? '',
			] );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export logs as JSON
	 */
	private function export_json( array $logs ): void {
		$filename = 'salt-shaker-audit-' . date( 'Y-m-d-His' ) . '.json';

		$export_data = [
			'export_date'    => current_time( 'mysql' ),
			'plugin_version' => SALT_SHAKER_VERSION,
			'total_records'  => count( $logs ),
			'logs'           => $logs,
		];

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * AJAX: Save audit settings
	 */
	public function ajax_save_audit_settings(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$retention_days        = isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : 90;
		$failed_retention_days = isset( $_POST['failed_retention_days'] ) ? absint( $_POST['failed_retention_days'] ) : 180;
		$auto_cleanup          = filter_var( wp_unslash( $_POST['auto_cleanup_enabled'] ?? true ), FILTER_VALIDATE_BOOLEAN );

		// Validate retention days (min 1, max 365)
		$retention_days        = max( 1, min( 365, $retention_days ) );
		$failed_retention_days = max( 1, min( 730, $failed_retention_days ) );

		$options = [
			'retention_days'        => $retention_days,
			'failed_retention_days' => $failed_retention_days,
			'log_ip_addresses'      => true,
			'log_user_agents'       => true,
			'auto_cleanup_enabled'  => $auto_cleanup,
		];

		update_option( 'salt_shaker_audit_options', $options );

		wp_send_json_success( [
			'message' => __( 'Audit settings saved successfully', 'salt-shaker' ),
		] );
	}

	/**
	 * AJAX: Manually cleanup old logs
	 */
	public function ajax_cleanup_audit_logs(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$options        = get_option( 'salt_shaker_audit_options', [] );
		$retention_days = $options['retention_days'] ?? 90;

		$deleted = $this->audit_logger->cleanup_old_logs( $retention_days, false );

		wp_send_json_success( [
			'message' => sprintf(
			/* translators: %d: number of deleted logs */
				__( 'Successfully deleted %d old log entries', 'salt-shaker' ),
				$deleted
			),
			'deleted' => $deleted,
		] );
	}
}
