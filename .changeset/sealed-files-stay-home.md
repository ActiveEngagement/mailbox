---
"mailbox": major
---

Remove the `url` attribute from `MailboxMessageAttachment`. The model no longer appends a public storage URL to its array and JSON output, and the `url` accessor is gone. Serve attachment files through an authenticated route that streams them from storage instead.
