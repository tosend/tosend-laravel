# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-09-26

### Added

- `ToSendAdmin`: client for the Admin API (domains, delivery logs, suppressions, webhooks, sending keys, client teams, and sending with an admin key that holds `emails:send`). Rejects non-admin keys at construction; `forAccount()` targets a child team via `X-Account-Id`. Resolved from the container when `TOSEND_ADMIN_KEY` (config `tosend.admin_key`) is set.
- `ToSendException::getErrorCode()` returns the Admin API's `errors.code` (for example `insufficient_scope`).

### Fixed

- `AccountInfo` now matches what `GET /v2/info` returns: `id`, `status`, `planType`, `creditBalance`, `monthlyEmailLimit`, `limitPerSecond`, `sesRegion`, `bounceRate`, `complaintRate`, `sesStatus`. The old `title`, `emailsUsageThisMonth` and `emailsSentLast24Hours` fields were never returned by the API and always read empty. **Breaking** for code that read them.
- `Domain` now carries `id`, `isDefault`, `totalSent`, `totalBounced` and `totalComplained` (the API never returned `created_at`), and lives in its own file so it autoloads on its own.
- Error responses whose `errors` or `message` are not the expected type no longer cause a `TypeError`; a non-JSON error body keeps its HTTP status.

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
