<?php

namespace SaltShaker\Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use SaltShaker\Core;

class CoreAccess {
	public static function newCore(): Core {
		return ( new ReflectionClass( Core::class ) )->newInstanceWithoutConstructor();
	}

	public static function invokeInitFilesystem( Core $core ) {
		$method = new ReflectionMethod( Core::class, 'initFilesystem' );
		$method->setAccessible( true );

		return $method->invoke( $core );
	}

	public static function getSaltKeys(): array {
		$ref = new ReflectionClass( Core::class );
		$constant = $ref->getConstant( 'SALT_KEYS' );

		return is_array( $constant ) ? $constant : [];
	}

	public static function invokeGetConfigFile( Core $core ) {
		$method = new ReflectionMethod( Core::class, 'getConfigFile' );
		$method->setAccessible( true );

		return $method->invoke( $core );
	}

	public static function invokeProcessSalts( Core $core, string $salts ) {
		$method = new ReflectionMethod( Core::class, 'processSalts' );
		$method->setAccessible( true );

		return $method->invoke( $core, $salts );
	}

	public static function invokeWriteSalts( Core $core, array $salt_keys, array $new_salts ): bool {
		$method = new ReflectionMethod( Core::class, 'writeSalts' );
		$method->setAccessible( true );

		return $method->invoke( $core, $salt_keys, $new_salts );
	}

	public static function invokeGenerateLocalSalts( Core $core ): string {
		$method = new ReflectionMethod( Core::class, 'generateLocalSalts' );
		$method->setAccessible( true );

		return $method->invoke( $core );
	}
}
