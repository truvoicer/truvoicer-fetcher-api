<?php

namespace App\Console\Commands;

use App\Http\Requests\Admin\User\CreateUserRequest;
use App\Models\Provider;
use App\Models\Role;
use App\Helpers\Tools\UtilHelpers;
use App\Models\Sr;
use App\Services\Provider\ProviderEventsService;
use App\Services\Provider\ProviderService;
use App\Services\Task\ScheduleService;
use App\Services\User\RoleService;
use App\Services\User\UserAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ProviderSrSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run provider sr schedules';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ProviderEventsService $providerService)
    {
        $provider = Provider::where('name', '=', 'reed')->first();
        $providerService->runSrOperationsByInterval($provider, ScheduleService::SCHEDULE_EVERY_DAY);
        return CommandAlias::SUCCESS;
    }
}
