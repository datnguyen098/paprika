@extends('admin.layouts.app')

@section('title', 'Sửa món ăn')

@section('content')
    <form method="POST" action="{{ route('admin.dishes.update', $dish) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.dishes.form')
    </form>
@endsection
