# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-01-30

### Fixed

- PHP 8.4 deprecation warning: Explicitly mark `$envelope` parameter as nullable in `ToSendTransport::send()`

## [1.1.0] - 2026-01-30

### Added

- Laravel 12.x support

### Changed

- Updated `ToSendTransport::send()` method signature for Symfony Mailer 7.x compatibility (Laravel 11+)
- Updated composer dependencies to support Laravel 10.x, 11.x, and 12.x

## [1.0.0] - 2026-01-24

### Added

- Initial release
- `ToSend::send()` - Send single emails
- `ToSend::batch()` - Send multiple emails in one request
- `ToSend::getAccountInfo()` - Retrieve account information
- Laravel Mail driver integration
- Fluent `Email` builder class
- `Attachment` helper with `fromPath()`, `fromContent()`, `fromBase64()`
- `Address` data class for email addresses
- Response DTOs: `EmailResponse`, `BatchResponse`, `AccountInfo`
- `ToSendException` with error type helpers
- Full test suite
