@php
    $question ??= null;
    $questionTypeClass ??= \App\Enums\QuestionType::class;
    $existingOptions = old('options', $question?->options?->pluck('option_text')->all() ?? ['', '', '', '']);
    $existingCorrect = array_map('intval', old('correct_options', $question?->options?->values()->map(fn ($option, $index) => $option->is_correct ? $index : null)->filter(fn ($index) => $index !== null)->all() ?? []));
    $questionTypes = collect($questionTypeClass::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
@endphp
<div class="space-y-4">
    <x-form.textarea name="prompt" label="Question" :value="$question?->prompt" rows="3" required />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="type" label="Question type" :options="$questionTypes" :value="$question?->type?->value ?? 'single_choice'" required />
        <x-form.input name="marks" label="Marks" type="number" min="0.01" step="0.01" :value="$question?->marks ?? 1" required />
    </div>
    <fieldset>
        <legend class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Answer options (check every correct answer)</legend>
        <div class="space-y-2">
            @foreach($existingOptions as $index => $option)
                <div class="grid grid-cols-[auto_1fr] items-center gap-3">
                    <input type="checkbox" name="correct_options[]" value="{{ $index }}" @checked(in_array($index, $existingCorrect, true)) class="h-5 w-5 rounded border-gray-300 text-brand-500">
                    <x-form.input :id="'option-'.$index.'-'.($question?->id ?? 'new')" name="options[]" :value="$option" :label="'Option '.($index + 1)" required />
                </div>
            @endforeach
        </div>
    </fieldset>
</div>
