---
"mailbox": patch
---

Fix PHP 8.5 compatibility and Laravel 13 support.

- Bump orchestra/testbench to ^10.0|^11.0 and orchestra/canvas to ^10.0|^11.0 to resolve PHP 8.5 PDO::MYSQL_ATTR_SSL_CA deprecation
- Add spatie/laravel-typescript-transformer ^3.0
- Add CI test matrix for PHP 8.4/8.5 and Laravel 12/13
- Apply Rector rules: scope methods to #[Scope] attribute, arrow function return types
- Fix PHPStan errors: type-safe date parsing in FollowupFlag cast
