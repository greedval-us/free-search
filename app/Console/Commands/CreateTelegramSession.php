<?php

namespace App\Console\Commands;

use App\Facades\MadelineProto;
use Illuminate\Console\Command;

class CreateTelegramSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-telegram-session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $madelineProto = $this->getMadelineProto();

        $this->info('Starting Telegram authentication process...');

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

            $this->info('Successfully logged in! Session file created.');

        } catch (\Exception $e) {
            $this->error('Authentication failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function getMadelineProto()
    {
        return MadelineProto::getFacadeRoot();
    }
}
