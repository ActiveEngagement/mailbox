---
"actengage/mailbox": patch
---

Fix TypeError when attachment contentBytes is a Guzzle Stream instead of a string.

- Handle `Psr\Http\Message\StreamInterface` in `AttachmentService::createFromAttachment()` by converting to string before base64 decoding
