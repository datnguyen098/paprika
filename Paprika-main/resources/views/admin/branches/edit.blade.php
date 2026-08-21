@extends('admin.layouts.app')

@section('title', 'Sửa cơ sở')

@section('content')
    <form method="POST" action="{{ route('admin.branches.update', $branch) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.branches.form')
    </form>
@endsection
