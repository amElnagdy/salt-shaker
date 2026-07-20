<?php

namespace SaltShaker\Tests\Unit;

use Brain\Monkey\Functions;
use SaltShaker\Tests\Support\ConfigFixture;
use SaltShaker\Tests\Support\CoreAccess;
use SaltShaker\Tests\Support\TestCase;

class StatusReportingTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		ConfigFixture::cleanup();
	}

	protected function tearDown(): void {
		ConfigFixture::cleanup();
		parent::tearDown();
	}

	public function test_writable_file_reports_writable_and_rotation_succeeds(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );

		$core        = CoreAccess::newCore();
		$status      = $core->checkConfigFilePermissions();
		$keys        = CoreAccess::getSaltKeys();
		$new_lines   = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'status-value' ) );
		$rotation_ok = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertTrue( $status['writable'], 'Writable file reported as not writable.' );
		$this->assertSame( $path, $status['file'] );
		$this->assertSame( '', $status['message'] );
		$this->assertTrue( $rotation_ok, 'Rotation failed on a writable file.' );
	}

	public function test_readonly_file_reports_not_writable_and_rotation_fails(): void {
		$path   = ConfigFixture::rootConfigPath();
		$body   = ConfigFixture::sampleBody();
		$before = ConfigFixture::writeFile( $path, $body, 0644 );
		chmod( $path, 0444 );

		if ( is_writable( $path ) ) {
			chmod( $path, 0777 );
			$this->markTestSkipped( 'read-only bits bypassed (root)' );
		}

		$core        = CoreAccess::newCore();
		$status      = $core->checkConfigFilePermissions();
		$keys        = CoreAccess::getSaltKeys();
		$new_lines   = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'status-value' ) );
		$rotation_ok = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $status['writable'], 'Read-only file reported as writable.' );
		$this->assertSame( $path, $status['file'] );
		$this->assertStringContainsString( 'not writable', strtolower( $status['message'] ) );
		$this->assertFalse( $rotation_ok, 'Rotation succeeded on a read-only file.' );
		$this->assertSame( $body, file_get_contents( $path ) );

		chmod( $path, 0777 );
	}

	public function test_missing_file_reports_not_found_and_rotation_fails(): void {
		ConfigFixture::cleanup();

		$core        = CoreAccess::newCore();
		$status      = $core->checkConfigFilePermissions();
		$keys        = CoreAccess::getSaltKeys();
		$new_lines   = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'status-value' ) );
		$rotation_ok = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $status['writable'], 'Missing file reported as writable.' );
		$this->assertSame( '', $status['file'] );
		$this->assertStringContainsString( 'not found', strtolower( $status['message'] ) );
		$this->assertFalse( $rotation_ok, 'Rotation succeeded with a missing file.' );
	}

	public function test_non_direct_method_reports_unavailable_and_rotation_fails(): void {
		$path      = ConfigFixture::rootConfigPath();
		$body      = ConfigFixture::sampleBody();
		$before    = ConfigFixture::writeFile( $path, $body, 0644 );

		Functions\when( 'get_filesystem_method' )->justReturn( 'ftpext' );

		$core        = CoreAccess::newCore();
		$status      = $core->checkConfigFilePermissions();
		$keys        = CoreAccess::getSaltKeys();
		$new_lines   = ConfigFixture::buildNewSaltLines( array_fill( 0, 8, 'status-value' ) );
		$rotation_ok = CoreAccess::invokeWriteSalts( $core, $keys, $new_lines );

		$this->assertFalse( $status['writable'], 'Non-direct method reported as writable.' );
		$this->assertSame( '', $status['file'] );
		$this->assertStringContainsString( 'not available', strtolower( $status['message'] ) );
		$this->assertFalse( $rotation_ok, 'Rotation succeeded with a non-direct filesystem method.' );
		$this->assertSame( $body, file_get_contents( $path ) );
	}
}
