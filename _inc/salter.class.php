<?php

class Salter extends SalterCore{
    
    public function __construct(){
        define("SALT_SHAKER_DOMAIN", "salt-shaker");
        add_action('admin_menu', array(__CLASS__, 'add_menu_item'));
        add_action('admin_init', array(__CLASS__, 'add_settings_metabox'));
        add_action( 'admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts') );
        add_action( 'wp_ajax_change_salts_now', array(&$this, 'wp_ajax_change_salts_now') );
        add_action( 'wp_ajax_save_salt_schd', array(&$this, 'wp_ajax_save_salt_schd') );
        add_action('salt_shaker_change_salts', array(&$this, 'shuffleSalts'));
    }
    
    public static function add_menu_item(){
        add_submenu_page( 'tools.php',  __('Salt Shaker Settings', 'salt-shaker'), __('Salt Shaker', 'salt-shaker'), 'manage_options', 'salt_shaker', array(__CLASS__, 'admin_page_content') );
    }
    
    public static function add_settings_metabox(){
        add_meta_box( 'salt_shaker_settings_metabox', __('Salt Changing Behaviour', 'salt-shaker'), array(__CLASS__, 'metabox_content') , 'saltshaker', 'normal' );
    }
    
    public static function admin_page_content(){
        include_once(plugin_dir_path(__FILE__) . "gui/settings-template.php");
    }
    
    public static function metabox_content(){
        include_once(plugin_dir_path(__FILE__) . "gui/inner-settings-template.php");
    }
    
    public static function enqueue_admin_scripts(){
        wp_enqueue_script( 'salt_shaker_admin', plugin_dir_url( __FILE__ ) . 'gui/js/salt_shaker_admin.js', array("jquery") );
    }
    
    public function wp_ajax_change_salts_now(){
        if ( !$this->check_nonce( '_ssnonce_now', 'salt-shaker_change-salts-now' )
          || !current_user_can( 'administrator' )) {
            wp_die( -1 );
        }
        do_action('salt_shaker_change_salts');
        die(0);
    }

    public function wp_ajax_save_salt_schd(){
        if ( !$this->check_nonce( '_ssnonce_scheduled', 'salt-shaker_save-salt-schd' )
          || !current_user_can( 'administrator' )) {
            wp_die( -1 );
        }
        update_option("salt_shaker_update_interval", $_POST["interval"]);
        update_option("salt_shaker_autoupdate_enabled", $_POST["enabled"]);
        if(isset($_POST["enabled"]) && $_POST["enabled"] == "true"){
            wp_schedule_event(time(), $_POST["interval"], "salt_shaker_change_salts");
        }else{
            wp_clear_scheduled_hook('salt_shaker_change_salts');
        }
        die(0);
    }

    /**
     * Ensure that the request contains a valid nonce.
     *
     * This is used to prevent CSRF attacks and must be called
     * *at the beginning* of any AJAX handler.
     *
     * @param  string $param
     * @param  string $name
     * @return boolean
     */
    public static function check_nonce( $param = '_wpnonce', $name = 'salt_shaker' ) {
        return (isset( $_POST[$param] ) && wp_verify_nonce( $_POST[$param], $name ));
    }
}