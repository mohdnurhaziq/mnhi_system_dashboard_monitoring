<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\GeneratedPromptController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');

Route::post('/projects/scan', [ScanController::class, 'scanAll'])->name('projects.scan-all');
Route::post('/projects/discover', [ScanController::class, 'discover'])->name('projects.discover');
Route::post('/projects/{project}/scan', [ScanController::class, 'scanOne'])->name('projects.scan-one');

Route::patch('/findings/{finding}', [FindingController::class, 'update'])->name('findings.update');

Route::post('/projects/{project}/prompts', [GeneratedPromptController::class, 'store'])->name('prompts.store');
Route::get('/prompts', [GeneratedPromptController::class, 'index'])->name('prompts.index');

Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
