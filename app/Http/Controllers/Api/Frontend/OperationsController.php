<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Operation\Request\OperationRequestBuilder;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationsRequest;
use App\Services\ApiManager\Operations\DataHandler\ApiRequestDataInterface;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class OperationsController extends Controller
{

    public function __construct(
        protected OperationRequestBuilder $operationRequestBuilder,
    ) {
        parent::__construct();
    }

    public function searchOperation(string $type, ApiRequestDataInterface $apiRequestDataHandler, OperationsRequest $request)
    {
        $validatedData = $request->validated();

        $formRequestData =  $this->operationRequestBuilder
            ->setData($validatedData)
            ->build();
        ini_set('max_execution_time', 60);

        $provider = $validatedData['provider'] ?? [];
        $service = $validatedData['service'] ?? null;
        $apiRequestDataHandler->setUser($request->user())
            ->setRequestData($formRequestData);

        $results = $apiRequestDataHandler->searchOperation(
            $validatedData['api_fetch_type'],
            $type,
            $provider,
            $service
        );
        if (!$results) {
            return $this->sendErrorResponse(
                'No results found',
            );
        }

        ini_restore('max_execution_time');
        return $results;
    }
}
