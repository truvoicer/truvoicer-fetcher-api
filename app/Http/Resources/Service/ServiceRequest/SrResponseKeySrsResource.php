<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SrResponseKeySr
 */
class SrResponseKeySrsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "sr_response_key" => $this->whenLoaded('srResponseKey', new SrResponseKeyResource($this->srResponseKey)),
            "sr" => $this->whenLoaded('sr', new SrTreeViewResource($this->sr)),
            "action" => $this->action,
            "single_request" =>  $this->single_request,
            "disable_request" =>  $this->disable_request,
            "request_response_keys" =>  $this->request_response_keys,
            "response_response_keys" =>  $this->response_response_keys,
            "created_at" =>  $this->created_at,
            "updated_at" =>  $this->updated_at,
        ];
    }
}
