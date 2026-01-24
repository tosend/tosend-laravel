# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
