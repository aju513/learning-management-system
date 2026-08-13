@extends('layouts.app')
@section('content')
    @include('pages.admin.learning-materials._form-page', [
        'submitLabel' => 'Save material',
        'method' => 'PUT',
        'action' => route(\App\Support\PortalRoute::name('learning-materials.update'), $material),
    ])
@endsection
