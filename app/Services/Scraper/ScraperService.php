<?php

namespace App\Services\Scraper;

use App\Models\ScraperConfig;
use App\Models\Scraper;
use App\Models\ScraperResponseKey;
use App\Models\ScraperSchedule;
use App\Models\ServiceResponseKey;
use App\Models\User;
use App\Models\UserScraper;
use App\Repositories\ScraperConfigRepository;
use App\Repositories\ScraperRepository;
use App\Repositories\ScraperResponseKeyRepository;
use App\Repositories\ScraperScheduleRepository;
use App\Repositories\UserScraperRepository;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiServices\ApiService;
use App\Services\BaseService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\HttpRequestService;
use App\Services\Tools\SerializerService;
use App\Services\Tools\UtilsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ScraperService extends BaseService
{


    protected EntityManagerInterface $em;
    protected HttpRequestService $httpRequestService;
    protected ScraperRepository $scraperRepository;
    protected ScraperConfigRepository $scraperConfigRepo;
    protected ScraperScheduleRepository $scraperScheduleRepo;
    protected ScraperResponseKeyRepository $scraperResponseKeyRepo;
    protected UserScraperRepository $userScraperRepo;
    protected ProviderService $providerService;
    protected ApiService $apiService;
    protected SerializerService $serializerService;
    protected ApiClientHandler $apiClientHandler;

    public function __construct(EntityManagerInterface $entityManager, HttpRequestService $httpRequestService,
                                TokenStorageInterface $tokenStorage, ProviderService $providerService,
                                ApiService $apiService, SerializerService $serializerService, ApiClientHandler $apiClientHandler)
    {
        parent::__construct($tokenStorage);
        $this->em = $entityManager;
        $this->httpRequestService = $httpRequestService;
        $this->providerService = $providerService;
        $this->apiService = $apiService;
        $this->serializerService = $serializerService;
        $this->apiClientHandler = $apiClientHandler;
        $this->scraperRepository = $this->em->getRepository(Scraper::class);
        $this->scraperConfigRepo = $this->em->getRepository(ScraperConfig::class);
        $this->scraperScheduleRepo = $this->em->getRepository(ScraperSchedule::class);
        $this->scraperResponseKeyRepo = $this->em->getRepository(ScraperResponseKey::class);
        $this->userScraperRepo = $this->em->getRepository(UserScraper::class);
    }

    public function findByQuery(string $query)
    {
        return $this->scraperRepository->findByQuery($query);
    }

    public function getScraperByName(string $scraperName = null)
    {
        return $this->scraperRepository->findByName($scraperName);
    }

    public function getUserScrapersByProvider(int $providerId, string $sort, string $order, int $count)
    {
        return $this->userScraperRepo->findUserScraperByProvider(
            $this->user,
            $this->providerService->getProviderById($providerId),
            $sort,
            $order,
            $count
        );
    }

    public function getUserScraperByName(string $scraperName = null)
    {
        return $this->userScraperRepo->findUserScraperByName($this->user, $scraperName);
    }

    public function getUserScrapers(string $sort, string $order, int $count)
    {
        return $this->userScraperRepo->findScrapersByUser($this->user, $sort, $order, $count);
    }

    public function getScraperById(int $scraperId)
    {
        $scraper = $this->scraperRepository->findOneBy(["id" => $scraperId]);
        if ($scraper === null) {
            throw new BadRequestHttpException(sprintf("Scraper id:%s not found in database.",
                $scraperId
            ));
        }
        return $scraper;
    }

    public function getScraperScheduleById(int $scraperScheduleId)
    {
        $scraperSchedule = $this->scraperScheduleRepo->findOneBy(["id" => $scraperScheduleId]);
        if ($scraperSchedule === null) {
            throw new BadRequestHttpException(sprintf("Scraper schedule id:%s not found in database.",
                $scraperScheduleId
            ));
        }
        return $scraperSchedule;
    }

    public function getScraperScheduleByScraper(Scraper $scraper)
    {
        $scraperSchedule = $this->scraperScheduleRepo->findOneBy(["scraper" => $scraper]);
        if ($scraperSchedule === null) {
            throw new BadRequestHttpException(sprintf("Scraper schedule for scraper:%s not found in database.",
                $scraper->getScraperLabel()
            ));
        }
        return $scraperSchedule;
    }

    public function getScraperConfigByScraper(Scraper $scraper)
    {
        return $this->scraperConfigRepo->findOneBy(["scraper" => $scraper]);
    }

    public function getScraperConfigById(int $scraperConfigId): ScraperConfig
    {
        $scraperConfig = $this->scraperConfigRepo->findOneBy(["id" => $scraperConfigId]);
        if ($scraperConfig === null) {
            throw new BadRequestHttpException(sprintf("Cron config id:%s not found in database.",
                $scraperConfigId
            ));
        }
        return $scraperConfig;
    }

    public function findScraperResponseKey(Scraper $scraper, ServiceResponseKey $responseKey)
    {
        return $this->scraperResponseKeyRepo->findOneBy([
            "scraper" => $scraper,
            "serviceResponseKey" => $responseKey
        ]);
    }

    public function findScraperResponseKeyList(Scraper $scraper, array $data = [])
    {
        return $this->scraperResponseKeyRepo->findScraperResponseKeysWithConditions(
            $scraper,
            $data
        );
    }

    public function createScraper(array $data = [])
    {
        return $this->scraperRepository->createScraper(
            $this->providerService->getProviderById($data["provider_id"]),
            $this->apiService->getServiceById($data["service_id"]),
            $this->user,
            $data,
        );
    }

    public function updateScraper(Scraper $scraper, array $data = [])
    {
        if (isset($data["label"])) {
            $scraper->setScraperLabel($data["label"]);
            $scraper->setScraperName(UtilsService::labelToName($data["label"]));
        }
        if (isset($data["priority"])) {
            $scraper->setPriority($data["priority"]);
        }
        if (isset($data["disabled"])) {
            $scraper->setDisabled($data["disabled"]);
        }
        if (isset($data["locked"])) {
            $scraper->setLocked($data["locked"]);
        }
        if (isset($data["execute_immediately"])) {
            $scraper->setExecuteImmediately($data["execute_immediately"]);
        }
        if (isset($data["arguments"])) {
            $scraper->setArguments($data["arguments"]);
        }
        if (isset($data["service_id"])) {
            $scraper->setService(
                $this->apiService->getServiceById($data["service_id"])
            );
        }
        return $this->scraperRepository->saveScraper($scraper);
    }


    public function createScraperSchedule(Scraper $scraper, array $data = [])
    {
        return $this->scraperScheduleRepo->createScraperSchedule(
            $scraper,
            $data
        );
    }

    public function createScraperResponseKey(Scraper $scraper, ServiceResponseKey $serviceResponseKey, array $data = [])
    {
        if (!isset($data["response_key_selector"])) {
            throw new BadRequestHttpException("Response key selector not in request.");
        }
        return $this->scraperResponseKeyRepo->createScraperResponseKey(
            $scraper, $serviceResponseKey, $data["response_key_selector"]
        );
    }

    public function updateScraperResponseKey(ScraperResponseKey $scraperResponseKey, array $data = [])
    {
        if (!isset($data["response_key_selector"])) {
            throw new BadRequestHttpException("Response key selector not in request.");
        }
        $scraperResponseKey->setResponseKeySelector($data["response_key_selector"]);
        return $this->scraperResponseKeyRepo->saveScraperResponseKey($scraperResponseKey);
    }

    public function updateScraperSchedule(ScraperSchedule $scraperSchedule, array $data = [])
    {
        return $this->scraperScheduleRepo->saveScraperSchedule(
            $this->scraperScheduleRepo->buildSaveObject($scraperSchedule, $data)
        );
    }

    public function createScraperConfig(Scraper $scraper, array $data = [])
    {
        return $this->scraperConfigRepo->createScraperConfig(
            $scraper,
            $data
        );
    }

    public function updateScraperConfig(ScraperConfig $scraperConfig, array $data = [])
    {
        return $this->scraperConfigRepo->saveScraperConfig(
            $this->scraperConfigRepo->buildSaveObject($scraperConfig, $data)
        );
    }

    public function sendScraperJob(Scraper $scraper, string $apiToken, string $url)
    {
        $apiRequest = new ApiRequest();
        $apiRequest->setUrl($url);
        $apiRequest->setAuthentication([
            "auth_bearer" => $apiToken
        ]);
        $apiRequest->setMethod("POST");
        $apiRequest->setBody(
            $this->serializerService->scraperEntityToArray($scraper, ['job'])
        );
        try {
            $response = $this->apiClientHandler->sendRequest($apiRequest);
            switch ($response->getStatusCode()) {
                case 200:
                    return true;
                default:
                    return false;
            }
        } catch (\Exception | TransportExceptionInterface $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    public function deleteScraperConfigById(int $scraperConfigId)
    {
        return $this->scraperConfigRepo->deleteById($scraperConfigId);
    }

    public function deleteScraperConfig(ScraperConfig $scraperConfig)
    {
        return $this->scraperConfigRepo->delete($scraperConfig);
    }

    public function deleteScraperResponseKeyById($scraperResponseKeyId)
    {
        return $this->scraperResponseKeyRepo->deleteById($scraperResponseKeyId);
    }

    public function deleteScraperById($scraperId)
    {
        return $this->deleteScraper($this->getScraperById($scraperId));
    }

    public function deleteScraper(Scraper $scraper)
    {
        return $this->scraperRepository->delete($scraper);
    }
}
