<?php

use SaltShaker\SalterOptions;

$salterOptionsObject = SalterOptions::getInstance();

$config_file_checker = new SalterCore();
$is_config           = $config_file_checker->config_file_path();
$salts_file_name     = apply_filters('salt_shaker_salts_file', 'wp-config');
if (!$is_config) {
	printf(
		/* translators: 1: wp-config.php 2: https://codex.wordpress.org/Changing_File_Permissions */
		__('The file <code>%1$s</code> which contains your salt keys is not writable. First, make sure it exists then read how to setup the correct permissions on <a href="%2$s">WordPress codex</a>.', 'salt-shaker'),
		$salts_file_name . '.php',
		'https://wordpress.org/support/article/changing-file-permissions/'
	);
} else {
?>
	<div class="salt_shaker_inner_settings">
		<div>
			<?php
			$salts = $config_file_checker->getSaltsArray();
			if (!empty($salts)) {
			?>
				<p><?php echo __('WordPress salt keys or security keys are codes that help protect important information on your website. They make it harder for hackers to access your website by making passwords more complex. You don\'t need to remember these codes, Salt Shaker plugin takes care of generating the codes directly from WordPress API.', 'salt-shaker'); ?></p>
				<h2><?php echo __('Current Salt Keys:', 'salt-shaker'); ?></h2>
				<p><?php echo __('The following table shows the current set of the salt keys in the configuration file.', 'salt-shaker'); ?></p>
				<table class="salt-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Name', 'salt-shaker'); ?></th>
							<th><?php esc_html_e('Value', 'salt-shaker'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($salts as $constant_name => $value) { ?>
							<tr>
								<td><?php echo esc_html($constant_name); ?></td>
								<td><?php if (!$value) {
										printf('<a href="https://wordpress.org/plugins/salt-shaker/#nothing%%20happens%%3F" target="_blank" rel="noopener">%s</a>', esc_html__("Does not exist", 'salt-shaker'));
									}
									else {
										echo esc_html($value);
									}
									?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<br />
			<?php } ?>
			<div>
				<p style="color:red; font-weight: bold"><?php esc_html_e('Changing WordPress salt keys will force all logged-in users to login again.', 'salt-shaker') ?></p>
				<h3><?php esc_html_e('Scheduled Change:', 'salt-shaker') ?></h3>
				<?php if ($salterOptionsObject->getOption("salt_shaker_autoupdate_enabled") == "true") {
					$format = 'l, ' . get_option('date_format');
					$next_schedule = date_i18n($format, wp_next_scheduled('salt_shaker_change_salts'));
				?>
					<p style="color:green; font-weight: bold">
						<?php printf(__('The salt keys will be automatically changed on %s', 'salt-shaker'), $next_schedule); ?>
					</p>
				<?php
				}
				?>
				<p> <?php esc_html_e('Choose when WordPress salt keys should be changed automatically:', 'salt-shake') ?></p>
				<!-- Enable schedules input -->
				<input type="checkbox" id="schedualed_salt_changer" <?php echo ($salterOptionsObject->getOption("salt_shaker_autoupdate_enabled") == "true" ? "checked" : ""); ?> />
				<label for="schedualed_salt_changer"><?php esc_html_e('Change WordPress salt keys', 'salt-shaker') ?></label>
				<?php wp_nonce_field('salt-shaker_save-salt-schd', '_ssnonce_scheduled'); ?>
				<!-- Schedule interval -->
				<select id="schedualed_salt_value">
					<option value="daily" <?php echo ($salterOptionsObject->getOption("salt_shaker_update_interval") == "daily" ? "selected" : ""); ?>><?php esc_html_e('Daily', 'salt-shaker') ?></option>
					<option value="weekly" <?php echo ($salterOptionsObject->getOption("salt_shaker_update_interval") == "weekly" ? "selected" : ""); ?>><?php esc_html_e('Weekly', 'salt-shaker') ?></option>
					<option value="monthly" <?php echo ($salterOptionsObject->getOption("salt_shaker_update_interval") == "monthly" ? "selected" : ""); ?>><?php esc_html_e('Monthly', 'salt-shaker') ?></option>
					<option value="quarterly" <?php echo ($salterOptionsObject->getOption("salt_shaker_update_interval") == "quarterly" ? "selected" : ""); ?>><?php esc_html_e('Quarterly', 'salt-shaker') ?></option>
					<option value="biannually" <?php echo ($salterOptionsObject->getOption("salt_shaker_update_interval") == "biannually" ? "selected" : ""); ?>><?php esc_html_e('Biannually', 'salt-shaker') ?></option>
				</select>


				<!-- Week days -->
				<br><br>
				<label class="salt-pro"><?php esc_html_e('PRO Feature:', 'salt-shaker') ?></label>
				<select disabled class="opacity_6" title="<?php esc_attr_e('Upgrade to Salt Shaker PRO', 'salt-shaker') ?>">
					<option value="sunday"><?php esc_html_e('Sunday', 'salt-shaker') ?></option>
				</select>

				<label class="opacity_6"><?php esc_html_e('At', 'salt-shaker') ?></label>
				<input type="time" disabled class="opacity_6" value="00:00">
				<span class="tooltip-icon"><span class="dashicons dashicons-editor-help"></span><span class="tooltip-text"><?php esc_html_e('With Salt Shaker PRO, you can select the exact times when the keys should be changed', 'salt-shaker') ?></span></span>

			</div>
			<!-- End Schedule settings -->

			<!-- Notifications Settings -->

			<div>
				<div >

					<!-- Enable manual notifications -->
					<p>
						<label class="salt-pro"><?php esc_html_e('PRO Feature:', 'salt-shaker') ?></label>

						<input type="checkbox" disabled />
						<label for="salt_shaker_manual_update_reminder_enabled">
							<?php esc_html_e('Remind me to update the keys manually.', 'salt-shaker') ?>
							<span class="tooltip-icon"><span class="dashicons dashicons-editor-help"></span><span class="tooltip-text"><?php printf('<a href="%s">%s</a>', esc_url('#pro'), esc_html__('PRO feature. Learn more.', 'salt-shaker')) ?></span></span>
						</label>
					</p>
					<!-- End Enable manual notifications -->

					<!-- Enable scheduled notifications -->
					<p id="salt_shaker_scheduled_update_reminder_enabled_wrap">
						<label class="salt-pro"><?php esc_html_e('PRO Feature:', 'salt-shaker') ?></label>

						<input type="checkbox" disabled />
						<label id="salt_shaker_scheduled_update_reminder_text" for="salt_shaker_scheduled_update_reminder_enabled"><?php esc_html_e('Notify me when an automatic update takes place.', 'salt-shaker') ?>
							<span class="tooltip-icon"><span class="dashicons dashicons-editor-help"></span><span class="tooltip-text"><?php printf('<a href="%s">%s</a>', esc_url('#pro'), esc_html__('PRO feature. Learn more.', 'salt-shaker')) ?></span></span>

						</label>
					</p>

					<!-- End Enable scheduled notifications -->

				</div>

				<!-- End Notfications emails -->
				<input type="button" id="save-salt-shaker-settings" name="change_salts_now" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'salt-shaker') ?>" />
			</div>

			<hr class="settings-separator">

			<!-- End Notifications Settings -->


			<div>
				<h3><?php esc_html_e('Immediate Change:', 'salt-shaker') ?></h3>
				<p class="keys_updated_message" style="display: none; color:green; font-weight: bold">
					<?php esc_html_e("Salt keys have been updated, you'll be redirected to the login page in a few seconds.", 'salt-shaker') ?>
				</p>
				<p><?php esc_html_e('When you click the following button, WordPress salt keys will change immediately. And all users will need to login again.', 'salt-shaker') ?></p>

				<input type="button" id="change_salts_now" name="change_salts_now" class="button button-primary" value="<?php esc_attr_e('Change Now', 'salt-shaker') ?>" />
				<?php wp_nonce_field('salt-shaker_change-salts-now', '_ssnonce_now'); ?>
				<div class="spinner" id="saving_spinner"></div>
			</div>
		</div>
	</div>
<?php } ?>
