<?php

namespace App\Http\Controllers\Api\Backend\Tools\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Ai\AiAssistantStoreRequest;
use App\Http\Resources\AiImportConfigResource;
use App\Models\AiImportConfig;
use App\Services\Tools\Ai\Assistant\AiAssistantService;
use Illuminate\Http\JsonResponse;

class AiAssistantController extends Controller
{
    public function __construct(
        private AiAssistantService $aiAssistantService
    ) {
        return parent::__construct();
    }

    public function store(AiAssistantStoreRequest $request): AiImportConfigResource|JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser($request->user())
            ->build(
                $request->validated('prompt')
            );
        if (!$aiImportConfig) {
            return $this->sendErrorResponse(
                "Error fetching and storing ai import config.",
            );
        }
        return AiImportConfigResource::make($aiImportConfig);
    }

    public function import(AiImportConfig $aiImportConfig): JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser(request()->user())
            ->makeImport($aiImportConfig);
        if (!$aiImportConfig) {
            return $this->sendErrorResponse(
                "Error importing.",
            );
        }
        return $this->sendSuccessResponse(
            'Successfully imported'
        );
    }
}
