<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Http\Resources\ProviderMinimalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SrResponseKeyMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */


    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'show_in_response' => $this->show_in_response,
            'is_service_request' => $this->is_service_request,
            'list_item' => $this->list_item,
            'custom_value' => $this->custom_value,
            'is_date' => $this->is_date,
            'date_format' => $this->date_format,
            'append_extra_data_value' => $this->append_extra_data_value,
            'prepend_extra_data_value' => $this->prepend_extra_data_value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
