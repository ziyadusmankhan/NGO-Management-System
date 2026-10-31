<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

require __DIR__.'/auth.php';
require __DIR__.'/organization-exports.php';
require __DIR__.'/finance-welfare-exports.php';

use App\Http\Controllers\Dashboard\DashboardController;

Route::get('/dashboard', [
    DashboardController::class,
    'index',
])->middleware(['auth'])
    ->name('dashboard');

Route::get('/settings', function () {
    return view('settings.index');
})->middleware('auth')->name('settings.index');

use App\Http\Controllers\Welfare\BeneficiaryController;
use App\Http\Controllers\Welfare\BeneficiaryVerificationController;
use App\Http\Controllers\Welfare\WelfareApplicationAgreementController;
use App\Http\Controllers\Welfare\WelfareApplicationController;

Route::middleware(['auth'])->group(function () {

    Route::prefix('welfare/beneficiaries')
        ->name('welfare.beneficiaries.')
        ->middleware('permission:welfare.view')
        ->group(function () {

            /*
             * Management
             */
            Route::middleware('permission:welfare.beneficiaries.manage')
                ->group(function () {

                    Route::get('/create', [
                        BeneficiaryController::class,
                        'create',
                    ])->name('create');

                    Route::post('/', [
                        BeneficiaryController::class,
                        'store',
                    ])->name('store');

                    Route::get('/{beneficiary}/edit', [
                        BeneficiaryController::class,
                        'edit',
                    ])->name('edit');

                    Route::put('/{beneficiary}', [
                        BeneficiaryController::class,
                        'update',
                    ])->name('update');

                    Route::delete('/{beneficiary}', [
                        BeneficiaryController::class,
                        'destroy',
                    ])->name('destroy');
                });

            /*
             * Verification
             */
            Route::middleware('permission:welfare.beneficiaries.verify')
                ->group(function () {

                    Route::post('/{beneficiary}/verify', [
                        BeneficiaryController::class,
                        'verify',
                    ])->name('verify');

                    Route::post('/{beneficiary}/reject-verification', [
                        BeneficiaryController::class,
                        'rejectVerification',
                    ])->name('reject-verification');
                });

            /*
             * Approval
             */
            Route::middleware('permission:welfare.beneficiaries.approve')
                ->group(function () {

                    Route::post('/{beneficiary}/approve', [
                        BeneficiaryController::class,
                        'approve',
                    ])->name('approve');

                    Route::post('/{beneficiary}/reject-approval', [
                        BeneficiaryController::class,
                        'rejectApproval',
                    ])->name('reject-approval');
                });

            /*
             * Read-only routes
             */
            Route::get('/', [
                BeneficiaryController::class,
                'index',
            ])->name('index');

            Route::get('/{beneficiary}', [
                BeneficiaryController::class,
                'show',
            ])->name('show');
        });

});

Route::prefix('welfare/applications')
    ->name('welfare.applications.')
    ->middleware('auth')
    ->group(function () {

        Route::get(
            '/',
            [WelfareApplicationController::class, 'index']
        )
            ->middleware('permission:welfare.assistance.view')
            ->name('index');

        Route::get(
            '/create',
            [WelfareApplicationController::class, 'create']
        )
            ->middleware('permission:welfare.assistance.manage')
            ->name('create');

        Route::post(
            '/',
            [WelfareApplicationController::class, 'store']
        )
            ->middleware('permission:welfare.assistance.manage')
            ->name('store');

        // These decision routes intentionally replace the old direct approve/reject routes.
        Route::post(
            '/{application}/implementation-decision',
            [WelfareApplicationController::class, 'implementationDecision']
        )
            ->middleware('permission:welfare.applications.recommend')
            ->name('implementation-decision');

        Route::post(
            '/{application}/secretary-review',
            [WelfareApplicationController::class, 'secretaryReview']
        )
            ->middleware('permission:welfare.applications.review')
            ->name('secretary-review');

        Route::post(
            '/{application}/committee-decision',
            [WelfareApplicationController::class, 'committeeDecision']
        )
            ->middleware('permission:welfare.applications.approve')
            ->name('committee-decision');

        Route::get(
            '/{application}/documents/{document}',
            [WelfareApplicationController::class, 'document']
        )
            ->middleware('permission:welfare.assistance.view')
            ->name('documents.show');

        Route::get(
            '/{application}',
            [WelfareApplicationController::class, 'show']
        )
            ->middleware('permission:welfare.assistance.view')
            ->name('show');
    });

