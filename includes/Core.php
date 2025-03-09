<?php

namespace SaltShaker;

use Error;
use Exception;

class Core {
	private const SALT_KEYS = [
		"'AUTH_KEY',",
		'SECURE_AUTH_KEY',
		'LOGGED_IN_KEY',
		'NONCE_KEY',
		"'AUTH_SALT',",
		'SECURE_AUTH_SALT',
		'LOGGED_IN_SALT',
		'NONCE_SALT'
	];

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'salt_shaker_change_salts', array( $this, 'shuffleSalts' ) );
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
				$key           = trim( $key, ",'" );  // Clean up the key
				$value         = defined( $key ) ? constant( $key ) : '';
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
		} else {
			$raw_salts       = wp_remote_retrieve_body( $http_salts );
			$processed_salts = $this->processSalts( $raw_salts );
			$returned_salts  = $processed_salts ? $processed_salts : $this->generateLocalSalts();
		}

		$new_salts = explode( "\n", $returned_salts );

		// Adding filters for additional salts.
		$new_salts = apply_filters( 'salt_shaker_salts', $new_salts );
		$salt_keys = apply_filters( 'salt_shaker_salt_ids', self::SALT_KEYS );

		return $this->writeSalts( $salt_keys, $new_salts );
	}

	/**
	 * Generate salt keys locally if WP.org API fails
	 *
	 * @return string
	 */
	private function generateLocalSalts(): string {
		$salts = '';
		foreach ( self::SALT_KEYS as $salt ) {
			$generated_password = wp_generate_password( 64, true, true );
			$salts              .= "define('" . $salt . "', '" . $generated_password . "');\n";
		}

		return $salts;
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
		if ( ! preg_match( "/define\(\s*'[A-Z_]+'\s*,\s*'[^']+'\s*\);/", $salts ) ) {
			return false;
		}

		$lines           = explode( "\n", $salts );
		$processed_lines = array_map( function ( $line ) {
			if ( empty( trim( $line ) ) ) {
				return '';
			}

			// Handle escaped backslashes at the end of the salt value
			$line = preg_replace( "/'([^']*?)\\\\'$/", "'$1'", $line );

			// Ensure the line is properly formatted
			if ( ! preg_match( "/^define\(\s*'[A-Z_]+'\s*,\s*'[^']+'\s*\);$/", $line ) ) {
				return '';
			}

			return $line;
		}, $lines );

		$processed_lines = array_filter( $processed_lines );

		return implode( "\n", $processed_lines );
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
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$config_file = $this->getConfigFile();
		if ( ! $config_file ) {
			return false;
		}

		// 
		$creds = request_filesystem_credentials( '', '', false, false, null );
		if ( ! WP_Filesystem( $creds ) ) {
			return false;
		}

		// Store original permissions
		$original_perms = fileperms( $config_file );
		if ( false === $original_perms ) {
			return false;
		}

		// Create a unique temporary file
		$tmp_config_file = $config_file . '.tmp.' . uniqid( '', true );

		// Read the original file
		$config_content = $wp_filesystem->get_contents( $config_file );
		if ( false === $config_content ) {
			return false;
		}

		// Split into lines for processing
		$lines     = explode( "\n", $config_content );
		$new_lines = array();

		// Process each line
		foreach ( $lines as $line ) {
			$replaced = false;
			foreach ( $salt_keys as $key => $salt_value ) {
				if ( stristr( $line, $salt_value ) ) {
					$new_lines[] = trim( $new_salts[ $key ] );
					$replaced    = true;
					break;
				}
			}
			if ( ! $replaced ) {
				$new_lines[] = rtrim( $line );
			}
		}

		// Join lines back together
		$new_content = implode( "\n", $new_lines );

		// Write to temporary file
		if ( ! $wp_filesystem->put_contents( $tmp_config_file, $new_content ) ) {
			return false;
		}

		// Set the same permissions on temporary file
		if ( ! chmod( $tmp_config_file, $original_perms ) ) {
			$wp_filesystem->delete( $tmp_config_file );
			return false;
		}

		// Verify the temporary file was written correctly
		if ( 0 === $wp_filesystem->size( $tmp_config_file ) ) {
			$wp_filesystem->delete( $tmp_config_file );
			return false;
		}

		// Delete original and rename temp file
		if ( ! $wp_filesystem->delete( $config_file ) || ! $wp_filesystem->move( $tmp_config_file, $config_file ) ) {
			$wp_filesystem->delete( $tmp_config_file );
			return false;
		}

		// Restore original permissions to final file
		if ( ! chmod( $config_file, $original_perms ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get wp-config.php file path
	 *
	 * @return string|false
	 */
	private function getConfigFile() {
		// Check if the file name is wp-salt.php used in some hosting providers
		$wp_salts_file   = 'wp-salt';
		$salts_file_name = ( file_exists( ABSPATH . $wp_salts_file . '.php' ) )
			? $wp_salts_file
			: apply_filters( 'salt_shaker_salts_file', 'wp-config' );

		$config_file    = ABSPATH . $salts_file_name . '.php';
		$config_file_up = ABSPATH . '../' . $salts_file_name . '.php';

		if ( file_exists( $config_file ) && is_writable( $config_file ) ) {
			return $config_file;
		} elseif ( file_exists( $config_file_up ) && is_writable( $config_file_up ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $config_file_up;
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
		$config_file = $this->getConfigFile();

		if ( ! $config_file ) {
			return [
				'writable' => false,
				'message'  => __( 'wp-config.php file not found or not accessible.', 'salt-shaker' ),
				'file'     => ''
			];
		}

		if ( ! is_writable( $config_file ) ) {
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
