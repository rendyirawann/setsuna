<?php

use Illuminate\Support\Facades\Route;

// Publik (landing + pemesanan paket)
use App\Http\Controllers\Frontend\LandingController;
use App\Http\Controllers\Frontend\OrderController;

// Portal acara (kamera tamu + galeri)
use App\Http\Controllers\Portal\CameraController;
use App\Http\Controllers\Portal\PortalController;

// Manajemen acara
use App\Http\Controllers\Backend\Events\ClientController;
use App\Http\Controllers\Backend\Events\EventController;
use App\Http\Controllers\Backend\Events\EventGuestController;
use App\Http\Controllers\Backend\Events\EventMediaController;
use App\Http\Controllers\Backend\Events\PlanController;
use App\Http\Controllers\Backend\Events\SubscriptionController;

// Dashboard
use App\Http\Controllers\Backend\Dashboard\DashboardAdminController;

// My profile
use App\Http\Controllers\Backend\MyProfile\AccountController;
use App\Http\Controllers\Backend\MyProfile\ActivityController;
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;

// User management
use App\Http\Controllers\Backend\UserManagement\RoleController;
use App\Http\Controllers\Backend\UserManagement\UserController;

// Help / system
use App\Http\Controllers\Backend\Help\LogActivityController;
use App\Http\Controllers\Backend\Settings\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Publik — etalase produk dan pemesanan paket
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/harga', [LandingController::class, 'pricing'])->name('pricing');
Route::get('/faq', [LandingController::class, 'faq'])->name('faq');

Route::get('/pesan/{plan:slug}', [OrderController::class, 'create'])->name('order.create');
Route::post('/pesan/{plan:slug}', [OrderController::class, 'store'])
    ->middleware('throttle:write')
    ->name('order.store');
Route::get('/pesanan/{subscription}', [OrderController::class, 'success'])->name('order.success');

