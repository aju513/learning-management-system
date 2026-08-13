@extends('layouts.app')
@section('content')
    @include('pages.admin.learning-materials._form-page', [
        'submitLabel' => 'Add material',
        'method' => 'POST',
        'action' => route(\App\Support\PortalRoute::name('learning-materials.store'), $chapter),
    ])
@endsection
