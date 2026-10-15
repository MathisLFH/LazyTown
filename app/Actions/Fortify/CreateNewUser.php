<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (array_key_exists('club_name', $input)) {
            return $this->createClub($input);
        }

        $input['roles'] = [UserRole::Player->value];
        $input['active_role'] = UserRole::Player->value;

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'roles' => $input['roles'],
                'active_role' => $input['active_role'],
            ]);

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }

    /**
     * Validate and create a club, its administrator, and subscription.
     *
     * @param  array<string, string>  $input
     */
    private function createClub(array $input): User
    {
        if (isset($input['subdomain'])) {
            $input['subdomain'] = Str::lower($input['subdomain']);
        }

        $data = Validator::make($input, [
            'club_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'alpha_dash', 'max:63', Rule::unique('tenants', 'subdomain')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'subscription' => ['required', 'string', Rule::in(['free', 'premium'])],
            'password' => $this->passwordRules(),
        ])->validate();

        $subscriptionPrice = $data['subscription'] === 'premium'
            ? (int) config('services.stripe.annual_access_amount_cents') / 100
            : 0;

        return DB::transaction(function () use ($data, $subscriptionPrice): User {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'birth_place' => $data['birth_place'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'address' => $data['address'] ?? null,
                'postcode' => $data['postcode'] ?? null,
                'city' => $data['city'] ?? null,
                'email' => $data['email'],
                'password' => $data['password'],
                'roles' => [UserRole::Administration->value],
                'active_role' => UserRole::Administration->value,
            ]);

            $tenant = Tenant::create([
                'admin_id' => $user->id,
                'subdomain' => $data['subdomain'],
                'name' => $data['club_name'],
            ]);

            $user->update(['tenant_id' => $tenant->id]);

            $team = $this->createTeam->handle(
                $user,
                $data['club_name'],
                addOwner: $data['subscription'] !== 'premium',
            );
            $team->update([
                'tenant_id' => $tenant->id,
                'payment_status' => $data['subscription'] === 'premium' ? 'pending' : 'not_required',
            ]);
            $team->memberships()
                ->where('user_id', $user->id)
                ->update(['tenant_id' => $tenant->id]);

            $subscription = Subscription::query()->firstOrCreate(
                ['type' => $data['subscription']],
                ['price' => $subscriptionPrice],
            );

            $tenant->subscriptions()->attach($subscription, [
                'subscription_time' => now(),
            ]);

            return $user->fresh();
        });
    }
}
