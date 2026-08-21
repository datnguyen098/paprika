@extends('admin.layouts.app')

@section('title', 'Thêm bàn')

@section('content')
    <form method="POST" action="{{ route('admin.restaurant-tables.store') }}" class="admin-form-card">
        @csrf
        @include('admin.restaurant-tables.form')
    </form>
@endsection
