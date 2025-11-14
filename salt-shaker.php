<?php
/**
 * Plugin Name: Salt Shaker
 * Plugin URI: https://nagdy.me/
 * Description: A plugin that changes WordPress Authentication Unique Keys and Salts to enhance and strengthen WordPress security.
 * Version: 2.1.0
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
	require_once __DIR__ . '/vendor/autoload.php';
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
