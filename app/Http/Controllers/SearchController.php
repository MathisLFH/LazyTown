<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchSuggestionsRequest;
use App\Search\SearchContext;
use App\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __invoke(SearchSuggestionsRequest $request, SearchService $search): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();
        $limit = min($request->integer('limit', 10), 10);

        if (Str::length($query) < 2) {
            return response()->json([
                'query' => $query,
                'groups' => [],
            ]);
        }

        $user = $request->user();

        return response()->json($search->search(
            new SearchContext(
                user: $user,
                tenant: $user->tenant,
                currentTeam: $user->currentTeam,
            ),
            $query,
            $limit,
        ));
    }
}
