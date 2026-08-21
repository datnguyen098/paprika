@extends('admin.layouts.app')

@section('title', 'Thêm tài khoản')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.users.form')
    </form>
@endsection
