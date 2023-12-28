<?php

namespace App\Console\Commands;

use App\Http\Requests\Admin\User\CreateUserRequest;
use App\Models\Role;
use App\Helpers\Tools\UtilHelpers;
use App\Services\User\RoleService;
use App\Services\User\UserAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
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
    public function handle(UserAdminService $userAdminService, RoleService $roleService)
    {
        $fetchRoles = $roleService->getRoleRepository()->findAll();
        $rolesArray = $fetchRoles->toArray();
        $userData = [];

        $roleName = $this->ask(
            sprintf("Enter role name (%s)",
                implode(', ', array_map(function ($role) {
                    return $role['name'];
                }, $rolesArray)),
            )
        );
        $role = $fetchRoles->where('name', '=', $roleName)->first();
        if (!$role instanceof Role) {
            $this->error('Invalid role');
            return CommandAlias::FAILURE;
        }
        $userData['role_id'] = $role->id;
        $userData['email'] = $this->ask('Enter email');
        $userData['password'] = $this->ask('Enter password');
        $userData['password_confirmation'] = $this->ask('Confirm password');
        $validator = Validator::make($userData, (new CreateUserRequest())->rules());

        if ($validator->fails()) {
            $this->output->error($validator->messages()->toJson());
            return CommandAlias::FAILURE;
        }
        unset($userData['role_id']);
        if ($userAdminService->createUserByRoleId($userData, $role->id)) {
            $this->info('User created!');
            return CommandAlias::SUCCESS;
        }
        $this->error('Error creating user');
        return CommandAlias::FAILURE;
    }
}
