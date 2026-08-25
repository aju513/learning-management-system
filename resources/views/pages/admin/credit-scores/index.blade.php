@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$title" />

@if(! $fiscalYear)
    <div class="rounded-2xl border border-dashed border-gray-300 p-14 text-center dark:border-gray-700">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-3xl text-brand-500 dark:bg-brand-500/10">★</div>
        <h2 class="mt-5 text-lg font-semibold text-gray-800 dark:text-white">Your credit score journey starts soon</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Credit claims will appear here after a fiscal year is activated.</p>
    </div>
@else
    @php
        $attendancePresent = (int) ($attendance?->present_days ?? 0);
        $attendanceThreshold = (int) $fiscalYear->attendance_threshold_days;
        $attendancePercentage = $attendanceThreshold > 0 ? min(100, round($attendancePresent / $attendanceThreshold * 100)) : 0;
        $attendanceReady = $attendancePresent >= $attendanceThreshold;
    @endphp

    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-cyan-600 p-6 text-white shadow-theme-sm sm:p-8">
        <div class="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-white/70">{{ $fiscalYear->name }}</p>
                <h1 class="mt-2 text-3xl font-bold">Your credit score wallet</h1>
                <p class="mt-2 max-w-xl text-sm text-white/80">Claim points earned from attendance, completed courses, and passed tests throughout this fiscal year.</p>
            </div>
            <div class="rounded-2xl bg-white/15 px-6 py-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-wide text-white/70">Claimed credits</p>
                <p class="mt-1 text-4xl font-bold">{{ number_format($claimedTotal, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Ready to claim</p><p class="mt-2 text-3xl font-bold text-brand-500">{{ number_format($eligibleTotal, 2) }}</p><p class="mt-1 text-xs text-gray-400">Points waiting for your action</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Attendance progress</p><p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $attendancePresent }} / {{ $attendanceThreshold }}</p><p class="mt-1 text-xs text-gray-400">Present days toward the threshold</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Claimable activities</p><p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $eligibleAwards->total() }}</p><p class="mt-1 text-xs text-gray-400">Course, test, and attendance awards</p></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <x-common.component-card title="Attendance award" desc="Reach the fiscal-year attendance threshold to unlock this award.">
            <div class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]">
                <div class="flex items-end justify-between gap-3"><div><p class="text-sm text-gray-500">Progress</p><p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ $attendancePresent }} <span class="text-base font-normal text-gray-400">/ {{ $attendanceThreshold }} days</span></p></div><span class="text-sm font-semibold text-brand-500">{{ $attendancePercentage }}%</span></div>
                <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-500" style="width: {{ $attendancePercentage }}%"></div></div>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <form method="POST" action="{{ route('learning.credit-scores.attendance.refresh') }}">@csrf<button class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Refresh attendance</button></form>
                    @if($attendanceReady)<span class="text-xs font-medium text-success-600">Threshold reached - award unlocked</span>@else<span class="text-xs text-gray-500">{{ max(0, $attendanceThreshold - $attendancePresent) }} days remaining</span>@endif
                </div>
            </div>
            @if($attendance?->status === 'failed')<p class="mt-4 rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700">Attendance could not be captured: {{ $attendance->error_message }}</p>@endif
        </x-common.component-card>

        <x-common.component-card title="Available to claim" desc="Claim each eligible activity once.">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($eligibleAwards as $award)
                    <div class="flex items-center justify-between gap-4 py-4"><div class="min-w-0"><p class="truncate font-medium text-gray-800 dark:text-white">{{ $award->source_label }}</p><p class="mt-1 text-xs text-gray-500">{{ $award->source_type->label() }} · Eligible {{ $award->eligible_at?->format('M d, Y') }}</p></div><div class="flex shrink-0 items-center gap-3"><span class="font-bold text-brand-500">+{{ number_format($award->points, 2) }}</span><form method="POST" action="{{ route('learning.credit-scores.claim', $award) }}">@csrf<button class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white">Claim</button></form></div></div>
                @empty
                    <div class="py-8 text-center"><p class="font-medium text-gray-800 dark:text-white">Nothing to claim yet</p><p class="mt-1 text-sm text-gray-500">Complete a course, pass a test, or reach the attendance threshold.</p></div>
                @endforelse
            </div>
            {{ $eligibleAwards->links() }}
        </x-common.component-card>
    </div>

    <div class="mt-6">
        <x-common.component-card title="Credit history" desc="A record of your claimed and eligible activity for this fiscal year.">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($history as $award)
                    <div class="flex flex-wrap items-center justify-between gap-4 py-4"><div><p class="font-medium text-gray-800 dark:text-white">{{ $award->source_label }}</p><p class="mt-1 text-xs text-gray-500">{{ $award->source_type->label() }} · {{ $award->claimed_at?->format('M d, Y') ?? 'Not claimed' }}</p></div><div class="flex items-center gap-3"><span class="font-semibold text-gray-800 dark:text-white">{{ number_format($award->points, 2) }}</span><x-ui.badge :color="$award->isClaimed() ? 'success' : 'warning'">{{ $award->isClaimed() ? 'Claimed' : 'Ready' }}</x-ui.badge></div></div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">No credit history yet.</p>
                @endforelse
            </div>
            {{ $history->links() }}
        </x-common.component-card>
    </div>
