<?php

namespace App\Http\Resources\Service\ServiceRequest;

use Truvoicer\TfDbReadCore\Enums\Api\ApiListKey;
use Truvoicer\TfDbReadCore\Enums\FormatOptions;
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
            'sr_rate_limit' => $this->whenLoaded('srRateLimit'),
            'name' => $this->name,
            'label' => $this->label,
            'pagination_type' => $this->pagination_type,
            'query_parameters' => $this->query_parameters,
            'type' => $this->type,
            ApiListKey::LIST_KEY->value => $this->{ApiListKey::LIST_KEY->value},
            ApiListKey::LIST_ITEM_REPEATER_KEY->value => $this->{ApiListKey::LIST_ITEM_REPEATER_KEY->value},
            'default_sr' => $this->default_sr,
            'default_data' => $this->default_data,
            ApiListKey::LIST_FORMAT_OPTIONS->value => (is_array($this->{ApiListKey::LIST_FORMAT_OPTIONS->value}))
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
                        $this->{ApiListKey::LIST_FORMAT_OPTIONS->value},

                        fn(string $value) => FormatOptions::tryFrom($value),
                        ARRAY_FILTER_USE_BOTH
                    )
                )
                : [],
            ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value => $this->{ApiListKey::LIST_FORMAT_OPTION_PREG_MATCH->value},
        ];
    }
}
