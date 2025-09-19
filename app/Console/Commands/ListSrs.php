<?php

namespace App\Console\Commands;

use App\Repositories\SrRepository;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ListSrs extends Command
{
    private SrRepository $srRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:srs {--provider=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List SRs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        SrRepository $srRepository
    ) {
        $this->srRepository = $srRepository;

        $provider = $this->option('provider');
        if (empty($provider)) {
            $srs = $this->srRepository->findAll();
        } else {
            $providerIds = array_map('intval', array_filter(
                array_map('trim', explode(',', $provider)),
                function ($value) {
                    return !empty($value) && is_numeric($value);
                }
            ));
            $providerNames = array_map('strval', array_filter(
                array_map('trim', explode(',', $provider)),
                function ($value) {
                    return !empty($value) && !is_numeric($value);
                }
            ));
            $this->srRepository->addWhere(
                'provider_id',
                $providerIds,
                'in'
            );
            if (count($providerNames)) {
                $this->srRepository->addWhere(
                    'provider_name',
                    $providerNames,
                    'in',
                    'OR'
                );
            }
        }
        $srs = $this->srRepository->findMany();
        //Display srs in table format
        $this->table(
            ['ID', 'Name', 'Type', 'Provider', 'Active', 'Created At', 'Updated At'],
            $srs->map(function ($sr) {
                return [
                    'ID' => $sr->id,
                    'Name' => $sr->name,
                    'Type' => $sr->type,
                    'Provider' => $sr->provider->name,
                    'Active' => $sr->active ? 'Yes' : 'No',
                    'Created At' => $sr->created_at,
                    'Updated At' => $sr->updated_at,
                ];
            })->toArray()
        );

        return CommandAlias::SUCCESS;
    }
}
