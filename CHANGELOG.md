# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-20

### Added

- Send regular, bulk, priority and OTP messages through the Semaphore v4 API.
- Automatic splitting of bulk sends over 1,000 recipients, with already-sent messages reported on failure.
- Read endpoints: messages (filtered and paginated), a single message, account, transactions, sender names and users.
- `semaphore` notification channel with `SemaphoreMessage` (priority, OTP, custom code, sender name).
- Typed DTOs, enums and value objects; exception family for auth, validation, rate limit and server errors.
- Read retries by default; opt-in send retries (`SEMAPHORE_RETRY_SENDS`, `Semaphore::retry()`).
- GSM-7/Unicode segment and credit counter.
- `Semaphore::fake()` with `assertSent`, `assertSentTo`, `assertSentCount`, `assertPrioritySent`, `assertOtpSent` and `assertNothingSent`.
