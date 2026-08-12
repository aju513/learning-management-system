@php($moduleOptions = $courses->flatMap(fn($course) => $course->modules->map(fn($module) => ['id' => $module->id, 'name' => $course->title.' — '.$module->title])))
<div class="grid gap-5 sm:grid-cols-2">
    <x-form.input name="title" label="Assessment title" :value="$assessment->title" required />
    <x-form.select name="course_id" label="Course (optional)" :options="$courses" :value="$assessment->course_id" placeholder="Standalone assessment" />
    <x-form.select name="course_module_id" label="Module (optional)" :options="$moduleOptions" :value="$assessment->course_module_id" placeholder="No module / final assessment" help="Selecting a module automatically links its course." />
    <x-form.input name="duration_minutes" label="Duration (minutes)" type="number" min="1" max="1440" :value="$assessment->duration_minutes ?? 30" required />
    <x-form.input name="passing_percentage" label="Passing score (%)" type="number" min="0" max="100" step="0.01" :value="$assessment->passing_percentage ?? 60" required />
    <x-form.input name="max_attempts" label="Maximum attempts" type="number" min="1" max="20" :value="$assessment->max_attempts ?? 1" required />
    <x-form.input name="starts_at" label="Available from" type="datetime-local" :value="$assessment->starts_at?->format('Y-m-d\TH:i')" />
    <x-form.input name="ends_at" label="Available until" type="datetime-local" :value="$assessment->ends_at?->format('Y-m-d\TH:i')" />
    <div class="sm:col-span-2"><x-form.textarea name="description" label="Description" :value="$assessment->description" rows="3" /></div>
    <div class="sm:col-span-2"><x-form.textarea name="instructions" label="Instructions" :value="$assessment->instructions" rows="5" /></div>
    <x-form.toggle name="show_results" label="Show score immediately after submission" :checked="$assessment->show_results ?? true" />
</div>
<div class="flex justify-end gap-3"><a href="{{ $assessment->exists ? route(\App\Support\PortalRoute::name('assessments.show'), $assessment) : route(\App\Support\PortalRoute::name('assessments.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button></div>
