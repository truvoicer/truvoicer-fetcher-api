<?php

namespace App\Enums\Import;

enum ImportAction: string
{
    case CREATE = "create";
    case OVERWRITE = "overwrite";
}
