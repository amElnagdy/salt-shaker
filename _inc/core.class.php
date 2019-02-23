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

        $tmp_config_file = ABSPATH . 'wp-config-tmp.php';

        foreach ($salts_array as $salt_key => $salt_value) {

                $readin_config = fopen($config_file, 'r');
                $writing_config = fopen($tmp_config_file, 'w');

                $replaced = false;
                while (!feof($readin_config)) {
                    $line = fgets($readin_config);
                    if (stristr($line, $salt_value)) {
                        $line = $new_salts[$salt_key] . "\n";
                        $replaced = true;
                    }
                    fputs($writing_config, $line);
                }

                fclose($readin_config);
                fclose($writing_config);

                if ($replaced) {
                    rename($tmp_config_file, $config_file);
                } else {
                    unlink($tmp_config_file);
                }
                /* TODO: Create a filter or an option to update the permissions*/
                //set the recommended permissions to wp-config.php read: https://codex.wordpress.org/Changing_File_Permissions
                chmod($config_file, 0666);
            }
        }

}