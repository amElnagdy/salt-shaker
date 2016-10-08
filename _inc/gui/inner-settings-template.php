<div class="salt_shaker_inner_settings">
    <div>
        <div>
        <p>Set scheduled job for automated Salt changing</p>
        <input type="checkbox" id="schedualed_salt_changer" <?php echo (get_option("salt_shaker_autoupdate_enabled") == "true" ? "checked" : ""); ?> /> <label>Change WP Salts on</label> 
        <select id="schedualed_salt_value">
            <option value="daily" <?php echo (get_option("salt_shaker_update_interval") == "daily" ? "selected" : ""); ?>>Daily</option>
            <option value="weekly" <?php echo (get_option("salt_shaker_update_interval") == "weekly" ? "selected" : ""); ?>>Weekly</option>
            <option value="monthly" <?php echo (get_option("salt_shaker_update_interval") == "monthly" ? "selected" : ""); ?>>Monthly</option>
        </select>
        Bases
        </div>
        <div>
            <p>Change WP Salts Immediatly (will invalidate all current NONCES)</p>
            <input type="button" id="change_salts_now" name="change_salts_now" class="button button-primary" value="Change Now"/> <div class="spinner" id="saving_spinner"></div>
        </div>
    </div>
</div>