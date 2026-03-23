# [1.0.0-beta.10](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.9...v1.0.0-beta.10) (2024-12-19)

## 1.0.1

### Patch Changes

- [#4](https://github.com/ActiveEngagement/mailbox/pull/4) [`1e21936`](https://github.com/ActiveEngagement/mailbox/commit/1e219360dda3cb30ab83773b2de4f11f5c5d5001) Thanks [@actengage](https://github.com/actengage)! - Fix PHP 8.5 compatibility and Laravel 13 support.

  - Bump orchestra/testbench to ^10.0|^11.0 and orchestra/canvas to ^10.0|^11.0 to resolve PHP 8.5 PDO::MYSQL_ATTR_SSL_CA deprecation
  - Add spatie/laravel-typescript-transformer ^3.0
  - Add CI test matrix for PHP 8.4/8.5 and Laravel 12/13
  - Apply Rector rules: scope methods to #[Scope] attribute, arrow function return types
  - Fix PHPStan errors: type-safe date parsing in FollowupFlag cast

## 1.0.0

### Major Changes

- [#1](https://github.com/ActiveEngagement/mailbox/pull/1) [`e104fea`](https://github.com/ActiveEngagement/mailbox/commit/e104fea9bd7249646467b61752d7805c7511a0dd) Thanks [@actengage](https://github.com/actengage)! - Major release with 100% test coverage, Laravel 12 support, and comprehensive codebase improvements.

  ### Breaking Changes

  - Refactored all service classes (AttachmentService, ClientService, FolderService, MessageService, ModelService, SubscriptionService) with updated method signatures and return types
  - Refactored all model classes with updated casts, relationships, and attribute definitions
  - Refactored all event classes with updated constructor signatures
  - Refactored all facade classes with updated accessor methods
  - Updated console commands with revised argument handling and return types
  - Refactored Cast classes (Body, ExternalId, FollowupFlag, Importance, Recipient, Recipients)
  - Updated Data classes (Conditional, EmailAddress, Filter, FollowupFlag) with revised interfaces
  - Updated Enum classes with revised backing values

  ### Features

  - Added Laravel 12 and 13 framework support
  - Added composite indexes migration for mailbox messages
  - Added PHPStan static analysis configuration
  - Added Rector for automated code quality

  ### Improvements

  - Achieved 100% code coverage across all source files
  - Replaced semantic-release with changesets for version management
  - Replaced Husky/commitlint with new CI/CD workflows
  - Updated all database migrations with improved column definitions
  - Updated webhook controllers and middleware
  - Updated job classes with improved error handling

### Bug Fixes

- fix attribute casting for meta and data_variables on send model ([761b5ab](https://github.com/ActiveEngagement/casey-jones-client/commit/761b5ab2d4824ea8bb5d7095281054ca26a24844))

# [1.0.0-beta.9](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.8...v1.0.0-beta.9) (2024-10-21)

### Bug Fixes

- updated form_params to json ([8b15e53](https://github.com/ActiveEngagement/casey-jones-client/commit/8b15e535f4dcb4282b28e45932b0527215b59503))

# [1.0.0-beta.8](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.7...v1.0.0-beta.8) (2024-10-21)

### Bug Fixes

- added send cancelled event ([e6df330](https://github.com/ActiveEngagement/casey-jones-client/commit/e6df330142a6fc708a5877fb8633e47ead76f2e9))

# [1.0.0-beta.7](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.6...v1.0.0-beta.7) (2024-10-21)

### Bug Fixes

- added SendScheduled event ([ee2c7bc](https://github.com/ActiveEngagement/casey-jones-client/commit/ee2c7bcca1df7360b0262660119d9bac15c363f8))

# [1.0.0-beta.6](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.5...v1.0.0-beta.6) (2024-10-16)

### Bug Fixes

- updated data types ([f43985b](https://github.com/ActiveEngagement/casey-jones-client/commit/f43985bdb6d5868a7bbec92919b39cba0243572c))

# [1.0.0-beta.5](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.4...v1.0.0-beta.5) (2024-10-15)

### Bug Fixes

- fixed issue with send resources using ids and send data requiring too much data ([b31d5a5](https://github.com/ActiveEngagement/casey-jones-client/commit/b31d5a596ebab0d177db114c92a83245f4cde16c))

# [1.0.0-beta.4](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.3...v1.0.0-beta.4) (2024-10-15)

### Bug Fixes

- improved api consistency with better resources and return types ([14e213f](https://github.com/ActiveEngagement/casey-jones-client/commit/14e213f299150010d5c0c6b87c303a50b2a9332c))

# [1.0.0-beta.3](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.2...v1.0.0-beta.3) (2024-10-14)

### Bug Fixes

- updated typescript definitions and default attribute value for data_variables ([8ea78dd](https://github.com/ActiveEngagement/casey-jones-client/commit/8ea78dd16a8161ffdf599d31019c48c67089746e))

# [1.0.0-beta.2](https://github.com/ActiveEngagement/casey-jones-client/compare/v1.0.0-beta.1...v1.0.0-beta.2) (2024-10-14)

### Bug Fixes

- updated typescript transform definitions ([6d4f917](https://github.com/ActiveEngagement/casey-jones-client/commit/6d4f917a780b25c8f3b7484eac0fff4a9d324b77))

# 1.0.0-beta.1 (2024-10-11)

### Features

- initial beta release ([53e90b8](https://github.com/ActiveEngagement/casey-jones-client/commit/53e90b892106e3709dca717603cfa8d987c55197))
