<?php

namespace App\Search\Providers;

use App\Models\Gym;
use App\Search\SearchContext;
use App\Search\SearchProvider;
use App\Search\SearchSuggestion;
use Illuminate\Support\Collection;

class GymSearchProvider implements SearchProvider
{
    /**
     * @return Collection<int, SearchSuggestion>
     */
    public function search(string $query, SearchContext $context, int $limit): Collection
    {
        if ($context->tenant === null || $context->currentTeam?->is_personal) {
            return collect();
        }

        return Gym::search($query)
            ->where('tenant_id', $context->tenant->id)
            ->take($limit)
            ->get()
            ->map(fn (Gym $gym): SearchSuggestion => new SearchSuggestion(
                type: 'gym',
                title: $gym->name,
                subtitle: 'Hallenort',
                url: route('hallenplan', ['gym' => $gym->id]),
            ))
            ->values();
    }
}
