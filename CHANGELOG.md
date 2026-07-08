# Changelog — waffle-commons/utils

All notable changes to this component are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Released in lockstep with the Waffle Commons umbrella tag.

## [0.1.0-beta5] — 2026-07-08

**Theme: static-analysis hardening.**

### Changed
- Enabled the Mago `cyclomatic-complexity` lint with a `threshold` of 50, ratcheting in a complexity ceiling for this component (config-only — no source behaviour change).

## [0.1.0-beta4] — 2026-06-13

**Theme: security hardening & developer experience.**

### Added
- `Validation\AssertValidator` + `Validation\ValidationResult` + `Validation\Violation` — an injectable, mockable `ValidatorInterface` wrapping the static `Assert` facade (DX-05).

### Changed
- Migrated user-input normalisation from `trim()` to native `mb_trim()` across the `Assert` traits, correctly stripping multi-byte whitespace (DX-04).
- Worker-safety migration to igor-php 0.7 (`#[WorkerSafe]`).

## [0.1.0-beta3] — 2026-06-07

**Theme: identity federation & stateless persistence (ecosystem wave).**

### Added
- `Assert` validation surface split into focused trait families — file, network, numeric and string assertions — keeping the facade lean while widening coverage.

### Changed
- Lockstep version bump; `composer.lock` refreshed with the beta-3 dependency wave.

## [0.1.0-beta2.1] — 2026-05-30

### Changed
- Lockstep re-tag of `0.1.0-beta2` (umbrella housekeeping patch) — no source changes in this component.

## [0.1.0-beta2] — 2026-05-29

### Changed
- Lockstep version bump only. No behavioural changes since `0.1.0-beta1`.
- `composer.lock` refreshed to align with the ecosystem-wide dependency wave.

## [0.1.0-beta1]

See the umbrella [CHANGELOG](../CHANGELOG.md#010-beta1) for the full Beta-1 narrative — `ReflectionTrait` decomposed into single-responsibility `final readonly` services (`ClassParser`, `AttributeReader`, `ReflectionInspector`).
