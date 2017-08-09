<?php
/*
Plugin Name: Salt Shaker
Plugin URI: https://wpcolt.com/
Description: A plugin that changes the WP salt values to enhance and strengthen WordPress security.
Version: 1.1.3
Author: WPColt
Author URI: https://wpcolt.com/
License: GPLv2 or later
Text Domain: salt-shaker
Domain Path: /languages
*/

/*
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

Copyright 2016 WPColt.
*/

include_once(plugin_dir_path(__FILE__) . "_inc/loader.php");
$salt_shaker = new Salter();

function salt_shaker_load_plugin_textdomain() {
    load_plugin_textdomain( 'salt-shaker', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'salt_shaker_load_plugin_textdomain' );