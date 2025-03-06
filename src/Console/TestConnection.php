<?php

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Facades\GraphServiceClient;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'mailbox:test')]
class TestConnection extends Command implements PromptsForMissingInput
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $command = 'mailbox:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Microsoft Graph API connection.';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        $client = GraphServiceClient::make($this->argument('connection'));
        
        dd($client->getRequestAdapter()->getBaseUrl()->);

        $messages = $client->users()
            ->byUserId('creativetest@actengage.net')
            ->mailFolders()
            ->byMailFolderId('inbox')
            ->messages()
            ->get()
            ->wait();

            dd(123);
    }

    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of the connection to test.', 'default'],
        ];
    }
}