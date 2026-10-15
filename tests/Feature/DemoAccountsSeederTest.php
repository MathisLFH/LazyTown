<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Services\Teams\TeamPermissionSynchronizer;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

test('creates reusable demo accounts with subscriptions and an active player membership', function () {
    $this->artisan('db:seed', ['--class' => DemoAccountsSeeder::class])->assertSuccessful();

    $premiumAdmin = User::query()->where('email', 'premium@example.com')->firstOrFail();
    $freeAdmin = User::query()->where('email', 'free@example.com')->firstOrFail();
    $trainer = User::query()->where('email', 'trainer@example.com')->firstOrFail();
    $player = User::query()->where('email', 'player@example.com')->firstOrFail();
    $premiumTeam = Team::query()->where('slug', 'demo-premium-verein')->firstOrFail();

    expect(Hash::check('12345678', $premiumAdmin->password))->toBeTrue()
        ->and(Hash::check('12345678', $freeAdmin->password))->toBeTrue()
        ->and(Hash::check('12345678', $trainer->password))->toBeTrue()
        ->and(Hash::check('12345678', $player->password))->toBeTrue()
        ->and($premiumAdmin->tenant->subscriptions()->where('type', 'premium')->exists())->toBeTrue()
        ->and($freeAdmin->tenant->subscriptions()->where('type', 'free')->exists())->toBeTrue()
        ->and($premiumTeam->payment_status)->toBe('paid')
        ->and($trainer->teamRole($premiumTeam))->toBe(TeamRole::Admin)
        ->and($trainer->belongsToTeam($premiumTeam))->toBeTrue()
        ->and($player->teamRole($premiumTeam))->toBe(TeamRole::Member)
        ->and($player->belongsToTeam($premiumTeam))->toBeTrue();

    $this->post(route('login.store'), [
        'email' => 'player@example.com',
        'password' => '12345678',
    ]);

    $this->assertAuthenticatedAs($player);

    $this->artisan('db:seed', ['--class' => DemoAccountsSeeder::class])->assertSuccessful();

    expect(User::query()->whereIn('email', [
        'premium@example.com',
        'free@example.com',
        'trainer@example.com',
        'player@example.com',
    ])->count())->toBe(4)
        ->and($premiumTeam->fresh()->memberships()->count())->toBe(3);
});

test('refuses to create demo accounts outside local and testing environments', function () {
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(DemoAccountsSeeder::class)->run(app(TeamPermissionSynchronizer::class)))
        ->toThrow(RuntimeException::class);

    expect(User::query()->count())->toBe(0);
});
