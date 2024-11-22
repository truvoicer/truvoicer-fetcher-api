<?php

namespace App\Enums\Import;

enum ImportConfig: string
{
    case SHOW = "show";
    case ID = "id";
    case NAME = "name";
    case LABEL = "label";
    case ROOT_LABEL_FIELD = "root_label_field";
    case NAME_FIELD = "name_field";
    case LABEL_FIELD = "label_field";
    case CHILDREN_KEYS = 'children_keys';
    case IMPORT_MAPPINGS = 'import_mappings';
}
