<x-common.component-card title="Dropzone" desc="Reusable drag-and-drop file input with client-side type and size feedback.">
    <x-form.file-upload name="demo_files[]" label="Upload images" accept="image/png,image/jpeg,image/webp,image/svg+xml" :multiple="true" :max-files="5" :max-size="5242880" help="The server must validate the files again before storing them." />
</x-common.component-card>
