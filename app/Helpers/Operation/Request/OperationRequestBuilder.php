<?php

namespace App\Helpers\Operation\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Enums\DatabaseFilterType;

class OperationRequestBuilder
{

    protected array $preProcessedFilters = [];
    protected array $processedFilters = [];
    protected array $data = [];
    protected Request $request;
    protected FormRequest $formRequest;

    public function setFormRequest(FormRequest $formRequest): self
    {
        $this->formRequest = $formRequest;
        return $this;
    }

    public function getFormRequest(): FormRequest
    {
        return $this->formRequest;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getProcessedFilters(): array {
        return $this->processedFilters;
    }

    public function getPreProcessedFilters(): array {
        return $this->preProcessedFilters;
    }

    public function fromRequest(): self
    {
        $this->setRequest(request());
        $this->setData($this->request->all());
        return $this;
    }

    public function addGreaterThanFilter(string $field): self
    {
        if (!isset($this->data['database_filters'])) {
            $this->data['database_filters'] = [];
        }
        $this->data['database_filters'][$field] = [
            'operator' => '>',
        ];
        return $this;
    }

    public function addLessThanFilter(string $field): self
    {
        if (!isset($this->data['database_filters'])) {
            $this->data['database_filters'] = [];
        }
        $this->data['database_filters'][$field] = [
            'operator' => '<',
        ];
        return $this;
    }

    public function build(): array
    {
        if (
            isset($this->formRequest)
        ) {
            $this->formRequest->setValidator(
                Validator::make(
                    $this->data,
                    $this->formRequest->rules(),
                    $this->formRequest->messages(),
                    $this->formRequest->attributes()
                )
            );
            $this->data = $this->formRequest->validated();
        }


        $this->preProcessedFilters = $this->data['filters'] ?? [];
        unset($this->data['filters']);
        foreach ($this->preProcessedFilters as $key => $value) {
            if (empty($value['type'])) {
                continue;
            }
            $type = DatabaseFilterType::tryFrom($value['type']);
            if (!$type) {
                continue;
            }
            switch ($type) {
                case DatabaseFilterType::GREATER_THAN:
                    $this->addGreaterThanFilter($value['field']);
                    break;
                case DatabaseFilterType::LESS_THAN:
                    $this->addLessThanFilter($value['field']);
                    break;
            }
            $this->processedFilters[$value['field']] = $value['value'];
        }

        $this->data = array_merge(
            $this->data,
            $this->processedFilters
        );

        return $this->data;
    }
}
