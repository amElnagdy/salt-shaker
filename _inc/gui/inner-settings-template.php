<?php
$config_file_checker = new SalterCore();
$is_config = $config_file_checker->config_file_path();
if (! $is_config){
    wp_die( '<p>' . sprintf(
        /* translators: 1: wp-config.php 2: https://codex.wordpress.org/Changing_File_Permissions */
            __( 'The file %1$s is not writable. Read how to setup the correct permissions<a href="%2$s"> on WordPress codex</a>.', 'salt-shaker' ),
            '<code>wp-config.php</code>',
            'https://codex.wordpress.org/Changing_File_Permissions'
        ) . '</p>'
    );
}
else {
?>
<div class="salt_shaker_inner_settings">
    <div>
        <div>
            <p style="color:red; font-weight: bold"><?php esc_html_e( 'Changing WP Keys and Salts will force all logged-in users to login again.', 'salt-shaker' ) ?></p>
            <h3><?php esc_html_e( 'Scheduled Change:', 'salt-shaker' ) ?></h3>
			<?php if ( get_option( "salt_shaker_autoupdate_enabled" ) == "true" ) {
				$next_schedule = date_i18n( get_option( 'date_format' ), wp_next_scheduled( 'salt_shaker_change_salts' ) );
				?>
                <p style="color:green; font-weight: bold">
					<?php printf( __( 'The salt keys will be automatically changed on %s' ), $next_schedule ); ?>
                </p>
			<?php
			}
			?>
            <p> <?php esc_html_e( 'Set scheduled job for automated Salt changing:', 'salt-shaker' ) ?></p>
            <input type="checkbox"
                   id="schedualed_salt_changer" <?php echo( get_option( "salt_shaker_autoupdate_enabled" ) == "true" ? "checked" : "" ); ?> />
            <label><?php esc_html_e( 'Change WP Keys and Salts on', 'salt-shaker' ) ?></label>
			<?php wp_nonce_field( 'salt-shaker_save-salt-schd', '_ssnonce_scheduled' ); ?>
            <select id="schedualed_salt_value">
                <option value="daily" <?php echo( get_option( "salt_shaker_update_interval" ) == "daily" ? "selected" : "" ); ?>><?php esc_html_e( 'Daily', 'salt-shaker' ) ?></option>
                <option value="weekly" <?php echo( get_option( "salt_shaker_update_interval" ) == "weekly" ? "selected" : "" ); ?>><?php esc_html_e( 'Weekly', 'salt-shaker' ) ?></option>
                <option value="monthly" <?php echo( get_option( "salt_shaker_update_interval" ) == "monthly" ? "selected" : "" ); ?>><?php esc_html_e( 'Monthly', 'salt-shaker' ) ?></option>
                <option value="quarterly" <?php echo( get_option( "salt_shaker_update_interval" ) == "quarterly" ? "selected" : "" ); ?>><?php esc_html_e( 'Quarterly', 'salt-shaker' ) ?></option>
                <option value="biannually" <?php echo( get_option( "salt_shaker_update_interval" ) == "biannually" ? "selected" : "" ); ?>><?php esc_html_e( 'Biannually', 'salt-shaker' ) ?></option>
            </select>
			<?php esc_html_e( 'Basis.', 'salt-shaker' ) ?>
        </div>
        <div>
            <h3><?php esc_html_e( 'Immediate Change:', 'salt-shaker' ) ?></h3>
            <p class="keys_updated_message" style="display: none; color:green; font-weight: bold">
                <?php esc_html_e( "Keys have been updated, you'll be redirected to the login page in a few seconds.", 'salt-shaker' ) ?>
            </p>
            <p><?php esc_html_e( 'When you click the following button, WP keys and salts will change immediately. And you will need to login again.', 'salt-shaker' ) ?></p>

            <input type="button" id="change_salts_now" name="change_salts_now" class="button button-primary"
                   value="<?php esc_attr_e( 'Change Now', 'salt-shaker' ) ?>"/>
			<?php wp_nonce_field( 'salt-shaker_change-salts-now', '_ssnonce_now' ); ?>
            <div class="spinner" id="saving_spinner"></div>
        </div>
    </div>
</div>
<?php }?>