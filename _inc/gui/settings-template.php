<div class="wrap metabox-holder">
	<h2><?php esc_html_e( 'Salt Shaker Settings', 'salt-shaker' ) ?></h2>
	<?php settings_errors(); ?>
	<form id="salt_shaker_form" method="post" action="options.php">
		<div id="poststuff" class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content" class="has-sidebar-content">
					<?php do_meta_boxes( 'saltshaker', 'normal', null ); ?>
				</div>
			</div>
		</div>
	</form>
	<div>
		<?php
			$url  = 'https://wordpress.org/support/plugin/salt-shaker/reviews/?rate=5#new-post';
			$link = sprintf( wp_kses( __( 'Do you find this plugin useful? Please <a href="%s">Rate it</a> on WordPress.org. BIG Thanks in advance!', 'salt-shaker' ),
				array(
					'a' => array(
						'href' => array()
					)
				)
			),
				esc_url( $url ) );
			echo $link;
		?>
	</div>
	<section id="pro" style="background-color: #f8f8f8; border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin: 50px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
		<h2 style="text-align: center; font-size: 24px; color: #333; text-shadow: 1px 1px #fff;"><?php esc_html_e( 'Looking for PRO Features?', 'salt-shaker' ); ?></h2>
		<ul style="list-style-type: circle; margin: 10px; padding: 10px; font-size: 16px;">
			<li style="margin-bottom: 10px;"><?php esc_html_e( 'Premium email support 24x7!', 'salt-shaker' ); ?></li>
			<li style="margin-bottom: 10px;"><?php esc_html_e( 'Set exactly what time to change the salt keys: You can choose the time when your users are sleeping!.', 'salt-shaker' ); ?></li>
			<li style="margin-bottom: 10px;"><?php esc_html_e( 'Get notified when the keys are changed: Receive an email notification when the salt keys are updated.', 'salt-shaker' ); ?></li>
			<li style="margin-bottom: 10px;"><?php esc_html_e( 'Set a custom email for receiving the notifications: Choose the email address where you want to receive the notification emails.', 'salt-shaker' ); ?></li>
			<li style="margin-bottom: 10px;"><?php esc_html_e( 'Salt Shaker can remind you to update the salt keys manually if it has been too long.', 'salt-shaker' ); ?></li>
		</ul>
		<div style="text-align: center; margin-top: 20px;">
			<a href="<?php echo esc_url( ss_fs()->get_upgrade_url() ); ?>" style="display: inline-block; padding: 10px 20px; background-color: #333; color: #fff; border-radius: 5px; text-decoration: none; font-size: 18px; text-shadow: 1px 1px #000;"><?php esc_html_e( 'Upgrade to PRO', 'salt-shaker' ); ?></a>
			<p style="margin-top: 10px; font-size: 16px; color: #333;">Get 50% off Salt Shaker PRO with coupon code "SALTSHAKERPRO"! HURRY UP! ONLY FOR THE FIRST 99 USERS!</p>

		</div>
	</section>
</div>