@endif

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <x-common.component-card title="Course credit scores" desc="Course completion credits available in this fiscal year.">
        @forelse($creditCourses as $course)
            @php
                $courseAward = $course->creditAwards->first();
                $courseEnrollment = $course->enrollments->first();
                $courseStatus = $courseAward?->isClaimed() ? 'Credit score taken' : ($courseAward ? 'Credit score ready' : ($courseEnrollment?->status->value === 'completed' ? 'Completed' : ($courseEnrollment ? 'Enrolled' : 'Available')));
                $courseStatusColor = $courseAward?->isClaimed() ? 'success' : ($courseAward ? 'warning' : ($courseEnrollment ? 'primary' : 'light'));
            @endphp
            <article class="border-b border-gray-100 py-5 first:pt-0 last:border-0 last:pb-0 dark:border-gray-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-500">Course module</p>
                        <h3 class="mt-1 font-semibold text-gray-800 dark:text-white">{{ $course->title }}</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ $course->category?->name ?? 'General' }} · {{ $course->estimated_duration_minutes }} minutes</p>
                    </div>
                    <div class="shrink-0 text-right"><p class="text-xs text-gray-500">Credit points</p><p class="text-xl font-bold text-brand-500">+{{ number_format($course->credit_points, 2) }}</p></div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div><x-ui.badge :color="$courseStatusColor">{{ $courseStatus }}</x-ui.badge><p class="mt-2 text-xs text-gray-500">{{ $course->required_training_key ? ($trainingNames[$course->required_training_key] ?? 'Required training') : 'Available to everyone' }}</p></div>
                    @if($courseAward && ! $courseAward->isClaimed())<form method="POST" action="{{ route('learning.credit-scores.claim', $courseAward) }}">@csrf<button type="submit" class="text-sm font-medium text-brand-500">Claim credit</button></form>@elseif($courseEnrollment?->status->value === 'completed')<form method="POST" action="{{ route('learning.credit-scores.course-claim', $courseEnrollment) }}">@csrf<button type="submit" class="text-sm font-medium text-brand-500">Claim credit</button></form>@elseif($courseEnrollment)<a href="{{ route('learning.courses.index') }}" class="text-sm font-medium text-brand-500">Open course</a>@else<a href="{{ route('learning.catalog.show', $course) }}" class="text-sm font-medium text-brand-500">View course</a>@endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700"><p class="font-medium text-gray-800 dark:text-white">No course credit scores</p><p class="mt-1 text-sm text-gray-500">Published credit-bearing courses available to you will appear here.</p></div>
        @endforelse
    </x-common.component-card>

    <x-common.component-card title="Test credit scores" desc="Credits earned by passing assigned standalone tests.">
        @forelse($creditAssessments as $assessment)
            @php
                $testAward = $assessment->creditAwards->first();
                $testPassed = $assessment->attempts->contains(fn ($attempt) => $attempt->passed);
                $testStatus = $testAward?->isClaimed() ? 'Credit score taken' : ($testAward ? 'Credit score ready' : ($testPassed ? 'Passed' : ($assessment->attempts_count > 0 ? 'Attempted' : 'Assigned')));
                $testStatusColor = $testAward?->isClaimed() ? 'success' : ($testAward ? 'warning' : ($testPassed ? 'primary' : 'light'));
                $assignment = $assessment->assignments->first();
            @endphp
            <article class="border-b border-gray-100 py-5 first:pt-0 last:border-0 last:pb-0 dark:border-gray-800">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-purple-500">Test module</p>
                        <h3 class="mt-1 font-semibold text-gray-800 dark:text-white">{{ $assessment->title }}</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ $assessment->questions_count }} questions · {{ $assessment->duration_minutes }} minutes · Pass {{ number_format((float) $assessment->passing_percentage, 0) }}%</p>
                    </div>
                    <div class="shrink-0 text-right"><p class="text-xs text-gray-500">Credit points</p><p class="text-xl font-bold text-purple-500">+{{ number_format($assessment->credit_points, 2) }}</p></div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div><x-ui.badge :color="$testStatusColor">{{ $testStatus }}</x-ui.badge><p class="mt-2 text-xs text-gray-500">{{ $assessment->required_training_key ? ($trainingNames[$assessment->required_training_key] ?? 'Required training') : 'Available to everyone' }} · {{ $assessment->attempts_count }} attempt(s)</p></div>
                    @if($assignment?->due_at)<p class="text-xs text-warning-600">Due {{ $assignment->due_at->format('M j, Y') }}</p>@endif
                </div>
                <div class="mt-3">
                    @if($assessment->attempts_count > 0)<a href="{{ route('learning.assessments.index') }}" class="text-sm font-medium text-brand-500">Open enrolled tests</a>@else<a href="{{ route('learning.assessments.applied') }}" class="text-sm font-medium text-brand-500">Open applied tests</a>@endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700"><p class="font-medium text-gray-800 dark:text-white">No test credit scores</p><p class="mt-1 text-sm text-gray-500">Assigned credit-bearing tests will appear here.</p></div>
        @endforelse
    </x-common.component-card>
</div>
@endsection
