<?php

namespace SaltShaker\Tests\Unit;

use Brain\Monkey\Functions;
use SaltShaker\Tests\Support\ConfigFixture;
use SaltShaker\Tests\Support\CoreAccess;
use SaltShaker\Tests\Support\TestCase;

class FilesystemGatewayTest extends TestCase {
	public function test_direct_method_returns_usable_handle(): void {
		$core = CoreAccess::newCore();
		$fs   = CoreAccess::invokeInitFilesystem( $core );

		$this->assertIsObject( $fs );
		$this->assertSame( $this->fake_filesystem, $fs );
	}

	public function test_non_direct_method_returns_false(): void {
		Functions\when( 'get_filesystem_method' )->justReturn( 'ftpext' );

		$core = CoreAccess::newCore();
		$fs   = CoreAccess::invokeInitFilesystem( $core );

		$this->assertFalse( $fs );
	}

	public function test_wp_filesystem_false_returns_false(): void {
		Functions\when( 'WP_Filesystem' )->justReturn( false );

		$core = CoreAccess::newCore();
		$fs   = CoreAccess::invokeInitFilesystem( $core );

		$this->assertFalse( $fs );
	}

	public function test_requires_admin_file_when_wp_filesystem_undefined(): void {
		$admin_file = ABSPATH . 'wp-admin/includes/file.php';
		$dir        = dirname( $admin_file );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
			$this->fail( "Failed to create directory: {$dir}" );
		}
		file_put_contents( $admin_file, "<?php \$GLOBALS['_ss_test_admin_file_loaded'] = true;" );

		Functions\when( 'function_exists' )->alias( function ( $function ) {
			if ( 'WP_Filesystem' === $function ) {
				return false;
			}
			if ( 'get_filesystem_method' === $function ) {
				return true;
			}

			return function_exists( $function );
		} );

		unset( $GLOBALS['_ss_test_admin_file_loaded'] );

		$core = CoreAccess::newCore();
		CoreAccess::invokeInitFilesystem( $core );

		$this->assertTrue( $GLOBALS['_ss_test_admin_file_loaded'] ?? false, 'Admin file was not loaded when WP_Filesystem was undefined.' );
	}

	public function test_no_filesystem_mutation_during_bootstrap(): void {
		$core = CoreAccess::newCore();
		CoreAccess::invokeInitFilesystem( $core );

		$this->assertNoTmpArtifacts( ABSPATH );
	}

	public function test_non_direct_method_aborts_rotation_and_leaves_config_unchanged(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );

		Functions\when( 'get_filesystem_method' )->justReturn( 'ftpext' );

		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'ftpext-value' ) );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertSame( $body, file_get_contents( $path ) );
		$this->assertSame( 'Filesystem access is not available.', $core->getLastFailureReason() );
		$this->assertNoTmpArtifacts( ABSPATH );
	}

	public function test_no_credential_prompt_is_ever_requested(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );

		$credentials_requested = false;
		Functions\when( 'request_filesystem_credentials' )->alias( function () use ( &$credentials_requested ) {
			$credentials_requested = true;
			return false;
		} );

		Functions\when( 'get_filesystem_method' )->justReturn( 'ftpext' );

		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'prompt-value' ) );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertFalse( $credentials_requested, 'request_filesystem_credentials() was invoked.' );
		$this->assertNoTmpArtifacts( ABSPATH );
	}
}
