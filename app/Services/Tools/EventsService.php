<?php
namespace App\Services\Tools;

use App\EventListener\ApiSendRequestListener;
use App\Events\ApiSendRequestEvent;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventsService {

    private $dispatcher;
    private $apiSendRequestListener;

    public function __construct(ApiSendRequestListener $apiSendRequestListener)
    {
        $this->dispatcher = new EventDispatcher();
        $this->apiSendRequestListener = $apiSendRequestListener;
    }

    public function apiSendRequestEvent(ApiRequest $apiRequest) {
        $apiRequestEvent = new ApiSendRequestEvent($apiRequest);
//        $listener = new ApiSendRequestListener();
        $this->dispatcher->addListener('api.request.sent', [$this->apiSendRequestListener, 'onApiRequestSent']);
        $this->dispatcher->dispatch($apiRequestEvent, $apiRequestEvent::NAME);
    }


}
