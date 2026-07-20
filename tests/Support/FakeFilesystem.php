<?php

namespace SaltShaker\Tests\Support;

class FakeFilesystem {
	/** @var bool */
	public $fail_put_contents = false;

	/** @var bool */
	public $fail_move = false;

	/** @var bool */
	public $fail_get_contents = false;

	/** @var bool */
	public $fail_delete = false;

	/** @var bool */
	public $fail_chmod = false;

	public function exists( string $path ): bool {
		return file_exists( $path );
	}

	public function is_writable( string $path ): bool {
		return is_writable( $path );
	}

	/**
	 * @return string|false
	 */
	public function get_contents( string $path ) {
		if ( $this->fail_get_contents ) {
			return false;
		}

		return file_get_contents( $path );
	}

	public function put_contents( string $path, string $content, $mode = false ): bool {
		if ( $this->fail_put_contents ) {
			return false;
		}

		$dir = dirname( $path );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
			return false;
		}

		$result = file_put_contents( $path, $content );
		if ( false === $result ) {
			return false;
		}

		if ( false !== $mode ) {
			chmod( $path, (int) $mode );
		}

		return true;
	}

	public function move( string $from, string $to, bool $overwrite = false ): bool {
		if ( $this->fail_move ) {
			return false;
		}

		if ( file_exists( $to ) && ! $overwrite ) {
			return false;
		}

		if ( rename( $from, $to ) ) {
			return true;
		}

		// Fallback: copy then delete, matching WP_Filesystem_Direct::move().
		if ( ! copy( $from, $to ) ) {
			return false;
		}

		chmod( $to, fileperms( $from ) );
		unlink( $from );

		return true;
	}

	public function delete( string $path ): bool {
		if ( $this->fail_delete ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return true;
		}

		// Reset read-only bit on Windows before removal.
		chmod( $path, 0777 );

		return unlink( $path );
	}

	/**
	 * @return string
	 */
	public function getchmod( string $path ) {
		if ( ! file_exists( $path ) ) {
			return '';
		}

		$perms = fileperms( $path );
		if ( false === $perms ) {
			return '';
		}

		return substr( decoct( $perms ), -3 );
	}

	public function chmod( string $path, int $mode ): bool {
		if ( $this->fail_chmod ) {
			return false;
		}

		return chmod( $path, $mode );
	}

	public function reset(): void {
		$this->fail_put_contents = false;
		$this->fail_move         = false;
		$this->fail_get_contents = false;
		$this->fail_delete       = false;
		$this->fail_chmod        = false;
	}
}
