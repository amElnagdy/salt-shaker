<?php
/**
 * Plugin Name: Salt Shaker
 * Plugin URI: https://nagdy.me/
 * Description: A plugin that changes WordPress Authentication Unique Keys and Salts to enhance and strengthen WordPress security.
 * Version: 2.1.1
 * Author: Nagdy
 * Author URI: https://nagdy.me/
 * License: GPLv2 or later
 * Text Domain: salt-shaker
 * Domain Path: /languages
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * Copyright 2025 Nagdy.me.
 */

use SaltShaker\Plugin;
use SaltShaker\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	// Verify Freemius SDK files exist before loading to prevent fatal errors.
	$freemius_sdk_path = __DIR__ . '/vendor/freemius/wordpress-sdk/includes/class-freemius.php';
	if ( ! file_exists( $freemius_sdk_path ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error"><p><strong>Salt Shaker:</strong> Plugin files are incomplete. Please reinstall the plugin.</p></div>';
		} );
		return;
	}
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( function_exists( 'fs_dynamic_init' ) && ! function_exists( 'ss_fs' ) ) {
	// Create a helper function for easy SDK access.
	function ss_fs() {
		global $ss_fs;

		if ( ! isset( $ss_fs ) ) {
			// SDK is auto-loaded through Composer.
			$ss_fs = fs_dynamic_init( array(
				'id'                => '8851',
				'slug'              => 'salt-shaker',
				'premium_slug'      => 'salt-shaker-pro',
				'type'              => 'plugin',
				'public_key'        => 'pk_f3d8cc8437a2ffddb2e1db1c8ad0e',
				'is_premium'        => false,
				'is_premium_only'   => false,
				'has_addons'        => false,
				'has_paid_plans'    => true,
				'menu'              => array(
					'slug'       => 'salt_shaker',
					'first-path' => 'tools.php?page=salt_shaker',
					'support'    => false,
					'parent'     => array(
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

/**
 * Plugin activation hook - Install database tables and default options
 *
 * @since 2.1.0
 */
function salt_shaker_activate() {
	// Deactivate PRO version if active
	if ( is_plugin_active( 'salt-shaker-pro/salt-shaker-pro.php' ) ) {
		deactivate_plugins( 'salt-shaker-pro/salt-shaker-pro.php' );
	}

	// Run installer
	Installer::install();
}

register_activation_hook( __FILE__, 'salt_shaker_activate' );

// Define the plugin constants
const SALT_SHAKER_VERSION = '2.1.0';
define( 'SALT_SHAKER_PLUGIN_FILE', __FILE__ );
define( 'SALT_SHAKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SALT_SHAKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SALT_SHAKER_PATH', dirname( __FILE__ ) );

// Initialize the plugin.
$plugin = Plugin::get_instance();
$plugin->run();
