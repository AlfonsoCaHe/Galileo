@extends('layouts.default')

@if(auth()->user()->isAdmin())
    @include('gestion.layouts.header')
@endif

@section('content')
<div class="container mt-5 d-flex justify-content-center">
    <h1 class="texto">¡Bienvenido/a!</h1>
</div>
@endsection 