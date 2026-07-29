<?php

namespace App\Console\Commands;

use App\Facades\MadelineProto;
use App\Support\MadelineProto\MadelineProtoManager;
use Illuminate\Console\Command;

class CreateTelegramSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-telegram-session {name=default : Session name to create or refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or refresh a named Telegram MadelineProto session';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sessionName = (string) $this->argument('name');
        $madelineProto = $this->getMadelineProto()->client($sessionName);

        $this->info(sprintf('Starting Telegram authentication process for session "%s"...', $sessionName));

        try {
            $phoneNumber = $this->ask('Enter your phone number (in international format, e.g. +1234567890):');
            $madelineProto->phoneLogin($phoneNumber);

            $phoneCode = $this->ask('Enter the verification code you received:');
            $authorization = $madelineProto->completePhoneLogin($phoneCode);

            if ($authorization['_'] === 'account.password') {
                $password = $this->secret("Please enter your password (hint: {$authorization['hint']}):");
                $authorization = $madelineProto->complete2falogin($password);
            }

            if ($authorization['_'] === 'account.needSignup') {
                $firstName = $this->ask('Please enter your first name:');
                $lastName = $this->ask('Please enter your last name (can be empty):', '');
                $authorization = $madelineProto->completeSignup($firstName, $lastName);
            }

            $availableSessions = $this->getMadelineProto()->availableSessionNames();

            $this->info(sprintf('Successfully logged in. Session "%s" is ready.', $sessionName));
            $this->line('Active Telegram sessions: ' . implode(', ', $availableSessions));

        } catch (\Exception $e) {
            $this->error('Authentication failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function getMadelineProto(): MadelineProtoManager
    {
        return MadelineProto::getFacadeRoot();
    }
}
