---
"mailbox": patch
---

`CreateMessage` and `UpdateMessage` no longer race on the same message. Graph sends `created` and `updated` notifications at almost the same time, so both jobs inserted the message and the second failed on `mailbox_messages_hash_unique` or `mailbox_message_attachments_path_unique`. Both jobs now share a `WithoutOverlapping` lock per mailbox and message. A job that finds the lock taken is released for 10 seconds, with up to 10 tries and 1 exception.
