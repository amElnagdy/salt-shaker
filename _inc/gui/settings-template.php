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
	<div style="margin-top: 20px">
		<?php
			$url  = 'https://www.buymeacoffee.com/nagdy';
			$link = sprintf( wp_kses( __( 'Want to buy me a coffee? You <a href="%s">can do it from here.</a>', 'salt-shaker' ),
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
</div>
