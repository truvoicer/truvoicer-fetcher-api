<?php

namespace App\Console\Commands;

use Truvoicer\TfDbReadCore\Models\Provider;
use Truvoicer\TfDbReadCore\Models\User;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Provider\ProviderEventService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RunProviderSrOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:op:run {--type=} {--provider_name=} {--email=}';

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
    public function handle(SrOperationsService $srOperationsService, ProviderEventService $providerEventService)
    {
//        $type = $this->ask('Event type? (queue/event)');
//        $interval = $this->ask(
//            sprintf(
//                'Interval? (%s)',
//                implode(', ', array_keys(ScheduleService::SCHEDULE_INTERVALS))
//            )
//        );
//        $providerName = $this->ask('Enter provider name');
        $type = $this->option('type');
        $providerName = $this->option('provider_name');

        $email = $this->option('email');
        if (empty($type) || empty($providerName) || empty($email)){
            $this->error('Missing required arguments');
            return CommandAlias::FAILURE;
        }
        $user = User::where('email', '=', $email)->first();
        if (!$user) {
            $this->error('User not found');
            return CommandAlias::FAILURE;
        }
        $interval = 'every_minute';

        $provider = Provider::where('name', '=', $providerName)->first();
        if (!$provider) {
            $this->error('Provider not found');
            return CommandAlias::FAILURE;
        }
        if ($type === 'queue') {
            $providerEventService->dispatchProviderSrOperationEvent($user, $provider, $interval, true);
            return CommandAlias::SUCCESS;
        }
        $srOperationsService->setUser($user);
        $srOperationsService->runSrOperationsByInterval($provider, $interval, true);
        return CommandAlias::SUCCESS;
    }
}
