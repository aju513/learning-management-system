<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseApplicationService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function apply(Course $course, User $trainee): Enrollment
    {
        if (! $course->isPublished()) {
            throw ValidationException::withMessages(['course' => 'This course is not accepting applications.']);
        }
        $this->availability->assertAvailable($course, $trainee);

        $existing = $course->enrollments()->where('user_id', $trainee->id)->first();
        if ($existing && in_array($existing->status, [EnrollmentStatus::Pending, EnrollmentStatus::Active, EnrollmentStatus::Completed], true)) {
            throw ValidationException::withMessages(['course' => 'You already have an application or enrollment for this course.']);
        }

        return DB::transaction(function () use ($course, $trainee): Enrollment {
            $application = $this->enrollments->createOrResetApplication($course, $trainee);
            activity('lms')->causedBy($trainee)->performedOn($application)->event('course-application.submitted')
                ->withProperties(['course_id' => $course->id])->log('Trainee applied for course');

            return $application;
        });
    }

    public function approve(Enrollment $application, User $reviewer): Enrollment
    {
        if ($application->status !== EnrollmentStatus::Pending) {
            throw ValidationException::withMessages(['application' => 'Only pending applications can be approved.']);
        }
        if (! $application->course->isPublished()) {
            throw ValidationException::withMessages(['application' => 'The course must be published before this application can be approved.']);
        }

        return DB::transaction(function () use ($application, $reviewer): Enrollment {
            $application = $this->enrollments->update($application, [
                'status' => EnrollmentStatus::Active,
                'enrolled_by' => $reviewer->id,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => null,
                'enrolled_at' => now(),
            ]);
            activity('lms')->causedBy($reviewer)->performedOn($application)->event('course-application.approved')
                ->withProperties(['course_id' => $application->course_id, 'trainee_id' => $application->user_id])->log('Course application approved');

            return $application;
        });
    }

    public function reject(Enrollment $application, User $reviewer, ?string $note): Enrollment
    {
        if ($application->status !== EnrollmentStatus::Pending) {
            throw ValidationException::withMessages(['application' => 'Only pending applications can be rejected.']);
        }

        return DB::transaction(function () use ($application, $reviewer, $note): Enrollment {
            $application = $this->enrollments->update($application, [
                'status' => EnrollmentStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
            activity('lms')->causedBy($reviewer)->performedOn($application)->event('course-application.rejected')
                ->withProperties(['course_id' => $application->course_id, 'trainee_id' => $application->user_id])->log('Course application rejected');

            return $application;
        });
    }
}
