<?php

namespace App\Search\Providers;

use App\Models\Team;
use App\Search\SearchContext;
use App\Search\SearchProvider;
use App\Search\SearchSuggestion;
use Illuminate\Support\Collection;

class TeamSearchProvider implements SearchProvider
{
    /**
     * @return Collection<int, SearchSuggestion>
     */
    public function search(string $query, SearchContext $context, int $limit): Collection
    {
        if ($context->tenant === null) {
            return collect();
        }

        $teamIds = $context->user->teams()
            ->where('teams.tenant_id', $context->tenant->id)
            ->where('teams.is_personal', false)
            ->wherePivot('status', 'active')
            ->pluck('teams.id');

        if ($teamIds->isEmpty()) {
            return collect();
        }

        return Team::search($query)
            ->where('tenant_id', $context->tenant->id)
            ->whereIn('id', $teamIds)
            ->take($limit)
            ->get()
            ->map(fn (Team $team): SearchSuggestion => new SearchSuggestion(
                type: 'team',
                title: $team->name,
                subtitle: 'Team',
                url: route('teams.edit', ['team' => $team->slug]),
            ))
            ->values();
    }
}
