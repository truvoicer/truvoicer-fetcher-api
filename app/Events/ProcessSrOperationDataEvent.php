<?php

namespace App\Events;

use App\Services\ApiManager\Response\Entity\ApiResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessSrOperationDataEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public int     $srId,
        public ApiResponse $apiResponse,
        public array $queryData,
        public ?bool     $runPagination = true,
        public ?bool     $runResponseKeySrRequests = true,
    )
    {


    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('process-sr-operation-data-event'),
        ];
    }
}
