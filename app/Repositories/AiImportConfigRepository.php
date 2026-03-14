<?php

namespace App\Repositories;

use App\Models\AiImportConfig;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Repositories\BaseRepository;

class AiImportConfigRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(AiImportConfig::class);
    }

    public function findByParams(string $sort, string $order, ?int $count = null)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }


    public function getModel(): AiImportConfig
    {
        return parent::getModel();
    }

    public function createImportConfig(User $user, array $data): bool
    {
        $importConfig = $this->getModel()->fill([
            ...$data,
            'user_id' => $user->id,
        ]);
        return  $importConfig->save();
    }

    public function updateImportConfig(AiImportConfig $aiImportConfig, array $data): bool
    {
        $this->setModel($aiImportConfig);
        return $this->save($data);
    }
}
