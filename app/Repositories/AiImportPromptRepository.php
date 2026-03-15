<?php

namespace App\Repositories;

use App\Models\AiImportPrompt;
use Truvoicer\TfDbReadCore\Models\User;
use Truvoicer\TfDbReadCore\Repositories\BaseRepository;

class AiImportPromptRepository extends BaseRepository
{

    public array $searchFields = [
        'prompt',
        'created_at',
        'updated_at',
    ];

    public function __construct()
    {
        parent::__construct(AiImportPrompt::class);
    }

    public function findByParams(string $sort, string $order, ?int $count = null)
    {
        return $this->findAllWithParams($sort, $order, $count);
    }


    public function getModel(): AiImportPrompt
    {
        return parent::getModel();
    }

    public function createImportPrompt(User $user, array $data): bool
    {
        $importPrompt = $this->getModel()->fill([
            ...$data,
            'user_id' => $user->id,
        ]);
        return  $importPrompt->save();
    }

    public function updateImportPrompt(AiImportPrompt $aiImportPrompt, array $data): bool
    {
        $this->setModel($aiImportPrompt);
        return $this->save($data);
    }
}
