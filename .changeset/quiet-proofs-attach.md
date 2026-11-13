---
"mailbox": patch
---

Only file attachments are saved. Item attachments (attached emails) and reference attachments (cloud links) no longer throw `Expected contentBytes to be a string or StreamInterface` during `mailbox:setup` and the message jobs. `CreateMessage`, `UpdateMessage` and `SaveFolder` now wait on their promise, so errors fail the job instead of being swallowed. `Messages::find()` and `Folders::find()` resolve to `null` when Graph returns 404.
