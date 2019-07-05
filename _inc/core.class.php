<?php

class SalterCore
{
    public $salts_array, $new_salts;

    public function shuffleSalts()
    {
        $this->salts_array = array(
            "define('AUTH_KEY',",
            "SECURE_AUTH_KEY",
            "LOGGED_IN_KEY",
            "NONCE_KEY",
            "define('AUTH_SALT',",
            "SECURE_AUTH_SALT",
            "LOGGED_IN_SALT",
            "NONCE_SALT",
        );

        $returned_salts = file_get_contents("https://api.wordpress.org/secret-key/1.1/salt/");
        $this->new_salts = explode("\n", $returned_salts);

        // Adding filters for additional salts
        $this->new_salts = apply_filters('salt_shaker_salts', $this->new_salts);
        $this->salts_array = apply_filters('salt_shaker_salt_ids', $this->salts_array);

        return $this->writeSalts($this->salts_array, $this->new_salts);

    }

    /**
     * Find the correct wp-config.php file. It supports one-level up.
     *
     * @since 1.2.2
     *
     * @return string|bool The path of the wp-config.php or false if it's not found
     */
    public function config_file_path()
    {

        $salts_file_name = apply_filters('salt_shaker_salts_file', 'wp-config');
        $config_file = ABSPATH . $salts_file_name . '.php';
        $config_file_up = ABSPATH . '../' . $salts_file_name . '.php';

        if (file_exists($config_file) && is_writable($config_file)) {
            return $config_file;
        } elseif (file_exists($config_file_up) && is_writable($config_file_up) && !file_exists(dirname(ABSPATH) . '/wp-settings.php')) {
            return $config_file_up;
        }

        return false;
    }

    public function writeSalts($salts_array, $new_salts){

        $config_file = $this -> config_file_path();
        
        $perms = fileperms($config_file); // Get the current permissions of wp-config.php

        $tmp_config_file = ABSPATH . 'wp-config-tmp.php';

        $readin_config = fopen($config_file, 'r');
        $writing_config = fopen($tmp_config_file, 'w');

        while (!feof($readin_config)) {
        		$line = fgets($readin_config);
        		foreach ($salts_array as $salt_key => $salt_value) {
               if (stristr($line, $salt_value)) {
               	$line = $new_salts[$salt_key] . "\n";
               }
            }
            fputs($writing_config, $line);
        }

        fclose($readin_config);
        fclose($writing_config);
        rename($tmp_config_file, $config_file);
        //keep the original permissions of wp-config.php
        chmod($config_file, $perms );
    }

}
