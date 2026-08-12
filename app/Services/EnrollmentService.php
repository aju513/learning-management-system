<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function assign(Course $course, array $traineeIds, User $actor): void
    {
        if (! $course->isPublished()) {
            throw ValidationException::withMessages(['course_id' => 'Only published courses can receive enrollments.']);
        }

        DB::transaction(function () use ($course, $traineeIds, $actor): void {
            $trainees = $this->users->findByIds($traineeIds);
            if ($trainees->count() !== count(array_unique($traineeIds))) {
                throw ValidationException::withMessages(['trainees' => 'One or more selected trainees could not be found.']);
            }
            foreach ($trainees as $trainee) {
                if (! $trainee->hasRole('trainee')) {
                    throw ValidationException::withMessages(['trainees' => "{$trainee->name} does not have trainee learning access."]);
                }
                $enrollment = $this->enrollments->createOrRestore($course, $trainee, $actor);
                activity('lms')->causedBy($actor)->performedOn($enrollment)->event('enrollment.assigned')
                    ->withProperties(['course_id' => $course->id, 'trainee_id' => $trainee->id])->log('Trainee enrolled in course');
            }
        });
    }

    public function cancel(Enrollment $enrollment, User $actor): void
    {
        $this->enrollments->update($enrollment, ['status' => 'cancelled']);
        activity('lms')->causedBy($actor)->performedOn($enrollment)->event('enrollment.cancelled')->log('Course enrollment cancelled');
    }
}
