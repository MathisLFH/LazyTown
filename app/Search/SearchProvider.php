<?php

namespace App\Search;

use Illuminate\Support\Collection;

interface SearchProvider
{
    /**
     * @return Collection<int, SearchSuggestion>
     */
    public function search(string $query, SearchContext $context, int $limit): Collection;
}
