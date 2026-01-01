<?php

namespace App\Http\Controllers\Api\Backend\Tools;

use App\Enums\SelectDataEnum;
use Truvoicer\TruFetcherGet\Helpers\Tools\ClassHelpers;
use App\Http\Controllers\Controller;
use Truvoicer\TruFetcherGet\Traits\Enum\EnumUtillityTrait;

class EnumController extends Controller
{

    public function show(string $enum)
    {
        $findEnum = SelectDataEnum::tryFrom($enum);
        if (! $findEnum) {
            return response()->json(['error' => 'Invalid enum type'], 400);
        }
        $enumClass = $findEnum->getEnumClass();
        if (!ClassHelpers::usesTrait($enumClass, EnumUtillityTrait::class, true)) {
            return response()->json(['error' => 'Enum does not implement SelectDataTrait'], 400);
        }
        if (! method_exists($enumClass, 'labelAndValueArray')) {
            return response()->json(['error' => 'Enum does not support toSelectData'], 400);
        }
        return response()->json([
            'data' => $enumClass::labelAndValueArray(),
        ]);
    }
}
