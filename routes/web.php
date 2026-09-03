<?php

use App\Http\Controllers\StartseiteController;
use App\Http\Controllers\Teams\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', StartseiteController::class)->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');

    Route::inertia('spielplan', 'Spielplan')->name('spielplan');
    Route::inertia('hallenplan', 'Hallenplan')->name('hallenplan');
    Route::inertia('mein-team', 'MeinTeam')->name('mein-team');
    Route::inertia('profil', 'Profil')->name('profil');
    Route::inertia('paesse-beantragen', 'PaesseBeantragen')->name('paesse-beantragen');
    Route::inertia('spielende-hinzufuegen', 'SpielendeHinzufuegen')->name('spielende-hinzufuegen');
    Route::inertia('mannschaft-bearbeiten', 'MannschaftBearbeiten')->name('mannschaft-bearbeiten');
    Route::inertia('hallenplan-bearbeiten', 'HallenplanBearbeiten')->name('hallenplan-bearbeiten');
    Route::inertia('bezahlung', 'Bezahlung')->name('bezahlung');
});

require __DIR__.'/settings.php';
