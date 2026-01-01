<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperationsRequest;
use Truvoicer\TruFetcherGet\Services\ApiManager\Operations\DataHandler\ApiRequestDataInterface;

/**
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class OperationsController extends Controller
{

    public function searchOperation(string $type, ApiRequestDataInterface $apiRequestDataHandler, OperationsRequest $request)
    {
        $validatedData = $request->validated();

        ini_set('max_execution_time', 60);

        $provider = $validatedData['provider'] ?? [];
        $service = $validatedData['service'] ?? null;
        $apiRequestDataHandler->setUser($request->user())
            ->setRequestData($validatedData);

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
