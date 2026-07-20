<?php

namespace SaltShaker;

use Error;
use Exception;

class Core {
	private const SALT_KEYS = [
		'AUTH_KEY',
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		'AUTH_SALT',
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT'
	];

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'salt_shaker_change_salts', array( $this, 'shuffleSalts' ) );
	}

	/**
	 * Prepare WordPress filesystem access.
	 *
	 * @return object|false The filesystem handle, or false if not available.
	 */
	private function initFilesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! function_exists( 'get_filesystem_method' ) || 'direct' !== get_filesystem_method() ) {
			return false;
		}

		if ( ! WP_Filesystem() ) {
			return false;
		}

		if ( ! isset( $GLOBALS['wp_filesystem'] ) || ! is_object( $GLOBALS['wp_filesystem'] ) ) {
			return false;
		}

		return $GLOBALS['wp_filesystem'];
	}

	/**
	 * Get the current salt values from wp-config.php
	 *
	 * @return array
	 */
	public function getSaltsArray(): array {
		$salts = [];
		foreach ( self::SALT_KEYS as $key ) {
			try {
				$value = defined( $key ) ? constant( $key ) : '';
				$salts[ $key ] = $value;
			} catch ( Error|Exception $e ) {
				$salts[ $key ] = '';
			}
		}

		return $salts;
	}

	/**
	 * Change WordPress salt keys
	 *
	 * @return bool
	 */
	public function shuffleSalts(): bool {
		// Initialize audit logger
		$audit_logger = new AuditLogger();
		$audit_logger->start_rotation();

		$config_file = $this->getConfigFile();
		$salt_source = 'wordpress_api';

		try {
			$http_salts = wp_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );

			// Check for API failures or invalid responses
			if (
				is_wp_error( $http_salts ) ||
				wp_remote_retrieve_response_code( $http_salts ) !== 200 ||
				empty( wp_remote_retrieve_body( $http_salts ) ) ||
				strpos( wp_remote_retrieve_body( $http_salts ), '404 Not Found' ) !== false
			) {
				// API call failed or invalid format, generate salts locally
				$returned_salts = $this->generateLocalSalts();
				$salt_source    = 'local_generation';
			} else {
				$raw_salts       = wp_remote_retrieve_body( $http_salts );
				$processed_salts = $this->processSalts( $raw_salts );
				$returned_salts  = $processed_salts ? $processed_salts : $this->generateLocalSalts();

				if ( ! $processed_salts ) {
					$salt_source = 'local_generation';
				}
			}

			$new_salts = explode( "\n", $returned_salts );

			// Adding filters for additional salts.
			$new_salts = apply_filters( 'salt_shaker_salts', $new_salts );
			$salt_keys = apply_filters( 'salt_shaker_salt_ids', self::SALT_KEYS );

			$result = $this->writeSalts( $salt_keys, $new_salts );

			if ( $result ) {
				// Log successful rotation
				$audit_logger->log_success( [
					'salt_source'      => $salt_source,
					'config_file_path' => $config_file,
					'affected_users'   => $this->count_active_sessions(),
				] );

				return true;
			} else {
				// Log failed rotation with the specific reason from writeSalts().
				$failure_reason = $this->getLastFailureReason();
				if ( empty( $failure_reason ) ) {
					$failure_reason = __( 'Failed to write salts to configuration file.', 'salt-shaker' );
				}

				$audit_logger->log_failure( [
					'error_message'    => $failure_reason,
					'config_file_path' => $config_file,
				] );

				return false;
			}
		} catch ( Exception $e ) {
			// Log exception
			$audit_logger->log_failure( [
				'error_message'    => $e->getMessage(),
				'config_file_path' => $config_file,
			] );

			return false;
		}
	}

	/**
	 * Generate salt keys locally if WP.org API fails
	 *
	 * @return string
	 */
	private function generateLocalSalts(): string {
		$salts = '';
		foreach ( self::SALT_KEYS as $salt_key ) {
			$generated_password = wp_generate_password( 64, true, true );
			$generated_password = str_replace( '\\', '', $generated_password );
			$generated_password = str_replace( "'", "\'", $generated_password );
			$generated_password = str_replace( ' ', '', $generated_password );
			$salts .= "define('" . $salt_key . "', '" . $generated_password . "');\n";
		}

		return $this->processSalts( $salts );
	}

	/**
	 * Process and validate salt keys
	 *
	 * @param string $salts
	 *
	 * @return string|false
	 */
	private function processSalts( string $salts ) {
		// First validate the overall format
		if ( ! preg_match( "/define\s*\(\s*'[A-Z_]+'\s*,\s*'[^']+'\s*\)\s*;/i", $salts ) ) {
			return false;
		}

		$lines = explode( "\n", $salts );
		$processed_lines = array_map( function ( $line ) {
			if ( empty( trim( $line ) ) ) {
				return '';
			}
			
			// Handle escaped backslashes and quotes
			$line = preg_replace( "/(.*)'(.*?)\\\'/", "$1'$2'", $line );
			$line = preg_replace( "/\\\\'/", "'", $line ); // Replace \' with '
			$line = preg_replace( "/\\\\\\\\/", "", $line ); // Remove backslashes
			
			// Clean up any other potential syntax issues
			$line = preg_replace( "/'([^']*?)\\\\'$/", "'$1'", $line );
			
			// Standardize define format
			if ( preg_match( "/define\s*\(\s*['\"]([A-Z_]+)['\"][\s,]*['\"](.*?)['\"]\s*\)\s*;/i", $line, $matches ) ) {
				$key = $matches[1];
				$value = $matches[2];
				// Standardize to single quotes
				$line = "define('" . $key . "', '" . $value . "');";
			}
			
			return $line;
		}, $lines );

		$processed_lines = array_filter( $processed_lines );
		return implode( "\n", $processed_lines );
	}

	/**
	 * Failure reason from the most recent filesystem-related call.
	 *
	 * @var string
	 */
	private $last_failure_reason = '';

	/**
	 * Resolved config file path, even when the file is not writable.
	 *
	 * @var string
	 */
	private $last_config_file_path = '';

	/**
	 * Get the failure reason from the most recent writeSalts() call.
	 *
	 * @return string
	 */
	public function getLastFailureReason(): string {
		return $this->last_failure_reason;
	}

	/**
	 * Write new salt keys to wp-config.php
	 *
	 * @param array $salt_keys Array of salt key names
	 * @param array $new_salts Array of new salt values
	 *
	 * @return bool
	 */
	private function writeSalts( array $salt_keys, array $new_salts ): bool {
		$this->last_failure_reason = '';

		$fs = $this->initFilesystem();
		if ( ! $fs ) {
			$this->last_failure_reason = __( 'Filesystem access is not available.', 'salt-shaker' );
			return false;
		}

		$config_file = $this->getConfigFile();
		if ( ! $config_file ) {
			if ( ! $this->last_failure_reason ) {
				$this->last_failure_reason = __( 'Salt definitions file not found or not writable.', 'salt-shaker' );
			}
			return false;
		}

		$original_content = $fs->get_contents( $config_file );
		if ( false === $original_content || '' === $original_content ) {
			$this->last_failure_reason = __( 'Failed to read the salt definitions file.', 'salt-shaker' );
			return false;
		}

		$chmod_string = $fs->getchmod( $config_file );
		$original_mode = is_numeric( $chmod_string ) ? intval( $chmod_string, 8 ) : null;

		$tmp_config_file = $config_file . '.tmp.' . uniqid( '', true );

		try {
			$segments = preg_split( '/(\r\n|\n|\r)/', $original_content, -1, PREG_SPLIT_DELIM_CAPTURE );
			$count    = count( $segments );

			$replaced_keys = array_fill_keys( array_keys( $salt_keys ), false );
			$new_content   = '';

			for ( $i = 0; $i < $count; $i++ ) {
				$segment = $segments[ $i ];

				// Terminators are attached to the line that precedes them.
				if ( preg_match( '/^(\r\n|\n|\r)$/', $segment ) ) {
					continue;
				}

				$terminator = '';
				if ( $i + 1 < $count && preg_match( '/^(\r\n|\n|\r)$/', $segments[ $i + 1 ] ) ) {
					$terminator = $segments[ $i + 1 ];
				}

				$line = $segment . $terminator;

				foreach ( $salt_keys as $key => $salt_key ) {
					if ( $replaced_keys[ $key ] ) {
						continue;
					}

					$pattern = "/define\s*\(\s*['\"]" . preg_quote( $salt_key, '/' ) . "['\"][\s,]*['\"].*?['\"]\s*\)\s*;/i";
					if ( preg_match( $pattern, $line ) ) {
						if ( isset( $new_salts[ $key ] ) && ! empty( trim( $new_salts[ $key ] ) ) ) {
							$line = $new_salts[ $key ] . $terminator;
							$replaced_keys[ $key ] = true;
						}
						break;
					}
				}

				$new_content .= $line;
			}

			if ( ! $fs->put_contents( $tmp_config_file, $new_content, $original_mode ) ) {
				$fs->delete( $tmp_config_file );
				$this->last_failure_reason = __( 'Failed to write the temporary configuration file.', 'salt-shaker' );
				return false;
			}

			if ( ! $fs->move( $tmp_config_file, $config_file, true ) ) {
				$fs->delete( $tmp_config_file );
				$this->last_failure_reason = __( 'Failed to replace the configuration file.', 'salt-shaker' );
				return false;
			}

			if ( null !== $original_mode ) {
				$fs->chmod( $config_file, $original_mode );
			}

			return true;
		} catch ( Exception $e ) {
			$this->last_failure_reason = $e->getMessage();
			if ( $fs->exists( $tmp_config_file ) ) {
				$fs->delete( $tmp_config_file );
			}
			return false;
		}
	}

	/**
	 * Get wp-config.php file path
	 *
	 * @return string|false
	 */
	private function getConfigFile() {
		$fs = $this->initFilesystem();
		if ( ! $fs ) {
			$this->last_failure_reason     = __( 'Filesystem access is not available.', 'salt-shaker' );
			$this->last_config_file_path   = '';
			return false;
		}

		// Check if the file name is wp-salt.php used in some hosting providers
		$wp_salts_file   = 'wp-salt';
		$salts_file_name = ( $fs->exists( ABSPATH . $wp_salts_file . '.php' ) )
			? $wp_salts_file
			: apply_filters( 'salt_shaker_salts_file', 'wp-config' );

		$config_file    = ABSPATH . $salts_file_name . '.php';
		$config_file_up = ABSPATH . '../' . $salts_file_name . '.php';

		if ( $fs->exists( $config_file ) && $fs->is_writable( $config_file ) ) {
			$this->last_config_file_path = $config_file;
			return $config_file;
		} elseif ( $fs->exists( $config_file_up ) && $fs->is_writable( $config_file_up ) && ! $fs->exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			$this->last_config_file_path = $config_file_up;
			return $config_file_up;
		}

		if ( $fs->exists( $config_file ) || $fs->exists( $config_file_up ) ) {
			$this->last_failure_reason   = __( 'Salt definitions file is not writable.', 'salt-shaker' );
			$this->last_config_file_path = $fs->exists( $config_file ) ? $config_file : $config_file_up;
		} else {
			$this->last_failure_reason   = __( 'Salt definitions file not found.', 'salt-shaker' );
			$this->last_config_file_path = '';
		}

		return false;
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_cron_schedule( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Weekly', 'salt-shaker' )
			);
		}

		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Monthly', 'salt-shaker' )
			);
		}

		if ( ! isset( $schedules['quarterly'] ) ) {
			$schedules['quarterly'] = array(
				'interval' => 90 * DAY_IN_SECONDS, // 3 months
				'display'  => __( 'Quarterly', 'salt-shaker' )
			);
		}

		if ( ! isset( $schedules['biannually'] ) ) {
			$schedules['biannually'] = array(
				'interval' => 180 * DAY_IN_SECONDS, // 6 months
				'display'  => __( 'Biannually', 'salt-shaker' )
			);
		}

		return $schedules;
	}

	/**
	 * Count active user sessions
	 *
	 * @return int Number of active sessions
	 */
	public function count_active_sessions(): int {
		global $wpdb;

		// Count sessions from usermeta table
		$count = $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id)
			FROM {$wpdb->usermeta}
			WHERE meta_key LIKE 'session_tokens'"
		);

		return (int) $count;
	}

	/**
	 * Check if wp-config.php is writable
	 *
	 * @return array {
	 *     Status information about the config file
	 *
	 * @type bool $writable Whether the file is writable
	 * @type string $message Error message if file is not writable
	 * @type string $file The path to the config file
	 * }
	 */
	public function checkConfigFilePermissions(): array {
		$fs = $this->initFilesystem();
		if ( ! $fs ) {
			return [
				'writable' => false,
				'message'  => __( 'Filesystem access is not available, so the salt definitions file cannot be checked.', 'salt-shaker' ),
				'file'     => ''
			];
		}

		$config_file = $this->getConfigFile();

		if ( ! $config_file ) {
			if ( 'Salt definitions file is not writable.' === $this->getLastFailureReason() && $this->last_config_file_path ) {
				return [
					'writable' => false,
					'message'  => sprintf(
					/* translators: %s: wp-config.php file path */
						__( 'wp-config.php file (%s) is not writable. Please check file permissions.', 'salt-shaker' ),
						$this->last_config_file_path
					),
					'file'     => $this->last_config_file_path
				];
			}

			return [
				'writable' => false,
				'message'  => __( 'wp-config.php file not found or not accessible.', 'salt-shaker' ),
				'file'     => ''
			];
		}

		if ( ! $fs->is_writable( $config_file ) ) {
			return [
				'writable' => false,
				'message'  => sprintf(
				/* translators: %s: wp-config.php file path */
					__( 'wp-config.php file (%s) is not writable. Please check file permissions.', 'salt-shaker' ),
					$config_file
				),
				'file'     => $config_file
			];
		}

		return [
			'writable' => true,
			'message'  => '',
			'file'     => $config_file
		];
	}
}
