@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb :pageTitle="$title" />

<div class="mb-6 rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-cyan-600 p-6 text-white shadow-theme-sm sm:p-8">
    <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm font-medium text-white/70">Administration reporting</p>
            <h1 class="mt-2 text-3xl font-bold">Fiscal-year credit score viewer</h1>
            <p class="mt-2 max-w-2xl text-sm text-white/80">Review trainee credit awards, completed courses, and quiz results from one fiscal-year record.</p>
        </div>
        @if($fiscalYear)
            <div class="rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                <p class="text-xs uppercase tracking-wide text-white/70">Selected fiscal year</p>
                <p class="mt-1 text-xl font-bold">{{ $fiscalYear->name }}</p>
                <p class="mt-1 text-xs text-white/70">{{ $fiscalYear->starts_on->format('M d, Y') }} – {{ $fiscalYear->ends_on->format('M d, Y') }}</p>
            </div>
        @endif
    </div>
</div>

<x-common.component-card title="Choose a fiscal year" desc="Select a period, then open a trainee to inspect their credit history.">
    <form method="GET" action="{{ route($viewerRoute) }}" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
        <x-form.select name="fiscal_year_id" label="Fiscal year" :options="$fiscalYears->pluck('name', 'id')->all()" :value="$fiscalYear?->id" placeholder="Select fiscal year" required />
        <x-form.input name="search" label="Search trainees" :value="request('search')" placeholder="Name or email" />
        <button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Apply filters</button>
    </form>
</x-common.component-card>

@if(! $fiscalYear)
    <div class="mt-6 rounded-2xl border border-dashed border-gray-300 p-14 text-center dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">No fiscal year is available</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">Create a fiscal year before reviewing trainee credit scores.</p>
    </div>
