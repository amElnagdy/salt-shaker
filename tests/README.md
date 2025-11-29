# Salt Shaker Tests

This directory contains PHPUnit tests for the Salt Shaker plugin.

## Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

### Installation

1. Install Composer dependencies:
```bash
composer install
```

2. Install WordPress test suite:
```bash
bash bin/install-wp-tests.sh wordpress_test root 'password' localhost latest
```

Replace `root` and `password` with your MySQL credentials.

## Running Tests

### Run all PHPUnit tests:
```bash
composer test
```

or

```bash
npm run test:php
```

### Run tests with coverage:
```bash
composer test:coverage
```

### Run specific test file:
```bash
vendor/bin/phpunit tests/test-audit-logger.php
```

### Run tests with verbose output:
```bash
vendor/bin/phpunit --verbose
```

## Test Structure

```
tests/
├── bootstrap.php           # PHPUnit bootstrap file
├── test-audit-logger.php   # Tests for AuditLogger class
├── test-installer.php      # Tests for Installer class
├── test-core.php          # Tests for Core class
└── README.md              # This file
```

## Writing Tests

All test classes should extend `WP_UnitTestCase` which provides WordPress-specific test methods and fixtures.

### Example Test:

```php
<?php
use SaltShaker\AuditLogger;

class Test_My_Feature extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        // Setup code
    }

    public function tear_down() {
        parent::tear_down();
        // Cleanup code
    }

    public function test_my_feature() {
        $this->assertTrue(true);
    }
}
```

## Continuous Integration

Tests run automatically on GitHub Actions for:
- PHP versions: 7.4, 8.0, 8.1, 8.2
- WordPress versions: latest, 6.4
- On push to master/main branches
- On pull requests

See `.github/workflows/tests.yml` for CI configuration.

## Troubleshooting

### MySQL Connection Issues

If you encounter MySQL connection errors, verify:
- MySQL is running
- Credentials are correct
- Database exists

### Test Database

The test database is completely separate from your development database. Tests will create and drop tables automatically.

### WordPress Test Suite Not Found

Run the install script again:
```bash
bash bin/install-wp-tests.sh wordpress_test root 'password' localhost latest
```

## Coverage

Generate HTML coverage report:
```bash
composer test:coverage
```

View the report by opening `coverage/index.html` in your browser.
