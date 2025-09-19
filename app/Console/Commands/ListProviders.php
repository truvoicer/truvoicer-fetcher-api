<?php

namespace App\Console\Commands;

use App\Repositories\ProviderRepository;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ListProviders extends Command
{
    private ProviderRepository $providerRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Providers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        ProviderRepository $providerRepository
    ) {

        $this->providerRepository = $providerRepository;
        $providers = $this->providerRepository->findMany();
        //Display providers in table format
        $this->table(
            ['ID', 'Name', 'Label', 'Created At', 'Updated At'],
            $providers->map(function ($provider) {
                return [
                    'ID' => $provider->id,
                    'Name' => $provider->name,
                    'Label' => $provider->label,
                    'Created At' => $provider->created_at,
                    'Updated At' => $provider->updated_at,
                ];
            })->toArray()
        );

        return CommandAlias::SUCCESS;
    }
}
