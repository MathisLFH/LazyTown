<?php

use App\Http\Controllers\StartseiteController;
use App\Http\Controllers\Teams\ClubOnboardingController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', StartseiteController::class)->name('home');
Route::inertia('about-us', 'AboutUs')->name('aboutus');

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::middleware('club.access')->group(function () {
        Route::inertia('spielplan', 'Spielplan')->name('spielplan');
        Route::inertia('hallenplan', 'Hallenplan')->name('hallenplan');
        Route::inertia('mein-team', 'MeinTeam')->name('mein-team');
    });
    Route::inertia('profil', 'Profil')->name('profil');
    Route::post('verein/{team}/beitritt-bestaetigen', [TeamMemberController::class, 'confirm'])->name('teams.members.confirm');
    Route::get('verein-erstellen', [ClubOnboardingController::class, 'create'])->middleware('role:trainer')->name('club.onboarding');
    Route::post('verein-erstellen', [ClubOnboardingController::class, 'store'])->middleware('role:trainer')->name('club.onboarding.store');
    Route::middleware(['club.access', 'role:trainer'])->group(function () {
        Route::inertia('paesse-beantragen', 'PaesseBeantragen')->name('paesse-beantragen');
        Route::get('spielende-hinzufuegen', [TeamController::class, 'editCurrent'])->name('spielende-hinzufuegen');
        Route::inertia('mannschaft-bearbeiten', 'MannschaftBearbeiten')->name('mannschaft-bearbeiten');
    });
    Route::middleware(['club.access', 'role:verwaltung'])->group(function () {
        Route::inertia('hallenplan-bearbeiten', 'HallenplanBearbeiten')->name('hallenplan-bearbeiten');
        Route::inertia('bezahlung', 'Bezahlung')->name('bezahlung');
    });

});

require __DIR__.'/settings.php';
