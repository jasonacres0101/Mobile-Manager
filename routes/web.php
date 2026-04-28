<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Webhook\GoCardlessWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    $user = request()->user();

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'customer.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/webhooks/gocardless', GoCardlessWebhookController::class)->name('webhooks.gocardless');
Route::get('/customer/direct-debit/callback', [Customer\DirectDebitController::class, 'callback'])->name('customer.direct-debit.callback');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/sync', [Admin\DashboardController::class, 'sync'])->name('sync');
    Route::get('/companies', [Admin\CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [Admin\CompanyController::class, 'show'])->name('companies.show');
    Route::put('/companies/{company}/auto-collect', [Admin\CompanyController::class, 'updateAutoCollect'])->name('companies.auto-collect.update');
    Route::post('/companies/{company}/gocardless/refresh-mandates', [Admin\CompanyController::class, 'refreshGoCardlessMandates'])->name('companies.gocardless.refresh-mandates');
    Route::post('/companies/{company}/gocardless/refresh-payments', [Admin\CompanyController::class, 'refreshGoCardlessPayments'])->name('companies.gocardless.refresh-payments');
    Route::get('/agreements', [Admin\AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/sims', [Admin\SimController::class, 'index'])->name('sims.index');
    Route::get('/fibre-connections', [Admin\FibreConnectionController::class, 'index'])->name('fibre-connections.index');
    Route::get('/jola-sims', [Admin\SimController::class, 'jola'])->name('sims.jola');
    Route::get('/jola-customers', [Admin\JolaCustomerController::class, 'index'])->name('jola-customers.index');
    Route::post('/jola-customers/sync', [Admin\JolaCustomerController::class, 'sync'])->name('jola-customers.sync');
    Route::get('/jola-customers/{jolaCustomer}', [Admin\JolaCustomerController::class, 'show'])->name('jola-customers.show');
    Route::get('/jola-customers/{jolaCustomer}/sims/{jolaSimId}', [Admin\JolaCustomerController::class, 'showSim'])->name('jola-customers.sims.show');
    Route::get('/jola-products', [Admin\JolaProductController::class, 'index'])->name('jola-products.index');
    Route::post('/jola-products/sync', [Admin\JolaProductController::class, 'sync'])->name('jola-products.sync');
    Route::get('/invoices', [Admin\InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices/{invoice}/collect', [Admin\InvoiceController::class, 'collect'])->name('invoices.collect');
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/refresh-gocardless', [Admin\PaymentController::class, 'refresh'])->name('payments.refresh-gocardless');
    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/gocardless', [Admin\SettingsController::class, 'updateGoCardless'])->name('settings.gocardless.update');
    Route::put('/settings/connectwise', [Admin\SettingsController::class, 'updateConnectWise'])->name('settings.connectwise.update');
    Route::post('/settings/connectwise/test', [Admin\SettingsController::class, 'testConnectWise'])->name('settings.connectwise.test');
    Route::post('/settings/connectwise/sync', [Admin\SettingsController::class, 'syncConnectWise'])->name('settings.connectwise.sync');
    Route::put('/settings/jola', [Admin\SettingsController::class, 'updateMobileManager'])->name('settings.jola.update');
    Route::post('/settings/jola/test', [Admin\SettingsController::class, 'testMobileManager'])->name('settings.jola.test');
    Route::put('/settings/microsoft365', [Admin\SettingsController::class, 'updateMicrosoft365'])->name('settings.microsoft365.update');
    Route::post('/settings/microsoft365/test', [Admin\SettingsController::class, 'testMicrosoft365'])->name('settings.microsoft365.test');
    Route::post('/settings/users', [Admin\SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::match(['post', 'put'], '/settings/users/welcome-email-template', [Admin\SettingsController::class, 'updateWelcomeEmail'])->name('settings.users.welcome-email-template');
    Route::post('/settings/users/welcome-email-template/test', [Admin\SettingsController::class, 'testWelcomeEmail'])->name('settings.users.welcome-email-template.test');
    Route::post('/settings/users/{user}/welcome-email', [Admin\SettingsController::class, 'sendWelcomeEmail'])->name('settings.users.welcome-email');
    Route::put('/settings/users/{user}', [Admin\SettingsController::class, 'updateUser'])->name('settings.users.update');
});

Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', Customer\DashboardController::class)->name('dashboard');
    Route::get('/sims', [Customer\SimController::class, 'index'])->name('sims.index');
    Route::get('/fibre-connections', [Customer\FibreConnectionController::class, 'index'])->name('fibre-connections.index');
    Route::get('/invoices', [Customer\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/direct-debit/setup', [Customer\DirectDebitController::class, 'setup'])->name('direct-debit.setup');
    Route::post('/direct-debit/refresh', [Customer\DirectDebitController::class, 'refresh'])->name('direct-debit.refresh');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
