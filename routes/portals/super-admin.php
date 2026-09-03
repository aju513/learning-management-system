<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Admin\AssessmentCategoryController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\AssessmentQuestionController;
use App\Http\Controllers\Admin\CourseAssessmentController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseChapterController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseModuleController;
use App\Http\Controllers\Admin\CreditScoreController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\FiscalYearController;
use App\Http\Controllers\Admin\LearningMaterialController;
use App\Http\Controllers\Admin\LearningMaterialImageController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Shared\RoleAccountController;
use App\Http\Controllers\Shared\TestApplicationReviewController;
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
    Route::get('/courses/{course}/preview', [CourseController::class, 'preview'])->name('courses.preview');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/modules', [CourseModuleController::class, 'store'])->name('course-modules.store');
    Route::patch('/courses/{course}/modules/reorder', [CourseModuleController::class, 'reorder'])->name('course-modules.reorder');
    Route::put('/course-modules/{course_module}', [CourseModuleController::class, 'update'])->name('course-modules.update');
    Route::patch('/course-modules/{course_module}/move', [CourseModuleController::class, 'move'])->name('course-modules.move');
    Route::delete('/course-modules/{course_module}', [CourseModuleController::class, 'destroy'])->name('course-modules.destroy');
    Route::post('/course-modules/{course_module}/chapters', [CourseChapterController::class, 'store'])->name('course-chapters.store');
    Route::patch('/course-modules/{course_module}/chapters/reorder', [CourseChapterController::class, 'reorder'])->name('course-chapters.reorder');
    Route::put('/course-chapters/{course_chapter}', [CourseChapterController::class, 'update'])->name('course-chapters.update');
    Route::patch('/course-chapters/{course_chapter}/move', [CourseChapterController::class, 'move'])->name('course-chapters.move');
    Route::delete('/course-chapters/{course_chapter}', [CourseChapterController::class, 'destroy'])->name('course-chapters.destroy');
    Route::get('/course-chapters/{course_chapter}/materials/create', [LearningMaterialController::class, 'create'])->name('learning-materials.create');
    Route::post('/course-chapters/{course_chapter}/materials', [LearningMaterialController::class, 'store'])->name('learning-materials.store');
    Route::post('/course-chapters/{course_chapter}/material-images', [LearningMaterialImageController::class, 'storeForChapter'])
        ->middleware('can:materials.create')->name('learning-material-images.store-chapter');
    Route::patch('/course-chapters/{course_chapter}/materials/reorder', [LearningMaterialController::class, 'reorder'])->name('learning-materials.reorder');
    Route::get('/learning-materials/{learning_material}/edit', [LearningMaterialController::class, 'edit'])->name('learning-materials.edit');
    Route::put('/learning-materials/{learning_material}', [LearningMaterialController::class, 'update'])->name('learning-materials.update');
    Route::post('/learning-materials/{learning_material}/material-images', [LearningMaterialImageController::class, 'storeForMaterial'])
        ->middleware('can:materials.edit')->name('learning-material-images.store-material');
    Route::patch('/learning-materials/{learning_material}/move', [LearningMaterialController::class, 'move'])->name('learning-materials.move');
    Route::delete('/learning-materials/{learning_material}', [LearningMaterialController::class, 'destroy'])->name('learning-materials.destroy');
    Route::get('/course-assessments/{course_assessment}', [CourseAssessmentController::class, 'show'])->name('course-assessments.show');
    Route::post('/course-assessments/{course_assessment}/questions', [CourseAssessmentController::class, 'store'])->name('course-assessment-questions.store');
    Route::patch('/course-assessments/{course_assessment}/questions/reorder', [CourseAssessmentController::class, 'reorder'])->name('course-assessment-questions.reorder');
    Route::get('/course-assessment-questions/{course_assessment_question}/edit', [CourseAssessmentController::class, 'edit'])->name('course-assessment-questions.edit');
    Route::put('/course-assessment-questions/{course_assessment_question}', [CourseAssessmentController::class, 'update'])->name('course-assessment-questions.update');
    Route::delete('/course-assessment-questions/{course_assessment_question}', [CourseAssessmentController::class, 'destroy'])->name('course-assessment-questions.destroy');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::patch('/applications/{enrollment}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::patch('/applications/{enrollment}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    Route::middleware('can:fiscal-years.manage')->group(function (): void {
        Route::get('/fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');
        Route::get('/fiscal-years/create', [FiscalYearController::class, 'create'])->middleware('can:fiscal-years.create')->name('fiscal-years.create');
        Route::post('/fiscal-years', [FiscalYearController::class, 'store'])->middleware('can:fiscal-years.create')->name('fiscal-years.store');
        Route::get('/fiscal-years/{fiscal_year}', [FiscalYearController::class, 'show'])->middleware('can:fiscal-years.show')->name('fiscal-years.show');
        Route::get('/fiscal-years/{fiscal_year}/edit', [FiscalYearController::class, 'edit'])->middleware('can:fiscal-years.edit')->name('fiscal-years.edit');
        Route::put('/fiscal-years/{fiscal_year}', [FiscalYearController::class, 'update'])->middleware('can:fiscal-years.edit')->name('fiscal-years.update');
        Route::patch('/fiscal-years/{fiscal_year}/status', [FiscalYearController::class, 'status'])->middleware('can:fiscal-years.edit')->name('fiscal-years.status');
        Route::delete('/fiscal-years/{fiscal_year}', [FiscalYearController::class, 'destroy'])->middleware('can:fiscal-years.delete')->name('fiscal-years.destroy');
    });

    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
    Route::get('/assessment-categories', [AssessmentCategoryController::class, 'index'])->name('assessment-categories.index');
    Route::get('/assessment-categories/create', [AssessmentCategoryController::class, 'create'])->name('assessment-categories.create');
    Route::post('/assessment-categories', [AssessmentCategoryController::class, 'store'])->name('assessment-categories.store');
    Route::get('/assessment-categories/{assessment_category}/edit', [AssessmentCategoryController::class, 'edit'])->name('assessment-categories.edit');
    Route::put('/assessment-categories/{assessment_category}', [AssessmentCategoryController::class, 'update'])->name('assessment-categories.update');
    Route::delete('/assessment-categories/{assessment_category}', [AssessmentCategoryController::class, 'destroy'])->name('assessment-categories.destroy');
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
    Route::middleware('can:test-applications.review-all')->group(function (): void {
        Route::get('/test-applications', [TestApplicationReviewController::class, 'index'])->name('test-applications.index');
        Route::patch('/test-applications/{assessment_application}/approve', [TestApplicationReviewController::class, 'approve'])->name('test-applications.approve');
        Route::patch('/test-applications/{assessment_application}/reject', [TestApplicationReviewController::class, 'reject'])->name('test-applications.reject');
    });
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{assessment_attempt}', [ResultController::class, 'show'])->name('results.show');
    Route::patch('/results/{assessment_attempt}/review', [ResultController::class, 'review'])->name('results.review');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/credit-scores', [CreditScoreController::class, 'viewer'])
        ->middleware('can:credit-scores.view-all')->name('credit-scores.index');
    Route::get('/access-matrix', PermissionController::class)->name('access-matrix.index');
    Route::get('/activity-log', ActivityLogController::class)->name('activity.index');
});
