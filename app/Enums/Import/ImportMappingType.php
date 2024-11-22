<?php

namespace App\Enums\Import;

enum ImportMappingType: string
{
    case SELF_NO_CHILDREN = "self_no_children";
    case SELF_WITH_CHILDREN = "self_with_children";

}
