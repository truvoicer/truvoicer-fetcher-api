<?php

namespace App\Enums\Import;

enum ImportAction: string
{
    case CREATE = "create";
    case OVERWRITE = "overwrite";
    case OVERWRITE_OR_CREATE = "overwrite_or_create";
}
