<?php

namespace SaltShaker\Tests\Unit;

use SaltShaker\Tests\Support\ConfigFixture;
use SaltShaker\Tests\Support\CoreAccess;
use SaltShaker\Tests\Support\TestCase;

class TrickyValuesTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		ConfigFixture::cleanup();
	}

	protected function tearDown(): void {
		ConfigFixture::cleanup();
		parent::tearDown();
	}

	/**
	 * Build a raw salt block using the given value for every key.
	 */
	private function rawSaltBlock( string $value ): string {
		$lines = [];
		foreach ( CoreAccess::getSaltKeys() as $key ) {
			$lines[] = "define('{$key}', '{$value}');";
		}

		return implode( "\n", $lines );
	}

	public function test_values_with_single_quotes_are_rejected_by_process_salts(): void {
		$core   = CoreAccess::newCore();
		$raw    = $this->rawSaltBlock( "it\\'s a test" );
		$result = CoreAccess::invokeProcessSalts( $core, $raw );

		$this->assertFalse( $result, 'processSalts rejected raw input containing single quotes.' );
	}

	public function test_values_with_escaped_quotes_are_rejected_by_process_salts(): void {
		$core   = CoreAccess::newCore();
		$raw    = $this->rawSaltBlock( "it\\\\'s a test" );
		$result = CoreAccess::invokeProcessSalts( $core, $raw );

		$this->assertFalse( $result, 'processSalts rejected raw input containing escaped quotes.' );
	}

	public function test_values_with_backslashes_are_stripped_and_produce_valid_file(): void {
		$core   = CoreAccess::newCore();
		// Double-quoted PHP string: "a\\\\b" yields the raw text a\\b (two consecutive backslashes).
		$raw    = $this->rawSaltBlock( "a\\\\b\\\\c" );
		$result = CoreAccess::invokeProcessSalts( $core, $raw );

		$this->assertIsString( $result );
		// The regex in Core.php removes pairs of backslashes; record the observed result.
		$this->assertStringContainsString( "define('AUTH_KEY', 'abc');", $result );

		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		ConfigFixture::writeFile( $path, $body, 0644 );

		$keys      = CoreAccess::getSaltKeys();
		$new_lines = explode( "\n", $result );
		$ok        = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $ok );
		$this->assertFileParses( $path );
		$this->assertNoTmpArtifacts( ABSPATH );

		$after = file_get_contents( $path );
		foreach ( $new_lines as $line ) {
			$this->assertStringContainsString( $line, $after );
		}
	}

	public function test_crlf_line_terminators_are_preserved(): void {
		$path = ConfigFixture::rootConfigPath();
		$keys = CoreAccess::getSaltKeys();

		$lines = [
			"<?php",
			"// Sample config for tests",
			"define('DB_NAME', 'wordpress');",
		];
		foreach ( $keys as $key ) {
			$lines[] = "define('{$key}', 'old {$key} value');";
		}
		$lines[] = "\$table_prefix = 'wp_';";
		$lines[] = "/* That's all, stop editing! */";

		$body   = implode( "\r\n", $lines ) . "\r\n";
		$before = ConfigFixture::writeFile( $path, $body, 0644 );

		$new_lines = ConfigFixture::buildNewSaltLines( [
			'crlf-auth-key',
			'crlf-secure-auth-key',
			'crlf-logged-in-key',
			'crlf-nonce-key',
			'crlf-auth-salt',
			'crlf-secure-auth-salt',
			'crlf-logged-in-salt',
			'crlf-nonce-salt',
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

		// No bare LF should appear outside a CRLF pair.
		$without_crlf = str_replace( "\r\n", '', $after );
		$this->assertStringNotContainsString( "\n", $without_crlf, 'Bare LF found in a CRLF file after rotation.' );

		// Non-salt lines must be byte-identical.
		$after_lines = explode( "\r\n", rtrim( $after, "\r\n" ) );
		$this->assertSame( "<?php", $after_lines[0] );
		$this->assertSame( "// Sample config for tests", $after_lines[1] );
		$this->assertSame( "define('DB_NAME', 'wordpress');", $after_lines[2] );
		$this->assertSame( "\$table_prefix = 'wp_';", $after_lines[ count( $after_lines ) - 2 ] );
		$this->assertSame( "/* That's all, stop editing! */", $after_lines[ count( $after_lines ) - 1 ] );
	}

	public function test_permissions_are_preserved_after_rotation(): void {
		$path = ConfigFixture::rootConfigPath();
		$body = ConfigFixture::sampleBody();

		ConfigFixture::writeFile( $path, $body, 0644 );
		$before_mode = fileperms( $path ) & 07777;

		$keys      = CoreAccess::getSaltKeys();
		$new_lines = ConfigFixture::buildNewSaltLines( [
			'perm-auth-key',
			'perm-secure-auth-key',
			'perm-logged-in-key',
			'perm-nonce-key',
			'perm-auth-salt',
			'perm-secure-auth-salt',
			'perm-logged-in-salt',
			'perm-nonce-salt',
		] );

		$core   = CoreAccess::newCore();
		$result = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $result );
		$this->assertFileParses( $path );
		$this->assertNoTmpArtifacts( ABSPATH );

		$after_mode = fileperms( $path ) & 07777;
		$this->assertSame( $before_mode, $after_mode, 'File permissions were not preserved.' );
		$this->assertLessThanOrEqual( $before_mode, $after_mode, 'File became more permissive after rotation.' );
	}
}
