<?php

namespace SaltShaker\Tests\Support;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

abstract class TestCase extends PHPUnitTestCase {
	/** @var FakeFilesystem */
	protected $fake_filesystem;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_generate_password' )->justReturn( 'Fixed_Test_Value_0123456789' );

		$this->fake_filesystem = new FakeFilesystem();
		$GLOBALS['wp_filesystem'] = $this->fake_filesystem;

		Functions\when( 'WP_Filesystem' )->justReturn( true );
		Functions\when( 'get_filesystem_method' )->justReturn( 'direct' );
	}

	protected function assertFileParses( string $path ): void {
		$php = $this->locatePhpBinary();
		$cmd = escapeshellarg( $php ) . ' -l ' . escapeshellarg( $path ) . ' 2>&1';
		exec( $cmd, $output, $exit_code );

		$this->assertSame( 0, $exit_code, "File failed PHP syntax check: {$path}\n" . implode( "\n", $output ) );
	}

	protected function assertNoTmpArtifacts( string $dir ): void {
		$artifacts = glob( rtrim( $dir, '/\\' ) . '/*.tmp.*' );
		$this->assertEmpty( $artifacts, 'Temporary artifacts left behind: ' . implode( ', ', $artifacts ?: [] ) );
	}

	protected function locatePhpBinary(): string {
		if ( defined( 'PHP_BINARY' ) && PHP_BINARY && file_exists( PHP_BINARY ) ) {
			return PHP_BINARY;
		}

		// Search PATH for php/php.exe.
		$search = [ 'php', 'php.exe' ];
		foreach ( $search as $name ) {
			foreach ( explode( PATH_SEPARATOR, getenv( 'PATH' ) ) as $dir ) {
				$full = rtrim( $dir, '/\\' ) . DIRECTORY_SEPARATOR . $name;
				if ( file_exists( $full ) ) {
					return $full;
				}
			}
		}

		// Fallback to common Laragon paths on Windows.
		$candidates = [
			'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
			'C:\\laragon\\bin\\php\\php-8.3.16-Win32-vs16-x64\\php.exe',
		];
		foreach ( $candidates as $candidate ) {
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}

		throw new \RuntimeException( 'Unable to locate PHP binary for syntax checks.' );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_filesystem'] );

		Monkey\tearDown();

		// Remove any fixture files created under ABSPATH or its parent.
		$abspath = rtrim( ABSPATH, '/\\' );
		$parent  = dirname( $abspath );

		foreach ( [ $abspath, $parent ] as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $iterator as $file ) {
				$pathname = $file->getPathname();
				// Only remove files (and the leaf directories we created).
				if ( $file->isFile() ) {
					// Reset read-only bit on Windows so unlink succeeds.
					chmod( $pathname, 0777 );
					unlink( $pathname );
				}
			}
		}

		parent::tearDown();
	}
}
