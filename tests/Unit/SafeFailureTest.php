<?php

namespace SaltShaker\Tests\Unit;

use SaltShaker\Tests\Support\ConfigFixture;
use SaltShaker\Tests\Support\CoreAccess;
use SaltShaker\Tests\Support\TestCase;

class SafeFailureTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		ConfigFixture::cleanup();
	}

	protected function tearDown(): void {
		ConfigFixture::cleanup();
		parent::tearDown();
	}

	public function test_missing_constants_not_injected(): void {
		$path      = ConfigFixture::rootConfigPath();
		$keys      = CoreAccess::getSaltKeys();
		$present   = [ 'AUTH_KEY', 'NONCE_SALT' ];
		$body      = ConfigFixture::sampleBody( $present );
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$new_lines = ConfigFixture::buildNewSaltLines( [
			'new-auth-key',
			'new-secure-auth-key',
			'new-logged-in-key',
			'new-nonce-key',
			'new-auth-salt',
			'new-secure-auth-salt',
			'new-logged-in-salt',
			'new-nonce-salt',
		] );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $result );

		$after = file_get_contents( $path );

		// Present keys were replaced.
		$this->assertStringContainsString( "define('AUTH_KEY', 'new-auth-key');", $after );
		$this->assertStringContainsString( "define('NONCE_SALT', 'new-nonce-salt');", $after );

		// Absent keys were NOT injected.
		$this->assertStringNotContainsString( "define('SECURE_AUTH_KEY', 'new-secure-auth-key');", $after );
		$this->assertStringNotContainsString( "define('LOGGED_IN_KEY', 'new-logged-in-key');", $after );
		$this->assertStringNotContainsString( "define('NONCE_KEY', 'new-nonce-key');", $after );
		$this->assertStringNotContainsString( "define('AUTH_SALT', 'new-auth-salt');", $after );
		$this->assertStringNotContainsString( "define('SECURE_AUTH_SALT', 'new-secure-auth-salt');", $after );
		$this->assertStringNotContainsString( "define('LOGGED_IN_SALT', 'new-logged-in-salt');", $after );

		// Count of define( lines unchanged for missing ones.
		$this->assertSame( 3, substr_count( $after, 'define(' ) ); // DB_NAME + 2 present salts.

		// Non-salt content intact.
		$this->assertStringContainsString( "define('DB_NAME', 'wordpress');", $after );
		$this->assertStringContainsString( "\$table_prefix = 'wp_';", $after );

		$this->assertNoTmpArtifacts( ABSPATH );
		$this->assertFileParses( $path );
	}

	public function test_zero_salt_lines_leaves_content_effectively_unchanged(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = "<?php\ndefine('DB_NAME', 'wordpress');\n\$table_prefix = 'wp_';\n";
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'unused-value' ) );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		// No salt lines match, so the file content is effectively unchanged.
		$this->assertTrue( $result );
		$this->assertSame( $body, file_get_contents( $path ) );
		$this->assertNoTmpArtifacts( ABSPATH );
		$this->assertFileParses( $path );
	}

	public function test_put_contents_failure_leaves_config_unchanged(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'injected-value' ) );

		$this->fake_filesystem->fail_put_contents = true;

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertSame( $body, file_get_contents( $path ) );
		$this->assertNoTmpArtifacts( ABSPATH );
	}

	public function test_move_failure_leaves_config_unchanged(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'injected-value' ) );

		$this->fake_filesystem->fail_move = true;

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertSame( $body, file_get_contents( $path ) );
		$this->assertNoTmpArtifacts( ABSPATH );
	}

	public function test_get_contents_failure_leaves_config_unchanged(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'injected-value' ) );

		$this->fake_filesystem->fail_get_contents = true;

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertSame( $body, file_get_contents( $path ) );
		$this->assertNoTmpArtifacts( ABSPATH );
	}

	public function test_readonly_config_fails_safely_and_reports_reason(): void {
		$path   = ConfigFixture::rootConfigPath();
		$body   = ConfigFixture::sampleBody();
		$before = ConfigFixture::writeFile( $path, $body, 0644 );
		chmod( $path, 0444 );

		if ( is_writable( $path ) ) {
			chmod( $path, 0777 );
			$this->markTestSkipped( 'read-only bits bypassed (root)' );
		}

		$core     = CoreAccess::newCore();
		$selected = CoreAccess::invokeGetConfigFile( $core );

		$this->assertFalse( $selected );
		$this->assertSame( 'Salt definitions file is not writable.', $core->getLastFailureReason() );

		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'should-not-be-written' ) );
		$result    = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $result );
		$this->assertSame( $body, file_get_contents( $path ), 'Read-only file was modified.' );
		$this->assertNoTmpArtifacts( ABSPATH );

		chmod( $path, 0777 );
	}
}
