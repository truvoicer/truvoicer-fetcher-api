<?php

namespace App\Http\Controllers\Api\Backend\Tools\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Ai\Import\Prompt\AiImportPromptStoreRequest;
use App\Http\Requests\Admin\Tools\Ai\Import\Prompt\AiImportPromptUpdateRequest;
use App\Http\Resources\AiImportPromptCollection;
use App\Http\Resources\AiImportPromptResource;
use App\Models\AiImportPrompt;
use App\Repositories\AiImportPromptRepository;
use App\Services\Tools\Ai\Import\Prompt\AiImportPromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiImportPromptController extends Controller
{
    public function __construct(
        private AiImportPromptService $aiImportPromptService,
        private AiImportPromptRepository $aiImportPromptRepository
    ) {
        return parent::__construct();
    }

    public function index(Request $request): AiImportPromptCollection
    {
        $user = request()->user();
        $this->accessControlService->setUser($user);

        return new AiImportPromptCollection(
            $this->aiImportPromptRepository
                ->setQuery(
                    ($this->accessControlService->inAdminGroup())
                        ? AiImportPrompt::query()
                        : $user->aiImportPrompts()
                )
                ->applyIndexRequest($request, $this->aiImportPromptRepository->searchFields)->paginate()
        );
    }

    public function store(AiImportPromptStoreRequest $request): AiImportPromptResource|JsonResponse
    {
        if (
            !$this->aiImportPromptService->setUser($request->user())
                ->createAiImportPrompt($request->validated())
        ) {
            return $this->sendErrorResponse(
                "Error storing ai import prompt.",
            );
        }
        return $this->sendSuccessResponse(
            "Successfully updated ai import prompt.",
        );
    }
    public function update(AiImportPrompt $aiImportPrompt, AiImportPromptUpdateRequest $request): AiImportPromptResource|JsonResponse
    {
        if (
            !$this->aiImportPromptService
                ->setUser($request->user())
                ->updateAiImportPrompt(
                    $aiImportPrompt,
                    $request->validated()
                )
        ) {
            return $this->sendErrorResponse(
                "Error updating ai import prompt.",
            );
        }
        return $this->sendSuccessResponse(
            "Successfully updated ai import prompt.",
        );
    }

    public function destroy(AiImportPrompt $aiImportPrompt): AiImportPromptResource|JsonResponse
    {
        $aiImportPrompt = $this->aiImportPromptService->setUser(request()->user())
            ->deleteAiImportPrompt($aiImportPrompt);
        if (!$aiImportPrompt) {
            return $this->sendErrorResponse(
                "Error deleting ai import prompt.",
            );
        }
        return $this->sendSuccessResponse(
            "Successfully deleted ai import prompt.",
        );
    }

    public function bulkDestroy(Request $request): AiImportPromptResource|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:ai_import_prompts,id'],
        ]);
        $validated = $validator->validate();

        $aiImportPrompt = $this->aiImportPromptService->setUser(request()->user())
            ->deleteBulkAiImportPrompts(
                (!empty($validated['ids']) && is_array($validated['ids'])) ? $validated['ids'] : []
            );
        if (!$aiImportPrompt) {
            return $this->sendErrorResponse(
                "Error deleting ai import prompts.",
            );
        }
        return $this->sendSuccessResponse(
            "Successfully deleted ai import prompts.",
        );
    }

}
