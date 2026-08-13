<?php

use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Admin\AssessmentQuestionController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Shared\RoleAccountController;
use App\Modules\Admin\Http\Controllers\ApplicationController;
use App\Modules\Admin\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'can:portals.admin.access'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    foreach (['instructors' => 'instructor', 'trainees' => 'trainee'] as $resource => $role) {
        Route::prefix($resource)->name($resource.'.')->controller(RoleAccountController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}/edit', 'edit')->name('edit');
            Route::put('/{user}', 'update')->name('update');
            Route::patch('/{user}/status', 'status')->name('status');
            Route::delete('/{user}', 'destroy')->name('destroy');
        });
    }
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::patch('/applications/{enrollment}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::patch('/applications/{enrollment}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessments/create', [AssessmentController::class, 'create'])->name('assessments.create');
    Route::get('/assessments/questions/template', [AssessmentQuestionController::class, 'template'])->name('assessment-questions.template');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
    Route::patch('/assessments/{assessment}/status', [AssessmentController::class, 'status'])->name('assessments.status');
    Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/assessments/{assessment}/questions', [AssessmentQuestionController::class, 'store'])->name('assessment-questions.store');
    Route::post('/assessments/{assessment}/questions/import', [AssessmentQuestionController::class, 'import'])->name('assessment-questions.import');
    Route::patch('/assessments/{assessment}/questions/reorder', [AssessmentQuestionController::class, 'reorder'])->name('assessment-questions.reorder');
    Route::get('/assessment-questions/{assessment_question}/edit', [AssessmentQuestionController::class, 'edit'])->name('assessment-questions.edit');
    Route::put('/assessment-questions/{assessment_question}', [AssessmentQuestionController::class, 'update'])->name('assessment-questions.update');
    Route::delete('/assessment-questions/{assessment_question}', [AssessmentQuestionController::class, 'destroy'])->name('assessment-questions.destroy');
    Route::post('/assessments/{assessment}/assignments', [AssessmentAssignmentController::class, 'store'])->name('assessment-assignments.store');
    Route::delete('/assessment-assignments/{assessment_assignment}', [AssessmentAssignmentController::class, 'destroy'])->name('assessment-assignments.destroy');
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{assessment_attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::patch('/results/{assessment_attempt}/review', [ResultController::class, 'review'])->name('results.review');
    Route::get('/reports', ReportController::class)->name('reports.index');
});
