<div class="space-y-5">
    <x-form.input name="name" label="Category name" :value="$category->name" required />
    <x-form.textarea name="description" label="Description" :value="$category->description" rows="4" />
    <x-form.toggle name="is_active" label="Active category" :checked="$category->is_active ?? true" />
</div>
