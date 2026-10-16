<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

require __DIR__.'/auth.php';

// <?php

// use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');

Route::get('/settings', function () {
    return view('settings.index');
})->name('settings.index');

Route::get('/welfare/beneficiaries', function () {
    return view('welfare.beneficiaries.index');
})->name('beneficiaries.index');

Route::get('/welfare/beneficiaries/create', function () {
    return view('welfare.beneficiaries.create');
})->name('beneficiaries.create');

Route::get('/welfare/beneficiaries/{id}', function ($id) {
    return view('welfare.beneficiaries.show', ['id' => $id]);
})->name('beneficiaries.show');


Route::get('/welfare/applications', function () {
    return view('welfare.applications.index');
})->name('welfare.applications.index');

Route::get('/welfare/applications/create', function () {
    return view('welfare.applications.create');
})->name('welfare.applications.create');

Route::get('/welfare/applications/{id}', function ($id) {
    return view('welfare.applications.show', ['id' => $id]);
})->name('welfare.applications.show');


Route::get('/welfare/verification', function () {
    return view('welfare.verification.index');
})->name('welfare.verification.index');

Route::get('/welfare/verification/{id}', function ($id) {
    return view('welfare.verification.show', ['id' => $id]);
})->name('welfare.verification.show');


Route::get('/welfare/assistance', function () {
    return view('welfare.assistance.index');
})->name('welfare.assistance.index');

Route::get('/welfare/assistance/create', function () {
    return view('welfare.assistance.create');
})->name('welfare.assistance.create');

Route::get('/welfare/assistance/{id}', function ($id) {
    return view('welfare.assistance.show', ['id' => $id]);
})->name('welfare.assistance.show');

Route::get('/business/applications', function () {
    return view('business.applications.index');
})->name('business.applications.index');

Route::get('/business/applications/create', function () {
    return view('business.applications.create');
})->name('business.applications.create');

Route::get('/business/applications/{id}/viability', function ($id) {
    return view('business.applications.viability', [
        'id' => $id
    ]);
})->name('business.applications.viability');

Route::get('/business/applications/{id}/recommendation', function ($id) {
    return view('business.applications.recommendation', [
        'id' => $id
    ]);
})->name('business.applications.recommendation');

Route::get('/business/applications/{id}/approval', function ($id) {
    return view('business.applications.approval', [
        'id' => $id
    ]);
})->name('business.applications.approval');

Route::get('/business/applications/{id}/agreement', function ($id) {
    return view('business.applications.agreement', [
        'id' => $id
    ]);
})->name('business.applications.agreement');

Route::get('/business/applications/{id}/disbursement', function ($id) {
    return view('business.applications.disbursement', [
        'id' => $id
    ]);
})->name('business.applications.disbursement');

Route::get('/business/applications/{id}/monitoring', function ($id) {
    return view('business.applications.monitoring', [
        'id' => $id
    ]);
})->name('business.applications.monitoring');

Route::get('/business/applications/{id}', function ($id) {
    return view('business.applications.show', [
        'id' => $id
    ]);
})->name('business.applications.show');

Route::get('/qard', function () {
    return view('qard.index');
})->name('qard.index');

Route::get('/qard/create', function () {
    return view('qard.create');
})->name('qard.create');

Route::get('/qard/{id}/repayment', function ($id) {
    return view('qard.repayment', [
        'id' => $id
    ]);
})->name('qard.repayment');

Route::get('/qard/{id}/recovery', function ($id) {
    return view('qard.recovery', [
        'id' => $id
    ]);
})->name('qard.recovery');

Route::get('/qard/{id}/recycling', function ($id) {
    return view('qard.recycling', [
        'id' => $id
    ]);
})->name('qard.recycling');

Route::get('/qard/{id}', function ($id) {
    return view('qard.show', [
        'id' => $id
    ]);
})->name('qard.show');
// Route::get('/finance/expenses', function () {
//     return view('finance.expenses.index');
// })->name('finance.expenses.index');

