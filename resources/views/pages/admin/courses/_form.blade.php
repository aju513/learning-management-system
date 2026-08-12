<div class="grid gap-5 sm:grid-cols-2">
    <x-form.input name="title" label="Course title" :value="$course->title" required />
    <x-form.select name="category_id" label="Category" :options="$categories" :value="$course->category_id" placeholder="Uncategorized" />
    @if($actor->can('courses.edit-any'))<x-form.select name="instructor_id" label="Instructor" :options="$instructors" :value="$course->instructor_id" placeholder="Select instructor" />@endif
    <x-form.select name="difficulty" label="Difficulty" :options="['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced']" :value="$course->difficulty ?? 'beginner'" required />
    <x-form.input name="estimated_duration_minutes" label="Estimated duration (minutes)" type="number" min="0" :value="$course->estimated_duration_minutes ?? 0" required />
    <x-form.select name="navigation_mode" label="Learning navigation" :options="['free' => 'Free navigation', 'sequential' => 'Sequential']" :value="$course->navigation_mode?->value ?? 'free'" required />
    <div class="sm:col-span-2"><x-form.textarea name="short_description" label="Short description" :value="$course->short_description" rows="3" required /></div>
    <div class="sm:col-span-2"><x-form.textarea name="description" label="Full description" :value="$course->description" rows="7" /></div>
    <div class="sm:col-span-2"><x-form.file-upload name="thumbnail" label="Course thumbnail" accept="image/*" :max-size="4194304" :help="$course->thumbnail_path ? 'Upload a new image to replace the current thumbnail.' : 'Optional image, up to 4 MB.'" /></div>
</div>
<div class="flex justify-end gap-3"><a href="{{ $course->exists ? route(\App\Support\PortalRoute::name('courses.show'), $course) : route(\App\Support\PortalRoute::name('courses.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a><button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button></div>
