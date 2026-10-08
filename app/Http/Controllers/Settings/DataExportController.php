<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user()->load('teams:id,name,slug');

        $export = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'birth_date' => $user->birth_date?->toDateString(),
                'city' => $user->city,
                'phone' => $user->phone,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'teams' => $user->teams->map(fn ($team) => [
                'name' => $team->name,
                'slug' => $team->slug,
            ])->values()->all(),
        ];

        return response()->streamDownload(
            function () use ($export): void {
                echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            },
            'lazytown-datenexport.json',
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
