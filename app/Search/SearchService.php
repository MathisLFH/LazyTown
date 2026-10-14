<?php

namespace App\Search;

use App\Search\Providers\GymSearchProvider;
use App\Search\Providers\SettingsSearchProvider;
use App\Search\Providers\TeamSearchProvider;
use App\Search\Providers\UserSearchProvider;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * @var array<int, SearchProvider>
     */
    private array $providers;

    /**
     * @var array<string, string>
     */
    private array $groupLabels = [
        'setting' => 'Einstellungen',
        'user' => 'Benutzer',
        'team' => 'Teams',
        'gym' => 'Hallenorte',
    ];

    public function __construct(
        UserSearchProvider $users,
        TeamSearchProvider $teams,
        SettingsSearchProvider $settings,
        GymSearchProvider $gyms,
    ) {
        $this->providers = [$settings, $users, $teams, $gyms];
    }

    /**
     * @return array{query: string, groups: array<int, array{type: string, label: string, items: array<int, array<string, string>>}>}
     */
    public function search(SearchContext $context, string $query, int $limit): array
    {
        $suggestions = new Collection;
        $remaining = $limit;

        foreach ($this->providers as $provider) {
            if ($remaining <= 0) {
                break;
            }

            $providerSuggestions = $provider->search($query, $context, min(5, $remaining));
            $suggestions = $suggestions->merge($providerSuggestions);
            $remaining = $limit - $suggestions->count();
        }

        $groups = $suggestions
            ->groupBy('type')
            ->map(fn (Collection $items, string $type): array => [
                'type' => $type,
                'label' => $this->groupLabels[$type] ?? $type,
                'items' => $items
                    ->map(fn (SearchSuggestion $suggestion): array => $suggestion->toArray())
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'query' => $query,
            'groups' => $groups,
        ];
    }
}