/*
 * Welfare application agreements.
 */
Route::prefix('welfare/applications/{application}/agreement')
    ->name('welfare.applications.agreement.')
    ->middleware('auth')
    ->group(function () {

        Route::get('/create', [
            WelfareApplicationAgreementController::class,
            'create',
        ])
            ->middleware('permission:welfare.agreements.manage')
            ->name('create');

        Route::post('/', [
            WelfareApplicationAgreementController::class,
            'store',
        ])
            ->middleware('permission:welfare.agreements.manage')
            ->name('store');

        Route::post('/prepare', [
            WelfareApplicationAgreementController::class,
            'prepare',
        ])
            ->middleware('permission:welfare.agreements.manage')
            ->name('prepare');

        Route::post('/signature/{party}', [
            WelfareApplicationAgreementController::class,
            'recordSignature',
        ])
            ->middleware('permission:welfare.agreements.manage')
            ->name('signature');

        Route::get('/evidence/{party}', [
            WelfareApplicationAgreementController::class,
            'evidence',
        ])
            ->middleware('permission:welfare.agreements.view')
            ->name('evidence');

        Route::get('/print', [
            WelfareApplicationAgreementController::class,
            'printable',
        ])
            ->middleware('permission:welfare.agreements.view')
            ->name('print');

        Route::get('/', [
            WelfareApplicationAgreementController::class,
            'show',
        ])
            ->middleware('permission:welfare.agreements.view')
            ->name('show');
    });

// use App\Http\Controllers\Welfare\BeneficiaryVerificationController;

Route::prefix('welfare/verification')
    ->name('welfare.verification.')
    ->middleware('auth')
    ->group(function () {

        /*
         * Queue / read-only access.
         */
        Route::get(
            '/',
            [
                BeneficiaryVerificationController::class,
                'index',
            ]
        )
            ->middleware('permission:welfare.view')
            ->name('index');

        /*
         * Assignment page MUST come before /{verification}.
         */
        Route::middleware(
            'permission:welfare.beneficiaries.verify'
        )->group(function () {

            Route::get(
                '/create',
                [
                    BeneficiaryVerificationController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    BeneficiaryVerificationController::class,
                    'store',
                ]
            )->name('store');

            Route::post(
                '/{verification}/start',
                [
                    BeneficiaryVerificationController::class,
                    'start',
                ]
            )->name('start');

            Route::post(
                '/{verification}/complete',
                [
                    BeneficiaryVerificationController::class,
                    'complete',
                ]
            )->name('complete');
        });

        /*
         * Evidence files (private disk, served via this route).
         */
        Route::get(
            '/{verification}/evidence/{evidence}',
            [
                BeneficiaryVerificationController::class,
                'evidence',
            ]
        )
            ->middleware('permission:welfare.view')
            ->name('evidence');

        /*
         * Detail page MUST come after /create.
         */
        Route::get(
            '/{verification}',
            [
                BeneficiaryVerificationController::class,
                'show',
            ]
        )
            ->middleware('permission:welfare.view')
            ->name('show');
    });

require __DIR__.'/business.php';
require __DIR__.'/projects.php';

require __DIR__.'/qard.php';
require __DIR__.'/tasks.php';

require __DIR__.'/procurement.php';
require __DIR__.'/meetings.php';

require __DIR__.'/documents.php';
require __DIR__.'/reports.php';

require __DIR__.'/audit.php';
require __DIR__.'/phase12.php';

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
])->middleware('auth')
    ->middlewareFor(['index', 'show'], 'permission:members.view')
    ->middlewareFor(['create', 'store'], 'permission:members.create')
    ->middlewareFor(['edit', 'update'], 'permission:members.update')
    ->middlewareFor('destroy', 'permission:members.delete');

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
])->middleware('auth')
    ->middlewareFor(['index', 'show'], 'permission:committees.view')
    ->middlewareFor(['create', 'store'], 'permission:committees.create')
    ->middlewareFor(['edit', 'update'], 'permission:committees.update');

Route::delete('/committees/{committee}', [CommitteeController::class, 'destroy'])
    ->middleware(['auth', 'permission:committees.delete'])
    ->name('committees.destroy');

