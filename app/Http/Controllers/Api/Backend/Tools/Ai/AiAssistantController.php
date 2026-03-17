<?php

namespace App\Http\Controllers\Api\Backend\Tools\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tools\Ai\AiAssistantStoreRequest;
use App\Http\Requests\Admin\Tools\Ai\AiAssistantUpdateRequest;
use App\Http\Resources\AiImportConfigCollection;
use App\Http\Resources\AiImportConfigResource;
use App\Repositories\AiImportConfigRepository;
use App\Services\Tools\Ai\Assistant\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Truvoicer\TfDbReadCore\Models\AiImportConfig;

class AiAssistantController extends Controller
{
    public function __construct(
        private AiAssistantService $aiAssistantService,
        private AiImportConfigRepository $aiImportConfigRepository
    ) {
        parent::__construct();
    }

    public function index(Request $request): AiImportConfigCollection
    {
        $user = request()->user();
        $this->accessControlService->setUser($user);

        return new AiImportConfigCollection(
            $this->aiImportConfigRepository
                ->setQuery(
                    ($this->accessControlService->inAdminGroup())
                        ? AiImportConfig::query()
                        : $user->aiImportConfigs()
                )
                ->applyIndexRequest($request, $this->aiImportConfigRepository->searchFields)
                ->paginate()
        );
    }

    public function store(AiAssistantStoreRequest $request): AiImportConfigResource|JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser($request->user())
            ->build(
                $request->validated('prompt')
            );
        if (! $aiImportConfig) {
            return $this->sendErrorResponse(
                'Error fetching and storing ai import config.',
            );
        }

        return AiImportConfigResource::make($aiImportConfig);
    }

    public function update(AiImportConfig $aiImportConfig, AiAssistantUpdateRequest $request): AiImportConfigResource|JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser($request->user())
            ->updateAiImportConfig(
                $aiImportConfig,
                $request->validated()
            );
        if (! $aiImportConfig) {
            return $this->sendErrorResponse(
                'Error updating ai import config.',
            );
        }

        return $this->sendSuccessResponse(
            'Successfully updated ai import config.',
        );
    }

    public function destroy(AiImportConfig $aiImportConfig): AiImportConfigResource|JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser(request()->user())
            ->deleteAiImportConfig($aiImportConfig);
        if (! $aiImportConfig) {
            return $this->sendErrorResponse(
                'Error deleting ai import config.',
            );
        }

        return $this->sendSuccessResponse(
            'Successfully deleted ai import config.',
        );
    }

    public function bulkDestroy(Request $request): AiImportConfigResource|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:ai_import_configs,id'],
        ]);
        $validated = $validator->validate();

        $aiImportConfig = $this->aiAssistantService->setUser(request()->user())
            ->deleteBulkAiImportConfigs(
                (! empty($validated['ids']) && is_array($validated['ids'])) ? $validated['ids'] : []
            );
        if (! $aiImportConfig) {
            return $this->sendErrorResponse(
                'Error deleting ai import configs.',
            );
        }

        return $this->sendSuccessResponse(
            'Successfully deleted ai import configs.',
        );
    }

    public function bulkImport(Request $request): AiImportConfigResource|JsonResponse
    {
        $user = request()->user();
        $this->accessControlService->setUser($user);

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:ai_import_configs,id'],
        ]);
        $validated = $validator->validate();

        foreach (
            (! empty($validated['ids']) && is_array($validated['ids'])) ? $validated['ids'] : [] as $id
        ) {

            if ($this->accessControlService->inAdminGroup()) {
                /** @var \Truvoicer\TfDbReadCore\Models\AiImportConfig|null $findAiImportConfig */
                $findAiImportConfig = AiImportConfig::where('id', $id)->first();
            } else {
                /** @var \Truvoicer\TfDbReadCore\Models\AiImportConfig|null $findAiImportConfig */
                $findAiImportConfig = $user->aiImportConfigs()->where('ai_import_configs.id', $id)->first();
            }
            if (! $findAiImportConfig) {
                continue;
            }
            $this->aiAssistantService->setUser(request()->user())
                ->makeImport($findAiImportConfig);
        }

        return $this->sendSuccessResponse(
            'Successfully imported ai import configs.',
        );
    }

    public function import(AiImportConfig $aiImportConfig): JsonResponse
    {
        $aiImportConfig = $this->aiAssistantService->setUser(request()->user())
            ->makeImport($aiImportConfig);

        return $this->sendSuccessResponse(
            'Successfully imported'
        );
    }
}
