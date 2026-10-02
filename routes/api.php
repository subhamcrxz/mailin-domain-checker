<?php

use App\Http\Controllers\Api\DomainCheckController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/check',
    [DomainCheckController::class, 'store']
);

Route::get(
    '/checks/{domainCheck}',
    [DomainCheckController::class, 'show']
);

Route::post(
    '/bulk-check',
    [DomainCheckController::class, 'bulkStore']
);

Route::get(
    '/batches/{batch}',
    [DomainCheckController::class, 'batchShow']
);

Route::get(
    '/batches/{batch}/results',
    [DomainCheckController::class, 'batchResults']
);

Route::get(
    '/batches/{batch}/export',
    [DomainCheckController::class, 'exportBatch']
);