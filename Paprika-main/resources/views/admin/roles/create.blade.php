@extends('admin.layouts.app')

@section('title', 'Thêm vai trò')

@section('content')
    <form method="POST" action="{{ route('admin.roles.store') }}" class="admin-form-card">
        @csrf
        @include('admin.roles.form')
    </form>
@endsection
