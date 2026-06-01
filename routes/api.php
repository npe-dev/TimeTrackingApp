<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GlobalLabelController;
use App\Http\Controllers\TaskLabelController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\TaskLinkController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Background status (public - needed before login for page styling)
Route::get('/settings/background/status', [SettingsController::class, 'backgroundStatus']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Boards
    Route::apiResource('boards', BoardController::class);
    Route::get('/boards/{boardId}/columns', [ColumnController::class, 'index']);

    // Columns
    Route::post('/columns', [ColumnController::class, 'store']);
    Route::put('/columns/{column}', [ColumnController::class, 'update']);
    Route::delete('/columns/{column}', [ColumnController::class, 'destroy']);

    // Tasks
    Route::get('/columns/{columnId}/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::patch('/tasks/{task}/move', [TaskController::class, 'move']);
    Route::post('/tasks/fix-positions', [TaskController::class, 'fixPositions']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    // Task labels
    Route::post('/tasks/{taskId}/labels', [TaskLabelController::class, 'store']);
    Route::delete('/labels/{label}', [TaskLabelController::class, 'destroy']);

    // Global labels
    Route::get('/global-labels', [GlobalLabelController::class, 'index']);
    Route::post('/global-labels', [GlobalLabelController::class, 'store']);
    Route::put('/global-labels/{globalLabel}', [GlobalLabelController::class, 'update']);
    Route::delete('/global-labels/{globalLabel}', [GlobalLabelController::class, 'destroy']);
    Route::patch('/global-labels/reorder', [GlobalLabelController::class, 'reorder']);

    // Checklist items
    Route::post('/tasks/{taskId}/checklist', [ChecklistItemController::class, 'store']);
    Route::put('/checklist/{checklistItem}', [ChecklistItemController::class, 'update']);
    Route::patch('/checklist/{checklistItem}/toggle', [ChecklistItemController::class, 'toggle']);
    Route::delete('/checklist/{checklistItem}', [ChecklistItemController::class, 'destroy']);

    // Task links
    Route::post('/tasks/{taskId}/links', [TaskLinkController::class, 'store']);
    Route::put('/links/{link}', [TaskLinkController::class, 'update']);
    Route::delete('/links/{link}', [TaskLinkController::class, 'destroy']);

    // Time entries
    Route::get('/entries/export/csv', [TimeEntryController::class, 'exportCsv']);
    Route::get('/entries', [TimeEntryController::class, 'index']);
    Route::get('/entries/running', [TimeEntryController::class, 'running']);
    Route::post('/entries/start', [TimeEntryController::class, 'start']);
    Route::post('/entries/stop', [TimeEntryController::class, 'stop']);
    Route::post('/entries/stop-at', [TimeEntryController::class, 'stopAt']);
    Route::post('/entries/heartbeat', [TimeEntryController::class, 'heartbeat']);
    Route::post('/entries', [TimeEntryController::class, 'store']);
    Route::put('/entries/{entry}', [TimeEntryController::class, 'update']);
    Route::delete('/entries/{entry}', [TimeEntryController::class, 'destroy']);
    Route::get('/tasks/{taskId}/time-entries', [TimeEntryController::class, 'taskEntries']);
    Route::post('/tasks/{taskId}/start-timer', [TimeEntryController::class, 'taskStart']);

    // Reports
    Route::get('/reports/summary', [ReportController::class, 'summary']);

    // Automations
    Route::get('/automations', [AutomationController::class, 'index']);
    Route::get('/automations/{automation}', [AutomationController::class, 'show']);
    Route::post('/automations', [AutomationController::class, 'store']);
    Route::put('/automations/{automation}', [AutomationController::class, 'update']);
    Route::delete('/automations/{automation}', [AutomationController::class, 'destroy']);
    Route::patch('/automations/{automation}/toggle', [AutomationController::class, 'toggle']);

    // Settings
    Route::post('/settings/background', [SettingsController::class, 'uploadBackground']);
    Route::delete('/settings/background', [SettingsController::class, 'deleteBackground']);
});
