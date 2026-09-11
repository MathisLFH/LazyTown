<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionTeam
{
    /**
     * Set the Spatie team scope for the current request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('current_team') ?? $request->route('team');

        if ($team instanceof Team) {
            setPermissionsTeamId($team->id);
        } elseif (is_string($team)) {
            setPermissionsTeamId(Team::where('slug', $team)->value('id'));
        }

        return $next($request);
    }
}
