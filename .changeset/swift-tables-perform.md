---
"mailbox": minor
---

Add performance indexes to mailbox tables: `message_id` index on `mailbox_message_attachments` and composite `(mailbox_id, folder_id, conversation_id, is_draft)` index on `mailbox_messages`
