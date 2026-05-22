---
"mailbox": minor
---

Allow mailbox models and migrations to run on a non-default database connection via the new `mailbox.database_connection` config (`MAILBOX_DB_CONNECTION` env). Defaults to `null`, which uses the application's default connection — backwards compatible with existing installs.
