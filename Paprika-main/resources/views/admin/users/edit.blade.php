@extends('admin.layouts.app')

@section('title', 'Sửa tài khoản')

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.users.form')
    </form>
@endsection
