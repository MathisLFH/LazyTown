<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteMemberProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberProfileCompletionController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->must_change_password, 404);

        return Inertia::render('auth/CompleteMemberProfile', [
            'member' => $request->user()->only([
                'first_name',
                'last_name',
                'email',
                'birth_date',
                'birth_place',
                'nationality',
                'address',
                'postcode',
                'city',
            ]),
        ]);
    }

    public function update(CompleteMemberProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->safe()->only([
            'first_name',
            'last_name',
            'email',
            'birth_date',
            'birth_place',
            'nationality',
            'address',
            'postcode',
            'city',
            'password',
        ]);

        $user->fill($data);
        $user->name = trim($data['first_name'].' '.$data['last_name']);
        $user->must_change_password = false;
        $user->save();

        return redirect()->to($user->tenant?->url() ?? route('home'));
    }
}
