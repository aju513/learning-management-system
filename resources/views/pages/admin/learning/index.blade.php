@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="My Learning" />
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
@forelse($enrollments as $enrollment)
    @php($firstMaterial = $enrollment->course->modules->flatMap->chapters->flatMap->materials->first())
    <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @if($enrollment->course->thumbnail_path)<img src="{{ Storage::disk('public')->url($enrollment->course->thumbnail_path) }}" alt="" class="h-40 w-full object-cover">@else<div class="flex h-40 items-center justify-center bg-brand-50 text-4xl font-bold text-brand-500 dark:bg-brand-500/10">{{ Str::upper(Str::substr($enrollment->course->title, 0, 1)) }}</div>@endif
        <div class="space-y-4 p-5"><div><x-ui.badge :color="$enrollment->status->value === 'completed' ? 'success' : 'primary'">{{ $enrollment->status->value }}</x-ui.badge><h2 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white">{{ $enrollment->course->title }}</h2><p class="mt-1 text-sm text-gray-500">{{ Str::limit($enrollment->course->short_description, 100) }}</p></div>
        <div><div class="mb-1 flex justify-between text-xs text-gray-500"><span>Progress</span><span>{{ $enrollment->progress_percentage }}%</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $enrollment->progress_percentage }}%"></div></div></div>
        @if($firstMaterial)<a href="{{ route('learning.courses.materials.show', [$enrollment, $firstMaterial]) }}" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $enrollment->started_at ? 'Continue learning' : 'Start course' }}</a>@else<span class="text-sm text-gray-500">Curriculum is being prepared.</span>@endif</div>
    </article>
@empty<div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-12 text-center dark:border-gray-700"><h2 class="font-semibold text-gray-800 dark:text-white">No active courses yet</h2><p class="mt-1 text-sm text-gray-500">Browse the catalog and apply for a published course.</p><a href="{{ route('learning.catalog.index') }}" class="mt-3 inline-flex text-sm font-medium text-brand-500">Browse course catalog</a></div>@endforelse
</div>
@endsection
