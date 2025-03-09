<?php

namespace SaltShaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Options {
	private const OPTION_NAME = 'salt_shaker_options';

	/**
	 * Default plugin options.
	 *
	 * @var array
	 */
	private array $defaults = [
		'salt_shaker_update_interval'    => 'weekly',
		'salt_shaker_autoupdate_enabled' => 'false',
	];

	/**
	 * Stored plugin options.
	 *
	 * @var array
	 */
	private array $options = [];

	public function __construct() {
		$stored_options = get_option( self::OPTION_NAME, [] );
		$this->options  = wp_parse_args( $stored_options, $this->defaults );
	}

	/**
	 * Get whether auto-update is enabled.
	 *
	 * @return bool
	 */
	public function is_auto_update_enabled(): bool {
		return $this->options['salt_shaker_autoupdate_enabled'] === 'true';
	}

	/**
	 * Set whether auto-update is enabled.
	 *
	 * @param bool $enabled
	 */
	public function set_auto_update_enabled( bool $enabled ): void {
		$this->options['salt_shaker_autoupdate_enabled'] = $enabled ? 'true' : 'false';
		$this->save();
	}

	/**
	 * Save the options to the database.
	 */
	private function save(): void {
		update_option( self::OPTION_NAME, $this->options );
	}

	/**
	 * Get the update interval.
	 *
	 * @return string
	 */
	public function get_update_interval(): string {
		return $this->options['salt_shaker_update_interval'];
	}

	/**
	 * Set the update interval.
	 *
	 * @param string $interval
	 */
	public function set_update_interval( string $interval ): void {
		// Validate interval
		$valid_intervals = [ 'daily', 'weekly', 'monthly', 'quarterly', 'biannually' ];
		if ( ! in_array( $interval, $valid_intervals ) ) {
			$interval = 'weekly'; // Default to weekly if invalid
		}

		$this->options['salt_shaker_update_interval'] = $interval;
		$this->save();
	}
}
