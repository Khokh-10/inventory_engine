<?php

declare(strict_types=1);

namespace App\Enums;

enum ProviderResponse: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case PARTIAL_SUCCESS = 'partial_success';
    case DUPLICATE = 'duplicate';
}
