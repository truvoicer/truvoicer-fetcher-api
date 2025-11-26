<?php

namespace App\Enums;

enum DatabaseFilterType: string
{
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN = 'less_than';
}
