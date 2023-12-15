<?php

namespace App\Http\Controllers\Api\Backend\Scrapers;

use App\Http\Controllers\Controller;
use App\Entity\ScraperConfig;
use App\Entity\Scraper;
use App\Entity\ScraperResponseKey;
use App\Entity\ScraperSchedule;
use App\Entity\ServiceRequest;
use App\Entity\ServiceResponseKey;
use App\Services\ApiManager\Operations\RequestOperation;
use App\Services\ApiServices\ApiService;
use App\Services\Permission\AccessControlService;
use App\Services\Scraper\ScraperService;
use App\Services\Tools\HttpRequestService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Tools\SerializerService;
use App\Services\User\UserAdminService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Contains Api endpoint functions for api service related request operations
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_USER")
 * @Route("/api/scraper")
 */
class ScrapersController extends Controller
{
    // Initialise services variables for this controller
    private ProviderService $providerService;
    private ApiService $apiServicesService;
    private ScraperService $scraperService;

    /**
     * Initialise services for this controller
     * @param ProviderService $providerService
     * @param HttpRequestService $httpRequestService
     * @param ScraperService $scraperService
     * @param ApiService $apiServicesService
     * @param SerializerService $serializerService
     * @param AccessControlService $accessControlService
     */
    public function __construct(ProviderService $providerService,
                                HttpRequestService $httpRequestService,
                                ScraperService $scraperService,
                                ApiService $apiServicesService,
                                SerializerService $serializerService,
                                AccessControlService $accessControlService)
    {
        parent::__construct($accessControlService, $httpRequestService, $serializerService);
        $this->providerService = $providerService;
        $this->apiServicesService = $apiServicesService;
        $this->scraperService = $scraperService;
    }

