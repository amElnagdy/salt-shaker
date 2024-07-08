<?php

namespace SaltShaker;

class SalterOptions
{

	private static $_instance = null;
	public array $options;
	private string $notice_option_name = 'salter_admin_notice_dismissed';

	public function __construct()
	{
		$tempSalterOptions = get_option('salt_shaker_options');
		$this->options = $tempSalterOptions ? $tempSalterOptions : [];
		add_action('admin_notices', [$this, 'display_admin_notice']);
		add_action('admin_post_salter_dismiss_notice', [$this, 'dismiss_notice']);
	}

	public function getOption(string $option_name): ?string
	{
		if (!isset($option_name)) {
			return null;
		}

		return isset($this->options[$option_name]) ? $this->options[$option_name] : null;
	}

	public function setOption(string $option_name, string $option_value)
	{
		if (!isset($option_name) || !isset($option_value)) {
			return;
		}

		$this->options[$option_name] = $option_value;

		update_option('salt_shaker_options', $this->options);
	}

	public function getSalterOptions()
	{
		return $this->options;
	}

	public function display_admin_notice()
	{

		if (!current_user_can('manage_options') || strpos($_SERVER['REQUEST_URI'], 'salt_shaker') === false || $this->getOption($this->notice_option_name)) {
			return;
		}

		$message = sprintf(
		/* translators: 1: URL of the upgrade page */
			esc_html__('From Salt Shaker: Use the discount SALTSHAKERPRO code and be among the first 99 to upgrade on our %1$spricing page%2$s and get 50%% off! 
			Here is what you get with the PRO version:', 'salt-shaker'),
			'<a href="' . admin_url('tools.php?page=salt_shaker-pricing') . '">',
			'</a>'
		);

		$pro_features = [
			esc_html__('Premium email support 24x7!', 'salt-shaker'),
			esc_html__('Set exactly what time to change the salt keys: You can choose the time when your users are sleeping!.', 'salt-shaker'),
			esc_html__('Get notified when the keys are changed.', 'salt-shaker'),
			esc_html__('Set a custom email for receiving the notifications.', 'salt-shaker'),
			esc_html__('Salt Shaker can remind you to update the salt keys manually if it has been too long.', 'salt-shaker')
		];

		echo '<div class="notice notice-success is-dismissible"> 
            <p>' . $message . '</p>
            <ul>';
		foreach ( $pro_features as $feature ) {
			echo '<li>' . $feature . '</li>';
		}
		echo '</ul>
            <a href="' . admin_url( 'admin-post.php?action=salter_dismiss_notice' ) . '">' . esc_html__( 'Never show this again.', 'salt-shaker' ) . '</a>
         
         </div>';

	}

	public function dismiss_notice()
	{
		// Update the option to dismiss the notice
		$this->setOption($this->notice_option_name, '1');
		// redirect back to dashboard
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	public static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new SalterOptions();
		}

		return self::$_instance;
	}
}
