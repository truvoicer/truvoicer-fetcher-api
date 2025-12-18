<?php

namespace App\Http\Resources\Service\ServiceRequest;

use App\Enums\FormatOptions;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProviderMinimalResource;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\Service\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sr */
class ServiceRequestResource extends JsonResource
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
            'sr_schedule_id' => $this->whenLoaded('srSchedule', $this->srSchedule?->id),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', CategoryResource::make($this->category)),
            'provider_id' => $this->provider_id,
            'provider' => $this->whenLoaded('provider', ProviderResource::make($this->provider)),
            's_id' => $this->s_id,
            's' => $this->whenLoaded('s', ServiceResource::make($this->s)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'child_srs' =>$this->whenLoaded('childSrs', ServiceRequestResource::collection($this->childSrs)),
            'provider' => new ProviderMinimalResource($this->provider),
            'hasChildren' => $this->childSrs->count() > 0,
            'srChildSr' => $this->whenLoaded('srChildSr'),
            'name' => $this->name,
            'label' => $this->label,
            'pagination_type' => $this->pagination_type,
            'query_parameters' => $this->query_parameters,
            'type' => $this->type,
            'items_array_key' => $this->items_array_key,
            'item_repeater_key' => $this->item_repeater_key,
            'default_sr' => $this->default_sr,
            'default_data' => $this->default_data,
            'items_array_format_options' => (is_array($this->items_array_format_options))
                ?

                array_map(
                    function (string $value) {
                        $formatOption = FormatOptions::tryFrom($value);
                        return [
                            'value' => $formatOption->value,
                            'label' => $formatOption->label()
                        ];
                    },
                    array_filter(
                        $this->items_array_format_options,

                        fn(string $value) => FormatOptions::tryFrom($value),
                        ARRAY_FILTER_USE_BOTH
                    )
                )
                : [],
            'items_array_format_preg_match' => $this->items_array_format_preg_match,
        ];
    }
}
