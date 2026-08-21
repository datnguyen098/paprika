@extends('admin.layouts.app')

@section('title', 'Sửa bàn')

@section('content')
    <form method="POST" action="{{ route('admin.restaurant-tables.update', $table) }}" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.restaurant-tables.form')
    </form>
@endsection
