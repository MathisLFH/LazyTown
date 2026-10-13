<?php

namespace App\Search\Providers;

use App\Models\User;
use App\Search\SearchContext;
use App\Search\SearchProvider;
use App\Search\SearchSuggestion;
use Illuminate\Support\Collection;

class UserSearchProvider implements SearchProvider
{
    /**
     * @return Collection<int, SearchSuggestion>
     */
    public function search(string $query, SearchContext $context, int $limit): Collection
    {
        if ($context->tenant === null) {
            return collect();
        }

        $targetUrl = $context->user->hasRole('verwaltung')
            ? route('admin.teams.index')
            : route('mein-team');

        $search = User::search($query)
            ->where('tenant_id', $context->tenant->id);

        if (! $context->user->hasRole('verwaltung')) {
            $team = $context->currentTeam;

            if ($team === null || $team->is_personal) {
                return collect();
            }

            $memberIds = $team->members()
                ->wherePivot('status', 'active')
                ->pluck('users.id');

            if ($memberIds->isEmpty()) {
                return collect();
            }

            $search->whereIn('id', $memberIds);
        }

        return $search
            ->take($limit)
            ->get()
            ->map(fn (User $user): SearchSuggestion => new SearchSuggestion(
                type: 'user',
                title: $user->name,
                subtitle: $user->active_role ? ucfirst($user->active_role) : null,
                url: $targetUrl,
            ))
            ->values();
    }
}
