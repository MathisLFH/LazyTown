<?php

use App\Http\Controllers\Auth\MemberProfileCompletionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Plans\MatchPlanController;
use App\Http\Controllers\Plans\TrainingPlanController;
use App\Http\Controllers\PrivacyConsentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StartseiteController;
use App\Http\Controllers\Teams\ClubOnboardingController;
use App\Http\Controllers\Teams\RegistrationPaymentController;
use App\Http\Controllers\Teams\StripeWebhookController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', StartseiteController::class)
    ->middleware('privacy.consent')
    ->name('home');
Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::inertia('contact/about-us', 'AboutUs')->name('aboutus');
Route::inertia('contact/kontakt', 'Kontakt')->name('kontakt');
Route::inertia('contact/impressum', 'Impressum')->name('impressum');
Route::inertia('contact/datenschutzerklaerung', 'Datenschutzerklaerung')->name('datenschutzerklaerung');
Route::get('startseite', StartseiteController::class)->name('startseite');

Route::middleware(['auth'])->group(function () {
    Route::get('datenschutz/bestaetigen', [PrivacyConsentController::class, 'edit'])->name('privacy.consent.edit');
    Route::post('datenschutz/bestaetigen', [PrivacyConsentController::class, 'update'])->name('privacy.consent.update');
});

Route::middleware(['auth', 'privacy.consent'])->group(function () {
    Route::post('notifications/read', [NotificationController::class, 'markAllAsRead'])->name('notifications.read');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read.single');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('notifications/test', [NotificationController::class, 'createTest'])->name('notifications.test');
    Route::get('suche', SearchController::class)
        ->middleware('throttle:search')
        ->name('search.suggestions');

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::get('register/payment', [RegistrationPaymentController::class, 'show'])->name('register.payment');
    Route::post('register/payment', [RegistrationPaymentController::class, 'store'])->name('register.payment.store');
    Route::delete('register/payment', [RegistrationPaymentController::class, 'destroy'])->name('register.payment.destroy');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::middleware('club.access')->group(function () {
        Route::get('spielplan', [MatchPlanController::class, 'show'])->name('spielplan');
        Route::get('hallenplan', [TrainingPlanController::class, 'show'])->name('hallenplan');
        Route::post('spielplan/csv', [MatchPlanController::class, 'parseCsv'])->name('spielplan.csv');
        Route::put('spielplan', [MatchPlanController::class, 'sync'])->name('spielplan.sync');
        Route::post('hallenplan/csv', [TrainingPlanController::class, 'parseCsv'])->name('hallenplan.csv');
        Route::put('hallenplan', [TrainingPlanController::class, 'sync'])->name('hallenplan.sync');
        Route::get('mein-team', [TeamController::class, 'meinTeam'])->name('mein-team');
        Route::post('teams/{team}/logo', [TeamController::class, 'updateLogo'])->name('teams.logo.update');
    });

    Route::middleware('auth')->group(function () {
        Route::get('mitglied-profil-vervollstaendigen', [MemberProfileCompletionController::class, 'edit'])
            ->name('member.profile-completion.edit');
        Route::patch('mitglied-profil-vervollstaendigen', [MemberProfileCompletionController::class, 'update'])
            ->name('member.profile-completion.update');
    });
    Route::inertia('profil', 'Profil')->name('profil');
    Route::post('verein/{team}/beitritt-bestaetigen', [TeamMemberController::class, 'confirm'])->name('teams.members.confirm');
    Route::get('verein-erstellen', [ClubOnboardingController::class, 'create'])->middleware('role:trainer')->name('club.onboarding');
    Route::post('verein-erstellen', [ClubOnboardingController::class, 'store'])->middleware('role:trainer')->name('club.onboarding.store');
});

require __DIR__.'/settings.php';
