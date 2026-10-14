<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyConsentController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('PrivacyConsent');
    }

    public function update(Request $request): RedirectResponse
    {
        Validator::make($request->all(), [
            'privacy_consent' => ['accepted'],
        ])->validate();

        $request->user()->forceFill([
            'privacy_policy_version' => config('privacy.policy_version'),
            'privacy_policy_accepted_at' => now(),
        ])->save();

        return to_route('home');
    }
}
