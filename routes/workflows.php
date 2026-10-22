<?php

use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

// In routes/web.php add:  require __DIR__.'/workflows.php';
Route::prefix('workflows')->name('workflows.')->group(function () {
    Route::get('/', [WorkflowController::class, 'index'])->name('index');
    Route::post('/', [WorkflowController::class, 'store'])->name('store');
    Route::get('{workflow}', [WorkflowController::class, 'show'])->name('show');
    Route::put('{workflow}', [WorkflowController::class, 'update'])->name('update');
    Route::delete('{workflow}', [WorkflowController::class, 'destroy'])->name('destroy');
    Route::post('{workflow}/publish', [WorkflowController::class, 'publish'])->name('publish');
    Route::post('{workflow}/simulate', [WorkflowController::class, 'simulate'])->middleware('throttle:20,1')->name('simulate');
    Route::get('{workflow}/runs/latest', [WorkflowController::class, 'latestRun'])->name('runs.latest');
});
