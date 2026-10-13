<?php

namespace App\Search;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;

class SearchContext
{
    public function __construct(
        public User $user,
        public ?Tenant $tenant,
        public ?Team $currentTeam,
    ) {}
}
