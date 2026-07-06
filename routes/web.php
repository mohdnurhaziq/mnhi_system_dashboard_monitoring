<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AnalysisController;
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
Route::post('/projects/{project}/analyze', [AnalysisController::class, 'analyze'])->name('projects.analyze');

// AI-powered features (local Ollama).
Route::post('/projects/{project}/ai/summarize', [AiController::class, 'summarize'])->name('ai.summarize');
Route::post('/projects/{project}/ai/readme', [AiController::class, 'readme'])->name('ai.readme');
Route::post('/ai/digest', [AiController::class, 'digest'])->name('ai.digest');
Route::get('/ask', [AiController::class, 'askPage'])->name('ai.ask');
Route::post('/ask', [AiController::class, 'ask'])->name('ai.ask.submit');

Route::patch('/findings/{finding}', [FindingController::class, 'update'])->name('findings.update');

Route::post('/projects/{project}/prompts', [GeneratedPromptController::class, 'store'])->name('prompts.store');
Route::get('/prompts', [GeneratedPromptController::class, 'index'])->name('prompts.index');

Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
