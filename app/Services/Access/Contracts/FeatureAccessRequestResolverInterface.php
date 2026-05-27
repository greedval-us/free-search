<?php

namespace App\Services\Access\Contracts;

use App\Services\Access\DTO\FeatureAccessRequest;
use Illuminate\Http\Request;

interface FeatureAccessRequestResolverInterface
{
    public function resolve(Request $request): ?FeatureAccessRequest;
}
