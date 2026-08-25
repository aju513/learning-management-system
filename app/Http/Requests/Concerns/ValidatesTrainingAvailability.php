<?php

namespace App\Http\Requests\Concerns;

use App\Enums\AvailabilityScope;
use App\Services\Training\TrainingCatalogProviderInterface;
use Illuminate\Validation\Rule;

trait ValidatesTrainingAvailability
{
    protected function prepareTrainingAvailability(): void
    {
        $availableToAll = $this->has('available_to_all') ? $this->boolean('available_to_all') : true;
        $scope = $availableToAll
            ? AvailabilityScope::All->value
            : AvailabilityScope::Training->value;

        $this->merge([
            'available_to_all' => $availableToAll,
            'availability_scope' => $scope,
            'required_training_key' => $scope === AvailabilityScope::All->value
                ? null
                : $this->input('required_training_key'),
        ]);
    }

    /** @return array<string, mixed> */
    protected function trainingAvailabilityRules(): array
    {
        $catalog = app(TrainingCatalogProviderInterface::class)->all()->pluck('name', 'key')->all();

        return [
            'available_to_all' => ['required', 'boolean'],
            'availability_scope' => ['required', Rule::enum(AvailabilityScope::class)],
            'required_training_key' => [
                'nullable', 'string', 'max:100',
                Rule::requiredIf(fn (): bool => $this->input('availability_scope') === AvailabilityScope::Training->value),
                Rule::in(array_keys($catalog)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTrainingAvailability();
    }
}
