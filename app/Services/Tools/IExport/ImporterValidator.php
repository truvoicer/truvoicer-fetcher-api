<?php

namespace App\Services\Tools\IExport;

use App\Traits\Error\ErrorTrait;
use Illuminate\Support\Arr;

class ImporterValidator
{
    use ErrorTrait;

    public function validate(array $data): bool
    {
        if (!Arr::isList($data)) {
            $this->addError('data', 'Data must be an array');
            return false;
        }
        foreach ($data as $index => $row) {
            if (!is_array($row)) {
                $this->addError('data', 'Data must be an array of arrays');
                return false;
            }
            if (!Arr::isAssoc($row)) {
                $this->addError('data_row', 'Data row must be an associative array');
                return false;
            }
            if (
                empty($row['type']) ||
                !is_string($row['type']) ||
                !in_array($row['type'], array_column(ExportService::getExportEntityFields(), 'name'))
            ) {
                $this->addError('data_row', 'Data row must contain a type key with a string value');
                return false;
            }
            if (empty($row['data']) || !is_array($row['data'])) {
                $this->addError('data_row', 'Data row must contain a data key with an array value');
                return false;
            }
        }
        return true;
    }
}
