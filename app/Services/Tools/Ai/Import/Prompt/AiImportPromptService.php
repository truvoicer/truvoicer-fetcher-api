<?php

namespace App\Services\Tools\Ai\Import\Prompt;

use App\Models\AiImportPrompt;
use App\Repositories\AiImportPromptRepository;
use RuntimeException;
use Truvoicer\TfDbReadCore\Traits\User\UserTrait;

class AiImportPromptService
{
    use UserTrait;


    public function __construct(
        private AiImportPromptRepository $aiImportPromptRepository,
    ) {}

    public function createAiImportPrompt(array $data): bool
    {
        return $this->aiImportPromptRepository->createImportPrompt(
            $this->user, $data
        );
    }

    public function updateAiImportPrompt(AiImportPrompt $aiImportPrompt, array $data): bool
    {
        return $this->aiImportPromptRepository->updateImportPrompt($aiImportPrompt, $data);
    }

    public function deleteAiImportPrompt(AiImportPrompt $aiImportPrompt): bool
    {
        return $this->aiImportPromptRepository->setModel($aiImportPrompt)->delete();
    }

    public function deleteBulkAiImportPrompts(array $ids): bool
    {
        return $this->aiImportPromptRepository->deleteBatch($ids);
    }

}
