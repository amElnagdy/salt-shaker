<?php

class SalterCore{
    public $salts_array, $new_salts;
    
    public function shuffleSalts(){
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
    
    public function writeSalts($salts_array, $new_salts){
        $config_file = ABSPATH . "wp-config.php";
        $tmp_config_file = ABSPATH . "wp-config-tmp.php";
        if(file_exists($config_file)){
            foreach($salts_array as $salt_key => $salt_value){
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

                if ($replaced) 
                {
                  rename($tmp_config_file, $config_file);
                } else {
                  unlink($tmp_config_file);
                }
            }
            
        }
    }
}

?>