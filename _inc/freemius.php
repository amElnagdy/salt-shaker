<?php
	if ( ! function_exists( 'ss_fs' ) ) {
		// Create a helper function for easy SDK access.
		function ss_fs() {
			global $ss_fs;
			
			if ( ! isset( $ss_fs ) ) {
				// Include Freemius SDK.
				require_once dirname(__DIR__) . '/freemius/start.php';
				
				$ss_fs = fs_dynamic_init( array(
					'id'                  => '8851',
					'slug'                => 'salt-shaker',
					'premium_slug'        => 'salt-shaker-pro',
					'type'                => 'plugin',
					'public_key'          => 'pk_f3d8cc8437a2ffddb2e1db1c8ad0e',
					'is_premium'          => false,
					'is_premium_only'     => false,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'menu'                => array(
						'slug'           => 'salt_shaker',
						'contact'        => false,
						'parent'         => array(
							'slug' => 'tools.php',
						),
					),
				) );
			}
			
			return $ss_fs;
		}
		
		// Init Freemius.
		ss_fs();
		// Signal that SDK was initiated.
		do_action( 'ss_fs_loaded' );
	}
	
	if( ! function_exists( 'ss_fs_custom_connect_message_on_update' ) ) {
		function ss_fs_custom_connect_message_on_update(
			$message,
			$user_first_name,
			$plugin_title,
			$user_login,
			$site_link,
			$freemius_link
		) {
			return sprintf(
				__( 'Hey %1$s' ) . ',<br>' .
				__( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'salt-shaker' ),
				$user_first_name,
				'<b>' . $plugin_title . '</b>',
				'<b>' . $user_login . '</b>',
				$site_link,
				$freemius_link
			);
		}
	}
	
	ss_fs()->add_filter( 'connect_message_on_update', 'ss_fs_custom_connect_message_on_update', 10, 6 );
