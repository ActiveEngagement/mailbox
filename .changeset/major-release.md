---
"mailbox": major
---

Major release with 100% test coverage, Laravel 12 support, and comprehensive codebase improvements.

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