// Route::get('/finance/expenses/create', function () {
//     return view('finance.expenses.create');
// })->name('finance.expenses.create');

// Route::get('/finance/expenses/{id}', function ($id) {
//     return view('finance.expenses.show', [
//         'id' => $id
//     ]);
// })->name('finance.expenses.show');

Route::get('/operations/tasks', function () {
    return view('operations.tasks.index');
})->name('operations.tasks.index');

Route::get('/operations/tasks/create', function () {
    return view('operations.tasks.create');
})->name('operations.tasks.create');

Route::get('/operations/tasks/{id}', function ($id) {
    return view('operations.tasks.show', [
        'id' => $id
    ]);
})->name('operations.tasks.show');

Route::get('/operations/procurement', function () {
    return view('operations.procurement.index');
})->name('operations.procurement.index');

Route::get('/operations/procurement/create', function () {
    return view('operations.procurement.create');
})->name('operations.procurement.create');

Route::get('/operations/procurement/{id}', function ($id) {
    return view('operations.procurement.show', [
        'id' => $id
    ]);
})->name('operations.procurement.show');

Route::get('/operations/meetings', function () {
    return view('operations.meetings.index');
})->name('operations.meetings.index');

Route::get('/operations/meetings/create', function () {
    return view('operations.meetings.create');
})->name('operations.meetings.create');

Route::get('/operations/meetings/{id}', function ($id) {
    return view('operations.meetings.show', [
        'id' => $id
    ]);
})->name('operations.meetings.show');

Route::get('/records/documents', function () {
    return view('records.documents.index');
})->name('records.documents.index');

Route::get('/records/documents/create', function () {
    return view('records.documents.create');
})->name('records.documents.create');

Route::get('/records/documents/{id}', function ($id) {
    return view('records.documents.show', [
        'id' => $id
    ]);
})->name('records.documents.show');

Route::get('/records/reports', function () {
    return view('records.reports.index');
})->name('records.reports.index');

Route::get('/records/reports/{id}', function ($id) {
    return view('records.reports.show', [
        'id' => $id
    ]);
})->name('records.reports.show');

Route::get('/records/audit', function () {
    return view('records.audit.index');
})->name('records.audit.index');

use App\Http\Controllers\Members\MemberController;

Route::resource(
    'members',
    MemberController::class
)->only([
    'index',
    'create',
    'store',
    'show',
    'edit',
    'update',
    'destroy',
]);

use App\Http\Controllers\Committees\CommitteeController;

Route::resource(
    'committees',
    CommitteeController::class
)->only([
    'index',
    'create',
    'store',
    'show',
    'edit',
    'update',
    'destroy',
]);

Route::post(
    '/committees/{committee}/members',
    [CommitteeController::class, 'assignMember']
)->name('committees.members.assign');

Route::delete(
    '/committees/{committee}/members/{member}',
    [CommitteeController::class, 'removeMember']
)->name('committees.members.remove');

Route::put(
    '/committees/{committee}/members/{member}',
    [CommitteeController::class, 'updateMember']
)->name('committees.members.update');

use App\Http\Controllers\Users\UserController;

Route::middleware([
    'auth',
    'permission:users.view',
])->group(function () {

    Route::get(
        '/users',
        [UserController::class, 'index']
    )->name('users.index');
});


Route::middleware([
    'auth',
    'permission:users.manage',
])->group(function () {

    // NOTE: '/users/create' must be registered before the
    // '/users/{user}' wildcard route below. Laravel matches
    // routes in registration order, so if the wildcard route
    // came first, a request to /users/create would match
    // {user} = "create" instead, and fail with a 404 when it
    // couldn't find a user with that id. This was the cause
    // of the "Add User" 404 bug.
    Route::get(
        '/users/create',
        [UserController::class, 'create']
    )->name('users.create');

    Route::post(
        '/users',
        [UserController::class, 'store']
    )->name('users.store');

    Route::get(
        '/users/{user}/edit',
        [UserController::class, 'edit']
    )->name('users.edit');

    Route::put(
        '/users/{user}',
        [UserController::class, 'update']
    )->name('users.update');

    Route::delete(
        '/users/{user}',
        [UserController::class, 'destroy']
    )->name('users.destroy');
});


