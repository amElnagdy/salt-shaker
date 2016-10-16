<div class="salt_shaker_inner_settings">
    <div>
        <div>
        <p> <?php esc_html_e('Set scheduled job for automated Salt changing:' , 'saltshaker') ?></p>
        <input type="checkbox" id="schedualed_salt_changer" <?php echo (get_option("salt_shaker_autoupdate_enabled") == "true" ? "checked" : ""); ?> /> <label><?php esc_html_e('Change WP Salts on', 'saltshaker') ?></label>
        <select id="schedualed_salt_value">
            <option value="daily" <?php echo (get_option("salt_shaker_update_interval") == "daily" ? "selected" : ""); ?>><?php esc_html_e('Daily', 'saltshaker')?></option>
            <option value="weekly" <?php echo (get_option("salt_shaker_update_interval") == "weekly" ? "selected" : ""); ?>><?php esc_html_e('Weekly', 'saltshaker')?></option>
            <option value="monthly" <?php echo (get_option("salt_shaker_update_interval") == "monthly" ? "selected" : ""); ?>><?php esc_html_e('Monthly', 'saltshaker')?></option>
        </select>
<?php esc_html_e('Basis.', 'saltshaker')?>
        </div>
        <div>
            <p style="color:red;"><?php esc_html_e('Changing WP Keys and Salts will force all logged-in users to login again.', 'saltshaker') ?></p>
            <input type="button" id="change_salts_now" name="change_salts_now" class="button button-primary" value="<?php esc_html_e('Change Now', 'saltshaker')?>"/> <div class="spinner" id="saving_spinner"></div>
        </div>
    </div>
</div>