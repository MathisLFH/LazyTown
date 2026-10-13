<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;

trait RedirectsToMemberProfileCompletion
{
    protected function memberProfileCompletionUrl(Request $request): ?string
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return null;
        }

        $path = route('member.profile-completion.edit', absolute: false);

        return $user->tenant
            ? rtrim($user->tenant->url(), '/').$path
            : route('member.profile-completion.edit');
    }
}
