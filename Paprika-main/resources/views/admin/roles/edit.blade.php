@extends('admin.layouts.app')

@section('title', 'Sửa vai trò')

@section('content')
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.roles.form')
    </form>
@endsection
