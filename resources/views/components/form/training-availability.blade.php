@props([
    'model' => null,
    'trainings' => collect(),
])

@php
    $scope = $model?->availability_scope?->value ?? $model?->availability_scope ?? 'all';
    $availableToAll = old('available_to_all', $scope !== 'training');
    $selectedTraining = old('required_training_key', $model?->required_training_key);
@endphp

<div x-data="{ availableToAll: @js((bool) $availableToAll), trainingKey: @js($selectedTraining), trainingLabels: @js($trainings->pluck('name', 'key')), modalOpen: false }" class="sm:col-span-2">
    <x-form.toggle
        name="available_to_all"
        label="Available to everyone"
        :checked="(bool) $availableToAll"
        help="Turn this off to require enrollment in a specific training first."
        x-on:change="availableToAll = $event.target.checked; if (! availableToAll) modalOpen = true"
    />

    <div x-show="! availableToAll" x-cloak class="mt-3 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-500/30 dark:bg-brand-500/10">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-gray-800 dark:text-white">Training enrollment required</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="trainingKey ? 'Selected training: ' + (trainingLabels[trainingKey] || trainingKey) : 'Choose the training a learner must already be enrolled in.'"></p>
            </div>
            <button type="button" @click="modalOpen = true" class="rounded-lg border border-brand-300 px-3 py-2 text-sm font-medium text-brand-600 hover:bg-white dark:border-brand-500/40 dark:text-brand-300 dark:hover:bg-white/5">Choose training</button>
        </div>
        <div class="mt-2 text-sm text-error-500">@error('required_training_key'){{ $message }}@enderror</div>
    </div>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="training-modal-title">
        <div @click.outside="modalOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="training-modal-title" class="text-lg font-semibold text-gray-800 dark:text-white">Select required training</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only learners enrolled in this training can access the content.</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-2xl leading-none text-gray-400" aria-label="Close">&times;</button>
            </div>
            <div class="mt-5">
                <x-form.select name="required_training_key" label="Training" :options="$trainings->pluck('name', 'key')" :value="$selectedTraining" placeholder="Select a training" x-model="trainingKey" x-bind:required="! availableToAll" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="modalOpen = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Cancel</button>
                <button type="button" @click="if (trainingKey) modalOpen = false" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white">Use training</button>
            </div>
        </div>
    </div>
</div>
