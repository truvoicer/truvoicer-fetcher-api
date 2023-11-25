<?php

namespace App\Console\Commands;

use App\Services\User\UserAdminService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(UserAdminService $userAdminService)
    {
        $userData = [];

        $userData['email'] = $this->ask('What is your email?');
        $userData['password'] = $this->ask('What is your password?');
        $userData['password_confirmation'] = $this->ask('Confirm password?');
        if ($userData['password'] !== $userData['password_confirmation']) {
            $this->error('Passwords don\'t match');
            return CommandAlias::FAILURE;
        }
        if ($userAdminService->createUser($userData)) {
            $this->error('User created!');
            return CommandAlias::SUCCESS;
        }
        $this->error('Error creating user');
        return CommandAlias::FAILURE;
    }
}
