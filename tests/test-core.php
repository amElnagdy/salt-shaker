<?php
/**
 * Tests for Core class
 */

use SaltShaker\Core;

class Test_Core extends WP_UnitTestCase {

	private $core;

	public function set_up() {
		parent::set_up();
		$this->core = new Core();
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test that Core can be instantiated
	 */
	public function test_core_instantiation() {
		$this->assertInstanceOf( Core::class, $this->core );
	}

	/**
	 * Test getSaltsArray() method
	 */
	public function test_get_salts_array() {
		$salts = $this->core->getSaltsArray();

		$this->assertIsArray( $salts );
		$this->assertCount( 8, $salts );

		$expected_keys = [
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $salts, "Salt array should have {$key}" );
		}
	}

	/**
	 * Test count_active_sessions() method
	 */
	public function test_count_active_sessions() {
		// Create a test user with a session
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$manager->create( time() + 3600 );

		$count = $this->core->count_active_sessions();

		$this->assertIsInt( $count );
		$this->assertGreaterThanOrEqual( 1, $count );
	}

	/**
	 * Test add_cron_schedule() method
	 */
	public function test_add_cron_schedule() {
		$schedules = $this->core->add_cron_schedule( [] );

		$this->assertIsArray( $schedules );
		$this->assertArrayHasKey( 'weekly', $schedules );
		$this->assertArrayHasKey( 'monthly', $schedules );
		$this->assertArrayHasKey( 'quarterly', $schedules );
		$this->assertArrayHasKey( 'biannually', $schedules );

		$this->assertEquals( 7 * DAY_IN_SECONDS, $schedules['weekly']['interval'] );
		$this->assertEquals( 30 * DAY_IN_SECONDS, $schedules['monthly']['interval'] );
		$this->assertEquals( 90 * DAY_IN_SECONDS, $schedules['quarterly']['interval'] );
		$this->assertEquals( 180 * DAY_IN_SECONDS, $schedules['biannually']['interval'] );
	}

	/**
	 * Test that existing cron schedules are not overwritten
	 */
	public function test_add_cron_schedule_preserves_existing() {
		$existing = [
			'weekly' => [
				'interval' => 604800,
				'display'  => 'Custom Weekly',
			],
		];

		$schedules = $this->core->add_cron_schedule( $existing );

		$this->assertEquals( 'Custom Weekly', $schedules['weekly']['display'] );
		$this->assertEquals( 604800, $schedules['weekly']['interval'] );
	}

	/**
	 * Test checkConfigFilePermissions() method
	 */
	public function test_check_config_file_permissions() {
		$permissions = $this->core->checkConfigFilePermissions();

		$this->assertIsArray( $permissions );
		$this->assertArrayHasKey( 'writable', $permissions );
		$this->assertArrayHasKey( 'message', $permissions );
		$this->assertArrayHasKey( 'file', $permissions );

		$this->assertIsBool( $permissions['writable'] );
		$this->assertIsString( $permissions['message'] );
	}

	/**
	 * Test salt keys constant
	 */
	public function test_salt_keys_constant() {
		$reflection = new ReflectionClass( Core::class );
		$constant = $reflection->getConstant( 'SALT_KEYS' );

		$this->assertIsArray( $constant );
		$this->assertCount( 8, $constant );
	}

	/**
	 * Test that Core hooks are registered
	 */
	public function test_core_hooks_registered() {
		$core = new Core();

		$this->assertGreaterThan( 0, has_filter( 'cron_schedules', [ $core, 'add_cron_schedule' ] ) );
		$this->assertGreaterThan( 0, has_action( 'salt_shaker_change_salts', [ $core, 'shuffleSalts' ] ) );
	}
}
