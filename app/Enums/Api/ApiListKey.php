<?php
namespace App\Enums\Api;

enum ApiListKey: string {
    case LIST_KEY = 'list_key';
    case LIST_ITEM_REPEATER_KEY = 'list_item_repeater_key';
    case LIST_FORMAT_OPTIONS = 'list_format_options';
    case LIST_FORMAT_OPTION_PREG_MATCH = 'list_format_option_preg_match';
    case LIST_FORMAT_OPTION_JSON_DECODE = 'list_format_option_json_decode';
}
