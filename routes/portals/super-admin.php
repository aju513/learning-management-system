<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\AssessmentQuestionController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseModuleController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\LearningMaterialController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Shared\RoleAccountController;
use App\Modules\SuperAdmin\Http\Controllers\ApplicationController;
use App\Modules\SuperAdmin\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'active', 'can:portals.super-admin.access'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    foreach (['admins' => 'admin', 'instructors' => 'instructor', 'trainees' => 'trainee'] as $resource => $role) {
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

    Route::get('/course-categories', [CourseCategoryController::class, 'index'])->name('course-categories.index');
    Route::get('/course-categories/create', [CourseCategoryController::class, 'create'])->name('course-categories.create');
    Route::post('/course-categories', [CourseCategoryController::class, 'store'])->name('course-categories.store');
    Route::get('/course-categories/{course_category}/edit', [CourseCategoryController::class, 'edit'])->name('course-categories.edit');
    Route::put('/course-categories/{course_category}', [CourseCategoryController::class, 'update'])->name('course-categories.update');
    Route::delete('/course-categories/{course_category}', [CourseCategoryController::class, 'destroy'])->name('course-categories.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::patch('/courses/{course}/status', [CourseController::class, 'status'])->name('courses.status');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/modules', [CourseModuleController::class, 'store'])->name('course-modules.store');
    Route::put('/course-modules/{course_module}', [CourseModuleController::class, 'update'])->name('course-modules.update');
    Route::patch('/course-modules/{course_module}/move', [CourseModuleController::class, 'move'])->name('course-modules.move');
    Route::delete('/course-modules/{course_module}', [CourseModuleController::class, 'destroy'])->name('course-modules.destroy');
    Route::post('/course-modules/{course_module}/materials', [LearningMaterialController::class, 'store'])->name('learning-materials.store');
    Route::put('/learning-materials/{learning_material}', [LearningMaterialController::class, 'update'])->name('learning-materials.update');
    Route::patch('/learning-materials/{learning_material}/move', [LearningMaterialController::class, 'move'])->name('learning-materials.move');
    Route::delete('/learning-materials/{learning_material}', [LearningMaterialController::class, 'destroy'])->name('learning-materials.destroy');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::patch('/applications/{enrollment}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::patch('/applications/{enrollment}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessments/create', [AssessmentController::class, 'create'])->name('assessments.create');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit');
    Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
    Route::patch('/assessments/{assessment}/status', [AssessmentController::class, 'status'])->name('assessments.status');
    Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/assessments/{assessment}/questions', [AssessmentQuestionController::class, 'store'])->name('assessment-questions.store');
    Route::get('/assessment-questions/{assessment_question}/edit', [AssessmentQuestionController::class, 'edit'])->name('assessment-questions.edit');
    Route::put('/assessment-questions/{assessment_question}', [AssessmentQuestionController::class, 'update'])->name('assessment-questions.update');
    Route::delete('/assessment-questions/{assessment_question}', [AssessmentQuestionController::class, 'destroy'])->name('assessment-questions.destroy');
    Route::post('/assessments/{assessment}/assignments', [AssessmentAssignmentController::class, 'store'])->name('assessment-assignments.store');
    Route::delete('/assessment-assignments/{assessment_assignment}', [AssessmentAssignmentController::class, 'destroy'])->name('assessment-assignments.destroy');
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{assessment_attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/access-matrix', PermissionController::class)->name('access-matrix.index');
    Route::get('/activity-log', ActivityLogController::class)->name('activity.index');
});
