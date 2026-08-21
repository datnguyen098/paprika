@extends('admin.layouts.app')

@section('title', 'Thêm món ăn')

@section('content')
    <form method="POST" action="{{ route('admin.dishes.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.dishes.form')
    </form>
@endsection
