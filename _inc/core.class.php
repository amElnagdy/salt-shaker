<?php
require_once 'SalterOptions.php';
class SalterCore
{

	public $salts_array;
	public $new_salts;

	public function shuffleSalts()
	{
		$this->salts_array = array(
			"'AUTH_KEY',",
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			"'AUTH_SALT',",
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		);

		$http_salts     = wp_remote_get('https://api.wordpress.org/secret-key/1.1/salt/');

		// Check for API failures or invalid responses
		if (
			is_wp_error($http_salts) ||
			wp_remote_retrieve_response_code($http_salts) !== 200 ||
			empty(wp_remote_retrieve_body($http_salts)) ||
			strpos(wp_remote_retrieve_body($http_salts), '404 Not Found') !== false ||
			!$this->isValidSaltFormat(wp_remote_retrieve_body($http_salts))
		) {
			// API call failed or invalid format, generate salts locally
			$returned_salts = $this->generateLocalSalts();
		} else {
			$returned_salts = wp_remote_retrieve_body($http_salts);
		}

		$this->new_salts = explode("\n", $returned_salts);

		// Adding filters for additional salts.
		$this->new_salts   = apply_filters('salt_shaker_salts', $this->new_salts);
		$this->salts_array = apply_filters('salt_shaker_salt_ids', $this->salts_array);
		return $this->writeSalts($this->salts_array, $this->new_salts);
	}

	/**
	 * Find the correct wp-config.php file. It supports one-level up.
	 * @return string|bool The path of the wp-config.php or false if it's not found
	 * @since 1.2.2
	 */
	public function config_file_path()
	{

		// Check if the file name is wp-salt.php used in some hosting providers
		$wp_salts_file   = 'wp-salt';
		$salts_file_name = (file_exists(ABSPATH . $wp_salts_file . '.php')) ? $wp_salts_file : apply_filters('salt_shaker_salts_file', 'wp-config');
		$config_file     = ABSPATH . $salts_file_name . '.php';
		$config_file_up  = ABSPATH . '../' . $salts_file_name . '.php';

		if (file_exists($config_file) && is_writable($config_file)) {
			return $config_file;
		} elseif (file_exists($config_file_up) && is_writable($config_file_up) && !file_exists(dirname(ABSPATH) . '/wp-settings.php')) {
			return $config_file_up;
		}

		return false;
	}


	public function writeSalts($salts_array, $new_salts)
	{

		$config_file = $this->config_file_path();

		// Get the current permissions of wp-config.php.
		$config_permissions = fileperms($config_file);

		$tmp_config_file = ABSPATH . 'wp-config-tmp.php';

		$reading_config  = fopen($config_file, 'r');
		$writing_config = fopen($tmp_config_file, 'w');

		while (!feof($reading_config)) {
			$line = fgets($reading_config);
			foreach ($salts_array as $salt_key => $salt_value) {
				if (stristr($line, $salt_value)) {
					$line = $new_salts[$salt_key] . "\n";
				}
			}
			fputs($writing_config, $line);
		}

		fclose($reading_config);
		fclose($writing_config);
		rename($tmp_config_file, $config_file);

		// Keep the original permissions of wp-config.php.
		chmod($config_file, $config_permissions);
	}

	/**
	 *	Fill the salts array with values
	 *
	 */
	public function getSaltsArray(): array
	{
		$salts_keys = [
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		];
		$salts_array = [];
		foreach ($salts_keys as $key) {
			try {
				$value = defined($key) ? constant($key) : '';
				$salts_array[$key] = $value;
			} catch (Error | Exception $e) {
				$salts_array[$key] = '';
			}
		}
		return $salts_array;
	}

	/**
	 * Validates that the returned salt string contains the expected format
	 * @param string $salts The salt string to validate
	 * @return boolean
	 */
	private function isValidSaltFormat($salts)
	{
		// Check if the string contains at least one valid salt definition
		return (bool) preg_match("/define\(\s*'[A-Z_]+'\s*,\s*'[^']+'\s*\);/", $salts);
	}

	/**
	 * Generates cryptographically secure salts locally
	 * @return string
	 */
	private function generateLocalSalts()
	{
		$salts = '';
		foreach ($this->salts_array as $salt) {
			$salt = trim($salt, "'");
			$salt = trim($salt, ",");
			$salts .= "define('" . $salt . "', '" . wp_generate_password(64, true, true) . "');\n";
		}
		return $salts;
	}
}
