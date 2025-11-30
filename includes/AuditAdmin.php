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
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// AJAX endpoints for audit features
		add_action( 'wp_ajax_salt_shaker_get_audit_stats', [ $this, 'ajax_get_audit_stats' ] );
		add_action( 'wp_ajax_salt_shaker_get_audit_settings', [ $this, 'ajax_get_audit_settings' ] );
		add_action( 'wp_ajax_salt_shaker_save_audit_settings', [ $this, 'ajax_save_audit_settings' ] );
		add_action( 'wp_ajax_salt_shaker_cleanup_audit_logs', [ $this, 'ajax_cleanup_audit_logs' ] );
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
						$rotation_timestamp = strtotime( $stats['last_rotation']['rotation_time'] . ' UTC' );
						echo esc_html(
							sprintf(
								/* translators: %s: human-readable time difference */
								__( '%s ago', 'salt-shaker' ),
								human_time_diff( $rotation_timestamp, time() )
							)
						);
						?>
						<br>
						<small><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $rotation_timestamp ) ); ?></small>
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
						$time_until     = human_time_diff( time(), $next_timestamp );
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

			<?php if ( $stats['failed_30_days'] > 0 ) : ?>
				<div class="widget-section widget-stats">
					<div class="stat-item">
						<span class="stat-value stat-failed"><?php echo esc_html( $stats['failed_30_days'] ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Failed (30d)', 'salt-shaker' ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<div class="widget-actions">
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=salt_shaker' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'View Settings', 'salt-shaker' ); ?>
				</a>
			</div>
		</div>
		<?php
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
	 * AJAX: Get audit settings
	 */
	public function ajax_get_audit_settings(): void {
		check_ajax_referer( 'salt-shaker-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'salt-shaker' ) );
		}

		$options = get_option( 'salt_shaker_audit_options', [] );

		wp_send_json_success( [
			'retention_days'        => $options['retention_days'] ?? 90,
			'failed_retention_days' => $options['failed_retention_days'] ?? 180,
			'auto_cleanup_enabled'  => $options['auto_cleanup_enabled'] ?? true,
		] );
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
			'log_user_agents'       => true,
			'auto_cleanup_enabled'  => $auto_cleanup,
		];

		update_option( 'salt_shaker_audit_options', $options );

		// Update cron schedule based on new setting
		$is_scheduled = wp_next_scheduled( 'salt_shaker_cleanup_old_logs' );
		if ( $auto_cleanup && ! $is_scheduled ) {
			wp_schedule_event( time(), 'daily', 'salt_shaker_cleanup_old_logs' );
		} elseif ( ! $auto_cleanup && $is_scheduled ) {
			wp_unschedule_event( $is_scheduled, 'salt_shaker_cleanup_old_logs' );
		}

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

		$options          = get_option( 'salt_shaker_audit_options', [] );
		$retention_days   = $options['retention_days'] ?? 90;
		$failed_retention = $options['failed_retention_days'] ?? 180;

		// Clean up successful logs older than retention_days
		$deleted_success = $this->audit_logger->cleanup_old_logs( $retention_days, 'success' );

		// Clean up failed logs older than failed_retention_days
		$deleted_failed = $this->audit_logger->cleanup_old_logs( $failed_retention, 'failed' );

		$total_deleted = $deleted_success + $deleted_failed;

		wp_send_json_success( [
			'message' => sprintf(
			/* translators: %d: number of deleted logs */
				__( 'Successfully deleted %d old log entries', 'salt-shaker' ),
				$total_deleted
			),
			'deleted' => $total_deleted,
		] );
	}
}
