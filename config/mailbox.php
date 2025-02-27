<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | This value is a string that refers to the name of the connection that is
    | used as the default. This connection will be used if no connection is
    | specified when making API calls with the Mailbox services provider.
    | 
    */

    'default' => env('MAILBOX_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Available Connections
    |--------------------------------------------------------------------------
    |
    | This value is an array of OAuth connection details that are created in
    | Azure. Each secret must contain at a minimum the following scopes:
    | Mail.Read, Mail.ReadWrite, Mail.Send, MailboxFolder.ReadWrite.All
    | 
    */

    'connections' => [
        'default' => [
            'tenant_id' => env('MAILBOX_TENANT_ID'),
            'client_id' => env('MAILBOX_CLIENT_ID'),
            'client_secret' => env('MAILBOX_CLIENT_SECRET'),
            'scopes' => explode(',', env('MAILBOX_SCOPES', 'https://graph.microsoft.com/.default')),
            'webhook_host' => env('MAILBOX_WEBHOOK_HOST', config('app.url')),
            'storage_disk' => env('MAILBOX_STORAGE_DISK', 'public')
        ]
    ]
];