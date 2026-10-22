<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\RunController;
use Illuminate\Support\Facades\Route;

// Redirect root to workflows dashboard
Route::get('/', function () {
    return redirect()->route('workflows.index');
});

// Leads Management & Testing
Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
Route::post('leads/process-queue', [LeadController::class, 'processQueue'])->name('leads.processQueue');
Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

// Workflow Runs
Route::get('runs', [RunController::class, 'index'])->name('runs.index');
Route::get('runs/{run}', [RunController::class, 'show'])->name('runs.show');

// Workflow routes (workflows.php)
require __DIR__.'/workflows.php';
