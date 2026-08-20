<?php

use App\Http\Controllers\Admin\AssessmentPlayerController;
use App\Http\Controllers\Admin\CourseAssessmentPlayerController;
use App\Http\Controllers\Admin\CreditScoreController;
use App\Http\Controllers\Admin\LearningController;
use App\Http\Controllers\Admin\ResultController;
use App\Modules\Trainee\Http\Controllers\ApplicationController;
use App\Modules\Trainee\Http\Controllers\CatalogController;
use App\Modules\Trainee\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('learning')->name('learning.')->middleware(['auth', 'active', 'can:portals.trainee.access'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{course}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/applications/{course}', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/courses', [LearningController::class, 'index'])->name('courses.index');
    Route::get('/courses/{enrollment}/materials/{learning_material}', [LearningController::class, 'show'])->name('courses.materials.show');
    Route::post('/courses/{enrollment}/materials/{learning_material}/complete', [LearningController::class, 'complete'])->name('courses.materials.complete');
    Route::get('/courses/{enrollment}/materials/{learning_material}/download', [LearningController::class, 'download'])->name('courses.materials.download');
    Route::post('/courses/{enrollment}/materials/{learning_material}/course-assessment/start', [CourseAssessmentPlayerController::class, 'start'])->name('courses.materials.course-assessment.start');
    Route::get('/courses/{enrollment}/course-assessment-attempts/{course_assessment_attempt}', [CourseAssessmentPlayerController::class, 'show'])->name('course-assessment-attempts.show');
    Route::post('/courses/{enrollment}/course-assessment-attempts/{course_assessment_attempt}', [CourseAssessmentPlayerController::class, 'submit'])->name('course-assessment-attempts.submit');
    Route::get('/assessments', [AssessmentPlayerController::class, 'index'])->name('assessments.index');
    Route::post('/assessments/{assessment}/start', [AssessmentPlayerController::class, 'start'])->name('assessments.start');
    Route::get('/assessments/attempts/{assessment_attempt}', [AssessmentPlayerController::class, 'show'])->name('assessments.attempts.show');
    Route::post('/assessments/attempts/{assessment_attempt}', [AssessmentPlayerController::class, 'submit'])->name('assessments.attempts.submit');
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{assessment_attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::middleware('can:credit-scores.view-own')->group(function (): void {
        Route::get('/credit-scores', [CreditScoreController::class, 'index'])->name('credit-scores.index');
        Route::post('/credit-scores/attendance/refresh', [CreditScoreController::class, 'refreshAttendance'])->middleware('can:credit-scores.refresh-attendance')->name('credit-scores.attendance.refresh');
        Route::post('/credit-scores/{credit_award}/claim', [CreditScoreController::class, 'claim'])->middleware('can:credit-scores.claim-own')->name('credit-scores.claim');
    });
});
