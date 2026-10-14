<?php

use App\Http\Controllers\Auth\MemberProfileCompletionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Plans\MatchPlanController;
use App\Http\Controllers\Plans\TrainingPlanController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StartseiteController;
use App\Http\Controllers\Teams\ClubOnboardingController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', StartseiteController::class)->name('home');
Route::inertia('contact/about-us', 'AboutUs')->name('aboutus');
Route::inertia('contact/kontakt', 'Kontakt')->name('kontakt');
Route::inertia('contact/impressum', 'Impressum')->name('impressum');
Route::inertia('contact/datenschutzerklaerung', 'Datenschutzerklaerung')->name('datenschutzerklaerung');
Route::get('startseite', StartseiteController::class)->name('startseite');

Route::middleware(['auth'])->group(function () {
    Route::post('notifications/read', [NotificationController::class, 'markAllAsRead'])->name('notifications.read');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read.single');
    Route::post('notifications/test', [NotificationController::class, 'createTest'])->name('notifications.test');
    Route::get('suche', SearchController::class)
        ->middleware('throttle:search')
        ->name('search.suggestions');

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::middleware('club.access')->group(function () {
        Route::get('spielplan', [MatchPlanController::class, 'show'])->name('spielplan');
        Route::get('hallenplan', [TrainingPlanController::class, 'show'])->name('hallenplan');
        Route::post('spielplan/csv', [MatchPlanController::class, 'parseCsv'])->name('spielplan.csv');
        Route::put('spielplan', [MatchPlanController::class, 'sync'])->name('spielplan.sync');
        Route::post('hallenplan/csv', [TrainingPlanController::class, 'parseCsv'])->name('hallenplan.csv');
        Route::put('hallenplan', [TrainingPlanController::class, 'sync'])->name('hallenplan.sync');
        Route::get('mein-team', [TeamController::class, 'meinTeam'])->name('mein-team');
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
