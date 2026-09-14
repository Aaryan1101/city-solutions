<?php

declare(strict_types=1);

namespace App\Controllers\Api;

final class MedicalController extends EcommerceController
{
    protected function moduleKey(): string
    {
        return 'medical';
    }
}
