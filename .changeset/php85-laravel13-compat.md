---
"mailbox": patch
---

Fix PHP 8.5 and Laravel 13 compatibility.

- Bump orchestra/testbench to ^10.0|^11.0 and orchestra/canvas to ^10.0|^11.0 to resolve PHP 8.5 PDO::MYSQL_ATTR_SSL_CA deprecation
- Bump pestphp/pest to ^4.0 and pestphp/pest-plugin-laravel to ^4.0 for Laravel 13 support
- Add spatie/laravel-typescript-transformer ^3.0
- Use sharafat/laravel-nestedset fork for Laravel 13 illuminate support
- Override usesSoftDelete on MailboxFolder to prevent re-entrant boot on Laravel 13
- Revert name scope to scopeName on MailboxMessageAttachment to avoid Laravel 13 attribute conflict
- Fix PHPStan errors: type-safe date parsing in FollowupFlag cast
- Add CI test matrix for PHP 8.4/8.5 and Laravel 12/13
