<?php

namespace App\Services\Access\Contracts;

use App\Services\Access\AccessResourcePolicy;
use App\Support\Access\AccountPlan;

interface PlanQuotaResolverInterface
{
    public function limitFor(AccountPlan $plan, AccessResourcePolicy $policy): int;
}
