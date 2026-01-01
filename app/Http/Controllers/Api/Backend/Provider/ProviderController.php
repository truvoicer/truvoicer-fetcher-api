<?php

namespace App\Http\Controllers\Api\Backend\Provider;

use App\Enums\Variable\VariableType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\CreateProviderRequest;
use App\Http\Requests\Provider\DeleteBatchProvidersRequest;
use App\Http\Requests\Provider\UpdateProviderRequest;
use App\Http\Resources\ProviderCollection;
use App\Http\Resources\ProviderResource;
use Truvoicer\TruFetcherGet\Models\Provider;
use Truvoicer\TruFetcherGet\Repositories\ProviderRepository;
use Truvoicer\TruFetcherGet\Services\Auth\AuthService;
use Truvoicer\TruFetcherGet\Services\Permission\AccessControlService;
use Truvoicer\TruFetcherGet\Services\Permission\PermissionService;
use App\Services\Tools\HttpRequestService;
use Truvoicer\TruFetcherGet\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\VariablesService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Contains api endpoint functions for provider related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class ProviderController extends Controller
{
    private ProviderRepository $providerRepository;

    /**
     * ProviderController constructor.
     * @param ProviderService $providerService
     */
    public function __construct(
       private ProviderService      $providerService
    )
    {
        parent::__construct();
        $this->providerRepository = $providerService->getProviderRepository();
    }

    /**
     * Gets a list of providers from the database based on the get request query parameters
     *
     */
    public function getProviderList(Request $request)
    {
        $user = $request->user();
        $pagination = $request->query->filter('pagination', true, FILTER_VALIDATE_BOOLEAN);

        $this->providerService->getProviderRepository()->setOrderDir($request->get('order', "asc"));
        $this->providerService->getProviderRepository()->setSortField($request->get('sort', "name"));
        $this->providerService->getProviderRepository()->setLimit($request->get('count', -1));
        $with = ['categories', 'providerRateLimit'];

        if ($request->query->getBoolean('show_srs', false)) {
            $with['srs'] = function ($query) use ($request) {
                    $query->whereDoesntHave('parentSrs');
                if ($request->query->getBoolean('show_nested_sr_children', false)) {
                    $query->with('childSrs');
                }
            };
        }
        $this->providerService->getProviderRepository()->setWith($with);
        return $this->sendSuccessResponse(
            "success",
            new ProviderCollection(
                $this->providerService->findProviders($user)
            )
        );
    }

    /**
     * Gets a single provider from the database based on the id in the get request url
     *
     */
    public function getProvider(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());

        return $this->sendSuccessResponse(
            "success",
            new ProviderResource($provider->load(['categories']))
        );
    }

    /**
     * Get list of provider variables
     * Returns a list of provider variables based on the request query parameters
     *
     */
    public function variables(
        Provider $provider,
        VariablesService $variablesService
    ) {
        return $this->sendSuccessResponse(
            "success",
            $variablesService->getVariables(VariableType::PROVIDER)
                ->getVariableList($provider)
        );
    }

    /**
     * Creates a provider in the database based on the post request data
     *
     */
    public function createProvider(CreateProviderRequest $request): \Illuminate\Http\JsonResponse
    {
        $name = $request->validated('name');
        $user = $request->user();
        $checkProvider = $this->providerRepository->findUserModelBy(new Provider(), $user, [
            ['name', '=', $name]
        ], false);

        if ($checkProvider instanceof Provider) {
            throw new BadRequestHttpException(sprintf("Provider (%s) already exists.", $name));
        }

        $createProvider = $this->providerService->createProvider(
            $request->user(),
            $request->validated()
        );

        if (!$createProvider) {
            return $this->sendErrorResponse("Error inserting provider");
        }
        return $this->sendSuccessResponse(
            "Provider added",
            new ProviderResource($this->providerService->getProviderRepository()->getModel())
        );
    }


    /**
     * Updates a provider in the database based on the post request data
     *
     */
    public function updateProvider(Provider $provider, UpdateProviderRequest $request): \Illuminate\Http\JsonResponse
    {
        $updateProvider = $this->providerService->updateProvider(
            $request->user(),
            $provider,
            $request->validated()
        );

        if (!$updateProvider) {
            return $this->sendErrorResponse("Error updating provider");
        }
        return $this->sendSuccessResponse(
            "Provider updated",
            new ProviderResource($this->providerService->getProviderRepository()->getModel())
        );
    }

    /**
     * Deletes a provider in the database based on the post request data
     *
     */
    public function deleteProvider(Provider $provider, Request $request): \Illuminate\Http\JsonResponse
    {
        $delete = $this->providerService->deleteProvider($provider);
        if (!$delete) {
            return $this->sendErrorResponse(
                "Error deleting provider"
            );
        }
        return $this->sendSuccessResponse(
            "Provider deleted."
        );
    }

    public function deleteBatchProviders(
        Provider                    $provider,
        DeleteBatchProvidersRequest $request
    ): \Illuminate\Http\JsonResponse
    {
        $this->setAccessControlUser($request->user());

        if (!$this->providerService->deleteBatchProvider($request->get('ids'))) {
            return $this->sendErrorResponse(
                "Error deleting providers",
            );
        }
        return $this->sendSuccessResponse(
            "Providers deleted.",
        );
    }
}
