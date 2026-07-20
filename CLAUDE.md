# Salt Shaker

WordPress plugin that rotates the eight salt keys in `wp-config.php` (or
`wp-salt.php`), manually or on a schedule.

## Layout

- `includes/` — PSR-4 `SaltShaker\` namespace. `Core.php` owns salt generation and
  the config-file rewrite; `Admin.php` owns settings screens and AJAX handlers.
- `assets/js/` — React admin UI. `assets/build/` is the compiled output and is
  committed.
- `tests/` — PHPUnit + Brain Monkey. No WordPress runtime; `$wp_filesystem` is a
  test double.
- `vendor/` — committed, ships to users. Only `freemius/` is a runtime dependency.

## Commands

```
composer test      # PHPUnit suite
npm run build      # compile assets/js -> assets/build
npm run lint:js
```

## Constraints

- **PHP 7.4 minimum.** No PHP 8-only syntax.
- **All filesystem access goes through the WordPress Filesystem API**
  (`$wp_filesystem`), never `fopen`/`rename`/`chmod`/`is_writable` directly.
  WordPress.org rejects direct calls.
- The config file must never be modified unless the full replacement content
  exists. Writes are atomic: temp file, then move.
- Non-salt lines, including line terminators, must survive a rotation
  byte-for-byte. The file may be CRLF.
- `@wordpress/*` packages are externalized by `@wordpress/scripts`, not bundled.
  They resolve to WordPress core's copies at runtime.
- Before tagging a release, regenerate the autoloader without dev dependencies
  (`composer install --no-dev`) — the committed `vendor/composer/` maps ship to
  users and must not reference dev packages.
