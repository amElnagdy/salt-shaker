<?php

namespace SaltShaker\Tests\Unit;

use SaltShaker\Tests\Support\ConfigFixture;
use SaltShaker\Tests\Support\CoreAccess;
use SaltShaker\Tests\Support\TestCase;

class FileLayoutTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		ConfigFixture::cleanup();
	}

	protected function tearDown(): void {
		ConfigFixture::cleanup();
		parent::tearDown();
	}

	public function test_standard_config_rewrite(): void {
		$path    = ConfigFixture::rootConfigPath();
		$body    = ConfigFixture::sampleBody();
		$before  = ConfigFixture::writeFile( $path, $body, 0644 );
		$perms   = fileperms( $path );
		$core    = CoreAccess::newCore();
		$keys    = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( [
			'std-auth-key',
			'std-secure-auth-key',
			'std-logged-in-key',
			'std-nonce-key',
			'std-auth-salt',
			'std-secure-auth-salt',
			'std-logged-in-salt',
			'std-nonce-salt',
		] );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $result );

		$after = file_get_contents( $path );
		foreach ( $new_lines as $line ) {
			$this->assertStringContainsString( $line, $after );
		}

		// Non-salt lines unchanged.
		$this->assertStringContainsString( "define('DB_NAME', 'wordpress');", $after );
		$this->assertStringContainsString( "\$table_prefix = 'wp_';", $after );
		$this->assertStringContainsString( "// Sample config for tests", $after );

		$this->assertSame( $perms, fileperms( $path ), 'Original permissions were not preserved.' );
		$this->assertNoTmpArtifacts( ABSPATH );
		$this->assertFileParses( $path );
	}

	public function test_above_root_config_selected_and_rewritten(): void {
		$root_path   = ConfigFixture::rootConfigPath();
		$above_path  = ConfigFixture::aboveRootConfigPath();
		$guard_path  = ConfigFixture::aboveRootGuardPath();

		// Ensure root config and guard do not exist.
		if ( is_file( $root_path ) ) {
			unlink( $root_path );
		}
		if ( is_file( $guard_path ) ) {
			unlink( $guard_path );
		}

		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $above_path, $body, 0644 );
		$core      = CoreAccess::newCore();
		$selected  = CoreAccess::invokeGetConfigFile( $core );

		$this->assertSame( realpath( $above_path ), realpath( $selected ) );

		$keys       = CoreAccess::getSaltKeys();
		$new_lines  = ConfigFixture::buildNewSaltLines( [
			'up-auth-key',
			'up-secure-auth-key',
			'up-logged-in-key',
			'up-nonce-key',
			'up-auth-salt',
			'up-secure-auth-salt',
			'up-logged-in-salt',
			'up-nonce-salt',
		] );

		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $result );

		$after = file_get_contents( $above_path );
		foreach ( $new_lines as $line ) {
			$this->assertStringContainsString( $line, $after );
		}

		$this->assertStringContainsString( "define('DB_NAME', 'wordpress');", $after );
		$this->assertNoTmpArtifacts( dirname( rtrim( ABSPATH, '/\\' ) ) );
		$this->assertFileParses( $above_path );
	}

	public function test_above_root_guarded_when_wp_settings_present(): void {
		$root_path  = ConfigFixture::rootConfigPath();
		$above_path = ConfigFixture::aboveRootConfigPath();
		$guard_path = ConfigFixture::aboveRootGuardPath();

		if ( is_file( $root_path ) ) {
			unlink( $root_path );
		}

		ConfigFixture::writeFile( $above_path, ConfigFixture::sampleBody(), 0644 );
		ConfigFixture::writeFile( $guard_path, '<?php // guard', 0644 );

		$core     = CoreAccess::newCore();
		$selected = CoreAccess::invokeGetConfigFile( $core );

		$this->assertFalse( $selected );
	}

	public function test_dedicated_wp_salt_file_takes_precedence(): void {
		$root_path  = ConfigFixture::rootConfigPath();
		$salt_path  = ConfigFixture::dedicatedSaltPath();

		ConfigFixture::writeFile( $root_path, ConfigFixture::sampleBody(), 0644 );
		ConfigFixture::writeFile( $salt_path, ConfigFixture::sampleBody(), 0644 );

		$core     = CoreAccess::newCore();
		$selected = CoreAccess::invokeGetConfigFile( $core );

		$this->assertSame( $salt_path, $selected );
	}

	public function test_non_salt_content_is_preserved_byte_for_byte(): void {
		$path = ConfigFixture::rootConfigPath();
		$keys = CoreAccess::getSaltKeys();

		$body = "<?php\n";
		$body .= "/**\n * Custom comment block\n */\n";
		$body .= "define('DB_NAME', 'wordpress');\n";
		$body .= "define('CUSTOM_CONSTANT', 'custom value');\n";
		foreach ( $keys as $key ) {
			$body .= "define('{$key}', 'old value with \\\"quotes\\\" and \\\\backslash\\\\\');\n";
		}
		$body .= "\$table_prefix = 'wp_';\n";
		$body .= "/* That's all, stop editing! */\n";

		$before = ConfigFixture::writeFile( $path, $body, 0644 );

		$new_lines = ConfigFixture::buildNewSaltLines( [
			'preserved-auth-key',
			'preserved-secure-auth-key',
			'preserved-logged-in-key',
			'preserved-nonce-key',
			'preserved-auth-salt',
			'preserved-secure-auth-salt',
			'preserved-logged-in-salt',
			'preserved-nonce-salt',
		] );

		$core   = CoreAccess::newCore();
		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $result );
		$this->assertFileParses( $path );
		$this->assertNoTmpArtifacts( ABSPATH );

		$after = file_get_contents( $path );
		foreach ( $new_lines as $line ) {
			$this->assertStringContainsString( $line, $after );
		}

		$after_lines = explode( "\n", rtrim( $after, "\n" ) );
		$this->assertSame( '<?php', $after_lines[0] );
		$this->assertSame( '/**', $after_lines[1] );
		$this->assertSame( ' * Custom comment block', $after_lines[2] );
		$this->assertSame( ' */', $after_lines[3] );
		$this->assertSame( "define('DB_NAME', 'wordpress');", $after_lines[4] );
		$this->assertSame( "define('CUSTOM_CONSTANT', 'custom value');", $after_lines[5] );
		$this->assertSame( "\$table_prefix = 'wp_';", $after_lines[ count( $after_lines ) - 2 ] );
		$this->assertSame( "/* That's all, stop editing! */", $after_lines[ count( $after_lines ) - 1 ] );
	}
}