@else
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Active trainees</p><p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $trainees->total() }}</p><p class="mt-1 text-xs text-gray-400">Matching this view</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Selected period</p><p class="mt-2 text-xl font-bold text-brand-500">{{ $fiscalYear->name }}</p><p class="mt-1 text-xs text-gray-400">{{ ucfirst($fiscalYear->status->value) }}</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Credit sources</p><p class="mt-2 text-xl font-bold text-gray-800 dark:text-white">Courses + quizzes</p><p class="mt-1 text-xs text-gray-400">Attendance is included in overall totals</p></div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"><p class="text-sm text-gray-500">Review mode</p><p class="mt-2 text-xl font-bold text-gray-800 dark:text-white">Read-only</p><p class="mt-1 text-xs text-gray-400">Ledger data is not changed here</p></div>
    </div>

    <div class="mt-6">
        <x-common.component-card title="Trainee credit summary" desc="Click a trainee to open their overall, course, and quiz details.">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Trainee</th>
                            <th class="px-4 py-3">Courses</th>
                            <th class="px-4 py-3">Course credits</th>
                            <th class="px-4 py-3">Quiz credits</th>
                            <th class="px-4 py-3">Overall credits</th>
                            <th class="px-4 py-3">Claimed / ready</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($trainees as $trainee)
                            @php
                                $isSelected = $selectedTrainee?->id === $trainee->id;
                                $detailUrl = route($viewerRoute, ['fiscal_year_id' => $fiscalYear->id, 'trainee_id' => $trainee->id, 'tab' => 'overall', 'search' => request('search')]);
                            @endphp
                            <tr class="{{ $isSelected ? 'bg-brand-50/60 dark:bg-brand-500/10' : '' }}">
                                <td class="px-4 py-4">
                                    <a href="{{ $detailUrl }}" class="group block min-w-52">
                                        <p class="font-medium text-gray-800 group-hover:text-brand-500 dark:text-white">{{ $trainee->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $trainee->email }}</p>
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"><span class="font-semibold">{{ $trainee->course_award_count }}</span><span class="ml-1 text-xs text-gray-400">completed</span></td>
                                <td class="px-4 py-4 text-sm font-semibold text-brand-500">{{ number_format((float) ($trainee->course_credit_points ?? 0), 2) }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-purple-500">{{ number_format((float) ($trainee->quiz_credit_points ?? 0), 2) }}</td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-800 dark:text-white">{{ number_format((float) ($trainee->total_credit_points ?? 0), 2) }}</td>
                                <td class="px-4 py-4 text-xs text-gray-500"><span class="font-semibold text-success-600">{{ number_format((float) ($trainee->claimed_credit_points ?? 0), 2) }}</span><span class="mx-1">/</span><span class="font-semibold text-warning-600">{{ number_format((float) ($trainee->ready_credit_points ?? 0), 2) }}</span></td>
                                <td class="px-4 py-4 text-right"><a href="{{ $detailUrl }}" class="rounded-lg border border-brand-200 px-3 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-300">View details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No active trainees match this search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $trainees->links() }}</div>
        </x-common.component-card>
    </div>

    @if($selectedTrainee && $details)
        @php
            $overall = $details['overall'];
            $tabLink = fn (string $tab): string => route($viewerRoute, ['fiscal_year_id' => $fiscalYear->id, 'trainee_id' => $selectedTrainee->id, 'tab' => $tab, 'search' => request('search')]);
        @endphp
        <div class="mt-6" id="trainee-details">
            <x-common.component-card :title="$selectedTrainee->name" desc="Detailed credit activity for the selected fiscal year.">
                <x-slot:actions><span class="text-sm text-gray-500">{{ $selectedTrainee->email }}</span></x-slot:actions>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/[0.03]"><p class="text-xs uppercase tracking-wide text-gray-500">Overall</p><p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($overall['total'], 2) }}</p><p class="mt-1 text-xs text-gray-400">All awarded credits</p></div>
                    <div class="rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10"><p class="text-xs uppercase tracking-wide text-brand-600">Courses</p><p class="mt-2 text-2xl font-bold text-brand-600">{{ number_format($overall['course'], 2) }}</p><p class="mt-1 text-xs text-brand-600/70">Course completions</p></div>
                    <div class="rounded-xl bg-purple-50 p-4 dark:bg-purple-500/10"><p class="text-xs uppercase tracking-wide text-purple-600">Quizzes</p><p class="mt-2 text-2xl font-bold text-purple-600">{{ number_format($overall['quiz'], 2) }}</p><p class="mt-1 text-xs text-purple-600/70">Passed quizzes</p></div>
                    <div class="rounded-xl bg-cyan-50 p-4 dark:bg-cyan-500/10"><p class="text-xs uppercase tracking-wide text-cyan-700">Attendance</p><p class="mt-2 text-2xl font-bold text-cyan-700">{{ number_format($overall['attendance'], 2) }}</p><p class="mt-1 text-xs text-cyan-700/70">Attendance award</p></div>
                    <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/10"><p class="text-xs uppercase tracking-wide text-success-700">Claimed</p><p class="mt-2 text-2xl font-bold text-success-700">{{ number_format($overall['claimed'], 2) }}</p><p class="mt-1 text-xs text-success-700/70">{{ number_format($overall['ready'], 2) }} ready to claim</p></div>
                </div>

                <div class="mt-6 border-b border-gray-200 dark:border-gray-800">
                    <nav class="flex gap-6 overflow-x-auto" aria-label="Credit detail tabs">
                        @foreach(['overall' => 'Overall', 'courses' => 'Courses', 'quizzes' => 'Quizzes'] as $tabKey => $tabLabel)
                            <a href="{{ $tabLink($tabKey) }}" class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium {{ $activeTab === $tabKey ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-200' }}">{{ $tabLabel }}</a>
                        @endforeach
                    </nav>
                </div>

                @if($activeTab === 'courses')
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead><tr class="text-left text-xs uppercase tracking-wide text-gray-500"><th class="px-4 py-3">Course</th><th class="px-4 py-3">Enrollment</th><th class="px-4 py-3">Completed</th><th class="px-4 py-3">Credit</th><th class="px-4 py-3">Award status</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($details['courses'] as $row)
                                    @php $award = $row['award']; $enrollment = $row['enrollment']; @endphp
                                    <tr><td class="px-4 py-4"><p class="font-medium text-gray-800 dark:text-white">{{ $row['course']?->title ?? 'Course unavailable' }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['course']?->category?->name ?? 'General' }}</p></td><td class="px-4 py-4 text-sm text-gray-500">{{ $enrollment?->status?->value ? ucfirst($enrollment->status->value) : 'Credit award only' }}</td><td class="px-4 py-4 text-sm text-gray-500">{{ $enrollment?->completed_at?->format('M d, Y') ?? 'Not completed' }}</td><td class="px-4 py-4 text-sm font-bold text-brand-500">{{ $award ? number_format((float) $award->points, 2) : '—' }}</td><td class="px-4 py-4">@if($award)<x-ui.badge :color="$award->isClaimed() ? 'success' : 'warning'">{{ $award->isClaimed() ? 'Claimed' : 'Ready to claim' }}</x-ui.badge>@else<x-ui.badge color="light">No credit yet</x-ui.badge>@endif</td></tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No course activity was recorded in this fiscal year.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($activeTab === 'quizzes')
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead><tr class="text-left text-xs uppercase tracking-wide text-gray-500"><th class="px-4 py-3">Quiz</th><th class="px-4 py-3">Type / course</th><th class="px-4 py-3">Taken</th><th class="px-4 py-3">Result</th><th class="px-4 py-3">Credit obtained</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($details['quizzes'] as $row)
                                    @php $attempt = $row['attempt']; $award = $row['award']; @endphp
                                    <tr><td class="px-4 py-4"><p class="font-medium text-gray-800 dark:text-white">{{ $row['title'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $row['kind'] }}</p></td><td class="px-4 py-4 text-sm text-gray-500">{{ $row['course']?->title ?? 'Standalone quiz' }}</td><td class="px-4 py-4 text-sm text-gray-500">{{ $row['date']?->format('M d, Y') ?? '—' }}</td><td class="px-4 py-4">@if($attempt?->passed === true)<x-ui.badge color="success">Passed · {{ number_format((float) $attempt->score_percentage, 1) }}%</x-ui.badge>@elseif($attempt?->passed === false)<x-ui.badge color="error">Not passed · {{ number_format((float) $attempt->score_percentage, 1) }}%</x-ui.badge>@else<x-ui.badge color="warning">Pending review</x-ui.badge>@endif</td><td class="px-4 py-4">@if($award)<p class="font-bold text-purple-600">{{ number_format((float) $award->points, 2) }}</p><p class="mt-1 text-xs text-gray-500">{{ $award->isClaimed() ? 'Claimed' : 'Ready to claim' }}</p>@else<span class="text-sm text-gray-400">No credit</span>@endif</td></tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No quiz attempts were recorded in this fiscal year.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-5 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                        <div>
                            <div class="flex items-center justify-between gap-4"><h3 class="font-semibold text-gray-800 dark:text-white">Credit activity</h3><span class="text-xs text-gray-500">{{ $details['awards']->count() }} award(s)</span></div>
                            <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($details['awards'] as $award)
                                    <div class="flex flex-wrap items-center justify-between gap-4 py-4"><div><p class="font-medium text-gray-800 dark:text-white">{{ $award->source_label }}</p><p class="mt-1 text-xs text-gray-500">{{ $award->source_type->label() }} · {{ $award->eligible_at?->format('M d, Y') }}</p></div><div class="flex items-center gap-3"><span class="font-semibold text-gray-800 dark:text-white">{{ number_format((float) $award->points, 2) }}</span><x-ui.badge :color="$award->isClaimed() ? 'success' : 'warning'">{{ $award->isClaimed() ? 'Claimed' : 'Ready' }}</x-ui.badge></div></div>
                                @empty
                                    <p class="py-8 text-sm text-gray-500">No credit awards were recorded in this fiscal year.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-5 dark:bg-white/[0.03]"><h3 class="font-semibold text-gray-800 dark:text-white">Attendance snapshot</h3><dl class="mt-4 space-y-4 text-sm"><div class="flex justify-between gap-4"><dt class="text-gray-500">Present days</dt><dd class="font-semibold text-gray-800 dark:text-white">{{ $details['attendance']?->present_days ?? 0 }} / {{ $fiscalYear->attendance_threshold_days }}</dd></div><div class="flex justify-between gap-4"><dt class="text-gray-500">Status</dt><dd class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($details['attendance']?->status ?? 'Not refreshed') }}</dd></div><div class="flex justify-between gap-4"><dt class="text-gray-500">Last refreshed</dt><dd class="font-semibold text-gray-800 dark:text-white">{{ $details['attendance']?->fetched_at?->format('M d, Y H:i') ?? '—' }}</dd></div></dl><p class="mt-5 text-xs text-gray-500">Overall combines course completion, quiz pass, and attendance awards.</p></div>
                    </div>
                @endif
            </x-common.component-card>
        </div>
    @endif
@endif
@endsection