use App\Http\Controllers\Users\RoleController;

Route::middleware('auth')->group(function () {

    // Registered before '/users/{user}' below so that the
    // wildcard route does not swallow it (same class of bug
    // noted for '/users/create' above: a wildcard route must
    // never be registered before the literal routes it could
    // match, or "/users/roles" would be treated as
    // "/users/{user}" with user = "roles").
    Route::get(
        '/users/roles',
        [RoleController::class, 'index']
    )->name('users.roles');

    Route::put(
        '/users/roles/{role}',
        [RoleController::class, 'update']
    )->name('users.roles.update');

});


Route::middleware([
    'auth',
    'permission:users.view',
])->group(function () {

    // Registered after '/users/create' and '/users/roles' above
    // so that this wildcard route does not shadow them (same class
    // of bug noted for the create route: a wildcard route must
    // never be registered before the literal routes it could
    // swallow).
    Route::get(
        '/users/{user}',
        [UserController::class, 'show']
    )->name('users.show');
});

use App\Http\Controllers\Finance\DonationController;

Route::prefix('finance/donations')
    ->name('finance.donations.')
    ->group(function () {

        Route::get(
            '/',
            [DonationController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [DonationController::class, 'create']
        )->name('create');

        Route::post(
            '/',
            [DonationController::class, 'store']
        )->name('store');

        Route::get(
            '/{donation}',
            [DonationController::class, 'show']
        )->name('show');

        Route::get(
            '/{donation}/edit',
            [DonationController::class, 'edit']
        )->name('edit');

        Route::put(
            '/{donation}',
            [DonationController::class, 'update']
        )->name('update');

        Route::delete(
            '/{donation}',
            [DonationController::class, 'destroy']
        )->name('destroy');
    });

use App\Http\Controllers\Finance\FundController;

Route::prefix('finance/funds')
    ->name('finance.funds.')
    ->group(function () {

        Route::get(
            '/',
            [FundController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [FundController::class, 'create']
        )->name('create');

        Route::post(
            '/',
            [FundController::class, 'store']
        )->name('store');

        Route::get(
            '/{fund}',
            [FundController::class, 'show']
        )->name('show');

        Route::get(
            '/{fund}/edit',
            [FundController::class, 'edit']
        )->name('edit');

        Route::put(
            '/{fund}',
            [FundController::class, 'update']
        )->name('update');

        Route::delete(
            '/{fund}',
            [FundController::class, 'destroy']
        )->name('destroy');
    });
use App\Http\Controllers\Finance\ExpenseController;

Route::prefix('finance/expenses')
    ->name('finance.expenses.')
    ->middleware(['auth']) // keep whatever permission middleware you already use here
    ->controller(ExpenseController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
 
        // /{expense}/edit MUST come before /{expense}
        // otherwise Laravel treats "edit" as the {expense} id.
        Route::get('/{expense}/edit', 'edit')->name('edit');
        Route::get('/{expense}', 'show')->name('show');
 
        Route::put('/{expense}', 'update')->name('update');
        Route::delete('/{expense}', 'destroy')->name('destroy');
 
        Route::post('/{expense}/approve', 'approve')->name('approve');
        Route::post('/{expense}/reject', 'reject')->name('reject');
        Route::post('/{expense}/paid', 'markPaid')->name('paid');
    });


use App\Http\Controllers\Finance\RestrictedFundController;

Route::get('/finance/restricted-funds', [
    RestrictedFundController::class,
    'index',
])->name('finance.restricted-funds.index');
