<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\CwvController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Admin\ProductGroupController as AdminProductGroupController;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('auth.login'))->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

// Sla geselecteerde site op in sessie (gebruikt door de header site-selector)
Route::post('/select-site', function (\Illuminate\Http\Request $request) {
    $request->session()->put('current_site_id', $request->input('site_id'));
    return back();
})->name('select-site')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');

    Route::get('/priorities', [PriorityController::class, 'index'])->name('priorities.index');

    Route::get('/seo', [SeoController::class, 'index'])->name('seo.index');
    Route::get('/cwv', [CwvController::class, 'index'])->name('cwv.index');
    Route::get('/geo', [GeoController::class, 'index'])->name('geo.index');
    Route::get('/ads', [AdsController::class, 'index'])->name('ads.index');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('sites', AdminSiteController::class);
        Route::resource('users', AdminUserController::class);

        // Koppelingen + Synchronisatie: samengevat op één pagina
        Route::get('integrations', [AdminIntegrationController::class, 'index'])->name('integrations.index');
        Route::get('integrations/{site}/{platform}/edit', [AdminIntegrationController::class, 'edit'])->name('integrations.edit');
        Route::put('integrations/{site}/{platform}', [AdminIntegrationController::class, 'update'])->name('integrations.update');
        Route::post('integrations/{site}/{platform}/sync', [AdminIntegrationController::class, 'sync'])->name('integrations.sync');
        Route::delete('integrations/{site}/{platform}', [AdminIntegrationController::class, 'destroy'])->name('integrations.destroy');

        // Productgroepen
        Route::resource('product-groups', AdminProductGroupController::class);
        Route::post('product-groups/{productGroup}/assign-users', [AdminProductGroupController::class, 'assignUsers'])->name('product-groups.assign-users');
        Route::post('product-groups/{productGroup}/assign-pages', [AdminProductGroupController::class, 'assignPages'])->name('product-groups.assign-pages');
    });
});
