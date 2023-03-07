<?php
/**
Plugin Name: Salt Shaker
Plugin URI: https://nagdy.net/
Description: A plugin that changes WordPress Authentication Unique Keys and Salts to enhance and strengthen WordPress security.
Version: 1.3.2
Author: Nagdy
Author URI: https://nagdy.net/
License: GPLv2 or later
Text Domain: salt-shaker
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2022 Nagdy.net.
 */
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	
	if ( function_exists( 'ss_fs' ) ) {
		ss_fs()->set_basename( true, __FILE__ );
	} else {
		// DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
		if ( ! function_exists( 'ss_fs' ) ) {
			
			if ( ! function_exists( 'ss_fs' ) ) {
				// Create a helper function for easy SDK access.
				function ss_fs() {
					global $ss_fs;
					
					if ( ! isset( $ss_fs ) ) {
						// Include Freemius SDK.
						require_once dirname( __FILE__ ) . '/freemius/start.php';
						
						$ss_fs = fs_dynamic_init( array(
							'id'                  => '8851',
							'slug'                => 'salt-shaker',
							'premium_slug'        => 'salt-shaker-pro',
							'type'                => 'plugin',
							'public_key'          => 'pk_f3d8cc8437a2ffddb2e1db1c8ad0e',
							'is_premium'          => true,
							'premium_suffix'      => 'PRO',
							'has_premium_version' => true,
							'has_addons'          => false,
							'has_paid_plans'      => true,
							'menu'                => array(
								'slug'    => 'salt_shaker',
								'contact' => false,
								'parent'  => array(
									'slug' => 'tools.php',
								),
							),
							// Set the SDK to work in a sandbox mode (for development & testing).
							// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
							'secret_key'          => 'sk_KkFwcK4YAI+1w{Rgk4C[iM-E&ocLM',
						) );
					}
					
					return $ss_fs;
				}
				
				// Init Freemius.
				ss_fs();
				// Signal that SDK was initiated.
				do_action( 'ss_fs_loaded' );
			}
			
		}
		
		include_once( plugin_dir_path( __FILE__ ) . "_inc/loader.php" );
		$salt_shaker = new Salter();
		
		function salt_shaker_load_plugin_textdomain() {
			load_plugin_textdomain( 'salt-shaker', false, basename( dirname( __FILE__ ) ) . '/languages/' );
		}
		
		add_action( 'plugins_loaded', 'salt_shaker_load_plugin_textdomain' );
		
		/**
		 * Add a link to the settings page on the plugins.php page.
		 *
		 * @param $actions
		 * @param $plugin_file
		 *
		 * @return array         List of modified plugin action links.
		 * @since 1.2.7
		 *
		 */
		
		function salt_shaker_settings_link( $actions, $plugin_file ) {
			static $plugin;
			
			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( __FILE__ );
			}
			if ( $plugin == $plugin_file ) {
				
				$settings  = array( 'settings' => '<a href="' . esc_url( admin_url( '/tools.php?page=salt_shaker' ) ) . '">' . __( 'Settings', 'salt-shaker' ) . '</a>' );
				$site_link = array( 'support' => '<a href="' . esc_url( 'https://www.buymeacoffee.com/nagdy' ) . '" style="color:#0eb804;">' . __( 'Buy Me a Coffee!', 'salt-shaker' ) . '</a>' );
				
				$actions = array_merge( $settings, $actions );
				$actions = array_merge( $site_link, $actions );
				
			}
			
			return $actions;
		}
		
		add_filter( 'plugin_action_links', 'salt_shaker_settings_link', 10, 5 );
	}