    /**
     * Get list of scrapers function
     * Returns a list of scrapers based on the request query parameters
     *
     * @Route("/list", name="api_get_scraper_list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getScraperList(Request $request)
    {
        $getServices = $this->scraperService->getUserScrapersByProvider(
            $request->get('provider_id'),
            $request->get('sort', "scraperName"),
            $request->get('order', "asc"),
            (int)$request->get('count', null)
        );
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityArrayToArray($getServices, ["list"]));
    }

    /**
     * @Route("/{scraper}", name="api_get_scraper", methods={"GET"})
     */
    public function getScraper(Scraper $scraper)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($scraper, ["single"]));
    }

    /**
     * @Route("/schedule/{scraperSchedule}", name="api_get_scraper_schedule", methods={"GET"})
     */
    public function getScraperSchedule(ScraperSchedule $scraperSchedule)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($scraperSchedule, ["single"]));
    }

    /**
     * @Route("/{scraper}/schedule", name="api_get_scraper_schedule_by_scraper", methods={"GET"})
     */
    public function getScraperScheduleByScraperId(Scraper $scraper)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray(
                $this->scraperService->getScraperScheduleByScraper($scraper),
                ["single"]
            )
        );
    }

    /**
     * @Route("/config/{scraperConfig}", methods={"GET"})
     *
     * @return Response
     */
    public function fetchScraperConfigAction(ScraperConfig $scraperConfig)
    {
        return $this->sendSuccessResponse(
            "",
            $this->serializerService->entityToArray($scraperConfig, ["single"])
        );
    }

    /**
     * @Route("/{scraper}/config", name="api_get_config_by_scraper", methods={"GET"})
     */
    public function getScraperConfigByScraperId(Scraper $scraper)
    {
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray(
                $this->scraperService->getScraperConfigByScraper($scraper),
                ["single"]
            )
        );
    }

    /**
     * @Route("/{scraper}/response-key/{responseKey}", name="api_get_scraper_response_key", methods={"GET"})
     */
    public function getScraperResponseKey(Scraper $scraper, ServiceResponseKey $responseKey)
    {
        $scraperResponseKey = $this->scraperService->findScraperResponseKey($scraper, $responseKey);
        if ($scraperResponseKey === null) {
            return $this->sendSuccessResponse("success",
                []
            );
        }
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($scraperResponseKey, ["single"])
        );
    }

    /**
     * @Route("/{scraper}/response-key/list", name="api_get_scraper_response_key_list", methods={"GET"})
     */
    public function getScraperResponseKeyList(Scraper $scraper)
    {
        $scraperResponseKey = $this->scraperService->findScraperResponseKeyList($scraper);
        return $this->sendSuccessResponse("success",
            $this->serializerService->entityToArray($scraperResponseKey, ["list"])
        );
    }

    /**
     * @Route("/{scraper}/job/request", name="api_send_scraper_job", methods={"POST"})
     */
    public function sendScraperJob(Scraper $scraper, Request $request, UserAdminService $userService)
    {
//        $apiToken = $userService->getLatestToken($request->user());
//        if ($apiToken === null) {
//            return $this->sendErrorResponse("Error retrieving api token");
//        }
//        $create = $this->scraperService->sendScraperJob(
//            $scraper,
//            $apiToken->getToken(),
//            $this->getParameter("app.scraper_base_url") . $this->getParameter("app.scraper_create_job_endpoint")
//        );
//
//        if (!$create) {
//            return $this->sendErrorResponse("Error sending scraper job");
//        }
        return $this->sendSuccessResponse("Scraper inserted",
            $this->serializerService->scraperEntityToArray($scraper, ['job'])
        );
    }

    /**
     * @Route("/create", name="api_create_scraper", methods={"POST"})
     */
    public function createScraper(Request $request)
    {
        $create = $this->scraperService->createScraper(
            $this->httpRequestService->getRequestData($request, true));

        if (!$create) {
            return $this->sendErrorResponse("Error inserting scraper");
        }
        return $this->sendSuccessResponse("Scraper inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * @Route("/{scraper}/update", name="api_update_scraper", methods={"POST"})
     */
    public function updateScraper(Scraper $scraper, Request $request)
    {
        $update = $this->scraperService->updateScraper(
            $scraper,
            $this->httpRequestService->getRequestData($request, true));

        if (!$update) {
            return $this->sendErrorResponse("Error updating scraper");
        }
        return $this->sendSuccessResponse("Scraper updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * @Route("/{scraper}/schedule/create", name="api_create_scraper_schedule", methods={"POST"})
     */
    public function createScraperSchedule(Scraper $scraper, Request $request)
    {
        $create = $this->scraperService->createScraperSchedule(
            $scraper,
            $this->httpRequestService->getRequestData($request, true));

        if (!$create) {
            return $this->sendErrorResponse("Error inserting scraper schedule");
        }
        return $this->sendSuccessResponse("Scraper schedule inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * @Route("/schedule/{scraperSchedule}/update", name="api_update_scraper_schedule", methods={"POST"})
     */
    public function updateScraperSchedule(ScraperSchedule $scraperSchedule, Request $request)
    {
        $update = $this->scraperService->updateScraperSchedule(
            $scraperSchedule,
            $this->httpRequestService->getRequestData($request, true));

        if (!$update) {
            return $this->sendErrorResponse("Error updating scraper schedule");
        }
        return $this->sendSuccessResponse("Scraper schedule updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * @Route("/{scraper}/response-key/{responseKey}/create", name="api_create_scraper_response_key", methods={"POST"})
     */
    public function createScraperResponseKey(Scraper $scraper, ServiceResponseKey $responseKey, Request $request)
    {
        $create = $this->scraperService->createScraperResponseKey(
            $scraper,
            $responseKey,
            $this->httpRequestService->getRequestData($request, true));

        if (!$create) {
            return $this->sendErrorResponse("Error inserting scraper response key");
        }
        return $this->sendSuccessResponse("Scraper response key inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * @Route("/response-key/{responseKey}/update", name="api_update_scraper_response_key", methods={"POST"})
     */
    public function updateScraperResponseKey(ScraperResponseKey $responseKey, Request $request)
    {
        $update = $this->scraperService->updateScraperResponseKey(
            $responseKey,
            $this->httpRequestService->getRequestData($request, true));

        if (!$update) {
            return $this->sendErrorResponse("Error updating scraper response key");
        }
        return $this->sendSuccessResponse("Scraper response key updated",
            $this->serializerService->entityToArray($update, ['single']));
    }


    /**
     * @Route("/{scraper}/config/create", name="api_create_scraper_config", methods={"POST"})
     */
    public function createScraperConfig(Scraper $scraper, Request $request)
    {
        $create = $this->scraperService->createScraperConfig(
            $scraper,
            $this->httpRequestService->getRequestData($request, true));

        if (!$create) {
            return $this->sendErrorResponse("Error inserting scraper config");
        }
        return $this->sendSuccessResponse("Cron config inserted",
            $this->serializerService->entityToArray($create, ['single']));
    }

    /**
     * @Route("/config/{scraperConfig}/update", name="api_update_scraper_config", methods={"POST"})
     * @param ScraperConfig $scraperConfig
     * @param Request $request
     * @return JsonResponse
     */
    public function updateScraperConfig(ScraperConfig $scraperConfig, Request $request)
    {
        $update = $this->scraperService->updateScraperConfig(
            $scraperConfig,
            $this->httpRequestService->getRequestData($request, true));

        if (!$update) {
            return $this->sendErrorResponse("Error updating scraper config");
        }
        return $this->sendSuccessResponse("Cron config updated",
            $this->serializerService->entityToArray($update, ['single']));
    }

    /**
     * Get a CronCommand object with its id and forward it to the index action (view).
     *
     * @Route("/config/{scraperConfig}/delete", methods={"DELETE"})
     * @return Response
     */
    public function deleteScraperConfigAction(ScraperConfig $scraperConfig, Request $request)
    {
        $requestDate = $this->httpRequestService->getRequestData($request);
        $deleteScraperConfigById = $this->scraperService->deleteScraperConfig(
            $scraperConfig
        );
        if (!$deleteScraperConfigById) {
            return $this->sendErrorResponse(
                "Error deleting",
                []
            );
        }
        return $this->sendSuccessResponse(
            "Successfully deleted",
            []
        );
    }

    /**
     * @Route("/{scraper}/delete", name="api_delete_scraper", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteScraper(Scraper $scraper, Request $request)
    {
        $delete = $this->scraperService->deleteScraper($scraper);
        if (!$delete) {
            return $this->sendErrorResponse("Error deleting scraper", $this->serializerService->entityToArray($delete, ['single']));
        }
        return $this->sendSuccessResponse("Scraper deleted.", $this->serializerService->entityToArray($delete, ['single']));
    }
}
