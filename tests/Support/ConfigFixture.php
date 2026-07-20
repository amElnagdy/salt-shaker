<?php

namespace SaltShaker\Tests\Support;

class ConfigFixture {
	public static function rootConfigPath(): string {
		return ABSPATH . 'wp-config.php';
	}

	public static function aboveRootConfigPath(): string {
		return dirname( rtrim( ABSPATH, '/\\' ) ) . '/wp-config.php';
	}

	public static function aboveRootGuardPath(): string {
		return dirname( rtrim( ABSPATH, '/\\' ) ) . '/wp-settings.php';
	}

	public static function dedicatedSaltPath(): string {
		return ABSPATH . 'wp-salt.php';
	}

	public static function sampleBody( array $present_keys = null ): string {
		$keys = CoreAccess::getSaltKeys();
		if ( $present_keys === null ) {
			$present_keys = $keys;
		}

		$body = "<?php\n// Sample config for tests\ndefine('DB_NAME', 'wordpress');\n";

		foreach ( $keys as $key ) {
			if ( in_array( $key, $present_keys, true ) ) {
				$body .= "define('{$key}', 'old {$key} value');\n";
			}
		}

		$body .= "\$table_prefix = 'wp_';\n/* That's all, stop editing! */\n";

		return $body;
	}

	public static function buildNewSaltLines( array $values ): array {
		$keys  = CoreAccess::getSaltKeys();
		$lines = [];

		foreach ( $keys as $index => $key ) {
			$value   = isset( $values[ $index ] ) ? $values[ $index ] : "new-value-{$index}";
			$lines[] = "define('{$key}', '{$value}');";
		}

		return $lines;
	}

	public static function writeFile( string $path, string $content, int $perms = 0777 ): string {
		$dir = dirname( $path );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
			throw new \RuntimeException( "Failed to create directory: {$dir}" );
		}

		file_put_contents( $path, $content );
		chmod( $path, $perms );

		return $path;
	}

	public static function cleanup(): void {
		$paths = [
			self::rootConfigPath(),
			self::dedicatedSaltPath(),
			self::aboveRootConfigPath(),
			self::aboveRootGuardPath(),
		];

		foreach ( $paths as $path ) {
			if ( is_file( $path ) ) {
				chmod( $path, 0777 );
				unlink( $path );
			}
		}

		// Remove any .tmp.* artifacts near targets.
		foreach ( [ ABSPATH, dirname( rtrim( ABSPATH, '/\\' ) ) ] as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			foreach ( glob( $dir . '*.tmp.*' ) as $tmp ) {
				if ( is_file( $tmp ) ) {
					chmod( $tmp, 0777 );
					unlink( $tmp );
				}
			}
		}
	}
}
