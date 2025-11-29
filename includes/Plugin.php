<?php

namespace SaltShaker;

class Plugin {
	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * Main Plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	public function run(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$options = new Options();
		$core    = new Core();
		$admin   = new Admin( $core, $options );
		$admin->init();
		$this->load_freemius();
		do_action( 'ss_fs_loaded' );
	}

	public function load_freemius() {
		global $ss_fs;
		if ( ! isset( $ss_fs ) ) {
			$ss_fs = fs_dynamic_init( array(
				'id'              => '8851',
				'slug'            => 'salt-shaker',
				'premium_slug'    => 'salt-shaker-pro',
				'type'            => 'plugin',
				'public_key'      => 'pk_f3d8cc8437a2ffddb2e1db1c8ad0e',
				'is_premium'      => false,
				'is_premium_only' => false,
				'has_addons'      => false,
				'has_paid_plans'  => true,
				'menu'            => array(
					'slug'       => 'salt_shaker',
					'first-path' => 'tools.php?page=salt_shaker',
					'contact'    => false,
					'parent'     => array(
						'slug' => 'tools.php',
					),
				),
			) );
		}

		return $ss_fs;
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'salt-shaker',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}
