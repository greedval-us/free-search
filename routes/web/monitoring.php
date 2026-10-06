<?php

use App\Http\Controllers\Monitoring\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('monitoring')->name('monitoring.')->group(function (): void {
    Route::get('/', [MonitoringController::class, 'index'])->name('index');
    Route::post('projects', [MonitoringController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [MonitoringController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/status', [MonitoringController::class, 'status'])->name('projects.status');
    Route::patch('projects/{project}', [MonitoringController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/lifecycle', [MonitoringController::class, 'lifecycle'])->name('projects.lifecycle');
    Route::delete('projects/{project}', [MonitoringController::class, 'destroy'])->name('projects.destroy');
    Route::post('projects/{project}/sources', [MonitoringController::class, 'addSource'])->name('sources.store');
    Route::post('projects/{project}/sources/{source}/validate', [MonitoringController::class, 'validateSource'])->name('sources.validate');
    Route::delete('projects/{project}/sources/{source}', [MonitoringController::class, 'removeSource'])->name('sources.destroy');
    Route::post('projects/{project}/schedules', [MonitoringController::class, 'saveSchedule'])->name('schedules.store');
    Route::patch('projects/{project}/schedules/{schedule}', [MonitoringController::class, 'saveSchedule'])->name('schedules.update');
    Route::post('projects/{project}/reports', [MonitoringController::class, 'requestReport'])->name('reports.store');
    Route::get('history', [MonitoringController::class, 'history'])->name('history');
    Route::get('projects/{project}/materials', [MonitoringController::class, 'materials'])->name('materials');
    Route::get('reports/{report}', [MonitoringController::class, 'report'])->name('reports.show');
    Route::get('reports/{report}/status', [MonitoringController::class, 'reportStatus'])->name('reports.status');
    Route::get('reports/{report}/download/{format}', [MonitoringController::class, 'download'])->whereIn('format', ['xlsx', 'json'])->name('reports.download');
    Route::post('reports/{report}/regenerate', [MonitoringController::class, 'regenerate'])->name('reports.regenerate');
});