Route::post(
    '/committees/{committee}/members',
    [CommitteeController::class, 'assignMember']
)->middleware(['auth', 'permission:committees.members.manage'])->name('committees.members.assign');

Route::delete(
    '/committees/{committee}/members/{member}',
    [CommitteeController::class, 'removeMember']
)->middleware(['auth', 'permission:committees.members.manage'])->name('committees.members.remove');

Route::put(
    '/committees/{committee}/members/{member}',
    [CommitteeController::class, 'updateMember']
)->middleware(['auth', 'permission:committees.members.manage'])->name('committees.members.update');

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

Route::middleware(['auth', 'permission:roles.manage'])->group(function () {

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
    ->middleware([
        'auth',
        'permission:finance.view',
    ])
    ->group(function () {

        Route::get(
            '/',
            [DonationController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [DonationController::class, 'create']
        )
            ->middleware('permission:finance.donations.manage')
            ->name('create');

        Route::post(
            '/',
            [DonationController::class, 'store']
        )
            ->middleware('permission:finance.donations.manage')
            ->name('store');

        Route::get(
            '/{donation}',
            [DonationController::class, 'show']
        )->name('show');

        Route::get(
            '/{donation}/edit',
            [DonationController::class, 'edit']
        )
            ->middleware('permission:finance.donations.manage')
            ->name('edit');

        Route::put(
            '/{donation}',
            [DonationController::class, 'update']
        )
            ->middleware('permission:finance.donations.manage')
            ->name('update');

        Route::delete(
            '/{donation}',
            [DonationController::class, 'destroy']
        )
            ->middleware('permission:finance.donations.manage')
            ->name('destroy');
    });

use App\Http\Controllers\Finance\FundController;

Route::prefix('finance/funds')
    ->name('finance.funds.')
    ->middleware([
        'auth',
        'permission:finance.view',
    ])
    ->group(function () {

        Route::get(
            '/',
            [FundController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [FundController::class, 'create']
        )
            ->middleware('permission:finance.funds.manage')
            ->name('create');

        Route::post(
            '/',
            [FundController::class, 'store']
        )
            ->middleware('permission:finance.funds.manage')
            ->name('store');

        Route::get(
            '/{fund}',
            [FundController::class, 'show']
        )->withTrashed()->name('show');

        Route::get(
            '/{fund}/edit',
            [FundController::class, 'edit']
        )
            ->middleware('permission:finance.funds.manage')
            ->name('edit');

        Route::put(
            '/{fund}',
            [FundController::class, 'update']
        )
            ->middleware('permission:finance.funds.manage')
            ->name('update');

        Route::delete(
            '/{fund}',
            [FundController::class, 'destroy']
        )
            ->middleware('permission:finance.funds.manage')
            ->name('destroy');
    });

use App\Http\Controllers\Finance\ExpenseController;

Route::prefix('finance/expenses')
    ->name('finance.expenses.')
    ->middleware([
        'auth',
        'permission:finance.view',
    ])
    ->controller(ExpenseController::class)
    ->group(function () {

        Route::get(
            '/',
            'index'
        )->name('index');

        Route::get(
            '/create',
            'create'
        )
            ->middleware('permission:finance.expenses.manage')
            ->name('create');

        Route::post(
            '/',
            'store'
        )
            ->middleware('permission:finance.expenses.manage')
            ->name('store');

        // /{expense}/edit MUST come before /{expense}
        Route::get(
            '/{expense}/edit',
            'edit'
        )
            ->middleware('permission:finance.expenses.manage')
            ->name('edit');

        Route::get(
            '/{expense}',
            'show'
        )->name('show');

        Route::put(
            '/{expense}',
            'update'
        )
            ->middleware('permission:finance.expenses.manage')
            ->name('update');

        Route::delete(
            '/{expense}',
            'destroy'
        )
            ->middleware('permission:finance.expenses.manage')
            ->name('destroy');

        Route::post(
            '/{expense}/approve',
            'approve'
        )
            ->middleware('permission:finance.expenses.approve')
            ->name('approve');

        Route::post(
            '/{expense}/reject',
            'reject'
        )
            ->middleware('permission:finance.expenses.approve')
            ->name('reject');

        Route::post(
            '/{expense}/paid',
            'markPaid'
        )
            ->middleware('permission:finance.expenses.approve')
            ->name('paid');
    });

use App\Http\Controllers\Finance\RestrictedFundController;

Route::get(
    '/finance/restricted-funds',
    [
        RestrictedFundController::class,
        'index',
    ]
)
    ->middleware([
        'auth',
        'permission:finance.view',
    ])
    ->name('finance.restricted-funds.index');

use App\Http\Controllers\Finance\TransactionController;

Route::middleware([
    'auth',
    'permission:finance.view',
])->group(function () {

    Route::get(
        '/finance/transactions',
        [
            TransactionController::class,
            'index',
        ]
    )->name('finance.transactions.index');

    Route::get(
        '/finance/transactions/{transaction}',
        [
            TransactionController::class,
            'show',
        ]
    )->withTrashed()->name('finance.transactions.show');
});

use App\Http\Controllers\Finance\FinanceReportController;

Route::get(
    '/finance/reports',
    [
        FinanceReportController::class,
        'index',
    ]
)
    ->middleware([
        'auth',
        'permission:finance.reports.view',
    ])
    ->name('finance.reports.index');

use App\Http\Controllers\Welfare\WelfareAssistanceController;
use App\Http\Controllers\Welfare\WelfareAssistancePaymentController;
use App\Http\Controllers\Welfare\WelfareAssistanceReviewController;

Route::middleware(['auth'])->group(function () {

    Route::prefix('welfare/assistance')
        ->name('welfare.assistance.')
        ->middleware('permission:welfare.assistance.view')
        ->group(function () {

            Route::get(
                '/',
                [WelfareAssistanceController::class, 'index']
            )->name('index');

            Route::middleware('permission:welfare.assistance.manage')
                ->group(function () {

                    Route::get(
                        '/create',
                        [WelfareAssistanceController::class, 'create']
                    )->name('create');

                    Route::post(
                        '/',
                        [WelfareAssistanceController::class, 'store']
                    )->name('store');

                    Route::get(
                        '/{assistance}/edit',
                        [WelfareAssistanceController::class, 'edit']
                    )->name('edit');

                    Route::put(
                        '/{assistance}',
                        [WelfareAssistanceController::class, 'update']
                    )->name('update');
                });

            Route::post('/{assistance}/resubmit', [WelfareAssistanceController::class, 'resubmit'])
                ->middleware('permission:welfare.assistance.manage')->name('resubmit');

            Route::post('/{assistance}/close', [WelfareAssistanceController::class, 'close'])
                ->middleware(['permission:welfare.reviews.view', 'permission:welfare.reviews.close'])->name('close');

            Route::middleware('permission:welfare.assistance.approve')
                ->group(function () {

                    Route::post(
                        '/{assistance}/approve',
                        [WelfareAssistanceController::class, 'approve']
                    )->name('approve');

                    Route::post(
                        '/{assistance}/reject',
                        [WelfareAssistanceController::class, 'reject']
                    )->name('reject');
                });

            Route::middleware('permission:welfare.reviews.view')->group(function () {
                Route::get('/reviews', [WelfareAssistanceReviewController::class, 'index'])->name('reviews.index');
                Route::middleware('permission:welfare.reviews.manage')->group(function () {
                    Route::get('/{assistance}/reviews/create', [WelfareAssistanceReviewController::class, 'create'])->name('reviews.create');
                    Route::post('/{assistance}/reviews', [WelfareAssistanceReviewController::class, 'store'])->name('reviews.store');
                });
            });

            Route::middleware('permission:welfare.assistance.manage')->group(function () {
                Route::get(
                    '/{assistance}/payments/create',
                    [WelfareAssistancePaymentController::class, 'create']
                )->name('payments.create');

                Route::post(
                    '/{assistance}/payments',
                    [WelfareAssistancePaymentController::class, 'store']
                )->name('payments.store');

                Route::post(
                    '/{assistance}/payments/{payment}/void',
                    [WelfareAssistancePaymentController::class, 'void']
                )->name('payments.void');
            });

            Route::get(
                '/{assistance}/payments/{payment}',
                [WelfareAssistancePaymentController::class, 'show']
            )->name('payments.show');

            Route::get(
                '/{assistance}',
                [WelfareAssistanceController::class, 'show']
            )->name('show');
        });
});
