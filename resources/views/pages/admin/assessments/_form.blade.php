<div class="grid gap-5 sm:grid-cols-2">
    <x-form.input name="title" label="Assessment title" :value="$assessment->title" required />
    <x-form.input name="duration_minutes" label="Duration (minutes)" type="number" min="1" max="1440" :value="$assessment->duration_minutes ?? 30" required />
    <x-form.input name="passing_percentage" label="Passing score (%)" type="number" min="0" max="100" step="0.01" :value="$assessment->passing_percentage ?? 60" required />
    <x-form.input name="credit_points" label="Passed-test credit points" type="number" min="0" step="0.01" :value="$assessment->credit_points ?? 0" required help="Credits available after a learner passes this test." />
    <x-form.input name="max_attempts" label="Maximum attempts" type="number" min="1" max="20" :value="$assessment->max_attempts ?? 1" required />
    <x-form.input name="starts_at" label="Available from" type="datetime-local" :value="$assessment->starts_at?->format('Y-m-d\TH:i')" />
    <x-form.input name="ends_at" label="Available until" type="datetime-local" :value="$assessment->ends_at?->format('Y-m-d\TH:i')" />
    <div class="sm:col-span-2"><x-form.textarea name="description" label="Description" :value="$assessment->description" rows="3" /></div>
    <div class="sm:col-span-2"><x-form.textarea name="instructions" label="Instructions" :value="$assessment->instructions" rows="5" /></div>
    <x-form.toggle name="show_results" label="Show score immediately after submission" :checked="$assessment->show_results ?? true" />
</div>
