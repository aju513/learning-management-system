@extends('layouts.app')

@section('content')
<x-common.page-breadcrumb pageTitle="UI Kit" />
<div class="grid gap-6 lg:grid-cols-2">
    <x-common.component-card title="Buttons" desc="Use x-ui.button with semantic variants and sm/md sizes."><div class="flex flex-wrap gap-3"><x-ui.button variant="primary">Primary</x-ui.button><x-ui.button variant="secondary">Secondary</x-ui.button><x-ui.button variant="success">Success</x-ui.button><x-ui.button variant="danger">Danger</x-ui.button><x-ui.button variant="warning">Warning</x-ui.button><x-ui.button variant="info">Info</x-ui.button><x-ui.button variant="outline">Outline</x-ui.button><x-ui.button variant="primary" disabled>Disabled</x-ui.button></div></x-common.component-card>
    <x-common.component-card title="Badges" desc="Status and categorization labels."><div class="flex flex-wrap gap-2"><x-ui.badge color="primary">Primary</x-ui.badge><x-ui.badge color="success">Active</x-ui.badge><x-ui.badge color="warning">Pending</x-ui.badge><x-ui.badge color="error">Inactive</x-ui.badge></div></x-common.component-card>
    <x-common.component-card title="Alerts" desc="Feedback variants with optional custom slot content."><div class="space-y-3"><x-ui.alert variant="success" title="Saved" message="The operation completed successfully." /><x-ui.alert variant="warning" title="Review required" message="Check the supplied values before continuing." /><x-ui.alert variant="error" title="Unable to save" message="Correct the validation errors and try again." /></div></x-common.component-card>
    <x-common.component-card title="Form controls" desc="Compose CRUD forms from these reusable TailAdmin controls.">
        <div class="space-y-4">
            <x-form.input name="ui-kit-name" label="Text input" placeholder="Enter a value" />
            <x-form.select name="ui-kit-status" label="Dropdown" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="Select status" />
            <x-form.searchable-select name="ui-kit-category" label="Searchable select" :options="['one' => 'First option', 'two' => 'Second option', 'three' => 'Third option']" />
            <x-form.textarea name="ui-kit-description" label="Textarea" placeholder="Enter a description..." rows="3" />
            <x-form.date-picker name="ui-kit-date" label="Date picker" />
            <x-form.multiselect name="ui-kit-tags[]" label="Multiselect" :options="['one' => 'First option', 'two' => 'Second option', 'three' => 'Third option']" />
            <x-form.toggle name="ui-kit-active" label="Toggle" :checked="true" />
            <x-form.checkbox name="ui-kit-feature" value="reports" label="Checkbox" description="Enable reports for this record." />
            <x-form.file-upload name="ui-kit-files[]" label="File upload / dropzone" accept="image/png,image/jpeg" :multiple="true" :max-files="3" :max-size="5242880" />
            <x-form.editor name="ui-kit-body" label="Editor" />
        </div>
    </x-common.component-card>
</div>
@endsection
