<div class="wrap metabox-holder">
	<h2><?php esc_html_e('Salt Shaker Settings', 'saltshaker')?></h2>
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
</div>