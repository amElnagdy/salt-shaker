<div class="wrap metabox-holder">
	<h2><?php esc_html_e('Salt Shaker Settings', 'salt-shaker')?></h2>
	<?php settings_errors(); ?>
		<form id="salt_shaker_form" method="post" action="options.php">
			<div id="poststuff" class="metabox-holder">
				<div id="post-body">
					<div id="post-body-content" class="has-sidebar-content">
					    <?php do_meta_boxes('saltshaker', 'normal', null);?>
					</div>
				</div>
			</div>
		</form>
	<div>If you find this plugin useful, please <a href="https://wordpress.org/support/plugin/salt-shaker/reviews/?rate=5#new-post" target="_blank">Rate it</a> on WordPress.org :-) </div>
</div>