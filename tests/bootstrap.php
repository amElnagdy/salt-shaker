<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Build a per-process temp workspace for filesystem fixtures.
$workspace_base = sys_get_temp_dir() . '/ss-tests-' . getmypid();
$abspath        = $workspace_base . '/site/';

if ( ! is_dir( $workspace_base ) && ! mkdir( $workspace_base, 0777, true ) && ! is_dir( $workspace_base ) ) {
	throw new RuntimeException( "Failed to create test workspace: {$workspace_base}" );
}

if ( ! is_dir( $abspath ) && ! mkdir( $abspath, 0777, true ) && ! is_dir( $abspath ) ) {
	throw new RuntimeException( "Failed to create ABSPATH: {$abspath}" );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $abspath );
}

/**
 * Recursively remove a directory.
 *
 * @param string $dir Directory to remove.
 */
function ss_tests_rmdir( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getPathname() );
		} else {
			unlink( $file->getPathname() );
		}
	}

	rmdir( $dir );
}

// Clean the entire workspace on shutdown.
register_shutdown_function( static function () use ( $workspace_base ) {
	ss_tests_rmdir( $workspace_base );
} );
