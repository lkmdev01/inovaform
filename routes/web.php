<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormBuilderController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PublicFunnelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicFunnelController::class, 'home'])->name('home');
Route::get('media/{path}', [FunnelController::class, 'showMedia'])
    ->where('path', '.*')
    ->name('funnels.media.show');

Route::get('f/{slug}', [PublicFunnelController::class, 'show'])->name('funnels.public.show');
Route::post('f/{slug}/submit', [PublicFunnelController::class, 'submit'])->name('funnels.public.submit');
Route::post('submit', [PublicFunnelController::class, 'submitForDomain'])->name('funnels.public.domain.submit');

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('forms/builder', [FormBuilderController::class, 'index'])->name('forms.builder');
    Route::post('forms/builder', [FormBuilderController::class, 'store'])->name('forms.store');
    Route::get('funnels', [FunnelController::class, 'index'])->name('funnels.index');
    Route::post('funnels', [FunnelController::class, 'store'])->name('funnels.store');
    Route::post('funnels/import', [FunnelController::class, 'import'])->name('funnels.import');
    Route::delete('funnels/{funnel}', [FunnelController::class, 'destroy'])->name('funnels.destroy');
    Route::post('funnels/{funnel}/duplicate', [FunnelController::class, 'duplicate'])->name('funnels.duplicate');
    Route::get('funnels/{funnel}/export', [FunnelController::class, 'export'])->name('funnels.export');
    Route::post('funnels/{funnel}/templates', [FunnelController::class, 'storeTemplate'])->name('funnels.templates.store');
    Route::patch('funnels/{funnel}', [FunnelController::class, 'update'])->name('funnels.update');
    Route::post('funnels/{funnel}/media', [FunnelController::class, 'uploadMedia'])->name('funnels.media.upload');
    Route::patch('funnels/{funnel}/design', [FunnelController::class, 'updateDesign'])->name('funnels.design.update');
    Route::post('funnels/{funnel}/share', [FunnelController::class, 'share'])->name('funnels.share');
    Route::get('funnels/{funnel}/builder', [FunnelController::class, 'builder'])->name('funnels.builder');
    Route::get('funnels/{funnel}/flow', [FunnelController::class, 'flow'])->name('funnels.flow');
    Route::get('funnels/{funnel}/design', [FunnelController::class, 'design'])->name('funnels.design');
    Route::get('funnels/{funnel}/settings', [FunnelController::class, 'settings'])->name('funnels.settings');
    Route::patch('funnels/{funnel}/settings', [FunnelController::class, 'updateSettings'])->name('funnels.settings.update');
    Route::get('funnels/{funnel}/leads', [FunnelController::class, 'leads'])->name('funnels.leads');
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::get('leads/export/csv', [LeadController::class, 'export'])->name('leads.export');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
});

require __DIR__.'/settings.php';

Route::get('{customDomainPath?}', [PublicFunnelController::class, 'showForDomain'])
    ->where('customDomainPath', '.*')
    ->name('funnels.public.domain.show');
