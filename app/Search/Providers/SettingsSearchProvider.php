<?php

namespace App\Search\Providers;

use App\Search\SearchContext;
use App\Search\SearchProvider;
use App\Search\SearchSuggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SettingsSearchProvider implements SearchProvider
{
    /**
     * @return Collection<int, SearchSuggestion>
     */
    public function search(string $query, SearchContext $context, int $limit): Collection
    {
        $query = Str::lower($query);

        $settings = [
            [
                'title' => 'Profil',
                'subtitle' => 'Persönliche Daten und Kontaktinformationen',
                'keywords' => ['profil', 'persönliche daten', 'kontakt'],
                'url' => route('profile.edit'),
            ],
            [
                'title' => 'Sicherheit',
                'subtitle' => 'Passwort, Zwei-Faktor-Authentifizierung und Passkeys',
                'keywords' => ['sicherheit', 'passwort', '2fa', 'passkey'],
                'url' => route('security.edit'),
                'requiresClub' => true,
            ],
            [
                'title' => 'Erscheinungsbild',
                'subtitle' => 'Darstellung und Farbschema',
                'keywords' => ['erscheinungsbild', 'darstellung', 'design', 'dark mode'],
                'url' => route('appearance.edit'),
                'requiresClub' => true,
            ],
            [
                'title' => 'Teams',
                'subtitle' => 'Vereine und Teams verwalten',
                'keywords' => ['teams', 'verein', 'mannschaft'],
                'url' => route('teams.index'),
                'requiresClub' => true,
            ],
            [
                'title' => 'Teams verwalten',
                'subtitle' => 'Teams und Mitglieder des Vereins verwalten',
                'keywords' => ['verwaltung', 'teams verwalten', 'mitglieder'],
                'url' => route('admin.teams.index'),
                'requiresClub' => true,
                'requiresAdministration' => true,
            ],
        ];

        return collect($settings)
            ->filter(function (array $setting) use ($query, $context): bool {
                if (($setting['requiresClub'] ?? false) && $context->currentTeam?->is_personal) {
                    return false;
                }

                if (($setting['requiresAdministration'] ?? false) && ! $context->user->hasRole('verwaltung')) {
                    return false;
                }

                return Str::contains(Str::lower($setting['title'].' '.$setting['subtitle'].' '.implode(' ', $setting['keywords'])), $query);
            })
            ->take($limit)
            ->map(fn (array $setting): SearchSuggestion => new SearchSuggestion(
                type: 'setting',
                title: $setting['title'],
                subtitle: $setting['subtitle'],
                url: $setting['url'],
            ))
            ->values();
    }
}
