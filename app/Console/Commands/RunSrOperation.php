<?php

namespace App\Console\Commands;

use Truvoicer\TruFetcherGet\Models\Provider;
use App\Models\User;
use Truvoicer\TruFetcherGet\Repositories\SrResponseKeySrRepository;
use App\Services\ApiServices\ServiceRequests\SrOperationsService;
use App\Services\Provider\ProviderEventService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RunSrOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sr:op:run {--type=} {--provider_name=} {--sr_name=} {--email=}';

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
        $type = $this->option('type');
        $providerName = $this->option('provider_name');
        $srName = $this->option('sr_name');
        $email = $this->option('email');
        if (empty($type) || empty($providerName) || empty($srName) || empty($email)){
            $this->error('Missing required arguments');
            return CommandAlias::FAILURE;
        }
        $user = User::where('email', '=', $email)->first();
        if (!$user) {
            $this->error('User not found');
            return CommandAlias::FAILURE;
        }
        $provider = Provider::where('name', '=', $providerName)->first();
        if (!$provider) {
            $this->error('Provider not found');
            return CommandAlias::FAILURE;
        }
        $sr = $provider->sr()->where('name', '=', $srName)->first();
        if (!$sr) {
            $this->error('Sr not found');
            return CommandAlias::FAILURE;
        }

        if ($type === 'queue') {
            $providerEventService->dispatchSrOperationEvent($user, $sr);
            return CommandAlias::SUCCESS;
        }
        $srOperationsService->setUser($user);
        $srOperationsService->getRequestOperation()->setProvider($provider);
        $srOperationsService->runOperationForSr(
            $sr,
            SrResponseKeySrRepository::ACTION_STORE
        );
        return CommandAlias::SUCCESS;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'type' => 'Event type? (queue/event)',
            'provider_name' => 'Enter provider name',
            'sr_name' => 'Enter service request name',
        ];
    }
}
