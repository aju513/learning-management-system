<div class="space-y-5">
    <x-form.input name="name" label="Category name" :value="$category->name" required />
    <x-form.textarea name="description" label="Description" :value="$category->description" rows="4" />
    <x-form.toggle name="is_active" label="Active category" :checked="$category->is_active ?? true" />
    <div class="flex justify-end gap-3">
        <a href="{{ route(\App\Support\PortalRoute::name('course-categories.index')) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">Cancel</a>
        <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">{{ $submitLabel }}</button>
    </div>
</div>
