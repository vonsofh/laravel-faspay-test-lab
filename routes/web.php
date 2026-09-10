<?php

use Illuminate\Support\Facades\Route;
use Vonso\FaspayTestLab\Http\Controllers\FaspayTestLabController;
use Vonso\FaspayTestLab\Http\Middleware\EnsureTestLabEnabled;

$prefix = config('faspay-test-lab.route_prefix', 'faspay-test-lab');
$middleware = array_merge(config('faspay-test-lab.middleware', ['web']), [EnsureTestLabEnabled::class]);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('faspay-test-lab.')
    ->group(function (): void {
        Route::get('/', [FaspayTestLabController::class, 'index'])->name('index');
        Route::post('/merchants', [FaspayTestLabController::class, 'storeMerchant'])->name('merchants.store');
        Route::put('/merchants/{merchant}', [FaspayTestLabController::class, 'updateMerchant'])->name('merchants.update');
        Route::post('/runs', [FaspayTestLabController::class, 'createRun'])->name('runs.store');
        Route::get('/runs/{run}', [FaspayTestLabController::class, 'show'])->name('runs.show');
        Route::post('/runs/{run}/cases/{caseNo}/execute', [FaspayTestLabController::class, 'executeCase'])
            ->where('caseNo', '\d+\.\d+')
            ->name('runs.cases.execute');
        Route::post('/runs/{run}/cases/18.12/check', [FaspayTestLabController::class, 'checkPayment'])->name('runs.cases.payment.check');
        Route::get('/runs/{run}/export', [FaspayTestLabController::class, 'export'])->name('runs.export');
    });