Route::middleware(['auth', 'forbid-banned-user'])->group(function () {

    // --- Dashboard (every authenticated role) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // --- My account (every authenticated user) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- Activity log ---
    // Accessible to every authenticated user; the controller scopes the query
    // so a non-Superadmin only ever sees the entries they caused.
    Route::get('/admin/log-activity', [LogActivityController::class, 'index'])->name('log-activity.index');
    Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    Route::get('/admin/log-activity/{id}/detail', [LogActivityController::class, 'detail'])->name('log-activity.detail');
    Route::get('/admin/log-activity/{id}', [LogActivityController::class, 'show'])->name('log-activity.show');

    // Shared helpers used by the role/user forms.
    Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

    /*
    |----------------------------------------------------------------------
    | Write operations — tighter rate limit than plain page reads.
    |----------------------------------------------------------------------
    */
    Route::middleware('throttle:write')->group(function () {

        Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');
        Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');
        Route::post('/admin/my-security/logout-other-devices', [SecurityController::class, 'logoutOtherDevices'])
            ->name('security.logout-other-devices');

        // --- Settings (Superadmin) ---
        Route::middleware('can:view_resources')->group(function () {
            Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/admin/settings/regenerate-branding', [SettingController::class, 'regenerateBranding'])
                ->name('settings.branding');

            Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
            Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
            Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
            Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');
            Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
        });
    });

    // --- Profile & security pages (resource controllers keep their own verbs) ---
    Route::resource('/admin/my-profile', ProfileController::class);
    Route::resource('/admin/my-security', SecurityController::class);

    // --- Settings page (read) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])
        ->middleware('can:view_resources')
        ->name('settings.index');

    /*
    |----------------------------------------------------------------------
    | User & role management — Superadmin only.
    |----------------------------------------------------------------------
    */
    Route::middleware('can:view_resources')->group(function () {
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');

        Route::resource('/admin/roles', RoleController::class);
        Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
    });

    /*
    |----------------------------------------------------------------------
    | Manajemen acara — paket, klien, acara, pesanan.
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // --- Paket langganan ---
        Route::get('get-dataplan', [PlanController::class, 'getData'])->name('plans.data');
        Route::resource('plans', PlanController::class)->except('show');

        // --- Klien ---
        Route::get('get-dataclient', [ClientController::class, 'getData'])->name('clients.data');
        Route::resource('clients', ClientController::class);

        // --- Pesanan / langganan ---
        Route::get('get-datasubscription', [SubscriptionController::class, 'getData'])->name('subscriptions.data');
        Route::post('subscriptions/{subscription}/paid', [SubscriptionController::class, 'markPaid'])->name('subscriptions.paid');
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::resource('subscriptions', SubscriptionController::class);

        // --- Acara ---
        Route::get('get-dataevent', [EventController::class, 'getData'])->name('events.data');
        Route::get('events/{event}/qr', [EventController::class, 'sign'])->name('events.sign');
        Route::get('events/{event}/qr/download', [EventController::class, 'qrDownload'])->name('events.qr.download');
        Route::get('events/{event}/download-all', [EventController::class, 'downloadAll'])->name('events.download-all');
        Route::post('events/{event}/status', [EventController::class, 'status'])->name('events.status');
        Route::post('events/{event}/reveal', [EventController::class, 'reveal'])->name('events.reveal');
        Route::post('events/{event}/rotate-qr', [EventController::class, 'rotateQr'])->name('events.rotate-qr');

        // --- Media & tamu satu acara ---
        Route::get('events/{event}/media', [EventMediaController::class, 'index'])->name('events.media');
        Route::post('events/{event}/media/bulk', [EventMediaController::class, 'bulk'])->name('events.media.bulk');
        Route::post('events/{event}/media/{medium}/status', [EventMediaController::class, 'status'])->name('events.media.status');
        Route::post('events/{event}/media/{medium}/feature', [EventMediaController::class, 'feature'])->name('events.media.feature');
        Route::delete('events/{event}/media/{medium}', [EventMediaController::class, 'destroy'])->name('events.media.destroy');

        Route::get('events/{event}/guests', [EventGuestController::class, 'index'])->name('events.guests');
        Route::post('events/{event}/guests/{guest}/grant', [EventGuestController::class, 'grant'])->name('events.guests.grant');
        Route::post('events/{event}/guests/{guest}/block', [EventGuestController::class, 'toggleBlock'])->name('events.guests.block');
        Route::delete('events/{event}/guests/{guest}', [EventGuestController::class, 'destroy'])->name('events.guests.destroy');

        Route::resource('events', EventController::class);
    });
});

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Portal acara — harus paling bawah
|--------------------------------------------------------------------------
|
| Tiap klien mendapat "sub folder" sendiri di root (mis. /pernikahan-emma):
| di situlah kamera tamu dibuka dan galeri acaranya tampil. Pola slug
| dibatasi dan slug sistem ditolak lewat config('setsuna.reserved_slugs'),
| jadi rute ini tidak bisa menabrak halaman aplikasi.
|
*/

Route::prefix('{event:slug}')
    ->where(['event' => '[a-z0-9][a-z0-9\-]{1,59}'])
    ->group(function () {
        Route::get('/', [PortalController::class, 'show'])->name('portal.show');
        Route::get('/kamera', [CameraController::class, 'show'])->name('portal.camera');
        Route::get('/galeri', [PortalController::class, 'gallery'])->name('portal.gallery');
        Route::get('/rollku', [PortalController::class, 'roll'])->name('portal.roll');
        Route::get('/tamu/{guest}', [PortalController::class, 'guestAlbum'])->name('portal.guest');
        Route::get('/media/{media}/unduh', [PortalController::class, 'download'])->name('portal.media.download');

        // Endpoint kamera (dipanggil dari halaman kamera, bukan API publik).
        // Limiter "capture" dikunci per perangkat tamu, bukan per IP.
        Route::middleware('throttle:capture')->group(function () {
            Route::post('/api/tamu', [CameraController::class, 'register'])->name('portal.guest.register');
            Route::post('/api/tangkap', [CameraController::class, 'capture'])->name('portal.capture');
        });

        Route::get('/api/kuota', [CameraController::class, 'quota'])->name('portal.quota');
        Route::get('/api/roll', [CameraController::class, 'roll'])->name('portal.roll.api');

        // Umpan media terbaru: album dan roll memakainya untuk memuat
        // jepretan baru tanpa perlu memuat ulang halaman.
        Route::get('/api/media', [PortalController::class, 'feed'])->name('portal.feed');
    });
